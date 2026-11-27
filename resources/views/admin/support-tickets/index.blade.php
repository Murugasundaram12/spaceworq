@extends('layouts.adminpanel')

@section('title', 'Support Tickets — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'support-tickets')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Support Tickets</h1>
      <p class="wm-page-subtitle">Manage user and partner support tickets across SpaceWorq.</p>
    </div>
    <form method="GET" action="{{ route('admin.support-tickets.index') }}" class="d-flex gap-2">
      <select name="status" class="form-select form-select-sm" style="width:170px;">
        <option value="">All Statuses</option>
        @foreach ([\App\Models\SupportTicket::STATUS_OPEN, \App\Models\SupportTicket::STATUS_IN_PROGRESS, \App\Models\SupportTicket::STATUS_RESOLVED, \App\Models\SupportTicket::STATUS_CLOSED] as $st)
          <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
    </form>
  </section>

  <section class="wm-table-card">
    <div class="wm-table-scroll">
      <table class="wm-table">
        <thead>
          <tr>
            <th>Code &amp; Subject</th>
            <th>Reporter</th>
            <th>Category</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($tickets as $ticket)
            <tr>
              <td>
                <div class="fw-semibold">{{ $ticket->subject }}</div>
                <div class="small text-muted">{{ $ticket->ticket_code }} · {{ \Illuminate\Support\Str::limit($ticket->message, 80) }}</div>
              </td>
              <td>
                <div>{{ $ticket->user?->name ?: '—' }}</div>
                <div class="small text-muted">{{ $ticket->user?->email }}</div>
              </td>
              <td><span class="wm-category-pill">{{ $ticket->category }}</span></td>
              <td>
                <span class="wm-status-badge {{ $ticket->status === 'OPEN' ? 'wm-status-review' : ($ticket->status === 'CLOSED' ? 'wm-status-inactive' : 'wm-status-active') }}">
                  {{ $ticket->status }}
                </span>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="d-flex gap-2 align-items-center">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="form-select form-select-sm" style="width:140px;">
                    @foreach ([\App\Models\SupportTicket::STATUS_OPEN, \App\Models\SupportTicket::STATUS_IN_PROGRESS, \App\Models\SupportTicket::STATUS_RESOLVED, \App\Models\SupportTicket::STATUS_CLOSED] as $st)
                      <option value="{{ $st }}" {{ $ticket->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="resolution_notes" value="{{ $ticket->resolution_notes }}" class="form-control form-control-sm" style="width:170px;" placeholder="Resolution notes" />
                  <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No support tickets found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $tickets->withQueryString()->links() }}</div>
  </section>
@endsection
