@extends('layouts.website')

@section('title', 'SpaceWorq - Find a Workspace | Coworking, Day Pass & Enterprise Offices')
@section('active_nav', 'find')

@section('content')
  <!-- ==========================================================================
         Hero Section
         ========================================================================== -->
  <main>
    <section class="hero-section" id="heroSection">
      <div class="hero-glow-circle"></div>
      <div class="container hero-content">
        <!-- Live Verified Badge -->
        <div class="hero-tag">
          <span class="tag-dot"></span>
          <span class="tag-text">Verified Spaces</span>
          <span class="tag-count">54 live</span>
        </div>

        <h1 class="hero-title">Find your next professional workspace</h1>
        <p class="hero-subtitle">Discover flexible coworking, private offices, meeting rooms, shared workspaces, and
          managed spaces across India.</p>

        <!-- Search & Filter Card -->
        <div class="hero-filter-panel">
          <!-- Quick Nav Chips -->
          <div class="quick-nav-row" role="tablist">
            <button class="quick-nav-chip active" data-category="LONG_TERM_LEASING" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
              </svg>
              <span>Long-term Leasing</span>
            </button>
            <button class="quick-nav-chip" data-category="VIRTUAL_OFFICE" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
              <span>Virtual Office</span>
            </button>
            <button class="quick-nav-chip" data-category="MEETINGS_EVENTS" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              <span>Meetings & Events</span>
            </button>
            <button class="quick-nav-chip" data-category="DAY_PASS" role="tab">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span>Day Pass</span>
            </button>
          </div>

          <!-- Search Action Row -->
          <div class="hero-search-action-row">
            <div class="hero-search-row">
              <!-- City Selector Dropdown -->
              <div class="hero-city-selector-wrap">
                <button class="hero-city-selector" id="heroCitySelector" type="button" aria-haspopup="true">
                  <span id="selectedCityLabel">All Cities</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <ul class="city-dropdown-menu" id="cityDropdown">
                  <li class="city-dropdown-item selected" data-city="all" data-name="All Cities">
                    <span>All Cities</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </li>
                  <li class="city-dropdown-item" data-city="bengaluru" data-name="Bengaluru">
                    <span>Bengaluru</span>
                  </li>
                  <li class="city-dropdown-item" data-city="chennai" data-name="Chennai">
                    <span>Chennai</span>
                  </li>
                  <li class="city-dropdown-item" data-city="mumbai" data-name="Mumbai">
                    <span>Mumbai</span>
                  </li>
                  <li class="city-dropdown-item" data-city="delhi" data-name="Delhi NCR">
                    <span>Delhi NCR</span>
                  </li>
                  <li class="city-dropdown-item" data-city="hyderabad" data-name="Hyderabad">
                    <span>Hyderabad</span>
                  </li>
                  <li class="city-dropdown-item" data-city="pune" data-name="Pune">
                    <span>Pune</span>
                  </li>
                </ul>
              </div>

              <!-- Search Input Wrap -->
              <div class="hero-search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="heroSearchInput" class="hero-search-input"
                  placeholder="Search by city, area, or workspace name..." autocomplete="off" />
                <button class="clear-search-btn" id="clearSearchBtn" aria-label="Clear search">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
            </div>

            <button class="hero-primary-cta" id="heroSearchBtn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <span>Find a Workspace</span>
            </button>

            <!-- Suggestions Panel -->
            <div class="search-suggestions-panel" id="searchSuggestions">
              <div class="search-suggestion-title">Suggested cities or localities</div>
              <div class="suggestions-list"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Featured Cities Section (Instant Booking)
           ========================================================================== -->
    <section class="featured-cities-section">
      <div class="container">
        <div class="cities-card-container">
          <div class="cities-header">
            <h2 class="cities-title">Featured Cities with Instant Booking</h2>
            <div class="cities-nav">
              <button class="city-nav-btn" id="cityNavPrev" aria-label="Previous cities">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              <button class="city-nav-btn" id="cityNavNext" aria-label="Next cities">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>

          <div class="cities-grid" id="citiesGrid">
            <!-- Rendered dynamically by main.js -->
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Journey Band (3 Value Cards)
           ========================================================================== -->
    <section class="journey-band-section">
      <div class="container">
        <div class="journey-grid">
          <div class="journey-card"
            onclick="document.getElementById('workspacesCatalog').scrollIntoView({behavior: 'smooth'})">
            <div class="journey-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </div>
            <div>
              <h3 class="journey-title">Find a Workspace</h3>
              <p class="journey-text">Coworking, private offices, meeting rooms, day passes, and virtual offices.</p>
            </div>
          </div>

          <div class="journey-card"
            onclick="document.getElementById('onePlatformSection').scrollIntoView({behavior: 'smooth'})">
            <div class="journey-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
              </svg>
            </div>
            <div>
              <h3 class="journey-title">Expansion Opportunities</h3>
              <p class="journey-text">Plan your next city, centre, and workspace model with SpaceWorq intelligence.</p>
            </div>
          </div>

          <div class="journey-card open-consultation-btn">
            <div class="journey-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <div>
              <h3 class="journey-title">Partner With Us</h3>
              <p class="journey-text">List buildings, publish capacity, and connect demand with partner inventory.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Expansion Opportunities Section (Matching Screenshot)
           ========================================================================== -->
    <section class="expansion-section" id="expansionSection">
      <div class="container">
        <div class="expansion-banner-card">
          <div class="expansion-tag">NEW BUILDINGS &amp; OFFICE/COMMERCIAL</div>
          <h2 class="expansion-title">Grow your network with new locations</h2>
          <p class="expansion-subtitle">Discover new buildings and commercial spaces where SPACEWORQ can help you launch
            or expand your next centre.</p>

          <!-- Expansion Filters -->
          <div class="expansion-filter-row">
            <div class="expansion-city-pill" id="expansionCityPill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span>All Cities</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </div>

            <div class="expansion-chips-group">
              <button class="expansion-chip active" type="button">All</button>
              <button class="expansion-chip" type="button">New Building</button>
              <button class="expansion-chip" type="button">Office/Commercial</button>
            </div>
          </div>

          <!-- Expansion Search -->
          <div class="expansion-search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="expansionSearchInput" name="expansion_search"
              aria-label="Search expansion locations by name, city, or area" autocomplete="off"
              placeholder="Search by name, city, or area..." />
          </div>

          <!-- Bottom Feature Cards -->
          <div class="expansion-features-grid">
            <div class="expansion-feature-card">
              <div class="feature-icon-circle">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
              </div>
              <div>
                <h4 class="feature-title">Market Expansion</h4>
                <p class="feature-text">Prioritise high-potential neighbourhoods and scale faster with city-wise workspace
                  intelligence.</p>
              </div>
            </div>

            <div class="expansion-feature-card">
              <div class="feature-icon-circle">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                  <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
              </div>
              <div>
                <h4 class="feature-title">Supply Matchmaking</h4>
                <p class="feature-text">Connect operators with landlords, developers, and property teams that need managed
                  workspace demand.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Category Showcase ("One platform. Every kind of workday.")
           ========================================================================== -->
    <section class="category-showcase-section" id="onePlatformSection">
      <div class="container">
        <div class="section-head">
          <span class="section-badge">Solutions</span>
          <h2 class="section-title">One platform. Every kind of workday.</h2>
          <p class="section-subtitle">Start with what you need</p>
        </div>

        <div class="category-cards-grid" id="categoryCardsGrid">
          <!-- Rendered dynamically by main.js -->
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Enterprise Consultation Callout Banner
           ========================================================================== -->
    <section class="enterprise-section">
      <div class="container">
        <div class="enterprise-banner">
          <div class="enterprise-left">
            <div class="enterprise-icon-circle">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
              </svg>
            </div>
            <div>
              <h3 class="enterprise-title">Need expert consultation to upgrade your office space?</h3>
              <p class="enterprise-desc">Talk to our dedicated enterprise manager for comprehensive and cost-effective
                rentals for premium office spaces.</p>
            </div>
          </div>
          <button class="btn-primary enterprise-btn open-consultation-btn">
            <span>Talk to an expert</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Live Workspaces Catalog & Discovery Engine
           ========================================================================== -->
    <section class="workspace-catalog-section" id="workspacesCatalog">
      <div class="container">
        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
          <div class="filter-top-row">
            <!-- Category Tabs -->
            <div class="category-tab-filters" role="tablist">
              <button class="tab-filter-btn" data-category="all">All Spaces</button>
              <button class="tab-filter-btn active" data-category="LONG_TERM_LEASING">Long-term Leasing</button>
              <button class="tab-filter-btn" data-category="VIRTUAL_OFFICE">Virtual Office</button>
              <button class="tab-filter-btn" data-category="MEETINGS_EVENTS">Meetings & Events</button>
              <button class="tab-filter-btn" data-category="DAY_PASS">Day Pass</button>
            </div>

            <!-- Right Controls: Amenities Toggle & Sorting -->
            <div class="filter-controls-right">
              <button class="amenity-toggle-btn" id="amenityToggleBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="4" y1="21" x2="4" y2="14"></line>
                  <line x1="4" y1="10" x2="4" y2="3"></line>
                  <line x1="12" y1="21" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12" y2="3"></line>
                  <line x1="20" y1="21" x2="20" y2="16"></line>
                  <line x1="20" y1="12" x2="20" y2="3"></line>
                  <line x1="1" y1="14" x2="7" y2="14"></line>
                  <line x1="9" y1="8" x2="15" y2="8"></line>
                  <line x1="17" y1="16" x2="23" y2="16"></line>
                </svg>
                <span>Amenities <span id="amenityCountDisplay"></span></span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>

              <div class="sort-select-wrap">
                <select class="sort-select" id="sortSelect" aria-label="Sort spaces">
                  <option value="recommended">Sort: Recommended</option>
                  <option value="priceLowHigh">Price: Low to High</option>
                  <option value="priceHighLow">Price: High to Low</option>
                  <option value="rating">Highest Rated</option>
                </select>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </div>
          </div>

          <!-- Collapsible Amenities Panel -->
          <div class="amenities-panel" id="amenitiesPanel">
            <!-- Rendered dynamically by main.js -->
          </div>
        </div>

        <!-- Results Counter & Action Bar -->
        <div class="results-header-bar">
          <div class="results-count" id="resultsCount">8 Spaces Found</div>
          <div class="results-meta-actions">
            <button class="clear-filters-link" id="clearFiltersBtn" style="display: none;">Clear all filters</button>
          </div>
        </div>

        <!-- Workspaces Grid -->
        <div class="workspaces-grid" id="workspacesGrid">
          <!-- Rendered dynamically by main.js -->
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           How It Works Section ("Find and book a workspace in three simple steps.")
           ========================================================================== -->
    <section class="how-it-works-section" id="howItWorks">
      <div class="container">
        <div class="section-head">
          <span class="section-badge">Simple Process</span>
          <h2 class="section-title">How it works</h2>
          <p class="section-subtitle">Find and book a workspace in three simple steps.</p>
        </div>

        <div class="how-it-works-grid">
          <div class="step-card">
            <div class="step-header">
              <div class="step-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </div>
              <div class="step-number">01</div>
            </div>
            <h3 class="step-title">Search</h3>
            <p class="step-desc">Browse spaces by city, locality, and workspace type that fits your team.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <div class="step-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
                </svg>
              </div>
              <div class="step-number">02</div>
            </div>
            <h3 class="step-title">Compare</h3>
            <p class="step-desc">Check amenities, pricing, reviews, and availability side by side.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <div class="step-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
              </div>
              <div class="step-number">03</div>
            </div>
            <h3 class="step-title">Book</h3>
            <p class="step-desc">Reserve instantly or schedule a visit with secure, verified bookings.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
           Why Choose SpaceWorq Section
           ========================================================================== -->
    <section class="why-choose-section" id="whyChooseUs">
      <div class="container">
        <div class="why-choose-box">
          <h2 class="why-choose-title">Why choose SpaceWorq?</h2>
          <div class="why-benefits-grid">
            <div class="benefit-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <span>Zero brokerage fee - Direct access to best properties</span>
            </div>

            <div class="benefit-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <span>Quick turnaround - Swift and effective solutions</span>
            </div>

            <div class="benefit-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <span>Largest network - 30+ cities with 5,000+ properties</span>
            </div>

            <div class="benefit-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              <span>Expert support - One-on-one guidance throughout</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ==========================================================================
         Comparison Floating Bar
         ========================================================================== -->
  <div class="comparison-floating-bar" id="comparisonFloatingBar">
    <div class="compare-bar-info">
      <span class="compare-badge" id="compareCountDisplay">0</span>
      <span>Spaces Selected for Comparison</span>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
      <button class="btn-open-compare" id="openCompareModalBtn">Compare Now</button>
      <button class="btn-clear-compare" id="clearCompareBtn">Clear</button>
    </div>
  </div>

  <!-- ==========================================================================
         Modals
         ========================================================================== -->

  <!-- 1. Comparison Modal -->
  <div class="modal-overlay" id="comparisonModal" role="dialog" aria-modal="true">
    <div class="modal-content comparison-modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Compare Workspaces</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="comparison-table-wrap">
          <table class="comparison-table">
            <thead id="comparisonTableHead"></thead>
            <tbody id="comparisonTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Instant Booking Modal -->
  <div class="modal-overlay" id="bookingModal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h3 class="modal-title" id="bookingSpaceName">Book Workspace</h3>
          <p style="font-size: 0.82rem; color: var(--text-muted);" id="bookingSpaceLocation">Bengaluru</p>
        </div>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <form id="bookingForm">
          <!-- Date Selection -->
          <div class="form-group">
            <label class="form-label" for="bookingDate">Select Booking Date</label>
            <input type="date" class="form-control" id="bookingDate" required />
          </div>

          <!-- Time Slot Selection -->
          <div class="form-group">
            <label class="form-label">Choose Slot</label>
            <div class="slot-selection-row">
              <div class="slot-option" data-slot="morning">
                <div class="slot-time">Morning</div>
                <div class="slot-desc">9:00 AM - 1:00 PM</div>
              </div>
              <div class="slot-option" data-slot="afternoon">
                <div class="slot-time">Afternoon</div>
                <div class="slot-desc">1:00 PM - 5:00 PM</div>
              </div>
              <div class="slot-option selected" data-slot="fullday">
                <div class="slot-time">Full Day</div>
                <div class="slot-desc">9:00 AM - 8:00 PM</div>
              </div>
            </div>
          </div>

          <!-- Seats Counter -->
          <div class="form-group">
            <label class="form-label">Number of Desks / Seats</label>
            <div class="seats-counter-wrap">
              <button type="button" class="counter-btn" id="seatDecrement">-</button>
              <div class="counter-val" id="seatCount">1</div>
              <button type="button" class="counter-btn" id="seatIncrement">+</button>
            </div>
          </div>

          <!-- User Details -->
          <div class="form-group">
            <label class="form-label" for="bookerName">Full Name</label>
            <input type="text" class="form-control" id="bookerName" placeholder="e.g. Rahul Sharma" required />
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="bookerEmail">Work Email</label>
              <input type="email" class="form-control" id="bookerEmail" placeholder="rahul@company.com" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="bookerPhone">Phone Number</label>
              <input type="tel" class="form-control" id="bookerPhone" placeholder="+91 98765 43210" required />
            </div>
          </div>

          <!-- Promo Code -->
          <div class="form-group">
            <label class="form-label">Promo Code (Try: WKND20, NEW100, WORQ500)</label>
            <div class="coupon-row">
              <input type="text" class="form-control" id="couponInput" placeholder="Enter coupon code" />
              <button type="button" class="btn-apply-coupon" id="applyCouponBtn">Apply</button>
            </div>
          </div>

          <!-- Booking Summary -->
          <div class="booking-summary-box">
            <div class="summary-row">
              <span>Subtotal</span>
              <span id="summarySubtotal">₹499</span>
            </div>
            <div class="summary-row" id="discountRow" style="display: none; color: #10B981;">
              <span>WorqPromo Discount</span>
              <span id="summaryDiscount">-₹100</span>
            </div>
            <div class="summary-row">
              <span>GST (18%)</span>
              <span id="summaryGst">₹90</span>
            </div>
            <div class="summary-row total">
              <span>Total Payable</span>
              <span id="summaryTotal">₹589</span>
            </div>
          </div>

          <button type="submit" class="modal-submit-btn">Confirm & Instant Book</button>
        </form>
      </div>
    </div>
  </div>

  <!-- 3. Enterprise Consultation Modal -->
  <div class="modal-overlay" id="consultationModal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Talk to an Enterprise Expert</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
          Tell us about your team's workspace requirements. Our senior commercial manager will provide curated proposals
          and customized pricing with zero brokerage.
        </p>
        <form id="consultationForm" method="POST" action="{{ route('website.consultations.store') }}">
          @csrf
          <div class="form-group">
            <label class="form-label" for="consultName">Your Name</label>
            <input type="text" class="form-control" id="consultName" name="name" value="{{ old('name') }}"
              placeholder="e.g. Priya Iyer" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="consultCompany">Company Name</label>
            <input type="text" class="form-control" id="consultCompany" name="company_name"
              value="{{ old('company_name') }}" placeholder="e.g. Acme Tech India" required />
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="consultPhone">Contact Number</label>
              <input type="tel" class="form-control" id="consultPhone" name="phone" value="{{ old('phone') }}"
                placeholder="+91 98765 43210" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="consultCity">Target City</label>
              <select class="form-control" id="consultCity" name="city_name">
                <option value="Bengaluru">Bengaluru</option>
                <option value="Mumbai">Mumbai</option>
                <option value="Delhi NCR">Delhi NCR / Gurgaon</option>
                <option value="Chennai">Chennai</option>
                <option value="Hyderabad">Hyderabad</option>
                <option value="Pune">Pune</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="consultSeats">Estimated Team Size / Desks Needed</label>
            <select class="form-control" id="consultSeats" name="seats_range">
              <option value="10-25">10 - 25 Desks</option>
              <option value="25-50">25 - 50 Desks</option>
              <option value="50-100">50 - 100 Desks</option>
              <option value="100+">100+ Enterprise Managed Floor</option>
            </select>
          </div>
          <button type="submit" class="modal-submit-btn">Request Callback</button>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    @php
      $__cityImageFallback = [
        'bengaluru' => asset('assets/images/cities/bangalore.jpg'),
        'bangalore' => asset('assets/images/cities/bangalore.jpg'),
        'chennai' => asset('assets/images/cities/chennai.jpg'),
        'hyderabad' => asset('assets/images/cities/hyderabad.jpg'),
        'mumbai' => asset('assets/images/cities/mumbai.jpg'),
        'delhi' => asset('assets/images/cities/delhi-ncr.jpg'),
        'delhi-ncr' => asset('assets/images/cities/delhi-ncr.jpg'),
        'pune' => asset('assets/images/cities/pune.jpg'),
      ];

      $__serverCities = $cities->map(function ($c) use ($__cityImageFallback) {
        $slug = strtolower($c->slug ?? $c->name);
        $img = $c->image_path
          ? asset($c->image_path)
          : ($__cityImageFallback[$slug] ?? asset('assets/images/cities/' . $slug . '.jpg'));
        return [
          'id' => $slug,
          'name' => $c->name,
          'image' => $img,
          'spaces' => ($c->workspaces_count ?? null) ? ($c->workspaces_count . '+ spaces') : 'Explore spaces',
        ];
      })->values();

      $__serverWorkspaces = $workspaces->map(function ($w) {
        $cfg = $w->listingConfigs->first();
        $priceDay = $cfg?->price_per_day ?? $cfg?->price_per_month ?? 499;
        $priceMonth = $cfg?->price_per_month ?? 6500;
        $catCode = $w->category?->code ?? 'LONG_TERM_LEASING';
        $catMap = [
          'LONG_TERM_LEASING' => ['key' => 'longterm', 'label' => 'Long-term'],
          'DAY_PASS' => ['key' => 'daypass', 'label' => 'Day Pass'],
          'MEETINGS_EVENTS' => ['key' => 'meetings', 'label' => 'Meetings'],
          'VIRTUAL_OFFICE' => ['key' => 'virtual', 'label' => 'Virtual Office'],
        ];
        $mapped = $catMap[$catCode] ?? ['key' => 'longterm', 'label' => ($w->category?->name ?? 'Workspace')];
        $primaryImg = $w->images->firstWhere('is_primary', true) ?? $w->images->first();
        $imgSrc = $primaryImg?->url ?? $primaryImg?->image_path ?? 'assets/images/space1.png';
        return [
          'id' => 'ws-' . $w->id,
          'db_id' => $w->id,
          'name' => $w->name,
          'category' => $mapped['key'],
          'categoryLabel' => $mapped['label'],
          'city' => $w->city?->name ?? 'Bengaluru',
          'locality' => $w->locality?->name ?? ($w->landmark ?: 'Commercial Hub'),
          'nearBy' => $w->landmark ?: ($w->city?->name ?? 'Central'),
          'image' => str_starts_with($imgSrc, 'http') ? $imgSrc : asset(ltrim($imgSrc, '/')),
          'rating' => (float) ($w->rating_avg ?: 4.7),
          'reviews' => (int) ($w->reviews_count ?: 0),
          'priceDay' => (float) $priceDay,
          'priceMonth' => (float) $priceMonth,
          'amenities' => $w->amenities->pluck('name')->values()->all(),
          'seats' => (int) ($w->available_seats ?: $w->total_seats ?: 25),
          'floors' => (int) ($w->total_floors ?: 1),
          'instantBookable' => (bool) $w->is_instant_bookable,
        ];
      })->values();
    @endphp
    window.__SPACEWORQ_SERVER_DATA__ = {
      cities: @json($__serverCities),
      workspaces: @json($__serverWorkspaces)
    };
  </script>
  <script src="{{ asset('website/js/data.js') }}"></script>
  <script src="{{ asset('website/js/main.js') }}"></script>
@endpush