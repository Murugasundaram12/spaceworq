@extends('layouts.website')

@section('title', 'SpaceWorq - Partner With Us | For Building Owners')
@section('active_nav', 'partner')
@section('active_partner', 'building-owners')
@section('body_class', 'owner-page-body')
@section('header_cta')
  <button class="btn-get-started open-submit-modal-btn" id="btnHeaderGetStarted" type="button">Submit Space</button>
@endsection
@section('drawer_cta')
  <button class="btn-get-started open-submit-modal-btn" style="width: 100%; justify-content: center;" type="button"><span>Submit Building</span></button>
@endsection

@push('styles')
<style>

    /* Specific styles for Building Owners Page (Exact SpaceWorq Production Design) */
    body.owner-page-body {
      background-color: #EDF6F9;
    }

    .owner-container {
      padding: 32px 0 60px 0;
    }

    /* Main Hero Action Card (Matches bundle heroCard) */
    .owner-hero-card {
      border-radius: 20px;
      padding: 36px 40px;
      border: 1px solid rgba(0, 108, 118, 0.22);
      background: rgba(0, 108, 118, 0.06);
      margin-bottom: 36px;
    }

    .owner-hero-kicker {
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1.8px;
      text-transform: uppercase;
      color: var(--primary);
      margin-bottom: 8px;
    }

    .owner-hero-title {
      font-size: 2.15rem;
      font-weight: 800;
      color: var(--text-heading);
      letter-spacing: -0.5px;
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .owner-hero-copy {
      font-size: 1.05rem;
      line-height: 1.6;
      color: var(--text-muted);
      max-width: 760px;
      margin-bottom: 24px;
    }

    .owner-cta-row {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .btn-submit-building {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 28px;
      font-size: 0.95rem;
      font-weight: 700;
      color: #FFFFFF;
      background-color: var(--primary);
      border-radius: var(--radius-full);
      box-shadow: 0 4px 14px rgba(0, 108, 118, 0.3);
      cursor: pointer;
      transition: var(--transition);
      border: none;
    }

    .btn-submit-building:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 108, 118, 0.4);
    }

    .btn-explore-expansion {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 26px;
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-heading);
      background-color: #FFFFFF;
      border: 1.5px solid var(--border-color);
      border-radius: var(--radius-full);
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
    }

    .btn-explore-expansion:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* Section Title */
    .owner-section-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-heading);
      margin-bottom: 20px;
    }

    /* Choose a Partnership Model (3 Cards Grid) */
    .owner-models-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }

    .owner-model-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 26px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      display: flex;
      flex-direction: column;
    }

    .owner-model-card:hover {
      transform: translateY(-3px);
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }

    .model-icon-box {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: rgba(0, 108, 118, 0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      margin-bottom: 16px;
    }

    .model-icon-box svg {
      width: 22px;
      height: 22px;
    }

    .owner-model-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-heading);
      margin-bottom: 8px;
    }

    .owner-model-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.55;
    }

    /* What We Need From You Section (Matches Bundle requirementsSection) */
    .requirements-box {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      display: flex;
      min-height: 380px;
      margin-bottom: 40px;
    }

    .requirements-copy {
      flex: 1.2;
      padding: 36px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 20px;
    }

    .requirements-title {
      font-size: 1.7rem;
      font-weight: 800;
      color: var(--text-heading);
      line-height: 1.25;
    }

    .requirements-list {
      display: flex;
      flex-direction: column;
      gap: 13px;
    }

    .requirement-item {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .requirement-item svg {
      width: 17px;
      height: 17px;
      color: var(--primary);
      flex-shrink: 0;
    }

    .requirement-item span {
      font-size: 0.95rem;
      font-weight: 500;
      color: var(--text-heading);
      line-height: 1.45;
    }

    .requirements-btn {
      align-self: flex-start;
      margin-top: 6px;
    }

    .requirements-image-wrap {
      flex: 0.9;
      position: relative;
      min-height: 360px;
      background-color: #DDE7E0;
    }

    .requirements-image {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Responsive adjustments */
    @media (max-width: 900px) {
      .owner-models-grid {
        grid-template-columns: 1fr;
      }
      .requirements-box {
        flex-direction: column-reverse;
      }
      .requirements-image-wrap {
        height: 230px;
        min-height: 230px;
        position: relative;
      }
      .requirements-copy {
        padding: 28px 24px;
      }
      .owner-hero-card {
        padding: 28px 24px;
      }
      .owner-hero-title {
        font-size: 1.75rem;
      }
    }
  
</style>
@endpush

@section('content')
<!-- ==========================================================================
       Main Building Owners Content (Matches Production Component module 1378)
       ========================================================================== -->
  <main class="owner-container">
    <div class="container">
      
      <!-- Hero Card Box -->
      <section class="owner-hero-card">
        <div class="owner-hero-kicker">Building Owners</div>
        <h1 class="owner-hero-title">List your building for workspace operators</h1>
        <p class="owner-hero-copy">Put your commercial floors in front of coworking operators planning their next centre - no broker chain, no listing fee.</p>
        
        <div class="owner-cta-row">
          <button class="btn-submit-building open-submit-modal-btn" type="button">
            <span>Submit your building</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
          <a href="{{ route('website.expansion') }}" class="btn-explore-expansion">
            <span>Explore expansion demand</span>
          </a>
        </div>
      </section>

      <!-- Choose a Partnership Model -->
      <section style="margin-bottom: 40px;">
        <h2 class="owner-section-title">Choose a partnership model</h2>
        <div class="owner-models-grid">
          
          <!-- Model 1: Straight Lease -->
          <div class="owner-model-card">
            <div class="model-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <h3 class="owner-model-title">Straight lease</h3>
            <p class="owner-model-text">Traditional lease to a vetted operator with a security deposit and lock-in.</p>
          </div>

          <!-- Model 2: Revenue Share -->
          <div class="owner-model-card">
            <div class="model-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="5" x2="5" y2="19"></line>
                <circle cx="6.5" cy="6.5" r="2.5"></circle>
                <circle cx="17.5" cy="17.5" r="2.5"></circle>
              </svg>
            </div>
            <h3 class="owner-model-title">Revenue share</h3>
            <p class="owner-model-text">Share the upside - a base rent plus a percentage of centre revenue.</p>
          </div>

          <!-- Model 3: Management Contract -->
          <div class="owner-model-card">
            <div class="model-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
              </svg>
            </div>
            <h3 class="owner-model-title">Management contract</h3>
            <p class="owner-model-text">Retain the asset while an operator runs the centre for a fee.</p>
          </div>

        </div>
      </section>

      <!-- What We Need From You Section -->
      <section>
        <div class="requirements-box">
          <div class="requirements-copy">
            <h2 class="requirements-title">What we need from you</h2>
            <div class="requirements-list">
              <div class="requirement-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>Building name, city and locality</span>
              </div>
              <div class="requirement-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>Total area and typical floor plate</span>
              </div>
              <div class="requirement-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>Expected rent per sq.ft. and availability date</span>
              </div>
              <div class="requirement-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>Building grade and key amenities</span>
              </div>
              <div class="requirement-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                  <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
                <span>Photos of the facade and available floors</span>
              </div>
            </div>

            <button class="btn-submit-building requirements-btn open-submit-modal-btn" type="button">
              <span>Submit building / commercial space</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>
          </div>

          <div class="requirements-image-wrap">
            <img src="{{ asset('assets/images/space3.png') }}" alt="Commercial Building for Operators" class="requirements-image" />
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- ==========================================================================
       Submit Building / Commercial Space Modal
       ========================================================================== -->
  <div class="modal-overlay" id="buildingModal" role="dialog" aria-modal="true">
    <div class="modal-content" style="max-width: 580px;">
      <div class="modal-header">
        <h3 class="modal-title">Submit Commercial Building</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
          Connect directly with top coworking brands &amp; managed operators looking to expand into your micro-market.
        </p>
        <form id="buildingSubmitForm" method="POST" action="{{ route('website.partner-inquiries.store') }}">
          @csrf
          <input type="hidden" name="inquiry_type" value="BUILDING_OWNER" />
          <div class="form-group">
            <label class="form-label" for="bldgName">Building / Property Name</label>
            <input type="text" class="form-control" id="bldgName" name="bldgName" value="{{ old('bldgName', old('entity_name')) }}" placeholder="e.g. Prestige Tech Park - Block C" required />
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="bldgCity">City</label>
              <select class="form-control" id="bldgCity" name="bldgCity">
                <option value="Bengaluru">Bengaluru</option>
                <option value="Mumbai">Mumbai</option>
                <option value="Delhi NCR">Delhi NCR / Gurgaon</option>
                <option value="Chennai">Chennai</option>
                <option value="Hyderabad">Hyderabad</option>
                <option value="Pune">Pune</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="bldgLocality">Micro-Market / Locality</label>
              <input type="text" class="form-control" id="bldgLocality" name="bldgLocality" value="{{ old('bldgLocality', old('locality')) }}" placeholder="e.g. Outer Ring Road, Kadubeesanahalli" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="bldgArea">Total Available Area (sq.ft.)</label>
              <input type="number" class="form-control" id="bldgArea" name="bldgArea" value="{{ old('bldgArea', old('total_area_sqft')) }}" placeholder="e.g. 35,000" min="2000" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="bldgFloorPlate">Floor Plate (sq.ft.)</label>
              <input type="number" class="form-control" id="bldgFloorPlate" name="bldgFloorPlate" value="{{ old('bldgFloorPlate', old('floor_plate_sqft')) }}" placeholder="e.g. 12,000" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="bldgModel">Preferred Model</label>
              <select class="form-control" id="bldgModel" name="bldgModel">
                <option value="Straight Lease">Straight Lease</option>
                <option value="Revenue Share">Revenue Share</option>
                <option value="Management Contract">Management Contract</option>
                <option value="Open to discussion">Open to discussion</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="bldgRent">Expected Rent (₹/sq.ft./mo)</label>
              <input type="text" class="form-control" id="bldgRent" name="bldgRent" value="{{ old('bldgRent', old('indicative_rent')) }}" placeholder="e.g. 85" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="bldgContact">Owner / Rep Name</label>
              <input type="text" class="form-control" id="bldgContact" name="bldgContact" value="{{ old('bldgContact', old('contact_name')) }}" placeholder="Full Name" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="bldgPhone">Phone Number</label>
              <input type="tel" class="form-control" id="bldgPhone" name="bldgPhone" value="{{ old('bldgPhone', old('contact_phone')) }}" placeholder="+91 98765 43210" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="bldgEmail">Business Email</label>
            <input type="email" class="form-control" id="bldgEmail" name="bldgEmail" value="{{ old('bldgEmail', old('contact_email')) }}" placeholder="owner@assetgroup.com" required />
          </div>

          <button type="submit" class="modal-submit-btn">Submit Property Details</button>
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

      const targetModal = document.getElementById('buildingModal');
      document.querySelectorAll('.open-submit-modal-btn, .open-listing-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          if (targetModal) {
            targetModal.classList.add('open');
            document.body.style.overflow = 'hidden';
          }
        });
      });

      const inquiryForm = document.getElementById('buildingSubmitForm');
      inquiryForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(inquiryForm);
        try {
          const resp = await fetch(inquiryForm.action, {
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
          targetModal?.classList.remove('open');
          document.body.style.overflow = '';
          showToast(data.message || 'Inquiry submitted! Our partnerships team will contact you shortly.');
          inquiryForm.reset();
        } catch (err) {
          showToast('Unable to submit inquiry right now.', 'warning');
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

    });
  </script>
@endpush
