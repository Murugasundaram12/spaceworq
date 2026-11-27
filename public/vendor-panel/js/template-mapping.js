/**
 * AdminPanel — Template Mapping (Workspace Search, Floor Accordions & Template Assignment)
 */
(function () {
  "use strict";

  const SERVER = window.__SW_MAPPING_DATA__ || null;
  const IS_SERVER = Boolean(SERVER && SERVER.mode === "server");
  const CSRF_TOKEN = window.__SW_PORTAL__?.csrfToken || "";

  const PROVIDER_WORKSPACES = IS_SERVER ? (SERVER.workspaces || []) : [];
  const AVAILABLE_TEMPLATES = IS_SERVER ? (SERVER.templates || []) : [];

  let expandedWorkspaceId = PROVIDER_WORKSPACES[0] ? String(PROVIDER_WORKSPACES[0].id) : "";
  let searchQuery = "";

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function showToast(message) {
    const banner = document.getElementById("tmMapStatusBanner");
    const textEl = document.getElementById("tmMapStatusText");
    if (!banner || !textEl) return;
    textEl.textContent = message;
    banner.classList.add("show");
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

    container.innerHTML = `
      <div class="tm-ws-list">
        ${filtered
          .map(function (ws) {
            const isOpen = String(ws.id) === String(expandedWorkspaceId);
            const floorsList = ws.floorsList || [];
            const mappedCount = floorsList.filter((f) => Boolean(f.mapped_template_id)).length;

            let floorsHtml = "";
            if (isOpen) {
              if (!floorsList.length) {
                floorsHtml = '<div class="tm-floor-rows"><p class="text-muted small mb-0">No configured floors for this workspace yet.</p></div>';
              } else {
                const rows = floorsList.map(function (fl) {
                  const mappedId = fl.mapped_template_id || "";
                  const mappedName = fl.mapped_template_name || "";
                  const optionsHtml = AVAILABLE_TEMPLATES.map(function (tpl) {
                    const selected = String(tpl.id) === String(mappedId) ? "selected" : "";
                    return `<option value="${escapeHtml(tpl.id)}" ${selected}>${escapeHtml(tpl.name)}</option>`;
                  }).join("");

                  return `
                    <div class="tm-floor-row">
                      <div class="tm-floor-left">
                        <span class="tm-floor-label">${escapeHtml(fl.label || ("Floor " + fl.floor_number))}</span>
                        <span class="tm-map-badge ${mappedName ? "mapped" : "unmapped"}">
                          ${mappedName ? `Mapped: ${escapeHtml(mappedName)}` : "Unmapped"}
                        </span>
                      </div>
                      <div class="tm-floor-controls">
                        <select
                          class="tm-template-select"
                          data-floor-select="${escapeHtml(fl.id)}"
                          aria-label="Select template for ${escapeHtml(ws.name)} Floor ${fl.floor_number}"
                        >
                          ${optionsHtml}
                        </select>
                        <button
                          type="button"
                          class="btn-tm-map"
                          data-ws-id="${escapeHtml(ws.id)}"
                          data-floor-id="${escapeHtml(fl.id)}"
                          data-ws-name="${escapeHtml(ws.name)}"
                          data-floor-num="${escapeHtml(fl.floor_number)}"
                        >
                          ${mappedName ? "Update" : "Map"}
                        </button>
                      </div>
                    </div>`;
                }).join("");
                floorsHtml = `<div class="tm-floor-rows">${rows}</div>`;
              }
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
                    <span class="tm-ws-meta-pill">${floorsList.length} ${floorsList.length === 1 ? "floor" : "floors"}</span>
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

    container?.addEventListener("click", async function (event) {
      const toggleBtn = event.target.closest("[data-toggle-ws]");
      if (toggleBtn) {
        const wsId = toggleBtn.getAttribute("data-toggle-ws");
        expandedWorkspaceId = String(expandedWorkspaceId) === String(wsId) ? "" : wsId;
        renderWorkspaces();
        return;
      }

      const mapBtn = event.target.closest("[data-floor-id]");
      if (mapBtn && IS_SERVER) {
        const wsId = mapBtn.getAttribute("data-ws-id");
        const floorId = mapBtn.getAttribute("data-floor-id");
        const wsName = mapBtn.getAttribute("data-ws-name");
        const floorNum = mapBtn.getAttribute("data-floor-num");
        const selectEl = container.querySelector(`[data-floor-select="${floorId}"]`);
        const templateId = selectEl?.value;
        if (!templateId) {
          showToast("Create a layout template first before mapping a floor.");
          return;
        }

        const resp = await fetch(SERVER.routes.map, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": CSRF_TOKEN,
          },
          body: JSON.stringify({
            workspace_id: Number(wsId),
            workspace_floor_id: Number(floorId),
            layout_template_id: Number(templateId),
          }),
        });
        const data = await resp.json();
        if (!resp.ok) {
          showToast(data.message || "Unable to map template.");
          return;
        }
        showToast(data.message || `Mapped template to ${wsName} — Floor ${floorNum}.`);
        window.location.reload();
      }
    });

    renderWorkspaces();
  }

  document.addEventListener("DOMContentLoaded", initTemplateMapping);
})();
