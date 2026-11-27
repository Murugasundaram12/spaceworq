<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Forgot Password — Partner Panel</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/auth.css') }}" />
</head>
<body>
  <main class="auth-wrapper">
    <header class="mobile-brand-header">
      <a href="{{ route('provider.login') }}" class="brand-logo" aria-label="AdminPanel Home">
        <svg class="brand-logo-icon" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" />
          <rect x="20" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
          <rect x="3" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
          <rect x="20" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" />
        </svg>
        <span class="brand-logo-text">AdminPanel</span>
      </a>
    </header>

    <section class="auth-brand-panel" aria-label="AdminPanel platform highlights">
      <div class="auth-brand-content">
        <a href="{{ route('provider.login') }}" class="brand-logo" aria-label="AdminPanel Home">
          <svg class="brand-logo-icon" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="3" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" />
            <rect x="20" y="3" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
            <rect x="3" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" fill-opacity="0.72" />
            <rect x="20" y="20" width="13" height="13" rx="3.5" fill="#FFFFFF" />
          </svg>
          <span class="brand-logo-text">AdminPanel</span>
        </a>
        <h1 class="brand-heading">Manage your<br />workspace with<br />confidence</h1>
        <p class="brand-description">Recover access to your partner account securely using your registered business email address.</p>
      </div>
    </section>

    <section class="auth-form-panel" aria-label="Password recovery">
      <div class="auth-form-container">
        <h1 class="auth-title">Forgot Password</h1>
        <p class="auth-subtitle">Enter your email address and we will send you a password reset link.</p>

        @include('partials.alerts')

        <form id="providerForgotForm" method="POST" action="{{ route('provider.password.email') }}" novalidate>
          @csrf
          <div class="sw-field">
            <input
              type="email"
              id="providerForgotEmail"
              name="email"
              value="{{ old('email') }}"
              class="sw-input @error('email') is-invalid @enderror"
              placeholder=" "
              autocomplete="email"
              required
            />
            <label for="providerForgotEmail" class="sw-label">Email address</label>
          </div>

          <button type="submit" class="btn-sw-primary">Send Reset Link</button>
        </form>

        <div class="auth-alt-prompt mt-4">
          Remembered your password?
          <a href="{{ route('provider.login') }}" class="auth-inline-link">Sign in here</a>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
