<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
$admin     = getAdminSession();
$adminName = htmlspecialchars($admin['full_name']);
$initial   = strtoupper($adminName[0]);

$pdo = db();

$patients = $pdo->query("
    SELECT p.*, u.username, u.status
    FROM patients p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.full_name ASC
")->fetchAll();

// Per-patient appointment counts
$apptCounts = [];
$rows = $pdo->query("SELECT patient_id, COUNT(*) AS cnt FROM appointments GROUP BY patient_id")->fetchAll();
foreach ($rows as $r) { $apptCounts[$r['patient_id']] = (int)$r['cnt']; }

// JSON for view modal
$patients_json = json_encode($patients, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// Appointments per patient for modal (keyed by patient_id)
$allAppts = $pdo->query("
    SELECT a.id, a.appt_no, a.patient_id, a.service, a.date, a.status
    FROM appointments a
    ORDER BY a.date DESC
")->fetchAll();
$appts_json = json_encode($allAppts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$pageTitle = 'Patient Records – RHU Rizal Admin';
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
          <h4>Patient Records</h4>
          <p>View and manage patient information</p>
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
      <div class="card">
        <div class="card-header">
          <h5><i class="fa-solid fa-hospital-user"></i> Patient Records</h5>
          <div class="flex-gap">
            <div class="search-bar" style="width:240px;">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchPatient" placeholder="Search patients..." oninput="filterPatients()" />
            </div>
          </div>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Patient ID</th><th>Full Name</th><th>Gender</th><th>Contact</th><th>Address</th><th>Registered</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="patientsTable">
              <?php if (empty($patients)): ?>
              <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-users-slash"></i><p>No patients found</p></div></td></tr>
              <?php else: foreach ($patients as $p): ?>
              <tr data-search="<?= htmlspecialchars(strtolower($p['full_name'].' '.$p['patient_no'].' '.$p['email'].' '.$p['phone'])) ?>">
                <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($p['patient_no']) ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:14px;flex-shrink:0;">
                      <?= strtoupper($p['full_name'][0]) ?>
                    </div>
                    <div>
                      <div style="font-weight:500;"><?= htmlspecialchars($p['full_name']) ?></div>
                      <div style="font-size:11px;color:#888;"><?= htmlspecialchars($p['email'] ?? '—') ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($p['gender'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['address'] ?? '—') ?></td>
                <td><span class="fmt-date" data-date="<?= htmlspecialchars($p['created_at']) ?>"></span></td>
                <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($p['status']) ?>"></span></td>
                <td>
                  <div class="actions">
                    <button class="btn btn-sm btn-info" onclick="viewPatient(<?= $p['id'] ?>)"><i class="fa-solid fa-eye"></i></button>
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

<!-- View Patient Modal -->
<div class="modal-overlay" id="viewPatientModal">
  <div class="modal-box lg">
    <div class="modal-header">
      <h5><i class="fa-solid fa-hospital-user"></i> Patient Details</h5>
      <button class="modal-close" data-modal-close="viewPatientModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="patientModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close="viewPatientModal">Close</button>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<SCRIPTS
<script>
  const PATIENTS = {$patients_json};
  const ALL_APPTS = {$appts_json};

  function filterPatients() {
    const search = document.getElementById("searchPatient").value.toLowerCase();
    document.querySelectorAll("#patientsTable tr[data-search]").forEach(row => {
      row.style.display = !search || row.dataset.search.includes(search) ? "" : "none";
    });
  }

  function viewPatient(id) {
    const p = PATIENTS.find(x => x.id == id);
    if (!p) return;
    const patAppts = ALL_APPTS.filter(a => a.patient_id == id);

    document.getElementById("patientModalBody").innerHTML = `
      <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
        <div style="text-align:center;">
          <div style="width:70px;height:70px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:700;margin:0 auto 10px;">\${p.full_name[0].toUpperCase()}</div>
          <p style="font-weight:700;font-size:15px;">\${p.full_name}</p>
          <p style="font-size:12px;color:#888;">\${p.patient_no}</p>
          <div style="margin-top:8px;">\${statusBadge(p.status)}</div>
        </div>
        <div style="flex:1;min-width:200px;">
          <div class="detail-list">
            <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value">\${p.email || '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Phone</div><div class="detail-value">\${p.phone || '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Birthdate</div><div class="detail-value">\${p.birthdate ? formatDate(p.birthdate) : '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Gender</div><div class="detail-value">\${p.gender || '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Blood Type</div><div class="detail-value">\${p.blood_type || '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Address</div><div class="detail-value">\${p.address || '—'}</div></div>
            <div class="detail-item"><div class="detail-label">Registered</div><div class="detail-value">\${formatDate(p.created_at)}</div></div>
          </div>
        </div>
      </div>
      <hr class="divider" />
      <p style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px;">
        <i class="fa-solid fa-calendar-check" style="color:var(--primary);"></i> Appointment History (\${patAppts.length})
      </p>
      \${patAppts.length === 0
        ? '<p class="text-muted text-center">No appointments yet</p>'
        : patAppts.map(a => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);">
              <div>
                <span style="font-size:12px;font-weight:600;color:var(--primary);">\${a.appt_no}</span>
                <span style="font-size:12px;color:#555;margin-left:8px;">\${a.service}</span>
                <span style="font-size:12px;color:#888;margin-left:8px;">\${formatDate(a.date)}</span>
              </div>
              <div>\${statusBadge(a.status)}</div>
            </div>`).join("")
      }
    `;
    openModal("viewPatientModal");
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".fmt-date[data-date]").forEach(el => el.textContent = formatDate(el.dataset.date));
    document.querySelectorAll(".status-badge-wrap[data-status]").forEach(el => el.innerHTML = statusBadge(el.dataset.status));
  });
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
