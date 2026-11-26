/**
 * AdminPanel — Detail Report Analytics, Filtering & CSV Export
 */
(function () {
  "use strict";

  const STATUS_COLORS = {
    Open: "#ea6c00",
    "In Progress": "#0284c7",
    Review: "#6366f1",
    Discussion: "#0ea5e9",
    Completed: "#16a34a",
    Cancelled: "#64748b",
    Rejected: "#dc2626",
    Upcoming: "#ea6c00",
    Active: "#0284c7",
  };

  // Empty initial dataset to match reference screenshot; populate from API as needed
  const REPORT_RECORDS = [];

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function getActiveFilters() {
    return {
      fromDate: document.getElementById("reportFromDate")?.value || "",
      toDate: document.getElementById("reportToDate")?.value || "",
      status: document.getElementById("reportStatusFilter")?.value || "all",
      workspace: document.getElementById("reportWorkspaceFilter")?.value || "all",
      location: document.getElementById("reportLocationFilter")?.value || "all",
      category: document.getElementById("reportCategoryFilter")?.value || "all",
    };
  }

  function filterRecords() {
    const f = getActiveFilters();
    return REPORT_RECORDS.filter(function (rec) {
      if (f.status !== "all" && rec.status !== f.status) return false;
      if (f.workspace !== "all" && rec.workspaceKey !== f.workspace) return false;
      if (f.location !== "all" && rec.location !== f.location) return false;
      if (f.category !== "all" && rec.category !== f.category) return false;
      if (f.fromDate && rec.isoDate < f.fromDate) return false;
      if (f.toDate && rec.isoDate > f.toDate) return false;
      return true;
    });
  }

  function renderDonutChart(records) {
    const segmentsGroup = document.getElementById("donutSegmentsGroup");
    const centerText = document.getElementById("donutCenterCount");
    const legendEl = document.getElementById("donutLegend");

    const total = records.length;
    if (centerText) centerText.textContent = String(total);

    if (!segmentsGroup) return;

    if (total === 0) {
      segmentsGroup.innerHTML = "";
      if (legendEl) legendEl.innerHTML = "";
      return;
    }

    const counts = {};
    records.forEach(function (rec) {
      counts[rec.status] = (counts[rec.status] || 0) + 1;
    });

    const radius = 44;
    const circumference = 2 * Math.PI * radius;
    let offset = 0;

    const entries = Object.entries(counts);
    segmentsGroup.innerHTML = entries
      .map(function ([status, count]) {
        const dash = (count / total) * circumference;
        const color = STATUS_COLORS[status] || "#5246e5";
        const seg = `
          <circle
            cx="60"
            cy="60"
            r="${radius}"
            fill="none"
            stroke="${color}"
            stroke-width="18"
            stroke-dasharray="${dash} ${circumference - dash}"
            stroke-dashoffset="${-offset}"
            transform="rotate(-90 60 60)"
          ></circle>`;
        offset += dash;
        return seg;
      })
      .join("");

    if (legendEl) {
      legendEl.innerHTML = entries
        .map(function ([status, count]) {
          const color = STATUS_COLORS[status] || "#5246e5";
          return `
            <div class="report-legend-row">
              <span class="report-legend-dot" style="background:${color};"></span>
              <span>${escapeHtml(status)}: <strong>${count}</strong></span>
            </div>`;
        })
        .join("");
    }
  }

  function renderDatePanel(records) {
    const bodyEl = document.getElementById("requestsByDateBody");
    if (!bodyEl) return;

    if (!records.length) {
      bodyEl.innerHTML =
        '<p class="report-date-empty">No request data for selected filters.</p>';
      return;
    }

    const byDate = {};
    records.forEach(function (rec) {
      byDate[rec.isoDate] = (byDate[rec.isoDate] || 0) + 1;
    });
    const maxCount = Math.max(...Object.values(byDate), 1);

    bodyEl.innerHTML = `
      <div class="w-100 d-flex flex-column gap-2">
        ${Object.entries(byDate)
          .map(function ([date, count]) {
            const pct = Math.round((count / maxCount) * 100);
            return `
              <div class="d-flex align-items-center gap-2 small">
                <span style="min-width:82px;color:#334155;">${escapeHtml(date)}</span>
                <div class="flex-grow-1 bg-white rounded-pill overflow-hidden" style="height:10px;">
                  <div style="width:${pct}%;height:100%;background:#3b82f6;border-radius:99px;"></div>
                </div>
                <strong style="min-width:20px;text-align:right;color:#0f172a;">${count}</strong>
              </div>`;
          })
          .join("")}
      </div>`;
  }

  function renderReport() {
    const records = filterRecords();
    const total = records.length;
    const uniqueWorkspaces = new Set(records.map((r) => r.workspace)).size;
    const uniqueLocations = new Set(records.map((r) => r.location)).size;

    // Top 3 KPI cards
    const setText = function (id, val) {
      const el = document.getElementById(id);
      if (el) el.textContent = String(val);
    };

    setText("kpiTotalRequests", total);
    setText("kpiTotalWorkspaces", uniqueWorkspaces);
    setText("kpiTotalLocations", uniqueLocations);

    // Bottom right summary pills
    setText("pillTotalRequests", `Total Requests: ${total}`);
    setText("pillTotalWorkspaces", `Workspaces: ${uniqueWorkspaces}`);
    setText("pillTotalLocations", `Locations: ${uniqueLocations}`);

    // Export CSV button state
    const exportBtn = document.getElementById("exportCsvBtn");
    if (exportBtn) exportBtn.disabled = total === 0;

    // Table body
    const tbody = document.getElementById("reportTableBody");
    const emptyMessage =
      tbody?.getAttribute("data-empty-message") ||
      "No reserve request details found for selected filters.";

    if (tbody) {
      if (!total) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="report-empty-cell">${escapeHtml(emptyMessage)}</td>
          </tr>`;
      } else {
        tbody.innerHTML = records
          .map(function (rec) {
            return `
              <tr>
                <td class="fw-semibold text-primary">${escapeHtml(rec.id)}</td>
                <td>${escapeHtml(rec.status)}</td>
                <td>${escapeHtml(rec.requester)}</td>
                <td>${escapeHtml(rec.workspace)}</td>
                <td>${escapeHtml(rec.location)}</td>
                <td>${escapeHtml(rec.reminders)}</td>
                <td>${escapeHtml(rec.updatedAt)}</td>
              </tr>`;
          })
          .join("");
      }
    }

    renderDonutChart(records);
    renderDatePanel(records);
  }

  function exportFilteredCsv() {
    const records = filterRecords();
    if (!records.length) return;

    const headers = [
      "Request ID",
      "Status",
      "Requester",
      "Workspace",
      "Location",
      "Reminders",
      "Updated",
    ];
    const csvRows = [headers.join(",")].concat(
      records.map(function (r) {
        return [
          r.id,
          r.status,
          r.requester,
          r.workspace,
          r.location,
          r.reminders,
          r.updatedAt,
        ]
          .map(function (val) {
            return `"${String(val ?? "").replace(/"/g, '""')}"`;
          })
          .join(",");
      })
    );

    const exportBtn = document.getElementById("exportCsvBtn");
    const filename =
      exportBtn?.getAttribute("data-csv-filename") || "reserve-requests-report.csv";
    const blob = new Blob([csvRows.join("\n")], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  function initReportPage() {
    const filterIds = [
      "reportFromDate",
      "reportToDate",
      "reportStatusFilter",
      "reportWorkspaceFilter",
      "reportLocationFilter",
      "reportCategoryFilter",
    ];

    filterIds.forEach(function (id) {
      document.getElementById(id)?.addEventListener("change", renderReport);
    });

    document.getElementById("reportResetBtn")?.addEventListener("click", function () {
      const fromEl = document.getElementById("reportFromDate");
      const toEl = document.getElementById("reportToDate");
      if (fromEl) fromEl.value = "";
      if (toEl) toEl.value = "";

      [
        "reportStatusFilter",
        "reportWorkspaceFilter",
        "reportLocationFilter",
        "reportCategoryFilter",
      ].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.value = "all";
      });

      renderReport();
    });

    document.getElementById("exportCsvBtn")?.addEventListener("click", exportFilteredCsv);

    renderReport();
  }

  document.addEventListener("DOMContentLoaded", initReportPage);
})();
