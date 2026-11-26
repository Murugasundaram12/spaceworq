/**
 * AdminPanel — Users Page Interactions (Workspace Selector, User Directory, Register/Edit/Delete)
 */
(function () {
  "use strict";

  const WORKSPACE_USERS = {
    "self-registered": [
      {
        id: "u-101",
        firstName: "Arjun",
        lastName: "Nair",
        email: "arjun.nair@adminpanel.com",
        mobile: "+91 98401 22310",
        role: "Workspace Manager",
        status: "ACTIVE",
      },
      {
        id: "u-102",
        firstName: "Meera",
        lastName: "Krishnan",
        email: "meera.k@adminpanel.com",
        mobile: "+91 97908 33419",
        role: "Operations Lead",
        status: "ACTIVE",
      },
      {
        id: "u-103",
        firstName: "Karthik",
        lastName: "Subramanian",
        email: "karthik.s@adminpanel.com",
        mobile: "+91 98402 88901",
        role: "Front Desk",
        status: "INACTIVE",
      },
    ],
    adminpanel: [
      {
        id: "u-201",
        firstName: "Divya",
        lastName: "Ramesh",
        email: "divya.r@adminpanel.com",
        mobile: "+91 98840 11209",
        role: "Workspace Manager",
        status: "ACTIVE",
      },
      {
        id: "u-202",
        firstName: "Vikram",
        lastName: "Iyer",
        email: "vikram.iyer@adminpanel.com",
        mobile: "+91 98840 77823",
        role: "Billing Admin",
        status: "ACTIVE",
      },
      {
        id: "u-203",
        firstName: "Priya",
        lastName: "Menon",
        email: "priya.menon@adminpanel.com",
        mobile: "+91 98401 44512",
        role: "Community Lead",
        status: "ACTIVE",
      },
    ],
    "system-admin": [
      {
        id: "u-301",
        firstName: "System",
        lastName: "Administrator",
        email: "admin@adminpanel.com",
        mobile: "+91 98400 00001",
        role: "Workspace Manager",
        status: "ACTIVE",
      },
    ],
  };

  let selectedWorkspaceId = "";
  let selectedWorkspaceName = "";
  let pendingDeleteUserId = null;

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

  /* --------------------------------------------------------------------------
     Workspace Custom Dropdown
     -------------------------------------------------------------------------- */
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
      if (wrapper.classList.contains("open")) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    options.forEach(function (opt) {
      opt.addEventListener("click", function () {
        selectOption(opt);
      });
      opt.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          selectOption(opt);
        } else if (event.key === "Escape") {
          closeMenu();
          toggleBtn.focus();
        }
      });
    });

    document.addEventListener("click", function (event) {
      if (!wrapper.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && wrapper.classList.contains("open")) {
        closeMenu();
        toggleBtn.focus();
      }
    });
  }

  /* --------------------------------------------------------------------------
     Register / Edit / Delete User Modals
     -------------------------------------------------------------------------- */
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

    form?.addEventListener("submit", function (event) {
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
      const role = document.getElementById("userRole").value;
      const status = document.getElementById("userStatus").value;
      const editingId = editIdInput?.value.trim();

      if (!WORKSPACE_USERS[selectedWorkspaceId]) {
        WORKSPACE_USERS[selectedWorkspaceId] = [];
      }
      const list = WORKSPACE_USERS[selectedWorkspaceId];

      if (editingId) {
        const match = list.find(function (u) {
          return u.id === editingId;
        });
        if (match) {
          match.firstName = firstName;
          match.lastName = lastName;
          match.email = email;
          match.mobile = mobile;
          match.role = role;
          match.status = status;
        }
      } else {
        list.unshift({
          id: `u-${Date.now()}`,
          firstName: firstName,
          lastName: lastName,
          email: email,
          mobile: mobile,
          role: role,
          status: status,
        });
      }

      renderUsersPanel();
      window.bootstrap?.Modal.getOrCreateInstance(modalEl).hide();
    });

    usersContent?.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-user-action]");
      if (!btn || !selectedWorkspaceId) return;

      const action = btn.getAttribute("data-user-action");
      const userId = btn.getAttribute("data-user-id");
      const list = WORKSPACE_USERS[selectedWorkspaceId] || [];
      const user = list.find(function (u) {
        return u.id === userId;
      });
      if (!user) return;

      if (action === "edit" && modalEl) {
        editIdInput.value = user.id;
        document.getElementById("userFirstName").value = user.firstName;
        document.getElementById("userLastName").value = user.lastName;
        document.getElementById("userEmail").value = user.email;
        document.getElementById("userMobile").value = user.mobile;
        document.getElementById("userRole").value = user.role;
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

    confirmDeleteBtn?.addEventListener("click", function () {
      if (!pendingDeleteUserId || !selectedWorkspaceId) return;
      WORKSPACE_USERS[selectedWorkspaceId] = (
        WORKSPACE_USERS[selectedWorkspaceId] || []
      ).filter(function (u) {
        return u.id !== pendingDeleteUserId;
      });
      pendingDeleteUserId = null;
      renderUsersPanel();
      window.bootstrap?.Modal.getOrCreateInstance(deleteModalEl).hide();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initWorkspaceDropdown();
    initUserModals();
    renderUsersPanel();
  });
})();
