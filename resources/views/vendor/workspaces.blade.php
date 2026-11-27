@extends('layouts.adminpanel')

@section('title', 'Workspace Management — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'workspaces')

@push('head_styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/workspace-management.css') }}" />
@endpush

@section('content')
<!-- Hero Banner Card -->
        <section class="wm-hero-banner" aria-label="Workspace Management header">
          <div class="wm-hero-top">
            <div class="wm-hero-heading-group">
              <span class="wm-hero-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21h18"></path>
                  <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                  <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                  <path d="M9 7h2"></path>
                  <path d="M9 11h2"></path>
                  <path d="M9 15h2"></path>
                </svg>
              </span>
              <div>
                <h1 class="wm-hero-title">Workspace Management</h1>
                <p class="wm-hero-subtitle">
                  Manage all your provider workspaces with richer controls and quick insight.
                </p>
              </div>
            </div>

            <button
              type="button"
              id="openNewWorkspaceBtn"
              class="btn-wm-primary"
              data-bs-toggle="offcanvas"
              data-bs-target="#newWorkspaceDrawer"
              aria-controls="newWorkspaceDrawer"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>New Workspace</span>
            </button>
          </div>

          <!-- Summary Pill Chips -->
          <div class="wm-summary-chips">
            <span class="wm-chip wm-chip-indigo">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 21h18"></path>
                <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
              </svg>
              <span id="summaryWorkspacesCount">7 workspaces</span>
            </span>

            <span class="wm-chip wm-chip-sky">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="4" y="3" width="16" height="18" rx="2"></rect>
                <path d="M9 7h2"></path>
                <path d="M13 7h2"></path>
                <path d="M9 11h2"></path>
                <path d="M13 11h2"></path>
                <path d="M9 15h2"></path>
                <path d="M13 15h2"></path>
              </svg>
              <span id="summaryFloorsCount">25 floors</span>
            </span>

            <span class="wm-chip wm-chip-teal">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"></path>
              </svg>
              <span id="summaryCitiesCount">1 cities</span>
            </span>
          </div>
        </section>

        <!-- Category Filter Card -->
        <section class="wm-filter-card" aria-label="Workspace filters">
          <div class="workspace-filter-wrap" id="categoryFilterDropdown">
            <span class="workspace-filter-label" id="categoryFilterLabel">
              Category Filter
            </span>
            <button
              type="button"
              id="categoryFilterToggle"
              class="workspace-filter-trigger"
              aria-haspopup="listbox"
              aria-expanded="false"
              aria-labelledby="categoryFilterLabel categoryFilterValue"
            >
              <span id="categoryFilterValue">All Categories</span>
              <svg class="workspace-filter-caret" width="12" height="12" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                <path d="M2.5 4.25L6 8l3.5-3.75H2.5z"></path>
              </svg>
            </button>
            <ul
              id="categoryFilterMenu"
              class="workspace-filter-menu"
              role="listbox"
              aria-labelledby="categoryFilterLabel"
            >
              <li role="option" class="workspace-filter-option active" data-value="all" aria-selected="true" tabindex="0">
                All Categories
              </li>
              <li role="option" class="workspace-filter-option" data-value="Long-term Leasing" aria-selected="false" tabindex="0">
                Long-term Leasing
              </li>
              <li role="option" class="workspace-filter-option" data-value="Day Pass" aria-selected="false" tabindex="0">
                Day Pass
              </li>
              <li role="option" class="workspace-filter-option" data-value="Meetings &amp; Events" aria-selected="false" tabindex="0">
                Meetings &amp; Events
              </li>
              <li role="option" class="workspace-filter-option" data-value="Virtual Office" aria-selected="false" tabindex="0">
                Virtual Office
              </li>
            </ul>
          </div>

          <span id="shownCountBadge" class="wm-shown-badge">7 shown</span>
        </section>

        <!-- Workspaces Table Card -->
        <section class="wm-table-card" aria-label="Workspaces directory">
          <div class="wm-table-scroll">
            <table class="wm-table">
              <thead>
                <tr>
                  <th scope="col">Workspace<br />Name</th>
                  <th scope="col">Category</th>
                  <th scope="col">Address</th>
                  <th scope="col" class="text-center">Floors</th>
                  <th scope="col">Created<br />By</th>
                  <th scope="col">Created<br />At</th>
                  <th scope="col">Updated<br />At</th>
                  <th scope="col">Status</th>
                  <th scope="col" class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="workspacesTableBody">
                <!-- Populated dynamically by assets/js/workspace-management.js -->
              </tbody>
            </table>
          </div>

          <!-- Table Pagination Bar -->
          <div class="wm-table-footer">
            <div class="wm-rows-select-wrap">
              <label for="wmRowsPerPage">Rows per page:</label>
              <select id="wmRowsPerPage" class="wm-rows-select" aria-label="Rows per page">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
              </select>
            </div>

            <span id="wmPageRange" class="wm-page-range">1-7 of 7</span>

            <div class="wm-page-nav">
              <button
                type="button"
                id="wmPrevPageBtn"
                class="wm-page-btn"
                aria-label="Previous page"
                disabled
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              <button
                type="button"
                id="wmNextPageBtn"
                class="wm-page-btn"
                aria-label="Next page"
                disabled
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>
        </section>
<footer class="admin-footer">
        <div>Copyright @ AdminPanel.com</div>
        <nav class="admin-footer-links" aria-label="Footer navigation">
          <a href="#about">About Us</a>
          <a href="#contact">Contact Us</a>
          <a href="#privacy">Privacy Policy</a>
          <a href="#terms">Terms</a>
          <a href="#support">Support</a>
        </nav>
      </footer>
    </div>
  </div>

  <!-- Add / Edit Workspace Slide-Over Drawer -->
  <div
    class="offcanvas offcanvas-end wm-drawer"
    tabindex="-1"
    id="newWorkspaceDrawer"
    aria-labelledby="newWorkspaceDrawerLabel"
  >
    <div class="offcanvas-header">
      <h2 class="offcanvas-title" id="newWorkspaceDrawerLabel">Add New Workspace</h2>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <form id="newWorkspaceForm" class="wm-drawer-form" novalidate>
      <input type="hidden" id="wsEditCode" value="" />

      <div class="wm-drawer-body">
        <!-- Workspace Name -->
        <div class="wm-field">
          <input
            type="text"
            id="wsNameInput"
            class="wm-input"
            placeholder=" "
            required
          />
          <label for="wsNameInput" class="wm-float-label">Workspace Name</label>
        </div>

        <!-- Workspace Description (Rich Text Editor) -->
        <div class="mb-3">
          <label class="wm-subsection-title d-block" for="wsDescriptionEditor">Workspace Description</label>
          <div class="wm-editor-toolbar" role="toolbar" aria-label="Text formatting">
            <button type="button" class="wm-editor-btn" data-editor-cmd="bold" title="Bold"><strong>B</strong></button>
            <button type="button" class="wm-editor-btn" data-editor-cmd="italic" title="Italic"><em>I</em></button>
            <button type="button" class="wm-editor-btn" data-editor-cmd="insertUnorderedList" title="Bullet list" aria-label="Bullet list">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="9" y1="6" x2="20" y2="6"></line>
                <line x1="9" y1="12" x2="20" y2="12"></line>
                <line x1="9" y1="18" x2="20" y2="18"></line>
                <circle cx="4" cy="6" r="1" fill="currentColor"></circle>
                <circle cx="4" cy="12" r="1" fill="currentColor"></circle>
                <circle cx="4" cy="18" r="1" fill="currentColor"></circle>
              </svg>
            </button>
            <button type="button" class="wm-editor-btn" data-editor-cmd="insertOrderedList" title="Numbered list" aria-label="Numbered list">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="10" y1="6" x2="21" y2="6"></line>
                <line x1="10" y1="12" x2="21" y2="12"></line>
                <line x1="10" y1="18" x2="21" y2="18"></line>
                <path d="M4 6h1v4"></path>
                <path d="M4 10h2"></path>
                <path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path>
              </svg>
            </button>
            <button type="button" class="wm-editor-btn" data-editor-cmd="createLink" title="Insert link" aria-label="Insert link">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
              </svg>
            </button>
            <button type="button" class="wm-editor-btn" data-editor-cmd="removeFormat" title="Clear formatting" aria-label="Clear formatting">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 7V4h16v3"></path>
                <path d="M9 20h6"></path>
                <path d="M12 4v16"></path>
                <line x1="3" y1="3" x2="21" y2="21"></line>
              </svg>
            </button>
          </div>
          <div
            id="wsDescriptionEditor"
            class="wm-editor-area"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            data-placeholder="Describe the workspace, vibe, ideal teams, and key differentiators..."
          ></div>
        </div>

        <!-- Near By Place -->
        <div class="wm-field">
          <input
            type="text"
            id="wsLandmarkInput"
            class="wm-input"
            placeholder=" "
          />
          <label for="wsLandmarkInput" class="wm-float-label">Near By Place</label>
          <div class="wm-help-text">Optional nearby landmark for easier discovery</div>
        </div>

        <!-- Total Floors -->
        <div class="wm-field">
          <select id="wsFloorsInput" class="form-select wm-select" required>
            <option value="1" selected>1 Floor</option>
            <option value="2">2 Floors</option>
            <option value="3">3 Floors</option>
            <option value="4">4 Floors</option>
            <option value="5">5 Floors</option>
            <option value="6">6 Floors</option>
            <option value="7">7 Floors</option>
            <option value="8">8 Floors</option>
            <option value="9">9 Floors</option>
            <option value="10">10 Floors</option>
            <option value="15">15 Floors</option>
            <option value="20">20 Floors</option>
          </select>
          <label for="wsFloorsInput" class="wm-float-label is-static">Total Floors</label>
        </div>

        <!-- Total Seats -->
        <div class="wm-field">
          <input
            type="number"
            id="wsTotalSeatsInput"
            class="wm-input"
            min="0"
            placeholder=" "
          />
          <label for="wsTotalSeatsInput" class="wm-float-label">Total Seats</label>
        </div>

        <!-- Available Seats -->
        <div class="wm-field">
          <input
            type="number"
            id="wsAvailableSeatsInput"
            class="wm-input"
            min="0"
            placeholder=" "
          />
          <label for="wsAvailableSeatsInput" class="wm-float-label">Available Seats</label>
        </div>

        <!-- Address Information -->
        <div class="wm-section-divider"></div>
        <h3 class="wm-section-title">Address Information</h3>

        <div class="wm-field">
          <input
            type="text"
            id="wsAddressInput"
            class="wm-input"
            placeholder="Search a workspace or enter address"
            required
          />
          <label for="wsAddressInput" class="wm-float-label is-static">Full Address</label>
        </div>

        <h4 class="wm-subsection-title">Location Search</h4>
        <div class="wm-field">
          <input
            type="text"
            id="wsLocationSearchInput"
            class="wm-input"
            placeholder="Search..."
          />
          <label for="wsLocationSearchInput" class="wm-float-label is-static">Location Search</label>
          <div class="wm-help-text">Search a place, then drag the pin to fine-tune the location.</div>
        </div>

        <div class="wm-location-actions">
          <button type="button" id="useCurrentLocationBtn" class="btn-wm-outline">
            Use Current Location
          </button>
          <span class="wm-help-text m-0 p-0">Tip: click on map or drag the pin to set exact coordinates.</span>
        </div>

        <div class="wm-map-container">
          <div id="workspaceMapCanvas" class="wm-map-canvas"></div>
          <div id="mapCoordStatus" class="wm-map-status-pill">Chennai · 13.0827, 80.2707</div>
        </div>

        <div class="wm-field">
          <input
            type="text"
            id="wsPlaceIdInput"
            class="wm-input"
            placeholder=" "
          />
          <label for="wsPlaceIdInput" class="wm-float-label">Location Place ID (optional)</label>
        </div>

        <!-- City & Country -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="wm-field mb-0">
              <select id="wsCityInput" class="form-select wm-select" required>
              <option value="" selected disabled>Select city</option>
              @foreach ($cities as $city)
                <option value="{{ $city->id }}" data-name="{{ $city->name }}">{{ $city->name }}</option>
              @endforeach
            </select>
              <label for="wsCityInput" class="wm-float-label is-static">City *</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="wm-field mb-0">
              <select id="wsCountryInput" class="form-select wm-select" required>
                <option value="" disabled>Select country</option>
                <option value="India" selected>India</option>
                <option value="Singapore">Singapore</option>
                <option value="United Arab Emirates">United Arab Emirates</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="United States">United States</option>
              </select>
              <label for="wsCountryInput" class="wm-float-label is-static">Country *</label>
            </div>
          </div>
        </div>

        <!-- Pincode, Latitude, Longitude -->
        <div class="row g-3">
          <div class="col-md-4">
            <div class="wm-field mb-0">
              <input
                type="text"
                id="wsPincodeInput"
                class="wm-input"
                placeholder="e.g., 560001"
              />
              <label for="wsPincodeInput" class="wm-float-label is-static">Pincode</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="wm-field mb-0">
              <input
                type="text"
                id="wsLatInput"
                class="wm-input"
                placeholder="e.g., 13.0827"
                value="13.0827"
              />
              <label for="wsLatInput" class="wm-float-label is-static">Latitude</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="wm-field mb-0">
              <input
                type="text"
                id="wsLngInput"
                class="wm-input"
                placeholder="e.g., 80.2707"
                value="80.2707"
              />
              <label for="wsLngInput" class="wm-float-label is-static">Longitude</label>
            </div>
          </div>
        </div>

        <!-- Working Hours -->
        <div class="wm-section-divider"></div>
        <h3 class="wm-section-title">Working Hours</h3>
        <div id="workingHoursList" class="wm-hours-list">
          <!-- Generated dynamically for Monday through Sunday -->
        </div>

        <!-- Workspace Images (Optional) -->
        <div class="wm-section-divider"></div>
        <h3 class="wm-section-title mb-1">Workspace Images (Optional)</h3>
        <p class="wm-help-text p-0 mb-2">Upload up to 5 images.</p>

        <input type="file" id="wsImageFileInput" accept="image/*" multiple hidden />
        <input type="file" id="wsCameraFileInput" accept="image/*" capture="environment" hidden />

        <div class="wm-upload-actions">
          <button type="button" id="addImagesBtn" class="btn-wm-upload">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"></path>
            </svg>
            <span>Add Images</span>
          </button>
          <button type="button" id="takePhotoBtn" class="btn-wm-upload">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <circle cx="12" cy="12" r="3.2"></circle>
              <path d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"></path>
            </svg>
            <span>Take Photo</span>
          </button>
        </div>

        <p class="wm-help-text p-0 mb-0">Camera can capture multiple photos until limit is reached.</p>
        <div id="selectedImagesBar" class="wm-upload-counter-bar">Selected 0 / 5</div>
        <div id="imagePreviewsGrid" class="wm-image-previews"></div>

        <!-- Amenities -->
        <div class="wm-section-divider"></div>
        <h3 class="wm-section-title">Amenities</h3>
        <div class="wm-amenities-grid" id="amenitiesGrid">
          <label class="wm-amenity-item"><input type="checkbox" value="WiFi" /><span>WiFi</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Parking" /><span>Parking</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Cafeteria" /><span>Cafeteria</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Gym" /><span>Gym</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Reception" /><span>Reception</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Conference Room" /><span>Conference Room</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Security" /><span>Security</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Elevator" /><span>Elevator</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Wheelchair Access" /><span>Wheelchair Access</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Meeting Rooms" /><span>Meeting Rooms</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Phone Booths" /><span>Phone Booths</span></label>
          <label class="wm-amenity-item"><input type="checkbox" value="Lounge Area" /><span>Lounge Area</span></label>
        </div>

        <!-- Workspace Type -->
        <div class="wm-section-divider"></div>
        <h3 class="wm-section-title">Workspace Type</h3>
        <input type="hidden" id="wsCategoryInput" value="Long-term Leasing" />

        <div class="wm-type-pills" id="workspaceTypePills" role="radiogroup" aria-label="Workspace Type">
          <button type="button" class="wm-type-pill active" data-type="Long-term Leasing" aria-pressed="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="5" r="2"></circle>
              <circle cx="6" cy="17" r="2"></circle>
              <circle cx="18" cy="17" r="2"></circle>
              <line x1="11" y1="7" x2="7" y2="15"></line>
              <line x1="13" y1="7" x2="17" y2="15"></line>
            </svg>
            <span>Long-term Leasing</span>
          </button>

          <button type="button" class="wm-type-pill" data-type="Day Pass" aria-pressed="false">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="4" width="18" height="18" rx="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>Day Pass</span>
          </button>

          <button type="button" class="wm-type-pill" data-type="Meetings &amp; Events" aria-pressed="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
            </svg>
            <span>Meetings &amp; Events</span>
          </button>

          <button type="button" class="wm-type-pill" data-type="Virtual Office" aria-pressed="false">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 21h18"></path>
              <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
              <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
            </svg>
            <span>Virtual Office</span>
          </button>
        </div>

        <div id="categoryDescriptionBox" class="wm-category-callout">
          <div class="wm-category-callout-title">Category Description</div>
          <p id="categoryDescriptionText" class="wm-category-callout-text">
            Rent dedicated seats and private or managed office space for long-term usage.
          </p>
        </div>

        <!-- Listing Configuration -->
        <h3 class="wm-section-title">Listing Configuration</h3>
        <input type="hidden" id="wsSubcategoryInput" value="Coworking Space" />
        <input type="hidden" id="wsStatusInput" value="ACTIVE" />

        <div id="listingConfigCards" class="wm-listing-cards" role="radiogroup" aria-label="Listing Configuration">
          <!-- Rendered dynamically based on active Workspace Type -->
        </div>

        <!-- Generated Post Title Preview -->
        <div class="wm-generated-title-row">
          <span class="wm-generated-title-label">Generated post title:</span>
          <span id="generatedPostTitlePill" class="wm-generated-title-pill">Will be auto-generated after save</span>
        </div>

        <!-- Tax Configuration -->
        <h3 class="wm-section-title mt-4">Tax Configuration</h3>
        <div class="mb-3">
          <label class="wm-amenity-item" for="wsGstEnabledInput">
            <input type="checkbox" id="wsGstEnabledInput" />
            <span>GST/VAT enabled</span>
          </label>
          <div id="taxRateWrap" class="wm-tax-rate-wrap">
            <div class="wm-field mb-0 mt-3" style="max-width: 240px;">
              <input
                type="number"
                id="wsTaxRateInput"
                class="wm-input"
                min="0"
                max="50"
                step="0.5"
                value="18"
                placeholder=" "
              />
              <label for="wsTaxRateInput" class="wm-float-label is-static">GST / VAT Rate (%)</label>
            </div>
          </div>
        </div>

        <!-- Selected Category Code Readout -->
        <p class="wm-selected-category-caption">
          Selected category: <span id="selectedCategoryCode">COWORKING_SPACE</span>
        </p>
      </div>

      <!-- Sticky Footer Buttons -->
      <div class="wm-drawer-footer">
        <button type="button" class="btn-wm-drawer-cancel" data-bs-dismiss="offcanvas">Cancel</button>
        <button type="submit" id="wsSubmitBtn" class="btn-wm-drawer-submit">Create</button>
      </div>
    </form>
  </div>

  <!-- Manage Floors Preview Modal -->
  <div class="modal fade wm-modal" id="floorsModal" tabindex="-1" aria-labelledby="floorsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="floorsModalLabel">Workspace Floors</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="floorsModalBody"></div>
          <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn-wm-primary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Workspace Confirmation Modal -->
  <div class="modal fade wm-modal" id="deleteWorkspaceModal" tabindex="-1" aria-labelledby="deleteWorkspaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="deleteWorkspaceModalLabel">Delete Workspace</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-4">
            Are you sure you want to delete <strong id="deleteWorkspaceName" class="text-dark"></strong>? This action cannot be undone.
          </p>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="button" id="confirmDeleteWorkspaceBtn" class="btn btn-danger px-3 fw-semibold">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
    @php
      $__wsPayload = $workspaces->map(function ($w) {
        return [
          'id' => $w->id,
          'name' => $w->name,
          'code' => $w->code,
          'city_id' => $w->city_id,
          'city' => $w->city?->name ?? 'Chennai',
          'category_id' => $w->category_id,
          'category' => $w->category?->name ?? 'Long-term Leasing',
          'subcategory_id' => $w->subcategory_id,
          'subcategory' => $w->subcategory?->name ?? 'Coworking Space',
          'landmark' => $w->landmark ?? '',
          'address' => $w->address_line ?? '',
          'pincode' => $w->pincode ?? '',
          'place_id' => $w->place_id ?? '',
          'latitude' => (float) ($w->latitude ?: 13.0827),
          'longitude' => (float) ($w->longitude ?: 80.2707),
          'floors' => (int) ($w->total_floors ?: 1),
          'total_seats' => (int) ($w->total_seats ?: 0),
          'available_seats' => (int) ($w->available_seats ?: 0),
          'gst_enabled' => (bool) $w->gst_enabled,
          'tax_rate' => (float) ($w->tax_rate ?: 18),
          'description_html' => $w->description_html ?? '',
          'status' => $w->status,
          'approval_status' => $w->approval_status,
          'createdBy' => $w->creator?->name ?? 'Partner Owner',
          'createdAt' => optional($w->created_at)->format('n/j/Y,
g:i:s A') ?? '',
          'updatedAt' => optional($w->updated_at)->format('n/j/Y,
g:i:s A') ?? '',
          'floorsList' => $w->floors->map(fn ($f) => ['id' => $f->id, 'floor_number' => $f->floor_number, 'label' => $f->label, 'total_seats' => $f->total_seats])->values(),
          'amenity_ids' => $w->amenities->pluck('id')->values(),
        ];
      })->values();

      $__catsPayload = $categories->map(fn ($c) => [
        'id' => $c->id,
        'code' => $c->code,
        'name' => $c->name,
        'subcategories' => $c->subcategories->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'name' => $s->name])->values(),
      ])->values();

      $__wsBoot = [
        'mode' => 'server',
        'workspaces' => $__wsPayload,
        'cities' => $cities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        'categories' => $__catsPayload,
        'amenities' => $amenities->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
        'routes' => [
          'store' => route('provider.workspaces.store'),
          'updateTemplate' => route('provider.workspaces.update', ['workspace' => '__ID__']),
          'destroyTemplate' => route('provider.workspaces.destroy', ['workspace' => '__ID__']),
          'submitReviewTemplate' => route('provider.workspaces.submit-review', ['workspace' => '__ID__']),
          'floorsTemplate' => route('provider.workspaces.floors.upsert', ['workspace' => '__ID__']),
          'imagesTemplate' => route('provider.workspaces.images.store', ['workspace' => '__ID__']),
        ],
      ];
    @endphp
    window.__SW_WORKSPACES_DATA__ = @json($__wsBoot);
  </script>
  <script src="{{ asset('vendor-panel/js/workspace-management.js') }}"></script>
@endpush
