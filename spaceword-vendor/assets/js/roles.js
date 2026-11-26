/**
 * AdminPanel — Roles Page State & Role Definitions Manager
 */
(function () {
  "use strict";

  const WORKSPACE_LABELS = {
    "self-registered": "Self Registered",
    adminpanel: "AdminPanel",
    "system-admin": "System Admin Workspace",
  };

  const ROLES_BY_WORKSPACE = {
    "self-registered": [
      {
        id: "r-1",
        name: "Workspace Manager",
        description: "Full control over workspaces, floor plans, pricing, and bookings.",
        permissions: ["Manage Workspaces", "Manage Users", "Approve Bookings", "View Reports"],
      },
      {
        id: "r-2",
        name: "Operations Lead",
        description: "Handles day-to-day floor operations, check-ins, and reserve requests.",
        permissions: ["Approve Bookings", "Layout Designer", "View Reports"],
      },
    ],
    adminpanel: [
      {
        id: "r-3",
        name: "Provider Admin",
        description: "Manages all provider workspaces, staff roles, and billing configuration.",
        permissions: [
          "Manage Workspaces",
          "Manage Users",
          "Manage Roles",
          "Approve Bookings",
          "View Reports",
          "Layout Designer",
        ],
      },
      {
        id: "r-4",
        name: "Community Lead",
        description: "Coordinates member onboarding, instant bookings, and front desk support.",
        permissions: ["Manage Users", "Approve Bookings"],
      },
    ],
    "system-admin": [
      {
        id: "r-5",
        name: "System Administrator",
        description: "Unrestricted portal access across all provider workspaces and settings.",
        permissions: [
          "Manage Workspaces",
          "Manage Users",
          "Manage Roles",
          "Approve Bookings",
          "View Reports",
          "Layout Designer",
        ],
      },
    ],
  };

  let activeWorkspaceKey = "";

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function loadSelectedWorkspace() {
    try {
      const saved = window.localStorage.getItem("adminpanel_selected_workspace");
      if (saved && WORKSPACE_LABELS[saved]) {
        activeWorkspaceKey = saved;
      }
    } catch (_) {
      activeWorkspaceKey = "";
    }
  }

  function renderRolesCard() {
    const card = document.getElementById("rolesContentCard");
    if (!card) return;

    if (!activeWorkspaceKey || !WORKSPACE_LABELS[activeWorkspaceKey]) {
      card.innerHTML =
        '<p class="roles-empty-prompt">Select a workspace to manage roles.</p>';
      return;
    }

    const wsName = WORKSPACE_LABELS[activeWorkspaceKey];
    const roles = ROLES_BY_WORKSPACE[activeWorkspaceKey] || [];

    const cardsHtml = roles
      .map(function (role) {
        const permChips = role.permissions
          .map(function (perm) {
            return `<span class="role-perm-chip">${escapeHtml(perm)}</span>`;
          })
          .join("");
        const safeId = escapeHtml(role.id);

        return `
          <article class="role-definition-card">
            <div class="role-card-top">
              <div>
                <h3 class="role-card-name">${escapeHtml(role.name)}</h3>
                <p class="role-card-desc">${escapeHtml(role.description)}</p>
              </div>
              <div class="role-card-actions">
                <button
                  type="button"
                  class="role-icon-btn btn-delete"
                  data-delete-role="${safeId}"
                  title="Delete role"
                  aria-label="Delete ${escapeHtml(role.name)}"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                  </svg>
                </button>
              </div>
            </div>
            <div class="role-perm-list">${permChips}</div>
          </article>`;
      })
      .join("");

    card.innerHTML = `
      <div class="roles-workspace-bar">
        <div class="roles-workspace-meta">
          <h2 class="roles-workspace-heading">${escapeHtml(wsName)} Roles</h2>
          <span class="roles-workspace-badge">${roles.length} ${roles.length === 1 ? "role" : "roles"}</span>
        </div>
        <button
          type="button"
          class="btn-roles-primary"
          data-bs-toggle="modal"
          data-bs-target="#newRoleModal"
        >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>Create Role</span>
        </button>
      </div>
      ${
        roles.length
          ? `<div class="roles-grid">${cardsHtml}</div>`
          : '<p class="roles-empty-prompt">No roles configured for this workspace yet.</p>'
      }`;
  }

  function initRoleActions() {
    const card = document.getElementById("rolesContentCard");
    const form = document.getElementById("newRoleForm");
    const modalEl = document.getElementById("newRoleModal");

    card?.addEventListener("click", function (event) {
      const delBtn = event.target.closest("[data-delete-role]");
      if (!delBtn || !activeWorkspaceKey) return;
      const roleId = delBtn.getAttribute("data-delete-role");
      ROLES_BY_WORKSPACE[activeWorkspaceKey] = (
        ROLES_BY_WORKSPACE[activeWorkspaceKey] || []
      ).filter(function (role) {
        return role.id !== roleId;
      });
      renderRolesCard();
    });

    form?.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!activeWorkspaceKey) return;
      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        return;
      }

      const name = document.getElementById("roleNameInput").value.trim();
      const description = document.getElementById("roleDescInput").value.trim();
      const permissions = Array.from(
        form.querySelectorAll('input[name="rolePermissions"]:checked')
      ).map(function (cb) {
        return cb.value;
      });

      if (!ROLES_BY_WORKSPACE[activeWorkspaceKey]) {
        ROLES_BY_WORKSPACE[activeWorkspaceKey] = [];
      }

      ROLES_BY_WORKSPACE[activeWorkspaceKey].unshift({
        id: `r-${Date.now()}`,
        name: name,
        description: description || "Custom provider role.",
        permissions: permissions.length ? permissions : ["View Reports"],
      });

      form.reset();
      form.classList.remove("was-validated");
      renderRolesCard();
      window.bootstrap?.Modal.getOrCreateInstance(modalEl).hide();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadSelectedWorkspace();
    renderRolesCard();
    initRoleActions();
  });
})();
