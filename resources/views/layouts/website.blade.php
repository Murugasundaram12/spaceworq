<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'SpaceWorq - Find a Workspace | Coworking, Day Pass & Enterprise Offices')</title>
  <meta name="description" content="@yield('meta_description', 'Find the perfect coworking space, day pass, meeting room, virtual office or enterprise leased office in Bangalore, Chennai, Hyderabad & across India.')" />
  <meta name="keywords" content="@yield('meta_keywords', 'coworking space, workspace, day pass, meeting rooms, virtual office, managed office, bangalore, chennai, hyderabad, spaceworq')" />

  <!-- Open Graph -->
  <meta property="og:title" content="@yield('og_title', 'SpaceWorq - Find a Workspace')" />
  <meta property="og:description" content="@yield('og_description', 'Coworking Spaces, Day Passes, Meeting Rooms & Enterprise Offices tailored to your team\'s rhythm.')" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ url()->current() }}" />

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}" />

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Stylesheets -->
  <link rel="stylesheet" href="{{ asset('website/css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('website/css/responsive.css') }}" />
  @stack('styles')
</head>
<body class="@yield('body_class')">
  @php
    $webUser = auth('web')->user();
    $currentRedirect = request()->getRequestUri();
    $accountRoute = route('customer.profile.show');
    if ($webUser) {
      if ($webUser->isSuperAdmin()) {
        $accountRoute = route('admin.dashboard');
      } elseif ($webUser->isProviderAccount()) {
        $accountRoute = route('provider.dashboard');
      }
    }
    $activeNav = trim((string) $__env->yieldContent('active_nav', 'find'));
    $partnerPage = trim((string) $__env->yieldContent('active_partner', ''));
  @endphp

  <!-- ==========================================================================
       Header & Navigation (Exact SpaceWorq Design)
       ========================================================================== -->
  <header class="site-header" id="siteHeader">
    <div class="container nav-container">
      <!-- Logo + Tagline -->
      <a href="{{ route('home') }}" class="brand-logo-wrap" id="brandLogo">
        <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq" class="brand-logo-img" />
        <div class="brand-text-block">
          <div class="brand-name-main">SpaceWorq</div>
          <div class="brand-tagline">You are in the right place</div>
        </div>
      </a>

      <!-- Center Desktop Nav Links -->
      <nav aria-label="Main Navigation">
        <ul class="nav-menu" id="desktopNavMenu">
          <li>
            <a href="{{ route('home') }}" class="nav-link {{ $activeNav === 'find' ? 'active' : '' }}" id="navFindWorkspace">
              Find a Workspace
            </a>
          </li>
          <li>
            <a href="{{ route('website.expansion') }}" class="nav-link {{ $activeNav === 'expansion' ? 'active' : '' }}" id="navExpansion">
              Expansion Opportunities
            </a>
          </li>
          <li class="nav-dropdown-item {{ $activeNav === 'partner' ? 'open' : '' }}" id="partnerDropdownItem">
            <button class="nav-link nav-dropdown-btn {{ $activeNav === 'partner' ? 'active' : '' }}" id="partnerDropdownBtn" type="button" aria-expanded="{{ $activeNav === 'partner' ? 'true' : 'false' }}" aria-haspopup="true">
              <span>Partner With Us</span>
              <svg class="chevron-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Partner With Us Dropdown Menu -->
            <div class="partner-dropdown-menu" id="partnerDropdownMenu">
              <a href="{{ route('website.workspace-providers') }}" class="partner-menu-link {{ $partnerPage === 'workspace-providers' ? 'active-page' : '' }}" id="linkProviders" @if($partnerPage === 'workspace-providers') style="background:#F0FBFE; border-left: 3px solid var(--primary);" @endif>
                <div class="partner-item-title" @if($partnerPage === 'workspace-providers') style="color:var(--primary);" @endif>For Workspace Providers</div>
                <div class="partner-item-sub">List &amp; manage your workspace inventory</div>
              </a>
              <a href="{{ route('website.building-owners') }}" class="partner-menu-link {{ $partnerPage === 'building-owners' ? 'active-page' : '' }}" id="linkOwners" @if($partnerPage === 'building-owners') style="background:#F0FBFE; border-left: 3px solid var(--primary);" @endif>
                <div class="partner-item-title" @if($partnerPage === 'building-owners') style="color:var(--primary);" @endif>For Building Owners</div>
                <div class="partner-item-sub">List your building for workspace operators</div>
              </a>
              <a href="{{ route('website.developers') }}" class="partner-menu-link {{ $partnerPage === 'developers' ? 'active-page' : '' }}" id="linkDevelopers" @if($partnerPage === 'developers') style="background:#F0FBFE; border-left: 3px solid var(--primary);" @endif>
                <div class="partner-item-title" @if($partnerPage === 'developers') style="color:var(--primary);" @endif>For Developers</div>
                <div class="partner-item-sub">Showcase upcoming commercial projects</div>
              </a>
            </div>
          </li>
        </ul>
      </nav>

      <!-- Right Nav Actions -->
      <div class="nav-actions">
        @if ($webUser)
          <a href="{{ $accountRoute }}" class="nav-signin-btn" style="text-decoration:none; display:inline-flex; align-items:center;">
            {{ $webUser->first_name ?: 'Account' }}
          </a>
          <form method="POST" action="{{ route('logout') }}" style="display:inline; margin:0;">
            @csrf
            <button type="submit" class="btn-get-started" style="cursor:pointer;">Sign Out</button>
          </form>
        @else
          <a href="{{ route('login', ['redirectTo' => $currentRedirect]) }}" class="nav-signin-btn" id="btnHeaderSignIn" style="text-decoration:none; display:inline-flex; align-items:center;">
            Sign In
          </a>
          @hasSection('header_cta')
            @yield('header_cta')
          @else
            <a href="{{ route('register', ['redirectTo' => $currentRedirect]) }}" class="btn-get-started" id="btnHeaderGetStarted" style="text-decoration:none; display:inline-flex; align-items:center;">
              Get Started
            </a>
          @endif
        @endif
        <button class="profile-avatar-btn {{ $webUser ? '' : 'open-auth-btn' }}" id="btnHeaderAvatar" aria-label="User Profile" @if($webUser) onclick="window.location.href='{{ $accountRoute }}'" @endif>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </button>

        <!-- Mobile Hamburger Button -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle navigation menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Drawer & Backdrop -->
  <div class="drawer-backdrop" id="drawerBackdrop"></div>
  <aside class="mobile-nav-drawer" id="mobileDrawer">
    <div class="drawer-header">
      <div class="brand-logo-wrap">
        <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq Logo" style="height: 38px;" />
        <div class="brand-text-block">
          <div class="brand-name-main">SpaceWorq</div>
          <div class="brand-tagline">You are in the right place</div>
        </div>
      </div>
      <button class="modal-close-btn" id="closeDrawerBtn" aria-label="Close menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="drawer-body">
      <ul class="drawer-nav-list">
        <li>
          <a href="{{ route('home') }}" class="drawer-link {{ $activeNav === 'find' ? 'active' : '' }}" id="drawerFindWorkspace">
            <span>Find a Workspace</span>
          </a>
        </li>
        <li>
          <a href="{{ route('website.expansion') }}" class="drawer-link {{ $activeNav === 'expansion' ? 'active' : '' }}" id="drawerExpansion">
            <span>Expansion Opportunities</span>
          </a>
        </li>
        <li class="drawer-dropdown-section {{ $activeNav === 'partner' ? 'open' : '' }}">
          <button class="drawer-accordion-btn" id="drawerPartnerBtn" type="button">
            <span>Partner With Us</span>
            <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="drawer-accordion-content" id="drawerPartnerContent">
            <a href="{{ route('website.workspace-providers') }}" class="drawer-sublink" @if($partnerPage === 'workspace-providers') style="background:#E2F1F0; border-left: 3px solid var(--primary);" @endif>
              <strong @if($partnerPage === 'workspace-providers') style="color:var(--primary);" @endif>For Workspace Providers</strong>
              <small>List &amp; manage your workspace inventory</small>
            </a>
            <a href="{{ route('website.building-owners') }}" class="drawer-sublink" @if($partnerPage === 'building-owners') style="background:#E2F1F0; border-left: 3px solid var(--primary);" @endif>
              <strong @if($partnerPage === 'building-owners') style="color:var(--primary);" @endif>For Building Owners</strong>
              <small>List your building for workspace operators</small>
            </a>
            <a href="{{ route('website.developers') }}" class="drawer-sublink" @if($partnerPage === 'developers') style="background:#E2F1F0; border-left: 3px solid var(--primary);" @endif>
              <strong @if($partnerPage === 'developers') style="color:var(--primary);" @endif>For Developers</strong>
              <small>Showcase upcoming commercial projects</small>
            </a>
          </div>
        </li>
      </ul>

      <div class="drawer-actions">
        @if ($webUser)
          <a href="{{ $accountRoute }}" class="btn-get-started" style="width: 100%; justify-content: center; text-decoration:none; display:inline-flex;">
            <span>My Account ({{ $webUser->first_name }})</span>
          </a>
          <form method="POST" action="{{ route('logout') }}" style="width:100%;">
            @csrf
            <button type="submit" class="nav-signin-btn" style="width: 100%; justify-content: center; text-align: center; border: 1px solid var(--border-color); padding: 9px 0; background:#fff;">
              <span>Sign Out</span>
            </button>
          </form>
        @else
          @hasSection('drawer_cta')
            @yield('drawer_cta')
          @else
            <a href="{{ route('register', ['redirectTo' => $currentRedirect]) }}" class="btn-get-started" style="width: 100%; justify-content: center; text-decoration:none; display:inline-flex;">
              <span>Get Started</span>
            </a>
          @endif
          <a href="{{ route('login', ['redirectTo' => $currentRedirect]) }}" class="nav-signin-btn" style="width: 100%; justify-content: center; text-align: center; border: 1px solid var(--border-color); padding: 9px 0; text-decoration:none; display:inline-flex;">
            <span>Sign In</span>
          </a>
        @endif
      </div>
    </div>
  </aside>

  @if (session('status') || session('error') || session('status_error') || $errors->any())
    <div class="container" style="padding-top: 16px;">
      @include('partials.alerts')
    </div>
  @endif

  @yield('content')

  <!-- Auth Sign-In OTP Modal -->
  <div class="modal-overlay" id="authModal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
    <div class="modal-content" style="max-width: 440px;">
      <div class="modal-header">
        <h3 class="modal-title" id="authModalTitle">Sign in or Create Account</h3>
        <button class="modal-close-btn" aria-label="Close modal" type="button">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <form id="authFormStep1" method="POST" action="{{ route('customer.otp.send') }}">
          @csrf
          <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 18px;">
            Unlock saved spaces, instant booking history, and priority support with a quick OTP.
          </p>
          <div class="form-group">
            <label class="form-label" for="authContactInput">Email Address or Mobile Number</label>
            <input type="text" name="identifier" class="form-control" id="authContactInput" placeholder="e.g., name@company.com or 9876543210" required />
          </div>
          <button type="submit" class="modal-submit-btn">Get Verification Code</button>
          <div style="text-align:center; margin-top:14px; font-size:0.84rem; color:var(--text-muted);">
            Prefer password sign in? <a href="{{ route('login', ['redirectTo' => $currentRedirect]) }}" style="color:var(--primary); font-weight:600;">Use Login Page</a>
          </div>
        </form>

        <form id="authFormStep2" style="display: none;" method="POST" action="{{ route('customer.otp.verify') }}">
          @csrf
          <input type="hidden" id="otpVerifyIdentifier" name="identifier" value="" />
          <input type="hidden" name="redirect_to" value="{{ $currentRedirect }}" />
          <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
            Enter the 6-digit code sent to <strong id="authTargetEmail"></strong>.
          </p>
          <div class="form-group">
            <label class="form-label" for="otpInput">6-Digit OTP</label>
            <input type="text" name="otp" class="form-control" id="otpInput" placeholder="••••••" maxlength="6" style="text-align: center; font-size: 1.25rem; letter-spacing: 6px; font-weight: 700;" />
          </div>
          <button type="button" class="modal-submit-btn" id="verifyOtpBtn">Verify &amp; Sign In</button>
          <button type="button" style="width: 100%; padding: 10px; font-size: 0.85rem; color: var(--primary); font-weight: 600; margin-top: 8px; background:none; border:none; cursor:pointer;" id="changeContactBtn">Change Email / Mobile</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- ==========================================================================
       Footer Bar
       ========================================================================== -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-top-row">
        <div class="footer-copyright">
          <img src="{{ asset('assets/images/logo.png') }}" alt="SpaceWorq" style="height: 28px;" />
          <span>Copyright @ SpaceWorQ.Com</span>
        </div>
        <ul class="footer-nav">
          <li><a href="{{ route('home') }}#howItWorks">About Us</a></li>
          <li><a href="{{ route('home') }}#consultationBanner">Contact Us</a></li>
          <li><a href="{{ route('home') }}">Privacy Policy</a></li>
          <li><a href="{{ route('home') }}">Terms</a></li>
          <li><a href="{{ route('home') }}#consultationBanner">Support</a></li>
        </ul>
      </div>

      <div class="footer-bottom-row">
        <span>SpaceWorq v1.0.0 • Made in India 🇮🇳</span>
        <div class="footer-badges">
          <span>🔒 256-bit SSL encrypted</span>
          <span>🛡️ PCI DSS compliant</span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Floating Instant Booking Button & Login Required Modal -->
  <button class="floating-instant-book-btn" id="floatingInstantBookBtn" type="button" aria-label="Instant Booking" title="Instant Booking">
    <svg class="instant-book-icon" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
      <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
    </svg>
    <span>Instant Booking</span>
  </button>

  <div class="modal-overlay auth-req-modal-overlay" id="authRequiredModal" role="dialog" aria-modal="true" aria-labelledby="authReqTitle">
    <div class="auth-req-card">
      <h3 class="auth-req-title" id="authReqTitle">Login Required</h3>
      <p class="auth-req-sub">Please login or signup to continue.</p>
      <div class="auth-req-actions">
        <button type="button" class="btn-auth-cancel" id="btnAuthReqCancel">Cancel</button>
        <a href="{{ route('register', ['redirectTo' => $currentRedirect]) }}" class="btn-auth-signup" id="btnAuthReqSignup">Signup</a>
        <a href="{{ route('login', ['redirectTo' => $currentRedirect]) }}" class="btn-auth-login" id="btnAuthReqLogin">Login</a>
      </div>
    </div>
  </div>

  @php
    $__websiteJsConfig = [
      'csrfToken' => csrf_token(),
      'assetBase' => asset(''),
      'isAuthenticated' => (bool) $webUser,
      'user' => $webUser ? [
        'id' => $webUser->id,
        'name' => trim(($webUser->first_name ?? '') . ' ' . ($webUser->last_name ?? '')),
        'email' => $webUser->email,
        'user_type' => $webUser->user_type,
      ] : null,
      'routes' => [
        'home' => route('home'),
        'login' => route('login'),
        'register' => route('register'),
        'consultationStore' => route('website.consultations.store'),
        'partnerInquiryStore' => route('website.partner-inquiries.store'),
        'otpSend' => route('customer.otp.send'),
        'otpVerify' => route('customer.otp.verify'),
      ],
    ];
  @endphp
  <script>
    window.__SPACEWORQ_CONFIG__ = @json($__websiteJsConfig);
  </script>
  @stack('scripts')
</body>
</html>
