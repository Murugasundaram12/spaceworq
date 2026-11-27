@extends('layouts.adminpanel')

@section('title', 'Workspaces Moderation — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'workspaces')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Workspaces Approval &amp; Moderation</h1>
      <p class="wm-page-subtitle">Approve submitted provider listings, unpublish, reject, or suspend workspaces.</p>
    </div>
    <form method="GET" action="{{ route('admin.workspaces.index') }}" class="d-flex flex-wrap gap-2">
      <select name="approval_status" class="form-select form-select-sm" style="width:165px;">
        <option value="">All Approval States</option>
        @foreach ([\App\Models\Workspace::APPROVAL_PENDING, \App\Models\Workspace::APPROVAL_APPROVED, \App\Models\Workspace::APPROVAL_REJECTED] as $ap)
          <option value="{{ $ap }}" {{ request('approval_status') === $ap ? 'selected' : '' }}>{{ $ap }}</option>
        @endforeach
      </select>
      <select name="status" class="form-select form-select-sm" style="width:160px;">
        <option value="">All Lifecycle States</option>
        @foreach ([\App\Models\Workspace::STATUS_DRAFT, \App\Models\Workspace::STATUS_IN_REVIEW, \App\Models\Workspace::STATUS_ACTIVE, \App\Models\Workspace::STATUS_INACTIVE, \App\Models\Workspace::STATUS_SUSPENDED, \App\Models\Workspace::STATUS_REJECTED] as $st)
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
            <th>Workspace &amp; Provider</th>
            <th>Category &amp; City</th>
            <th>Status</th>
            <th>Approval</th>
            <th>Moderation Decision</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($workspaces as $ws)
            <tr>
              <td>
                <div class="fw-semibold">{{ $ws->name }}</div>
                <div class="small text-muted">{{ $ws->code }} · {{ $ws->provider?->company_name ?: 'Provider' }}</div>
              </td>
              <td>
                <div>{{ $ws->category?->name ?: '—' }}</div>
                <div class="small text-muted">{{ $ws->city?->name ?: '—' }}</div>
              </td>
              <td>
                <span class="wm-status-badge {{ $ws->status === 'ACTIVE' ? 'wm-status-active' : ($ws->status === 'In Review' ? 'wm-status-review' : 'wm-status-inactive') }}">
                  {{ $ws->status }}
                </span>
              </td>
              <td>
                <span class="wm-category-pill">{{ $ws->approval_status }}</span>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.workspaces.decision', $ws) }}" class="d-flex flex-wrap gap-2 align-items-center">
                  @csrf
                  <select name="decision" class="form-select form-select-sm" style="width:130px;">
                    <option value="APPROVE">APPROVE</option>
                    <option value="REJECT">REJECT</option>
                    <option value="SUSPEND">SUSPEND</option>
                    <option value="UNPUBLISH">UNPUBLISH</option>
                  </select>
                  <input type="text" name="notes" class="form-control form-control-sm" style="width:165px;" placeholder="Notes (required if reject)" />
                  <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No workspaces matched your filters.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $workspaces->withQueryString()->links() }}</div>
  </section>
@endsection
