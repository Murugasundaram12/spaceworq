@extends('layouts.adminpanel')

@section('title', 'Company Profile & KYC — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'profile')

@section('content')
  <section class="welcome-banner" aria-label="Company profile header">
    <div>
      <h1 class="welcome-title">Company Profile &amp; KYC</h1>
      <p class="welcome-subtitle">Manage your business details and upload KYC documents for SpaceWorq verification.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="status-pill {{ $provider->isApproved() ? 'status-pill-active' : 'status-pill-inactive' }}">
        Status: {{ $provider->statusLabel() }}
      </span>
      <span class="status-pill {{ $provider->kyc_status === \App\Models\Provider::KYC_VERIFIED ? 'status-pill-active' : 'status-pill-inactive' }}">
        KYC: {{ $provider->kycStatusLabel() }}
      </span>
    </div>
  </section>

  @if (! $canEditOnboarding)
    <div class="alert alert-danger mb-4" role="alert">
      <strong>Read-Only Access:</strong> Suspended provider accounts cannot modify company details or upload new KYC documents. You may review your application status, download previously uploaded documents, or sign out.
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-7">
      <div class="surface-card">
        <h2 class="section-card-title">Company Details</h2>

        <form id="providerProfileForm" method="POST" action="{{ route('provider.profile.update') }}">
          @csrf
          @method('PATCH')

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="providerCompanyName" class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
              <input
                type="text"
                id="providerCompanyName"
                name="company_name"
                value="{{ old('company_name', $provider->company_name) }}"
                class="form-control @error('company_name') is-invalid @enderror"
                {{ $canEditOnboarding ? 'required' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-6">
              <label for="providerBrandName" class="form-label fw-semibold">Brand Name</label>
              <input
                type="text"
                id="providerBrandName"
                name="brand_name"
                value="{{ old('brand_name', $provider->brand_name) }}"
                class="form-control @error('brand_name') is-invalid @enderror"
                {{ $canEditOnboarding ? '' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-6">
              <label for="providerBusinessEmail" class="form-label fw-semibold">Business Email <span class="text-danger">*</span></label>
              <input
                type="email"
                id="providerBusinessEmail"
                name="business_email"
                value="{{ old('business_email', $provider->business_email) }}"
                class="form-control @error('business_email') is-invalid @enderror"
                {{ $canEditOnboarding ? 'required' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-6">
              <label for="providerBusinessPhone" class="form-label fw-semibold">Business Phone <span class="text-danger">*</span></label>
              <input
                type="text"
                id="providerBusinessPhone"
                name="business_phone"
                value="{{ old('business_phone', $provider->business_phone) }}"
                class="form-control @error('business_phone') is-invalid @enderror"
                {{ $canEditOnboarding ? 'required' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-4">
              <label for="providerGstin" class="form-label fw-semibold">GSTIN</label>
              <input
                type="text"
                id="providerGstin"
                name="gstin"
                value="{{ old('gstin', $provider->gstin) }}"
                class="form-control @error('gstin') is-invalid @enderror"
                placeholder="29ABCDE1234F1Z5"
                {{ $canEditOnboarding ? '' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-4">
              <label for="providerPan" class="form-label fw-semibold">PAN</label>
              <input
                type="text"
                id="providerPan"
                name="pan"
                value="{{ old('pan', $provider->pan) }}"
                class="form-control @error('pan') is-invalid @enderror"
                placeholder="ABCDE1234F"
                {{ $canEditOnboarding ? '' : 'disabled' }}
              />
            </div>

            <div class="col-12 col-md-4">
              <label for="providerCityId" class="form-label fw-semibold">Primary City</label>
              <select id="providerCityId" name="city_id" class="form-select @error('city_id') is-invalid @enderror" {{ $canEditOnboarding ? '' : 'disabled' }}>
                <option value="">Select city</option>
                @foreach ($cities as $city)
                  <option value="{{ $city->id }}" {{ (string) old('city_id', $provider->city_id) === (string) $city->id ? 'selected' : '' }}>
                    {{ $city->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label for="providerAddress" class="form-label fw-semibold">Registered Business Address <span class="text-danger">*</span></label>
              <textarea
                id="providerAddress"
                name="address"
                rows="3"
                class="form-control @error('address') is-invalid @enderror"
                {{ $canEditOnboarding ? 'required' : 'disabled' }}
              >{{ old('address', $provider->address) }}</textarea>
            </div>

            @if ($canEditOnboarding && $provider->isRejected())
              <div class="col-12">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="resubmitAppCheck" name="resubmit_application" value="1" checked />
                  <label class="form-check-label" for="resubmitAppCheck">
                    Resubmit rejected application for Super Admin review
                  </label>
                </div>
              </div>
            @endif
          </div>

          @if ($canEditOnboarding)
            <div class="mt-4 d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                Save Company Profile
              </button>
              <a href="{{ route('provider.status') }}" class="btn btn-outline-secondary">Back to Status</a>
            </div>
          @endif
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="surface-card mb-4">
        <h2 class="section-card-title">Upload KYC Document</h2>

        @if ($canEditOnboarding)
          <form id="providerKycUploadForm" method="POST" action="{{ route('provider.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label for="kycDocumentType" class="form-label fw-semibold">Document Type</label>
              <select id="kycDocumentType" name="document_type" class="form-select" required>
                <option value="GST_CERTIFICATE">GST Certificate</option>
                <option value="PAN_CARD">Company / Owner PAN Card</option>
                <option value="COI">Certificate of Incorporation (COI)</option>
                <option value="TRADE_LICENSE">Trade License</option>
                <option value="ADDRESS_PROOF">Address Proof</option>
                <option value="OTHER">Other Supporting Document</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="kycDocumentFile" class="form-label fw-semibold">Document File (PDF, JPG, PNG)</label>
              <input type="file" id="kycDocumentFile" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required />
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">Upload KYC Document</button>
          </form>
        @else
          <p class="text-muted small mb-0">Document uploads are disabled while this provider account is suspended.</p>
        @endif
      </div>

      <div class="overview-card">
        <h2 class="section-card-title">Uploaded KYC Documents</h2>
        @if ($provider->documents->isEmpty())
          <p class="text-muted small mb-0">No KYC documents uploaded yet.</p>
        @else
          <ul class="workspace-list">
            @foreach ($provider->documents as $doc)
              <li class="workspace-list-item">
                <div>
                  <div class="workspace-name" style="font-size:0.9rem;">{{ $doc->document_type }}</div>
                  <div class="small text-muted">{{ $doc->original_filename }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="status-pill {{ $doc->status === 'APPROVED' ? 'status-pill-active' : 'status-pill-inactive' }}">
                    {{ $doc->status }}
                  </span>
                  <a href="{{ route('provider.documents.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                    Download
                  </a>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>
@endsection
