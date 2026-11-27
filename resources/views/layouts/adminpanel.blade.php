<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'AdminPanel')</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
    rel="stylesheet"
  />

  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
  />

  @stack('head_styles')

  <!-- Shared Shell & Page Styles -->
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/dashboard.css') }}" />
  @stack('styles')
</head>
<body>
  @php
    $portalMode = trim((string) $__env->yieldContent('portal_mode', 'vendor'));
    $activeMenu = trim((string) $__env->yieldContent('active_menu', 'dashboard'));
    $currentUser = auth('web')->user();
    $providerRecord = $provider ?? null;
    $isVendorOperational = $portalMode === 'vendor'
      && $providerRecord
      && method_exists($providerRecord, 'isVerifiedAndActive')
      && $providerRecord->isVerifiedAndActive();
    $displayEmail = $currentUser?->email ?? ($portalMode === 'admin' ? 'superadmin@spaceworq.com' : 'partner@spaceworq.com');
    $displayRole = $portalMode === 'admin'
      ? 'Super Admin'
      : ($providerRecord?->owner_user_id === $currentUser?->id ? 'Provider Owner' : 'Provider Staff');
    $avatarChar = strtoupper(substr((string) ($currentUser?->first_name ?: $displayEmail), 0, 1)) ?: 'A';
    $layoutActive = in_array($activeMenu, ['templates', 'template-mapping'], true);
  @endphp

  <div class="admin-shell">
    <!-- Mobile Backdrop -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>

    <!-- Left Dark Sidebar -->
    <aside class="admin-sidebar" aria-label="Primary Sidebar Navigation">
      <!-- Brand Header Pill -->
      <div class="sidebar-brand-box">
        <a href="{{ $portalMode === 'admin' ? route('admin.dashboard') : ($isVendorOperational ? route('provider.dashboard') : route('provider.status')) }}" class="sidebar-brand-link">
          <span class="brand-avatar-badge" aria-hidden="true">{{ $portalMode === 'admin' ? 'S' : 'A' }}</span>
          <span class="sidebar-brand-name">{{ $portalMode === 'admin' ? 'SuperAdmin' : 'AdminPanel' }}</span>
        </a>
        <button
          type="button"
          id="sidebarCollapseBtn"
          class="sidebar-collapse-btn"
          aria-label="Collapse sidebar"
          title="Toggle sidebar"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="sidebar-nav">
        @if ($portalMode === 'admin')
          <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link {{ $activeMenu === 'dashboard' ? 'active' : '' }}" title="Dashboard">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <rect x="3" y="3" width="8" height="8" rx="1.5"></rect>
                <rect x="13" y="3" width="8" height="8" rx="1.5"></rect>
                <rect x="3" y="13" width="8" height="8" rx="1.5"></rect>
                <rect x="13" y="13" width="8" height="8" rx="1.5"></rect>
              </svg>
            </span>
            <span class="sidebar-nav-text">Dashboard</span>
          </a>

          <a href="{{ route('admin.providers.index') }}" class="sidebar-nav-link {{ $activeMenu === 'providers' ? 'active' : '' }}" title="Providers">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
              </svg>
            </span>
            <span class="sidebar-nav-text">Providers &amp; KYC</span>
          </a>

          <a href="{{ route('admin.workspaces.index') }}" class="sidebar-nav-link {{ $activeMenu === 'workspaces' ? 'active' : '' }}" title="Workspaces">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21h18"></path>
                <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                <path d="M9 7h2"></path>
                <path d="M9 11h2"></path>
                <path d="M9 15h2"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Workspaces</span>
          </a>

          <a href="{{ route('admin.customers.index') }}" class="sidebar-nav-link {{ $activeMenu === 'customers' ? 'active' : '' }}" title="Customers">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Customers</span>
          </a>

          <a href="{{ route('admin.consultations.index') }}" class="sidebar-nav-link {{ $activeMenu === 'consultations' ? 'active' : '' }}" title="Consultations">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Consultations</span>
          </a>

          <a href="{{ route('admin.partner-inquiries.index') }}" class="sidebar-nav-link {{ $activeMenu === 'partner-inquiries' ? 'active' : '' }}" title="Partner Inquiries">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1"></rect>
              </svg>
            </span>
            <span class="sidebar-nav-text">Partner Inquiries</span>
          </a>

          <a href="{{ route('admin.support-tickets.index') }}" class="sidebar-nav-link {{ $activeMenu === 'support-tickets' ? 'active' : '' }}" title="Support Tickets">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </span>
            <span class="sidebar-nav-text">Support Tickets</span>
          </a>

          <a href="{{ route('admin.reviews.index') }}" class="sidebar-nav-link {{ $activeMenu === 'reviews' ? 'active' : '' }}" title="Reviews">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
            </span>
            <span class="sidebar-nav-text">Reviews</span>
          </a>

          <a href="{{ route('admin.master-data.index') }}" class="sidebar-nav-link {{ $activeMenu === 'master-data' ? 'active' : '' }}" title="Master Data">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Master Data</span>
          </a>

          <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-nav-link {{ $activeMenu === 'audit-logs' ? 'active' : '' }}" title="Audit Logs">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Audit Logs</span>
          </a>
        @else
          @if ($isVendorOperational)
            <a href="{{ route('provider.dashboard') }}" class="sidebar-nav-link {{ $activeMenu === 'dashboard' ? 'active' : '' }}" title="Dashboard">
              <span class="sidebar-nav-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="3" y="3" width="8" height="8" rx="1.5"></rect>
                  <rect x="13" y="3" width="8" height="8" rx="1.5"></rect>
                  <rect x="3" y="13" width="8" height="8" rx="1.5"></rect>
                  <rect x="13" y="13" width="8" height="8" rx="1.5"></rect>
                </svg>
              </span>
              <span class="sidebar-nav-text">Dashboard</span>
            </a>

            <a href="{{ route('provider.workspaces.index') }}" class="sidebar-nav-link {{ $activeMenu === 'workspaces' ? 'active' : '' }}" title="Workspace Management">
              <span class="sidebar-nav-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21h18"></path>
                  <path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path>
                  <path d="M15 9h4a2 2 0 0 1 2 2v10"></path>
                  <path d="M9 7h2"></path>
                  <path d="M9 11h2"></path>
                  <path d="M9 15h2"></path>
                </svg>
              </span>
              <span class="sidebar-nav-text">Workspace Management</span>
            </a>

            <a href="{{ route('provider.users.index') }}" class="sidebar-nav-link {{ $activeMenu === 'users' ? 'active' : '' }}" title="Users">
              <span class="sidebar-nav-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                </svg>
              </span>
              <span class="sidebar-nav-text">Users</span>
            </a>

            <a href="{{ route('provider.roles.index') }}" class="sidebar-nav-link {{ $activeMenu === 'roles' ? 'active' : '' }}" title="Roles">
              <span class="sidebar-nav-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                  <path d="M12 2v20"></path>
                </svg>
              </span>
              <span class="sidebar-nav-text">Roles</span>
            </a>

            <!-- Collapsible Layout Designer Group -->
            <button
              type="button"
              class="sidebar-nav-link {{ $layoutActive ? 'active' : '' }}"
              data-bs-toggle="collapse"
              data-bs-target="#layoutDesignerSubmenu"
              aria-expanded="{{ $layoutActive ? 'true' : 'false' }}"
              aria-controls="layoutDesignerSubmenu"
              title="Layout Designer"
            >
              <span class="sidebar-nav-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
              </span>
              <span class="sidebar-nav-text">Layout Designer</span>
              <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <ul id="layoutDesignerSubmenu" class="sidebar-submenu collapse {{ $layoutActive ? 'show' : '' }}">
              <li><a href="{{ route('provider.templates.index') }}" class="sidebar-submenu-link {{ $activeMenu === 'templates' ? 'active' : '' }}">Template Management</a></li>
              <li><a href="{{ route('provider.template-mapping.index') }}" class="sidebar-submenu-link {{ $activeMenu === 'template-mapping' ? 'active' : '' }}">Template Mapping</a></li>
            </ul>
          @endif

          <a href="{{ route('provider.status') }}" class="sidebar-nav-link {{ $activeMenu === 'status' ? 'active' : '' }}" title="Application Status">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </span>
            <span class="sidebar-nav-text">Application Status</span>
          </a>

          <a href="{{ route('provider.profile.show') }}" class="sidebar-nav-link {{ $activeMenu === 'profile' ? 'active' : '' }}" title="Company Profile & KYC">
            <span class="sidebar-nav-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"></path>
              </svg>
            </span>
            <span class="sidebar-nav-text">Company Profile &amp; KYC</span>
          </a>
        @endif
      </nav>

      <!-- Bottom User Profile Card + Dropup Menu -->
      <div class="sidebar-footer dropup">
        <button
          type="button"
          class="sidebar-user-card"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="Account menu"
        >
          <div class="sidebar-user-meta">
            <span id="sidebarUserEmail" class="sidebar-user-email">{{ $displayEmail }}</span>
            <span id="sidebarUserRole" class="sidebar-user-role">{{ $displayRole }}</span>
          </div>
          <span id="sidebarUserAvatar" class="sidebar-user-avatar" aria-hidden="true">{{ $avatarChar }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end sidebar-user-menu mb-2">
          @if ($portalMode === 'vendor')
            <li>
              <a class="dropdown-item" href="{{ route('provider.profile.show') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="8" r="4"></circle>
                  <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"></path>
                </svg>
                Profile Settings &amp; KYC
              </a>
            </li>
            <li><hr class="dropdown-divider" /></li>
          @endif
          <li>
            <form method="POST" action="{{ $portalMode === 'admin' ? route('admin.logout') : route('provider.logout') }}" style="margin:0;">
              @csrf
              <button id="signOutBtn" type="submit" class="dropdown-item text-danger-soft w-100 text-start">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                  <polyline points="16 17 21 12 16 7"></polyline>
                  <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sign out
              </button>
            </form>
          </li>
        </ul>
      </div>
    </aside>

    <!-- Main Dashboard Column -->
    <div class="admin-main">
      <!-- Mobile Navigation Bar -->
      <header class="mobile-topbar">
        <a href="{{ $portalMode === 'admin' ? route('admin.dashboard') : ($isVendorOperational ? route('provider.dashboard') : route('provider.status')) }}" class="sidebar-brand-link">
          <span class="brand-avatar-badge" aria-hidden="true">{{ $portalMode === 'admin' ? 'S' : 'A' }}</span>
          <span class="sidebar-brand-name">{{ $portalMode === 'admin' ? 'SuperAdmin' : 'AdminPanel' }}</span>
        </a>
        <button
          type="button"
          id="mobileMenuToggle"
          class="mobile-menu-btn"
          aria-label="Open navigation menu"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
      </header>

      <!-- Main Dashboard Content -->
      <main class="dashboard-content">
        @include('partials.alerts')
        @yield('content')
      </main>

      <!-- Footer -->
      <footer class="admin-footer">
        <div>Copyright @ SpaceWorQ.Com</div>
      </footer>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
  ></script>

  <script>
    window.__SW_PORTAL__ = {
      csrfToken: @json(csrf_token()),
      portalMode: @json($portalMode),
      userEmail: @json($displayEmail),
      userName: @json($currentUser?->first_name ?: 'Admin'),
      userRole: @json($displayRole),
      avatarChar: @json($avatarChar)
    };
  </script>

  @stack('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const shell = document.querySelector('.admin-shell');
      const collapseBtn = document.getElementById('sidebarCollapseBtn');
      const mobileToggle = document.getElementById('mobileMenuToggle');
      const backdrop = document.getElementById('sidebarBackdrop');

      collapseBtn?.addEventListener('click', function () {
        shell?.classList.toggle('is-collapsed');
      });

      mobileToggle?.addEventListener('click', function () {
        shell?.classList.toggle('mobile-open');
      });

      backdrop?.addEventListener('click', function () {
        shell?.classList.remove('mobile-open');
      });
    });
  </script>
</body>
</html>
