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
$flashSuccess = getFlash('doctor_success');
$flashError   = getFlash('doctor_error');

$pageTitle = 'Doctor Schedule – RHU Rizal Admin';
$extraHead = <<<'CSS'
<style>
  .day-selector-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
  }
  .day-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1.5px solid var(--gray-300);
    background: var(--gray-100);
    color: var(--gray-700);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
  }
  .day-chip input[type="checkbox"] {
    display: none;
  }
  .day-chip:hover {
    border-color: var(--primary);
    background: #fff;
  }
  .day-chip.checked,
  .day-chip:has(input:checked) {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
    box-shadow: 0 2px 4px rgba(26, 107, 60, 0.25);
  }
  .schedule-preview-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--primary-light);
    color: var(--primary);
    border: 1px solid rgba(26, 107, 60, 0.25);
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12.5px;
    font-weight: 500;
  }
  .schedule-preview-badge.empty {
    background: #fdf2e9;
    color: #b9770e;
    border-color: #f5cba7;
  }
</style>
CSS;
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
        <button class="btn btn-primary btn-sm" onclick="prepareAddDoctorModal()">
          <i class="fa-solid fa-plus"></i> Add Doctor
        </button>
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <?php if ($flashSuccess): ?>
      <div class="alert alert-<?= htmlspecialchars($flashSuccess['type']) ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flashSuccess['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <?php endif; ?>
      <?php if ($flashError): ?>
      <div class="alert alert-<?= htmlspecialchars($flashError['type']) ?> alert-dismissible mb-3">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($flashError['message']) ?>
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
    <form method="post" action="<?= BASE_URL ?>/actions/admin/save-doctor.php" onsubmit="return validateDoctorForm('add')">
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
          <label class="form-label">Duty Schedule *</label>
          <div style="margin-bottom:8px;">
            <select class="form-select" id="addSchedulePreset" onchange="applySchedulePreset('add')">
              <option value="">-- Choose Quick Preset or Pick Days Below --</option>
              <option value="Mon-Sat">Monday to Saturday (Mon-Sat)</option>
              <option value="Mon-Fri">Monday to Friday (Mon-Fri)</option>
              <option value="Mon-Wed-Fri">Monday, Wednesday, Friday (Mon-Wed-Fri)</option>
              <option value="Tue-Thu">Tuesday, Thursday (Tue-Thu)</option>
              <option value="Mon-Thu">Monday to Thursday (Mon-Thu)</option>
              <option value="Wed-Fri">Wednesday, Friday (Wed-Fri)</option>
              <option value="Tue-Fri">Tuesday to Friday (Tue-Fri)</option>
              <option value="Sat">Saturday Only (Sat)</option>
              <option value="custom">Custom Days Selection</option>
            </select>
          </div>
          <div class="day-selector-group" id="addDaySelectorGroup">
            <label class="day-chip" id="addChip_Mon">
              <input type="checkbox" name="schedule_days[]" value="Mon" onchange="syncScheduleFromDays('add')">
              <span>Mon</span>
            </label>
            <label class="day-chip" id="addChip_Tue">
              <input type="checkbox" name="schedule_days[]" value="Tue" onchange="syncScheduleFromDays('add')">
              <span>Tue</span>
            </label>
            <label class="day-chip" id="addChip_Wed">
              <input type="checkbox" name="schedule_days[]" value="Wed" onchange="syncScheduleFromDays('add')">
              <span>Wed</span>
            </label>
            <label class="day-chip" id="addChip_Thu">
              <input type="checkbox" name="schedule_days[]" value="Thu" onchange="syncScheduleFromDays('add')">
              <span>Thu</span>
            </label>
            <label class="day-chip" id="addChip_Fri">
              <input type="checkbox" name="schedule_days[]" value="Fri" onchange="syncScheduleFromDays('add')">
              <span>Fri</span>
            </label>
            <label class="day-chip" id="addChip_Sat">
              <input type="checkbox" name="schedule_days[]" value="Sat" onchange="syncScheduleFromDays('add')">
              <span>Sat</span>
            </label>
          </div>
          <div class="schedule-preview-badge empty" id="addScheduleBadge">
            <i class="fa-solid fa-calendar-days"></i> Selected: <strong id="addScheduleText" style="margin-left:4px;">None selected</strong>
          </div>
          <input type="hidden" name="schedule" id="addDocSchedule" required />
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
    <form method="post" action="<?= BASE_URL ?>/actions/admin/save-doctor.php" onsubmit="return validateDoctorForm('edit')">
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
          <label class="form-label">Duty Schedule *</label>
          <div style="margin-bottom:8px;">
            <select class="form-select" id="editSchedulePreset" onchange="applySchedulePreset('edit')">
              <option value="">-- Choose Quick Preset or Pick Days Below --</option>
              <option value="Mon-Sat">Monday to Saturday (Mon-Sat)</option>
              <option value="Mon-Fri">Monday to Friday (Mon-Fri)</option>
              <option value="Mon-Wed-Fri">Monday, Wednesday, Friday (Mon-Wed-Fri)</option>
              <option value="Tue-Thu">Tuesday, Thursday (Tue-Thu)</option>
              <option value="Mon-Thu">Monday to Thursday (Mon-Thu)</option>
              <option value="Wed-Fri">Wednesday, Friday (Wed-Fri)</option>
              <option value="Tue-Fri">Tuesday to Friday (Tue-Fri)</option>
              <option value="Sat">Saturday Only (Sat)</option>
              <option value="custom">Custom Days Selection</option>
            </select>
          </div>
          <div class="day-selector-group" id="editDaySelectorGroup">
            <label class="day-chip" id="editChip_Mon">
              <input type="checkbox" name="schedule_days[]" value="Mon" onchange="syncScheduleFromDays('edit')">
              <span>Mon</span>
            </label>
            <label class="day-chip" id="editChip_Tue">
              <input type="checkbox" name="schedule_days[]" value="Tue" onchange="syncScheduleFromDays('edit')">
              <span>Tue</span>
            </label>
            <label class="day-chip" id="editChip_Wed">
              <input type="checkbox" name="schedule_days[]" value="Wed" onchange="syncScheduleFromDays('edit')">
              <span>Wed</span>
            </label>
            <label class="day-chip" id="editChip_Thu">
              <input type="checkbox" name="schedule_days[]" value="Thu" onchange="syncScheduleFromDays('edit')">
              <span>Thu</span>
            </label>
            <label class="day-chip" id="editChip_Fri">
              <input type="checkbox" name="schedule_days[]" value="Fri" onchange="syncScheduleFromDays('edit')">
              <span>Fri</span>
            </label>
            <label class="day-chip" id="editChip_Sat">
              <input type="checkbox" name="schedule_days[]" value="Sat" onchange="syncScheduleFromDays('edit')">
              <span>Sat</span>
            </label>
          </div>
          <div class="schedule-preview-badge empty" id="editScheduleBadge">
            <i class="fa-solid fa-calendar-days"></i> Selected: <strong id="editScheduleText" style="margin-left:4px;">None selected</strong>
          </div>
          <input type="hidden" name="schedule" id="editDocSchedule" required />
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
  const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  const PRESET_MAP = {
    'Mon-Sat': ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'Mon-Fri': ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    'Mon-Wed-Fri': ['Mon', 'Wed', 'Fri'],
    'Tue-Thu': ['Tue', 'Thu'],
    'Mon-Thu': ['Mon', 'Tue', 'Wed', 'Thu'],
    'Wed-Fri': ['Wed', 'Fri'],
    'Tue-Fri': ['Tue', 'Wed', 'Thu', 'Fri'],
    'Sat': ['Sat']
  };

  function applySchedulePreset(prefix) {
    const presetSelect = document.getElementById(prefix + 'SchedulePreset');
    const presetVal = presetSelect.value;
    if (!presetVal || presetVal === 'custom') {
      return;
    }
    const targetDays = PRESET_MAP[presetVal] || [];
    setDaysSelection(prefix, targetDays, presetVal);
  }

  function syncScheduleFromDays(prefix) {
    const group = document.getElementById(prefix + 'DaySelectorGroup');
    const checkedBoxes = Array.from(group.querySelectorAll('input[type="checkbox"]:checked'));
    const checkedDays = checkedBoxes.map(cb => cb.value);

    // Update visual .checked class on chips for cross-browser styling
    WEEKDAYS.forEach(day => {
      const chip = document.getElementById(prefix + 'Chip_' + day);
      if (chip) {
        const cb = chip.querySelector('input[type="checkbox"]');
        if (cb && cb.checked) {
          chip.classList.add('checked');
        } else {
          chip.classList.remove('checked');
        }
      }
    });

    const badge = document.getElementById(prefix + 'ScheduleBadge');
    const textEl = document.getElementById(prefix + 'ScheduleText');
    const hiddenInput = document.getElementById(prefix + 'DocSchedule');
    const presetSelect = document.getElementById(prefix + 'SchedulePreset');

    if (checkedDays.length === 0) {
      hiddenInput.value = '';
      textEl.textContent = 'None selected';
      badge.className = 'schedule-preview-badge empty';
      presetSelect.value = '';
      return;
    }

    // Detect matching preset
    let matchedPreset = 'custom';
    for (const [key, days] of Object.entries(PRESET_MAP)) {
      if (days.length === checkedDays.length && days.every((d, i) => d === checkedDays[i])) {
        matchedPreset = key;
        break;
      }
    }

    let formattedSchedule = '';
    if (matchedPreset !== 'custom') {
      formattedSchedule = matchedPreset;
      presetSelect.value = matchedPreset;
    } else {
      formattedSchedule = checkedDays.join('-');
      presetSelect.value = 'custom';
    }

    hiddenInput.value = formattedSchedule;
    textEl.textContent = formattedSchedule;
    badge.className = 'schedule-preview-badge';
  }

  function setDaysSelection(prefix, days, fallbackStr) {
    WEEKDAYS.forEach(day => {
      const chip = document.getElementById(prefix + 'Chip_' + day);
      if (chip) {
        const cb = chip.querySelector('input[type="checkbox"]');
        const isChecked = days.includes(day);
        if (cb) cb.checked = isChecked;
        if (isChecked) {
          chip.classList.add('checked');
        } else {
          chip.classList.remove('checked');
        }
      }
    });

    const badge = document.getElementById(prefix + 'ScheduleBadge');
    const textEl = document.getElementById(prefix + 'ScheduleText');
    const hiddenInput = document.getElementById(prefix + 'DocSchedule');
    const presetSelect = document.getElementById(prefix + 'SchedulePreset');

    // Find if days match preset
    let matchedPreset = 'custom';
    for (const [key, pDays] of Object.entries(PRESET_MAP)) {
      if (pDays.length === days.length && pDays.every((d, i) => d === days[i])) {
        matchedPreset = key;
        break;
      }
    }

    let finalSchedule = '';
    if (matchedPreset !== 'custom') {
      finalSchedule = matchedPreset;
      presetSelect.value = matchedPreset;
    } else if (days.length > 0) {
      finalSchedule = days.join('-');
      presetSelect.value = 'custom';
    } else {
      finalSchedule = fallbackStr || '';
      presetSelect.value = '';
    }

    hiddenInput.value = finalSchedule;
    if (finalSchedule) {
      textEl.textContent = finalSchedule;
      badge.className = 'schedule-preview-badge';
    } else {
      textEl.textContent = 'None selected';
      badge.className = 'schedule-preview-badge empty';
    }
  }

  function parseScheduleStringToDays(schedStr) {
    if (!schedStr) return [];
    const s = schedStr.trim();
    const lower = s.toLowerCase();

    // Check Monday to Saturday
    if (lower.includes('mon') && (lower.includes('sat') || lower.includes('saturday')) && (lower.includes('to') || lower.includes('-'))) {
      return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    }
    // Check Monday to Friday
    if (lower.includes('mon') && (lower.includes('fri') || lower.includes('friday')) && (lower.includes('to') || lower.includes('-'))) {
      return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    }
    // Check Monday to Thursday
    if (lower.includes('mon') && lower.includes('thu') && (lower.includes('to') || lower.includes('-')) && !lower.includes('wed') && !lower.includes('fri')) {
      return ['Mon', 'Tue', 'Wed', 'Thu'];
    }
    // Check Tuesday to Friday
    if (lower.includes('tue') && lower.includes('fri') && (lower.includes('to') || lower.includes('-')) && !lower.includes('thu')) {
      return ['Tue', 'Wed', 'Thu', 'Fri'];
    }

    // Parse discrete days
    const detected = [];
    if (lower.includes('mon')) detected.push('Mon');
    if (lower.includes('tue')) detected.push('Tue');
    if (lower.includes('wed')) detected.push('Wed');
    if (lower.includes('thu')) detected.push('Thu');
    if (lower.includes('fri')) detected.push('Fri');
    if (lower.includes('sat')) detected.push('Sat');
    return detected;
  }

  function validateDoctorForm(prefix) {
    const hiddenInput = document.getElementById(prefix + 'DocSchedule');
    if (!hiddenInput || !hiddenInput.value || hiddenInput.value.trim() === '') {
      alert('Please select at least one clinic duty day for the doctor schedule.');
      return false;
    }
    return true;
  }

  function prepareAddDoctorModal() {
    const preset = document.getElementById('addSchedulePreset');
    if (preset) preset.value = '';
    setDaysSelection('add', [], '');
    openModal('addDoctorModal');
  }

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
    document.getElementById("editDocAvailable").value = doc.available ? "1" : "0";
    
    const parsedDays = parseScheduleStringToDays(doc.schedule);
    setDaysSelection('edit', parsedDays, doc.schedule);

    openModal("editDoctorModal");
  }

  // Auto-open add modal if redirected back with error on add
  <?php if ($flashError && !isset($_POST['id'])): ?>
  document.addEventListener("DOMContentLoaded", () => prepareAddDoctorModal());
  <?php endif; ?>
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
