<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SupportFaqController extends Controller
{
    public function faqs(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'topic' => 'Track my booking',
                    'summary' => 'Track all bookings under Bookings with live booking reference and status.',
                ],
                [
                    'topic' => 'Payment issue',
                    'summary' => 'Failed transactions, double charges, and GST invoice requests can be escalated via support tickets.',
                ],
                [
                    'topic' => 'Cancel booking',
                    'summary' => 'Cancellation and refund eligibility follow configured platform policy rules.',
                ],
                [
                    'topic' => 'Report a problem',
                    'summary' => 'Report workspace discrepancies, facility unavailability, or staff issues.',
                ],
            ],
        ]);
    }
}
