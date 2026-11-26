<?php

namespace App\Services\Public;

use App\Models\Consultation;
use App\Models\PartnerInquiry;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InquiryService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function storeConsultation(
        array $validated,
        ?User $actor = null,
        bool $rejectRecentDuplicate = false,
        ?Request $request = null
    ): Consultation {
        if ($rejectRecentDuplicate) {
            $duplicate = Consultation::query()
                ->whereRaw('LOWER(company_name) = ?', [strtolower(trim((string) $validated['company_name']))])
                ->where('phone', trim((string) $validated['phone']))
                ->whereRaw('LOWER(city_name) = ?', [strtolower(trim((string) $validated['city_name']))])
                ->where('status', 'NEW')
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'phone' => ['A recent consultation request for this organization and phone is already being processed.'],
                ]);
            }
        }

        $consultation = Consultation::query()->create(array_merge($validated, [
            'user_id' => $actor?->id,
            'status' => 'NEW',
        ]));

        $this->auditLogService->log('public.consultation_submitted', $actor, $consultation, [
            'city_name' => $consultation->city_name,
        ], null, $request);

        return $consultation;
    }

    public function storePartnerInquiry(
        array $validated,
        ?User $actor = null,
        bool $rejectRecentDuplicate = false,
        ?Request $request = null
    ): PartnerInquiry {
        if ($rejectRecentDuplicate) {
            $duplicate = PartnerInquiry::query()
                ->where('inquiry_type', $validated['inquiry_type'])
                ->whereRaw('LOWER(entity_name) = ?', [strtolower(trim((string) $validated['entity_name']))])
                ->whereRaw('LOWER(contact_email) = ?', [strtolower(trim((string) $validated['contact_email']))])
                ->where('status', 'NEW')
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'contact_email' => ['A recent partner submission with these details is already under review.'],
                ]);
            }
        }

        $inquiry = PartnerInquiry::query()->create(array_merge($validated, [
            'user_id' => $actor?->id,
            'status' => 'NEW',
        ]));

        $this->auditLogService->log('public.partner_inquiry_submitted', $actor, $inquiry, [
            'inquiry_type' => $inquiry->inquiry_type,
            'city_name' => $inquiry->city_name,
        ], null, $request);

        return $inquiry;
    }
}
