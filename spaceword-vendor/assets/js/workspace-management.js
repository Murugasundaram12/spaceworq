/**
 * AdminPanel — Workspace Management Table, Filtering, Pagination & Add Modal
 */
(function () {
  "use strict";

  const INITIAL_WORKSPACES = [
    {
      name: "Workefella",
      code: "LTL-CHECS0007",
      city: "Chennai",
      landmark: "Ananda Hotel",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "Zone 9 Teynampet, Chennai, Tamil Nadu, India",
      floors: 7,
      createdBy: "System Administrator",
      createdAt: "9/5/2026,\n3:06:03 PM",
      updatedAt: "9/22/2026,\n8:44:26 PM",
      status: "ACTIVE",
    },
    {
      name: "smartwork",
      code: "LTL-CHECS0004",
      city: "Chennai",
      landmark: "Guindy Industrial Estate",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "Guindy, Chennai, Tamil Nadu, 600032, India",
      floors: 3,
      createdBy: "System Administrator",
      createdAt: "9/4/2026,\n11:20:10 AM",
      updatedAt: "9/22/2026,\n8:44:26 PM",
      status: "ACTIVE",
    },
    {
      name: "cove",
      code: "LTL-CHECS0002",
      city: "Chennai",
      landmark: "Tidel Park",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "Taramani, Chennai, Tamil Nadu, 600113, India",
      floors: 2,
      createdBy: "System Administrator",
      createdAt: "9/3/2026,\n2:15:40 PM",
      updatedAt: "9/22/2026,\n8:44:26 PM",
      status: "ACTIVE",
    },
    {
      name: "dc works",
      code: "LTL-CHECS0001",
      city: "Chennai",
      landmark: "OMR Perungudi",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "OMR Road, Perungudi, Chennai, Tamil Nadu, 600096, India",
      floors: 2,
      createdBy: "System Administrator",
      createdAt: "9/3/2026,\n10:12:05 AM",
      updatedAt: "9/18/2026,\n5:30:14 PM",
      status: "In Review",
    },
    {
      name: "Test",
      code: "LTL-CHECS0003",
      city: "Chennai",
      landmark: "Thiruvamyur",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "Perumbakkam, Sholinganallur, Chennai, Tamil Nadu, 600100, India",
      floors: 1,
      createdBy: "System Administrator",
      createdAt: "9/3/2026,\n3:42:24 PM",
      updatedAt: "9/3/2026,\n6:35:28 PM",
      status: "In Review",
    },
    {
      name: "awfis",
      code: "LTL-CHECS0005",
      city: "Chennai",
      landmark: "APOLLO HOSPITALS PERUNGUDI",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address:
        "Damodaran Street, Kannammapet, Ward 141, Zone 10 Kodambakkam, Chennai, Tamil Nadu, 600022, India",
      floors: 3,
      createdBy: "System Administrator",
      createdAt: "9/5/2026,\n1:35:53 PM",
      updatedAt: "9/22/2026,\n8:44:26 PM",
      status: "ACTIVE",
    },
    {
      name: "wework",
      code: "LTL-CHECS0006",
      city: "Chennai",
      landmark: "APOLLO HOSPITALS PERUNGUDI",
      category: "Long-term Leasing",
      subcategory: "Coworking Space",
      address: "APOLLO HOSPITALS",
      floors: 7,
      createdBy: "System Administrator",
      createdAt: "9/5/2026,\n1:47:32 PM",
      updatedAt: "9/22/2026,\n8:44:26 PM",
      status: "ACTIVE",
    },
  ];

  const state = {
    workspaces: INITIAL_WORKSPACES.slice(),
    category: "all",
    rowsPerPage: 10,
    page: 1,
  };

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function getFilteredWorkspaces() {
    if (state.category === "all") return state.workspaces;
    return state.workspaces.filter(function (ws) {
      return ws.category === state.category;
    });
  }

  function getStatusBadgeMarkup(status) {
    if (status === "ACTIVE") {
      return '<span class="wm-status-badge wm-status-active">ACTIVE</span>';
    }
    if (status === "In Review") {
      return '<span class="wm-status-badge wm-status-review">In Review</span>';
    }
    return `<span class="wm-status-badge wm-status-inactive">${escapeHtml(status)}</span>`;
  }

  function getCategoryPillClass(category) {
    if (category === "Day Pass") return "wm-category-pill pill-day-pass";
    if (category === "Meetings & Events") return "wm-category-pill pill-meetings";
    if (category === "Virtual Office") return "wm-category-pill pill-virtual";
    return "wm-category-pill";
  }

  function updateSummaryChips() {
    const totalWorkspaces = state.workspaces.length;
    const totalFloors = state.workspaces.reduce(function (sum, item) {
      return sum + (Number(item.floors) || 0);
    }, 0);
    const uniqueCities = new Set(
      state.workspaces
        .map(function (item) {
          return (item.city || "").trim().toLowerCase();
        })
        .filter(Boolean)
    ).size;

    const wsCountEl = document.getElementById("summaryWorkspacesCount");
    const floorCountEl = document.getElementById("summaryFloorsCount");
    const cityCountEl = document.getElementById("summaryCitiesCount");

    if (wsCountEl) wsCountEl.textContent = `${totalWorkspaces} workspaces`;
    if (floorCountEl) floorCountEl.textContent = `${totalFloors} floors`;
    if (cityCountEl) cityCountEl.textContent = `${uniqueCities} cities`;
  }

  function renderTable() {
    const tbody = document.getElementById("workspacesTableBody");
    const shownBadge = document.getElementById("shownCountBadge");
    const pageRangeEl = document.getElementById("wmPageRange");
    const prevBtn = document.getElementById("wmPrevPageBtn");
    const nextBtn = document.getElementById("wmNextPageBtn");

    if (!tbody) return;

    const filtered = getFilteredWorkspaces();
    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / state.rowsPerPage));
    if (state.page > totalPages) state.page = totalPages;

    const startIdx = (state.page - 1) * state.rowsPerPage;
    const pageItems = filtered.slice(startIdx, startIdx + state.rowsPerPage);

    if (shownBadge) {
      shownBadge.textContent = `${total} shown`;
    }

    if (total === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="9" class="text-center py-5 text-muted">
            No workspaces found for the selected category.
          </td>
        </tr>`;
      if (pageRangeEl) pageRangeEl.textContent = "0-0 of 0";
      if (prevBtn) prevBtn.disabled = true;
      if (nextBtn) nextBtn.disabled = true;
      return;
    }

    tbody.innerHTML = pageItems
      .map(function (ws) {
        const createdLines = escapeHtml(ws.createdAt).replace(/\n/g, "<br />");
        const updatedLines = escapeHtml(ws.updatedAt).replace(/\n/g, "<br />");
        const safeCode = escapeHtml(ws.code);

        return `
          <tr>
            <td>
              <div class="wm-name-cell">
                <span class="wm-thumb" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"></path>
                  </svg>
                </span>
                <div class="wm-name-meta">
                  <span class="wm-name-title">${escapeHtml(ws.name)}</span>
                  <a href="#workspace-${safeCode}" class="wm-name-code">${safeCode}</a>
                  <span class="wm-name-sub">${escapeHtml(ws.city)}</span>
                  <span class="wm-name-sub">Near: ${escapeHtml(ws.landmark)}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="wm-category-cell">
                <span class="${getCategoryPillClass(ws.category)}">${escapeHtml(ws.category)}</span>
                <span class="wm-category-sub">${escapeHtml(ws.subcategory)}</span>
              </div>
            </td>
            <td class="wm-address-cell">
              <div class="wm-address-main">${escapeHtml(ws.address)}</div>
              <div class="wm-address-landmark">${escapeHtml(ws.landmark)}</div>
            </td>
            <td class="wm-floors-cell">${escapeHtml(ws.floors)}</td>
            <td>${escapeHtml(ws.createdBy)}</td>
            <td class="wm-date-cell">${createdLines}</td>
            <td class="wm-date-cell">${updatedLines}</td>
            <td>${getStatusBadgeMarkup(ws.status)}</td>
            <td class="wm-actions-cell">
              <div class="wm-actions-group">
                <button
                  type="button"
                  class="wm-action-btn wm-action-floors"
                  data-action="floors"
                  data-code="${safeCode}"
                  title="Manage Floors"
                  aria-label="Manage floors for ${escapeHtml(ws.name)}"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M11.99 18.54l-7.37-5.73L3 14.07l9 7 9-7-1.63-1.27-7.38 5.74zM12 16l7.36-5.73L21 9l-9-7-9 7 1.63 1.27L12 16z"></path>
                  </svg>
                </button>
                <button
                  type="button"
                  class="wm-action-btn wm-action-edit"
                  data-action="edit"
                  data-code="${safeCode}"
                  title="Edit Workspace"
                  aria-label="Edit ${escapeHtml(ws.name)}"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path>
                  </svg>
                </button>
                <button
                  type="button"
                  class="wm-action-btn wm-action-delete"
                  data-action="delete"
                  data-code="${safeCode}"
                  title="Delete Workspace"
                  aria-label="Delete ${escapeHtml(ws.name)}"
                >
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>
                  </svg>
                </button>
              </div>
            </td>
          </tr>`;
      })
      .join("");

    const from = startIdx + 1;
    const to = Math.min(startIdx + pageItems.length, total);
    if (pageRangeEl) pageRangeEl.textContent = `${from}-${to} of ${total}`;
    if (prevBtn) prevBtn.disabled = state.page <= 1;
    if (nextBtn) nextBtn.disabled = state.page >= totalPages;
  }

  /* --------------------------------------------------------------------------
     Category Filter Custom Dropdown
     -------------------------------------------------------------------------- */
  function initCategoryDropdown() {
    const wrapper = document.getElementById("categoryFilterDropdown");
    const toggleBtn = document.getElementById("categoryFilterToggle");
    const valueEl = document.getElementById("categoryFilterValue");
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

    function selectOption(opt) {
      const value = opt.getAttribute("data-value") || "all";
      options.forEach(function (item) {
        const isMatch = item === opt;
        item.classList.toggle("active", isMatch);
        item.setAttribute("aria-selected", String(isMatch));
      });
      valueEl.textContent = opt.textContent.trim();
      state.category = value;
      state.page = 1;
      closeMenu();
      renderTable();
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
     Pagination Controls
     -------------------------------------------------------------------------- */
  function initPagination() {
    const rowsSelect = document.getElementById("wmRowsPerPage");
    const prevBtn = document.getElementById("wmPrevPageBtn");
    const nextBtn = document.getElementById("wmNextPageBtn");

    rowsSelect?.addEventListener("change", function () {
      state.rowsPerPage = Number(rowsSelect.value) || 10;
      state.page = 1;
      renderTable();
    });

    prevBtn?.addEventListener("click", function () {
      if (state.page > 1) {
        state.page--;
        renderTable();
      }
    });

    nextBtn?.addEventListener("click", function () {
      const totalPages = Math.ceil(getFilteredWorkspaces().length / state.rowsPerPage);
      if (state.page < totalPages) {
        state.page++;
        renderTable();
      }
    });
  }

  /* --------------------------------------------------------------------------
     New Workspace Modal Form
     -------------------------------------------------------------------------- */
  function formatCurrentTimestamp() {
    const now = new Date();
    const datePart = `${now.getMonth() + 1}/${now.getDate()}/${now.getFullYear()},`;
    const timePart = now.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      second: "2-digit",
      hour12: true,
    });
    return `${datePart}\n${timePart}`;
  }

  const WORKSPACE_TYPE_META = {
    "Long-term Leasing": {
      description:
        "Rent dedicated seats and private or managed office space for long-term usage.",
      theme: "",
    },
    "Day Pass": {
      description:
        "Flexible daily hot-desking access for individuals and remote teams on demand.",
      theme: "theme-blue",
    },
    "Meetings & Events": {
      description:
        "Bookable boardrooms, conference halls, and event venues by the hour or day.",
      theme: "theme-purple",
    },
    "Virtual Office": {
      description:
        "Professional business address, mail handling, and compliance support without physical space.",
      theme: "theme-amber",
    },
  };

  const WORKING_DAYS = [
    { name: "Monday", open: "09:00", close: "18:00", closed: false },
    { name: "Tuesday", open: "09:00", close: "18:00", closed: false },
    { name: "Wednesday", open: "09:00", close: "18:00", closed: false },
    { name: "Thursday", open: "09:00", close: "18:00", closed: false },
    { name: "Friday", open: "09:00", close: "18:00", closed: false },
    { name: "Saturday", open: "10:00", close: "16:00", closed: true },
    { name: "Sunday", open: "10:00", close: "16:00", closed: true },
  ];

  const CITY_COORDS = {
    Chennai: [13.0827, 80.2707],
    Bengaluru: [12.9716, 77.5946],
    Hyderabad: [17.385, 78.4867],
    Mumbai: [19.076, 72.8777],
    Pune: [18.5204, 73.8567],
    "Delhi NCR": [28.6139, 77.209],
  };

  let mapInstance = null;
  let mapMarker = null;
  let selectedImages = [];

  /* --------------------------------------------------------------------------
     Rich Text Editor Controls
     -------------------------------------------------------------------------- */
  function initRichTextEditor() {
    const editor = document.getElementById("wsDescriptionEditor");
    const buttons = document.querySelectorAll("[data-editor-cmd]");
    if (!editor || !buttons.length) return;

    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        const cmd = btn.getAttribute("data-editor-cmd");
        editor.focus();

        if (cmd === "createLink") {
          const url = window.prompt("Enter link URL:", "https://");
          if (url && url.trim()) {
            document.execCommand("createLink", false, url.trim());
          }
          return;
        }

        document.execCommand(cmd, false, null);
      });
    });
  }

  /* --------------------------------------------------------------------------
     Working Hours Builder & Closed Toggle
     -------------------------------------------------------------------------- */
  function renderWorkingHours() {
    const container = document.getElementById("workingHoursList");
    if (!container) return;

    container.innerHTML = WORKING_DAYS.map(function (day, idx) {
      const checkedAttr = day.closed ? "checked" : "";
      const disabledAttr = day.closed ? "disabled" : "";
      return `
        <div class="wm-day-row" data-day="${day.name}">
          <div class="wm-day-header">
            <span class="wm-day-name">${day.name}</span>
            <label class="wm-closed-toggle">
              <input type="checkbox" class="wm-day-closed-cb" data-day-idx="${idx}" ${checkedAttr} />
              <span>Closed</span>
            </label>
          </div>
          <div class="wm-day-times">
            <div class="wm-field">
              <input type="time" id="whOpen_${idx}" class="wm-input" value="${day.open}" ${disabledAttr} />
              <label for="whOpen_${idx}" class="wm-float-label is-static">Opening</label>
            </div>
            <span class="wm-day-to">to</span>
            <div class="wm-field">
              <input type="time" id="whClose_${idx}" class="wm-input" value="${day.close}" ${disabledAttr} />
              <label for="whClose_${idx}" class="wm-float-label is-static">Closing</label>
            </div>
          </div>
        </div>`;
    }).join("");

    container.querySelectorAll(".wm-day-closed-cb").forEach(function (cb) {
      cb.addEventListener("change", function () {
        const idx = cb.getAttribute("data-day-idx");
        const openEl = document.getElementById(`whOpen_${idx}`);
        const closeEl = document.getElementById(`whClose_${idx}`);
        if (openEl) openEl.disabled = cb.checked;
        if (closeEl) closeEl.disabled = cb.checked;
      });
    });
  }

  /* --------------------------------------------------------------------------
     Interactive Leaflet Map & Location Sync
     -------------------------------------------------------------------------- */
  function setMapCoordinates(lat, lng, labelPrefix) {
    const latNum = Number(lat);
    const lngNum = Number(lng);
    if (Number.isNaN(latNum) || Number.isNaN(lngNum)) return;

    const latStr = latNum.toFixed(4);
    const lngStr = lngNum.toFixed(4);

    const latInput = document.getElementById("wsLatInput");
    const lngInput = document.getElementById("wsLngInput");
    const statusPill = document.getElementById("mapCoordStatus");
    const cityVal = document.getElementById("wsCityInput")?.value || "Location";

    if (latInput) latInput.value = latStr;
    if (lngInput) lngInput.value = lngStr;
    if (statusPill) {
      statusPill.textContent = `${labelPrefix || cityVal} · ${latStr}, ${lngStr}`;
    }

    if (mapInstance && mapMarker) {
      mapMarker.setLatLng([latNum, lngNum]);
      mapInstance.setView([latNum, lngNum], mapInstance.getZoom());
    }
  }

  function ensureMapInitialized() {
    const mapEl = document.getElementById("workspaceMapCanvas");
    if (!mapEl || typeof window.L === "undefined") return;

    if (!mapInstance) {
      const startLat = Number(document.getElementById("wsLatInput")?.value) || 13.0827;
      const startLng = Number(document.getElementById("wsLngInput")?.value) || 80.2707;

      mapInstance = window.L.map(mapEl).setView([startLat, startLng], 12);
      window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap",
      }).addTo(mapInstance);

      mapMarker = window.L.marker([startLat, startLng], { draggable: true }).addTo(mapInstance);

      mapMarker.on("dragend", function () {
        const pos = mapMarker.getLatLng();
        setMapCoordinates(pos.lat, pos.lng);
      });

      mapInstance.on("click", function (e) {
        setMapCoordinates(e.latlng.lat, e.latlng.lng);
      });
    } else {
      window.setTimeout(function () {
        mapInstance.invalidateSize();
      }, 150);
    }
  }

  function initLocationControls() {
    const currentLocBtn = document.getElementById("useCurrentLocationBtn");
    const citySelect = document.getElementById("wsCityInput");
    const latInput = document.getElementById("wsLatInput");
    const lngInput = document.getElementById("wsLngInput");
    const searchInput = document.getElementById("wsLocationSearchInput");

    currentLocBtn?.addEventListener("click", function () {
      if (!navigator.geolocation) {
        setMapCoordinates(13.0827, 80.2707, "Chennai");
        return;
      }

      currentLocBtn.disabled = true;
      currentLocBtn.textContent = "Locating...";
      navigator.geolocation.getCurrentPosition(
        function (position) {
          setMapCoordinates(position.coords.latitude, position.coords.longitude, "Current Location");
          currentLocBtn.disabled = false;
          currentLocBtn.textContent = "Use Current Location";
        },
        function () {
          setMapCoordinates(13.0827, 80.2707, "Chennai");
          currentLocBtn.disabled = false;
          currentLocBtn.textContent = "Use Current Location";
        },
        { timeout: 6000 }
      );
    });

    citySelect?.addEventListener("change", function () {
      const coords = CITY_COORDS[citySelect.value];
      if (coords) {
        setMapCoordinates(coords[0], coords[1], citySelect.value);
      }
    });

    [latInput, lngInput].forEach(function (input) {
      input?.addEventListener("change", function () {
        setMapCoordinates(latInput.value, lngInput.value);
      });
    });

    searchInput?.addEventListener("keydown", function (event) {
      if (event.key !== "Enter") return;
      event.preventDefault();
      const query = searchInput.value.trim().toLowerCase();
      const matchedCity = Object.keys(CITY_COORDS).find(function (city) {
        return city.toLowerCase().includes(query);
      });
      if (matchedCity) {
        if (citySelect) citySelect.value = matchedCity;
        setMapCoordinates(CITY_COORDS[matchedCity][0], CITY_COORDS[matchedCity][1], matchedCity);
      }
    });
  }

  /* --------------------------------------------------------------------------
     Workspace Image Picker (Up to 5 Images)
     -------------------------------------------------------------------------- */
  function renderSelectedImages() {
    const bar = document.getElementById("selectedImagesBar");
    const grid = document.getElementById("imagePreviewsGrid");

    if (bar) {
      bar.textContent = `Selected ${selectedImages.length} / 5`;
    }

    if (!grid) return;
    grid.innerHTML = selectedImages
      .map(function (imgObj, idx) {
        return `
          <div class="wm-preview-item">
            <img src="${imgObj.url}" alt="${escapeHtml(imgObj.name)}" />
            <button type="button" class="wm-preview-remove" data-remove-img="${idx}" aria-label="Remove image">&times;</button>
          </div>`;
      })
      .join("");
  }

  function initImageUploads() {
    const addBtn = document.getElementById("addImagesBtn");
    const cameraBtn = document.getElementById("takePhotoBtn");
    const fileInput = document.getElementById("wsImageFileInput");
    const cameraInput = document.getElementById("wsCameraFileInput");
    const grid = document.getElementById("imagePreviewsGrid");

    function handleFiles(fileList) {
      if (!fileList || !fileList.length) return;
      Array.from(fileList).forEach(function (file) {
        if (selectedImages.length >= 5) return;
        const url = URL.createObjectURL(file);
        selectedImages.push({ name: file.name, url: url });
      });
      renderSelectedImages();
    }

    addBtn?.addEventListener("click", function () {
      fileInput?.click();
    });

    cameraBtn?.addEventListener("click", function () {
      cameraInput?.click();
    });

    fileInput?.addEventListener("change", function () {
      handleFiles(fileInput.files);
      fileInput.value = "";
    });

    cameraInput?.addEventListener("change", function () {
      handleFiles(cameraInput.files);
      cameraInput.value = "";
    });

    grid?.addEventListener("click", function (event) {
      const btn = event.target.closest("[data-remove-img]");
      if (!btn) return;
      const idx = Number(btn.getAttribute("data-remove-img"));
      if (!Number.isNaN(idx) && selectedImages[idx]) {
        URL.revokeObjectURL(selectedImages[idx].url);
        selectedImages.splice(idx, 1);
        renderSelectedImages();
      }
    });
  }

  const LISTING_CONFIG_BY_TYPE = {
    "Long-term Leasing": [
      {
        code: "COWORKING_SPACE",
        title: "Coworking Space",
        description:
          "Rent dedicated seats and private cabins in fully-equipped coworking spaces.",
        placeholder: "Price per seat per month (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M20 9V7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v2a3 3 0 0 0-2 2.83V17a2 2 0 0 0 2 2v1h2v-1h12v1h2v-1a2 2 0 0 0 2-2v-5.17A3 3 0 0 0 20 9zm-13-3h10a1 1 0 0 1 1 1v2.17A3 3 0 0 0 16 12v1H8v-1a3 3 0 0 0-2-2.83V7a1 1 0 0 1 1-1z"></path></svg>',
      },
      {
        code: "MANAGED_OFFICE",
        title: "Managed Office",
        description: "Rent dedicated office space managed by a provider.",
        placeholder: "Price per seat per month (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V9l7-5 7 5v12"></path><path d="M9 21v-6h6v6"></path></svg>',
      },
      {
        code: "OFFICE_COMMERCIAL",
        title: "Office/Commercial",
        description: "Rent/Lease office space for your company.",
        placeholder: "Price per sq.ft per month (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"></path></svg>',
      },
      {
        code: "NEW_BUILDING",
        title: "New Building",
        description:
          "List a new building; mark it upcoming with a tentative availability date if not yet ready.",
        placeholder: "Tentative availability date",
        inputType: "upcoming",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M15 11V5l-3-3-3 3v2H3v14h18V11h-6zm-8 8H5v-2h2v2zm0-4H5v-2h2v2zm0-4H5V9h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V9h2v2zm0-4h-2V5h2v2zm6 12h-2v-2h2v2zm0-4h-2v-2h2v2z"></path></svg>',
      },
    ],
    "Day Pass": [
      {
        code: "HOT_DESK_DAY_PASS",
        title: "Hot Desk Day Pass",
        description: "Open seating day passes with high-speed WiFi and lounge access.",
        placeholder: "Price per day (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
      },
      {
        code: "PRIVATE_CABIN_DAY_PASS",
        title: "Private Cabin Day Pass",
        description: "Book a private day cabin for focused work or small teams.",
        placeholder: "Price per cabin per day (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>',
      },
    ],
    "Meetings & Events": [
      {
        code: "MEETING_ROOM",
        title: "Meeting Room",
        description: "Hourly or daily bookable rooms equipped with display and conferencing tools.",
        placeholder: "Price per hour (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"></path></svg>',
      },
      {
        code: "EVENT_SPACE",
        title: "Conference / Event Hall",
        description: "Host workshops, town halls, and corporate events with flexible seating.",
        placeholder: "Price per half-day (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
      },
    ],
    "Virtual Office": [
      {
        code: "BUSINESS_ADDRESS",
        title: "Business Address",
        description: "Use a prime commercial address for company registration and mail handling.",
        placeholder: "Price per month (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"></path><path d="M15 9h4a2 2 0 0 1 2 2v10"></path></svg>',
      },
      {
        code: "GST_VIRTUAL_OFFICE",
        title: "GST & Compliance Package",
        description: "Complete documentation, NOC, and utility bills for GST registration.",
        placeholder: "Price per year (INR)",
        inputType: "price",
        icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
      },
    ],
  };

  function selectListingOption(code, title) {
    const subInput = document.getElementById("wsSubcategoryInput");
    const codeEl = document.getElementById("selectedCategoryCode");
    const cards = document.querySelectorAll("#listingConfigCards .wm-listing-card");

    if (subInput && title) subInput.value = title;
    if (codeEl && code) codeEl.textContent = code;

    cards.forEach(function (card) {
      const isSelected = card.getAttribute("data-config-code") === code;
      card.classList.toggle("selected", isSelected);
      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = isSelected;
    });
  }

  function renderListingConfigCards(workspaceType, preferredTitle) {
    const container = document.getElementById("listingConfigCards");
    if (!container) return;

    const options =
      LISTING_CONFIG_BY_TYPE[workspaceType] || LISTING_CONFIG_BY_TYPE["Long-term Leasing"];

    let selectedOpt = options[0];
    if (preferredTitle) {
      const matched = options.find(function (opt) {
        return opt.title.toLowerCase() === preferredTitle.toLowerCase();
      });
      if (matched) selectedOpt = matched;
    }

    container.innerHTML = options
      .map(function (opt) {
        const isSelected = opt.code === selectedOpt.code;
        const extraField =
          opt.inputType === "upcoming"
            ? `
              <label class="wm-amenity-item">
                <input type="checkbox" class="wm-upcoming-cb" />
                <span>Mark as upcoming</span>
              </label>
              <input type="date" class="wm-listing-price-input" aria-label="Tentative availability date" />`
            : `<input type="number" min="0" class="wm-listing-price-input" placeholder="${escapeHtml(opt.placeholder)}" />`;

        return `
          <div class="wm-listing-card ${isSelected ? "selected" : ""}" data-config-code="${opt.code}" data-config-title="${escapeHtml(opt.title)}">
            <div class="wm-listing-card-top">
              <input
                type="radio"
                name="listingConfigOption"
                class="wm-listing-radio"
                value="${opt.code}"
                ${isSelected ? "checked" : ""}
              />
              <div class="wm-listing-card-content">
                <div class="wm-listing-card-heading">
                  <span class="wm-listing-card-icon" aria-hidden="true">${opt.icon}</span>
                  <span>${escapeHtml(opt.title)}</span>
                </div>
                <p class="wm-listing-card-desc">${escapeHtml(opt.description)}</p>
              </div>
            </div>
            <div class="wm-listing-card-extra">
              ${extraField}
            </div>
          </div>`;
      })
      .join("");

    container.querySelectorAll(".wm-listing-card").forEach(function (card) {
      card.addEventListener("click", function (event) {
        if (event.target.closest(".wm-listing-card-extra")) return;
        selectListingOption(
          card.getAttribute("data-config-code"),
          card.getAttribute("data-config-title")
        );
      });
    });

    selectListingOption(selectedOpt.code, selectedOpt.title);
  }

  function initTaxConfiguration() {
    const gstCheckbox = document.getElementById("wsGstEnabledInput");
    const rateWrap = document.getElementById("taxRateWrap");
    if (!gstCheckbox || !rateWrap) return;

    gstCheckbox.addEventListener("change", function () {
      rateWrap.classList.toggle("visible", gstCheckbox.checked);
    });
  }

  /* --------------------------------------------------------------------------
     Workspace Type Pills & Category Description Box
     -------------------------------------------------------------------------- */
  function setActiveWorkspaceType(typeName, preferredSubcategory) {
    const hiddenInput = document.getElementById("wsCategoryInput");
    const pills = document.querySelectorAll("#workspaceTypePills .wm-type-pill");
    const box = document.getElementById("categoryDescriptionBox");
    const textEl = document.getElementById("categoryDescriptionText");

    const normalized = WORKSPACE_TYPE_META[typeName] ? typeName : "Long-term Leasing";
    if (hiddenInput) hiddenInput.value = normalized;

    pills.forEach(function (pill) {
      const isMatch = pill.getAttribute("data-type") === normalized;
      pill.classList.toggle("active", isMatch);
      pill.setAttribute("aria-pressed", String(isMatch));
    });

    const meta = WORKSPACE_TYPE_META[normalized];
    if (box) {
      box.className = `wm-category-callout ${meta.theme}`.trim();
    }
    if (textEl) {
      textEl.textContent = meta.description;
    }

    renderListingConfigCards(normalized, preferredSubcategory);
  }

  function initWorkspaceTypePills() {
    document.querySelectorAll("#workspaceTypePills .wm-type-pill").forEach(function (pill) {
      pill.addEventListener("click", function () {
        setActiveWorkspaceType(pill.getAttribute("data-type"));
      });
    });
  }

  /* --------------------------------------------------------------------------
     Add / Edit Workspace Drawer Form
     -------------------------------------------------------------------------- */
  function resetDrawerForm() {
    const form = document.getElementById("newWorkspaceForm");
    if (form) {
      form.reset();
      form.classList.remove("was-validated");
    }

    const editCodeInput = document.getElementById("wsEditCode");
    const drawerTitle = document.getElementById("newWorkspaceDrawerLabel");
    const submitBtn = document.getElementById("wsSubmitBtn");
    const editor = document.getElementById("wsDescriptionEditor");

    if (editCodeInput) editCodeInput.value = "";
    if (drawerTitle) drawerTitle.textContent = "Add New Workspace";
    if (submitBtn) submitBtn.textContent = "Create";
    if (editor) editor.innerHTML = "";

    setActiveWorkspaceType("Long-term Leasing", "Coworking Space");
    renderWorkingHours();

    const taxRateWrap = document.getElementById("taxRateWrap");
    if (taxRateWrap) taxRateWrap.classList.remove("visible");

    selectedImages.forEach(function (img) {
      URL.revokeObjectURL(img.url);
    });
    selectedImages = [];
    renderSelectedImages();
    setMapCoordinates(13.0827, 80.2707, "Chennai");
  }

  function initNewWorkspaceDrawer() {
    const form = document.getElementById("newWorkspaceForm");
    const drawerEl = document.getElementById("newWorkspaceDrawer");
    const editCodeInput = document.getElementById("wsEditCode");
    if (!form || !drawerEl) return;

    drawerEl.addEventListener("shown.bs.offcanvas", function () {
      ensureMapInitialized();
    });

    drawerEl.addEventListener("hidden.bs.offcanvas", function () {
      resetDrawerForm();
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        const firstInvalid = form.querySelector(":invalid");
        firstInvalid?.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalid?.focus();
        return;
      }

      const name = document.getElementById("wsNameInput").value.trim();
      const category = document.getElementById("wsCategoryInput").value || "Long-term Leasing";
      const subcategory = document.getElementById("wsSubcategoryInput").value || "Coworking Space";
      const city = document.getElementById("wsCityInput").value || "Chennai";
      const landmark = document.getElementById("wsLandmarkInput").value.trim() || city;
      const address = document.getElementById("wsAddressInput").value.trim();
      const floors = Math.max(1, Number(document.getElementById("wsFloorsInput").value) || 1);
      const status = document.getElementById("wsStatusInput").value || "ACTIVE";
      const editingCode = editCodeInput?.value.trim();
      const timestamp = formatCurrentTimestamp();

      if (editingCode) {
        const existing = state.workspaces.find(function (item) {
          return item.code === editingCode;
        });
        if (existing) {
          existing.name = name;
          existing.category = category;
          existing.subcategory = subcategory;
          existing.city = city;
          existing.landmark = landmark;
          existing.address = address;
          existing.floors = floors;
          existing.status = status;
          existing.updatedAt = timestamp;
        }
      } else {
        const nextIndex = String(state.workspaces.length + 1).padStart(4, "0");
        state.workspaces.unshift({
          name: name,
          code: `LTL-CHECS${nextIndex}`,
          city: city,
          landmark: landmark,
          category: category,
          subcategory: subcategory,
          address: address,
          floors: floors,
          createdBy: "System Administrator",
          createdAt: timestamp,
          updatedAt: timestamp,
          status: status,
        });
        state.page = 1;
      }

      updateSummaryChips();
      renderTable();

      const drawerInstance = window.bootstrap?.Offcanvas.getOrCreateInstance(drawerEl);
      drawerInstance?.hide();
    });
  }

  /* --------------------------------------------------------------------------
     Row Actions (Manage Floors, Edit Workspace, Delete Workspace)
     -------------------------------------------------------------------------- */
  function initRowActions() {
    const tbody = document.getElementById("workspacesTableBody");
    const floorsModalEl = document.getElementById("floorsModal");
    const floorsModalLabel = document.getElementById("floorsModalLabel");
    const floorsModalBody = document.getElementById("floorsModalBody");
    const drawerEl = document.getElementById("newWorkspaceDrawer");
    const deleteModalEl = document.getElementById("deleteWorkspaceModal");
    const deleteNameEl = document.getElementById("deleteWorkspaceName");
    const confirmDeleteBtn = document.getElementById("confirmDeleteWorkspaceBtn");

    let pendingDeleteCode = null;

    tbody?.addEventListener("click", function (event) {
      const btn = event.target.closest(".wm-action-btn");
      if (!btn) return;

      const action = btn.getAttribute("data-action");
      const code = btn.getAttribute("data-code");
      const workspace = state.workspaces.find(function (item) {
        return item.code === code;
      });
      if (!workspace) return;

      if (action === "floors" && floorsModalEl) {
        if (floorsModalLabel) {
          floorsModalLabel.textContent = `${workspace.name} — Floors`;
        }
        if (floorsModalBody) {
          const chips = [];
          for (let i = 1; i <= Number(workspace.floors || 0); i++) {
            chips.push(`<span class="wm-floor-chip">Floor ${i}</span>`);
          }
          floorsModalBody.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-semibold text-primary small">${escapeHtml(workspace.code)}</span>
              ${getStatusBadgeMarkup(workspace.status)}
            </div>
            <p class="small text-muted mb-2">${escapeHtml(workspace.address)}</p>
            <div class="small fw-semibold text-dark">Configured Floors (${escapeHtml(workspace.floors)}):</div>
            <div class="wm-floor-chip-list">${chips.join("")}</div>`;
        }
        window.bootstrap?.Modal.getOrCreateInstance(floorsModalEl).show();
      } else if (action === "edit" && drawerEl) {
        document.getElementById("wsEditCode").value = workspace.code;
        document.getElementById("wsNameInput").value = workspace.name;
        document.getElementById("wsLandmarkInput").value = workspace.landmark;
        document.getElementById("wsFloorsInput").value = String(workspace.floors);
        document.getElementById("wsAddressInput").value = workspace.address;
        document.getElementById("wsCityInput").value = workspace.city;
        document.getElementById("wsStatusInput").value = workspace.status;
        setActiveWorkspaceType(workspace.category, workspace.subcategory);
        document.getElementById("newWorkspaceDrawerLabel").textContent = "Edit Workspace";
        document.getElementById("wsSubmitBtn").textContent = "Save Changes";
        window.bootstrap?.Offcanvas.getOrCreateInstance(drawerEl).show();
      } else if (action === "delete" && deleteModalEl) {
        pendingDeleteCode = workspace.code;
        if (deleteNameEl) deleteNameEl.textContent = workspace.name;
        window.bootstrap?.Modal.getOrCreateInstance(deleteModalEl).show();
      }
    });

    confirmDeleteBtn?.addEventListener("click", function () {
      if (!pendingDeleteCode) return;
      state.workspaces = state.workspaces.filter(function (item) {
        return item.code !== pendingDeleteCode;
      });
      pendingDeleteCode = null;
      updateSummaryChips();
      renderTable();
      window.bootstrap?.Modal.getOrCreateInstance(deleteModalEl).hide();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    updateSummaryChips();
    renderTable();
    renderWorkingHours();
    initCategoryDropdown();
    initPagination();
    initRichTextEditor();
    initLocationControls();
    initImageUploads();
    initWorkspaceTypePills();
    setActiveWorkspaceType("Long-term Leasing", "Coworking Space");
    initTaxConfiguration();
    initNewWorkspaceDrawer();
    initRowActions();
  });
})();
