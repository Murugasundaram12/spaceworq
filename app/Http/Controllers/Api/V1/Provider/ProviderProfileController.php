<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreBankAccountRequest;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Http\Resources\ProviderBankAccountResource;
use App\Http\Resources\ProviderDocumentResource;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Models\ProviderBankAccount;
use App\Models\ProviderDocument;
use App\Services\AuditLogService;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderProfileController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProviderOnboardingService $onboardingService
    ) {}

    public function status(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('view', $provider);

        return response()->json([
            'data' => [
                'provider' => new ProviderResource($provider),
                'is_verified_and_active' => $provider->isVerifiedAndActive(),
                'can_access_operational_features' => $provider->isVerifiedAndActive(),
                'kyc_notes' => $provider->kyc_notes,
            ],
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $provider->load(['settings', 'documents', 'bankAccounts']);

        return response()->json([
            'data' => [
                'provider' => new ProviderResource($provider),
                'settings' => $provider->settings,
                'documents' => ProviderDocumentResource::collection($provider->documents),
                'bank_accounts' => ProviderBankAccountResource::collection($provider->bankAccounts),
            ],
        ]);
    }

    public function update(UpdateProviderProfileRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $this->onboardingService->updateProfile($provider, $request->user(), $request->validated(), $request);

        return $this->show($request);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $maxKb = (int) config('spaceworq.uploads.max_document_kb', 10240);
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:GST_CERTIFICATE,PAN_CARD,COI,TRADE_LICENSE,ADDRESS_PROOF,OTHER'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
        ]);

        $doc = $this->onboardingService->uploadDocument(
            $provider,
            $request->user(),
            $validated['document_type'],
            $validated['document'],
            $request
        );

        return (new ProviderDocumentResource($doc))
            ->response()
            ->setStatusCode(201);
    }

    public function downloadDocument(ProviderDocument $document, Request $request): StreamedResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        return $this->onboardingService->downloadDocument($document, $provider);
    }

    public function storeBankAccount(StoreBankAccountRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $validated = $request->validated();
        $rawNumber = preg_replace('/\s+/', '', (string) $validated['account_number']);
        $last4 = substr($rawNumber, -4);
        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        $account = DB::transaction(function () use ($provider, $validated, $rawNumber, $last4, $isPrimary): ProviderBankAccount {
            if ($isPrimary || $provider->bankAccounts()->count() === 0) {
                $provider->bankAccounts()->update(['is_primary' => false]);
                $isPrimary = true;
            }

            return ProviderBankAccount::query()->create([
                'provider_id' => $provider->id,
                'account_holder_name' => $validated['account_holder_name'],
                'bank_name' => $validated['bank_name'],
                'account_number_encrypted' => $rawNumber,
                'account_last4' => $last4,
                'ifsc_code' => strtoupper($validated['ifsc_code']),
                'upi_id' => $validated['upi_id'] ?? null,
                'is_primary' => $isPrimary,
                'is_verified' => false,
            ]);
        });

        $this->auditLogService->log('provider.bank_account_added', $request->user(), $account, [
            'account_last4' => $last4,
        ], $provider->id, $request);

        return (new ProviderBankAccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    public function deleteBankAccount(ProviderBankAccount $bankAccount, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        if ($bankAccount->provider_id !== $provider->id) {
            abort(403, 'Forbidden: bank account belongs to another provider.');
        }

        $bankAccount->delete();

        $this->auditLogService->log('provider.bank_account_deleted', $request->user(), $bankAccount, [], $provider->id, $request);

        return response()->json(['message' => 'Bank account removed.']);
    }
}
