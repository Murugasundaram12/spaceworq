@extends('layouts.adminpanel')

@section('title', $provider->company_name . ' — Provider Review')
@section('portal_mode', 'admin')
@section('active_menu', 'providers')

@section('content')
  <section class="welcome-banner">
    <div>
      <h1 class="welcome-title">{{ $provider->company_name }}</h1>
      <p class="welcome-subtitle">{{ $provider->brand_name ?: 'Partner Onboarding Profile' }} ·
        {{ $provider->owner?->email }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="status-pill {{ $provider->isApproved() ? 'status-pill-active' : 'status-pill-inactive' }}">
        Status: {{ $provider->statusLabel() }}
      </span>
      <span
        class="status-pill {{ $provider->kyc_status === \App\Models\Provider::KYC_VERIFIED ? 'status-pill-active' : 'status-pill-inactive' }}">
        KYC: {{ $provider->kycStatusLabel() }}
      </span>
      <a href="{{ route('admin.providers.index') }}" class="btn btn-sm btn-outline-secondary">Back to Providers</a>
    </div>
  </section>

  <div class="row g-4">
    <div class="col-12 col-lg-6">
      <div class="surface-card mb-4">
        <h2 class="section-card-title">Company &amp; Contact Profile</h2>
        <ul class="overview-list">
          <li class="overview-row">
            <span class="overview-key">Company Name</span>
            <span class="fw-semibold">{{ $provider->company_name }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Business Email</span>
            <span>{{ $provider->business_email ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Business Phone</span>
            <span>{{ $provider->business_phone ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">GSTIN / PAN</span>
            <span>{{ $provider->gstin ?: '—' }} / {{ $provider->pan ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">City</span>
            <span>{{ $provider->city?->name ?: '—' }}</span>
          </li>
          <li class="overview-row">
            <span class="overview-key">Address</span>
            <span class="text-end" style="max-width:260px;">{{ $provider->address ?: '—' }}</span>
          </li>
        </ul>
      </div>

      <div class="surface-card">
        <h2 class="section-card-title">Submitted KYC Documents</h2>
        @if ($provider->documents->isEmpty())
          <p class="text-muted small mb-0">No KYC documents uploaded yet.</p>
        @else
          <ul class="workspace-list">
            @foreach ($provider->documents as $doc)
              <li class="workspace-list-item">
                <div>
                  <div class="workspace-name">{{ $doc->document_type }}</div>
                  <div class="small text-muted">{{ $doc->original_filename }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="status-pill {{ $doc->status === 'APPROVED' ? 'status-pill-active' : 'status-pill-inactive' }}">
                    {{ $doc->status }}
                  </span>
                  <a href="{{ route('admin.provider-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                    Download
                  </a>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="surface-card mb-4">
        <h2 class="section-card-title">Application Status Decision</h2>
        <form id="adminProviderStatusForm" method="POST" action="{{ route('admin.providers.status', $provider) }}">
          @csrf
          @method('PATCH')
          <div class="mb-3">
            <label for="providerStatusSelect" class="form-label fw-semibold">Status</label>
            <select id="providerStatusSelect" name="status" class="form-select" required>
              @foreach (\App\Models\Provider::STATUS_LABELS as $code => $label)
                <option value="{{ $code }}" {{ $provider->status === $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="providerStatusNotes" class="form-label fw-semibold">Decision Notes (Required for Rejected /
              Suspended)</label>
            <textarea id="providerStatusNotes" name="notes" rows="3"
              class="form-control">{{ old('notes', $provider->status_reason) }}</textarea>
          </div>

          <button type="submit" class="btn btn-primary fw-semibold">Update Application Status</button>
        </form>
      </div>

      <div class="surface-card">
        <h2 class="section-card-title">KYC Verification Decision</h2>
        <form id="adminProviderKycForm" method="POST" action="{{ route('admin.providers.verify-kyc', $provider) }}">
          @csrf
          @method('POST')
          <div class="mb-3">
            <label for="providerKycSelect" class="form-label fw-semibold">KYC Status</label>
            <select id="providerKycSelect" name="kyc_status" class="form-select" required>
              <option value="{{ \App\Models\Provider::KYC_PENDING }}" {{ $provider->kyc_status === \App\Models\Provider::KYC_PENDING ? 'selected' : '' }}>Under Review
                ({{ \App\Models\Provider::KYC_PENDING }})</option>
              <option value="{{ \App\Models\Provider::KYC_VERIFIED }}" {{ $provider->kyc_status === \App\Models\Provider::KYC_VERIFIED ? 'selected' : '' }}>Verified
                ({{ \App\Models\Provider::KYC_VERIFIED }})</option>
              <option value="{{ \App\Models\Provider::KYC_REJECTED }}" {{ $provider->kyc_status === \App\Models\Provider::KYC_REJECTED ? 'selected' : '' }}>Rejected
                ({{ \App\Models\Provider::KYC_REJECTED }})</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="providerKycNotes" class="form-label fw-semibold">KYC Review Notes (Required if Rejected)</label>
            <textarea id="providerKycNotes" name="kyc_notes" rows="3"
              class="form-control">{{ old('kyc_notes', $provider->kyc_notes) }}</textarea>
          </div>

          @if ($provider->documents->isNotEmpty())
            <div class="mb-3">
              <div class="fw-semibold small mb-2">Individual Document Decisions</div>
              @foreach ($provider->documents as $idx => $doc)
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2 p-2 border rounded">
                  <span class="small">{{ $doc->document_type }}</span>
                  <input type="hidden" name="document_decisions[{{ $idx }}][id]" value="{{ $doc->id }}" />
                  <select name="document_decisions[{{ $idx }}][status]" class="form-select form-select-sm"
                    style="width:150px;">
                    <option value="{{ \App\Models\ProviderDocument::STATUS_APPROVED }}" {{ $doc->status === \App\Models\ProviderDocument::STATUS_APPROVED ? 'selected' : '' }}>APPROVED</option>
                    <option value="{{ \App\Models\ProviderDocument::STATUS_REJECTED }}" {{ $doc->status === \App\Models\ProviderDocument::STATUS_REJECTED ? 'selected' : '' }}>REJECTED</option>
                  </select>
                </div>
              @endforeach
            </div>
          @endif

          <button type="submit" class="btn btn-success fw-semibold">Save KYC Verification</button>
        </form>
      </div>
    </div>
  </div>
@endsection