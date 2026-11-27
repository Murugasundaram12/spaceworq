<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Choose New Password — SpaceWorq</title>
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
      <div class="auth-brand-wrap">
        <a href="{{ route('home') }}" style="text-decoration: none;">
          <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq Logo" class="auth-logo-img" />
        </a>
        <h1 class="auth-brand-title">SpaceWorq</h1>
        <p class="auth-brand-tagline">Your workspace, your way</p>
      </div>

      <div class="auth-card">
        <h2 class="auth-card-title">Set a new password</h2>
        <p class="auth-card-sub">Confirm your email and enter your new password below.</p>

        @include('partials.alerts')

        <form id="resetPasswordForm" method="POST" action="{{ route('password.update') }}">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}" />

          <div class="auth-field">
            <label class="auth-label" for="resetEmail">Email Address</label>
            <div class="auth-input-wrap">
              <input
                type="email"
                class="auth-input"
                id="resetEmail"
                name="email"
                value="{{ old('email', $email) }}"
                required
                autocomplete="email"
              />
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label" for="resetPassword">New Password</label>
            <div class="auth-input-wrap">
              <input
                type="password"
                class="auth-input"
                id="resetPassword"
                name="password"
                placeholder="Minimum 8 characters"
                required
                autocomplete="new-password"
              />
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label" for="resetPasswordConfirmation">Confirm New Password</label>
            <div class="auth-input-wrap">
              <input
                type="password"
                class="auth-input"
                id="resetPasswordConfirmation"
                name="password_confirmation"
                placeholder="Re-enter new password"
                required
                autocomplete="new-password"
              />
            </div>
          </div>

          <button type="submit" class="btn-auth-submit">
            <span>Update Password</span>
          </button>
        </form>
      </div>

      <div class="auth-footer">
        <a href="{{ route('login') }}">Return to Sign In</a>
      </div>
    </div>
  </main>
</body>
</html>
