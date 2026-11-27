@extends('layouts.adminpanel')

@section('title', 'Workspace Reviews Moderation — Super Admin')
@section('portal_mode', 'admin')
@section('active_menu', 'reviews')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
  <section class="wm-header-row">
    <div>
      <h1 class="wm-page-title">Reviews Moderation</h1>
      <p class="wm-page-subtitle">Moderate existing customer workspace reviews for public visibility.</p>
    </div>
  </section>

  <section class="wm-table-card">
    <div class="wm-table-scroll">
      <table class="wm-table">
        <thead>
          <tr>
            <th>Workspace &amp; Author</th>
            <th>Rating &amp; Review</th>
            <th>Visibility</th>
            <th>Moderate</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($reviews as $review)
            <tr>
              <td>
                <div class="fw-semibold">{{ $review->workspace?->name ?? 'Workspace' }}</div>
                <div class="small text-muted">{{ $review->user?->email ?? 'User' }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ $review->rating }}/5 — {{ $review->title ?: 'Review' }}</div>
                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($review->comment, 120) }}</div>
              </td>
              <td>
                <span class="wm-status-badge {{ $review->is_visible ? 'wm-status-active' : 'wm-status-inactive' }}">
                  {{ $review->is_visible ? 'VISIBLE' : 'HIDDEN' }}
                </span>
              </td>
              <td>
                <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}" class="d-flex gap-2 align-items-center">
                  @csrf
                  @method('PATCH')
                  <select name="is_visible" class="form-select form-select-sm" style="width:110px;">
                    <option value="1" {{ $review->is_visible ? 'selected' : '' }}>Visible</option>
                    <option value="0" {{ ! $review->is_visible ? 'selected' : '' }}>Hide</option>
                  </select>
                  <input type="text" name="moderation_notes" value="{{ $review->moderation_notes }}" class="form-control form-control-sm" style="width:170px;" placeholder="Moderation note" />
                  <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center py-4 text-muted">No workspace reviews submitted yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $reviews->withQueryString()->links() }}</div>
  </section>
@endsection
