@extends('layouts.website')

@section('title', 'SpaceWorq - Partner With Us | For Developers')
@section('active_nav', 'partner')
@section('active_partner', 'developers')
@section('body_class', 'dev-page-body')
@section('header_cta')
  <button class="btn-get-started open-submit-modal-btn" id="btnHeaderGetStarted" type="button">Submit Project</button>
@endsection
@section('drawer_cta')
  <button class="btn-get-started open-submit-modal-btn" style="width: 100%; justify-content: center;" type="button"><span>Submit Project</span></button>
@endsection

@push('styles')
<style>

    /* Specific styles for Developers Page (Exact SpaceWorq Production Design module 1380) */
    body.dev-page-body {
      background-color: #EDF6F9;
    }

    .dev-container {
      padding: 32px 0 60px 0;
    }

    /* Main Hero Action Card (Matches bundle heroCard) */
    .dev-hero-card {
      border-radius: 20px;
      padding: 36px 40px;
      border: 1px solid rgba(0, 108, 118, 0.22);
      background: rgba(0, 108, 118, 0.06);
      margin-bottom: 36px;
    }

    .dev-hero-kicker {
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1.8px;
      text-transform: uppercase;
      color: var(--primary);
      margin-bottom: 8px;
    }

    .dev-hero-title {
      font-size: 2.15rem;
      font-weight: 800;
      color: var(--text-heading);
      letter-spacing: -0.5px;
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .dev-hero-copy {
      font-size: 1.05rem;
      line-height: 1.6;
      color: var(--text-muted);
      max-width: 760px;
      margin-bottom: 24px;
    }

    .dev-cta-row {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .btn-submit-project {
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

    .btn-submit-project:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 108, 118, 0.4);
    }

    .btn-explore-demand {
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

    .btn-explore-demand:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* Section Title */
    .dev-section-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-heading);
      margin-bottom: 20px;
    }

    /* Why List With Us (3 Cards Grid) */
    .dev-features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }

    .dev-feature-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 26px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      display: flex;
      flex-direction: column;
    }

    .dev-feature-card:hover {
      transform: translateY(-3px);
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }

    .feature-icon-box {
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

    .feature-icon-box svg {
      width: 22px;
      height: 22px;
    }

    .dev-feature-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-heading);
      margin-bottom: 8px;
    }

    .dev-feature-text {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.55;
    }

    /* What We Need From You Card (Matches Bundle highlightsCard) */
    .dev-highlights-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 32px 36px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 40px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .dev-highlight-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .dev-highlight-row svg {
      width: 20px;
      height: 20px;
      color: var(--primary);
      flex-shrink: 0;
    }

    .dev-highlight-row span {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-heading);
    }

    .dev-bottom-cta-banner {
      background: linear-gradient(135deg, rgba(0, 108, 118, 0.08) 0%, rgba(62, 157, 143, 0.12) 100%);
      border: 1px solid rgba(0, 108, 118, 0.2);
      border-radius: 16px;
      padding: 24px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      margin-top: 10px;
      flex-wrap: wrap;
    }

    .dev-bottom-cta-text {
      max-width: 600px;
    }

    .dev-bottom-cta-text h4 {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-heading);
      margin-bottom: 4px;
    }

    .dev-bottom-cta-text p {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    /* Responsive adjustments */
    @media (max-width: 900px) {
      .dev-features-grid {
        grid-template-columns: 1fr;
      }
      .dev-hero-card {
        padding: 28px 24px;
      }
      .dev-hero-title {
        font-size: 1.75rem;
      }
      .dev-highlights-card {
        padding: 24px 20px;
      }
      .dev-bottom-cta-banner {
        flex-direction: column;
        align-items: flex-start;
      }
      .btn-submit-project {
        width: 100%;
        justify-content: center;
      }
    }
  
</style>
@endpush

@section('content')
<!-- ==========================================================================
       Main Developers Content (Matches Production Component module 1380)
       ========================================================================== -->
  <main class="dev-container">
    <div class="container">
      
      <!-- Hero Card Box -->
      <section class="dev-hero-card">
        <div class="dev-hero-kicker">Developers</div>
        <h1 class="dev-hero-title">Showcase upcoming commercial projects</h1>
        <p class="dev-hero-copy">Start pre-leasing conversations early. Put your under-construction campuses in front of flex operators and enterprise occupiers.</p>
        
        <div class="dev-cta-row">
          <button class="btn-submit-project open-submit-modal-btn" type="button">
            <span>Submit upcoming project</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
          <a href="{{ route('website.expansion') }}" class="btn-explore-demand">
            <span>Explore expansion demand</span>
          </a>
        </div>
      </section>

      <!-- Why List With Us (3 Features Grid) -->
      <section style="margin-bottom: 40px;">
        <h2 class="dev-section-title">Why list with us</h2>
        <div class="dev-features-grid">
          
          <!-- Feature 1: Early visibility -->
          <div class="dev-feature-card">
            <div class="feature-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <h3 class="dev-feature-title">Early visibility</h3>
            <p class="dev-feature-text">Put under-construction campuses in front of flex operators and enterprise occupiers before completion.</p>
          </div>

          <!-- Feature 2: Pre-leasing pipeline -->
          <div class="dev-feature-card">
            <div class="feature-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
              </svg>
            </div>
            <h3 class="dev-feature-title">Pre-leasing pipeline</h3>
            <p class="dev-feature-text">Start pre-leasing conversations early and gauge demand as your project progresses.</p>
          </div>

          <!-- Feature 3: Growth corridors -->
          <div class="dev-feature-card">
            <div class="feature-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
              </svg>
            </div>
            <h3 class="dev-feature-title">Growth corridors</h3>
            <p class="dev-feature-text">Reach operators and occupiers actively scouting India's fastest-growing commercial micro-markets.</p>
          </div>

        </div>
      </section>

      <!-- What We Need From You Card -->
      <section>
        <h2 class="dev-section-title">What we need from you</h2>
        <div class="dev-highlights-card">
          <div class="dev-highlight-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Project name, city and micro-market</span>
          </div>

          <div class="dev-highlight-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Expected completion date and construction stage</span>
          </div>

          <div class="dev-highlight-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Total leasable area and floor plate options</span>
          </div>

          <div class="dev-highlight-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Indicative rent per sq.ft.</span>
          </div>

          <div class="dev-highlight-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Renders, master plan, or site photos</span>
          </div>

          <div class="dev-bottom-cta-banner">
            <div class="dev-bottom-cta-text">
              <h4>Ready to present your campus to 500+ operators?</h4>
              <p>Our commercial developer relations team creates verified pre-leasing listings and matches you with anchor flex operators.</p>
            </div>
            <button class="btn-submit-project open-submit-modal-btn" type="button">
              <span>Submit Project Details</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- ==========================================================================
       Submit Upcoming Commercial Project Modal
       ========================================================================== -->
  <div class="modal-overlay" id="projectModal" role="dialog" aria-modal="true">
    <div class="modal-content" style="max-width: 580px;">
      <div class="modal-header">
        <h3 class="modal-title">Submit Commercial Project</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
          Present your under-construction campus or IT/business park to coworking brands and enterprise clients before handover.
        </p>
        <form id="projectSubmitForm" method="POST" action="{{ route('website.partner-inquiries.store') }}">
          @csrf
          <input type="hidden" name="inquiry_type" value="DEVELOPER" />
          <div class="form-group">
            <label class="form-label" for="projName">Project / Campus Name</label>
            <input type="text" class="form-control" id="projName" name="projName" value="{{ old('projName', old('entity_name')) }}" placeholder="e.g. Horizon Business Tech Park" required />
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="projCity">City</label>
              <select class="form-control" id="projCity" name="projCity">
                <option value="Bengaluru">Bengaluru</option>
                <option value="Mumbai">Mumbai</option>
                <option value="Delhi NCR">Delhi NCR / Gurgaon</option>
                <option value="Chennai">Chennai</option>
                <option value="Hyderabad">Hyderabad</option>
                <option value="Pune">Pune</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="projMicroMarket">Micro-Market / Corridor</label>
              <input type="text" class="form-control" id="projMicroMarket" name="projMicroMarket" value="{{ old('projMicroMarket', old('locality')) }}" placeholder="e.g. Whitefield, EPIP Zone" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="projTotalArea">Total Leasable Area (sq.ft.)</label>
              <input type="number" class="form-control" id="projTotalArea" name="projTotalArea" value="{{ old('projTotalArea', old('total_area_sqft')) }}" placeholder="e.g. 250,000" min="5000" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="projFloorPlate">Typical Floor Plate (sq.ft.)</label>
              <input type="number" class="form-control" id="projFloorPlate" name="projFloorPlate" value="{{ old('projFloorPlate', old('floor_plate_sqft')) }}" placeholder="e.g. 35,000" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="projStage">Construction Stage</label>
              <select class="form-control" id="projStage" name="projStage">
                <option value="Structure Complete">Structure Complete</option>
                <option value="Under Construction">Under Construction</option>
                <option value="Fit-out Ready">Fit-out Ready / Warm Shell</option>
                <option value="Upcoming / Planned">Upcoming / Planned</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="projDate">Expected Completion</label>
              <input type="text" class="form-control" id="projDate" name="projDate" value="{{ old('projDate', old('expected_completion')) }}" placeholder="e.g. Q3 2026" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="projRent">Indicative Rent (₹/sq.ft./mo)</label>
              <input type="text" class="form-control" id="projRent" name="projRent" value="{{ old('projRent', old('indicative_rent')) }}" placeholder="e.g. 75" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="projDevBrand">Developer / Enterprise Group</label>
              <input type="text" class="form-control" id="projDevBrand" name="projDevBrand" value="{{ old('projDevBrand', old('developer_brand')) }}" placeholder="e.g. Assetz Property Group" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="projContact">Contact Person</label>
              <input type="text" class="form-control" id="projContact" name="projContact" value="{{ old('projContact', old('contact_name')) }}" placeholder="Full Name" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="projPhone">Phone Number</label>
              <input type="tel" class="form-control" id="projPhone" name="projPhone" value="{{ old('projPhone', old('contact_phone')) }}" placeholder="+91 98765 43210" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="projEmail">Corporate Email</label>
            <input type="email" class="form-control" id="projEmail" name="projEmail" value="{{ old('projEmail', old('contact_email')) }}" placeholder="leasing@developer.com" required />
          </div>

          <button type="submit" class="modal-submit-btn">Submit Upcoming Commercial Project</button>
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

      const targetModal = document.getElementById('projectModal');
      document.querySelectorAll('.open-submit-modal-btn, .open-listing-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          if (targetModal) {
            targetModal.classList.add('open');
            document.body.style.overflow = 'hidden';
          }
        });
      });

      const inquiryForm = document.getElementById('projectSubmitForm');
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
