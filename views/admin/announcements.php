<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('admin');

$admin     = getAdminSession();
$adminName = htmlspecialchars($admin['full_name'] ?? $admin['username']);
$initial   = strtoupper(substr($adminName, 0, 1) ?: 'A');

$pdo = db();

// Fetch announcements
$announcements = $pdo->query("
    SELECT a.*, d.name AS doctor_name, s.name AS service_name
    FROM announcements a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN services s ON s.id = a.service_id
    ORDER BY a.is_active DESC, a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors and services for dropdowns
$doctors  = $pdo->query("SELECT id, name, specialty FROM doctors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$services = $pdo->query("SELECT id, name FROM services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$flashError   = getFlash('announcement_error');
$flashSuccess = getFlash('announcement_success');

$pageTitle = 'Announcements & Clinic Advisories – RHU Rizal Admin';
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
          <h4>Announcements &amp; Unavailability Advisories</h4>
          <p>Broadcast service suspensions, doctor leaves, and health notices to patients</p>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm" onclick="openAddModal()">
          <i class="fa-solid fa-plus"></i> New Announcement
        </button>
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <?php if ($flashSuccess): ?>
      <div class="alert alert-success mb-3">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($flashSuccess['message']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($flashError): ?>
      <div class="alert alert-danger mb-3">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= htmlspecialchars($flashError['message']) ?></div>
      </div>
      <?php endif; ?>

      <!-- Announcements Table Card -->
      <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
          <h5><i class="fa-solid fa-bullhorn text-primary"></i> Published Advisories (<?= count($announcements) ?>)</h5>
          <div style="font-size:12px;color:var(--gray-600);">
            Active notices are displayed on Patient Dashboard, Booking Form, and Public Schedule Board.
          </div>
        </div>

        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Title &amp; Notice</th>
                <th>Target Reference</th>
                <th>Date Period</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($announcements)): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <i class="fa-solid fa-bullhorn"></i>
                    <p>No announcements or unavailability advisories yet.</p>
                  </div>
                </td>
              </tr>
              <?php else: foreach ($announcements as $ann): ?>
              <tr>
                <td>
                  <?php if ($ann['type'] === 'doctor_unavailable'): ?>
                    <span class="badge badge-danger"><i class="fa-solid fa-user-doctor"></i> Doctor Offline</span>
                  <?php elseif ($ann['type'] === 'service_unavailable'): ?>
                    <span class="badge badge-warning"><i class="fa-solid fa-briefcase-medical"></i> Service Suspended</span>
                  <?php else: ?>
                    <span class="badge badge-info"><i class="fa-solid fa-circle-info"></i> General Advisory</span>
                  <?php endif; ?>
                </td>
                <td style="max-width:320px;">
                  <strong style="color:var(--gray-800);display:block;"><?= htmlspecialchars($ann['title']) ?></strong>
                  <div style="font-size:12px;color:var(--gray-600);line-height:1.4;margin-top:2px;">
                    <?= htmlspecialchars($ann['message']) ?>
                  </div>
                </td>
                <td>
                  <?php if ($ann['doctor_name']): ?>
                    <div><i class="fa-solid fa-stethoscope text-primary"></i> <strong><?= htmlspecialchars($ann['doctor_name']) ?></strong></div>
                  <?php elseif ($ann['service_name']): ?>
                    <div><i class="fa-solid fa-hand-holding-medical text-warning"></i> <strong><?= htmlspecialchars($ann['service_name']) ?></strong></div>
                  <?php else: ?>
                    <span style="color:var(--gray-500);font-size:12px;">All RHU Patients</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--gray-700);">
                  <?php if ($ann['start_date'] || $ann['end_date']): ?>
                    <?= htmlspecialchars($ann['start_date'] ?: 'Now') ?> to <?= htmlspecialchars($ann['end_date'] ?: 'Ongoing') ?>
                  <?php else: ?>
                    <span style="color:var(--gray-500);">Always Active</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int)$ann['is_active'] === 1): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-circle-dot"></i> Broadcasting</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">Disabled</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                  <div style="display:inline-flex;gap:6px;">
                    <!-- Toggle Status -->
                    <form method="POST" action="<?= BASE_URL ?>/actions/admin/toggle-announcement.php" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                      <button type="submit" class="btn <?= (int)$ann['is_active'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm" title="Toggle Broadcast">
                        <i class="fa-solid <?= (int)$ann['is_active'] === 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                      </button>
                    </form>

                    <!-- Edit -->
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick='openEditModal(<?= json_encode($ann) ?>)' title="Edit Notice">
                      <i class="fa-solid fa-pen"></i>
                    </button>

                    <!-- Delete -->
                    <form method="POST" action="<?= BASE_URL ?>/actions/admin/delete-announcement.php" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                      </button>
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

<!-- Add / Edit Modal -->
<div class="modal-overlay" id="announcementModal">
  <div class="modal-box md">
    <div class="modal-header">
      <h5 id="modalTitle"><i class="fa-solid fa-bullhorn text-primary"></i> Create Announcement</h5>
      <button class="modal-close" data-modal-close="announcementModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/actions/admin/save-announcement.php" id="announcementForm">
      <?= csrfField() ?>
      <input type="hidden" name="id" id="annId" value="0">
      
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Advisory Type *</label>
          <select class="form-select" name="type" id="annType" required onchange="handleTypeChange()">
            <option value="general_advisory">General Public Advisory / Clinic Notice</option>
            <option value="doctor_unavailable">Doctor Unavailability / Physician On Leave</option>
            <option value="service_unavailable">Service Suspension / Equipment Maintenance</option>
          </select>
        </div>

        <div class="form-group" id="doctorGroup" style="display:none;">
          <label class="form-label"><i class="fa-solid fa-user-doctor"></i> Specific Doctor Affected</label>
          <select class="form-select" name="doctor_id" id="annDoctorId">
            <option value="">-- Select Doctor (Optional) --</option>
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> – <?= htmlspecialchars($d['specialty']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" id="serviceGroup" style="display:none;">
          <label class="form-label"><i class="fa-solid fa-briefcase-medical"></i> Specific Service Affected</label>
          <select class="form-select" name="service_id" id="annServiceId">
            <option value="">-- Select Service (Optional) --</option>
            <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Title / Headline *</label>
          <input type="text" class="form-control" name="title" id="annTitle" placeholder="e.g. Dr. Roberto Cruz On Leave this Friday" required />
        </div>

        <div class="form-group">
          <label class="form-label">Message / Instructions *</label>
          <textarea class="form-control" name="message" id="annMessage" rows="3" placeholder="Describe the details and instructions for visiting patients..." required></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date (Optional)</label>
            <input type="date" class="form-control" name="start_date" id="annStartDate" />
          </div>
          <div class="form-group">
            <label class="form-label">End Date (Optional)</label>
            <input type="date" class="form-control" name="end_date" id="annEndDate" />
          </div>
        </div>

        <div class="form-group mb-0">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_active" id="annIsActive" value="1" checked />
            <strong>Broadcast Immediately (Make Visible to Patients)</strong>
          </label>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close="announcementModal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Save &amp; Publish</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  function handleTypeChange() {
    const type = document.getElementById('annType').value;
    document.getElementById('doctorGroup').style.display = (type === 'doctor_unavailable') ? 'block' : 'none';
    document.getElementById('serviceGroup').style.display = (type === 'service_unavailable') ? 'block' : 'none';
  }

  function openAddModal() {
    document.getElementById('annId').value = '0';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-bullhorn text-primary"></i> Create Announcement';
    document.getElementById('annType').value = 'general_advisory';
    document.getElementById('annTitle').value = '';
    document.getElementById('annMessage').value = '';
    document.getElementById('annDoctorId').value = '';
    document.getElementById('annServiceId').value = '';
    document.getElementById('annStartDate').value = '';
    document.getElementById('annEndDate').value = '';
    document.getElementById('annIsActive').checked = true;
    handleTypeChange();
    openModal('announcementModal');
  }

  function openEditModal(ann) {
    document.getElementById('annId').value = ann.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-primary"></i> Edit Announcement';
    document.getElementById('annType').value = ann.type;
    document.getElementById('annTitle').value = ann.title;
    document.getElementById('annMessage').value = ann.message;
    document.getElementById('annDoctorId').value = ann.doctor_id || '';
    document.getElementById('annServiceId').value = ann.service_id || '';
    document.getElementById('annStartDate').value = ann.start_date || '';
    document.getElementById('annEndDate').value = ann.end_date || '';
    document.getElementById('annIsActive').checked = (parseInt(ann.is_active) === 1);
    handleTypeChange();
    openModal('announcementModal');
  }
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
