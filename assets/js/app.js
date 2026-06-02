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

// Close modal on overlay click
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) closeAllModals();
  if (e.target.closest("[data-modal-close]")) {
    const id = e.target.closest("[data-modal-close]").dataset.modalClose;
    closeModal(id);
  }
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
      const isPast = dateObj < this.today;
      const isToday = dateObj.getTime() === this.today.getTime();
      const isBooked = this.bookedDates.includes(dateStr);
      const isClosed = this.closedDates.includes(dateStr) || isSunday;
      const isSat = isSaturday;
      const isSelected = this.selectedDate === dateStr;

      let cls = "cal-day";
      if (isPast) cls += " past";
      else if (isClosed) cls += " closed";
      else if (isBooked) cls += " booked";
      else cls += " available";
      if (isToday) cls += " today";
      if (isSelected) cls += " selected";
      if (isSat && !isPast && !isClosed) cls += " available";

      daysHTML += `<div class="${cls}" data-date="${dateStr}">${d}</div>`;
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
          <div class="legend-item"><div class="legend-dot gray"></div> Closed</div>
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
      .querySelectorAll(".cal-day.available, .cal-day.today")
      .forEach((day) => {
        day.addEventListener("click", () => {
          const d = day.dataset.date;
          if (
            !d ||
            day.classList.contains("past") ||
            day.classList.contains("closed") ||
            day.classList.contains("booked")
          )
            return;
          this.selectedDate = d;
          if (this.options.onSelect) this.options.onSelect(d);
          this.render();
        });
      });
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
  const d = new Date(dateStr + "T00:00:00");
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
// INIT
// ============================================================
document.addEventListener("DOMContentLoaded", () => {
  initSidebar();
  initTabs();
  setSidebarActive();
});
