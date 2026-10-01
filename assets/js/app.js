// ============================================================
// RHU Rizal Appointment System - Core App JS
// ============================================================

// ============================================================
// TOAST
// ============================================================
function showToast(message, type = "success", title = null) {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    document.body.appendChild(container);
  }

  const icons = {
    success: "fa-circle-check",
    error: "fa-circle-xmark",
    warning: "fa-triangle-exclamation",
    info: "fa-circle-info",
  };
  const titles = {
    success: "Success",
    error: "Error",
    warning: "Warning",
    info: "Info",
  };

  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fa-solid ${icons[type] || icons.info} toast-icon"></i>
    <div class="toast-body">
      <div class="toast-title">${title || titles[type]}</div>
      <div class="toast-msg">${message}</div>
    </div>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("hide");
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ============================================================
// MODAL
// ============================================================
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    const sidebar = document.querySelector(".sidebar");
    const sidebarOverlay = document.querySelector(".sidebar-overlay");
    sidebar?.classList.remove("open");
    sidebarOverlay?.classList.remove("show");
    el.classList.add("show");
    document.body.style.overflow = "hidden";
  }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove("show");
    document.body.style.overflow = "";
  }
}
function closeAllModals() {
  document.querySelectorAll(".modal-overlay.show").forEach((m) => {
    m.classList.remove("show");
  });
  document.body.style.overflow = "";
}

// Close modal on overlay click or close button
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) closeAllModals();
  if (e.target.closest("[data-modal-close]")) {
    const id = e.target.closest("[data-modal-close]").dataset.modalClose;
    closeModal(id);
  }
});

// Close modal on Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeAllModals();
});

// ============================================================
// SIDEBAR
// ============================================================
function initSidebar() {
  const toggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");
  if (!toggle || !sidebar) return;

  toggle.addEventListener("click", () => {
    sidebar.classList.toggle("open");
    overlay?.classList.toggle("show");
  });
  overlay?.addEventListener("click", () => {
    sidebar.classList.remove("open");
    overlay.classList.remove("show");
  });
}

// ============================================================
// TABS
// ============================================================
function initTabs() {
  document.querySelectorAll(".tab-list").forEach((list) => {
    list.querySelectorAll(".tab-item").forEach((tab) => {
      tab.addEventListener("click", () => {
        const target = tab.dataset.tab;
        const parent = tab.closest(".tab-wrapper") || document;

        list
          .querySelectorAll(".tab-item")
          .forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");

        parent
          .querySelectorAll(".tab-pane")
          .forEach((p) => p.classList.remove("active"));
        const pane = parent.querySelector(`#${target}`);
        if (pane) pane.classList.add("active");
      });
    });
  });
}

// ============================================================
// STATUS BADGE
// ============================================================
function statusBadge(status) {
  const map = {
    Pending: "badge-pending",
    Approved: "badge-approved",
    Completed: "badge-completed",
    Rejected: "badge-rejected",
    Cancelled: "badge-cancelled",
    Active: "badge-active",
    Inactive: "badge-inactive",
  };
  return `<span class="badge ${map[status] || "badge-pending"}">${status}</span>`;
}

// ============================================================
// CALENDAR WIDGET
// ============================================================
class RHUCalendar {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    this.options = options;
    const now = new Date();
    this.date = new Date(now.getFullYear(), now.getMonth(), 1);
    this.selectedDate = null;
    this.bookedDates = [];
    this.closedDates = [];
    this.today = new Date();
    this.today.setHours(0, 0, 0, 0);
    this.render();
  }

  render() {
    if (!this.container) return;
    const year = this.date.getFullYear();
    const month = this.date.getMonth();
    const monthName = new Date(year, month, 1).toLocaleString("default", {
      month: "long",
      year: "numeric",
    });
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    let daysHTML = "";

    // Empty cells
    for (let i = 0; i < firstDay; i++) {
      daysHTML += `<div class="cal-day empty"></div>`;
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
      const dateObj = new Date(year, month, d);
      const isSunday = dateObj.getDay() === 0;
      const isSaturday = dateObj.getDay() === 6;
      const isWeekend = isSunday || isSaturday;
      const isPast = dateObj < this.today;
      const isToday = dateObj.getTime() === this.today.getTime();
      const isBooked = this.bookedDates.includes(dateStr);

      // Check if custom filter / doctor duty day filter marks this date closed
      let isOffDuty = false;
      if (typeof this.options.isDateAvailable === 'function') {
        isOffDuty = !this.options.isDateAvailable(dateStr, dateObj);
      }

      const isHolidayOrClosed = this.closedDates.includes(dateStr);
      const isClosed = isWeekend || isHolidayOrClosed || isOffDuty;
      const isSelected = this.selectedDate === dateStr;

      let cls = "cal-day";
      let title = "";
      if (isPast) {
        cls += " past";
        title = "Past date";
      } else if (isWeekend) {
        cls += " closed weekend";
        title = "Closed on Weekends (Saturday & Sunday)";
      } else if (isOffDuty) {
        cls += " closed doctor-off";
        title = "Doctor off duty on this day";
      } else if (isHolidayOrClosed) {
        cls += " closed holiday";
        title = "Clinic closed / Holiday";
      } else if (isBooked) {
        cls += " booked";
        title = "Fully booked";
      } else {
        cls += " available";
        title = "Available for appointment";
      }
      if (isToday) cls += " today";
      if (isSelected) cls += " selected";

      daysHTML += `<div class="${cls}" data-date="${dateStr}" title="${title}">${d}</div>`;
    }

    this.container.innerHTML = `
      <div class="calendar-wrapper">
        <div class="calendar-header">
          <button class="cal-nav" id="cal-prev"><i class="fa-solid fa-chevron-left"></i></button>
          <h4>${monthName}</h4>
          <button class="cal-nav" id="cal-next"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
        <div class="calendar-grid">
          <div class="calendar-days-header">
            ${days.map((d) => `<span>${d}</span>`).join("")}
          </div>
          <div class="calendar-days">${daysHTML}</div>
        </div>
        <div class="calendar-legend">
          <div class="legend-item"><div class="legend-dot green"></div> Available</div>
          <div class="legend-item"><div class="legend-dot red"></div> Fully Booked</div>
          <div class="legend-item"><div class="legend-dot gray"></div> Closed (Weekends/Holidays)</div>
          <div class="legend-item"><div class="legend-dot blue"></div> Selected</div>
        </div>
      </div>
    `;

    // Events
    this.container.querySelector("#cal-prev")?.addEventListener("click", () => {
      this.date.setMonth(this.date.getMonth() - 1);
      this.render();
    });
    this.container.querySelector("#cal-next")?.addEventListener("click", () => {
      this.date.setMonth(this.date.getMonth() + 1);
      this.render();
    });
    this.container
      .querySelectorAll(".cal-day")
      .forEach((day) => {
        day.addEventListener("click", () => {
          const d = day.dataset.date;
          if (!d || day.classList.contains("empty") || day.classList.contains("past")) return;
          if (day.classList.contains("closed")) {
            if (day.classList.contains("doctor-off")) {
              showToast("The selected doctor is off duty on this day.", "warning");
            } else if (day.classList.contains("weekend")) {
              showToast("The RHU is closed on weekends (Saturday & Sunday). Please choose a weekday.", "warning");
            } else {
              showToast("The RHU is closed on this date.", "warning");
            }
            return;
          }
          if (day.classList.contains("booked")) {
            showToast("This date is fully booked.", "warning");
            return;
          }
          this.selectedDate = d;
          if (this.options.onSelect) this.options.onSelect(d);
          this.render();
        });
      });
  }

  setDoctorFilter(dutyDays) {
    if (!dutyDays || dutyDays.length === 0) {
      this.options.isDateAvailable = null;
    } else {
      const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
      this.options.isDateAvailable = (dateStr, dateObj) => {
        const dayName = dayNames[dateObj.getDay()];
        return dutyDays.includes(dayName);
      };
    }
    this.render();
  }
}

// ============================================================
// SET SIDEBAR ACTIVE
// ============================================================
function setSidebarActive() {
  const path = window.location.pathname;
  document.querySelectorAll(".nav-item").forEach((item) => {
    const href = item.getAttribute("href");
    if (href && path.endsWith(href.split("/").pop())) {
      item.classList.add("active");
    }
  });
}

// ============================================================
// FORMAT DATE
// ============================================================
function formatDate(dateStr) {
  if (!dateStr) return "—";
  const datePart = String(dateStr).split(/[T ]/)[0]; // strip time from DATETIME strings
  const d = new Date(datePart + "T00:00:00");
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}
function formatTime(timeStr) {
  if (!timeStr) return "—";
  const [h, m] = timeStr.split(":");
  const hour = parseInt(h);
  const ampm = hour >= 12 ? "PM" : "AM";
  const hr = hour % 12 || 12;
  return `${hr}:${m} ${ampm}`;
}

// ============================================================
// PAGE LOADER
// ============================================================
function hideLoader() {
  const loader = document.getElementById("page-loader");
  if (loader && !loader.classList.contains("loaded")) {
    loader.classList.add("loaded");
  }
}

function showLoader(subtext) {
  const loader = document.getElementById("page-loader");
  if (loader) {
    if (subtext) {
      const sub = loader.querySelector(".loader-sub");
      if (sub) sub.textContent = subtext;
    }
    loader.classList.remove("loaded");
  }
}

// Automatically hide loader on window load
window.addEventListener("load", hideLoader);

// Safety fallback: ensure loader dismisses within 1.2s max if load already fired or slow CDN
setTimeout(hideLoader, 1200);

// Global form submit handler for smooth loading feedback
document.addEventListener("submit", (e) => {
  const form = e.target;
  if (!form.hasAttribute("data-no-loader")) {
    showLoader("Processing request...");
  }
});

// ============================================================
// INIT
// ============================================================
document.addEventListener("DOMContentLoaded", () => {
  initSidebar();
  initTabs();
  setSidebarActive();
});

