/**
 * AdminPanel — Template Management (Interactive Floor Canvas & Template CRUD)
 */
(function () {
  "use strict";

  const STORAGE_KEY = "adminpanel_layout_templates";

  const ELEMENT_PRESETS = {
    desk: { label: "Desk Bay", width: 120, height: 72, seats: 4, color: "#dbeafe" },
    meeting: { label: "Meeting Room", width: 168, height: 108, seats: 8, color: "#ede9fe" },
    cabin: { label: "Private Cabin", width: 144, height: 96, seats: 6, color: "#dcfce7" },
    lounge: { label: "Lounge Area", width: 156, height: 96, seats: 5, color: "#fef3c7" },
    reception: { label: "Reception", width: 132, height: 64, seats: 2, color: "#fce7f3" },
    booth: { label: "Phone Booth", width: 72, height: 72, seats: 1, color: "#e0f2fe" },
  };

  let templates = [];
  let elements = [];
  let selectedElementId = null;
  let loadedTemplateId = null;
  let zoomLevel = 1;

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function loadStoredTemplates() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) templates = parsed;
      }
    } catch (_) {
      templates = [];
    }
  }

  function persistTemplates() {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(templates));
    } catch (_) {
      // Ignore storage errors
    }
  }

  function setStatusBanner(message, isSuccess) {
    const banner = document.getElementById("tmStatusBanner");
    const textEl = document.getElementById("tmStatusBannerText");
    if (!banner || !textEl) return;
    textEl.textContent = message;
    banner.classList.toggle("is-success", Boolean(isSuccess));
  }

  function updateModalSummaryStrip() {
    const floorSelect = document.getElementById("tmAssignFloorInput");
    const floorVal = floorSelect?.value || "";
    const floorText = floorVal || "None";
    const strip = document.getElementById("tmCurrentSummaryStrip");
    if (strip) {
      strip.innerHTML = `<strong>Current:</strong> Assigned Floor: ${escapeHtml(floorText)} | Elements: ${elements.length} | Partner: AdminPanel`;
    }
  }

  function updateButtonsState() {
    const updateBtn = document.getElementById("updateLoadedTemplateBtn");
    if (updateBtn) {
      updateBtn.disabled = !loadedTemplateId;
    }
  }

  /* --------------------------------------------------------------------------
     Canvas Rendering & Dragging
     -------------------------------------------------------------------------- */
  function renderCanvas() {
    const board = document.getElementById("tmCanvasBoard");
    if (!board) return;

    board.innerHTML = elements
      .map(function (el) {
        const isSelected = el.id === selectedElementId;
        return `
          <div
            class="tm-canvas-element ${isSelected ? "selected" : ""}"
            data-el-id="${escapeHtml(el.id)}"
            style="left:${el.x}px;top:${el.y}px;width:${el.width}px;height:${el.height}px;background-color:${escapeHtml(el.color)};"
          >
            <span class="tm-el-title">${escapeHtml(el.label)}</span>
            <span class="tm-el-sub">${el.seats} ${el.seats === 1 ? "seat" : "seats"}</span>
          </div>`;
      })
      .join("");

    updateModalSummaryStrip();
    renderPropertiesPane();
  }

  function addElementToCanvas(typeKey) {
    const preset = ELEMENT_PRESETS[typeKey] || ELEMENT_PRESETS.desk;
    const offset = (elements.length % 6) * 24;
    const newEl = {
      id: `el-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
      type: typeKey,
      label: `${preset.label} ${elements.length + 1}`,
      x: 96 + offset,
      y: 84 + offset,
      width: preset.width,
      height: preset.height,
      seats: preset.seats,
      color: preset.color,
    };

    elements.push(newEl);
    selectedElementId = newEl.id;
    renderCanvas();
  }

  function initCanvasDragging() {
    const board = document.getElementById("tmCanvasBoard");
    if (!board) return;

    let dragState = null;

    board.addEventListener("pointerdown", function (event) {
      const targetEl = event.target.closest(".tm-canvas-element");
      if (!targetEl) {
        selectedElementId = null;
        renderCanvas();
        return;
      }

      const elId = targetEl.getAttribute("data-el-id");
      const item = elements.find((e) => e.id === elId);
      if (!item) return;

      selectedElementId = elId;
      renderCanvas();

      const freshDom = board.querySelector(`[data-el-id="${elId}"]`);
      dragState = {
        item: item,
        dom: freshDom,
        startX: event.clientX,
        startY: event.clientY,
        origX: item.x,
        origY: item.y,
      };
      freshDom?.setPointerCapture?.(event.pointerId);
    });

    window.addEventListener("pointermove", function (event) {
      if (!dragState) return;
      const dx = (event.clientX - dragState.startX) / zoomLevel;
      const dy = (event.clientY - dragState.startY) / zoomLevel;
      const snappedX = Math.max(0, Math.round((dragState.origX + dx) / 12) * 12);
      const snappedY = Math.max(0, Math.round((dragState.origY + dy) / 12) * 12);
      dragState.item.x = snappedX;
      dragState.item.y = snappedY;
      if (dragState.dom) {
        dragState.dom.style.left = `${snappedX}px`;
        dragState.dom.style.top = `${snappedY}px`;
      }
    });

    window.addEventListener("pointerup", function () {
      if (dragState) {
        dragState = null;
        renderPropertiesPane();
      }
    });
  }

  /* --------------------------------------------------------------------------
     Properties Tab Inspector
     -------------------------------------------------------------------------- */
  function renderPropertiesPane() {
    const container = document.getElementById("tmPropertiesContent");
    if (!container) return;

    const item = elements.find((e) => e.id === selectedElementId);
    if (!item) {
      container.innerHTML = `
        <div class="tm-info-banner">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span>Select an element on the canvas to edit its label, dimensions, seating, or color.</span>
        </div>`;
      return;
    }

    container.innerHTML = `
      <div class="tm-prop-field">
        <label for="propLabelInput">Element Label</label>
        <input type="text" id="propLabelInput" value="${escapeHtml(item.label)}" />
      </div>
      <div class="row g-2">
        <div class="col-6 tm-prop-field">
          <label for="propWidthInput">Width (px)</label>
          <input type="number" id="propWidthInput" min="48" max="600" step="12" value="${item.width}" />
        </div>
        <div class="col-6 tm-prop-field">
          <label for="propHeightInput">Height (px)</label>
          <input type="number" id="propHeightInput" min="48" max="600" step="12" value="${item.height}" />
        </div>
      </div>
      <div class="row g-2">
        <div class="col-6 tm-prop-field">
          <label for="propSeatsInput">Seats</label>
          <input type="number" id="propSeatsInput" min="1" max="100" value="${item.seats}" />
        </div>
        <div class="col-6 tm-prop-field">
          <label for="propColorInput">Fill Color</label>
          <input type="color" id="propColorInput" value="${escapeHtml(item.color)}" style="padding:2px 4px;" />
        </div>
      </div>
      <button type="button" id="deleteSelectedElBtn" class="btn btn-outline-danger btn-sm w-100 mt-2 fw-semibold">
        Remove Element
      </button>`;

    document.getElementById("propLabelInput")?.addEventListener("input", function (e) {
      item.label = e.target.value;
      renderCanvas();
    });
    document.getElementById("propWidthInput")?.addEventListener("change", function (e) {
      item.width = Math.max(48, Number(e.target.value) || item.width);
      renderCanvas();
    });
    document.getElementById("propHeightInput")?.addEventListener("change", function (e) {
      item.height = Math.max(48, Number(e.target.value) || item.height);
      renderCanvas();
    });
    document.getElementById("propSeatsInput")?.addEventListener("change", function (e) {
      item.seats = Math.max(1, Number(e.target.value) || 1);
      renderCanvas();
    });
    document.getElementById("propColorInput")?.addEventListener("input", function (e) {
      item.color = e.target.value;
      renderCanvas();
    });
    document.getElementById("deleteSelectedElBtn")?.addEventListener("click", function () {
      elements = elements.filter((el) => el.id !== item.id);
      selectedElementId = null;
      renderCanvas();
    });
  }

  /* --------------------------------------------------------------------------
     Saved Templates List
     -------------------------------------------------------------------------- */
  function renderTemplatesList() {
    const container = document.getElementById("tmTemplatesListContainer");
    if (!container) return;

    if (!templates.length) {
      container.innerHTML = `
        <div class="tm-info-banner">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span>No provider templates found. Design a layout and save your first template.</span>
        </div>`;
      return;
    }

    container.innerHTML = `
      <div class="tm-template-list">
        ${templates
          .map(function (tpl) {
            const isLoaded = tpl.id === loadedTemplateId;
            const floorText = tpl.assignedFloor ? ` · ${tpl.assignedFloor}` : "";
            return `
              <div class="tm-template-item ${isLoaded ? "is-loaded" : ""}">
                <div class="tm-template-item-name">${escapeHtml(tpl.name)}</div>
                <div class="tm-template-item-meta">${tpl.elements.length} elements${escapeHtml(floorText)}</div>
                <div class="tm-template-item-actions">
                  <button type="button" class="tm-mini-btn" data-tpl-action="load" data-tpl-id="${escapeHtml(tpl.id)}">Load</button>
                  <button type="button" class="tm-mini-btn" data-tpl-action="clone" data-tpl-id="${escapeHtml(tpl.id)}">Clone</button>
                  <button type="button" class="tm-mini-btn btn-danger-outline" data-tpl-action="delete" data-tpl-id="${escapeHtml(tpl.id)}">Delete</button>
                </div>
              </div>`;
          })
          .join("")}
      </div>`;
  }

  /* --------------------------------------------------------------------------
     UI Controls & Save Modal Initialization
     -------------------------------------------------------------------------- */
  function initControls() {
    // Tabs switching
    document.querySelectorAll(".tm-tab-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const tabName = btn.getAttribute("data-tm-tab");
        document.querySelectorAll(".tm-tab-btn").forEach((b) => b.classList.toggle("active", b === btn));
        document.querySelectorAll(".tm-tab-pane").forEach(function (pane) {
          pane.classList.toggle("active", pane.getAttribute("data-tm-pane") === tabName);
        });
      });
    });

    // Close / Reopen right inspector panel
    const inspector = document.getElementById("tmInspectorPanel");
    const closePanelBtn = document.getElementById("tmClosePanelBtn");
    const reopenPanelBtn = document.getElementById("tmReopenPanelBtn");

    closePanelBtn?.addEventListener("click", function () {
      inspector?.classList.add("collapsed");
      reopenPanelBtn?.classList.add("visible");
    });

    reopenPanelBtn?.addEventListener("click", function () {
      inspector?.classList.remove("collapsed");
      reopenPanelBtn?.classList.remove("visible");
    });

    // Floating Elements Toolbox
    const toolboxFab = document.getElementById("tmToolboxFab");
    const toolboxPanel = document.getElementById("tmToolboxPanel");
    toolboxFab?.addEventListener("click", function (e) {
      e.stopPropagation();
      toolboxPanel?.classList.toggle("open");
    });

    document.querySelectorAll("[data-add-element]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        addElementToCanvas(btn.getAttribute("data-add-element"));
        toolboxPanel?.classList.remove("open");
      });
    });

    document.addEventListener("click", function (e) {
      if (toolboxPanel && !toolboxPanel.contains(e.target) && e.target !== toolboxFab) {
        toolboxPanel.classList.remove("open");
      }
    });

    // Zoom Controls
    const board = document.getElementById("tmCanvasBoard");
    const zoomLabel = document.getElementById("tmZoomLabel");
    function applyZoom() {
      if (board) board.style.transform = `scale(${zoomLevel})`;
      if (zoomLabel) zoomLabel.textContent = `${Math.round(zoomLevel * 100)}%`;
    }

    document.getElementById("tmZoomInBtn")?.addEventListener("click", function () {
      zoomLevel = Math.min(1.5, Number((zoomLevel + 0.1).toFixed(2)));
      applyZoom();
    });
    document.getElementById("tmZoomOutBtn")?.addEventListener("click", function () {
      zoomLevel = Math.max(0.6, Number((zoomLevel - 0.1).toFixed(2)));
      applyZoom();
    });

    // New Design button
    document.getElementById("newDesignBtn")?.addEventListener("click", function () {
      elements = [];
      selectedElementId = null;
      loadedTemplateId = null;
      renderCanvas();
      updateButtonsState();
      renderTemplatesList();
      setStatusBanner(
        "Started a new blank template. Add elements on the canvas, then save it from this management tab.",
        false
      );
    });

    // Update Loaded Template button
    document.getElementById("updateLoadedTemplateBtn")?.addEventListener("click", function () {
      if (!loadedTemplateId) return;
      const match = templates.find((t) => t.id === loadedTemplateId);
      if (!match) return;
      match.elements = JSON.parse(JSON.stringify(elements));
      match.updatedAt = new Date().toLocaleString();
      persistTemplates();
      renderTemplatesList();
      setStatusBanner(`Updated template "${match.name}" (${elements.length} elements).`, true);
    });

    // Saved Templates List actions (Load / Clone / Delete)
    document.getElementById("tmTemplatesListContainer")?.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-tpl-action]");
      if (!btn) return;
      const action = btn.getAttribute("data-tpl-action");
      const id = btn.getAttribute("data-tpl-id");
      const tpl = templates.find((t) => t.id === id);
      if (!tpl) return;

      if (action === "load") {
        loadedTemplateId = tpl.id;
        elements = JSON.parse(JSON.stringify(tpl.elements || []));
        selectedElementId = null;
        renderCanvas();
        updateButtonsState();
        renderTemplatesList();
        setStatusBanner(`Loaded "${tpl.name}" onto the canvas. Edit and click Update Loaded Template.`, false);
      } else if (action === "clone") {
        const cloned = {
          id: `tpl-${Date.now()}`,
          name: `${tpl.name} (Copy)`,
          description: tpl.description || "",
          assignedFloor: "",
          elements: JSON.parse(JSON.stringify(tpl.elements || [])),
          updatedAt: new Date().toLocaleString(),
        };
        templates.unshift(cloned);
        persistTemplates();
        renderTemplatesList();
        setStatusBanner(`Cloned "${tpl.name}" as "${cloned.name}".`, true);
      } else if (action === "delete") {
        templates = templates.filter((t) => t.id !== id);
        if (loadedTemplateId === id) loadedTemplateId = null;
        persistTemplates();
        updateButtonsState();
        renderTemplatesList();
        setStatusBanner(`Deleted template "${tpl.name}".`, false);
      }
    });

    // Save Layout as Template Modal
    const saveModalEl = document.getElementById("saveTemplateModal");
    const nameInput = document.getElementById("tmTemplateNameInput");
    const descInput = document.getElementById("tmTemplateDescInput");
    const floorInput = document.getElementById("tmAssignFloorInput");
    const saveSubmitBtn = document.getElementById("tmSaveTemplateSubmitBtn");
    const saveForm = document.getElementById("saveTemplateForm");

    saveModalEl?.addEventListener("show.bs.modal", function () {
      updateModalSummaryStrip();
      if (saveSubmitBtn) saveSubmitBtn.disabled = !(nameInput?.value.trim());
    });

    nameInput?.addEventListener("input", function () {
      if (saveSubmitBtn) saveSubmitBtn.disabled = !nameInput.value.trim();
    });

    floorInput?.addEventListener("change", updateModalSummaryStrip);

    saveForm?.addEventListener("submit", function (event) {
      event.preventDefault();
      const name = nameInput?.value.trim();
      if (!name) return;

      const newTpl = {
        id: `tpl-${Date.now()}`,
        name: name,
        description: descInput?.value.trim() || "",
        assignedFloor: floorInput?.value || "",
        elements: JSON.parse(JSON.stringify(elements)),
        updatedAt: new Date().toLocaleString(),
      };

      templates.unshift(newTpl);
      loadedTemplateId = newTpl.id;
      persistTemplates();
      updateButtonsState();
      renderTemplatesList();
      setStatusBanner(`Saved template "${newTpl.name}" successfully.`, true);

      saveForm.reset();
      if (saveSubmitBtn) saveSubmitBtn.disabled = true;
      window.bootstrap?.Modal.getOrCreateInstance(saveModalEl).hide();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadStoredTemplates();
    renderCanvas();
    renderTemplatesList();
    updateButtonsState();
    initCanvasDragging();
    initControls();
  });
})();
