<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('patient');
$session   = getPatientSession();
$patient   = $session['patient'];
$fullName  = trim($patient['first_name'] . ' ' . $patient['last_name']);
$initial   = strtoupper(mb_substr($fullName, 0, 1));
$patientId = (int) $patient['id'];

$pdo = db();

// History = Completed, Cancelled, Rejected
$stmt = $pdo->prepare("
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status,
           d.name AS doctor_name
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE a.patient_id = ? AND a.status IN ('Completed','Cancelled','Rejected')
    ORDER BY a.date DESC, a.time DESC
");
$stmt->execute([$patientId]);
$history = $stmt->fetchAll();

// Stats
$completed      = array_values(array_filter($history, fn($a) => $a['status'] === 'Completed'));
$completedCount = count($completed);
$uniqueServices = count(array_unique(array_column($completed, 'service')));
$uniqueDoctors  = count(array_unique(array_column($completed, 'doctor_name')));

$pageTitle = 'Medical History – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/user-sidebar.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px">
          <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');"><i class="fa-solid fa-bars"></i></button>
          <div><h4>Medical History</h4><p>View your past medical appointments</p></div>
        </div>
        <div class="topbar-right">
          <div class="topbar-user" onclick="window.location.href='<?= BASE_URL ?>/views/user/profile.php'">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div><span class="user-name"><?= htmlspecialchars($fullName) ?></span>
          </div>
        </div>
      </header>

      <div class="page-content">
        <div class="grid-3 mb-3" id="historyStats">
          <div class="stat-card"><div class="stat-icon primary"><i class="fa-solid fa-file-medical"></i></div><div class="stat-info"><div class="value"><?= $completedCount ?></div><div class="label">Total Visits</div></div></div>
          <div class="stat-card"><div class="stat-icon info"><i class="fa-solid fa-stethoscope"></i></div><div class="stat-info"><div class="value"><?= $uniqueServices ?></div><div class="label">Services Availed</div></div></div>
          <div class="stat-card"><div class="stat-icon success"><i class="fa-solid fa-user-doctor"></i></div><div class="stat-info"><div class="value"><?= $uniqueDoctors ?></div><div class="label">Doctors Visited</div></div></div>
        </div>

        <div class="card">
          <div class="card-header">
            <h5><i class="fa-solid fa-file-medical"></i> Medical History</h5>
            <div class="search-bar" style="width:220px">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchHistory" placeholder="Search..." oninput="renderHistory()" />
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead><tr><th>Appt. ID</th><th>Service</th><th>Doctor</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
              <tbody id="historyTable">
                <?php if (empty($history)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-file-circle-xmark"></i><p>No medical history found</p></div></td></tr>
                <?php else: foreach ($history as $a): ?>
                <tr data-search="<?= htmlspecialchars(strtolower($a['service'] . ' ' . $a['doctor_name'] . ' ' . $a['appt_no'])) ?>">
                  <td><span style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($a['appt_no']) ?></span></td>
                  <td><?= htmlspecialchars($a['service']) ?></td>
                  <td><?= htmlspecialchars($a['doctor_name'] ?? 'TBA') ?></td>
                  <td>
                    <div style="font-weight:500" class="fmt-date" data-date="<?= htmlspecialchars($a['date']) ?>"></div>
                    <div style="font-size:12px;color:#888" class="fmt-time" data-time="<?= htmlspecialchars($a['time']) ?>"></div>
                  </td>
                  <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></span></td>
                  <td><button class="btn btn-sm btn-info" onclick="viewHistory(<?= $a['id'] ?>)"><i class="fa-solid fa-eye"></i> View</button></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- View History Modal -->
  <div class="modal-overlay" id="historyModal">
    <div class="modal-box">
      <div class="modal-header">
        <h5><i class="fa-solid fa-file-medical"></i> Medical Record Details</h5>
        <button class="modal-close" data-modal-close="historyModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="historyModalBody"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="historyModal">Close</button>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Record</button>
      </div>
    </div>
  </div>

<?php
$histJson = json_encode(array_map(fn($a) => [
    'id'          => $a['id'],
    'appt_no'     => $a['appt_no'],
    'service'     => $a['service'],
    'doctor_name' => $a['doctor_name'] ?? 'TBA',
    'date'        => $a['date'],
    'time'        => $a['time'],
    'reason'      => $a['reason'],
    'status'      => $a['status'],
], $history), JSON_HEX_TAG);

$extraScripts = <<<JS
<script>
  const HISTORY = {$histJson};

  document.querySelectorAll('.fmt-date').forEach(el => el.textContent = formatDate(el.dataset.date));
  document.querySelectorAll('.fmt-time').forEach(el => el.textContent = formatTime(el.dataset.time));
  document.querySelectorAll('.status-badge-wrap').forEach(el => el.innerHTML = statusBadge(el.dataset.status));

  function renderHistory() {
    const search = document.getElementById('searchHistory').value.toLowerCase();
    document.querySelectorAll('#historyTable tr[data-search]').forEach(row => {
      row.style.display = !search || row.dataset.search.includes(search) ? '' : 'none';
    });
  }

  function viewHistory(id) {
    const a = HISTORY.find(x => x.id === id); if (!a) return;
    document.getElementById('historyModalBody').innerHTML = \`
      <div style="background:var(--primary-light);border-radius:10px;padding:16px;margin-bottom:18px;display:flex;gap:14px;align-items:center;">
        <div style="font-size:40px;color:var(--primary)"><i class="fa-solid fa-file-waveform"></i></div>
        <div>
          <p style="font-size:15px;font-weight:700;color:var(--primary)">\${a.service}</p>
          <p style="font-size:12px;color:#555">\${a.doctor_name} &middot; \${formatDate(a.date)}</p>
          <div style="margin-top:6px">\${statusBadge(a.status)}</div>
        </div>
      </div>
      <div class="detail-list">
        <div class="detail-item"><div class="detail-label">Record ID</div><div class="detail-value fw-600 text-primary">\${a.appt_no}</div></div>
        <div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">\${a.service}</div></div>
        <div class="detail-item"><div class="detail-label">Attending Doctor</div><div class="detail-value">\${a.doctor_name}</div></div>
        <div class="detail-item"><div class="detail-label">Visit Date</div><div class="detail-value">\${formatDate(a.date)}</div></div>
        <div class="detail-item"><div class="detail-label">Visit Time</div><div class="detail-value">\${formatTime(a.time)}</div></div>
        <div class="detail-item"><div class="detail-label">Chief Complaint</div><div class="detail-value">\${a.reason}</div></div>
        <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">\${statusBadge(a.status)}</div></div>
      </div>
      <div class="alert alert-info mt-2"><i class="fa-solid fa-circle-info"></i><div>This is a prototype record. In the actual system, clinical findings, diagnosis, and prescriptions will appear here.</div></div>\`;
    openModal('historyModal');
  }
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
