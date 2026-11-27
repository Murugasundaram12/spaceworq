@extends('layouts.adminpanel')

@section('title', 'Users — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'users')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/users.css') }}" />
@endpush

@section('content')
  <!-- Page Header -->
  <header class="users-page-header">
    <h1 class="users-page-title">Users</h1>
    <p class="users-page-subtitle">
      Select a workspace to manage users in that workspace.
    </p>
  </header>

        <!-- Workspace Selector + Register New User Bar -->
        <section class="users-toolbar-card" aria-label="Workspace selector and user registration">
          <div class="users-workspace-picker workspace-filter-wrap" id="usersWorkspaceDropdown">
            <span class="workspace-filter-label" id="usersWorkspaceLabel">Workspace</span>
            <button
              type="button"
              id="usersWorkspaceToggle"
              class="workspace-filter-trigger"
              aria-haspopup="listbox"
              aria-expanded="false"
              aria-labelledby="usersWorkspaceLabel usersWorkspaceValue"
            >
              <span id="usersWorkspaceValue">Workspace</span>
              <svg class="workspace-filter-caret" width="12" height="12" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                <path d="M2.5 4.25L6 8l3.5-3.75H2.5z"></path>
              </svg>
            </button>
            <ul id="usersWorkspaceMenu" class="workspace-filter-menu" role="listbox" aria-labelledby="usersWorkspaceLabel">
              <li role="option" class="workspace-filter-option active" data-value="" aria-selected="true" tabindex="0">Workspace</li>
              <li role="option" class="workspace-filter-option" data-value="all" aria-selected="false" tabindex="0">All Workspaces</li>
              @foreach ($workspaces as $ws)
                <li role="option" class="workspace-filter-option" data-value="{{ $ws->id }}" aria-selected="false" tabindex="0">{{ $ws->name }}</li>
              @endforeach
            </ul>
          </div>

    <button type="button" id="registerNewUserBtn" class="btn-register-user" data-bs-toggle="modal"
      data-bs-target="#registerUserModal" disabled>
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Register New User</span>
    </button>
  </section>

  <!-- Workspace Users Card -->
  <section class="users-list-card" aria-label="Workspace Users">
    <div class="users-list-header">
      <h2 class="users-list-title">Workspace Users</h2>
      <span id="usersCountPill" class="users-count-pill"></span>
    </div>

          <div id="workspaceUsersContent">
            <p class="users-empty-msg">Select a workspace to view existing users.</p>
          </div>
        </section>
  </div>

  <!-- Register / Edit User Modal -->
  <div class="modal fade users-modal" id="registerUserModal" tabindex="-1" aria-labelledby="registerUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h2 class="modal-title" id="registerUserModalLabel">Register New User</h2>
            <div class="small text-muted">Workspace: <strong id="registerModalWorkspaceName"></strong></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="registerUserForm" method="POST" action="{{ route('provider.users.store') }}" novalidate>
            @csrf
            <input type="hidden" id="editUserId" value="" />
            <div class="row g-3">
              <div class="col-sm-6">
                <label for="userFirstName" class="form-label">First Name</label>
                <input type="text" id="userFirstName" name="first_name" class="form-control" placeholder="e.g. Arjun" required />
              </div>
              <div class="col-sm-6">
                <label for="userLastName" class="form-label">Last Name</label>
                <input type="text" id="userLastName" name="last_name" class="form-control" placeholder="e.g. Nair" required />
              </div>
              <div class="col-12">
                <label for="userEmail" class="form-label">Email Address</label>
                <input type="email" id="userEmail" name="email" class="form-control" placeholder="user@adminpanel.com" required />
              </div>
              <div class="col-12">
                <label for="userMobile" class="form-label">Mobile Number</label>
                <input type="tel" id="userMobile" name="mobile" class="form-control" placeholder="+91 98400 00000" required />
              </div>
              <div class="col-sm-6">
                <label for="userRole" class="form-label">Role</label>
                <select id="userRole" name="provider_role_id" class="form-select" required>
                  @foreach ($roles as $role)
                    <option value="{{ $role->id }}" data-name="{{ $role->name }}">{{ $role->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-6">
                <label for="userStatus" class="form-label">Status</label>
                <select id="userStatus" name="status" class="form-select" required>
                  <option value="ACTIVE" selected>ACTIVE</option>
                  <option value="INACTIVE">INACTIVE</option>
                </select>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" id="registerUserSubmitBtn" class="btn btn-primary px-4 fw-semibold"
                style="background-color: #5246e5; border-color: #5246e5;">
                Register User
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete User Confirmation Modal -->
  <div class="modal fade users-modal" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="deleteUserModalLabel">Remove User</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-4">
            Are you sure you want to remove <strong id="deleteUserName" class="text-dark"></strong> from this workspace?
          </p>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="button" id="confirmDeleteUserBtn" class="btn btn-danger px-3 fw-semibold">Remove</button>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    @php
      $__staffPayload = $staff->map(fn($s) => [
        'id' => (string) $s->id,
        'workspace_id' => $s->workspace_id ? (string) $s->workspace_id : 'all',
        'firstName' => $s->user?->first_name ?? '',
        'lastName' => $s->user?->last_name ?? '',
        'email' => $s->user?->email ?? '',
        'mobile' => $s->user?->mobile ?? '',
        'provider_role_id' => $s->provider_role_id,
        'role' => $s->providerRole?->name ?? ($s->role_name ?: 'Staff'),
        'status' => $s->status,
      ])->values();
      $__usersBoot = [
        'mode' => 'server',
        'workspaces' => $workspaces->map(fn($w) => ['id' => (string) $w->id, 'name' => $w->name])->values(),
        'roles' => $roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values(),
        'staff' => $__staffPayload,
        'routes' => [
          'store' => route('provider.users.store'),
          'updateTemplate' => route('provider.users.update', ['staff' => '__ID__']),
          'destroyTemplate' => route('provider.users.destroy', ['staff' => '__ID__']),
        ],
      ];
    @endphp
    window.__SW_USERS_DATA__ = @json($__usersBoot);
  </script>
  <script src="{{ asset('vendor-panel/js/users.js') }}"></script>
@endpush