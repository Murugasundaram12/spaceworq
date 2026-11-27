@extends('layouts.adminpanel')

@section('title', 'Master Data — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'master-data')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Master Data</h1>
      <p class="wm-page-subtitle">Manage active platform cities, localities, and workspace amenities.</p>
    </div>
  </section>

  <div class="row g-4">
    <div class="col-12 col-lg-5">
      <div class="surface-card">
        <h2 class="section-card-title">Add New City</h2>
        <form id="adminCityStoreForm" method="POST" action="{{ route('admin.cities.store') }}">
          @csrf
          <div class="mb-3">
            <label for="cityNameInput" class="form-label fw-semibold">City Name</label>
            <input type="text" id="cityNameInput" name="name" value="{{ old('name') }}" class="form-control" required placeholder="e.g. Pune" />
          </div>
          <div class="mb-3">
            <label for="cityStateInput" class="form-label fw-semibold">State</label>
            <input type="text" id="cityStateInput" name="state" value="{{ old('state') }}" class="form-control" placeholder="e.g. Maharashtra" />
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="cityCountryInput" class="form-label fw-semibold">Country</label>
              <input type="text" id="cityCountryInput" name="country" value="{{ old('country', 'India') }}" class="form-control" />
            </div>
            <div class="col-6">
              <label for="citySortInput" class="form-label fw-semibold">Sort Order</label>
              <input type="number" id="citySortInput" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control" min="0" />
            </div>
          </div>
          <div class="d-flex gap-4 mb-3">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="is_featured" value="1" />
              <span class="form-check-label">Featured</span>
            </label>
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="is_active" value="1" checked />
              <span class="form-check-label">Active</span>
            </label>
          </div>
          <button type="submit" class="btn btn-primary fw-semibold">Create City</button>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <section class="wm-table-card mb-4">
        <div class="p-3 border-bottom fw-bold">Configured Cities ({{ $cities->count() }})</div>
        <div class="wm-table-scroll">
          <table class="wm-table">
            <thead>
              <tr>
                <th>City</th>
                <th>State</th>
                <th>Status</th>
                <th>Update</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($cities as $city)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $city->name }}</div>
                    <div class="small text-muted">{{ $city->slug }}</div>
                  </td>
                  <td>{{ $city->state ?: '—' }}</td>
                  <td>
                    <span class="wm-status-badge {{ $city->is_active ? 'wm-status-active' : 'wm-status-inactive' }}">
                      {{ $city->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                  </td>
                  <td>
                    <form method="POST" action="{{ route('admin.cities.update', $city) }}" class="d-flex gap-2 align-items-center">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="is_active" value="{{ $city->is_active ? 0 : 1 }}" />
                      <button type="submit" class="btn btn-sm btn-outline-primary">
                        {{ $city->is_active ? 'Disable' : 'Enable' }}
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-3 text-muted">No cities created yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>

      <div class="surface-card">
        <h2 class="section-card-title">Localities ({{ $localities->count() }}) &amp; Amenities ({{ $amenities->count() }})</h2>
        <p class="small text-muted mb-2">Active localities: {{ $localities->take(8)->pluck('name')->implode(', ') ?: 'None' }}</p>
        <p class="small text-muted mb-0">Active amenities: {{ $amenities->take(10)->pluck('name')->implode(', ') ?: 'None' }}</p>
      </div>
    </div>
  </div>
@endsection
