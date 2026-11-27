<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Sign in to SpaceWorq</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('website/css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('website/css/responsive.css') }}" />
  @include('customer.auth._styles')
</head>
<body class="auth-page-body">
<main class="auth-page-container">
    <div class="auth-content-wrap">
      
      <!-- Top Row: Back Navigation Button -->
      <div class="auth-top-row">
        <a href="{{ route('home') }}" class="auth-back-btn" id="authBackBtn" aria-label="Go back">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
        </a>
      </div>

      <!-- Brand Logo & Tagline -->
      <div class="auth-brand-wrap">
        <a href="{{ route('home') }}" style="text-decoration: none;">
          <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq Logo" class="auth-logo-img" />
        </a>
        <h1 class="auth-brand-title">SpaceWorq</h1>
        <p class="auth-brand-tagline">Your workspace, your way</p>
      </div>

      <!-- Redirect Notification Banner (if redirected from protected/partner routes) -->
      @if (($redirectTo ?? '/') !== '/')
        <div class="redirect-banner" id="redirectBanner" style="display:flex;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span id="redirectTargetLabel">Sign in to continue to: {{ $redirectTo }}</span>
        </div>
      @endif

      <!-- Sign In Card -->
      <div class="auth-card">
        <h2 class="auth-card-title">Welcome back</h2>
        <p class="auth-card-sub">Sign in to manage your bookings and listings</p>

        @include('partials.alerts')

        <form id="loginForm" method="POST" action="{{ route('customer.login.submit') }}">
          @csrf
          <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '/' }}" />
          <!-- Email Address Field -->
          <div class="auth-field">
            <label class="auth-label" for="loginEmail">Email Address</label>
            <div class="auth-input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              <input 
                type="email" 
                class="auth-input" 
                id="loginEmail" 
                name="email"
                value="{{ old('email', old('identifier')) }}"
                placeholder="you@company.com" 
                required 
                autocomplete="email"
              />
            </div>
          </div>

          <!-- Password Field -->
          <div class="auth-field">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label class="auth-label" for="loginPassword" style="margin-bottom:0;">Password</label>
              <a href="{{ route('password.request') }}" style="font-size:0.8rem; color:var(--primary); font-weight:600; text-decoration:none;">Forgot password?</a>
            </div>
            <div class="auth-input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input 
                type="password" 
                class="auth-input" 
                id="loginPassword" 
                name="password"
                placeholder="Enter password" 
                required 
                autocomplete="current-password"
              />
              <button type="button" class="auth-eye-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">
                <svg id="eyeIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="btn-auth-submit" id="btnSubmitLogin">
            <span>Sign In</span>
          </button>
        </form>
      </div>

      <!-- Footer Account Navigation -->
      <div class="auth-footer">
        <span>New to SpaceWorq?</span>
        <a href="{{ route('register', ['redirectTo' => $redirectTo ?? '/']) }}" id="createAccountBtn">Create Account</a>
      </div>

      <!-- Continue as Guest -->
      <a href="{{ $redirectTo ?? route('home') }}" class="auth-guest-btn" id="guestBtn">
        Continue as Guest
      </a>

      <!-- Resend Activation Email Link -->
      <a href="#resendActivation" class="auth-resend-link" id="openResendModalBtn">
        Already registered but not activated? Resend activation email
      </a>

    </div>
  </main>

  <!-- Resend Activation Modal -->
  <div class="modal-overlay" id="resendModal" role="dialog" aria-modal="true">
    <div class="modal-content-card">
      <div class="modal-header-row">
        <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-heading); margin: 0;">
          Resend activation email
        </h3>
        <button class="modal-close-icon" id="closeResendBtn" aria-label="Close modal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 18px 0;">
        Enter the email used to create your Client or Partner account.
      </p>

      <form id="resendForm" method="POST" action="{{ route('customer.activation.resend') }}">
        @csrf
        <div class="auth-field">
          <label class="auth-label" for="resendEmail">Email Address</label>
          <div class="auth-input-wrap">
            <input 
              type="email" 
              class="auth-input" 
              id="resendEmail" name="email" value="{{ old('email') }}" 
              placeholder="you@company.com" 
              required 
            />
          </div>
        </div>
        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
          <button type="button" class="auth-guest-btn" id="cancelResendBtn" style="width: auto; margin: 0; padding: 10px 18px;">
            Close
          </button>
          <button type="submit" class="btn-auth-submit" style="width: auto; margin: 0; padding: 10px 22px;">
            Send activation email
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Client-side Controller -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const loginForm = document.getElementById('loginForm');
      const loginEmail = document.getElementById('loginEmail');
      const loginPassword = document.getElementById('loginPassword');
      const emailGroup = document.getElementById('emailGroup');
      const passwordGroup = document.getElementById('passwordGroup');
      const emailError = document.getElementById('emailError');
      const passwordError = document.getElementById('passwordError');
      const togglePasswordBtn = document.getElementById('togglePasswordBtn');
      const iconEyeOpen = document.getElementById('iconEyeOpen');
      const iconEyeClosed = document.getElementById('iconEyeClosed');
      const btnSubmitLogin = document.getElementById('btnSubmitLogin');

      if (togglePasswordBtn && loginPassword) {
        togglePasswordBtn.addEventListener('click', () => {
          const isPassword = loginPassword.type === 'password';
          loginPassword.type = isPassword ? 'text' : 'password';
          togglePasswordBtn.setAttribute('aria-pressed', String(isPassword));
        });
      }

      if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
          const emailVal = loginEmail?.value.trim() || '';
          const passVal = loginPassword?.value || '';
          const errorBox = document.getElementById('authErrorBox');
          if (!emailVal || !passVal) {
            e.preventDefault();
            if (errorBox) {
              errorBox.textContent = 'Please enter both email address and password.';
              errorBox.classList.add('active');
            }
            return;
          }
          if (btnSubmitLogin) {
            btnSubmitLogin.disabled = true;
            btnSubmitLogin.textContent = 'Signing in...';
          }
        });
      }

      const openResendLink = document.getElementById('openResendModalBtn');
      const resendModal = document.getElementById('resendModal');
      const closeResendBtn = document.getElementById('closeResendBtn');
      const cancelResendBtn = document.getElementById('cancelResendBtn');
      const resendEmail = document.getElementById('resendEmail');

      openResendLink?.addEventListener('click', (e) => {
        e.preventDefault();
        if (resendEmail && loginEmail?.value.trim()) resendEmail.value = loginEmail.value.trim();
        resendModal?.classList.add('open');
      });
      closeResendBtn?.addEventListener('click', () => resendModal?.classList.remove('open'));
      cancelResendBtn?.addEventListener('click', () => resendModal?.classList.remove('open'));
      resendModal?.addEventListener('click', (e) => {
        if (e.target === resendModal) resendModal.classList.remove('open');
      });

      @php
        $__guestRedirect = $redirectTo ?? route('home');
      @endphp
      document.getElementById('btnContinueGuest')?.addEventListener('click', () => {
        window.location.href = @json($__guestRedirect);
      });
    });
  </script>
</body>
</html>
