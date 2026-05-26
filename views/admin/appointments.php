<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');
$session  = getAdminSession();
$adminName = $session['full_name'] ?? $session['username'];
$initial   = strtoupper(mb_substr($adminName, 0, 1));
$csrf      = csrfField();

$pdo = db();

$appointments = $pdo->query("
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status, a.created_at,
           p.full_name AS patient_name, p.patient_no,
           d.name AS doctor_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    ORDER BY a.date DESC, a.time DESC
")->fetchAll();

$pendingCount = count(array_filter($appointments, fn($a) => $a['status'] === 'Pending'));

$flash      = getFlash('appt_success');
$flashError = getFlash('appt_error');

$pageTitle = 'Manage Appointments – RHU Rizal Admin';
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
          <h4>Manage Appointments</h4>
          <p>View, approve, and manage patient appointments</p>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-user" onclick="window.location.href='<?= BASE_URL ?>/views/admin/profile.php'">
          <div class="avatar"><?= htmlspecialchars($initial) ?></div>
          <span class="user-name"><?= htmlspecialchars($adminName) ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <?php if ($flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-2">
        <i class="fa-solid fa-circle-check"></i><div><?= htmlspecialchars($flash['message']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($flashError): ?>
      <div class="alert alert-<?= htmlspecialchars($flashError['type']) ?> mb-2">
        <i class="fa-solid fa-circle-exclamation"></i><div><?= htmlspecialchars($flashError['message']) ?></div>
      </div>
      <?php endif; ?>
      <!-- Filter & Search -->
      <div class="card mb-3">
        <div class="card-body" style="padding:16px 22px;">
          <div class="flex-between">
            <div class="tab-list" style="margin-bottom:0;border:none;gap:6px;" id="statusTabs">
              <div class="tab-item active" onclick="setFilter('All', this)">All</div>
              <div class="tab-item" onclick="setFilter('Pending', this)">Pending <span id="pendingCount" class="nav-badge" style="position:static;margin-left:4px;"><?= $pendingCount ?></span></div>
              <div class="tab-item" onclick="setFilter('Approved', this)">Approved</div>
              <div class="tab-item" onclick="setFilter('Completed', this)">Completed</div>
              <div class="tab-item" onclick="setFilter('Rejected', this)">Rejected</div>
              <div class="tab-item" onclick="setFilter('Cancelled', this)">Cancelled</div>
            </div>
            <div class="search-bar" style="width:240px;">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchInput" placeholder="Search appointments..." oninput="filterTable()" />
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="card-header">
          <h5><i class="fa-solid fa-calendar-days"></i> Appointments List</h5>
          <span id="recordCount" style="font-size:12px;color:#888;"></span>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Appt. ID</th><th>Patient</th><th>Service</th><th>Doctor</th><th>Date & Time</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="appointmentsTable">
              <?php if (empty($appointments)): ?>
              <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No appointments found</p></div></td></tr>
              <?php else: foreach ($appointments as $a): ?>
              <tr data-status="<?= htmlspecialchars($a['status']) ?>"
                  data-search="<?= htmlspecialchars(strtolower($a['patient_name'].' '.$a['service'].' '.($a['doctor_name']??'').' '.$a['appt_no'])) ?>">
                <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($a['appt_no']) ?></span></td>
                <td>
                  <div style="font-weight:500;"><?= htmlspecialchars($a['patient_name']) ?></div>
                  <div style="font-size:11px;color:#888;"><?= htmlspecialchars($a['patient_no']) ?></div>
                </td>
                <td><?= htmlspecialchars($a['service']) ?></td>
                <td><?= htmlspecialchars($a['doctor_name'] ?? 'TBA') ?></td>
                <td>
                  <div style="font-weight:500;" class="fmt-date" data-date="<?= htmlspecialchars($a['date']) ?>"></div>
                  <div style="font-size:12px;color:#888;" class="fmt-time" data-time="<?= htmlspecialchars($a['time']) ?>"></div>
                </td>
                <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></span></td>
                <td>
                  <div class="actions">
                    <button class="btn btn-sm btn-info" onclick="viewAppointment(<?= $a['id'] ?>)"><i class="fa-solid fa-eye"></i></button>
                    <?php if ($a['status'] === 'Pending'): ?>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="status" value="Approved">
                      <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="status" value="Rejected">
                      <button type="submit" class="btn btn-sm btn-danger" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                    <?php elseif ($a['status'] === 'Approved'): ?>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="status" value="Completed">
                      <button type="submit" class="btn btn-sm btn-success" title="Mark Complete"><i class="fa-solid fa-circle-check"></i></button>
                    </form>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="status" value="Cancelled">
                      <button type="submit" class="btn btn-sm btn-danger" title="Cancel"><i class="fa-solid fa-ban"></i></button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
  <div class="modal-box">
    <div class="modal-header">
      <h5><i class="fa-solid fa-calendar-check"></i> Appointment Details</h5>
      <button class="modal-close" data-modal-close="viewModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="viewBody"></div>
    <div class="modal-footer" id="viewActions"></div>
  </div>
</div>

<!-- Reschedule Modal -->
<div class="modal-overlay" id="reschedModal">
  <div class="modal-box sm">
    <div class="modal-header">
      <h5><i class="fa-solid fa-calendar-days"></i> Reschedule Appointment</h5>
      <button class="modal-close" data-modal-close="reschedModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php">
      <?= $csrf ?>
      <input type="hidden" name="appointment_id" id="reschedId" />
      <input type="hidden" name="status" value="Approved" />
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">New Date</label>
          <input type="date" class="form-control" name="new_date" id="reschedDate" />
        </div>
        <div class="form-group">
          <label class="form-label">New Time</label>
          <select class="form-select" name="new_time" id="reschedTime">
            <option value="08:00">8:00 AM</option>
            <option value="09:00">9:00 AM</option>
            <option value="10:00">10:00 AM</option>
            <option value="11:00">11:00 AM</option>
            <option value="13:00">1:00 PM</option>
            <option value="14:00">2:00 PM</option>
            <option value="15:00">3:00 PM</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Reschedule</label>
          <textarea class="form-control" name="note" rows="2" placeholder="Optional note..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close="reschedModal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calendar-check"></i> Reschedule</button>
      </div>
    </form>
  </div>
</div>


<?php
$appts_json = json_encode($appointments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$csrf_js    = json_encode(csrfToken());
$extraScripts = <<<SCRIPTS
<script>
  const APPTS = {$appts_json};
  const CSRF_TOKEN = {$csrf_js};
  let currentFilter = "All";

  function setFilter(filter, el) {
    currentFilter = filter;
    document.querySelectorAll("#statusTabs .tab-item").forEach(t => t.classList.remove("active"));
    el.classList.add("active");
    filterTable();
  }

  function filterTable() {
    const search = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#appointmentsTable tr[data-status]");
    let visible = 0;
    rows.forEach(row => {
      const matchFilter = currentFilter === "All" || row.dataset.status === currentFilter;
      const matchSearch = !search || (row.dataset.search || "").includes(search);
      row.style.display = (matchFilter && matchSearch) ? "" : "none";
      if (matchFilter && matchSearch) visible++;
    });
    const rc = document.getElementById("recordCount");
    if (rc) rc.textContent = visible + " records";
  }

  function viewAppointment(id) {
    const a = APPTS.find(x => x.id == id);
    if (!a) return;
    document.getElementById("viewBody").innerHTML = `
      <div class="detail-list">
        <div class="detail-item"><div class="detail-label">Appointment ID</div><div class="detail-value fw-600 text-primary">\${a.appt_no}</div></div>
        <div class="detail-item"><div class="detail-label">Patient Name</div><div class="detail-value">\${a.patient_name}</div></div>
        <div class="detail-item"><div class="detail-label">Patient No</div><div class="detail-value">\${a.patient_no}</div></div>
        <div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">\${a.service}</div></div>
        <div class="detail-item"><div class="detail-label">Doctor</div><div class="detail-value">\${a.doctor_name || 'TBA'}</div></div>
        <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">\${formatDate(a.date)}</div></div>
        <div class="detail-item"><div class="detail-label">Time</div><div class="detail-value">\${formatTime(a.time)}</div></div>
        <div class="detail-item"><div class="detail-label">Reason</div><div class="detail-value">\${a.reason || '-'}</div></div>
        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">\${statusBadge(a.status)}</div></div>
        <div class="detail-item"><div class="detail-label">Booked On</div><div class="detail-value">\${formatDate(a.created_at)}</div></div>
      </div>
    `;
    const acts = document.getElementById("viewActions");
    acts.innerHTML = '<button class="btn btn-secondary" data-modal-close="viewModal">Close</button>';
    if (a.status === "Pending") {
      acts.innerHTML += `
        <form method="post" action="/rhu-appointment-system/actions/admin/update-appointment.php" style="display:inline">
          <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
          <input type="hidden" name="appointment_id" value="\${a.id}">
          <input type="hidden" name="status" value="Approved">
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Approve</button>
        </form>
        <form method="post" action="/rhu-appointment-system/actions/admin/update-appointment.php" style="display:inline">
          <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
          <input type="hidden" name="appointment_id" value="\${a.id}">
          <input type="hidden" name="status" value="Rejected">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Reject</button>
        </form>`;
    }
    if (a.status === "Approved") {
      acts.innerHTML += `
        <form method="post" action="/rhu-appointment-system/actions/admin/update-appointment.php" style="display:inline">
          <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
          <input type="hidden" name="appointment_id" value="\${a.id}">
          <input type="hidden" name="status" value="Completed">
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-circle-check"></i> Mark Complete</button>
        </form>
        <form method="post" action="/rhu-appointment-system/actions/admin/update-appointment.php" style="display:inline">
          <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
          <input type="hidden" name="appointment_id" value="\${a.id}">
          <input type="hidden" name="status" value="Cancelled">
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancel</button>
        </form>`;
    }
    openModal("viewModal");
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".fmt-date[data-date]").forEach(el => el.textContent = formatDate(el.dataset.date));
    document.querySelectorAll(".fmt-time[data-time]").forEach(el => el.textContent = formatTime(el.dataset.time));
    document.querySelectorAll(".status-badge-wrap[data-status]").forEach(el => el.innerHTML = statusBadge(el.dataset.status));
    filterTable();
  });
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>