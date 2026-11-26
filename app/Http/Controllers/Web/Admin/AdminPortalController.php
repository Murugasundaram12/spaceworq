<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Consultation;
use App\Models\Locality;
use App\Models\PartnerInquiry;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceApprovalHistory;
use App\Models\WorkspaceReview;
use App\Notifications\ProviderApprovalNotification;
use App\Services\Admin\ReviewModerationService;
use App\Services\AuditLogService;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPortalController extends Controller
{
    public function __construct(
        private readonly ProviderOnboardingService $onboardingService,
        private readonly ReviewModerationService $reviewModerationService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function dashboard(): View|Response
    {
        return $this->renderPortalView('admin.dashboard', [
            'metrics' => [
                'customers_total' => User::query()->where('user_type', User::TYPE_CUSTOMER)->count(),
                'providers_total' => Provider::query()->count(),
                'providers_pending' => Provider::query()->where('status', Provider::STATUS_PENDING)->count(),
                'providers_approved' => Provider::query()->where('status', Provider::STATUS_ACTIVE)->count(),
                'providers_rejected' => Provider::query()->where('status', Provider::STATUS_REJECTED)->count(),
                'providers_suspended' => Provider::query()->where('status', Provider::STATUS_SUSPENDED)->count(),
                'providers_kyc_pending' => Provider::query()->where('kyc_status', Provider::KYC_PENDING)->count(),
                'workspaces_total' => Workspace::query()->count(),
                'workspaces_pending_review' => Workspace::query()->where('status', Workspace::STATUS_IN_REVIEW)->count(),
                'workspaces_published' => Workspace::query()->where('is_published', true)->count(),
                'consultations_open' => Consultation::query()->where('status', 'NEW')->count(),
                'partner_inquiries_open' => PartnerInquiry::query()->where('status', 'NEW')->count(),
                'reviews_total' => WorkspaceReview::query()->count(),
            ],
            'recentAuditLogs' => AuditLog::query()->with('actor:id,name,email')->latest('id')->limit(10)->get(),
        ]);
    }

    public function providers(Request $request): View|Response
    {
        $query = Provider::query()
            ->with(['city:id,name,slug', 'owner:id,name,email,mobile'])
            ->withCount(['workspaces', 'documents'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', (string) $request->input('kyc_status'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(brand_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(business_email) LIKE ?', [$term]);
            });
        }

        return $this->renderPortalView('admin.providers.index', [
            'providers' => $query->paginate(20),
        ]);
    }

    public function showProvider(Provider $provider): View|Response
    {
        $provider->load(['owner:id,name,email,mobile,status', 'city:id,name,slug', 'settings', 'documents', 'statusUpdatedBy:id,name,email']);

        return $this->renderPortalView('admin.providers.show', [
            'provider' => $provider,
        ]);
    }

    public function updateProviderStatus(Provider $provider, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                Provider::STATUS_PENDING,
                Provider::STATUS_ACTIVE,
                Provider::STATUS_SUSPENDED,
                Provider::STATUS_REJECTED,
            ])],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,' . Provider::STATUS_REJECTED . ',' . Provider::STATUS_SUSPENDED,
            ],
        ]);

        $updated = $this->onboardingService->updateStatus(
            $provider,
            $request->user(),
            $validated['status'],
            $validated['notes'] ?? null,
            $request
        );

        $message = "Provider status updated to {$updated->statusLabel()}.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'status' => $updated->status]);
        }

        return back()->with('status', $message);
    }

    public function verifyProviderKyc(Provider $provider, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kyc_status' => ['required', 'string', 'in:' . implode(',', [
                Provider::KYC_PENDING,
                Provider::KYC_VERIFIED,
                Provider::KYC_REJECTED,
            ])],
            'kyc_notes' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:kyc_status,' . Provider::KYC_REJECTED,
            ],
            'document_decisions' => ['nullable', 'array'],
            'document_decisions.*.id' => ['required', 'integer', 'exists:provider_documents,id'],
            'document_decisions.*.status' => ['required', 'string', 'in:' . ProviderDocument::STATUS_APPROVED . ',' . ProviderDocument::STATUS_REJECTED],
            'document_decisions.*.review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->onboardingService->reviewKyc($provider, $request->user(), $validated, $request);
        $message = "Provider KYC updated to {$updated->kycStatusLabel()}.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'kyc_status' => $updated->kyc_status,
                'status' => $updated->status,
            ]);
        }

        return back()->with('status', $message);
    }

    public function downloadProviderDocument(ProviderDocument $document): StreamedResponse
    {
        return $this->onboardingService->downloadDocument($document);
    }

    public function workspaces(Request $request): View|Response
    {
        $query = Workspace::query()
            ->with(['provider:id,company_name,kyc_status,status', 'category:id,code,name', 'city:id,name'])
            ->latest('id');

        if ($request->filled('approval_status')) {
            $query->where('approval_status', (string) $request->input('approval_status'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return $this->renderPortalView('admin.workspaces.index', [
            'workspaces' => $query->paginate(20),
        ]);
    }

    public function decideWorkspace(Workspace $workspace, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:APPROVE,REJECT,SUSPEND,UNPUBLISH'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:decision,REJECT,SUSPEND'],
        ]);

        $workspace->loadMissing('provider.owner');
        $decision = $validated['decision'];

        if ($decision === 'APPROVE') {
            if (! $workspace->provider || ! $workspace->provider->isVerifiedAndActive()) {
                throw ValidationException::withMessages([
                    'provider' => ['Provider account must be ACTIVE and KYC VERIFIED before approving a workspace.'],
                ]);
            }
        }

        DB::transaction(function () use ($workspace, $decision, $validated, $request): void {
            $fromStatus = $workspace->status;
            $updates = match ($decision) {
                'APPROVE' => [
                    'status' => Workspace::STATUS_ACTIVE,
                    'approval_status' => Workspace::APPROVAL_APPROVED,
                    'is_published' => true,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ],
                'REJECT' => [
                    'status' => Workspace::STATUS_REJECTED,
                    'approval_status' => Workspace::APPROVAL_REJECTED,
                    'is_published' => false,
                ],
                'SUSPEND' => [
                    'status' => Workspace::STATUS_SUSPENDED,
                    'is_published' => false,
                ],
                'UNPUBLISH' => [
                    'status' => Workspace::STATUS_INACTIVE,
                    'is_published' => false,
                ],
            };

            $updates['updated_by'] = $request->user()->id;
            $workspace->forceFill($updates)->save();

            WorkspaceApprovalHistory::query()->create([
                'workspace_id' => $workspace->id,
                'actor_user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $updates['status'],
                'notes' => $validated['notes'] ?? "Workspace decision: {$decision}",
            ]);
        });

        $this->auditLogService->log('admin.workspace_' . strtolower($decision), $request->user(), $workspace, [
            'decision' => $decision,
            'notes' => $validated['notes'] ?? null,
        ], $workspace->provider_id, $request);

        if ($workspace->provider?->owner) {
            $workspace->provider->owner->notify(new ProviderApprovalNotification(
                'Workspace',
                $workspace->id,
                $workspace->name,
                $decision,
                $validated['notes'] ?? null
            ));
        }

        $message = "Workspace decision [{$decision}] recorded.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'status' => $workspace->fresh()->status]);
        }

        return back()->with('status', $message);
    }

    public function customers(Request $request): View|Response
    {
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

        return $this->renderPortalView('admin.customers.index', [
            'customers' => $query->paginate(20),
        ]);
    }

    public function updateCustomerStatus(User $user, Request $request): JsonResponse|RedirectResponse
    {
        if ($user->user_type !== User::TYPE_CUSTOMER) {
            abort(404, 'Customer account not found.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . User::STATUS_ACTIVE . ',' . User::STATUS_SUSPENDED],
        ]);

        $user->forceFill(['status' => $validated['status']])->save();
        if ($validated['status'] === User::STATUS_SUSPENDED) {
            $user->tokens()->delete();
        }

        $this->auditLogService->log('admin.customer_status_updated', $request->user(), $user, [
            'status' => $validated['status'],
        ], null, $request);

        $message = 'Customer account status updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function consultations(Request $request): View|Response
    {
        $query = Consultation::query()->with('handler:id,name,email')->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return $this->renderPortalView('admin.consultations.index', [
            'consultations' => $query->paginate(20),
        ]);
    }

    public function updateConsultation(Consultation $consultation, Request $request): JsonResponse|RedirectResponse
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
        $message = 'Consultation updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => $consultation]);
        }

        return back()->with('status', $message);
    }

    public function partnerInquiries(Request $request): View|Response
    {
        $query = PartnerInquiry::query()->with('handler:id,name,email')->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('inquiry_type')) {
            $query->where('inquiry_type', (string) $request->input('inquiry_type'));
        }

        return $this->renderPortalView('admin.partner-inquiries.index', [
            'inquiries' => $query->paginate(20),
        ]);
    }

    public function updatePartnerInquiry(PartnerInquiry $inquiry, Request $request): JsonResponse|RedirectResponse
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
        $message = 'Partner inquiry updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => $inquiry]);
        }

        return back()->with('status', $message);
    }

    public function supportTickets(Request $request): View|Response
    {
        $query = SupportTicket::query()->with(['user:id,name,email', 'assignedAdmin:id,name,email'])->latest('id');
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return $this->renderPortalView('admin.support-tickets.index', [
            'tickets' => $query->paginate(20),
        ]);
    }

    public function updateSupportTicket(SupportTicket $ticket, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', [
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_IN_PROGRESS,
                SupportTicket::STATUS_RESOLVED,
                SupportTicket::STATUS_CLOSED,
            ])],
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

        $message = 'Support ticket updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function reviews(Request $request): View|Response
    {
        return $this->renderPortalView('admin.reviews.index', [
            'reviews' => $this->reviewModerationService->paginate($request),
        ]);
    }

    public function moderateReview(WorkspaceReview $review, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
            'moderation_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->reviewModerationService->moderate(
            $review,
            $request->user(),
            (bool) $validated['is_visible'],
            $validated['moderation_notes'] ?? null,
            $request
        );

        $message = 'Workspace review moderation updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => $updated]);
        }

        return back()->with('status', $message);
    }

    public function masterData(): View|Response
    {
        return $this->renderPortalView('admin.master-data.index', [
            'cities' => City::query()->orderBy('sort_order')->get(),
            'localities' => Locality::query()->with('city:id,name')->orderBy('name')->get(),
            'amenities' => Amenity::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCity(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:cities,slug'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $city = City::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'India',
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->auditLogService->log('admin.city_created', $request->user(), $city, ['name' => $city->name], null, $request);
        $message = 'City created.';

        return $request->expectsJson() ? response()->json(['message' => $message, 'data' => $city], 201) : back()->with('status', $message);
    }

    public function updateCity(City $city, Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('cities', 'slug')->ignore($city->id)],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $city->forceFill($validated)->save();
        $this->auditLogService->log('admin.city_updated', $request->user(), $city, [], null, $request);
        $message = 'City updated.';

        return $request->expectsJson() ? response()->json(['message' => $message, 'data' => $city]) : back()->with('status', $message);
    }

    public function auditLogs(Request $request): View|Response
    {
        $query = AuditLog::query()->with(['actor:id,name,email'])->latest('id');
        if ($request->filled('action')) {
            $query->where('action', (string) $request->input('action'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', (int) $request->input('provider_id'));
        }

        return $this->renderPortalView('admin.audit-logs.index', [
            'auditLogs' => $query->paginate(30),
        ]);
    }
}
