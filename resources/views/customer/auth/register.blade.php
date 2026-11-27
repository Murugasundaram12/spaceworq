<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Create your SpaceWorq account</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('website/css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('website/css/responsive.css') }}" />
  <style>

    /* Specific styles for SpaceWorq Register Screen (Matches module 1373) */
    body.register-page-body {
      background-color: #EDF6F9;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      padding: 0;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .register-page-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 16px 48px 16px;
      width: 100%;
      box-sizing: border-box;
    }

    .register-content-wrap {
      width: 100%;
      max-width: 540px;
      margin: 0 auto;
    }

    /* Top Row: Back Button */
    .register-top-row {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      margin-bottom: 20px;
    }

    .register-back-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #FFFFFF;
      border: 1px solid var(--border-color);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--text-heading);
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      box-shadow: var(--shadow-sm);
    }

    .register-back-btn:hover {
      background-color: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary);
      transform: translateX(-2px);
    }

    /* Brand Block */
    .register-brand-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-bottom: 22px;
    }

    .register-logo-img {
      height: 48px;
      width: auto;
      object-fit: contain;
      margin-bottom: 8px;
    }

    .register-brand-title {
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--text-heading);
      letter-spacing: -0.3px;
      margin: 0;
    }

    .register-brand-tagline {
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* Main Register Card */
    .register-card {
      background: #FFFFFF;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 32px 30px;
      box-shadow: 0 10px 30px rgba(0, 50, 60, 0.06);
      margin-bottom: 20px;
    }

    .register-card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 22px;
    }

    .register-card-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: var(--text-heading);
      margin: 0 0 4px 0;
      letter-spacing: -0.2px;
    }

    .register-card-sub {
      font-size: 0.88rem;
      color: var(--text-muted);
      margin: 0;
    }

    /* Form Fields Grid */
    .register-fields-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 14px;
    }

    .register-field {
      margin-bottom: 14px;
    }

    .register-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 6px;
    }

    .register-input-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #F8FAFC;
      border: 1.5px solid var(--border-color);
      border-radius: 12px;
      padding: 11px 14px;
      transition: var(--transition);
    }

    .register-input-wrap:focus-within {
      border-color: var(--primary);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(0, 108, 118, 0.12);
    }

    .register-input-wrap.has-error {
      border-color: #EF4444;
      background: #FEF2F2;
    }

    .register-input-wrap svg.input-icon {
      width: 17px;
      height: 17px;
      color: var(--text-muted);
      flex-shrink: 0;
    }

    .register-input {
      flex: 1;
      border: none;
      background: transparent;
      outline: none;
      font-size: 0.92rem;
      color: var(--text-heading);
      font-family: inherit;
    }

    .register-input::placeholder {
      color: #94A3B8;
    }

    .register-eye-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .register-eye-btn:hover {
      color: var(--text-heading);
    }

    .register-field-hint {
      display: none;
      font-size: 0.76rem;
      color: #EF4444;
      margin-top: 4px;
      font-weight: 500;
    }

    .register-field-hint.active {
      display: block;
    }

    /* Agree Row */
    .agree-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin: 16px 0 10px 0;
      cursor: pointer;
      user-select: none;
    }

    .custom-checkbox {
      width: 20px;
      height: 20px;
      border-radius: 5px;
      border: 1.5px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: transparent;
      transition: var(--transition);
      flex-shrink: 0;
      margin-top: 1px;
    }

    .agree-row.checked .custom-checkbox {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    .custom-checkbox svg {
      width: 12px;
      height: 12px;
      color: #FFFFFF;
      display: none;
    }

    .agree-row.checked .custom-checkbox svg {
      display: block;
    }

    .agree-text {
      font-size: 0.85rem;
      color: var(--text-muted);
      line-height: 1.45;
    }

    .agree-text a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .agree-text a:hover {
      text-decoration: underline;
    }

    /* Error Box */
    .form-error-box {
      display: none;
      padding: 10px 14px;
      border-radius: 10px;
      background: #FEE2E2;
      border: 1px solid #FCA5A5;
      color: #DC2626;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 14px;
      text-align: center;
    }

    .form-error-box.active {
      display: block;
    }

    /* Submit Button */
    .btn-register-submit {
      width: 100%;
      padding: 14px;
      border-radius: 14px;
      background-color: var(--primary);
      color: #FFFFFF;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 4px 14px rgba(0, 108, 118, 0.28);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 10px;
    }

    .btn-register-submit:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 108, 118, 0.38);
    }

    .btn-register-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
      transform: none;
    }

    /* Success Box (Matches Bundle successBox) */
    .success-box {
      display: none;
      border: 1.5px solid rgba(0, 108, 118, 0.35);
      background: rgba(0, 108, 118, 0.08);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 14px;
      flex-direction: column;
      gap: 12px;
      text-align: center;
    }

    .success-box.active {
      display: flex;
    }

    .success-title {
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--text-heading);
      margin: 0;
    }

    .success-text {
      font-size: 0.92rem;
      color: var(--text-muted);
      line-height: 1.55;
      margin: 0;
    }

    .btn-outline-action {
      width: 100%;
      border: 1.5px solid var(--primary);
      background: transparent;
      color: var(--primary);
      border-radius: 12px;
      padding: 12px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-outline-action:hover {
      background: rgba(0, 108, 118, 0.06);
    }

    /* Footer Navigation */
    .register-footer {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      font-size: 0.88rem;
      color: var(--text-muted);
      margin-top: 12px;
    }

    .register-footer a {
      color: var(--primary);
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
    }

    .register-footer a:hover {
      text-decoration: underline;
    }

    @media (max-width: 600px) {
      .register-fields-grid {
        grid-template-columns: 1fr;
      }
      .register-card {
        padding: 24px 20px;
      }
    }
  
  </style>
</head>
<body class="register-page-body">
<main class="register-page-container">
    <div class="register-content-wrap">
      
      <!-- Top Row: Back Navigation Button -->
      <div class="register-top-row">
        <a href="{{ route('home') }}" class="register-back-btn" id="regBackBtn" aria-label="Go back">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
        </a>
      </div>

      <!-- Brand Logo & Tagline -->
      <div class="register-brand-wrap">
        <a href="{{ route('home') }}" style="text-decoration: none;">
          <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq Logo" class="register-logo-img" />
        </a>
        <h1 class="register-brand-title">SpaceWorq</h1>
        <p class="register-brand-tagline">Your workspace, your way</p>
      </div>

      <!-- Main Register Card -->
      <div class="register-card">
        <div class="register-card-header">
          <div>
            <h2 class="register-card-title" id="cardHeading">Create User</h2>
            <p class="register-card-sub" id="cardSub">Sign up as a client user</p>
          </div>
        </div>

        @include('partials.alerts')

        <!-- Registration Form -->
        <form id="registerForm" method="POST" action="{{ route('customer.register.submit') }}">
          @csrf
          <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '/' }}" />
          <!-- Form Error Box -->
          <div class="form-error-box" id="formErrorBox"></div>

          <!-- First & Last Name -->
          <div class="register-fields-grid">
            <div>
              <label class="register-label" for="firstName">First Name</label>
              <div class="register-input-wrap" id="wrapFirstName">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input 
                  type="text" 
                  class="register-input" 
                  id="firstName" name="first_name" value="{{ old('first_name') }}" 
                  placeholder="Arjun" 
                  required 
                  autocomplete="given-name"
                />
              </div>
              <div class="register-field-hint" id="hintFirstName">First name must be at least 2 characters</div>
            </div>

            <div>
              <label class="register-label" for="lastName">Last Name</label>
              <div class="register-input-wrap" id="wrapLastName">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input 
                  type="text" 
                  class="register-input" 
                  id="lastName" name="last_name" value="{{ old('last_name') }}" 
                  placeholder="Kumar" 
                  required 
                  autocomplete="family-name"
                />
              </div>
              <div class="register-field-hint" id="hintLastName">Last name must be at least 2 characters</div>
            </div>
          </div>

          <!-- Email Address -->
          <div class="register-field">
            <label class="register-label" for="regEmail">Email Address</label>
            <div class="register-input-wrap" id="wrapEmail">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              <input 
                type="email" 
                class="register-input" 
                id="regEmail" name="email" value="{{ old('email') }}" 
                placeholder="arjun@company.com" 
                required 
                autocomplete="email"
              />
            </div>
            <div class="register-field-hint" id="hintEmail">Enter a valid email address</div>
          </div>

          <!-- Mobile Number -->
          <div class="register-field">
            <label class="register-label" for="regMobile">Mobile Number</label>
            <div class="register-input-wrap" id="wrapMobile">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                <line x1="12" y1="18" x2="12.01" y2="18"></line>
              </svg>
              <input 
                type="tel" 
                class="register-input" 
                id="regMobile" name="mobile" value="{{ old('mobile') }}" 
                placeholder="9876543210" 
                maxlength="10" 
                required 
                autocomplete="tel"
              />
            </div>
            <div class="register-field-hint" id="hintMobile">Mobile number must be exactly 10 digits</div>
          </div>

          <!-- Password -->
          <div class="register-field">
            <label class="register-label" for="regPassword">Password</label>
            <div class="register-input-wrap" id="wrapPassword">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input 
                type="password" 
                class="register-input" 
                id="regPassword" name="password" 
                placeholder="Min. 8 with upper, lower, number, special" 
                required 
                autocomplete="new-password"
              />
              <button type="button" class="register-eye-btn" id="toggleRegPass" aria-label="Toggle password visibility">
                <svg id="passEyeIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
            <div class="register-field-hint" id="hintPassword">Use 8+ chars with upper, lower, number, and special character</div>
          </div>

          <!-- Confirm Password -->
          <div class="register-field">
            <label class="register-label" for="regConfirmPassword">Confirm Password</label>
            <div class="register-input-wrap" id="wrapConfirmPassword">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <input 
                type="password" 
                class="register-input" 
                id="regConfirmPassword" name="password_confirmation" 
                placeholder="Re-enter your password" 
                required 
                autocomplete="new-password"
              />
            </div>
            <div class="register-field-hint" id="hintConfirmPassword">Passwords do not match</div>
          </div>

          <!-- Agree Terms -->
          <label class="agree-row {{ old('terms_accepted') ? 'checked' : '' }}" id="agreeRow" for="termsAcceptedInput">
            <input type="checkbox" id="termsAcceptedInput" name="terms_accepted" value="1" {{ old('terms_accepted') ? 'checked' : '' }} style="display:none;" />
            <div class="custom-checkbox">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div class="agree-text">
              I agree to the <a href="#terms">Terms of Service</a> and <a href="#privacy">Privacy Policy</a>
            </div>
          </label>
          <div class="register-field-hint" id="hintTerms" style="margin-bottom: 12px;">Please accept Terms of Service and Privacy Policy</div>

          <!-- Submit Button -->
          <button type="submit" class="btn-register-submit" id="btnSubmitRegister">
            <span>Create Client Account</span>
          </button>

          <!-- Footer -->
          <div class="register-footer">
            <span>Already have an account?</span>
            <a href="{{ route('login', ['redirectTo' => $redirectTo ?? '/']) }}" id="linkToSignIn">Sign In</a>
          </div>
        </form>
      </div>

    </div>
  </main>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Client-side Logic -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('registerForm');
      const firstName = document.getElementById('firstName');
      const lastName = document.getElementById('lastName');
      const regEmail = document.getElementById('regEmail');
      const regMobile = document.getElementById('regMobile');
      const regPassword = document.getElementById('regPassword');
      const regConfirmPassword = document.getElementById('regConfirmPassword');
      const agreeRow = document.getElementById('agreeRow');
      const termsInput = document.getElementById('termsAcceptedInput');
      const hintTerms = document.getElementById('hintTerms');
      const btnSubmitRegister = document.getElementById('btnSubmitRegister');

      agreeRow?.addEventListener('click', (e) => {
        if (e.target.tagName === 'A') return;
        setTimeout(() => {
          if (termsInput) {
            agreeRow.classList.toggle('checked', termsInput.checked);
            if (termsInput.checked) hintTerms?.classList.remove('active');
          }
        }, 0);
      });

      document.getElementById('toggleRegPass')?.addEventListener('click', () => {
        regPassword.type = regPassword.type === 'password' ? 'text' : 'password';
      });

      form?.addEventListener('submit', (e) => {
        let valid = true;
        document.querySelectorAll('.register-input-wrap').forEach(w => w.classList.remove('has-error'));
        document.querySelectorAll('.register-field-hint').forEach(h => h.classList.remove('active'));
        if (!firstName?.value.trim() || firstName.value.trim().length < 2) {
          document.getElementById('wrapFirstName')?.classList.add('has-error');
          document.getElementById('hintFirstName')?.classList.add('active');
          valid = false;
        }
        if (!lastName?.value.trim() || lastName.value.trim().length < 2) {
          document.getElementById('wrapLastName')?.classList.add('has-error');
          document.getElementById('hintLastName')?.classList.add('active');
          valid = false;
        }
        if (!regEmail?.value.trim()) {
          document.getElementById('wrapEmail')?.classList.add('has-error');
          document.getElementById('hintEmail')?.classList.add('active');
          valid = false;
        }
        if (!/^\d{10}$/.test(regMobile?.value.trim() || '')) {
          document.getElementById('wrapMobile')?.classList.add('has-error');
          document.getElementById('hintMobile')?.classList.add('active');
          valid = false;
        }
        if (!regPassword?.value || regPassword.value.length < 8) {
          document.getElementById('wrapPassword')?.classList.add('has-error');
          document.getElementById('hintPassword')?.classList.add('active');
          valid = false;
        }
        if (regConfirmPassword?.value !== regPassword?.value) {
          document.getElementById('wrapConfirmPassword')?.classList.add('has-error');
          document.getElementById('hintConfirmPassword')?.classList.add('active');
          valid = false;
        }
        if (termsInput && !termsInput.checked) {
          hintTerms?.classList.add('active');
          valid = false;
        }
        if (!valid) {
          e.preventDefault();
          return;
        }
        if (btnSubmitRegister) {
          btnSubmitRegister.disabled = true;
          btnSubmitRegister.textContent = 'Creating Account...';
        }
      });
    });
  </script>
</body>
</html>
