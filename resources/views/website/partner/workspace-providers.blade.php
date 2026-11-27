@extends('layouts.website')

@section('title', 'SpaceWorq - Partner With Us | For Workspace Providers')
@section('active_nav', 'partner')
@section('active_partner', 'workspace-providers')
@section('body_class', 'partner-page-body')

@push('styles')
<style>

    /* Specific styles for Workspace Providers Page (Exact SpaceWorq Production Design) */
    body.provider-page-body {
      background-color: #EDF6F9;
    }

    .provider-container {
      padding: 32px 0 60px 0;
    }

    /* Top Operator Cover Banner (Matches Bundle bottomImageSection) */
    .provider-cover-banner {
      position: relative;
      min-height: 240px;
      border-radius: 20px;
      overflow: hidden;
      background-color: #0B3320;
      margin-bottom: 24px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .provider-cover-img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .provider-cover-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(4, 24, 15, 0.3) 0%, rgba(4, 24, 15, 0.82) 100%);
    }

    .provider-cover-content {
      position: relative;
      z-index: 2;
      min-height: 240px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 28px 32px;
    }

    .provider-cover-kicker {
      color: #BCE7C9;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 1.6px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .provider-cover-title {
      color: #FFFFFF;
      font-size: 1.85rem;
      font-weight: 800;
      line-height: 1.25;
      max-width: 680px;
    }

    /* Main Hero Action Card (Matches bundle heroCard) */
    .provider-hero-card {
      border-radius: 20px;
      padding: 36px 40px;
      border: 1px solid rgba(0, 108, 118, 0.22);
      background: rgba(0, 108, 118, 0.06);
      margin-bottom: 36px;
    }

    .provider-hero-kicker {
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 1.8px;
      text-transform: uppercase;
      color: var(--primary);
      margin-bottom: 8px;
    }

    .provider-hero-title {
      font-size: 2.15rem;
      font-weight: 800;
      color: var(--text-heading);
      letter-spacing: -0.5px;
      line-height: 1.2;
      margin-bottom: 12px;
    }

    .provider-hero-copy {
      font-size: 1.05rem;
      line-height: 1.6;
      color: var(--text-muted);
      max-width: 760px;
      margin-bottom: 24px;
    }

    .provider-cta-row {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }

    .btn-start-listing {
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
    }

    .btn-start-listing:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 108, 118, 0.4);
    }

    .btn-find-centre {
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
    }

    .btn-find-centre:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* Section Subtitle */
    .provider-section-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-heading);
      margin-bottom: 20px;
    }

    /* How Listing Works (3 Cards Grid) */
    .provider-steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }

    .provider-step-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }

    .provider-step-card:hover {
      transform: translateY(-3px);
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }

    .provider-step-num {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 10px;
      font-variant-numeric: tabular-nums;
    }

    .provider-step-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-heading);
      margin-bottom: 8px;
    }

    .provider-step-text {
      font-size: 0.88rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    /* Everything In One Dashboard Highlights */
    .provider-highlights-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 28px 32px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 40px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .highlight-item {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .highlight-item svg {
      width: 20px;
      height: 20px;
      color: var(--primary);
      flex-shrink: 0;
    }

    .highlight-item span {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-heading);
    }

    @media (max-width: 900px) {
      .provider-steps-grid {
        grid-template-columns: 1fr;
      }
      .provider-cover-title {
        font-size: 1.45rem;
      }
      .provider-hero-title {
        font-size: 1.7rem;
      }
    }
  
</style>
@endpush

@section('content')
<!-- ==========================================================================
       Main Workspace Providers Content (Matches Production Component)
       ========================================================================== -->
  <main class="provider-container">
    <div class="container">
      
      <!-- Top Cover Image Section (bottomImageSection in bundle) -->
      <section class="provider-cover-banner">
        <img src="{{ asset('assets/images/space2.png') }}" alt="Built for operators" class="provider-cover-img" />
        <div class="provider-cover-overlay"></div>
        <div class="provider-cover-content">
          <div class="provider-cover-kicker">Built for operators</div>
          <h1 class="provider-cover-title">Keep every centre visible, current, and ready for demand.</h1>
        </div>
      </section>

      <!-- Hero Card Box -->
      <section class="provider-hero-card">
        <div class="provider-hero-kicker">Workspace Partners</div>
        <h2 class="provider-hero-title">List &amp; manage your workspace inventory</h2>
        <p class="provider-hero-copy">Publish every centre you operate, keep pricing and availability current, and get in front of members searching in your micro-market.</p>
        
        <div class="provider-cta-row">
          <button class="btn-start-listing open-listing-modal-btn" type="button">
            <span>Start listing</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
          <a href="{{ route('website.expansion') }}" class="btn-find-centre">
            <span>Find your next centre</span>
          </a>
        </div>
      </section>

      <!-- How Listing Works -->
      <section style="margin-bottom: 40px;">
        <h2 class="provider-section-title">How listing works</h2>
        <div class="provider-steps-grid">
          <div class="provider-step-card">
            <div class="provider-step-num">01</div>
            <h3 class="provider-step-title">Create your partner account</h3>
            <p class="provider-step-text">Sign up as a workspace partner - it takes under two minutes.</p>
          </div>

          <div class="provider-step-card">
            <div class="provider-step-num">02</div>
            <h3 class="provider-step-title">Add your inventory</h3>
            <p class="provider-step-text">Publish each centre with photos, seat counts, amenities, and pricing.</p>
          </div>

          <div class="provider-step-card">
            <div class="provider-step-num">03</div>
            <h3 class="provider-step-title">Manage and grow</h3>
            <p class="provider-step-text">Edit pricing, pause listings, and track enquiries from your dashboard.</p>
          </div>
        </div>
      </section>

      <!-- Everything in One Dashboard Highlights -->
      <section>
        <h2 class="provider-section-title">Everything in one dashboard</h2>
        <div class="provider-highlights-card">
          <div class="highlight-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Publish unlimited centres and space types</span>
          </div>

          <div class="highlight-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Set pricing, seat counts, and amenities</span>
          </div>

          <div class="highlight-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Save drafts and publish when you're ready</span>
          </div>

          <div class="highlight-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Edit or remove any listing instantly</span>
          </div>

          <div class="highlight-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Zero listing fee - you keep your margin</span>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- ==========================================================================
       Partner Registration / Start Listing Modal
       ========================================================================== -->
  <div class="modal-overlay" id="partnerModal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Partner Onboarding</h3>
        <button class="modal-close-btn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
          Join 500+ operators publishing centres across India. Our partner relations team will approve your inventory in under 24 hours.
        </p>
        <form id="partnerListingForm" method="POST" action="{{ route('website.partner-inquiries.store') }}">
          @csrf
          <input type="hidden" name="inquiry_type" value="WORKSPACE_PROVIDER" />
          <div class="form-group">
            <label class="form-label" for="centreName">Centre / Brand Name</label>
            <input type="text" class="form-control" id="centreName" name="centreName" value="{{ old('centreName', old('entity_name')) }}" placeholder="e.g. Hive Coworking Koramangala" required />
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="providerCity">City</label>
              <select class="form-control" id="providerCity" name="providerCity">
                <option value="Bengaluru">Bengaluru</option>
                <option value="Mumbai">Mumbai</option>
                <option value="Delhi NCR">Delhi NCR / Gurgaon</option>
                <option value="Chennai">Chennai</option>
                <option value="Hyderabad">Hyderabad</option>
                <option value="Pune">Pune</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="locality">Micro-Market / Area</label>
              <input type="text" class="form-control" id="locality" name="locality" value="{{ old('locality') }}" placeholder="e.g. HSR Layout, Sector 4" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="totalDesks">Total Capacity (Desks)</label>
              <input type="number" class="form-control" id="totalDesks" name="totalDesks" value="{{ old('totalDesks', old('total_desks')) }}" placeholder="e.g. 150" min="5" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="deskPrice">Starting Desk Price (₹/mo)</label>
              <input type="number" class="form-control" id="deskPrice" name="deskPrice" value="{{ old('deskPrice', old('desk_price')) }}" placeholder="e.g. 7500" required />
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="form-group">
              <label class="form-label" for="contactPerson">Contact Person</label>
              <input type="text" class="form-control" id="contactPerson" name="contactPerson" value="{{ old('contactPerson', old('contact_name')) }}" placeholder="Full Name" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="contactPhone">Phone Number</label>
              <input type="tel" class="form-control" id="contactPhone" name="contactPhone" value="{{ old('contactPhone', old('contact_phone')) }}" placeholder="+91 98765 43210" required />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="contactEmail">Business Email</label>
            <input type="email" class="form-control" id="contactEmail" name="contactEmail" value="{{ old('contactEmail', old('contact_email')) }}" placeholder="partner@yourbrand.com" required />
          </div>

          <button type="submit" class="modal-submit-btn">Submit Partner Listing</button>
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

      const targetModal = document.getElementById('partnerModal');
      document.querySelectorAll('.open-submit-modal-btn, .open-listing-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          if (targetModal) {
            targetModal.classList.add('open');
            document.body.style.overflow = 'hidden';
          }
        });
      });

      const inquiryForm = document.getElementById('partnerListingForm');
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
