<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('patient');
$session   = getPatientSession();
$fullName  = $session['full_name'];
$initial   = strtoupper(mb_substr($fullName, 0, 1));
$patientId = (int) $session['id'];

$pdo = db();

// Fetch all this patient's appointments
$stmt = $pdo->prepare("
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status, a.created_at, a.admin_note,
           d.name AS doctor_name
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.date DESC, a.time DESC
");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll();

$flash       = getFlash('book_success');
$flashError  = getFlash('appt_error');

$pageTitle = 'My Appointments – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/user-sidebar.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px">
          <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');"><i class="fa-solid fa-bars"></i></button>
          <div><h4>My Appointments</h4><p>Manage your scheduled appointments</p></div>
        </div>
        <div class="topbar-right">
          <a href="<?= BASE_URL ?>/views/user/book-appointment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Book New</a>
          <div class="topbar-user" onclick="window.location.href='<?= BASE_URL ?>/views/user/profile.php'">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div><span class="user-name"><?= htmlspecialchars($fullName) ?></span>
          </div>
        </div>
      </header>

      <div class="page-content">
        <div class="card">
          <div class="card-header">
            <h5><i class="fa-solid fa-calendar-days"></i> Appointments</h5>
            <div class="search-bar" style="width:240px">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchInput" placeholder="Search appointments..." oninput="filterAppointments()" />
            </div>
          </div>
          <div class="card-body" style="padding:0 22px">
            <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mt-2">
              <i class="fa-solid fa-circle-check"></i><div><?= htmlspecialchars($flash['message']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="alert alert-<?= htmlspecialchars($flashError['type']) ?> mt-2">
              <i class="fa-solid fa-circle-exclamation"></i><div><?= htmlspecialchars($flashError['message']) ?></div>
            </div>
            <?php endif; ?>
            <div class="tab-list tab-wrapper" id="aptTabs">
              <div class="tab-item active" data-tab="tabAll"       onclick="setTab('All')">All</div>
              <div class="tab-item"         data-tab="tabPending"  onclick="setTab('Pending')">Pending</div>
              <div class="tab-item"         data-tab="tabApproved" onclick="setTab('Approved')">Approved</div>
              <div class="tab-item"         data-tab="tabCompleted"onclick="setTab('Completed')">Completed</div>
              <div class="tab-item"         data-tab="tabCancelled"onclick="setTab('Cancelled')">Cancelled/Rejected</div>
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead><tr><th>Appt. ID</th><th>Service</th><th>Doctor</th><th>Date &amp; Time</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="appointmentsTable">
                <?php if (empty($appointments)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No appointments found</p></div></td></tr>
                <?php else: foreach ($appointments as $a): ?>
                <tr data-status="<?= htmlspecialchars($a['status']) ?>"
                    data-search="<?= htmlspecialchars(strtolower($a['service'] . ' ' . $a['doctor_name'] . ' ' . $a['appt_no'])) ?>">
                  <td><span style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($a['appt_no']) ?></span></td>
                  <td><?= htmlspecialchars($a['service']) ?></td>
                  <td><?= htmlspecialchars($a['doctor_name'] ?? 'TBA') ?></td>
                  <td>
                    <div style="font-weight:500" class="fmt-date" data-date="<?= htmlspecialchars($a['date']) ?>"></div>
                    <div style="font-size:12px;color:#888" class="fmt-time" data-time="<?= htmlspecialchars($a['time']) ?>"></div>
                  </td>
                  <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></span></td>
                  <td><div class="actions">
                    <button class="btn btn-sm btn-info" onclick="viewAppointment(<?= $a['id'] ?>)"><i class="fa-solid fa-eye"></i></button>
                    <?php if (in_array($a['status'], ['Pending','Approved'])): ?>
                    <form method="post" action="<?= BASE_URL ?>/actions/cancel-appointment.php" style="display:inline" id="cancelForm<?= $a['id'] ?>">
                      <?= csrfField() ?>
                      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                      <button type="button" class="btn btn-sm btn-danger"
                              onclick="openCancelModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['appt_no']) ?>')">
                        <i class="fa-solid fa-calendar-xmark"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div></td>
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
      <div class="modal-header"><h5><i class="fa-solid fa-calendar-check"></i> Appointment Details</h5><button class="modal-close" data-modal-close="viewModal"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="modal-body" id="viewModalBody"></div>
      <div class="modal-footer"><button class="btn btn-secondary" data-modal-close="viewModal">Close</button></div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal-overlay" id="editModal">
    <div class="modal-box">
      <div class="modal-header"><h5><i class="fa-solid fa-pen-to-square"></i> Edit Appointment</h5><button class="modal-close" data-modal-close="editModal"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="modal-body">
        <input type="hidden" id="editId" />
        <div class="form-group"><label class="form-label">Service Type</label><select class="form-select" id="editService"></select></div>
        <div class="form-group"><label class="form-label">Doctor</label><select class="form-select" id="editDoctor"></select></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="editDate" /></div>
          <div class="form-group">
            <label class="form-label">Time</label>
            <select class="form-select" id="editTime">
              <option value="08:00">8:00 AM</option><option value="09:00">9:00 AM</option>
              <option value="10:00">10:00 AM</option><option value="11:00">11:00 AM</option>
              <option value="13:00">1:00 PM</option><option value="14:00">2:00 PM</option><option value="15:00">3:00 PM</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Reason</label><textarea class="form-control" id="editReason" rows="3"></textarea></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="editModal">Cancel</button>
        <button class="btn btn-primary" onclick="saveEdit()"><i class="fa-solid fa-save"></i> Save Changes</button>
      </div>
    </div>
  </div>

  <!-- Cancel Confirm Modal -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal-box sm">
      <div class="modal-header"><h5><i class="fa-solid fa-calendar-xmark" style="color:var(--danger)"></i> Cancel Appointment</h5><button class="modal-close" data-modal-close="deleteModal"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="modal-body">
        <div class="alert alert-warning mb-2"><i class="fa-solid fa-triangle-exclamation"></i><div>This action cannot be undone.</div></div>
        <p style="font-size:13.5px;color:#555">Are you sure you want to cancel appointment <strong id="cancelApptNo"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="deleteModal">Keep It</button>
        <button class="btn btn-danger" id="confirmCancelBtn"><i class="fa-solid fa-calendar-xmark"></i> Yes, Cancel It</button>
      </div>
    </div>
  </div>

<?php
$apptJson = json_encode(array_map(fn($a) => [
    'id'          => (int) $a['id'],
    'appt_no'     => $a['appt_no'],
    'service'     => $a['service'],
    'doctor_name' => $a['doctor_name'] ?? 'TBA',
    'date'        => $a['date'],
    'time'        => $a['time'],
    'reason'      => $a['reason'],
    'admin_note'  => $a['admin_note'] ?? '',
    'status'      => $a['status'],
    'created_at'  => substr($a['created_at'], 0, 10),
], $appointments), JSON_HEX_TAG);

$extraScripts = <<<JS
<script>
  const APPTS = {$apptJson};
  let currentFilter = 'All';

  document.querySelectorAll('.fmt-date').forEach(el => el.textContent = formatDate(el.dataset.date));
  document.querySelectorAll('.fmt-time').forEach(el => el.textContent = formatTime(el.dataset.time));
  document.querySelectorAll('.status-badge-wrap').forEach(el => el.innerHTML = statusBadge(el.dataset.status));

  function setTab(filter) {
    currentFilter = filter;
    document.getElementById('searchInput').value = '';
    filterRows();
    document.querySelectorAll('.tab-item').forEach(t => t.classList.toggle('active', t.dataset.tab === 'tab' + filter));
  }

  function filterAppointments() { filterRows(); }

  function filterRows() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#appointmentsTable tr[data-status]').forEach(row => {
      const status = row.dataset.status;
      const text   = row.dataset.search || '';
      const matchFilter = currentFilter === 'All'
        || (currentFilter === 'Cancelled' ? status === 'Cancelled' || status === 'Rejected' : status === currentFilter);
      const matchSearch = !search || text.includes(search);
      row.style.display = matchFilter && matchSearch ? '' : 'none';
    });
  }

  function viewAppointment(id) {
    const a = APPTS.find(x => x.id === id); if (!a) return;
    const adminNoteHtml = (a.status === 'Cancelled' || a.status === 'Rejected') && a.admin_note
      ? '<div class="detail-item"><div class="detail-label">Reason from Admin</div>' +
        '<div class="detail-value" style="color:var(--danger)">' + a.admin_note + '</div></div>'
      : '';
    document.getElementById('viewModalBody').innerHTML =
      '<div class="detail-list">' +
      '<div class="detail-item"><div class="detail-label">Appointment ID</div><div class="detail-value fw-600 text-primary">' + a.appt_no + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">' + a.service + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Doctor</div><div class="detail-value">' + a.doctor_name + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">' + formatDate(a.date) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Time</div><div class="detail-value">' + formatTime(a.time) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Reason</div><div class="detail-value">' + (a.reason || '-') + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">' + statusBadge(a.status) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Booked On</div><div class="detail-value">' + formatDate(a.created_at) + '</div></div>' +
      adminNoteHtml +
      '</div>';
    openModal('viewModal');
  }

  function openCancelModal(id, apptNo) {
    document.getElementById('cancelApptNo').textContent = apptNo;
    document.getElementById('confirmCancelBtn').onclick = function() {
      document.getElementById('cancelForm' + id).submit();
    };
    openModal('deleteModal');
  }
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
