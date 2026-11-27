@extends('layouts.adminpanel')

@section('title', 'Template Management — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'templates')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/reports.css') }}" />
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/template-management.css') }}" />
@endpush

@section('content')
  <!-- Interactive Graph-Paper Canvas -->
  <section class="tm-canvas-viewport" aria-label="Floor plan design canvas">
    <!-- Floating Elements Palette Toggle -->
    <button type="button" id="tmToolboxFab" class="tm-toolbox-fab" aria-label="Add floor plan elements"
      title="Add elements to canvas">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="4" y1="7" x2="20" y2="7"></line>
        <line x1="4" y1="12" x2="20" y2="12"></line>
        <line x1="4" y1="17" x2="20" y2="17"></line>
      </svg>
    </button>

    <!-- Floating Elements Palette -->
    <div id="tmToolboxPanel" class="tm-toolbox-panel" aria-label="Canvas elements">
      <div class="tm-toolbox-title">Add Canvas Element</div>
      <div class="tm-toolbox-grid">
        <button type="button" class="tm-tool-item" data-add-element="desk">Desk Bay</button>
        <button type="button" class="tm-tool-item" data-add-element="meeting">Meeting Room</button>
        <button type="button" class="tm-tool-item" data-add-element="cabin">Private Cabin</button>
        <button type="button" class="tm-tool-item" data-add-element="lounge">Lounge Area</button>
        <button type="button" class="tm-tool-item" data-add-element="reception">Reception</button>
        <button type="button" class="tm-tool-item" data-add-element="booth">Phone Booth</button>
      </div>
    </div>

    <!-- Reopen Inspector Pill -->
    <button type="button" id="tmReopenPanelBtn" class="tm-reopen-panel-btn">
      Open Management
    </button>

    <!-- Draggable Canvas Surface -->
    <div id="tmCanvasBoard" class="tm-canvas-board"></div>

    <!-- Zoom Bar -->
    <div class="tm-canvas-controls" aria-label="Canvas zoom controls">
      <button type="button" id="tmZoomOutBtn" class="tm-zoom-btn" aria-label="Zoom out">&minus;</button>
      <span id="tmZoomLabel" class="tm-zoom-label">100%</span>
      <button type="button" id="tmZoomInBtn" class="tm-zoom-btn" aria-label="Zoom in">+</button>
    </div>
  </section>

  <!-- Right Management & Properties Inspector Panel -->
  <aside id="tmInspectorPanel" class="tm-inspector-panel" aria-label="Template inspector">
    <div class="tm-inspector-tabs">
      <div class="tm-tab-buttons" role="tablist">
        <button type="button" class="tm-tab-btn active" data-tm-tab="management" role="tab" aria-selected="true">
          Management
        </button>
        <button type="button" class="tm-tab-btn" data-tm-tab="properties" role="tab" aria-selected="false">
          Properties
        </button>
      </div>

      <button type="button" id="tmClosePanelBtn" class="tm-panel-close-btn" aria-label="Close inspector panel">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
          stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="tm-inspector-body">
      <!-- Management Tab -->
      <div class="tm-tab-pane active" data-tm-pane="management" role="tabpanel">
        <h1 class="tm-section-kicker">Template Management</h1>
        <p class="tm-section-desc">
          Create a new template, load an existing one to edit on the canvas, clone it, update it, or delete it here.
        </p>

        <!-- 3 Action Tile Buttons -->
        <div class="tm-action-row">
          <button type="button" id="newDesignBtn" class="tm-action-tile tm-btn-new">
            New<br />Design
          </button>
          <button type="button" id="saveNewTemplateBtn" class="tm-action-tile tm-btn-save" data-bs-toggle="modal"
            data-bs-target="#saveTemplateModal">
            Save<br />New<br />Template
          </button>
          <button type="button" id="updateLoadedTemplateBtn" class="tm-action-tile tm-btn-update" disabled>
            Update<br />Loaded<br />Template
          </button>
        </div>

        <!-- Active Canvas Status Info Banner -->
        <div id="tmStatusBanner" class="tm-info-banner">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span id="tmStatusBannerText">
            Started a new blank template. Add elements on the canvas, then save it from this management tab.
          </span>
        </div>

        <!-- Saved Templates Directory / Empty State -->
        <div id="tmTemplatesListContainer"></div>
      </div>

            <!-- Properties Tab -->
            <div class="tm-tab-pane" data-tm-pane="properties" role="tabpanel">
              <h2 class="tm-section-kicker">Element Properties</h2>
              <div id="tmPropertiesContent"></div>
            </div>
          </div>
        </aside>
  </div>

  <!-- Save Layout as Template Modal -->
  <div class="modal fade tm-save-modal" id="saveTemplateModal" tabindex="-1" aria-labelledby="saveTemplateModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <h2 class="tm-save-modal-title" id="saveTemplateModalLabel">Save Layout as Template</h2>

        <form id="saveTemplateForm" method="POST" action="{{ route('provider.templates.store') }}" novalidate>
          @csrf
          <input
            type="text"
            id="tmTemplateNameInput"
            name="name"
            class="tm-modal-input"
            placeholder="Template Name"
            required
          />

          <textarea
            id="tmTemplateDescInput"
            name="description"
            class="tm-modal-textarea"
            placeholder="Description (Optional)"
          ></textarea>

          <select id="tmAssignFloorInput" name="workspace_floor_id" class="form-select tm-modal-select" aria-label="Assign to Floor (Optional)">
            <option value="">Assign to Floor (Optional)</option>
            @foreach ($floors as $fl)
              <option value="{{ $fl->id }}">{{ $fl->workspace?->name ?? 'Workspace' }} — Floor {{ $fl->floor_number }}
              </option>
            @endforeach
          </select>

          <div class="tm-how-box">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="16" x2="12" y2="12"></line>
              <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
              <div class="tm-how-title">How it works:</div>
              <ul class="tm-how-list">
                <li>&bull; Templates are saved per partner organization</li>
                <li>&bull; Optionally assign this template to a floor</li>
                <li>&bull; You can map this template to other floors later</li>
                <li>&bull; Other partners cannot access your templates</li>
              </ul>
            </div>
          </div>

          <div id="tmCurrentSummaryStrip" class="tm-current-strip">
            <strong>Current:</strong> Assigned Floor: None | Elements: 0 | Partner: AdminPanel
          </div>

          <div class="tm-modal-footer">
            <button type="button" class="btn-tm-modal-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" id="tmSaveTemplateSubmitBtn" class="btn-tm-modal-save" disabled>
              Save Template
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @php
    $__tmBoot = [
      'mode' => 'server',
      'templates' => $templates->map(fn($t) => [
        'id' => (string) $t->id,
        'name' => $t->name,
        'description' => $t->description ?? '',
        'elements' => $t->elements ?? [],
        'updatedAt' => optional($t->updated_at)->format('M d, Y') ?? '',
      ])->values(),
      'routes' => [
        'store' => route('provider.templates.store'),
        'updateTemplate' => route('provider.templates.update', ['template' => '__ID__']),
        'destroyTemplate' => route('provider.templates.destroy', ['template' => '__ID__']),
      ],
    ];
  @endphp
  <script>
    window.__SW_TEMPLATES_DATA__ = @json($__tmBoot);
  </script>
  <script src="{{ asset('vendor-panel/js/template-management.js') }}"></script>
@endpush