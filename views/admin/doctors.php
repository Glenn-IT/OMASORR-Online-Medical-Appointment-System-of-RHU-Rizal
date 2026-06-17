<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
$admin     = getAdminSession();
$adminName = htmlspecialchars($admin['full_name']);
$initial   = strtoupper($adminName[0]);
$csrf      = csrfField();

$pdo     = db();
$doctors = $pdo->query("SELECT * FROM doctors ORDER BY name ASC")->fetchAll();

// Stats
$totalDoctors     = count($doctors);
$availableDoctors = count(array_filter($doctors, fn($d) => $d['available']));

// Flash
[$successMsg, $successType] = getFlash('doctor_success');
[$errorMsg,   $errorType]   = getFlash('doctor_error');

$pageTitle = 'Doctor Schedule – RHU Rizal Admin';
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
          <h4>Doctor Schedule</h4>
          <p>Manage doctors and their availability</p>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('addDoctorModal')">
          <i class="fa-solid fa-plus"></i> Add Doctor
        </button>
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <?php if ($successMsg): ?>
      <div class="alert alert-<?= $successType ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
      <div class="alert alert-<?= $errorType ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endif; ?>

      <!-- Doctor Cards -->
      <div class="grid-3 mb-3">
        <?php foreach (array_slice($doctors, 0, 6) as $d): ?>
        <div class="card" style="overflow:visible;">
          <div class="card-body text-center" style="padding:24px;">
            <div style="width:60px;height:60px;background:<?= $d['available'] ? 'var(--primary-light)' : 'var(--gray-200)' ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:<?= $d['available'] ? 'var(--primary)' : 'var(--gray-400)' ?>;">
              <i class="fa-solid fa-user-doctor"></i>
            </div>
            <p style="font-size:14px;font-weight:700;margin-bottom:2px;"><?= htmlspecialchars($d['name']) ?></p>
            <p style="font-size:12px;color:#888;margin-bottom:8px;"><?= htmlspecialchars($d['specialty']) ?></p>
            <div style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:#888;margin-bottom:12px;">
              <i class="fa-solid fa-calendar-days" style="color:var(--primary);"></i> <?= htmlspecialchars($d['schedule']) ?>
            </div>
            <?php if ($d['available']): ?>
              <span class="badge badge-active"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Available</span>
            <?php else: ?>
              <span class="badge badge-inactive">Unavailable</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($doctors)): ?>
        <div style="grid-column:1/-1;"><div class="empty-state"><i class="fa-solid fa-user-doctor"></i><p>No doctors found</p></div></div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <h5><i class="fa-solid fa-user-doctor"></i> Doctors List</h5>
          <div class="search-bar" style="width:220px;">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="searchDoctor" placeholder="Search doctors..." oninput="filterDoctors()" />
          </div>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>#</th><th>Doctor Name</th><th>Specialty</th><th>Schedule</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="doctorTable">
              <?php if (empty($doctors)): ?>
              <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-user-doctor"></i><p>No doctors found</p></div></td></tr>
              <?php else: foreach ($doctors as $i => $d): ?>
              <tr data-search="<?= htmlspecialchars(strtolower($d['name'].' '.$d['specialty'])) ?>">
                <td><?= $i + 1 ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:14px;flex-shrink:0;">
                      <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <div style="font-weight:500;"><?= htmlspecialchars($d['name']) ?></div>
                  </div>
                </td>
                <td><?= htmlspecialchars($d['specialty']) ?></td>
                <td><i class="fa-solid fa-calendar-days" style="color:var(--primary);margin-right:6px;"></i><?= htmlspecialchars($d['schedule']) ?></td>
                <td>
                  <?php if ($d['available']): ?>
                    <span class="badge badge-active">Available</span>
                  <?php else: ?>
                    <span class="badge badge-inactive">Unavailable</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="actions">
                    <button class="btn btn-sm btn-warning" onclick="openEditModal(<?= htmlspecialchars(json_encode($d)) ?>)"><i class="fa-solid fa-pen"></i></button>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/toggle-doctor.php" style="display:inline">
                      <?= $csrf ?>
                      <input type="hidden" name="id" value="<?= $d['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $d['available'] ? 'btn-danger' : 'btn-success' ?>" title="<?= $d['available'] ? 'Mark Unavailable' : 'Mark Available' ?>">
                        <i class="fa-solid fa-<?= $d['available'] ? 'ban' : 'check' ?>"></i>
                      </button>
                    </form>
                    <form method="post" action="<?= BASE_URL ?>/actions/admin/delete-doctor.php" style="display:inline"
                          onsubmit="return confirm('Remove this doctor?')">
                      <?= $csrf ?>
                      <input type="hidden" name="id" value="<?= $d['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                    </form>
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

<!-- Add Doctor Modal -->
<div class="modal-overlay" id="addDoctorModal">
  <div class="modal-box">
    <div class="modal-header">
      <h5><i class="fa-solid fa-user-doctor"></i> Add New Doctor</h5>
      <button class="modal-close" data-modal-close="addDoctorModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/actions/admin/save-doctor.php">
      <?= $csrf ?>
      <input type="hidden" name="id" value="0">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name (with title) *</label>
          <input type="text" class="form-control" name="name" placeholder="Dr. Juan Dela Cruz" required />
        </div>
        <div class="form-group">
          <label class="form-label">Specialty *</label>
          <input type="text" class="form-control" name="specialty" placeholder="e.g. General Medicine" required />
        </div>
        <div class="form-group">
          <label class="form-label">Schedule *</label>
          <input type="text" class="form-control" name="schedule" placeholder="e.g. Mon-Wed-Fri" required />
        </div>
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select class="form-select" name="available">
            <option value="1">Available</option>
            <option value="0">Unavailable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close="addDoctorModal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Add Doctor</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal-overlay" id="editDoctorModal">
  <div class="modal-box">
    <div class="modal-header">
      <h5><i class="fa-solid fa-pen-to-square"></i> Edit Doctor</h5>
      <button class="modal-close" data-modal-close="editDoctorModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/actions/admin/save-doctor.php">
      <?= $csrf ?>
      <input type="hidden" name="id" id="editDocId" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" name="name" id="editDocName" required />
        </div>
        <div class="form-group">
          <label class="form-label">Specialty</label>
          <input type="text" class="form-control" name="specialty" id="editDocSpecialty" required />
        </div>
        <div class="form-group">
          <label class="form-label">Schedule</label>
          <input type="text" class="form-control" name="schedule" id="editDocSchedule" required />
        </div>
        <div class="form-group">
          <label class="form-label">Availability</label>
          <select class="form-select" name="available" id="editDocAvailable">
            <option value="1">Available</option>
            <option value="0">Unavailable</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close="editDoctorModal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScripts = <<<'SCRIPTS'
<script>
  function filterDoctors() {
    const search = document.getElementById("searchDoctor").value.toLowerCase();
    document.querySelectorAll("#doctorTable tr[data-search]").forEach(row => {
      row.style.display = !search || row.dataset.search.includes(search) ? "" : "none";
    });
  }

  function openEditModal(doc) {
    document.getElementById("editDocId").value       = doc.id;
    document.getElementById("editDocName").value     = doc.name;
    document.getElementById("editDocSpecialty").value = doc.specialty;
    document.getElementById("editDocSchedule").value = doc.schedule;
    document.getElementById("editDocAvailable").value = doc.available ? "1" : "0";
    openModal("editDoctorModal");
  }

  // Auto-open add modal if redirected back with error on add
  <?php if ($errorMsg && !isset($_POST['id'])): ?>
  document.addEventListener("DOMContentLoaded", () => openModal("addDoctorModal"));
  <?php endif; ?>
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
