<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
$admin = getAdminSession();
$adminName = htmlspecialchars($admin['full_name']);
$initial   = strtoupper($adminName[0]);

$pdo = db();

// Fetch all appointments (date + partial info for calendar rendering)
$calAppts = $pdo->query("
    SELECT a.id, a.appt_no, a.date, a.time, a.service, a.status,
           p.full_name AS patient_name, COALESCE(d.name,'TBA') AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    ORDER BY a.date, a.time
")->fetchAll();

$pageTitle = 'Appointment Calendar – RHU Rizal Admin';
$extraHead = <<<'HTML'
<style>
  .big-calendar { background:#fff; border-radius:var(--radius); border:1px solid var(--gray-200); overflow:hidden; }
  .big-cal-header { background:var(--primary); color:#fff; padding:20px 28px; display:flex; align-items:center; justify-content:space-between; }
  .big-cal-header h3 { font-size:20px; font-weight:700; }
  .big-cal-days-header { display:grid; grid-template-columns:repeat(7,1fr); background:var(--gray-100); border-bottom:1px solid var(--gray-200); }
  .big-cal-days-header span { text-align:center; padding:12px; font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; }
  .big-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); }
  .big-cal-cell { min-height:100px; border-right:1px solid var(--gray-200); border-bottom:1px solid var(--gray-200); padding:8px; cursor:pointer; transition:var(--transition); position:relative; }
  .big-cal-cell:nth-child(7n) { border-right:none; }
  .big-cal-cell:hover:not(.empty-cell):not(.closed-cell) { background:var(--primary-light); }
  .big-cal-cell.empty-cell { background:var(--gray-100); cursor:default; }
  .big-cal-cell.today-cell { background:#e8f5ee; }
  .big-cal-cell.booked-cell { background:#fdecea; }
  .big-cal-cell.closed-cell { background:#f5f5f5; cursor:not-allowed; }
  .cell-date { font-size:13px; font-weight:600; color:var(--gray-700); margin-bottom:4px; }
  .big-cal-cell.today-cell .cell-date { color:var(--primary); font-size:15px; }
  .big-cal-cell.booked-cell .cell-date { color:var(--danger); }
  .big-cal-cell.closed-cell .cell-date { color:var(--gray-400); }
  .cell-event { font-size:10px; padding:2px 6px; border-radius:4px; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .event-pending { background:#fef3cd; color:#856404; }
  .event-approved { background:#d1e7dd; color:#0a5230; }
  .event-completed { background:#cff4fc; color:#055160; }
</style>
HTML;
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/../../includes/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h4>Appointment Calendar</h4>
          <p>View all appointments on the calendar</p>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <div class="grid-2 mb-3" style="grid-template-columns:1fr auto;align-items:start;">
        <div></div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <div class="legend-item"><div class="legend-dot green"></div> Available</div>
          <div class="legend-item"><div class="legend-dot red"></div> Fully Booked</div>
          <div class="legend-item"><div class="legend-dot gray"></div> Closed / Holiday</div>
          <div class="legend-item" style="background:#fef3cd;padding:3px 8px;border-radius:4px;font-size:11px;color:#856404;">Pending</div>
          <div class="legend-item" style="background:#d1e7dd;padding:3px 8px;border-radius:4px;font-size:11px;color:#0a5230;">Approved</div>
        </div>
      </div>

      <div class="big-calendar">
        <div class="big-cal-header">
          <button class="cal-nav" id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
          <h3 id="calMonthTitle">April 2026</h3>
          <button class="cal-nav" id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
        <div class="big-cal-days-header">
          <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>
        <div class="big-cal-grid" id="calGrid"></div>
      </div>
    </div>
  </div>
</div>

<!-- Day Detail Modal -->
<div class="modal-overlay" id="dayModal">
  <div class="modal-box">
    <div class="modal-header">
      <h5 id="dayModalTitle"><i class="fa-solid fa-calendar-day"></i> Appointments</h5>
      <button class="modal-close" data-modal-close="dayModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="dayModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close="dayModal">Close</button>
    </div>
  </div>
</div>

<?php
$cal_json = json_encode($calAppts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$extraScripts = <<<SCRIPTS
<script>
  const ALL_APPTS = {$cal_json};
  let currentDate = new Date();
  currentDate.setDate(1);
  const today = new Date();
  today.setHours(0,0,0,0);

  function renderCalendar() {
    const year  = currentDate.getFullYear();
    const month = currentDate.getMonth();
    document.getElementById("calMonthTitle").textContent = new Date(year, month, 1).toLocaleString("default", { month:"long", year:"numeric" });

    const firstDay    = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let html = "";
    for (let i = 0; i < firstDay; i++) html += '<div class="big-cal-cell empty-cell"></div>';

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = year + "-" + String(month+1).padStart(2,"0") + "-" + String(d).padStart(2,"0");
      const dateObj = new Date(year, month, d);
      const isSunday = dateObj.getDay() === 0;
      const isToday  = dateObj.getTime() === today.getTime();
      const dayAppts = ALL_APPTS.filter(a => a.date === dateStr);

      let cellClass = "big-cal-cell";
      if (isSunday) cellClass += " closed-cell";
      else if (dayAppts.length >= 4) cellClass += " booked-cell";
      else if (isToday) cellClass += " today-cell";

      const eventsHTML = dayAppts.slice(0,3).map(a => {
        const cls = a.status==="Approved" ? "event-approved" : a.status==="Completed" ? "event-completed" : "event-pending";
        return '<div class="cell-event ' + cls + '">' + a.patient_name.split(" ")[0] + ' \u2013 ' + a.service.split(" ")[0] + '</div>';
      }).join("");
      const moreHTML = dayAppts.length > 3 ? '<div class="cell-event" style="background:#e9ecef;color:#555;">+' + (dayAppts.length-3) + ' more</div>' : "";

      html += '<div class="' + cellClass + '" onclick="' + (!isSunday ? "showDay('" + dateStr + "')" : "") + '">'
            + '<div class="cell-date">' + d + (isToday ? ' <small style="font-size:9px;color:var(--primary);">TODAY</small>' : "") + '</div>'
            + eventsHTML + moreHTML
            + (isSunday ? '<div style="font-size:10px;color:var(--gray-400);">Closed</div>' : "")
            + '</div>';
    }

    document.getElementById("calGrid").innerHTML = html;
  }

  function showDay(dateStr) {
    const appts = ALL_APPTS.filter(a => a.date === dateStr);
    document.getElementById("dayModalTitle").innerHTML = '<i class="fa-solid fa-calendar-day"></i> ' + formatDate(dateStr);
    document.getElementById("dayModalBody").innerHTML = appts.length === 0
      ? '<div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No appointments on this date</p></div>'
      : appts.map(a => `
          <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--gray-100);align-items:center;">
            <div style="width:40px;height:40px;background:var(--primary-light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0;">
              <i class="fa-solid fa-user"></i>
            </div>
            <div style="flex:1;">
              <p style="font-size:13px;font-weight:600;">\${a.patient_name} <span style="font-size:11px;color:#888;">(\${a.appt_no})</span></p>
              <p style="font-size:12px;color:#888;">\${a.service} &middot; \${a.doctor_name} &middot; \${formatTime(a.time)}</p>
            </div>
            <div>\${statusBadge(a.status)}</div>
          </div>`).join("");
    openModal("dayModal");
  }

  document.getElementById("prevMonth").addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); });
  document.getElementById("nextMonth").addEventListener("click", () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); });

  renderCalendar();
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
