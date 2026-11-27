@extends('layouts.adminpanel')

@section('title', 'Application Status — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'status')

@section('content')
  @php
    $statusBadgeClass = match($provider->status) {
      \App\Models\Provider::STATUS_ACTIVE => 'status-pill-active',
      default => 'status-pill-inactive',
    };
    $kycBadgeClass = match($provider->kyc_status) {
      \App\Models\Provider::KYC_VERIFIED => 'status-pill-active',
      default => 'status-pill-inactive',
    };
    $userAccount = auth('web')->user();
  @endphp

  <section class="welcome-banner" aria-label="Application status header">
    <div>
      <h1 class="welcome-title">{{ $provider->company_name }}</h1>
      <p class="welcome-subtitle">Partner Verification &amp; Onboarding Status</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('provider.profile.show') }}" class="btn btn-primary btn-sm px-3 py-2 fw-semibold">
        {{ $provider->isSuspended() ? 'View Company Profile & KYC' : 'Company Profile & KYC' }}
      </a>
      @if ($provider->isVerifiedAndActive())
        <a href="{{ route('provider.dashboard') }}" class="btn btn-outline-primary btn-sm px-3 py-2 fw-semibold">
          Open Operational Dashboard
        </a>
      @endif
    </div>
  </section>

  @if ($provider->isSuspended())
    <div class="alert alert-danger mb-4" role="alert">
      <strong>Account Suspended:</strong> Your provider portal access is currently read-only. Operational features and onboarding modifications are disabled. Contact SpaceWorq support if you need assistance.
      @if ($provider->status_reason)
        <div class="mt-1 small">Reason: {{ $provider->status_reason }}</div>
      @endif
    </div>
  @elseif ($provider->isRejected() || $provider->kyc_status === \App\Models\Provider::KYC_REJECTED)
    <div class="alert alert-warning mb-4" role="alert">
      <strong>Attention Required:</strong> Please review the reviewer feedback below, update your Company Profile &amp; KYC documents, and resubmit your application.
      @if ($provider->status_reason)
        <div class="mt-1 small">Application Note: {{ $provider->status_reason }}</div>
      @endif
      @if ($provider->kyc_notes)
        <div class="mt-1 small">KYC Feedback: {{ $provider->kyc_notes }}</div>
      @endif
    </div>
  @elseif (! $provider->isVerifiedAndActive())
    <div class="alert alert-info mb-4" role="alert">
      <strong>Onboarding In Progress:</strong> Operational modules (Workspaces, Staff, Roles, Layout Designer) unlock once both your Provider Application is <strong>Approved</strong> and your KYC is <strong>Verified</strong>.
    </div>
  @endif

  <section aria-label="Status overview" class="mb-4">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Application Status</span>
            <span class="status-pill {{ $statusBadgeClass }}">{{ $provider->statusLabel() }}</span>
          </div>
          <div class="kpi-value" style="font-size:1.35rem;">{{ $provider->statusLabel() }}</div>
          <p class="kpi-caption">{{ $provider->status_updated_at ? 'Updated ' . $provider->status_updated_at->format('M d, Y H:i') : 'Awaiting admin review' }}</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">KYC Verification</span>
            <span class="status-pill {{ $kycBadgeClass }}">{{ $provider->kycStatusLabel() }}</span>
          </div>
          <div class="kpi-value" style="font-size:1.35rem;">{{ $provider->kycStatusLabel() }}</div>
          <p class="kpi-caption">{{ $statusSummary['documents_count'] ?? 0 }} KYC document(s) uploaded</p>
        </article>
      </div>

      <div class="col-12 col-sm-6 col-xl-4">
        <article class="kpi-card">
          <div class="kpi-card-header">
            <span class="kpi-label">Email Verification</span>
            <span class="status-pill {{ ($statusSummary['email_verified'] ?? false) ? 'status-pill-active' : 'status-pill-inactive' }}">
              {{ ($statusSummary['email_verified'] ?? false) ? 'VERIFIED' : 'PENDING' }}
            </span>
          </div>
          <div class="kpi-value" style="font-size:1.35rem;">{{ ($statusSummary['email_verified'] ?? false) ? 'Verified' : 'Unverified' }}</div>
          <p class="kpi-caption">{{ $userAccount?->email }}</p>
        </article>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-12 col-lg-8">
      <div class="surface-card">
        <h2 class="section-card-title">Verification Checklist</h2>
        <ul class="workspace-list">
          <li class="workspace-list-item">
            <span class="workspace-name">1. Provider Account &amp; Email Activation</span>
            <span class="status-pill {{ ($statusSummary['email_verified'] ?? false) ? 'status-pill-active' : 'status-pill-inactive' }}">
              {{ ($statusSummary['email_verified'] ?? false) ? 'COMPLETE' : 'ACTION NEEDED' }}
            </span>
          </li>
          <li class="workspace-list-item">
            <span class="workspace-name">2. Company Profile Details (Address, Tax ID, Contact)</span>
            <span class="status-pill {{ $provider->address ? 'status-pill-active' : 'status-pill-inactive' }}">
              {{ $provider->address ? 'SUBMITTED' : 'INCOMPLETE' }}
            </span>
          </li>
          <li class="workspace-list-item">
            <span class="workspace-name">3. KYC Compliance Documents</span>
            <span class="status-pill {{ $kycBadgeClass }}">{{ $provider->kycStatusLabel() }}</span>
          </li>
          <li class="workspace-list-item">
            <span class="workspace-name">4. Super Admin Final Approval</span>
            <span class="status-pill {{ $statusBadgeClass }}">{{ $provider->statusLabel() }}</span>
          </li>
        </ul>

        @if (! ($statusSummary['email_verified'] ?? false) && $userAccount)
          <form method="POST" action="{{ route('provider.activation.resend') }}" class="mt-3">
            @csrf
            <input type="hidden" name="email" value="{{ $userAccount->email }}" />
            <button type="submit" class="btn btn-outline-primary btn-sm">Resend Activation Email</button>
          </form>
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="overview-card">
        <h2 class="section-card-title">Business Summary</h2>
        <ul class="overview-list">
          <li class="overview-row">
            <span class="overview-key">Brand Name</span>
            <span class="overview-val" style="font-size:0.95rem;">{{ $provider->brand_name ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Business Email</span>
            <span class="overview-val" style="font-size:0.9rem;">{{ $provider->business_email ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Business Phone</span>
            <span class="overview-val" style="font-size:0.95rem;">{{ $provider->business_phone ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Submitted At</span>
            <span class="overview-val" style="font-size:0.9rem;">{{ $provider->submitted_at ? $provider->submitted_at->format('M d, Y') : 'Not submitted' }}</span>
          </li>
        </ul>
      </div>
    </div>
  </section>
@endsection
