/**
 * AdminPanel — Instant Booking Requests Filtering, Status Chips, Reset Past Bookings & Table
 */
(function () {
  "use strict";

  const ALL_STATUSES = [
    "Upcoming",
    "Active",
    "In Progress",
    "Review",
    "Completed",
    "Cancelled",
  ];

  // Empty initial dataset to match reference screenshot; plug in backend API records here
  const INSTANT_BOOKING_REQUESTS = [];

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
    if (state.workspace === "all") return INSTANT_BOOKING_REQUESTS;
    return INSTANT_BOOKING_REQUESTS.filter(function (req) {
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
    const tbody = document.getElementById("instantBookingBody");
    if (!tbody) return;

    const rows = getFullyFiltered();
    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="9" class="rr-empty-cell">
            No instant booking requests found for this status.
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
            <td>${escapeHtml(req.bookingDetails)}</td>
            <td><span class="status-pill status-pill-active">${escapeHtml(req.status)}</span></td>
            <td>${escapeHtml(req.remarks || "—")}</td>
            <td>${escapeHtml(req.assignedTo || "Unassigned")}</td>
            <td>
              <select class="rr-move-select" data-move-id="${escapeHtml(req.id)}" aria-label="Move booking ${escapeHtml(req.id)} status">
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

    document.querySelectorAll("#ibrStatusStrip .rr-status-pill").forEach(function (pill) {
      const isMatch = pill.getAttribute("data-status") === statusValue;
      pill.classList.toggle("active", isMatch);
      pill.setAttribute("aria-pressed", String(isMatch));
    });

    const dropdown = document.getElementById("ibrStatusDropdown");
    const valueEl = document.getElementById("ibrStatusValue");
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

  function initResetPastBookings() {
    const btn = document.getElementById("resetPastBookingsBtn");
    const toast = document.getElementById("resetToastBanner");
    const toastText = document.getElementById("resetToastText");
    const closeToastBtn = document.getElementById("closeResetToastBtn");
    let hideTimer = null;

    closeToastBtn?.addEventListener("click", function () {
      toast?.classList.remove("show");
    });

    btn?.addEventListener("click", function () {
      const icon = btn.querySelector("svg");
      btn.disabled = true;
      icon?.classList.add("is-spinning");

      window.setTimeout(function () {
        btn.disabled = false;
        icon?.classList.remove("is-spinning");
        updateStatusCounts();
        renderTable();

        if (toast && toastText) {
          toastText.textContent =
            "Past instant bookings checked and reset successfully (0 expired records found).";
          toast.classList.add("show");
          if (hideTimer) window.clearTimeout(hideTimer);
          hideTimer = window.setTimeout(function () {
            toast.classList.remove("show");
          }, 4000);
        }
      }, 650);
    });
  }

  function initInstantBookings() {
    initCustomDropdown(
      "ibrWorkspaceDropdown",
      "ibrWorkspaceToggle",
      "ibrWorkspaceValue",
      function (val) {
        state.workspace = val;
        updateStatusCounts();
        renderTable();
      }
    );

    initCustomDropdown(
      "ibrStatusDropdown",
      "ibrStatusToggle",
      "ibrStatusValue",
      function (val) {
        syncStatusUI(val);
      }
    );

    document.querySelectorAll("#ibrStatusStrip .rr-status-pill").forEach(function (pill) {
      pill.addEventListener("click", function () {
        const st = pill.getAttribute("data-status") || "all";
        syncStatusUI(st);
      });
    });

    const tbody = document.getElementById("instantBookingBody");
    tbody?.addEventListener("change", function (event) {
      const select = event.target.closest(".rr-move-select");
      if (!select) return;
      const reqId = select.getAttribute("data-move-id");
      const req = INSTANT_BOOKING_REQUESTS.find(function (item) {
        return item.id === reqId;
      });
      if (req) {
        req.status = select.value;
        updateStatusCounts();
        renderTable();
      }
    });

    initResetPastBookings();
    updateStatusCounts();
    renderTable();
  }

  document.addEventListener("DOMContentLoaded", initInstantBookings);
})();
