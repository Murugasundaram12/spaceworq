<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderBankAccountResource;
use App\Http\Resources\ProviderDocumentResource;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminProviderController extends Controller
{
    public function __construct(
        private readonly ProviderOnboardingService $onboardingService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));

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
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->input('city_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(company_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(brand_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(business_email) LIKE ?', [$term]);
            });
        }

        return ProviderResource::collection($query->paginate($perPage))->response();
    }

    public function show(Provider $provider): JsonResponse
    {
        $provider->load(['owner:id,name,email,mobile,status', 'city:id,name,slug', 'settings', 'documents', 'bankAccounts']);

        return response()->json([
            'data' => [
                'provider' => new ProviderResource($provider),
                'owner' => $provider->owner,
                'settings' => $provider->settings,
                'documents' => ProviderDocumentResource::collection($provider->documents),
                'bank_accounts' => ProviderBankAccountResource::collection($provider->bankAccounts),
            ],
        ]);
    }

    public function updateStatus(Provider $provider, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    Provider::STATUS_PENDING,
                    Provider::STATUS_ACTIVE,
                    Provider::STATUS_SUSPENDED,
                    Provider::STATUS_REJECTED,
                ])
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->onboardingService->updateStatus(
            $provider,
            $request->user(),
            $validated['status'],
            $validated['notes'] ?? null,
            $request
        );

        return (new ProviderResource($updated))->response();
    }

    public function verifyKyc(Provider $provider, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kyc_status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    Provider::KYC_PENDING,
                    Provider::KYC_VERIFIED,
                    Provider::KYC_REJECTED,
                ])
            ],
            'kyc_notes' => ['nullable', 'string', 'max:1000'],
            'document_decisions' => ['nullable', 'array'],
            'document_decisions.*.id' => ['required', 'integer', 'exists:provider_documents,id'],
            'document_decisions.*.status' => ['required', 'string', 'in:' . ProviderDocument::STATUS_APPROVED . ',' . ProviderDocument::STATUS_REJECTED],
            'document_decisions.*.review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->onboardingService->reviewKyc($provider, $request->user(), $validated, $request);

        return (new ProviderResource($updated))->response();
    }

    public function downloadDocument(ProviderDocument $document): StreamedResponse
    {
        return $this->onboardingService->downloadDocument($document);
    }
}
