<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Forgot Password — SpaceWorq</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('website/css/style.css') }}" />
  @include('customer.auth._styles')
</head>
<body class="auth-page-body">
  <main class="auth-page-container">
    <div class="auth-content-wrap">
      <div class="auth-top-row">
        <a href="{{ route('login') }}" class="auth-back-btn" aria-label="Back to Sign In">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
        </a>
      </div>

      <div class="auth-brand-wrap">
        <a href="{{ route('home') }}" style="text-decoration: none;">
          <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq Logo" class="auth-logo-img" />
        </a>
        <h1 class="auth-brand-title">SpaceWorq</h1>
        <p class="auth-brand-tagline">Your workspace, your way</p>
      </div>

      <div class="auth-card">
        <h2 class="auth-card-title">Reset your password</h2>
        <p class="auth-card-sub">Enter your email address and we will send you a password reset link.</p>

        @include('partials.alerts')

        <form id="forgotPasswordForm" method="POST" action="{{ route('password.email') }}">
          @csrf
          <div class="auth-field">
            <label class="auth-label" for="forgotEmail">Email Address</label>
            <div class="auth-input-wrap">
              <input
                type="email"
                class="auth-input"
                id="forgotEmail"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@company.com"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <button type="submit" class="btn-auth-submit">
            <span>Send Reset Link</span>
          </button>
        </form>
      </div>

      <div class="auth-footer">
        <span>Remembered your password?</span>
        <a href="{{ route('login') }}">Back to Sign In</a>
      </div>
    </div>
  </main>
</body>
</html>
