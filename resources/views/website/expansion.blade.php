@extends('layouts.website')

@section('title', 'SpaceWorq - Expansion Opportunities')
@section('meta_description', 'Discover new buildings and commercial spaces where SPACEWORQ can help you launch or expand your next centre across India.')
@section('active_nav', 'expansion')
@section('body_class', 'expansion-page-body')

@push('styles')
  <style>
    /* Specific styles for Expansion Page (Matching Screenshot) */
    body.expansion-page-body {
      background-color: #EEF2F6;
    }

    .expansion-page-container {
      padding: 32px 0 60px 0;
    }

    /* Hero Card Box */
    .exp-hero-card {
      background: #FFFFFF;
      border: 1px solid rgba(200, 225, 226, 0.7);
      border-radius: 16px;
      padding: 32px 36px;
      margin-bottom: 24px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    .exp-kicker {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--primary);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }

    .exp-title {
      font-size: 2.15rem;
      font-weight: 800;
      color: #17363B;
      letter-spacing: -0.5px;
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .exp-copy {
      font-size: 0.98rem;
      color: #6B858A;
      line-height: 1.5;
    }

    /* Filters Row */
    .exp-controls-row {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 28px;
    }

    .exp-filters-top {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* City Pill Dropdown */
    .exp-city-dropdown-wrap {
      position: relative;
    }

    .exp-city-pill-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-full);
      font-size: 0.88rem;
      font-weight: 600;
      color: #17363B;
      cursor: pointer;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      transition: var(--transition);
    }

    .exp-city-pill-btn:hover {
      border-color: var(--primary);
    }

    .exp-city-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      width: 220px;
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
      list-style: none;
      padding: 6px 0;
      z-index: 50;
      display: none;
    }

    .exp-city-menu.open {
      display: block;
      animation: fadeIn 0.2s ease-out;
    }

    .exp-city-item {
      padding: 9px 16px;
      font-size: 0.88rem;
      color: #17363B;
      cursor: pointer;
      transition: var(--transition);
    }

    .exp-city-item:hover,
    .exp-city-item.active {
      background: #F0FBFE;
      color: var(--primary);
      font-weight: 600;
    }

    /* Subtype Chips */
    .exp-chips-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .exp-chip-btn {
      padding: 7px 18px;
      font-size: 0.86rem;
      font-weight: 600;
      border-radius: var(--radius-full);
      border: 1px solid var(--border-color);
      background: #FFFFFF;
      color: #17363B;
      cursor: pointer;
      transition: var(--transition);
    }

    .exp-chip-btn:hover {
      border-color: var(--primary);
    }

    .exp-chip-btn.active {
      background: var(--primary);
      border-color: var(--primary);
      color: #FFFFFF;
      box-shadow: 0 2px 8px rgba(0, 108, 118, 0.25);
    }

    /* Search Box */
    .exp-search-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 10px 16px;
      max-width: 480px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .exp-search-wrap svg {
      width: 17px;
      height: 17px;
      color: #94A3B8;
      flex-shrink: 0;
    }

    .exp-search-input {
      width: 100%;
      border: none;
      background: transparent;
      font-size: 0.925rem;
      color: #17363B;
    }

    .exp-search-input::placeholder {
      color: #94A3B8;
    }

    /* Results Header */
    .exp-results-header {
      margin-bottom: 24px;
    }

    .exp-results-count {
      font-size: 1.15rem;
      font-weight: 700;
      color: #17363B;
    }

    /* Empty State (Matching Screenshot) */
    .exp-empty-state-box {
      background: transparent;
      padding: 70px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      margin-bottom: 40px;
    }

    .exp-empty-icon {
      width: 52px;
      height: 52px;
      color: #94A3B8;
      margin-bottom: 14px;
      stroke-width: 1.6;
    }

    .exp-empty-text {
      font-size: 0.95rem;
      color: #64748B;
      font-weight: 500;
    }

    /* Opportunities Cards Grid (When spaces are shown) */
    .exp-cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }

    /* Bottom Feature Highlights (2-Column Grid Matching Screenshot) */
    .exp-features-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .exp-feature-card {
      background: #FFFFFF;
      border: 1px solid rgba(200, 225, 226, 0.7);
      border-radius: 14px;
      padding: 24px 28px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
      transition: var(--transition);
    }

    .exp-feature-card:hover {
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 108, 118, 0.08);
    }

    .exp-feature-icon {
      width: 32px;
      height: 32px;
      color: var(--primary);
      flex-shrink: 0;
      margin-top: 2px;
    }

    .exp-feature-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #17363B;
      margin-bottom: 6px;
    }

    .exp-feature-text {
      font-size: 0.88rem;
      color: #6B858A;
      line-height: 1.5;
    }

    @media (max-width: 768px) {
      .exp-features-grid {
        grid-template-columns: 1fr;
      }

      .exp-cards-grid {
        grid-template-columns: 1fr;
      }

      .exp-title {
        font-size: 1.65rem;
      }
    }
  </style>
@endpush

@section('content')
  <!-- ==========================================================================
         Main Expansion Page Content (Matches Screenshot)
         ========================================================================== -->
  <main class="expansion-page-container">
    <div class="container">

      <!-- Top Hero Card -->
      <div class="exp-hero-card">
        <div class="exp-kicker">NEW BUILDINGS &amp; OFFICE/COMMERCIAL</div>
        <h1 class="exp-title">Grow your network with new locations</h1>
        <p class="exp-copy">Discover new buildings and commercial spaces where SPACEWORQ can help you launch or expand
          your next centre.</p>
      </div>

      <!-- Controls Row: City Dropdown, Filter Chips, Search Input -->
      <div class="exp-controls-row">
        <div class="exp-filters-top">
          <!-- City Dropdown -->
          <div class="exp-city-dropdown-wrap">
            <button class="exp-city-pill-btn" id="expCityPillBtn" type="button">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span id="expCityLabel">All Cities</span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <ul class="exp-city-menu" id="expCityMenu">
              <li class="exp-city-item active" data-city="all">All Cities</li>
              <li class="exp-city-item" data-city="bengaluru">Bengaluru</li>
              <li class="exp-city-item" data-city="mumbai">Mumbai</li>
              <li class="exp-city-item" data-city="delhi">Delhi NCR</li>
              <li class="exp-city-item" data-city="chennai">Chennai</li>
              <li class="exp-city-item" data-city="hyderabad">Hyderabad</li>
              <li class="exp-city-item" data-city="pune">Pune</li>
            </ul>
          </div>

          <!-- Subtype Filter Chips (Matches Screenshot: Office/Commercial selected) -->
          <div class="exp-chips-group">
            <button class="exp-chip-btn" data-subtype="ALL" type="button">All</button>
            <button class="exp-chip-btn" data-subtype="NEW_BUILDING" type="button">New Building</button>
            <button class="exp-chip-btn active" data-subtype="OFFICE_COMMERCIAL" type="button">Office/Commercial</button>
          </div>
        </div>

        <!-- Search Input -->
        <div class="exp-search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" class="exp-search-input" id="expSearchInput" name="expansion_search"
            aria-label="Search expansion opportunities by name, city, or area" autocomplete="off"
            placeholder="Search by name, city, or area..." />
        </div>
      </div>

      <!-- Results Count Header -->
      <div class="exp-results-header">
        <h2 class="exp-results-count" id="expResultsCount">0 Opportunities Found</h2>
      </div>

      <!-- Opportunities Container (Empty State shown in screenshot) -->
      <div id="expResultsContainer">
        <div class="exp-empty-state-box" id="expEmptyState">
          <svg class="exp-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <p class="exp-empty-text">No expansion opportunities match your filters</p>
        </div>
      </div>

      <!-- Bottom Feature Cards (2 Column Grid Matching Screenshot) -->
      <div class="exp-features-grid">
        <div class="exp-feature-card">
          <svg class="exp-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <div>
            <h3 class="exp-feature-title">Market Expansion</h3>
            <p class="exp-feature-text">Prioritise high-potential neighbourhoods and scale faster with city-wise workspace
              intelligence.</p>
          </div>
        </div>

        <div class="exp-feature-card">
          <svg class="exp-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <div>
            <h3 class="exp-feature-title">Supply Matchmaking</h3>
            <p class="exp-feature-text">Connect operators with landlords, developers, and property teams that need managed
              workspace demand.</p>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- ==========================================================================
         Modals (Consultation & Sign In)
         ========================================================================== -->
  <!-- Consultation Modal -->
  <div class="modal-overlay" id="consultationModal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">List Building or Expand Centre</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
          Submit your property or operator expansion requirements to connect with SpaceWorq's nationwide enterprise
          network.
        </p>
        <form id="consultationForm" method="POST" action="{{ route('website.consultations.store') }}">
          @csrf
          <div class="form-group">
            <label class="form-label" for="consultName">Contact Name</label>
            <input type="text" class="form-control" id="consultName" name="name" value="{{ old('name') }}"
              placeholder="e.g. Ramesh Kulkarni" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="consultCompany">Organization / Developer Name</label>
            <input type="text" class="form-control" id="consultCompany" name="company_name"
              value="{{ old('company_name') }}" placeholder="e.g. Prestige Commercials" required />
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="consultPhone">Contact Phone</label>
              <input type="tel" class="form-control" id="consultPhone" name="phone" value="{{ old('phone') }}"
                placeholder="+91 98765 43210" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="consultCity">Primary City</label>
              <select class="form-control" id="consultCity" name="city_name">
                <option value="Bengaluru">Bengaluru</option>
                <option value="Mumbai">Mumbai</option>
                <option value="Delhi NCR">Delhi NCR</option>
                <option value="Chennai">Chennai</option>
                <option value="Hyderabad">Hyderabad</option>
                <option value="Pune">Pune</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-control" name="category_interest">
              <option>Workspace Provider (Listing Inventory)</option>
              <option>Building Owner (Leasing Floorplate)</option>
              <option>Developer (Upcoming Commercial Project)</option>
            </select>
          </div>
          <button type="submit" class="modal-submit-btn">Submit Expansion Request</button>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const toastContainer = document.getElementById('toastContainer');
      function showToast(msg, type = 'success') {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        const dotColor = type === 'warning' ? '#F59E0B' : '#10B981';
        toast.innerHTML = `<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:${dotColor};"></span><span>${msg}</span>`;
        toastContainer.appendChild(toast);
        setTimeout(() => {
          toast.style.opacity = '0';
          setTimeout(() => toast.remove(), 300);
        }, 3200);
      }

      @php
        $__expItems = $workspaces->map(function ($w) {
          $cfg = $w->listingConfigs->first();
          return [
            'id' => $w->id,
            'name' => $w->name,
            'city' => $w->city?->name ?? 'Bengaluru',
            'locality' => $w->locality?->name ?? ($w->landmark ?: ''),
            'subtype' => $w->subcategory?->code ?? 'NEW_BUILDING',
            'subtypeLabel' => $w->subcategory?->name ?? 'New Building',
            'seats' => $w->total_seats,
            'price' => $cfg?->price_per_sqft ?? $cfg?->price_per_month ?? null,
            'image' => asset('assets/images/space2.png'),
          ];
        })->values();
      @endphp
      const expansionData = @json($__expItems);

      let expState = { city: 'all', subtype: 'ALL', query: '' };
      const expCityPillBtn = document.getElementById('expCityPillBtn');
      const expCityMenu = document.getElementById('expCityMenu');
      const expCityLabel = document.getElementById('expCityLabel');
      const expSearchInput = document.getElementById('expSearchInput');
      const expChipBtns = document.querySelectorAll('.exp-chip-btn');
      const expResultsCount = document.getElementById('expResultsCount');
      const expResultsContainer = document.getElementById('expResultsContainer');

      function renderExpansionSpaces() {
        if (!expResultsContainer) return;
        let filtered = expansionData.filter(item => {
          if (expState.city !== 'all' && item.city.toLowerCase() !== expState.city.toLowerCase()) return false;
          if (expState.subtype !== 'ALL' && item.subtype !== expState.subtype) return false;
          if (expState.query && !`${item.name} ${item.city} ${item.locality}`.toLowerCase().includes(expState.query.toLowerCase())) return false;
          return true;
        });

        if (expResultsCount) {
          expResultsCount.textContent = `${filtered.length} ${filtered.length === 1 ? 'opportunity' : 'opportunities'} available`;
        }

        if (filtered.length === 0) {
          expResultsContainer.innerHTML = `
              <div class="exp-empty-state-box">
                <svg class="exp-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M3 21h18"></path>
                  <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                  <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                  <path d="M9 7h2"></path>
                  <path d="M9 11h2"></path>
                  <path d="M9 15h2"></path>
                </svg>
                <p class="exp-empty-text">No spaces available yet for the selected filters.</p>
              </div>
            `;
          return;
        }

        expResultsContainer.innerHTML = '<div class="exp-cards-grid">' + filtered.map(item => `
            <div class="workspace-card" style="background:#fff; border-radius:14px; border:1px solid #E2E8F0; overflow:hidden;">
              <img src="${item.image}" alt="${item.name}" style="width:100%; height:170px; object-fit:cover;" />
              <div style="padding:16px;">
                <div style="font-size:0.75rem; font-weight:700; color:var(--primary); text-transform:uppercase;">${item.subtypeLabel}</div>
                <h3 style="font-size:1.05rem; font-weight:700; margin:4px 0; color:#17363B;">${item.name}</h3>
                <p style="font-size:0.85rem; color:#64748B; margin-bottom:10px;">${item.locality ? item.locality + ' • ' : ''}${item.city}</p>
                <button type="button" class="btn-get-started open-consultation-btn" style="width:100%; justify-content:center;">Request Details</button>
              </div>
            </div>
          `).join('') + '</div>';

        expResultsContainer.querySelectorAll('.open-consultation-btn').forEach(b => {
          b.addEventListener('click', () => {
            const modal = document.getElementById('consultationModal');
            modal?.classList.add('open');
            document.body.style.overflow = 'hidden';
          });
        });
      }

      expCityPillBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        expCityMenu?.classList.toggle('open');
      });
      document.addEventListener('click', () => expCityMenu?.classList.remove('open'));
      document.querySelectorAll('.exp-city-item').forEach(item => {
        item.addEventListener('click', () => {
          document.querySelectorAll('.exp-city-item').forEach(i => i.classList.remove('active'));
          item.classList.add('active');
          expState.city = item.dataset.city;
          if (expCityLabel) expCityLabel.textContent = item.dataset.city === 'all' ? 'All Cities' : item.dataset.city;
          expCityMenu?.classList.remove('open');
          renderExpansionSpaces();
        });
      });

      expChipBtns.forEach(chip => {
        chip.addEventListener('click', () => {
          expChipBtns.forEach(c => c.classList.remove('active'));
          chip.classList.add('active');
          expState.subtype = chip.dataset.subtype;
          renderExpansionSpaces();
        });
      });

      expSearchInput?.addEventListener('input', (e) => {
        expState.query = e.target.value;
        renderExpansionSpaces();
      });

      const consultationModal = document.getElementById('consultationModal');
      document.querySelectorAll('.exp-feature-card, .open-consultation-btn').forEach(el => {
        el.addEventListener('click', () => {
          consultationModal?.classList.add('open');
          document.body.style.overflow = 'hidden';
        });
      });

      const consultationForm = document.getElementById('consultationForm');
      consultationForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(consultationForm);
        try {
          const resp = await fetch(consultationForm.action, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': window.__SPACEWORQ_CONFIG__.csrfToken,
            },
            body: formData,
          });
          const data = await resp.json();
          if (!resp.ok) {
            const firstErr = data?.errors ? Object.values(data.errors)[0][0] : (data.message || 'Please check your input.');
            showToast(firstErr, 'warning');
            return;
          }
          consultationModal?.classList.remove('open');
          document.body.style.overflow = '';
          showToast(data.message || 'Thank you! Our SpaceWorq Enterprise Team will call you shortly.');
          consultationForm.reset();
        } catch (err) {
          showToast('Unable to submit consultation right now.', 'warning');
        }
      });

      const partnerDropdownItem = document.getElementById('partnerDropdownItem');
      const partnerDropdownBtn = document.getElementById('partnerDropdownBtn');
      const partnerDropdownMenu = document.getElementById('partnerDropdownMenu');
      if (partnerDropdownBtn && partnerDropdownItem && partnerDropdownMenu) {
        partnerDropdownBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          const isOpen = partnerDropdownMenu.classList.contains('open');
          partnerDropdownMenu.classList.toggle('open', !isOpen);
          partnerDropdownItem.classList.toggle('open', !isOpen);
        });
      }
      document.addEventListener('click', () => {
        if (partnerDropdownMenu) partnerDropdownMenu.classList.remove('open');
        if (partnerDropdownItem) partnerDropdownItem.classList.remove('open');
      });

      const hamburgerBtn = document.getElementById('hamburgerBtn');
      const mobileDrawer = document.getElementById('mobileDrawer');
      const drawerBackdrop = document.getElementById('drawerBackdrop');
      const closeDrawerBtn = document.getElementById('closeDrawerBtn');
      if (hamburgerBtn && mobileDrawer && drawerBackdrop) {
        hamburgerBtn.addEventListener('click', () => {
          mobileDrawer.classList.add('open');
          drawerBackdrop.classList.add('open');
        });
      }
      closeDrawerBtn?.addEventListener('click', () => {
        mobileDrawer?.classList.remove('open');
        drawerBackdrop?.classList.remove('open');
      });
      drawerBackdrop?.addEventListener('click', () => {
        mobileDrawer?.classList.remove('open');
        drawerBackdrop?.classList.remove('open');
      });
      const drawerPartnerBtn = document.getElementById('drawerPartnerBtn');
      const drawerPartnerContent = document.getElementById('drawerPartnerContent');
      drawerPartnerBtn?.addEventListener('click', () => {
        drawerPartnerBtn.parentElement?.classList.toggle('open');
        drawerPartnerContent?.classList.toggle('open');
      });

      function openModal(el) {
        if (!el) return;
        el.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
      function closeAllModals() {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
      }
      document.querySelectorAll('.modal-close-btn').forEach(btn => btn.addEventListener('click', closeAllModals));
      document.querySelectorAll('.modal-overlay').forEach(ov => {
        ov.addEventListener('click', (e) => {
          if (e.target === ov) closeAllModals();
        });
      });

      const authModal = document.getElementById('authModal');
      document.querySelectorAll('.open-auth-btn').forEach(btn => btn.addEventListener('click', () => openModal(authModal)));
      const authFormStep1 = document.getElementById('authFormStep1');
      const authFormStep2 = document.getElementById('authFormStep2');
      const authContactInput = document.getElementById('authContactInput');
      const authTargetEmail = document.getElementById('authTargetEmail');
      const otpVerifyIdentifier = document.getElementById('otpVerifyIdentifier');
      const otpInput = document.getElementById('otpInput');
      const verifyOtpBtn = document.getElementById('verifyOtpBtn');
      const changeContactBtn = document.getElementById('changeContactBtn');

      authFormStep1?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const identifier = authContactInput?.value.trim() || '';
        if (!identifier) return;
        try {
          const resp = await fetch(window.__SPACEWORQ_CONFIG__.routes.otpSend, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': window.__SPACEWORQ_CONFIG__.csrfToken,
            },
            body: JSON.stringify({ identifier, purpose: 'login' }),
          });
          const data = await resp.json();
          if (!resp.ok) {
            showToast(data.message || 'Unable to send OTP.', 'warning');
            return;
          }
          if (authTargetEmail) authTargetEmail.textContent = identifier;
          if (otpVerifyIdentifier) otpVerifyIdentifier.value = identifier;
          authFormStep1.style.display = 'none';
          if (authFormStep2) authFormStep2.style.display = 'block';
          showToast(data.message || 'Verification code sent!');
        } catch (err) {
          showToast('Network error sending OTP.', 'warning');
        }
      });

      changeContactBtn?.addEventListener('click', () => {
        if (authFormStep2) authFormStep2.style.display = 'none';
        if (authFormStep1) authFormStep1.style.display = 'block';
      });

      verifyOtpBtn?.addEventListener('click', async () => {
        const otp = otpInput?.value.trim() || '';
        const identifier = otpVerifyIdentifier?.value.trim() || authContactInput?.value.trim() || '';
        if (otp.length !== 6) {
          showToast('Please enter the 6-digit OTP.', 'warning');
          return;
        }
        try {
          const resp = await fetch(window.__SPACEWORQ_CONFIG__.routes.otpVerify, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': window.__SPACEWORQ_CONFIG__.csrfToken,
            },
            body: JSON.stringify({ identifier, otp, purpose: 'login' }),
          });
          const data = await resp.json();
          if (!resp.ok) {
            showToast(data.message || 'Invalid OTP.', 'warning');
            return;
          }
          showToast(data.message || 'Signed in successfully!');
          closeAllModals();
          if (data.redirect) {
            window.location.href = data.redirect;
          } else {
            window.location.reload();
          }
        } catch (err) {
          showToast('Verification failed.', 'warning');
        }
      });

      const floatingBtn = document.getElementById('floatingInstantBookBtn');
      const authRequiredModal = document.getElementById('authRequiredModal');
      const btnAuthReqCancel = document.getElementById('btnAuthReqCancel');
      floatingBtn?.addEventListener('click', () => {
        if (window.__SPACEWORQ_CONFIG__.isAuthenticated) {
          window.location.href = window.__SPACEWORQ_CONFIG__.routes.home + '#workspacesCatalog';
        } else {
          openModal(authRequiredModal);
        }
      });
      btnAuthReqCancel?.addEventListener('click', () => authRequiredModal?.classList.remove('open'));

      renderExpansionSpaces();
    });
  </script>
@endpush