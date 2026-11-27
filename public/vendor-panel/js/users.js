/**
 * AdminPanel — Users Page Interactions (Workspace Selector, User Directory, Register/Edit/Delete)
 */
(function () {
  "use strict";

  const SERVER = window.__SW_USERS_DATA__ || null;
  const IS_SERVER = Boolean(SERVER && SERVER.mode === "server");
  const CSRF_TOKEN = window.__SW_PORTAL__?.csrfToken || "";

  let WORKSPACE_USERS = {};
  let selectedWorkspaceId = "";
  let selectedWorkspaceName = "";
  let pendingDeleteUserId = null;

  function bootstrapServerData() {
    if (!IS_SERVER) return;
    WORKSPACE_USERS = { all: [] };
    (SERVER.workspaces || []).forEach(function (ws) {
      WORKSPACE_USERS[String(ws.id)] = [];
    });
    (SERVER.staff || []).forEach(function (item) {
      const wsKey = item.workspace_id ? String(item.workspace_id) : "all";
      if (!WORKSPACE_USERS[wsKey]) WORKSPACE_USERS[wsKey] = [];
      WORKSPACE_USERS[wsKey].push(item);
      if (wsKey !== "all") {
        WORKSPACE_USERS.all.push(item);
      }
    });
    if ((SERVER.workspaces || []).length > 0) {
      selectedWorkspaceId = String(SERVER.workspaces[0].id);
      selectedWorkspaceName = SERVER.workspaces[0].name;
    } else {
      selectedWorkspaceId = "all";
      selectedWorkspaceName = "All Workspaces";
    }
    const valueEl = document.getElementById("usersWorkspaceValue");
    const wrapper = document.getElementById("usersWorkspaceDropdown");
    if (valueEl) valueEl.textContent = selectedWorkspaceName;
    wrapper?.classList.add("has-value");
    wrapper?.querySelectorAll(".workspace-filter-option").forEach(function (opt) {
      const isMatch = (opt.getAttribute("data-value") || "") === selectedWorkspaceId;
      opt.classList.toggle("active", isMatch);
      opt.setAttribute("aria-selected", String(isMatch));
    });
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function getInitials(firstName, lastName) {
    const a = (firstName || "").trim().charAt(0);
    const b = (lastName || "").trim().charAt(0);
    return (a + b || "U").toUpperCase();
  }

  function renderUsersPanel() {
    const contentEl = document.getElementById("workspaceUsersContent");
    const countPill = document.getElementById("usersCountPill");
    const registerBtn = document.getElementById("registerNewUserBtn");
    const modalWsName = document.getElementById("registerModalWorkspaceName");

    if (!contentEl) return;

    if (!selectedWorkspaceId) {
      if (registerBtn) registerBtn.disabled = true;
      if (countPill) {
        countPill.classList.remove("visible");
        countPill.textContent = "";
      }
      contentEl.innerHTML =
        '<p class="users-empty-msg">Select a workspace to view existing users.</p>';
      return;
    }

    if (registerBtn) registerBtn.disabled = false;
    if (modalWsName) modalWsName.textContent = selectedWorkspaceName;

    const users = WORKSPACE_USERS[selectedWorkspaceId] || [];
    if (countPill) {
      countPill.textContent = `${users.length} ${users.length === 1 ? "user" : "users"}`;
      countPill.classList.add("visible");
    }

    if (users.length === 0) {
      contentEl.innerHTML = `
        <p class="users-empty-msg">
          No users registered in <strong>${escapeHtml(selectedWorkspaceName)}</strong> yet.
          Click <strong>+ Register New User</strong> to add one.
        </p>`;
      return;
    }

    const rowsHtml = users
      .map(function (u) {
        const fullName = `${u.firstName} ${u.lastName}`.trim();
        const badgeClass =
          u.status === "ACTIVE" ? "status-pill-active" : "status-pill-inactive";
        const safeId = escapeHtml(u.id);

        return `
          <tr>
            <td>
              <div class="user-identity-cell">
                <span class="user-avatar-circle" aria-hidden="true">${escapeHtml(getInitials(u.firstName, u.lastName))}</span>
                <span class="user-name-text">${escapeHtml(fullName)}</span>
              </div>
            </td>
            <td>${escapeHtml(u.email)}</td>
            <td>${escapeHtml(u.mobile)}</td>
            <td><span class="user-role-badge">${escapeHtml(u.role)}</span></td>
            <td><span class="status-pill ${badgeClass}">${escapeHtml(u.status)}</span></td>
            <td class="text-end">
              <div class="user-action-group">
                <button
                  type="button"
                  class="user-icon-btn btn-edit"
                  data-user-action="edit"
                  data-user-id="${safeId}"
                  title="Edit User"
                  aria-label="Edit ${escapeHtml(fullName)}"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path>
                  </svg>
                </button>
                <button
                  type="button"
                  class="user-icon-btn btn-delete"
                  data-user-action="delete"
                  data-user-id="${safeId}"
                  title="Delete User"
                  aria-label="Delete ${escapeHtml(fullName)}"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                  </svg>
                </button>
              </div>
            </td>
          </tr>`;
      })
      .join("");

    contentEl.innerHTML = `
      <div class="users-table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th scope="col">User</th>
              <th scope="col">Email</th>
              <th scope="col">Mobile</th>
              <th scope="col">Role</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>${rowsHtml}</tbody>
        </table>
      </div>`;
  }

  function initWorkspaceDropdown() {
    const wrapper = document.getElementById("usersWorkspaceDropdown");
    const toggleBtn = document.getElementById("usersWorkspaceToggle");
    const valueEl = document.getElementById("usersWorkspaceValue");
    const options = wrapper?.querySelectorAll(".workspace-filter-option");

    if (!wrapper || !toggleBtn || !valueEl || !options?.length) return;

    function openMenu() {
      wrapper.classList.add("open");
      toggleBtn.setAttribute("aria-expanded", "true");
    }

    function closeMenu() {
      wrapper.classList.remove("open");
      toggleBtn.setAttribute("aria-expanded", "false");
    }

    function selectOption(optionEl) {
      const value = optionEl.getAttribute("data-value") || "";
      const label = optionEl.textContent.trim();

      options.forEach(function (opt) {
        const isMatch = opt === optionEl;
        opt.classList.toggle("active", isMatch);
        opt.setAttribute("aria-selected", String(isMatch));
      });

      selectedWorkspaceId = value;
      selectedWorkspaceName = value ? label : "";
      valueEl.textContent = value ? label : "Workspace";
      wrapper.classList.toggle("has-value", Boolean(value));

      closeMenu();
      renderUsersPanel();
    }

    toggleBtn.addEventListener("click", function (event) {
      event.stopPropagation();
      wrapper.classList.contains("open") ? closeMenu() : openMenu();
    });

    options.forEach(function (opt) {
      opt.addEventListener("click", function () {
        selectOption(opt);
      });
    });

    document.addEventListener("click", function (event) {
      if (!wrapper.contains(event.target)) closeMenu();
    });
  }

  function initUserModals() {
    const form = document.getElementById("registerUserForm");
    const modalEl = document.getElementById("registerUserModal");
    const modalTitle = document.getElementById("registerUserModalLabel");
    const submitBtn = document.getElementById("registerUserSubmitBtn");
    const editIdInput = document.getElementById("editUserId");
    const usersContent = document.getElementById("workspaceUsersContent");
    const deleteModalEl = document.getElementById("deleteUserModal");
    const deleteNameEl = document.getElementById("deleteUserName");
    const confirmDeleteBtn = document.getElementById("confirmDeleteUserBtn");

    modalEl?.addEventListener("hidden.bs.modal", function () {
      form?.reset();
      form?.classList.remove("was-validated");
      if (editIdInput) editIdInput.value = "";
      if (modalTitle) modalTitle.textContent = "Register New User";
      if (submitBtn) submitBtn.textContent = "Register User";
    });

    form?.addEventListener("submit", async function (event) {
      event.preventDefault();
      if (!selectedWorkspaceId) return;

      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        return;
      }

      const firstName = document.getElementById("userFirstName").value.trim();
      const lastName = document.getElementById("userLastName").value.trim();
      const email = document.getElementById("userEmail").value.trim();
      const mobile = document.getElementById("userMobile").value.trim();
      const roleVal = document.getElementById("userRole").value;
      const status = document.getElementById("userStatus").value;
      const editingId = editIdInput?.value.trim();

      if (IS_SERVER) {
        try {
          const isEdit = Boolean(editingId);
          const url = isEdit
            ? SERVER.routes.updateTemplate.replace("__ID__", editingId)
            : SERVER.routes.store;
          const payload = isEdit
            ? {
                provider_role_id: Number(roleVal),
                workspace_id: selectedWorkspaceId !== "all" ? Number(selectedWorkspaceId) : null,
                status: status,
              }
            : {
                first_name: firstName,
                last_name: lastName,
                email: email,
                mobile: mobile,
                provider_role_id: Number(roleVal),
                workspace_id: selectedWorkspaceId !== "all" ? Number(selectedWorkspaceId) : null,
              };
          const resp = await fetch(url, {
            method: isEdit ? "PATCH" : "POST",
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
              "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
          });
          const data = await resp.json();
          if (!resp.ok) {
            window.alert(data.message || "Unable to save user.");
            return;
          }
          window.location.reload();
        } catch (_) {
          window.alert("Network error saving user.");
        }
        return;
      }
    });

    usersContent?.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-user-action]");
      if (!btn || !selectedWorkspaceId) return;

      const action = btn.getAttribute("data-user-action");
      const userId = btn.getAttribute("data-user-id");
      const list = WORKSPACE_USERS[selectedWorkspaceId] || [];
      const user = list.find(function (u) {
        return String(u.id) === String(userId);
      });
      if (!user) return;

      if (action === "edit" && modalEl) {
        editIdInput.value = user.id;
        document.getElementById("userFirstName").value = user.firstName;
        document.getElementById("userLastName").value = user.lastName;
        document.getElementById("userEmail").value = user.email;
        document.getElementById("userMobile").value = user.mobile;
        document.getElementById("userRole").value = String(user.provider_role_id || user.role);
        document.getElementById("userStatus").value = user.status;
        if (modalTitle) modalTitle.textContent = "Edit User";
        if (submitBtn) submitBtn.textContent = "Save Changes";
        window.bootstrap?.Modal.getOrCreateInstance(modalEl).show();
      } else if (action === "delete" && deleteModalEl) {
        pendingDeleteUserId = user.id;
        if (deleteNameEl) {
          deleteNameEl.textContent = `${user.firstName} ${user.lastName}`.trim();
        }
        window.bootstrap?.Modal.getOrCreateInstance(deleteModalEl).show();
      }
    });

    confirmDeleteBtn?.addEventListener("click", async function () {
      if (!pendingDeleteUserId || !selectedWorkspaceId) return;
      if (IS_SERVER) {
        const resp = await fetch(
          SERVER.routes.destroyTemplate.replace("__ID__", pendingDeleteUserId),
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
          return;
        }
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    bootstrapServerData();
    initWorkspaceDropdown();
    initUserModals();
    renderUsersPanel();
  });
})();
