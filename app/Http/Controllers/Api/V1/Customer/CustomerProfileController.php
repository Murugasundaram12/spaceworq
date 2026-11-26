<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateSupportTicketRequest;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\SupportTicket;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerProfileController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('customerProfile.preferredCity');

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'profile' => $user->customerProfile ? [
                    'company_name' => $user->customerProfile->company_name,
                    'gstin' => $user->customerProfile->gstin,
                    'preferred_city_id' => $user->customerProfile->preferred_city_id,
                    'billing_address' => $user->customerProfile->billing_address,
                ] : null,
            ],
        ]);
    }

    public function update(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $userUpdates = [];
            foreach (['first_name', 'last_name', 'mobile'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $userUpdates[$field] = $validated[$field];
                }
            }

            if ($userUpdates !== []) {
                $firstName = $userUpdates['first_name'] ?? $user->first_name;
                $lastName = $userUpdates['last_name'] ?? $user->last_name;
                $userUpdates['name'] = trim("{$firstName} {$lastName}");
                $user->forceFill($userUpdates)->save();
            }

            $profileFields = ['company_name', 'gstin', 'preferred_city_id', 'billing_address'];
            $profileUpdates = [];
            foreach ($profileFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $profileUpdates[$field] = $validated[$field];
                }
            }

            if ($profileUpdates !== []) {
                CustomerProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileUpdates
                );
            }
        });

        $this->auditLogService->log('customer.profile_updated', $user, $user, [], null, $request);

        return $this->show($request);
    }

    public function notifications(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));
        $notifications = $request->user()->notifications()->latest()->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markNotificationRead(string $notificationId, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function supportTickets(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));
        $tickets = $request->user()->supportTickets()->latest('id')->paginate($perPage);

        return SupportTicketResource::collection($tickets)->response();
    }

    public function storeSupportTicket(CreateSupportTicketRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! empty($validated['booking_id'])) {
            $booking = Booking::query()->find($validated['booking_id']);
            if (! $booking || $booking->customer_user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'booking_id' => ['You can only reference your own booking.'],
                ]);
            }
        }

        $ticket = SupportTicket::query()->create([
            'ticket_code' => 'SUP-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'booking_id' => $validated['booking_id'] ?? null,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $this->auditLogService->log('support_ticket.created', $user, $ticket, [
            'ticket_code' => $ticket->ticket_code,
        ], null, $request);

        return (new SupportTicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    public function showSupportTicket(SupportTicket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        return (new SupportTicketResource($ticket))->response();
    }
}
