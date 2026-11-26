/**
 * AdminPanel — Template Mapping (Workspace Search, Floor Accordions & Template Assignment)
 */
(function () {
  "use strict";

  const TEMPLATES_STORAGE_KEY = "adminpanel_layout_templates";
  const MAPPINGS_STORAGE_KEY = "adminpanel_template_mappings";

  const PROVIDER_WORKSPACES = [
    { id: "workefella", name: "Workefella", code: "LTL-CHECS0007", city: "Chennai", floors: 7 },
    { id: "smartwork", name: "smartwork", code: "LTL-CHECS0004", city: "Chennai", floors: 3 },
    { id: "cove", name: "cove", code: "LTL-CHECS0002", city: "Chennai", floors: 2 },
    { id: "dc-works", name: "dc works", code: "LTL-CHECS0001", city: "Chennai", floors: 2 },
    { id: "test", name: "Test", code: "LTL-CHECS0003", city: "Chennai", floors: 1 },
    { id: "awfis", name: "awfis", code: "LTL-CHECS0005", city: "Chennai", floors: 3 },
    { id: "wework", name: "wework", code: "LTL-CHECS0006", city: "Chennai", floors: 7 },
  ];

  const DEFAULT_TEMPLATES = [
    { id: "default-open-floor", name: "Open Coworking Floor Layout" },
    { id: "default-enterprise-bay", name: "Enterprise Bay & Cabins" },
    { id: "default-meeting-suite", name: "Meeting & Conference Floor" },
  ];

  let mappings = {};
  let expandedWorkspaceId = "";
  let searchQuery = "";

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function getAvailableTemplates() {
    let saved = [];
    try {
      const raw = window.localStorage.getItem(TEMPLATES_STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) saved = parsed;
      }
    } catch (_) {
      saved = [];
    }
    return saved.length ? saved : DEFAULT_TEMPLATES;
  }

  function loadMappings() {
    try {
      const raw = window.localStorage.getItem(MAPPINGS_STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed === "object") mappings = parsed;
      }
    } catch (_) {
      mappings = {};
    }
  }

  function saveMappings() {
    try {
      window.localStorage.setItem(MAPPINGS_STORAGE_KEY, JSON.stringify(mappings));
    } catch (_) {
      // Ignore storage errors
    }
  }

  function showToast(message) {
    const banner = document.getElementById("tmMapStatusBanner");
    const textEl = document.getElementById("tmMapStatusText");
    if (!banner || !textEl) return;
    textEl.textContent = message;
    banner.classList.add("show");
  }

  function getMappedCount(wsId, floorCount) {
    let count = 0;
    for (let i = 1; i <= floorCount; i++) {
      if (mappings[`${wsId}::${i}`]) count++;
    }
    return count;
  }

  function renderWorkspaces() {
    const container = document.getElementById("tmMapWorkspacesContainer");
    if (!container) return;

    const q = searchQuery.trim().toLowerCase();
    const filtered = PROVIDER_WORKSPACES.filter(function (ws) {
      if (!q) return true;
      return (
        ws.name.toLowerCase().includes(q) ||
        ws.code.toLowerCase().includes(q) ||
        ws.city.toLowerCase().includes(q)
      );
    });

    if (!filtered.length) {
      container.innerHTML = '<p class="tm-map-empty">No workspaces available</p>';
      return;
    }

    const availableTemplates = getAvailableTemplates();

    container.innerHTML = `
      <div class="tm-ws-list">
        ${filtered
          .map(function (ws) {
            const isOpen = ws.id === expandedWorkspaceId;
            const mappedCount = getMappedCount(ws.id, ws.floors);

            let floorsHtml = "";
            if (isOpen) {
              const rows = [];
              for (let floorNum = 1; floorNum <= ws.floors; floorNum++) {
                const mapKey = `${ws.id}::${floorNum}`;
                const mappedName = mappings[mapKey] || "";

                const optionsHtml = availableTemplates
                  .map(function (tpl) {
                    const selected = tpl.name === mappedName ? "selected" : "";
                    return `<option value="${escapeHtml(tpl.name)}" ${selected}>${escapeHtml(tpl.name)}</option>`;
                  })
                  .join("");

                rows.push(`
                  <div class="tm-floor-row">
                    <div class="tm-floor-left">
                      <span class="tm-floor-label">Floor ${floorNum}</span>
                      <span class="tm-map-badge ${mappedName ? "mapped" : "unmapped"}">
                        ${mappedName ? `Mapped: ${escapeHtml(mappedName)}` : "Unmapped"}
                      </span>
                    </div>
                    <div class="tm-floor-controls">
                      <select
                        class="tm-template-select"
                        data-floor-select="${escapeHtml(mapKey)}"
                        aria-label="Select template for ${escapeHtml(ws.name)} Floor ${floorNum}"
                      >
                        ${optionsHtml}
                      </select>
                      <button
                        type="button"
                        class="btn-tm-map"
                        data-map-key="${escapeHtml(mapKey)}"
                        data-ws-name="${escapeHtml(ws.name)}"
                        data-floor-num="${floorNum}"
                      >
                        ${mappedName ? "Update" : "Map"}
                      </button>
                      ${
                        mappedName
                          ? `<button type="button" class="btn-tm-unmap" data-unmap-key="${escapeHtml(mapKey)}" data-ws-name="${escapeHtml(ws.name)}" data-floor-num="${floorNum}">Unmap</button>`
                          : ""
                      }
                    </div>
                  </div>`);
              }
              floorsHtml = `<div class="tm-floor-rows">${rows.join("")}</div>`;
            }

            return `
              <div class="tm-ws-item ${isOpen ? "open" : ""}">
                <button
                  type="button"
                  class="tm-ws-header"
                  data-toggle-ws="${escapeHtml(ws.id)}"
                  aria-expanded="${String(isOpen)}"
                >
                  <div class="tm-ws-info">
                    <span class="tm-ws-name">${escapeHtml(ws.name)}</span>
                    <span class="tm-ws-code">${escapeHtml(ws.code)}</span>
                    <span class="tm-ws-meta-pill">${ws.floors} ${ws.floors === 1 ? "floor" : "floors"}</span>
                    <span class="tm-ws-meta-pill">${mappedCount} mapped</span>
                  </div>
                  <svg class="tm-ws-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
                <div class="tm-ws-body">${floorsHtml}</div>
              </div>`;
          })
          .join("")}
      </div>`;
  }

  function initTemplateMapping() {
    const searchInput = document.getElementById("tmSearchWorkspacesInput");
    const container = document.getElementById("tmMapWorkspacesContainer");
    const closeBannerBtn = document.getElementById("tmCloseMapBannerBtn");
    const statusBanner = document.getElementById("tmMapStatusBanner");

    closeBannerBtn?.addEventListener("click", function () {
      statusBanner?.classList.remove("show");
    });

    searchInput?.addEventListener("input", function () {
      searchQuery = searchInput.value;
      renderWorkspaces();
    });

    container?.addEventListener("click", function (event) {
      const toggleBtn = event.target.closest("[data-toggle-ws]");
      if (toggleBtn) {
        const wsId = toggleBtn.getAttribute("data-toggle-ws");
        expandedWorkspaceId = expandedWorkspaceId === wsId ? "" : wsId;
        renderWorkspaces();
        return;
      }

      const mapBtn = event.target.closest("[data-map-key]");
      if (mapBtn) {
        const key = mapBtn.getAttribute("data-map-key");
        const wsName = mapBtn.getAttribute("data-ws-name");
        const floorNum = mapBtn.getAttribute("data-floor-num");
        const selectEl = container.querySelector(`[data-floor-select="${key}"]`);
        const chosenTemplate = selectEl?.value;
        if (chosenTemplate) {
          mappings[key] = chosenTemplate;
          saveMappings();
          renderWorkspaces();
          showToast(`Mapped "${chosenTemplate}" to ${wsName} — Floor ${floorNum}.`);
        }
        return;
      }

      const unmapBtn = event.target.closest("[data-unmap-key]");
      if (unmapBtn) {
        const key = unmapBtn.getAttribute("data-unmap-key");
        const wsName = unmapBtn.getAttribute("data-ws-name");
        const floorNum = unmapBtn.getAttribute("data-floor-num");
        delete mappings[key];
        saveMappings();
        renderWorkspaces();
        showToast(`Removed template mapping from ${wsName} — Floor ${floorNum}.`);
      }
    });

    loadMappings();
    renderWorkspaces();
  }

  document.addEventListener("DOMContentLoaded", initTemplateMapping);
})();
