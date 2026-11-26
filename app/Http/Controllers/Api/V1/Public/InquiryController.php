<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreConsultationRequest;
use App\Http\Requests\Public\StorePartnerInquiryRequest;
use App\Services\Public\InquiryService;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    public function __construct(
        private readonly InquiryService $inquiryService
    ) {}

    public function storeConsultation(StoreConsultationRequest $request): JsonResponse
    {
        $consultation = $this->inquiryService->storeConsultation(
            $request->validated(),
            $request->user(),
            false,
            $request
        );

        return response()->json([
            'message' => 'Enterprise inquiry received! Our dedicated manager will contact you shortly.',
            'data' => [
                'id' => $consultation->id,
                'status' => $consultation->status,
            ],
        ], 201);
    }

    public function storePartnerInquiry(StorePartnerInquiryRequest $request): JsonResponse
    {
        $inquiry = $this->inquiryService->storePartnerInquiry(
            $request->validated(),
            $request->user(),
            false,
            $request
        );

        return response()->json([
            'message' => 'Partner details submitted! Our team will review and reach out shortly.',
            'data' => [
                'id' => $inquiry->id,
                'inquiry_type' => $inquiry->inquiry_type,
                'status' => $inquiry->status,
            ],
        ], 201);
    }
}
