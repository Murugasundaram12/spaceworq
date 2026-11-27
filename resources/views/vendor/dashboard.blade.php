@extends('layouts.adminpanel')

@section('title', 'Dashboard — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'dashboard')

@section('content')
@php
  $recentList = $recentWorkspaces ?? $provider->workspaces()->latest('id')->limit(5)->get();
@endphp
<!-- Welcome Banner -->
        <section class="welcome-banner" aria-label="Dashboard header">
          <div>
            <h1 class="welcome-title">Welcome, <span id="welcomeUserName">{{ auth('web')->user()?->first_name ?: 'Partner' }}</span></h1>
            <p id="portalSubtitle" class="welcome-subtitle">{{ $provider->company_name }} · Partner Portal</p>
          </div>

          <div class="workspace-filter-wrap dropdown" id="providerWorkspaceDropdown">
            <span class="workspace-filter-label" id="providerWorkspaceLabel">
              Provider Workspace
            </span>
            <button
              type="button"
              id="providerWorkspaceToggle"
              class="workspace-filter-trigger"
              aria-haspopup="listbox"
              aria-expanded="false"
              aria-labelledby="providerWorkspaceLabel providerWorkspaceValue"
            >
              <span id="providerWorkspaceValue">All Workspaces</span>
              <svg class="workspace-filter-caret" width="12" height="12" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                <path d="M2.5 4.25L6 8l3.5-3.75H2.5z"></path>
              </svg>
            </button>
            <ul id="providerWorkspaceMenu" class="workspace-filter-menu" role="listbox" aria-labelledby="providerWorkspaceLabel">
              <li role="option" class="workspace-filter-option active" data-value="all" aria-selected="true" tabindex="0">
                All Workspaces
              </li>
              @foreach ($recentList as $rw)
                <li role="option" class="workspace-filter-option" data-value="ws-{{ $rw->id }}" aria-selected="false" tabindex="0">
                  {{ $rw->name }}
                </li>
              @endforeach
            </ul>
          </div>
        </section>

        <!-- KPI Stat Cards Grid (5 Cards: 3 + 2 on Desktop) -->
        <section aria-label="Key metrics" class="mb-4">
          <div class="row g-4">
            <!-- System Users -->
            <div class="col-12 col-sm-6 col-xl-4">
              <article class="kpi-card">
                <div class="kpi-card-header">
                  <span class="kpi-label">System Users</span>
                  <span class="kpi-icon-badge kpi-icon-indigo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                    </svg>
                  </span>
                </div>
                <div id="kpiSystemUsers" class="kpi-value">{{ $metrics['users_count'] ?? 0 }}</div>
                <p class="kpi-caption">Active users</p>
              </article>
            </div>

            <!-- Active Providers -->
            <div class="col-12 col-sm-6 col-xl-4">
              <article class="kpi-card">
                <div class="kpi-card-header">
                  <span class="kpi-label">Active Providers</span>
                  <span class="kpi-icon-badge kpi-icon-emerald" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M3 21h18"></path>
                      <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                      <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                      <path d="M9 7h2"></path>
                      <path d="M9 11h2"></path>
                      <path d="M9 15h2"></path>
                    </svg>
                  </span>
                </div>
                <div id="kpiActiveProviders" class="kpi-value">{{ $provider->isVerifiedAndActive() ? 1 : 0 }}</div>
                <p class="kpi-caption">Provider workspaces</p>
              </article>
            </div>

            <!-- All Workspaces -->
            <div class="col-12 col-sm-6 col-xl-4">
              <article class="kpi-card">
                <div class="kpi-card-header">
                  <span class="kpi-label">All Workspaces</span>
                  <span class="kpi-icon-badge kpi-icon-amber" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M3 21h18"></path>
                      <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                      <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                      <path d="M9 7h2"></path>
                      <path d="M9 11h2"></path>
                      <path d="M9 15h2"></path>
                    </svg>
                  </span>
                </div>
                <div id="kpiAllWorkspaces" class="kpi-value">{{ $metrics['workspaces_count'] ?? 0 }}</div>
                <p class="kpi-caption">Across all providers</p>
              </article>
            </div>

            <!-- All Floors -->
            <div class="col-12 col-sm-6 col-xl-4">
              <article class="kpi-card">
                <div class="kpi-card-header">
                  <span class="kpi-label">All Floors</span>
                  <span class="kpi-icon-badge kpi-icon-sky" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M11.99 18.54l-7.37-5.73L3 14.07l9 7 9-7-1.63-1.27-7.38 5.74zM12 16l7.36-5.73L21 9l-9-7-9 7 1.63 1.27L12 16z"></path>
                    </svg>
                  </span>
                </div>
                <div id="kpiAllFloors" class="kpi-value">{{ $metrics['floors_count'] ?? 0 }}</div>
                <p class="kpi-caption">Across all providers</p>
              </article>
            </div>

            <!-- Mapped Templates -->
            <div class="col-12 col-sm-6 col-xl-4">
              <article class="kpi-card">
                <div class="kpi-card-header">
                  <span class="kpi-label">Mapped Templates</span>
                  <span class="kpi-icon-badge kpi-icon-violet" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm0 17.93V4.18l6 2.25v4.66c0 4.08-2.55 7.91-6 8.84z"></path>
                    </svg>
                  </span>
                </div>
                <div id="kpiMappedTemplates" class="kpi-value">{{ $metrics['mapped_templates_count'] ?? 0 }}</div>
                <p class="kpi-caption">Floor-template mappings</p>
              </article>
            </div>
          </div>
        </section>

        <!-- Bottom Two-Column Section: Recent Workspaces & Workspace Overview -->
        <section aria-label="Workspace details">
          <div class="row g-4 align-items-stretch">
            <!-- Recent Workspaces -->
            <div class="col-12 col-lg-8">
              <div class="surface-card">
                <h2 class="section-card-title">Recent Workspaces</h2>
                <ul id="recentWorkspacesList" class="workspace-list">
                @forelse ($recentList as $rw)
                  <li class="workspace-list-item">
                    <span class="workspace-name">{{ $rw->name }}</span>
                    <span class="status-pill {{ $rw->status === 'ACTIVE' ? 'status-pill-active' : 'status-pill-inactive' }}">{{ $rw->status }}</span>
                  </li>
                @empty
                  <li class="workspace-list-item">
                    <span class="workspace-name text-muted">No workspaces created yet.</span>
                  </li>
                @endforelse
              </ul>
              </div>
            </div>

            <!-- Workspace Overview -->
            <div class="col-12 col-lg-4">
              <div class="overview-card">
                <h2 class="section-card-title">Workspace Overview</h2>
                <ul class="overview-list">
                  <li class="overview-row">
                    <span class="overview-key">Total Workspaces</span>
                    <span id="overviewTotalWorkspaces" class="overview-val">{{ $metrics['workspaces_count'] ?? 0 }}</span>
                  </li>
                  <li class="overview-row">
                    <span class="overview-key">Total Floors</span>
                    <span id="overviewTotalFloors" class="overview-val">{{ $metrics['floors_count'] ?? 0 }}</span>
                  </li>
                  <li class="overview-row">
                    <span class="overview-key">Total Spaces</span>
                    <span id="overviewTotalSpaces" class="overview-val">{{ $metrics['templates_count'] ?? 0 }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </section>
@endsection

@push('scripts')
  @php
    $__dashData = [
      'mode' => 'server',
      'user' => [
        'firstName' => auth('web')->user()?->first_name ?? 'Partner',
        'email' => auth('web')->user()?->email ?? '',
      ],
      'metrics' => $metrics,
      'recentWorkspaces' => $recentList->map(fn ($w) => [
        'id' => 'ws-' . $w->id,
        'name' => $w->name,
        'status' => $w->status,
      ])->values(),
    ];
  @endphp
  <script>
    window.__SW_DASHBOARD_DATA__ = @json($__dashData);
  </script>
  <script src="{{ asset('vendor-panel/js/dashboard.js') }}"></script>
@endpush
