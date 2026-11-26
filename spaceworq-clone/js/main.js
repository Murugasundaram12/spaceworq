// SpaceWorq - Interactive Logic & UI Controller
document.addEventListener('DOMContentLoaded', () => {
  // Application State
  const state = {
    selectedCategory: 'LONG_TERM_LEASING',
    selectedCity: 'all',
    searchQuery: '',
    selectedAmenities: [],
    sortBy: 'recommended',
    savedSpaces: new Set(),
    comparedSpaces: new Set(),
    currentBookingSpace: null,
    bookingSeats: 1,
    bookingSlot: 'fullday',
    discountAmount: 0,
    appliedCoupon: null
  };

  // DOM Elements
  const header = document.querySelector('.site-header');
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const drawerBackdrop = document.getElementById('drawerBackdrop');
  const closeDrawerBtn = document.getElementById('closeDrawerBtn');

  // Hero Controls
  const quickNavChips = document.querySelectorAll('.quick-nav-chip');
  const citySelectorBtn = document.getElementById('heroCitySelector');
  const cityDropdown = document.getElementById('cityDropdown');
  const citySelectorLabel = document.getElementById('selectedCityLabel');
  const searchInput = document.getElementById('heroSearchInput');
  const clearSearchBtn = document.getElementById('clearSearchBtn');
  const searchSuggestions = document.getElementById('searchSuggestions');
  const heroSearchBtn = document.getElementById('heroSearchBtn');

  // Featured Cities Grid
  const citiesGrid = document.getElementById('citiesGrid');
  const cityNavPrev = document.getElementById('cityNavPrev');
  const cityNavNext = document.getElementById('cityNavNext');

  // Categories Showcase
  const categoryCardsGrid = document.getElementById('categoryCardsGrid');

  // Workspace Catalog
  const tabFilterBtns = document.querySelectorAll('.tab-filter-btn');
  const amenityToggleBtn = document.getElementById('amenityToggleBtn');
  const amenitiesPanel = document.getElementById('amenitiesPanel');
  const sortSelect = document.getElementById('sortSelect');
  const workspacesGrid = document.getElementById('workspacesGrid');
  const resultsCount = document.getElementById('resultsCount');
  const clearFiltersBtn = document.getElementById('clearFiltersBtn');
  const vaultBadges = document.querySelectorAll('.vault-count');

  // Comparison
  const comparisonBar = document.getElementById('comparisonFloatingBar');
  const compareCountDisplay = document.getElementById('compareCountDisplay');
  const openCompareModalBtn = document.getElementById('openCompareModalBtn');
  const clearCompareBtn = document.getElementById('clearCompareBtn');
  const comparisonModal = document.getElementById('comparisonModal');
  const comparisonTableHead = document.getElementById('comparisonTableHead');
  const comparisonTableBody = document.getElementById('comparisonTableBody');

  // Booking Modal
  const bookingModal = document.getElementById('bookingModal');
  const bookingSpaceName = document.getElementById('bookingSpaceName');
  const bookingSpaceLocation = document.getElementById('bookingSpaceLocation');
  const seatDecrementBtn = document.getElementById('seatDecrement');
  const seatIncrementBtn = document.getElementById('seatIncrement');
  const seatCountDisplay = document.getElementById('seatCount');
  const slotOptions = document.querySelectorAll('.slot-option');
  const bookingDateInput = document.getElementById('bookingDate');
  const couponInput = document.getElementById('couponInput');
  const applyCouponBtn = document.getElementById('applyCouponBtn');
  const subtotalDisplay = document.getElementById('summarySubtotal');
  const gstDisplay = document.getElementById('summaryGst');
  const discountRow = document.getElementById('discountRow');
  const discountDisplay = document.getElementById('summaryDiscount');
  const totalDisplay = document.getElementById('summaryTotal');
  const bookingForm = document.getElementById('bookingForm');

  // Enterprise Consultation Modal
  const consultationModal = document.getElementById('consultationModal');
  const openConsultationBtns = document.querySelectorAll('.open-consultation-btn');
  const consultationForm = document.getElementById('consultationForm');

  // Sign In Modal
  const authModal = document.getElementById('authModal');
  const openAuthBtns = document.querySelectorAll('.open-auth-btn');
  const authFormStep1 = document.getElementById('authFormStep1');
  const authFormStep2 = document.getElementById('authFormStep2');
  const otpInput = document.getElementById('otpInput');
  const verifyOtpBtn = document.getElementById('verifyOtpBtn');
  const authTargetEmail = document.getElementById('authTargetEmail');

  // Toast Container
  const toastContainer = document.getElementById('toastContainer');

  /* ==========================================================================
     Helper Utilities
     ========================================================================== */
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <span>${message}</span>
    `;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(50px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function formatCurrency(amount) {
    return '₹' + Number(amount).toLocaleString('en-IN');
  }

  /* ==========================================================================
     Navigation & Header Scroll
     ========================================================================== */
  window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // Mobile Drawer Toggle
  function toggleDrawer(open) {
    if (open) {
      hamburgerBtn.classList.add('active');
      mobileDrawer.classList.add('open');
      drawerBackdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
    } else {
      hamburgerBtn.classList.remove('active');
      mobileDrawer.classList.remove('open');
      drawerBackdrop.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', () => {
      const isOpen = mobileDrawer.classList.contains('open');
      toggleDrawer(!isOpen);
    });
  }

  if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', () => toggleDrawer(false));
  if (drawerBackdrop) drawerBackdrop.addEventListener('click', () => toggleDrawer(false));

  /* ==========================================================================
     Partner With Us Dropdown Toggle (Matching Screenshot)
     ========================================================================== */
  const partnerDropdownItem = document.getElementById('partnerDropdownItem');
  const partnerDropdownBtn = document.getElementById('partnerDropdownBtn');
  const partnerDropdownMenu = document.getElementById('partnerDropdownMenu');

  if (partnerDropdownBtn && partnerDropdownItem) {
    partnerDropdownBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = partnerDropdownMenu.classList.contains('open');
      partnerDropdownMenu.classList.toggle('open', !isOpen);
      partnerDropdownItem.classList.toggle('open', !isOpen);
      partnerDropdownBtn.setAttribute('aria-expanded', !isOpen);
    });
  }

  // Mobile Drawer Partner Accordion Toggle
  const drawerPartnerBtn = document.getElementById('drawerPartnerBtn');
  if (drawerPartnerBtn) {
    drawerPartnerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const parentLi = drawerPartnerBtn.closest('.drawer-dropdown-section');
      if (parentLi) {
        parentLi.classList.toggle('open');
      }
    });
  }

  // Close dropdown on outside click
  document.addEventListener('click', () => {
    if (partnerDropdownMenu) partnerDropdownMenu.classList.remove('open');
    if (partnerDropdownItem) partnerDropdownItem.classList.remove('open');
    if (cityDropdown) cityDropdown.classList.remove('open');
    if (searchSuggestions) searchSuggestions.classList.remove('open');
  });

  // Partner Menu Links Action
  document.querySelectorAll('.partner-menu-link, .drawer-sublink').forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      if (href && !href.startsWith('#') && href !== 'javascript:void(0)') {
        // Allow natural navigation to the target page (e.g. workspace-providers.html)
        if (partnerDropdownMenu) partnerDropdownMenu.classList.remove('open');
        if (partnerDropdownItem) partnerDropdownItem.classList.remove('open');
        toggleDrawer(false);
        return;
      }

      e.preventDefault();
      if (partnerDropdownMenu) partnerDropdownMenu.classList.remove('open');
      if (partnerDropdownItem) partnerDropdownItem.classList.remove('open');
      toggleDrawer(false);
      
      const title = link.querySelector('.partner-item-title, strong')?.textContent || 'Partner Program';
      showToast(`Selected "${title}". Opening Partner Consultation...`);
      openModal(consultationModal);
    });
  });

  /* ==========================================================================
     City Selector Dropdown Toggle
     ========================================================================== */
  if (citySelectorBtn) {
    citySelectorBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      cityDropdown.classList.toggle('open');
    });
  }

  document.querySelectorAll('.city-dropdown-item').forEach(item => {
    item.addEventListener('click', (e) => {
      const city = e.currentTarget.dataset.city;
      const cityName = e.currentTarget.dataset.name;
      state.selectedCity = city;
      citySelectorLabel.textContent = cityName;
      
      document.querySelectorAll('.city-dropdown-item').forEach(i => i.classList.remove('selected'));
      e.currentTarget.classList.add('selected');
      
      cityDropdown.classList.remove('open');
      renderFeaturedCities();
      filterAndRenderWorkspaces();
      showToast(`Filtered by ${cityName}`);
    });
  });

  /* ==========================================================================
     Quick Nav Category Chips
     ========================================================================== */
  quickNavChips.forEach(chip => {
    chip.addEventListener('click', () => {
      quickNavChips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const cat = chip.dataset.category;
      state.selectedCategory = cat;
      
      // Sync with catalog category tabs
      tabFilterBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === cat);
      });

      filterAndRenderWorkspaces();
    });
  });

  /* ==========================================================================
     Search Input & Suggestions
     ========================================================================== */
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim();
      state.searchQuery = q;
      
      if (clearSearchBtn) {
        clearSearchBtn.classList.toggle('visible', q.length > 0);
      }

      if (q.length >= 2) {
        buildSearchSuggestions(q);
      } else {
        searchSuggestions.classList.remove('open');
      }

      filterAndRenderWorkspaces();
    });

    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        state.searchQuery = '';
        clearSearchBtn.classList.remove('visible');
        searchSuggestions.classList.remove('open');
        filterAndRenderWorkspaces();
      });
    }
  }

  function buildSearchSuggestions(query) {
    const qLower = query.toLowerCase();
    const matches = SpaceWorqData.workspaces.filter(s => 
      s.name.toLowerCase().includes(qLower) || 
      s.city.toLowerCase().includes(qLower) || 
      s.locality.toLowerCase().includes(qLower)
    ).slice(0, 5);

    if (matches.length === 0) {
      searchSuggestions.classList.remove('open');
      return;
    }

    const listContainer = searchSuggestions.querySelector('.suggestions-list');
    listContainer.innerHTML = matches.map(s => `
      <div class="suggestion-row" data-name="${s.name}" data-city="${s.city}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
          <circle cx="12" cy="10" r="3"></circle>
        </svg>
        <div>
          <strong>${s.name}</strong>
          <span style="font-size: 0.8rem; color: #94A3B8; margin-left: 8px;">${s.locality}, ${s.city}</span>
        </div>
      </div>
    `).join('');

    searchSuggestions.classList.add('open');

    listContainer.querySelectorAll('.suggestion-row').forEach(row => {
      row.addEventListener('click', (e) => {
        e.stopPropagation();
        searchInput.value = row.dataset.name;
        state.searchQuery = row.dataset.name;
        searchSuggestions.classList.remove('open');
        clearSearchBtn.classList.add('visible');
        filterAndRenderWorkspaces();
      });
    });
  }

  if (heroSearchBtn) {
    heroSearchBtn.addEventListener('click', () => {
      const catalogEl = document.getElementById('workspacesCatalog');
      if (catalogEl) {
        catalogEl.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }

  /* ==========================================================================
     Featured Cities Carousel
     ========================================================================== */
  function renderFeaturedCities() {
    if (!citiesGrid) return;
    citiesGrid.innerHTML = SpaceWorqData.cities.map(c => {
      const isActive = state.selectedCity.toLowerCase() === c.name.toLowerCase();
      return `
        <div class="city-card ${isActive ? 'active' : ''}" data-city="${c.id}" data-name="${c.name}">
          <img src="${c.image}" alt="${c.name} Workspace" loading="lazy" />
          <div class="city-overlay">
            <div class="city-name">${c.name}</div>
            <div class="city-spaces">${c.spaces}</div>
          </div>
        </div>
      `;
    }).join('');

    citiesGrid.querySelectorAll('.city-card').forEach(card => {
      card.addEventListener('click', () => {
        const cityName = card.dataset.name;
        if (state.selectedCity.toLowerCase() === cityName.toLowerCase()) {
          state.selectedCity = 'all';
          citySelectorLabel.textContent = 'All Cities';
        } else {
          state.selectedCity = cityName;
          citySelectorLabel.textContent = cityName;
        }
        renderFeaturedCities();
        filterAndRenderWorkspaces();
        
        const catalogEl = document.getElementById('workspacesCatalog');
        if (catalogEl) {
          catalogEl.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  }

  if (cityNavPrev && cityNavNext && citiesGrid) {
    cityNavPrev.addEventListener('click', () => {
      citiesGrid.scrollBy({ left: -240, behavior: 'smooth' });
    });
    cityNavNext.addEventListener('click', () => {
      citiesGrid.scrollBy({ left: 240, behavior: 'smooth' });
    });
  }

  /* ==========================================================================
     Category Showcase Grid
     ========================================================================== */
  function renderCategories() {
    if (!categoryCardsGrid) return;
    categoryCardsGrid.innerHTML = SpaceWorqData.categories.map(cat => `
      <div class="category-card" data-category="${cat.id}">
        <div class="category-img-wrap">
          <img src="${cat.image}" alt="${cat.title}" loading="lazy" />
          <div class="category-badge-chip">${cat.badge}</div>
        </div>
        <div class="category-body">
          <h3 class="category-card-title">${cat.title}</h3>
          <div class="category-card-subtitle">${cat.subtitle}</div>
          <ul class="category-bullets">
            ${cat.bullets.map(b => `<li>${b}</li>`).join('')}
          </ul>
          <button class="category-explore-btn" data-category="${cat.id}">
            <span>${cat.cta}</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </div>
      </div>
    `).join('');

    categoryCardsGrid.querySelectorAll('.category-explore-btn, .category-card').forEach(elem => {
      elem.addEventListener('click', (e) => {
        const cat = elem.dataset.category;
        state.selectedCategory = cat;
        
        // Update quick chips
        quickNavChips.forEach(c => c.classList.toggle('active', c.dataset.category === cat));
        // Update catalog tabs
        tabFilterBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.category === cat));
        
        filterAndRenderWorkspaces();
        const catalogEl = document.getElementById('workspacesCatalog');
        if (catalogEl) catalogEl.scrollIntoView({ behavior: 'smooth' });
      });
    });
  }

  /* ==========================================================================
     Amenities Filter Accordion & Multi-Select
     ========================================================================== */
  function setupAmenitiesPanel() {
    if (!amenitiesPanel) return;
    amenitiesPanel.innerHTML = SpaceWorqData.amenitiesList.map(amenity => `
      <button class="amenity-pill" data-amenity="${amenity}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>${amenity}</span>
      </button>
    `).join('');

    amenitiesPanel.querySelectorAll('.amenity-pill').forEach(pill => {
      pill.addEventListener('click', () => {
        const am = pill.dataset.amenity;
        const idx = state.selectedAmenities.indexOf(am);
        if (idx >= 0) {
          state.selectedAmenities.splice(idx, 1);
          pill.classList.remove('selected');
        } else {
          state.selectedAmenities.push(am);
          pill.classList.add('selected');
        }
        updateAmenityToggleLabel();
        filterAndRenderWorkspaces();
      });
    });
  }

  if (amenityToggleBtn) {
    amenityToggleBtn.addEventListener('click', () => {
      const isOpen = amenitiesPanel.classList.contains('open');
      amenitiesPanel.classList.toggle('open', !isOpen);
      amenityToggleBtn.classList.toggle('active', !isOpen);
    });
  }

  function updateAmenityToggleLabel() {
    const count = state.selectedAmenities.length;
    const countSpan = document.getElementById('amenityCountDisplay');
    if (countSpan) {
      countSpan.textContent = count > 0 ? `(${count})` : '';
    }
  }

  /* ==========================================================================
     Catalog Category Tabs & Sorting
     ========================================================================== */
  tabFilterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabFilterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.category;
      state.selectedCategory = cat;

      quickNavChips.forEach(c => c.classList.toggle('active', c.dataset.category === cat));
      filterAndRenderWorkspaces();
    });
  });

  if (sortSelect) {
    sortSelect.addEventListener('change', (e) => {
      state.sortBy = e.target.value;
      filterAndRenderWorkspaces();
    });
  }

  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', () => {
      state.selectedCategory = 'all';
      state.selectedCity = 'all';
      state.searchQuery = '';
      state.selectedAmenities = [];
      state.sortBy = 'recommended';

      if (searchInput) searchInput.value = '';
      if (clearSearchBtn) clearSearchBtn.classList.remove('visible');
      if (citySelectorLabel) citySelectorLabel.textContent = 'All Cities';

      quickNavChips.forEach(c => c.classList.remove('active'));
      tabFilterBtns.forEach(b => b.classList.toggle('active', b.dataset.category === 'all'));
      document.querySelectorAll('.amenity-pill').forEach(p => p.classList.remove('selected'));
      updateAmenityToggleLabel();
      renderFeaturedCities();
      filterAndRenderWorkspaces();
      showToast('Filters cleared');
    });
  }

  /* ==========================================================================
     Workspaces Filter, Sort & Render Engine
     ========================================================================== */
  function filterAndRenderWorkspaces() {
    if (!workspacesGrid) return;

    let list = [...SpaceWorqData.workspaces];

    // Filter by category
    if (state.selectedCategory && state.selectedCategory !== 'all') {
      list = list.filter(item => item.category === state.selectedCategory);
    }

    // Filter by city
    if (state.selectedCity && state.selectedCity !== 'all') {
      list = list.filter(item => item.city.toLowerCase() === state.selectedCity.toLowerCase());
    }

    // Filter by query
    if (state.searchQuery) {
      const q = state.searchQuery.toLowerCase();
      list = list.filter(item => 
        item.name.toLowerCase().includes(q) ||
        item.city.toLowerCase().includes(q) ||
        item.locality.toLowerCase().includes(q) ||
        item.nearBy.toLowerCase().includes(q)
      );
    }

    // Filter by amenities (must match all selected)
    if (state.selectedAmenities.length > 0) {
      list = list.filter(item => 
        state.selectedAmenities.every(am => item.amenities.includes(am))
      );
    }

    // Sort
    if (state.sortBy === 'priceLowHigh') {
      list.sort((a, b) => (a.priceMonth || a.priceDay) - (b.priceMonth || b.priceDay));
    } else if (state.sortBy === 'priceHighLow') {
      list.sort((a, b) => (b.priceMonth || b.priceDay) - (a.priceMonth || a.priceDay));
    } else if (state.sortBy === 'rating') {
      list.sort((a, b) => b.rating - a.rating || b.reviews - a.reviews);
    }

    // Update Counter
    if (resultsCount) {
      resultsCount.textContent = `${list.length} Spaces Found`;
    }

    // Check if clear filters button should show
    const isFiltered = state.selectedCategory !== 'all' || 
                       state.selectedCity !== 'all' || 
                       state.searchQuery !== '' || 
                       state.selectedAmenities.length > 0;
    if (clearFiltersBtn) {
      clearFiltersBtn.style.display = isFiltered ? 'inline-block' : 'none';
    }

    // Render Cards
    if (list.length === 0) {
      workspacesGrid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 48px 20px; background: #FFF; border-radius: 16px; border: 1px solid var(--border-color);">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" style="margin-bottom: 12px;">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-heading); margin-bottom: 8px;">No spaces match your filters</h3>
          <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">Try adjusting your search criteria or clearing active filters to see available spaces.</p>
          <button class="btn-primary" id="emptyStateResetBtn">Reset All Filters</button>
        </div>
      `;
      const resetBtn = document.getElementById('emptyStateResetBtn');
      if (resetBtn) resetBtn.addEventListener('click', () => clearFiltersBtn.click());
      return;
    }

    workspacesGrid.innerHTML = list.map(space => {
      const isFav = state.savedSpaces.has(space.id);
      const isCompared = state.comparedSpaces.has(space.id);
      const img = space.images[0] || 'assets/images/space1.png';
      
      // Determine pricing string based on category
      let priceLabel = '';
      let priceSub = '';
      if (space.category === 'DAY_PASS') {
        priceLabel = `${formatCurrency(space.priceDay)}`;
        priceSub = 'per person / day';
      } else if (space.category === 'MEETINGS_EVENTS') {
        priceLabel = `${formatCurrency(space.priceMeetingHour)}`;
        priceSub = 'per hour';
      } else if (space.category === 'VIRTUAL_OFFICE') {
        priceLabel = `${formatCurrency(space.priceMonth)}`;
        priceSub = 'per month (Annual plan)';
      } else {
        priceLabel = `${formatCurrency(space.priceMonth)}`;
        priceSub = 'per desk / month';
      }

      return `
        <div class="workspace-card" data-id="${space.id}">
          <div class="workspace-image-container">
            <img src="${img}" alt="${space.name}" loading="lazy" />
            <div class="card-top-badges">
              <span class="badge-verified">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Verified
              </span>
              <span class="badge-availability ${space.availability}">
                ${space.availability === 'available' ? 'Available' : 'Filling Fast'}
              </span>
            </div>
            <button class="favorite-btn ${isFav ? 'active' : ''}" data-id="${space.id}" title="${isFav ? 'Remove from Vault' : 'Save to Vault'}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="${isFav ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </button>
          </div>

          <div class="workspace-card-body">
            <div class="card-type-row">
              <span class="card-type-label">${space.typeLabel}</span>
              <div class="card-rating-wrap">
                <svg viewBox="0 0 24 24">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>${space.rating}</span>
                <span style="color: var(--text-muted); font-size: 0.74rem;">(${space.reviews})</span>
              </div>
            </div>

            <h3 class="card-title">${space.name}</h3>
            
            <div class="card-location">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span>${space.locality} • ${space.city}</span>
            </div>

            <div class="card-amenities-row">
              ${space.amenities.slice(0, 4).map(am => `<span class="card-amenity-tag">${am}</span>`).join('')}
              ${space.amenities.length > 4 ? `<span class="card-amenity-tag">+${space.amenities.length - 4} more</span>` : ''}
            </div>

            <div class="card-footer-row">
              <div class="card-price-wrap">
                <span class="price-val">${priceLabel}</span>
                <span class="price-sub">${priceSub}</span>
              </div>

              <div class="card-actions-wrap">
                <label class="compare-check-label">
                  <input type="checkbox" class="compare-checkbox" data-id="${space.id}" ${isCompared ? 'checked' : ''} />
                  <span>Compare</span>
                </label>
                <button class="btn-book-card" data-id="${space.id}">Book</button>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');

    attachCardEventListeners();
  }

  function attachCardEventListeners() {
    // Favorite Heart buttons
    document.querySelectorAll('.favorite-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = btn.dataset.id;
        const space = SpaceWorqData.workspaces.find(s => s.id === id);
        if (state.savedSpaces.has(id)) {
          state.savedSpaces.delete(id);
          btn.classList.remove('active');
          showToast(`Removed "${space.name}" from Vault`);
        } else {
          state.savedSpaces.add(id);
          btn.classList.add('active');
          showToast(`Saved "${space.name}" to Vault ❤️`);
        }
        updateVaultBadges();
      });
    });

    // Compare checkboxes
    document.querySelectorAll('.compare-checkbox').forEach(cb => {
      cb.addEventListener('change', (e) => {
        const id = cb.dataset.id;
        if (cb.checked) {
          if (state.comparedSpaces.size >= 3) {
            cb.checked = false;
            showToast('You can compare a maximum of 3 spaces at once.', 'warning');
            return;
          }
          state.comparedSpaces.add(id);
        } else {
          state.comparedSpaces.delete(id);
        }
        updateComparisonBar();
      });
    });

    // Book buttons
    document.querySelectorAll('.btn-book-card').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = btn.dataset.id;
        openBookingModal(id);
      });
    });
  }

  function updateVaultBadges() {
    const count = state.savedSpaces.size;
    vaultBadges.forEach(badge => {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
  }

  /* ==========================================================================
     Space Comparison Engine & Modal
     ========================================================================== */
  function updateComparisonBar() {
    const count = state.comparedSpaces.size;
    if (count > 0) {
      compareCountDisplay.textContent = count;
      comparisonBar.classList.add('visible');
    } else {
      comparisonBar.classList.remove('visible');
    }
  }

  if (clearCompareBtn) {
    clearCompareBtn.addEventListener('click', () => {
      state.comparedSpaces.clear();
      document.querySelectorAll('.compare-checkbox').forEach(cb => cb.checked = false);
      updateComparisonBar();
    });
  }

  if (openCompareModalBtn) {
    openCompareModalBtn.addEventListener('click', () => {
      buildComparisonTable();
      openModal(comparisonModal);
    });
  }

  function buildComparisonTable() {
    const spaces = Array.from(state.comparedSpaces).map(id => 
      SpaceWorqData.workspaces.find(s => s.id === id)
    ).filter(Boolean);

    if (spaces.length === 0) return;

    // Header
    comparisonTableHead.innerHTML = `
      <tr>
        <th style="width: 180px;">Space Attributes</th>
        ${spaces.map(s => `
          <th>
            <div style="font-weight: 700; font-size: 1rem; color: var(--text-heading);">${s.name}</div>
            <div style="font-size: 0.8rem; color: var(--primary); font-weight: 600;">${s.typeLabel}</div>
          </th>
        `).join('')}
      </tr>
    `;

    // Body rows
    const rows = [
      {
        label: 'City & Location',
        getter: s => `${s.locality}, ${s.city} (${s.nearBy})`
      },
      {
        label: 'Monthly Rate',
        getter: s => s.priceMonth ? `${formatCurrency(s.priceMonth)} / mo` : 'N/A'
      },
      {
        label: 'Day Pass Rate',
        getter: s => s.priceDay ? `${formatCurrency(s.priceDay)} / day` : 'N/A'
      },
      {
        label: 'Meeting Room Rate',
        getter: s => s.priceMeetingHour ? `${formatCurrency(s.priceMeetingHour)} / hr` : 'N/A'
      },
      {
        label: 'Capacity',
        getter: s => s.capacity
      },
      {
        label: 'Rating & Reviews',
        getter: s => `★ ${s.rating} (${s.reviews} verified reviews)`
      },
      {
        label: 'Availability',
        getter: s => s.availability === 'available' ? '<span style="color:#3E9D8F;font-weight:700;">● Available Now</span>' : '<span style="color:#D89450;font-weight:700;">● Filling Fast</span>'
      },
      {
        label: 'Amenities Included',
        getter: s => s.amenities.map(a => `<span class="card-amenity-tag" style="margin:2px;display:inline-block;">${a}</span>`).join('')
      },
      {
        label: 'Action',
        getter: s => `<button class="btn-primary" style="padding: 6px 16px; font-size: 0.82rem;" onclick="window.triggerBookFromModal('${s.id}')">Instant Book</button>`
      }
    ];

    comparisonTableBody.innerHTML = rows.map(r => `
      <tr>
        <td class="feature-title">${r.label}</td>
        ${spaces.map(s => `<td>${r.getter(s)}</td>`).join('')}
      </tr>
    `).join('');
  }

  window.triggerBookFromModal = function(spaceId) {
    closeModal(comparisonModal);
    openBookingModal(spaceId);
  };

  /* ==========================================================================
     Instant Booking Modal & Calculator
     ========================================================================== */
  function openBookingModal(spaceId) {
    const space = SpaceWorqData.workspaces.find(s => s.id === spaceId);
    if (!space) return;

    state.currentBookingSpace = space;
    state.bookingSeats = 1;
    state.bookingSlot = 'fullday';
    state.discountAmount = 0;
    state.appliedCoupon = null;

    if (seatCountDisplay) seatCountDisplay.textContent = '1';
    if (bookingSpaceName) bookingSpaceName.textContent = space.name;
    if (bookingSpaceLocation) bookingSpaceLocation.textContent = `${space.locality}, ${space.city}`;
    if (couponInput) couponInput.value = '';
    if (discountRow) discountRow.style.display = 'none';

    // Set default date to today
    if (bookingDateInput) {
      const today = new Date().toISOString().split('T')[0];
      bookingDateInput.value = today;
      bookingDateInput.min = today;
    }

    // Reset slots
    slotOptions.forEach(opt => {
      opt.classList.toggle('selected', opt.dataset.slot === 'fullday');
    });

    calculateBookingTotal();
    openModal(bookingModal);
  }

  // Seat Counter
  if (seatDecrementBtn) {
    seatDecrementBtn.addEventListener('click', () => {
      if (state.bookingSeats > 1) {
        state.bookingSeats--;
        seatCountDisplay.textContent = state.bookingSeats;
        calculateBookingTotal();
      }
    });
  }

  if (seatIncrementBtn) {
    seatIncrementBtn.addEventListener('click', () => {
      if (state.bookingSeats < 25) {
        state.bookingSeats++;
        seatCountDisplay.textContent = state.bookingSeats;
        calculateBookingTotal();
      } else {
        showToast('For bookings above 25 seats, please request Enterprise consultation.', 'warning');
      }
    });
  }

  // Slot Option toggles
  slotOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      slotOptions.forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      state.bookingSlot = opt.dataset.slot;
      calculateBookingTotal();
    });
  });

  // Coupon Logic
  if (applyCouponBtn && couponInput) {
    applyCouponBtn.addEventListener('click', () => {
      const code = couponInput.value.trim().toUpperCase();
      if (!code) return;

      if (code === 'WKND20') {
        state.appliedCoupon = 'WKND20';
        state.discountPercent = 0.20;
        showToast('Promo code WKND20 applied! (20% OFF)');
      } else if (code === 'NEW100') {
        state.appliedCoupon = 'NEW100';
        state.discountFlat = 100;
        showToast('Promo code NEW100 applied! (₹100 OFF)');
      } else if (code === 'WORQ500') {
        state.appliedCoupon = 'WORQ500';
        state.discountFlat = 500;
        showToast('Promo code WORQ500 applied! (₹500 OFF)');
      } else {
        showToast('Invalid coupon code. Try: WKND20, NEW100, or WORQ500', 'warning');
        state.appliedCoupon = null;
      }
      calculateBookingTotal();
    });
  }

  function calculateBookingTotal() {
    if (!state.currentBookingSpace) return;
    const space = state.currentBookingSpace;
    
    // Base unit price
    let baseRate = space.priceDay || 499;
    if (state.bookingSlot === 'morning' || state.bookingSlot === 'afternoon') {
      baseRate = Math.round(baseRate * 0.65);
    }

    const subtotal = baseRate * state.bookingSeats;
    let discount = 0;

    if (state.appliedCoupon === 'WKND20') {
      discount = Math.round(subtotal * 0.20);
    } else if (state.appliedCoupon === 'NEW100') {
      discount = Math.min(subtotal, 100);
    } else if (state.appliedCoupon === 'WORQ500') {
      discount = Math.min(subtotal, 500);
    }

    const taxableAmount = Math.max(0, subtotal - discount);
    const gst = Math.round(taxableAmount * 0.18);
    const total = taxableAmount + gst;

    if (subtotalDisplay) subtotalDisplay.textContent = formatCurrency(subtotal);
    if (gstDisplay) gstDisplay.textContent = formatCurrency(gst);
    
    if (discountRow && discountDisplay) {
      if (discount > 0) {
        discountRow.style.display = 'flex';
        discountDisplay.textContent = `-${formatCurrency(discount)}`;
      } else {
        discountRow.style.display = 'none';
      }
    }

    if (totalDisplay) totalDisplay.textContent = formatCurrency(total);
  }

  // Booking Form Submission
  if (bookingForm) {
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = document.getElementById('bookerName').value.trim();
      const email = document.getElementById('bookerEmail').value.trim();
      const phone = document.getElementById('bookerPhone').value.trim();
      const date = bookingDateInput.value;

      if (!name || !email || !phone) {
        showToast('Please fill in all contact details.', 'warning');
        return;
      }

      closeModal(bookingModal);
      showToast(`🎉 Booking confirmed for ${name}! Details sent to ${email}.`, 'success');
      bookingForm.reset();
    });
  }

  /* ==========================================================================
     Enterprise Consultation Modal
     ========================================================================== */
  openConsultationBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      openModal(consultationModal);
    });
  });

  if (consultationForm) {
    consultationForm.addEventListener('submit', (e) => {
      e.preventDefault();
      closeModal(consultationModal);
      showToast('Enterprise inquiry received! Our dedicated manager will contact you within 15 minutes.');
      consultationForm.reset();
    });
  }

  /* ==========================================================================
     Auth / Sign In Modal with OTP Demo
     ========================================================================== */
  openAuthBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (authFormStep1) authFormStep1.style.display = 'block';
      if (authFormStep2) authFormStep2.style.display = 'none';
      openModal(authModal);
    });
  });

  if (authFormStep1) {
    authFormStep1.addEventListener('submit', (e) => {
      e.preventDefault();
      const contactVal = document.getElementById('authContactInput').value.trim();
      if (!contactVal) return;
      
      authTargetEmail.textContent = contactVal;
      authFormStep1.style.display = 'none';
      authFormStep2.style.display = 'block';
      showToast(`Verification code sent to ${contactVal} (Demo code: 123456)`);
      if (otpInput) otpInput.focus();
    });
  }

  if (verifyOtpBtn) {
    verifyOtpBtn.addEventListener('click', () => {
      const code = otpInput.value.trim();
      if (code.length < 4) {
        showToast('Please enter the 6-digit OTP', 'warning');
        return;
      }
      closeModal(authModal);
      showToast('Logged in successfully! Welcome to SpaceWorq.');
      
      // Update Sign In buttons
      openAuthBtns.forEach(b => {
        b.innerHTML = `
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <span>My Profile</span>
        `;
      });
    });
  }

  /* ==========================================================================
     Floating Instant Booking & Login Required Modal Handlers
     ========================================================================== */
  const floatingInstantBookBtn = document.getElementById('floatingInstantBookBtn');
  const authRequiredModal = document.getElementById('authRequiredModal');
  const btnAuthReqCancel = document.getElementById('btnAuthReqCancel');

  if (floatingInstantBookBtn) {
    floatingInstantBookBtn.addEventListener('click', () => {
      // Check if user is logged in
      const savedUser = localStorage.getItem('spaceworq_user') || sessionStorage.getItem('spaceworq_logged_in');
      let isLoggedIn = false;
      if (savedUser) {
        try {
          const parsed = typeof savedUser === 'string' && savedUser.startsWith('{') ? JSON.parse(savedUser) : null;
          isLoggedIn = parsed ? !!parsed.isLoggedIn : (savedUser === 'true');
        } catch (e) {
          isLoggedIn = false;
        }
      }

      if (isLoggedIn) {
        // Logged in: open instant booking modal directly
        const defaultSpaceId = state.currentBookingSpace ? state.currentBookingSpace.id : (SpaceWorqData.workspaces[0] ? SpaceWorqData.workspaces[0].id : null);
        if (defaultSpaceId) {
          openBookingModal(defaultSpaceId);
        } else {
          showToast('Please select a workspace to instant book.', 'info');
          const catalog = document.getElementById('workspacesSection');
          if (catalog) catalog.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        // Not logged in: show Login Required modal matching official SpaceWorq screenshot
        openModal(authRequiredModal);
      }
    });
  }

  if (btnAuthReqCancel) {
    btnAuthReqCancel.addEventListener('click', () => {
      closeModal(authRequiredModal);
    });
  }

  /* ==========================================================================
     Generic Modal Open / Close Helpers
     ========================================================================== */
  function openModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modalEl) {
    if (!modalEl) return;
    modalEl.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(modal);
    });
    const closeBtn = modal.querySelector('.modal-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', () => closeModal(modal));
  });

  // ESC key closes modals
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m));
      toggleDrawer(false);
    }
  });

  /* ==========================================================================
     Initialize UI
     ========================================================================== */
  renderFeaturedCities();
  renderCategories();
  setupAmenitiesPanel();
  filterAndRenderWorkspaces();
});
