/**
 * AdminPanel — Roles Page State & Role Definitions Manager
 */
(function () {
  "use strict";

  const SERVER = window.__SW_ROLES_DATA__ || null;
  const IS_SERVER = Boolean(SERVER && SERVER.mode === "server");
  const CSRF_TOKEN = window.__SW_PORTAL__?.csrfToken || "";

  let rolesList = IS_SERVER ? (SERVER.roles || []).slice() : [];

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function renderRolesCard() {
    const card = document.getElementById("rolesContentCard");
    if (!card) return;

    const cardsHtml = rolesList
      .map(function (role) {
        const permChips = (role.permissions || [])
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
                <p class="role-card-desc">${escapeHtml(role.description || "Custom provider role.")}</p>
              </div>
              <div class="role-card-actions">
                ${role.is_system ? "" : `<button
                  type="button"
                  class="role-icon-btn btn-delete"
                  data-delete-role="${safeId}"
                  title="Delete role"
                  aria-label="Delete ${escapeHtml(role.name)}"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                  </svg>
                </button>`}
              </div>
            </div>
            <div class="role-perm-list">${permChips}</div>
          </article>`;
      })
      .join("");

    card.innerHTML = `
      <div class="roles-workspace-bar">
        <div class="roles-workspace-meta">
          <h2 class="roles-workspace-heading">Partner Roles &amp; Permissions</h2>
          <span class="roles-workspace-badge">${rolesList.length} ${rolesList.length === 1 ? "role" : "roles"}</span>
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
        rolesList.length
          ? `<div class="roles-grid">${cardsHtml}</div>`
          : '<p class="roles-empty-prompt">No roles configured yet.</p>'
      }`;
  }

  function initRoleActions() {
    const card = document.getElementById("rolesContentCard");
    const form = document.getElementById("newRoleForm");

    card?.addEventListener("click", async function (event) {
      const delBtn = event.target.closest("[data-delete-role]");
      if (!delBtn || !IS_SERVER) return;
      const roleId = delBtn.getAttribute("data-delete-role");
      const resp = await fetch(
        SERVER.routes.destroyTemplate.replace("__ID__", roleId),
        {
          method: "DELETE",
          headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": CSRF_TOKEN,
          },
        }
      );
      if (resp.ok) {
        window.location.reload();
      }
    });

    form?.addEventListener("submit", async function (event) {
      event.preventDefault();
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

      if (IS_SERVER) {
        const resp = await fetch(SERVER.routes.store, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": CSRF_TOKEN,
          },
          body: JSON.stringify({
            name: name,
            description: description || "Custom provider role.",
            permissions: permissions.length ? permissions : ["View Reports"],
          }),
        });
        const data = await resp.json();
        if (!resp.ok) {
          window.alert(data.message || "Unable to create role.");
          return;
        }
        window.location.reload();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    renderRolesCard();
    initRoleActions();
  });
})();
