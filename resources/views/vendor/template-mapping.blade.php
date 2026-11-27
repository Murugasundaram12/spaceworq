@extends('layouts.adminpanel')

@section('title', 'Template Mapping — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'template-mapping')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/reports.css') }}" />
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/template-mapping.css') }}" />
@endpush

@section('content')
<section class="tm-map-card" aria-label="Template Mapping">
          <h1 class="tm-map-kicker">Template Mapping</h1>
          <p class="tm-map-subtitle">
            Search provider workspaces, open floors for one workspace, and map templates independently from design work.
          </p>

          <div class="tm-map-search-wrap">
            <input
              type="search"
              id="tmSearchWorkspacesInput"
              class="tm-map-search-input"
              placeholder="Search Workspaces"
              aria-label="Search Workspaces"
            />
          </div>

          <div id="tmMapStatusBanner" class="tm-map-status-banner" role="status" aria-live="polite">
            <span id="tmMapStatusText"></span>
            <button type="button" id="tmCloseMapBannerBtn" class="btn-close btn-sm" aria-label="Dismiss"></button>
          </div>

          <div id="tmMapWorkspacesContainer">
            <p class="tm-map-empty">No workspaces available</p>
          </div>
        </section>
@endsection

@push('scripts')
  <script>
    @php
      $__mapWorkspaces = $workspaces->map(function ($w) {
        return [
          'id' => (string) $w->id,
          'name' => $w->name,
          'code' => $w->code,
          'city' => $w->city?->name ?? 'Chennai',
          'floorsCount' => $w->floors->count(),
          'floorsList' => $w->floors->map(fn ($f) => [
            'id' => $f->id,
            'floor_number' => $f->floor_number,
            'label' => $f->label ?: ('Floor ' . $f->floor_number),
            'mapped_template_id' => $f->templateMapping?->layout_template_id ? (string) $f->templateMapping->layout_template_id : '',
            'mapped_template_name' => $f->templateMapping?->template?->name ?? '',
          ])->values(),
        ];
      })->values();
      $__mapBoot = [
        'mode' => 'server',
        'workspaces' => $__mapWorkspaces,
        'templates' => $templates->map(fn ($t) => ['id' => (string) $t->id, 'name' => $t->name])->values(),
        'routes' => [
          'map' => route('provider.template-mapping.store'),
        ],
      ];
    @endphp
    window.__SW_MAPPING_DATA__ = @json($__mapBoot);
  </script>
  <script src="{{ asset('vendor-panel/js/template-mapping.js') }}"></script>
@endpush
