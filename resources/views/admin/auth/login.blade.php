<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Super Admin Login — SpaceWorq</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/auth.css') }}" />
</head>
<body>
  <main class="auth-wrapper">
    <header class="mobile-brand-header">
      <a href="{{ route('admin.login') }}" class="brand-logo" aria-label="SpaceWorq Admin Home">
        <svg class="brand-logo-icon" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" />
          <rect x="20" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
          <rect x="3" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
          <rect x="20" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" />
        </svg>
        <span class="brand-logo-text">SuperAdmin</span>
      </a>
    </header>

    <section class="auth-brand-panel" aria-label="SpaceWorq Administration">
      <div class="auth-brand-content">
        <a href="{{ route('admin.login') }}" class="brand-logo" aria-label="SpaceWorq Admin Home">
          <svg class="brand-logo-icon" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="3" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" />
            <rect x="20" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
            <rect x="3" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
            <rect x="20" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" />
          </svg>
          <span class="brand-logo-text">SuperAdmin</span>
        </a>

        <h1 class="brand-heading">Platform<br />governance &amp;<br />approvals</h1>
        <p class="brand-description">Review provider applications, verify KYC documents, moderate workspace listings, and manage consultations and partner inquiries.</p>

        <ul class="brand-features">
          <li>Approve providers and verify submitted KYC compliance files</li>
          <li>Approve or moderate published workspace inventory across cities</li>
          <li>Manage master data, support tickets, and audit history</li>
        </ul>
      </div>
    </section>

    <section class="auth-form-panel" aria-label="Super Admin Sign In Form">
      <div class="auth-form-container">
        <h1 class="auth-title">Super Admin Sign In</h1>
        <p class="auth-subtitle">Enter your authorized administrator credentials to continue.</p>

        @include('partials.alerts')

        <form id="adminLoginForm" method="POST" action="{{ route('admin.login.submit') }}" novalidate>
          @csrf
          <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? route('admin.dashboard') }}" />

          <div class="sw-field">
            <input
              type="text"
              id="adminLoginIdentifier"
              name="identifier"
              value="{{ old('identifier') }}"
              class="sw-input @error('identifier') is-invalid @enderror"
              placeholder=" "
              autocomplete="username"
              autofocus
              required
            />
            <label for="adminLoginIdentifier" class="sw-label">Admin email or username</label>
          </div>

          <div class="sw-field">
            <input
              type="password"
              id="adminLoginPassword"
              name="password"
              class="sw-input sw-input-password @error('password') is-invalid @enderror"
              placeholder=" "
              autocomplete="current-password"
              required
            />
            <label for="adminLoginPassword" class="sw-label">Password</label>
            <button
              type="button"
              class="password-toggle-btn"
              data-toggle-password="adminLoginPassword"
              aria-label="Show password"
              aria-pressed="false"
            >
              <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
              </svg>
              <svg class="eye-closed d-none" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                <line x1="1" y1="1" x2="23" y2="23" />
              </svg>
            </button>
          </div>

          <div class="auth-options-row">
            <label class="sw-checkbox-label" for="adminRememberMe">
              <input type="checkbox" id="adminRememberMe" name="remember" value="1" class="sw-checkbox" />
              <span>Remember me</span>
            </label>
          </div>

          <button type="submit" id="adminLoginSubmitBtn" class="btn-sw-primary">Sign In</button>
        </form>
      </div>
    </section>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          const target = document.getElementById(btn.getAttribute('data-toggle-password'));
          if (!target) return;
          const isPwd = target.type === 'password';
          target.type = isPwd ? 'text' : 'password';
          btn.querySelector('.eye-open')?.classList.toggle('d-none', isPwd);
          btn.querySelector('.eye-closed')?.classList.toggle('d-none', !isPwd);
        });
      });
    });
  </script>
</body>
</html>
