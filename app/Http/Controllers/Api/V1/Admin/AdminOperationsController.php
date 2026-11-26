<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentTransactionResource;
use App\Http\Resources\ReserveRequestResource;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\Coupon;
use App\Models\PartnerInquiry;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\ReserveRequest;
use App\Models\SupportTicket;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminOperationsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function customers(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = User::query()
            ->where('user_type', User::TYPE_CUSTOMER)
            ->with('customerProfile')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]);
            });
        }

        return UserResource::collection($query->paginate($perPage))->response();
    }

    public function updateCustomerStatus(User $user, Request $request): JsonResponse
    {
        if ($user->user_type !== User::TYPE_CUSTOMER) {
            abort(404, 'Customer account not found.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . User::STATUS_ACTIVE . ',' . User::STATUS_SUSPENDED . ',' . User::STATUS_INACTIVE],
        ]);

        $user->forceFill(['status' => $validated['status']])->save();

        $this->auditLogService->log('admin.customer_status_updated', $request->user(), $user, [
            'status' => $validated['status'],
        ], null, $request);

        return (new UserResource($user))->response();
    }

    public function bookings(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = Booking::query()
            ->with(['workspace', 'items', 'paymentTransactions'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', (string) $request->input('payment_status'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', (int) $request->input('provider_id'));
        }

        return BookingResource::collection($query->paginate($perPage))->response();
    }

    public function reserveRequests(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = ReserveRequest::query()->with(['workspace'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return ReserveRequestResource::collection($query->paginate($perPage))->response();
    }

    public function coupons(): JsonResponse
    {
        return response()->json(['data' => Coupon::query()->latest('id')->get()]);
    }

    public function storeCoupon(StoreCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon = Coupon::query()->create($validated);

        $this->auditLogService->log('admin.coupon_created', $request->user(), $coupon, [
            'code' => $coupon->code,
        ], null, $request);

        return response()->json(['data' => $coupon], 201);
    }

    public function updateCoupon(Coupon $coupon, StoreCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon->forceFill($validated)->save();

        $this->auditLogService->log('admin.coupon_updated', $request->user(), $coupon, [
            'code' => $coupon->code,
        ], null, $request);

        return response()->json(['data' => $coupon]);
    }

    public function deleteCoupon(Coupon $coupon, Request $request): JsonResponse
    {
        $coupon->delete();
        $this->auditLogService->log('admin.coupon_deleted', $request->user(), $coupon, ['code' => $coupon->code], null, $request);

        return response()->json(['message' => 'Coupon deleted.']);
    }

    public function payments(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $txs = PaymentTransaction::query()->latest('id')->paginate($perPage);

        return PaymentTransactionResource::collection($txs)->response();
    }

    public function webhookEvents(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $events = WebhookEvent::query()->latest('id')->paginate($perPage);

        return response()->json($events);
    }

    public function refunds(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));

        return response()->json(Refund::query()->with('booking:id,booking_reference')->latest('id')->paginate($perPage));
    }

    public function recordRefund(Booking $booking, Request $request): JsonResponse
    {
        if ($booking->payment_status !== Booking::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'payment_status' => ['Only paid bookings can have a refund recorded.'],
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $booking->total_amount],
            'gateway_refund_id' => ['nullable', 'string', 'max:120'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $refund = DB::transaction(function () use ($booking, $validated): Refund {
            $tx = $booking->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_CAPTURED)
                ->latest('id')
                ->firstOrFail();

            $refund = Refund::query()->create([
                'booking_id' => $booking->id,
                'payment_transaction_id' => $tx->id,
                'cancellation_record_id' => $booking->cancellationRecord?->id,
                'gateway_refund_id' => $validated['gateway_refund_id'] ?? null,
                'amount' => $validated['amount'],
                'status' => Refund::STATUS_PROCESSED,
                'reason' => $validated['reason'] ?? 'Admin processed refund',
                'processed_at' => now(),
            ]);

            $booking->forceFill(['payment_status' => Booking::PAYMENT_REFUNDED])->save();
            $tx->forceFill(['status' => PaymentTransaction::STATUS_REFUNDED])->save();

            return $refund;
        });

        $this->auditLogService->log('admin.refund_recorded', $request->user(), $refund, [
            'booking_id' => $booking->id,
            'amount' => $refund->amount,
        ], $booking->provider_id, $request);

        return response()->json(['data' => $refund], 201);
    }

    public function consultations(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = Consultation::query()->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return response()->json($query->paginate($perPage));
    }

    public function updateConsultation(Consultation $consultation, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:NEW,CONTACTED,QUALIFIED,CLOSED'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $consultation->forceFill(array_merge($validated, [
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]))->save();
        $this->auditLogService->log('admin.consultation_updated', $request->user(), $consultation, $validated, null, $request);

        return response()->json(['data' => $consultation]);
    }

    public function partnerInquiries(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = PartnerInquiry::query()->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('inquiry_type')) {
            $query->where('inquiry_type', (string) $request->input('inquiry_type'));
        }

        return response()->json($query->paginate($perPage));
    }

    public function updatePartnerInquiry(PartnerInquiry $inquiry, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:NEW,CONTACTED,ONBOARDING,CLOSED'],
            'admin_notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $inquiry->forceFill(array_merge($validated, [
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]))->save();
        $this->auditLogService->log('admin.partner_inquiry_updated', $request->user(), $inquiry, $validated, null, $request);

        return response()->json(['data' => $inquiry]);
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));
        $query = SupportTicket::query()->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return SupportTicketResource::collection($query->paginate($perPage))->response();
    }

    public function updateSupportTicket(SupportTicket $ticket, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    SupportTicket::STATUS_OPEN,
                    SupportTicket::STATUS_IN_PROGRESS,
                    SupportTicket::STATUS_RESOLVED,
                    SupportTicket::STATUS_CLOSED,
                ])
            ],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->forceFill([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $ticket->resolution_notes,
            'assigned_admin_id' => $request->user()->id,
        ])->save();

        $this->auditLogService->log('admin.support_ticket_updated', $request->user(), $ticket, [
            'status' => $ticket->status,
        ], null, $request);

        return (new SupportTicketResource($ticket))->response();
    }

    public function settings(): JsonResponse
    {
        return response()->json(['data' => SystemSetting::query()->orderBy('group')->orderBy('key')->get()]);
    }

    public function upsertSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'value' => ['nullable', 'string', 'max:2000'],
            'value_type' => ['required', 'string', 'in:string,boolean,integer,decimal,json'],
            'group' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $setting = SystemSetting::query()->updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => $validated['value'],
                'value_type' => $validated['value_type'],
                'group' => $validated['group'] ?? 'general',
                'description' => $validated['description'] ?? null,
                'is_public' => (bool) ($validated['is_public'] ?? false),
                'updated_by' => $request->user()->id,
            ]
        );

        $this->auditLogService->log('admin.setting_updated', $request->user(), $setting, [
            'key' => $setting->key,
            'value' => $setting->value,
        ], null, $request);

        return response()->json(['data' => $setting]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::query()->with('permissions:id,name')->get(),
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 30)));
        $query = AuditLog::query()->with(['actor:id,name,email'])->latest('id');

        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }
        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', (int) $request->input('actor_user_id'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', (int) $request->input('provider_id'));
        }

        return response()->json($query->paginate($perPage));
    }
}
