@extends('layouts.adminpanel')

@section('title', 'Providers & KYC — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'providers')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Providers &amp; KYC</h1>
      <p class="wm-page-subtitle">Review provider onboarding profiles, verify KYC compliance, and approve partner accounts.</p>
    </div>
    <form method="GET" action="{{ route('admin.providers.index') }}" class="d-flex flex-wrap gap-2">
      <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:190px;" placeholder="Company, brand, email" />
      <select name="status" class="form-select form-select-sm" style="width:165px;">
        <option value="">All Statuses</option>
        @foreach (\App\Models\Provider::STATUS_LABELS as $code => $label)
          <option value="{{ $code }}" {{ request('status') === $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
        @endforeach
      </select>
      <select name="kyc_status" class="form-select form-select-sm" style="width:165px;">
        <option value="">All KYC Statuses</option>
        @foreach (\App\Models\Provider::KYC_STATUS_LABELS as $code => $label)
          <option value="{{ $code }}" {{ request('kyc_status') === $code ? 'selected' : '' }}>{{ $label }}</option>
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
            <th>Company &amp; Brand</th>
            <th>Owner</th>
            <th>City</th>
            <th>Documents</th>
            <th>Provider Status</th>
            <th>KYC Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($providers as $prov)
            <tr>
              <td>
                <div class="fw-semibold">{{ $prov->company_name }}</div>
                <div class="small text-muted">{{ $prov->brand_name ?: '—' }}</div>
              </td>
              <td>
                <div>{{ $prov->owner?->name ?: '—' }}</div>
                <div class="small text-muted">{{ $prov->owner?->email }}</div>
              </td>
              <td>{{ $prov->city?->name ?: '—' }}</td>
              <td>{{ $prov->documents_count ?? $prov->documents()->count() }}</td>
              <td>
                <span class="wm-status-badge {{ $prov->isApproved() ? 'wm-status-active' : ($prov->isPending() ? 'wm-status-review' : 'wm-status-inactive') }}">
                  {{ $prov->statusLabel() }}
                </span>
              </td>
              <td>
                <span class="wm-status-badge {{ $prov->kyc_status === \App\Models\Provider::KYC_VERIFIED ? 'wm-status-active' : ($prov->kyc_status === \App\Models\Provider::KYC_PENDING ? 'wm-status-review' : 'wm-status-inactive') }}">
                  {{ $prov->kycStatusLabel() }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('admin.providers.show', $prov) }}" class="btn btn-sm btn-primary">Review</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No providers matched your filters.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $providers->withQueryString()->links() }}</div>
  </section>
@endsection
