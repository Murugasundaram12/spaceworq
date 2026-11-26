/**
 * AdminPanel — Dashboard Interactions & State
 */
(function () {
  "use strict";

  const PROVIDER_VIEWS = {
    all: {
      metrics: {
        users: 8,
        providers: 1,
        workspaces: 7,
        floors: 0,
        templates: 0,
        spaces: 2170,
      },
      workspaces: [
        { name: "Workefella", status: "ACTIVE" },
        { name: "smartwork", status: "ACTIVE" },
        { name: "cove", status: "ACTIVE" },
        { name: "dc works", status: "INACTIVE" },
        { name: "Test", status: "INACTIVE" },
      ],
    },
    "self-registered": {
      metrics: {
        users: 3,
        providers: 1,
        workspaces: 2,
        floors: 0,
        templates: 0,
        spaces: 500,
      },
      workspaces: [
        { name: "cove", status: "ACTIVE" },
        { name: "Test", status: "INACTIVE" },
      ],
    },
    adminpanel: {
      metrics: {
        users: 4,
        providers: 1,
        workspaces: 4,
        floors: 0,
        templates: 0,
        spaces: 1460,
      },
      workspaces: [
        { name: "Workefella", status: "ACTIVE" },
        { name: "smartwork", status: "ACTIVE" },
        { name: "dc works", status: "INACTIVE" },
      ],
    },
    "system-admin": {
      metrics: {
        users: 1,
        providers: 1,
        workspaces: 1,
        floors: 0,
        templates: 0,
        spaces: 210,
      },
      workspaces: [{ name: "AdminPanel HQ", status: "ACTIVE" }],
    },
  };

  /* --------------------------------------------------------------------------
     Load Signed-in User Session (from Login or Register)
     -------------------------------------------------------------------------- */
  function hydrateUserSession() {
    let user = {
      name: "admin",
      email: "admin@adminpanel.com",
      role: "Admin",
      portalSubtitle: "System Administrator Portal",
    };

    try {
      const saved = window.localStorage.getItem("adminpanel_user");
      if (saved) {
        const parsed = JSON.parse(saved);
        if (parsed && typeof parsed === "object") {
          user = {
            name: parsed.name || user.name,
            email: parsed.email || user.email,
            role: parsed.role || user.role,
            portalSubtitle: parsed.portalSubtitle || user.portalSubtitle,
          };
        }
      }
    } catch (_) {
      // Fall back to default admin user if storage is unavailable
    }

    const welcomeNameEl = document.getElementById("welcomeUserName");
    const portalSubtitleEl = document.getElementById("portalSubtitle");
    const sidebarEmailEl = document.getElementById("sidebarUserEmail");
    const sidebarRoleEl = document.getElementById("sidebarUserRole");
    const sidebarAvatarEl = document.getElementById("sidebarUserAvatar");

    if (welcomeNameEl) welcomeNameEl.textContent = user.name;
    if (portalSubtitleEl) portalSubtitleEl.textContent = user.portalSubtitle;
    if (sidebarEmailEl) sidebarEmailEl.textContent = user.email;
    if (sidebarRoleEl) sidebarRoleEl.textContent = user.role;
    if (sidebarAvatarEl) {
      const initial = (user.name || user.email || "A").trim().charAt(0).toUpperCase();
      sidebarAvatarEl.textContent = initial || "A";
    }
  }

  /* --------------------------------------------------------------------------
     Sidebar Collapse (Desktop) & Drawer (Mobile)
     -------------------------------------------------------------------------- */
  function initSidebarControls() {
    const collapseBtn = document.getElementById("sidebarCollapseBtn");
    const mobileToggleBtn = document.getElementById("mobileMenuToggle");
    const backdrop = document.getElementById("sidebarBackdrop");

    collapseBtn?.addEventListener("click", function () {
      document.body.classList.toggle("sidebar-collapsed");
      const isCollapsed = document.body.classList.contains("sidebar-collapsed");
      collapseBtn.setAttribute(
        "aria-label",
        isCollapsed ? "Expand sidebar" : "Collapse sidebar"
      );
    });

    function closeMobileSidebar() {
      document.body.classList.remove("sidebar-mobile-open");
      backdrop?.classList.remove("show");
    }

    mobileToggleBtn?.addEventListener("click", function () {
      document.body.classList.toggle("sidebar-mobile-open");
      backdrop?.classList.toggle("show");
    });

    backdrop?.addEventListener("click", closeMobileSidebar);

    window.addEventListener("resize", function () {
      if (window.innerWidth >= 992) {
        closeMobileSidebar();
      }
    });

    // Active navigation item highlight for demo sidebar links
    document.querySelectorAll(".sidebar-nav-link[data-nav-item]").forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        document
          .querySelectorAll(".sidebar-nav-link[data-nav-item]")
          .forEach(function (item) {
            item.classList.remove("active");
          });
        link.classList.add("active");
        if (window.innerWidth < 992) {
          closeMobileSidebar();
        }
      });
    });
  }

  /* --------------------------------------------------------------------------
     Provider Workspace Filter & Dynamic Dashboard Rendering
     -------------------------------------------------------------------------- */
  function renderWorkspaceList(items) {
    const listEl = document.getElementById("recentWorkspacesList");
    if (!listEl) return;

    listEl.innerHTML = items
      .map(function (ws) {
        const badgeClass =
          ws.status === "ACTIVE" ? "status-pill-active" : "status-pill-inactive";
        return `
          <li class="workspace-list-item">
            <span class="workspace-name">${ws.name}</span>
            <span class="status-pill ${badgeClass}">${ws.status}</span>
          </li>`;
      })
      .join("");
  }

  function updateMetrics(metrics) {
    const map = {
      kpiSystemUsers: metrics.users,
      kpiActiveProviders: metrics.providers,
      kpiAllWorkspaces: metrics.workspaces,
      kpiAllFloors: metrics.floors,
      kpiMappedTemplates: metrics.templates,
      overviewTotalWorkspaces: metrics.workspaces,
      overviewTotalFloors: metrics.floors,
      overviewTotalSpaces: metrics.spaces,
    };

    Object.keys(map).forEach(function (id) {
      const el = document.getElementById(id);
      if (el) el.textContent = String(map[id]);
    });
  }

  function initWorkspaceFilter() {
    const wrapper = document.getElementById("providerWorkspaceDropdown");
    const toggleBtn = document.getElementById("providerWorkspaceToggle");
    const valueEl = document.getElementById("providerWorkspaceValue");
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
      const value = optionEl.getAttribute("data-value") || "all";
      const label = optionEl.textContent.trim();

      options.forEach(function (opt) {
        const isMatch = opt === optionEl;
        opt.classList.toggle("active", isMatch);
        opt.setAttribute("aria-selected", String(isMatch));
      });

      valueEl.textContent = label;
      closeMenu();

      try {
        if (value === "all") {
          window.localStorage.removeItem("adminpanel_selected_workspace");
        } else {
          window.localStorage.setItem("adminpanel_selected_workspace", value);
        }
      } catch (_) {
        // Ignore storage errors
      }

      const view = PROVIDER_VIEWS[value] || PROVIDER_VIEWS.all;
      updateMetrics(view.metrics);
      renderWorkspaceList(view.workspaces);
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
     Sign Out Handler
     -------------------------------------------------------------------------- */
  function initSignOut() {
    const signOutBtn = document.getElementById("signOutBtn");
    signOutBtn?.addEventListener("click", function (event) {
      event.preventDefault();
      try {
        window.localStorage.removeItem("adminpanel_user");
      } catch (_) {
        // Ignore storage errors
      }
      window.location.href = "login.html";
    });
  }

  /* --------------------------------------------------------------------------
     Initialize Dashboard
     -------------------------------------------------------------------------- */
  document.addEventListener("DOMContentLoaded", function () {
    hydrateUserSession();
    initSidebarControls();
    initWorkspaceFilter();
    initSignOut();
  });
})();
