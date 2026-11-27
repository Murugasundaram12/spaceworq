@extends('layouts.adminpanel')

@section('title', 'Roles — Partner Panel')
@section('portal_mode', 'vendor')
@section('active_menu', 'roles')

@push('styles')
  <link rel="stylesheet" href="{{ asset('vendor-panel/css/roles.css') }}" />
@endpush

@section('content')
  <header class="roles-page-header">
    <h1 class="roles-page-title">Roles</h1>
    <p class="roles-page-subtitle">
      Manage provider role definitions and the permissions assigned to each role.
    </p>
  </header>

        <section id="rolesContentCard" class="roles-surface-card" aria-label="Role definitions">
          <p class="roles-empty-prompt">Select a workspace to manage roles.</p>
        </section>
  </div>

  <!-- Create Role Modal (available when a workspace is selected) -->
  <div class="modal fade roles-modal" id="newRoleModal" tabindex="-1" aria-labelledby="newRoleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="newRoleModalLabel">Create Role</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="newRoleForm" method="POST" action="{{ route('provider.roles.store') }}" novalidate>
            @csrf
            <div class="mb-3">
              <label for="roleNameInput" class="form-label fw-semibold small">Role Name</label>
              <input type="text" id="roleNameInput" name="name" class="form-control" placeholder="e.g. Floor Coordinator" required />
            </div>
            <div class="mb-3">
              <label for="roleDescInput" class="form-label fw-semibold small">Description</label>
              <textarea id="roleDescInput" name="description" class="form-control" rows="2" placeholder="Briefly describe this role's responsibilities"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small d-block mb-2">Permissions</label>
              <div class="roles-perm-checks">
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions"
                    value="Manage Workspaces" /><span>Manage Workspaces</span></label>
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions"
                    value="Manage Users" /><span>Manage Users</span></label>
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions"
                    value="Manage Roles" /><span>Manage Roles</span></label>
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions"
                    value="Approve Bookings" /><span>Approve Bookings</span></label>
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions" value="View Reports"
                    checked /><span>View Reports</span></label>
                <label class="roles-perm-check-label"><input type="checkbox" name="rolePermissions"
                    value="Layout Designer" /><span>Layout Designer</span></label>
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn-roles-primary">Save Role</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @php
    $__rolesBoot = [
      'mode' => 'server',
      'roles' => $roles->map(fn($r) => [
        'id' => (string) $r->id,
        'workspace_id' => $r->workspace_id,
        'name' => $r->name,
        'description' => $r->description,
        'permissions' => $r->permissions ?? [],
        'is_system' => (bool) $r->is_system,
      ])->values(),
      'routes' => [
        'store' => route('provider.roles.store'),
        'updateTemplate' => route('provider.roles.update', ['role' => '__ID__']),
        'destroyTemplate' => route('provider.roles.destroy', ['role' => '__ID__']),
      ],
    ];
  @endphp
  <script>
    window.__SW_ROLES_DATA__ = @json($__rolesBoot);
  </script>
  <script src="{{ asset('vendor-panel/js/roles.js') }}"></script>
@endpush