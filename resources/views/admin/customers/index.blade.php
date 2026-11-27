@extends('layouts.adminpanel')

@section('title', 'Customers — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'customers')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Customers</h1>
      <p class="wm-page-subtitle">View registered customer accounts and manage active/suspended status.</p>
    </div>
    <form method="GET" action="{{ route('admin.customers.index') }}" class="d-flex flex-wrap gap-2">
      <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:210px;" placeholder="Search name or email" />
      <select name="status" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Statuses</option>
        <option value="{{ \App\Models\User::STATUS_ACTIVE }}" {{ request('status') === \App\Models\User::STATUS_ACTIVE ? 'selected' : '' }}>ACTIVE</option>
        <option value="{{ \App\Models\User::STATUS_SUSPENDED }}" {{ request('status') === \App\Models\User::STATUS_SUSPENDED ? 'selected' : '' }}>SUSPENDED</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
    </form>
  </section>

  <section class="wm-table-card">
    <div class="wm-table-scroll">
      <table class="wm-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact</th>
            <th>Company / GSTIN</th>
            <th>Joined</th>
            <th>Status</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($customers as $customer)
            <tr>
              <td>
                <div class="fw-semibold">{{ trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: $customer->name }}</div>
                <div class="small text-muted">#{{ $customer->id }}</div>
              </td>
              <td>
                <div>{{ $customer->email }}</div>
                <div class="small text-muted">{{ $customer->mobile ?: '—' }}</div>
              </td>
              <td>
                <div>{{ $customer->customerProfile?->company_name ?: '—' }}</div>
                <div class="small text-muted">{{ $customer->customerProfile?->gstin ?: '' }}</div>
              </td>
              <td class="small text-muted">{{ optional($customer->created_at)->format('M d, Y') }}</td>
              <td>
                <span class="wm-status-badge {{ $customer->status === \App\Models\User::STATUS_ACTIVE ? 'wm-status-active' : 'wm-status-inactive' }}">
                  {{ $customer->status }}
                </span>
              </td>
              <td class="text-end">
                <form method="POST" action="{{ route('admin.customers.status', $customer) }}" class="d-inline">
                  @csrf
                  @method('PATCH')
                  @if ($customer->status === \App\Models\User::STATUS_ACTIVE)
                    <input type="hidden" name="status" value="{{ \App\Models\User::STATUS_SUSPENDED }}" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">Suspend</button>
                  @else
                    <input type="hidden" name="status" value="{{ \App\Models\User::STATUS_ACTIVE }}" />
                    <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                  @endif
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">No customers found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">
      {{ $customers->withQueryString()->links() }}
    </div>
  </section>
@endsection
