<?php

namespace App\Services\Provider;

use App\Models\Booking;
use App\Models\Provider;
use App\Models\ReserveRequest;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderReportService
{
    public function reserveRequestsQuery(Provider $provider, array $filters): Builder
    {
        $query = ReserveRequest::query()
            ->where('provider_id', $provider->id)
            ->with(['workspace', 'customer', 'assignedStaff.user'])
            ->latest('id');

        if (! empty($filters['workspace_id'])) {
            $query->where('workspace_id', (int) $filters['workspace_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', (string) $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', (string) $filters['to_date']);
        }

        return $query;
    }

    public function instantBookingsQuery(Provider $provider, array $filters): Builder
    {
        $query = Booking::query()
            ->where('provider_id', $provider->id)
            ->with(['workspace', 'customer', 'assignedStaff.user'])
            ->latest('id');

        if (! empty($filters['workspace_id'])) {
            $query->where('workspace_id', (int) $filters['workspace_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', (string) $filters['payment_status']);
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('booking_date', '>=', (string) $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('booking_date', '<=', (string) $filters['to_date']);
        }

        return $query;
    }

    public function streamReserveRequestsCsv(Provider $provider, array $filters): StreamedResponse
    {
        $rows = $this->reserveRequestsQuery($provider, $filters)->limit(5000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request Code', 'Workspace Code', 'Workspace Name', 'Customer Name', 'Customer Email', 'Requested Seats', 'Move-in Date', 'Status', 'Reminders', 'Created At']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->request_code,
                    $row->workspace?->code ?? '',
                    $row->workspace?->name ?? '',
                    $row->customer?->name ?? '',
                    $row->customer?->email ?? '',
                    $row->requested_seats,
                    $row->move_in_date?->toDateString() ?? '',
                    $row->status,
                    $row->reminders_count,
                    $row->created_at?->toIso8601String() ?? '',
                ]);
            }

            fclose($out);
        }, 'reserve-requests-report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function streamInstantBookingsCsv(Provider $provider, array $filters): StreamedResponse
    {
        $rows = $this->instantBookingsQuery($provider, $filters)->limit(5000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Booking Ref', 'Workspace Code', 'Workspace Name', 'Customer Name', 'Booking Date', 'Slot', 'Seats', 'Total Amount', 'Currency', 'Status', 'Payment Status', 'Created At']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->booking_reference,
                    $row->workspace?->code ?? '',
                    $row->workspace?->name ?? '',
                    $row->contact_name,
                    $row->booking_date?->toDateString() ?? '',
                    $row->slot,
                    $row->seat_count,
                    $row->total_amount,
                    $row->currency,
                    $row->status,
                    $row->payment_status,
                    $row->created_at?->toIso8601String() ?? '',
                ]);
            }

            fclose($out);
        }, 'instant-booking-requests-report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
