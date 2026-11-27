@extends('layouts.adminpanel')

@section('title', 'Partner Inquiries — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'partner-inquiries')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Partner Inquiries</h1>
      <p class="wm-page-subtitle">Workspace provider, building owner, and commercial developer leads.</p>
    </div>
    <form method="GET" action="{{ route('admin.partner-inquiries.index') }}" class="d-flex flex-wrap gap-2">
      <select name="inquiry_type" class="form-select form-select-sm" style="width:190px;">
        <option value="">All Partner Types</option>
        @foreach (\App\Models\PartnerInquiry::TYPES as $tp)
          <option value="{{ $tp }}" {{ request('inquiry_type') === $tp ? 'selected' : '' }}>{{ $tp }}</option>
        @endforeach
      </select>
      <select name="status" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Statuses</option>
        @foreach (['NEW', 'CONTACTED', 'ONBOARDING', 'CLOSED'] as $st)
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
            <th>Entity &amp; Type</th>
            <th>Location</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($inquiries as $inq)
            <tr>
              <td>
                <div class="fw-semibold">{{ $inq->entity_name }}</div>
                <span class="wm-category-pill">{{ $inq->inquiry_type }}</span>
              </td>
              <td>
                <div>{{ $inq->city_name }}</div>
                <div class="small text-muted">{{ $inq->locality ?: '—' }}</div>
              </td>
              <td>
                <div>{{ $inq->contact_name }}</div>
                <div class="small text-muted">{{ $inq->contact_phone }} · {{ $inq->contact_email }}</div>
              </td>
              <td>
                <span class="wm-status-badge {{ $inq->status === 'NEW' ? 'wm-status-review' : ($inq->status === 'CLOSED' ? 'wm-status-inactive' : 'wm-status-active') }}">
                  {{ $inq->status }}
                </span>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.partner-inquiries.update', $inq) }}" class="d-flex gap-2 align-items-center">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="form-select form-select-sm" style="width:130px;">
                    @foreach (['NEW', 'CONTACTED', 'ONBOARDING', 'CLOSED'] as $st)
                      <option value="{{ $st }}" {{ $inq->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="admin_notes" value="{{ $inq->admin_notes }}" class="form-control form-control-sm" style="width:160px;" placeholder="Admin notes" />
                  <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No partner inquiries found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $inquiries->withQueryString()->links() }}</div>
  </section>
@endsection
