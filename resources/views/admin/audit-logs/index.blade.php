@extends('layouts.adminpanel')

@section('title', 'Audit Logs — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'audit-logs')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Audit Logs</h1>
      <p class="wm-page-subtitle">Immutable trail of authentication, onboarding, KYC, and workspace moderation events.</p>
    </div>
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="d-flex gap-2">
      <input type="text" name="action" value="{{ request('action') }}" class="form-control form-control-sm" style="width:200px;" placeholder="Filter by action" />
      <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
    </form>
  </section>

  <section class="wm-table-card">
    <div class="wm-table-scroll">
      <table class="wm-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Actor</th>
            <th>Action</th>
            <th>Target</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($auditLogs as $log)
            <tr>
              <td class="small text-muted">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
              <td>
                <div class="fw-semibold">{{ $log->actor?->email ?? 'System' }}</div>
                <div class="small text-muted">{{ $log->actor_role ?: '—' }}</div>
              </td>
              <td><span class="wm-category-pill">{{ $log->action }}</span></td>
              <td class="small text-muted">{{ $log->auditable_type ? class_basename($log->auditable_type) . ' #' . $log->auditable_id : '—' }}</td>
              <td class="small text-muted">{{ $log->ip_address ?: '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No audit logs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $auditLogs->withQueryString()->links() }}</div>
  </section>
@endsection
