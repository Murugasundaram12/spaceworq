<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\ProviderSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ProviderApprovalNotification;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProviderOnboardingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function updateProfile(Provider $provider, User $actor, array $validated, ?Request $request = null): Provider
    {
        if ($provider->status === Provider::STATUS_SUSPENDED) {
            throw new HttpException(403, 'Suspended provider accounts cannot modify onboarding details.');
        }

        DB::transaction(function () use ($provider, $actor, $validated): void {
            $providerFields = ['company_name', 'brand_name', 'business_email', 'business_phone', 'gstin', 'pan', 'city_id', 'address'];
            $updates = [];

            foreach ($providerFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $updates[$field] = $validated[$field];
                }
            }

            if ($updates !== []) {
                $updates['submitted_at'] = $provider->submitted_at ?? now();

                if ($provider->status === Provider::STATUS_REJECTED && ! empty($validated['resubmit_application'])) {
                    $updates['status'] = Provider::STATUS_PENDING;
                    $updates['status_reason'] = null;
                    $updates['status_updated_at'] = now();
                    $updates['status_updated_by'] = $actor->id;
                }

                $provider->forceFill($updates)->save();
            }

            if (! empty($validated['settings']) && is_array($validated['settings'])) {
                ProviderSetting::query()->updateOrCreate(
                    ['provider_id' => $provider->id],
                    $validated['settings']
                );
            }
        });

        $this->auditLogService->log('provider.profile_updated', $actor, $provider, [
            'resubmitted' => ! empty($validated['resubmit_application']),
        ], $provider->id, $request);

        return $provider->fresh(['settings', 'documents', 'city']);
    }

    public function uploadDocument(
        Provider $provider,
        User $actor,
        string $documentType,
        UploadedFile $file,
        ?Request $request = null
    ): ProviderDocument {
        if ($provider->status === Provider::STATUS_SUSPENDED) {
            throw new HttpException(403, 'Suspended provider accounts cannot upload KYC documents.');
        }

        $disk = (string) config('spaceworq.uploads.provider_documents_disk', 'local');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        $filename = Str::uuid()->toString() . '.' . $ext;
        $dir = "providers/{$provider->id}/kyc";
        $path = "{$dir}/{$filename}";

        Storage::disk($disk)->putFileAs($dir, $file, $filename);

        $doc = DB::transaction(function () use ($provider, $actor, $documentType, $file, $disk, $path): ProviderDocument {
            $doc = ProviderDocument::query()->create([
                'provider_id' => $provider->id,
                'document_type' => $documentType,
                'original_filename' => $file->getClientOriginalName(),
                'disk' => $disk,
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/pdf',
                'size_bytes' => $file->getSize() ?: 0,
                'status' => ProviderDocument::STATUS_PENDING,
            ]);

            $providerUpdates = [
                'submitted_at' => $provider->submitted_at ?? now(),
            ];

            if (in_array($provider->kyc_status, [Provider::KYC_NOT_SUBMITTED, Provider::KYC_REJECTED], true)) {
                $providerUpdates['kyc_status'] = Provider::KYC_PENDING;
            }

            if ($provider->status === Provider::STATUS_REJECTED) {
                $providerUpdates['status'] = Provider::STATUS_PENDING;
                $providerUpdates['status_reason'] = null;
                $providerUpdates['status_updated_at'] = now();
                $providerUpdates['status_updated_by'] = $actor->id;
            }

            $provider->forceFill($providerUpdates)->save();

            return $doc;
        });

        $this->auditLogService->log('provider.document_uploaded', $actor, $doc, [
            'document_type' => $doc->document_type,
        ], $provider->id, $request);

        return $doc;
    }

    public function downloadDocument(ProviderDocument $document, ?Provider $scopedProvider = null): StreamedResponse
    {
        if ($scopedProvider !== null && $document->provider_id !== $scopedProvider->id) {
            abort(403, 'Forbidden: KYC document belongs to another provider.');
        }

        if (! Storage::disk($document->disk)->exists($document->storage_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk($document->disk)->download($document->storage_path, $document->original_filename);
    }

    public function updateStatus(
        Provider $provider,
        User $admin,
        string $status,
        ?string $notes = null,
        ?Request $request = null
    ): Provider {
        DB::transaction(function () use ($provider, $admin, $status, $notes): void {
            // Note: updating provider status alone never alters kyc_status or bypasses KYC verification.
            $provider->forceFill([
                'status' => $status,
                'kyc_notes' => $notes ?? $provider->kyc_notes,
                'status_reason' => $notes ?? ($status === Provider::STATUS_ACTIVE ? null : $provider->status_reason),
                'status_updated_at' => now(),
                'status_updated_by' => $admin->id,
            ])->save();

            if (in_array($status, [Provider::STATUS_SUSPENDED, Provider::STATUS_REJECTED], true)) {
                Workspace::query()
                    ->where('provider_id', $provider->id)
                    ->update(['is_published' => false]);
            }
        });

        $this->auditLogService->log('admin.provider_status_updated', $admin, $provider, [
            'status' => $status,
            'notes' => $notes,
        ], $provider->id, $request);

        if ($provider->owner) {
            $provider->owner->notify(new ProviderApprovalNotification(
                'Provider Account',
                $provider->id,
                $provider->company_name,
                $status,
                $notes
            ));
        }

        return $provider->fresh();
    }

    public function reviewKyc(
        Provider $provider,
        User $admin,
        array $validated,
        ?Request $request = null
    ): Provider {
        DB::transaction(function () use ($provider, $admin, $validated): void {
            $kycStatus = $validated['kyc_status'];
            $notes = $validated['kyc_notes'] ?? null;

            $updates = [
                'kyc_status' => $kycStatus,
                'kyc_notes' => $notes ?? $provider->kyc_notes,
            ];

            if ($kycStatus === Provider::KYC_VERIFIED) {
                $updates['status'] = Provider::STATUS_ACTIVE;
                $updates['verified_at'] = now();
                $updates['verified_by'] = $admin->id;
                $updates['status_reason'] = $notes ?? $provider->status_reason;
                $updates['status_updated_at'] = now();
                $updates['status_updated_by'] = $admin->id;
            } elseif ($kycStatus === Provider::KYC_REJECTED) {
                $updates['status_reason'] = $notes ?? $provider->status_reason;
                $updates['status_updated_at'] = now();
                $updates['status_updated_by'] = $admin->id;
            }

            $provider->forceFill($updates)->save();

            foreach ($validated['document_decisions'] ?? [] as $docDecision) {
                ProviderDocument::query()
                    ->where('id', $docDecision['id'])
                    ->where('provider_id', $provider->id)
                    ->update([
                        'status' => $docDecision['status'],
                        'review_notes' => $docDecision['review_notes'] ?? null,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                    ]);
            }
        });

        $this->auditLogService->log('admin.provider_kyc_updated', $admin, $provider, [
            'kyc_status' => $validated['kyc_status'],
            'kyc_notes' => $validated['kyc_notes'] ?? null,
        ], $provider->id, $request);

        if ($provider->owner) {
            $provider->owner->notify(new ProviderApprovalNotification(
                'Provider KYC',
                $provider->id,
                $provider->company_name,
                $validated['kyc_status'],
                $validated['kyc_notes'] ?? null
            ));
        }

        return $provider->fresh();
    }
}
