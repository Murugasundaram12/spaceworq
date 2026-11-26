<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Consultation;
use App\Models\PartnerInquiry;
use App\Models\Provider;
use App\Models\ReserveRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'metrics' => [
                    'customers_total' => User::query()->where('user_type', User::TYPE_CUSTOMER)->count(),
                    'providers_total' => Provider::query()->count(),
                    'providers_pending_kyc' => Provider::query()->where('kyc_status', Provider::KYC_PENDING)->count(),
                    'providers_verified' => Provider::query()->where('kyc_status', Provider::KYC_VERIFIED)->count(),
                    'workspaces_total' => Workspace::query()->count(),
                    'workspaces_pending_review' => Workspace::query()->where('status', Workspace::STATUS_IN_REVIEW)->count(),
                    'workspaces_published' => Workspace::query()->where('is_published', true)->count(),
                    'bookings_total' => Booking::query()->count(),
                    'bookings_paid' => Booking::query()->where('payment_status', Booking::PAYMENT_PAID)->count(),
                    'gross_paid_revenue' => round((float) Booking::query()->where('payment_status', Booking::PAYMENT_PAID)->sum('total_amount'), 2),
                    'reserve_requests_open' => ReserveRequest::query()->where('status', 'Open')->count(),
                    'consultations_open' => Consultation::query()->where('status', 'NEW')->count(),
                    'partner_inquiries_open' => PartnerInquiry::query()->where('status', 'NEW')->count(),
                    'support_tickets_open' => SupportTicket::query()->where('status', SupportTicket::STATUS_OPEN)->count(),
                ],
                'recent_audit_logs' => AuditLog::query()->with('actor:id,name,email')->latest('id')->limit(10)->get(),
            ],
        ]);
    }
}
