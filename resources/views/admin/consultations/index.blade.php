@extends('layouts.adminpanel')

@section('title', 'Consultations — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'consultations')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Enterprise Consultations</h1>
      <p class="wm-page-subtitle">Follow up and update status on public consultation requests.</p>
    </div>
    <form method="GET" action="{{ route('admin.consultations.index') }}" class="d-flex gap-2">
      <select name="status" class="form-select form-select-sm" style="width:160px;">
        <option value="">All Statuses</option>
        @foreach (['NEW', 'CONTACTED', 'QUALIFIED', 'CLOSED'] as $st)
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
            <th>Requester &amp; Company</th>
            <th>Phone &amp; Email</th>
            <th>City &amp; Seats</th>
            <th>Status</th>
            <th>Update Lead</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($consultations as $item)
            <tr>
              <td>
                <div class="fw-semibold">{{ $item->name }}</div>
                <div class="small text-muted">{{ $item->company_name }}</div>
              </td>
              <td>
                <div>{{ $item->phone }}</div>
                <div class="small text-muted">{{ $item->email ?: '—' }}</div>
              </td>
              <td>
                <div>{{ $item->city_name }}</div>
                <div class="small text-muted">{{ $item->seats_range ?: 'Custom' }}</div>
              </td>
              <td>
                <span class="wm-status-badge {{ $item->status === 'NEW' ? 'wm-status-review' : ($item->status === 'CLOSED' ? 'wm-status-inactive' : 'wm-status-active') }}">
                  {{ $item->status }}
                </span>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.consultations.update', $item) }}" class="d-flex gap-2 align-items-center">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="form-select form-select-sm" style="width:130px;">
                    @foreach (['NEW', 'CONTACTED', 'QUALIFIED', 'CLOSED'] as $st)
                      <option value="{{ $st }}" {{ $item->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="notes" value="{{ $item->notes }}" class="form-control form-control-sm" style="width:160px;" placeholder="Internal notes" />
                  <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No consultation inquiries found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $consultations->withQueryString()->links() }}</div>
  </section>
@endsection
