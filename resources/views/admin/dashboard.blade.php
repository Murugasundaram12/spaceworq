@extends('layouts.adminpanel')

@section('title', 'Super Admin Dashboard — SpaceWorq')
@section('portal_mode', 'admin')
@section('active_menu', 'dashboard')

@section('content')
  <section class="welcome-banner" aria-label="Super Admin Dashboard Header">
    <div>
      <h1 class="welcome-title">Welcome, {{ auth('web')->user()?->first_name ?: 'Super Admin' }}</h1>
      <p class="welcome-subtitle">SpaceWorq Platform Administration &amp; Governance Portal</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('admin.providers.index', ['status' => \App\Models\Provider::STATUS_PENDING]) }}" class="btn btn-primary btn-sm px-3 fw-semibold">
        Pending Providers ({{ $metrics['providers_pending'] ?? 0 }})
      </a>
      <a href="{{ route('admin.workspaces.index', ['approval_status' => 'PENDING']) }}" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
        Workspaces In Review ({{ $metrics['workspaces_pending_review'] ?? 0 }})
      </a>
    </div>
  </section>

  <section aria-label="Platform metrics" class="mb-4">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Registered Customers</span>
            <span class="kpi-icon-badge kpi-icon-indigo" aria-hidden="true">C</span>
          </div>
          <div class="kpi-value">{{ $metrics['customers_total'] ?? 0 }}</div>
          <p class="kpi-caption">Active &amp; registered accounts</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Approved Providers</span>
            <span class="kpi-icon-badge kpi-icon-emerald" aria-hidden="true">P</span>
          </div>
          <div class="kpi-value">{{ $metrics['providers_approved'] ?? 0 }}</div>
          <p class="kpi-caption">{{ $metrics['providers_pending'] ?? 0 }} pending · {{ $metrics['providers_kyc_pending'] ?? 0 }} KYC under review</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Total Workspaces</span>
            <span class="kpi-icon-badge kpi-icon-amber" aria-hidden="true">W</span>
          </div>
          <div class="kpi-value">{{ $metrics['workspaces_total'] ?? 0 }}</div>
          <p class="kpi-caption">{{ $metrics['workspaces_published'] ?? 0 }} published · {{ $metrics['workspaces_pending_review'] ?? 0 }} in review</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Open Consultations</span>
            <span class="kpi-icon-badge kpi-icon-sky" aria-hidden="true">Q</span>
          </div>
          <div class="kpi-value">{{ $metrics['consultations_open'] ?? 0 }}</div>
          <p class="kpi-caption">New enterprise requests</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">New Partner Inquiries</span>
            <span class="kpi-icon-badge kpi-icon-violet" aria-hidden="true">I</span>
          </div>
          <div class="kpi-value">{{ $metrics['partner_inquiries_open'] ?? 0 }}</div>
          <p class="kpi-caption">Providers, building owners &amp; developers</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Workspace Reviews</span>
            <span class="kpi-icon-badge kpi-icon-indigo" aria-hidden="true">R</span>
          </div>
          <div class="kpi-value">{{ $metrics['reviews_total'] ?? 0 }}</div>
          <p class="kpi-caption">Customer review submissions</p>
        </article>
      </div>
    </div>
  </section>

  <section class="row g-4 align-items-stretch">
    <div class="col-12 col-lg-8">
      <div class="surface-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="section-card-title mb-0">Recent Audit Activity</h2>
          <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-primary">View All Logs</a>
        </div>
        <ul class="workspace-list">
          @forelse ($recentAuditLogs as $log)
            <li class="workspace-list-item">
              <div>
                <div class="workspace-name">{{ $log->action }}</div>
                <div class="small text-muted">{{ $log->actor?->email ?? 'System' }} · {{ optional($log->created_at)->format('M d, Y H:i') }}</div>
              </div>
              <span class="status-pill status-pill-active">{{ $log->actor_role ?: 'SYSTEM' }}</span>
            </li>
          @empty
            <li class="workspace-list-item">
              <span class="text-muted">No audit activity recorded yet.</span>
            </li>
          @endforelse
        </ul>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="overview-card">
        <h2 class="section-card-title">Provider Breakdown</h2>
        <ul class="overview-list">
          <li class="overview-row">
            <span class="overview-key">Total Providers</span>
            <span class="overview-val">{{ $metrics['providers_total'] ?? 0 }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Pending Review</span>
            <span class="overview-val">{{ $metrics['providers_pending'] ?? 0 }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Rejected</span>
            <span class="overview-val">{{ $metrics['providers_rejected'] ?? 0 }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Suspended</span>
            <span class="overview-val">{{ $metrics['providers_suspended'] ?? 0 }}</span>
          </li>
        </ul>
      </div>
    </div>
  </section>
@endsection
