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
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status, a.created_at, a.admin_note,
           p.id AS patient_id, p.full_name AS patient_name, p.patient_no, p.birthdate, p.gender, p.address,
           d.name AS doctor_name,
           c.id AS consultation_id, c.mode_of_transaction, c.consultation_date, c.consultation_time,
           c.folder_no, c.tin_no, c.nature_of_visit, c.chief_complaints, c.height, c.weight,
           c.bp, c.rr, c.pr, c.temperature, c.history_of_illness, c.past_medical_history,
           c.pertinent_pe, c.diagnosis, c.treatment, c.lab_findings, c.age AS consult_age
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN consultation_records c ON c.appointment_id = a.id
    ORDER BY a.date DESC, a.time DESC
")->fetchAll();

$pastConsultsStmt = $pdo->query("
    SELECT c.patient_id, c.consultation_date, c.diagnosis, c.treatment, c.nature_of_visit, a.service, a.appt_no
    FROM consultation_records c
    JOIN appointments a ON a.id = c.appointment_id
    ORDER BY c.consultation_date DESC, c.id DESC
");
$pastConsultsByPatient = [];
foreach ($pastConsultsStmt->fetchAll() as $row) {
    $pastConsultsByPatient[$row['patient_id']][] = $row;
}

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
                    <button type="button" class="btn btn-sm btn-danger" title="Reject"
                            onclick="openReasonModal(<?= $a['id'] ?>, 'Rejected', false)">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                    <?php elseif ($a['status'] === 'Approved'): ?>
                    <button type="button" class="btn btn-sm btn-success" title="Complete Consultation"
                            onclick="openConsultationModal(<?= $a['id'] ?>, false)">
                      <i class="fa-solid fa-clipboard-check"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" title="Cancel"
                            onclick="openReasonModal(<?= $a['id'] ?>, 'Cancelled', false)">
                      <i class="fa-solid fa-ban"></i>
                    </button>
                    <?php elseif ($a['status'] === 'Completed'): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" title="View Consultation Record"
                            onclick="viewConsultationRecord(<?= $a['id'] ?>)">
                      <i class="fa-solid fa-file-waveform"></i>
                    </button>
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


<!-- Reason Modal (Cancel / Reject) -->
<div class="modal-overlay" id="reasonModal">
  <div class="modal-box sm">
    <div class="modal-header">
      <h5 id="reasonModalTitle"><i class="fa-solid fa-comment-dots"></i> Add Reason</h5>
      <button class="modal-close" data-modal-close="reasonModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" id="reasonForm">
      <?= $csrf ?>
      <input type="hidden" name="appointment_id" id="reasonApptId">
      <input type="hidden" name="status" id="reasonStatus">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--danger)">*</span></label>
          <textarea class="form-control" name="note" id="reasonNote" rows="3"
                    placeholder="Enter reason for this action..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close="reasonModal">Back</button>
        <button type="submit" class="btn btn-danger" id="reasonSubmitBtn">
          <i class="fa-solid fa-check"></i> Confirm
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Consultation Record Modal (Before Completing Appointment) -->
<div class="modal-overlay" id="consultationModal">
  <div class="modal-box xl">
    <div class="modal-header">
      <div>
        <h5 id="consultModalTitle"><i class="fa-solid fa-notes-medical"></i> Clinical Consultation Record</h5>
        <div style="font-size:12px;color:var(--gray-600);margin-top:2px;" id="consultModalSub">Complete patient consultation details before finalizing appointment</div>
      </div>
      <button class="modal-close" data-modal-close="consultationModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/actions/admin/update-appointment.php" id="consultationForm">
      <?= $csrf ?>
      <input type="hidden" name="appointment_id" id="consultApptId">
      <input type="hidden" name="status" value="Completed">

      <div class="modal-body" style="padding:20px 24px;max-height:calc(85vh - 130px);overflow-y:auto;">
        <!-- Section 1: Patient Information -->
        <div class="consultation-section-title"><i class="fa-solid fa-id-card"></i> Patient Identification & Demographics</div>
        <div class="grid-3 mb-2" style="gap:12px;">
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Patient Name</label>
            <input type="text" class="form-control" name="patient_name" id="consultPatientName" readonly style="background:var(--gray-100);">
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Date of Birth (DOB)</label>
            <input type="date" class="form-control" name="dob" id="consultDob" onchange="calculateConsultAge()">
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Age</label>
            <input type="number" class="form-control" name="age" id="consultAge" min="0" max="150" placeholder="Auto-calculated">
          </div>
        </div>

        <div class="grid-3 mb-2" style="gap:12px;">
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Sex / Gender</label>
            <input type="text" class="form-control" name="gender" id="consultGender" placeholder="Male / Female">
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Folder / Patient ID</label>
            <input type="text" class="form-control" name="folder_no" id="consultFolderNo" placeholder="e.g. P-001">
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">TIN # <span style="font-size:11px;color:#888;">(Optional)</span></label>
            <input type="text" class="form-control" name="tin_no" id="consultTinNo" placeholder="e.g. 000-000-000">
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" style="font-size:12px;">Address</label>
          <input type="text" class="form-control" name="address" id="consultAddress" placeholder="Barangay / Municipality">
        </div>

        <!-- Section 2: Consultation Details -->
        <div class="consultation-section-title"><i class="fa-solid fa-clock"></i> Consultation Details</div>
        <div class="grid-3 mb-2" style="gap:12px;">
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Mode of Transaction</label>
            <input type="text" class="form-control" name="mode_of_transaction" id="consultMode" value="Online appointment" required>
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Date of Consultation</label>
            <input type="date" class="form-control" name="consultation_date" id="consultDate" required>
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Time</label>
            <input type="time" class="form-control" name="consultation_time" id="consultTime" required>
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" style="font-size:12px;">Nature of Visit</label>
          <input type="text" class="form-control" name="nature_of_visit" id="consultNature" placeholder="Reason for consultation / visit">
        </div>

        <!-- Section 3: Vital Signs & Measurements -->
        <div class="consultation-section-title"><i class="fa-solid fa-heart-pulse"></i> Vital Signs & Physical Measurements</div>
        <div class="vitals-grid mb-3">
          <div class="vital-input-card">
            <label><i class="fa-solid fa-arrows-up-down"></i> Height</label>
            <input type="text" name="height" id="consultHeight" placeholder="e.g. 165 cm">
          </div>
          <div class="vital-input-card">
            <label><i class="fa-solid fa-weight-scale"></i> Weight</label>
            <input type="text" name="weight" id="consultWeight" placeholder="e.g. 60 kg">
          </div>
          <div class="vital-input-card">
            <label><i class="fa-solid fa-gauge-high"></i> BP</label>
            <input type="text" name="bp" id="consultBp" placeholder="e.g. 120/80">
          </div>
          <div class="vital-input-card">
            <label><i class="fa-solid fa-lungs"></i> RR</label>
            <input type="text" name="rr" id="consultRr" placeholder="e.g. 18 cpm">
          </div>
          <div class="vital-input-card">
            <label><i class="fa-solid fa-heart"></i> PR</label>
            <input type="text" name="pr" id="consultPr" placeholder="e.g. 72 bpm">
          </div>
          <div class="vital-input-card">
            <label><i class="fa-solid fa-temperature-half"></i> Temperature</label>
            <input type="text" name="temperature" id="consultTemp" placeholder="e.g. 36.5 °C">
          </div>
        </div>

        <!-- Section 4: Clinical History & Assessment -->
        <div class="consultation-section-title"><i class="fa-solid fa-stethoscope"></i> Clinical Assessment & Medical Management</div>
        <div class="grid-2 mb-2" style="gap:12px;">
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Chief Complaints</label>
            <textarea class="form-control" name="chief_complaints" id="consultComplaints" rows="2" placeholder="Primary complaint reported by patient..."></textarea>
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">History of Patient Illness</label>
            <textarea class="form-control" name="history_of_illness" id="consultHistoryIllness" rows="2" placeholder="History of presenting illness, onset, duration..."></textarea>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="form-label" style="font-size:12px;">Past Medical History <span style="font-size:11px;color:#888;">(Shows previous records if booked in the past)</span></label>
          <textarea class="form-control" name="past_medical_history" id="consultPastMed" rows="2" placeholder="Previous medical conditions, allergies, or past bookings..."></textarea>
        </div>

        <div class="form-group mb-2">
          <label class="form-label" style="font-size:12px;">Pertinent Physical Examination (PE)</label>
          <textarea class="form-control" name="pertinent_pe" id="consultPertinentPe" rows="2" placeholder="Pertinent physical exam findings (HEENT, Chest, Abdomen, Extremities, etc.)..."></textarea>
        </div>

        <div class="grid-2 mb-2" style="gap:12px;">
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Diagnosis <span style="color:var(--danger)">*</span></label>
            <textarea class="form-control" name="diagnosis" id="consultDiagnosis" rows="2" placeholder="Clinical diagnosis / ICD-10 impression..." required></textarea>
          </div>
          <div class="form-group" style="margin-bottom:8px;">
            <label class="form-label" style="font-size:12px;">Treatment / Prescriptions</label>
            <textarea class="form-control" name="treatment" id="consultTreatment" rows="2" placeholder="Medications, dosage, instructions, and follow-up plan..."></textarea>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="form-label" style="font-size:12px;">Laboratory Findings / Impression</label>
          <textarea class="form-control" name="lab_findings" id="consultLabFindings" rows="2" placeholder="Laboratory, radiology results or diagnostic impressions..."></textarea>
        </div>
      </div>

      <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--gray-200);display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn btn-secondary" data-modal-close="consultationModal">Cancel</button>
        <button type="submit" class="btn btn-success" id="consultSubmitBtn">
          <i class="fa-solid fa-circle-check"></i> Save & Mark Completed
        </button>
      </div>
    </form>
  </div>
</div>

<!-- View Consultation Record Modal (For Viewing / Printing Completed Records) -->
<div class="modal-overlay" id="viewConsultModal">
  <div class="modal-box xl">
    <div class="modal-header">
      <h5><i class="fa-solid fa-file-medical"></i> Official Consultation Record</h5>
      <button class="modal-close" data-modal-close="viewConsultModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="viewConsultBody" style="max-height:calc(85vh - 130px);overflow-y:auto;padding:22px;"></div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-modal-close="viewConsultModal">Close</button>
      <a href="#" id="adminConsultPrintBtn" target="_blank" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print Official ITR</a>
    </div>
  </div>
</div>

<?php
$appts_json       = json_encode($appointments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$pastConsults_json = json_encode($pastConsultsByPatient, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$csrf_js          = json_encode(csrfToken());
ob_start();
?>
<script>
  const APPTS = <?= $appts_json ?>;
  const PAST_CONSULTS = <?= $pastConsults_json ?>;
  const CSRF_TOKEN = <?= $csrf_js ?>;
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

  function calculateConsultAge() {
    const dobVal = document.getElementById('consultDob').value;
    if (!dobVal) return;
    const dob = new Date(dobVal);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    if (!isNaN(age) && age >= 0) {
      document.getElementById('consultAge').value = age;
    }
  }

  function openConsultationModal(apptId, fromViewModal) {
    if (fromViewModal) closeModal('viewModal');
    const a = APPTS.find(x => x.id == apptId);
    if (!a) return;

    document.getElementById('consultApptId').value = a.id;
    document.getElementById('consultPatientName').value = a.patient_name || '';
    document.getElementById('consultDob').value = a.birthdate || '';
    document.getElementById('consultGender').value = a.gender || '';
    document.getElementById('consultAddress').value = a.address || '';
    document.getElementById('consultFolderNo').value = a.folder_no || a.patient_no || '';
    document.getElementById('consultTinNo').value = a.tin_no || '';

    // Calculate age if not already set
    if (a.consult_age) {
      document.getElementById('consultAge').value = a.consult_age;
    } else {
      calculateConsultAge();
    }

    document.getElementById('consultMode').value = a.mode_of_transaction || 'Online appointment';
    document.getElementById('consultDate').value = a.consultation_date || a.date || new Date().toISOString().split('T')[0];
    document.getElementById('consultTime').value = (a.consultation_time || a.time || '').substring(0, 5);
    document.getElementById('consultNature').value = a.nature_of_visit || a.reason || a.service || '';

    // Vitals
    document.getElementById('consultHeight').value = a.height || '';
    document.getElementById('consultWeight').value = a.weight || '';
    document.getElementById('consultBp').value = a.bp || '';
    document.getElementById('consultRr').value = a.rr || '';
    document.getElementById('consultPr').value = a.pr || '';
    document.getElementById('consultTemp').value = a.temperature || '';

    // Clinical Assessment & Past Medical History
    document.getElementById('consultComplaints').value = a.chief_complaints || a.reason || '';
    document.getElementById('consultHistoryIllness').value = a.history_of_illness || '';
    
    // Past Medical History prefill from previous visits
    let pastHistoryText = a.past_medical_history || '';
    if (!pastHistoryText) {
      const patientPast = PAST_CONSULTS[a.patient_id];
      if (patientPast && patientPast.length > 0) {
        pastHistoryText = patientPast
          .filter(p => p.appt_no !== a.appt_no)
          .map(p => '• Date: ' + p.consultation_date + ' (' + (p.appt_no || 'Past Appt') + ') - Service: ' + (p.service || 'Consultation') +
                     (p.diagnosis ? ' | Dx: ' + p.diagnosis : '') +
                     (p.treatment ? ' | Tx: ' + p.treatment : ''))
          .join('\n');
      }
      if (!pastHistoryText) {
        pastHistoryText = 'No prior consultation history on file.';
      }
    }
    document.getElementById('consultPastMed').value = pastHistoryText;
    document.getElementById('consultPertinentPe').value = a.pertinent_pe || '';
    document.getElementById('consultDiagnosis').value = a.diagnosis || '';
    document.getElementById('consultTreatment').value = a.treatment || '';
    document.getElementById('consultLabFindings').value = a.lab_findings || '';

    openModal('consultationModal');
  }

  function viewConsultationRecord(apptId) {
    const a = APPTS.find(x => x.id == apptId);
    if (!a) return;

    const body = document.getElementById('viewConsultBody');
    body.innerHTML = `
      <div style="background:var(--primary-light);border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
        <div>
          <div style="font-size:12px;text-transform:uppercase;font-weight:700;color:var(--primary-dark);letter-spacing:0.5px;">Rural Health Unit of Rizal &middot; Clinical Consultation Sheet</div>
          <h4 style="margin-top:2px;color:var(--primary);font-size:18px;">${a.patient_name}</h4>
          <div style="font-size:13px;color:#555;">Folder/Patient No: <strong>${a.folder_no || a.patient_no}</strong> &middot; Appt: <strong>${a.appt_no}</strong></div>
        </div>
        <div style="text-align:right;">
          <span class="badge badge-completed">Completed</span>
          <div style="font-size:12px;color:#666;margin-top:4px;">Date: ${formatDate(a.consultation_date || a.date)} ${formatTime(a.consultation_time || a.time)}</div>
        </div>
      </div>

      <div class="consultation-section-title"><i class="fa-solid fa-user"></i> Patient Demographics & Transaction</div>
      <div class="detail-list mb-3">
        <div class="detail-item"><div class="detail-label">Mode of Transaction</div><div class="detail-value">${a.mode_of_transaction || 'Online appointment'}</div></div>
        <div class="detail-item"><div class="detail-label">DOB / Age / Sex</div><div class="detail-value">${a.birthdate ? formatDate(a.birthdate) : '—'} &middot; ${a.consult_age || '—'} yrs &middot; ${a.gender || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">TIN #</div><div class="detail-value">${a.tin_no || 'N/A'}</div></div>
        <div class="detail-item"><div class="detail-label">Address</div><div class="detail-value">${a.address || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Nature of Visit</div><div class="detail-value">${a.nature_of_visit || a.reason || a.service || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Attending Doctor</div><div class="detail-value">${a.doctor_name || 'RHU Physician'}</div></div>
      </div>

      <div class="consultation-section-title"><i class="fa-solid fa-heart-pulse"></i> Vital Signs & Physical Measurements</div>
      <div class="grid-3 mb-3" style="gap:10px;">
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.bp || '—'}</div><div class="label">Blood Pressure</div></div></div>
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.temperature || '—'}</div><div class="label">Temperature</div></div></div>
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.pr || '—'}</div><div class="label">Pulse Rate (PR)</div></div></div>
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.rr || '—'}</div><div class="label">Resp. Rate (RR)</div></div></div>
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.height || '—'}</div><div class="label">Height</div></div></div>
        <div class="stat-card" style="padding:10px 14px;"><div class="stat-info"><div class="value" style="font-size:16px;">${a.weight || '—'}</div><div class="label">Weight</div></div></div>
      </div>

      <div class="consultation-section-title"><i class="fa-solid fa-stethoscope"></i> Clinical Findings & Management</div>
      <div class="detail-list">
        <div class="detail-item"><div class="detail-label">Chief Complaints</div><div class="detail-value">${a.chief_complaints || a.reason || 'None stated'}</div></div>
        <div class="detail-item"><div class="detail-label">History of Present Illness</div><div class="detail-value">${a.history_of_illness || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Past Medical History</div><div class="detail-value" style="white-space:pre-line;">${a.past_medical_history || 'None recorded'}</div></div>
        <div class="detail-item"><div class="detail-label">Pertinent PE</div><div class="detail-value">${a.pertinent_pe || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Diagnosis</div><div class="detail-value fw-600 text-primary">${a.diagnosis || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Treatment / Plan</div><div class="detail-value">${a.treatment || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Lab Findings / Impression</div><div class="detail-value">${a.lab_findings || 'None'}</div></div>
      </div>
    `;
    const adminPrintBtn = document.getElementById('adminConsultPrintBtn');
    if (adminPrintBtn) {
      adminPrintBtn.href = '<?= BASE_URL ?>/views/user/print-medical-history.php?id=' + a.id + '&auto=1';
    }
    openModal('viewConsultModal');
  }

  function viewAppointment(id) {
    const a = APPTS.find(x => x.id == id);
    if (!a) return;
    const adminNoteHtml = (a.status === 'Cancelled' || a.status === 'Rejected') && a.admin_note
      ? '<div class="detail-item"><div class="detail-label">Admin Note</div>' +
        '<div class="detail-value" style="color:var(--danger)">' + a.admin_note + '</div></div>'
      : '';
    document.getElementById("viewBody").innerHTML =
      '<div class="detail-list">' +
      '<div class="detail-item"><div class="detail-label">Appointment ID</div><div class="detail-value fw-600 text-primary">' + a.appt_no + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Patient Name</div><div class="detail-value">' + a.patient_name + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Patient No</div><div class="detail-value">' + a.patient_no + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">' + a.service + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Doctor</div><div class="detail-value">' + (a.doctor_name || 'TBA') + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">' + formatDate(a.date) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Time</div><div class="detail-value">' + formatTime(a.time) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Reason</div><div class="detail-value">' + (a.reason || '-') + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">' + statusBadge(a.status) + '</div></div>' +
      '<div class="detail-item"><div class="detail-label">Booked On</div><div class="detail-value">' + formatDate(a.created_at) + '</div></div>' +
      adminNoteHtml +
      '</div>';
    const acts = document.getElementById("viewActions");
    acts.innerHTML = '<button class="btn btn-secondary" data-modal-close="viewModal">Close</button>';
    if (a.status === "Pending") {
      acts.innerHTML +=
        '<form method="post" action="/rhu-appointment-system/actions/admin/update-appointment.php" style="display:inline">' +
        '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
        '<input type="hidden" name="appointment_id" value="' + a.id + '">' +
        '<input type="hidden" name="status" value="Approved">' +
        '<button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Approve</button>' +
        '</form>' +
        '<button type="button" class="btn btn-danger" onclick="openReasonModal(' + a.id + ', \'Rejected\', true)">' +
        '<i class="fa-solid fa-xmark"></i> Reject</button>';
    }
    if (a.status === "Approved") {
      acts.innerHTML +=
        '<button type="button" class="btn btn-success" onclick="openConsultationModal(' + a.id + ', true)">' +
        '<i class="fa-solid fa-clipboard-check"></i> Complete Consultation</button>' +
        '<button type="button" class="btn btn-danger" onclick="openReasonModal(' + a.id + ', \'Cancelled\', true)">' +
        '<i class="fa-solid fa-ban"></i> Cancel</button>';
    }
    if (a.status === "Completed") {
      acts.innerHTML +=
        '<button type="button" class="btn btn-info" onclick="closeModal(\'viewModal\');viewConsultationRecord(' + a.id + ')">' +
        '<i class="fa-solid fa-file-waveform"></i> View Consultation Record</button>';
    }
    openModal("viewModal");
  }

  function openReasonModal(apptId, status, fromViewModal) {
    if (fromViewModal) closeModal('viewModal');
    document.getElementById('reasonApptId').value = apptId;
    document.getElementById('reasonStatus').value = status;
    document.getElementById('reasonNote').value = '';
    const isCancel = status === 'Cancelled';
    document.getElementById('reasonModalTitle').innerHTML =
      '<i class="fa-solid fa-comment-dots"></i> ' + (isCancel ? 'Cancel Appointment' : 'Reject Appointment');
    const btn = document.getElementById('reasonSubmitBtn');
    btn.innerHTML = '<i class="fa-solid fa-' + (isCancel ? 'ban' : 'xmark') + '"></i> ' +
      (isCancel ? 'Confirm Cancel' : 'Confirm Reject');
    openModal('reasonModal');
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".fmt-date[data-date]").forEach(el => el.textContent = formatDate(el.dataset.date));
    document.querySelectorAll(".fmt-time[data-time]").forEach(el => el.textContent = formatTime(el.dataset.time));
    document.querySelectorAll(".status-badge-wrap[data-status]").forEach(el => el.innerHTML = statusBadge(el.dataset.status));
    filterTable();
  });
</script>
<?php
$extraScripts = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';
?>