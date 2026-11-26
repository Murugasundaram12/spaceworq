/**
 * AdminPanel — Reserve Requests Filtering, Status Chips & Table Rendering
 */
(function () {
  "use strict";

  const ALL_STATUSES = [
    "Open",
    "In Progress",
    "Review",
    "Discussion",
    "Completed",
    "Cancelled",
    "Rejected",
  ];

  // Empty initial dataset to match reference screenshot; plug in backend API records here
  const RESERVE_REQUESTS = [];

  const state = {
    workspace: "all",
    status: "all",
  };

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function getWorkspaceFiltered() {
    if (state.workspace === "all") return RESERVE_REQUESTS;
    return RESERVE_REQUESTS.filter(function (req) {
      return req.workspaceKey === state.workspace;
    });
  }

  function getFullyFiltered() {
    const byWs = getWorkspaceFiltered();
    if (state.status === "all") return byWs;
    return byWs.filter(function (req) {
      return req.status === state.status;
    });
  }

  function updateStatusCounts() {
    const byWs = getWorkspaceFiltered();
    const counts = { all: byWs.length };
    ALL_STATUSES.forEach(function (st) {
      counts[st] = 0;
    });
    byWs.forEach(function (req) {
      if (Object.prototype.hasOwnProperty.call(counts, req.status)) {
        counts[req.status]++;
      }
    });

    document.querySelectorAll("[data-count-status]").forEach(function (badge) {
      const key = badge.getAttribute("data-count-status");
      badge.textContent = String(counts[key] ?? 0);
    });
  }

  function renderTable() {
    const tbody = document.getElementById("reserveRequestsBody");
    if (!tbody) return;

    const rows = getFullyFiltered();
    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="10" class="rr-empty-cell">
            No reserve requests found for this status.
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = rows
      .map(function (req) {
        const moveOptions = ALL_STATUSES.map(function (st) {
          const selected = st === req.status ? "selected" : "";
          return `<option value="${escapeHtml(st)}" ${selected}>${escapeHtml(st)}</option>`;
        }).join("");

        return `
          <tr>
            <td class="fw-semibold text-primary">${escapeHtml(req.id)}</td>
            <td>${escapeHtml(req.requester)}</td>
            <td>${escapeHtml(req.workspace)}</td>
            <td>${escapeHtml(req.request)}</td>
            <td>${escapeHtml(req.reminders)}</td>
            <td><span class="status-pill status-pill-active">${escapeHtml(req.status)}</span></td>
            <td>${escapeHtml(req.remarks || "—")}</td>
            <td>${escapeHtml(req.assignedTo || "Unassigned")}</td>
            <td>
              <select class="rr-move-select" data-move-id="${escapeHtml(req.id)}" aria-label="Move request ${escapeHtml(req.id)} status">
                ${moveOptions}
              </select>
            </td>
            <td>${escapeHtml(req.updatedAt)}</td>
          </tr>`;
      })
      .join("");
  }

  function syncStatusUI(statusValue) {
    state.status = statusValue;

    // Sync status pills
    document.querySelectorAll("#rrStatusStrip .rr-status-pill").forEach(function (pill) {
      const isMatch = pill.getAttribute("data-status") === statusValue;
      pill.classList.toggle("active", isMatch);
      pill.setAttribute("aria-pressed", String(isMatch));
    });

    // Sync Status dropdown label & active item
    const dropdown = document.getElementById("rrStatusDropdown");
    const valueEl = document.getElementById("rrStatusValue");
    const options = dropdown?.querySelectorAll(".workspace-filter-option");
    options?.forEach(function (opt) {
      const isMatch = opt.getAttribute("data-value") === statusValue;
      opt.classList.toggle("active", isMatch);
      opt.setAttribute("aria-selected", String(isMatch));
      if (isMatch && valueEl) {
        valueEl.textContent = opt.textContent.trim();
      }
    });

    renderTable();
  }

  /* --------------------------------------------------------------------------
     Reusable Custom Material Dropdown Wiring
     -------------------------------------------------------------------------- */
  function initCustomDropdown(wrapperId, toggleId, valueId, onSelect) {
    const wrapper = document.getElementById(wrapperId);
    const toggleBtn = document.getElementById(toggleId);
    const valueEl = document.getElementById(valueId);
    const options = wrapper?.querySelectorAll(".workspace-filter-option");
    if (!wrapper || !toggleBtn || !valueEl || !options?.length) return;

    function closeMenu() {
      wrapper.classList.remove("open");
      toggleBtn.setAttribute("aria-expanded", "false");
    }

    function openMenu() {
      document.querySelectorAll(".workspace-filter-wrap.open").forEach(function (other) {
        if (other !== wrapper) other.classList.remove("open");
      });
      wrapper.classList.add("open");
      toggleBtn.setAttribute("aria-expanded", "true");
    }

    function pick(opt) {
      const val = opt.getAttribute("data-value") || "all";
      options.forEach(function (item) {
        const isMatch = item === opt;
        item.classList.toggle("active", isMatch);
        item.setAttribute("aria-selected", String(isMatch));
      });
      valueEl.textContent = opt.textContent.trim();
      closeMenu();
      onSelect(val);
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
        pick(opt);
      });
      opt.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          pick(opt);
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

  function initReserveRequests() {
    initCustomDropdown(
      "rrWorkspaceDropdown",
      "rrWorkspaceToggle",
      "rrWorkspaceValue",
      function (val) {
        state.workspace = val;
        updateStatusCounts();
        renderTable();
      }
    );

    initCustomDropdown(
      "rrStatusDropdown",
      "rrStatusToggle",
      "rrStatusValue",
      function (val) {
        syncStatusUI(val);
      }
    );

    // Status pill clicks
    document.querySelectorAll("#rrStatusStrip .rr-status-pill").forEach(function (pill) {
      pill.addEventListener("click", function () {
        const st = pill.getAttribute("data-status") || "all";
        syncStatusUI(st);
      });
    });

    // Live status change from table "Move To" select
    const tbody = document.getElementById("reserveRequestsBody");
    tbody?.addEventListener("change", function (event) {
      const select = event.target.closest(".rr-move-select");
      if (!select) return;
      const reqId = select.getAttribute("data-move-id");
      const req = RESERVE_REQUESTS.find(function (item) {
        return item.id === reqId;
      });
      if (req) {
        req.status = select.value;
        updateStatusCounts();
        renderTable();
      }
    });

    updateStatusCounts();
    renderTable();
  }

  document.addEventListener("DOMContentLoaded", initReserveRequests);
})();
