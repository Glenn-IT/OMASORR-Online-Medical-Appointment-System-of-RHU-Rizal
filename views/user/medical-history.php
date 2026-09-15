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

// History: ONLY Completed appointments are displayed (Cancelled and Rejected are excluded per specification)
$stmt = $pdo->prepare("
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status,
           d.name AS doctor_name,
           c.id AS consultation_id, c.mode_of_transaction, c.consultation_date, c.consultation_time,
           c.patient_name, c.dob, c.age, c.gender, c.address, c.folder_no, c.tin_no,
           c.nature_of_visit, c.chief_complaints, c.height, c.weight, c.bp, c.rr, c.pr, c.temperature,
           c.history_of_illness, c.past_medical_history, c.pertinent_pe, c.diagnosis, c.treatment,
           c.lab_findings, c.created_at AS consult_created_at
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN consultation_records c ON c.appointment_id = a.id
    WHERE a.patient_id = ? AND a.status = 'Completed'
    ORDER BY a.date DESC, a.time DESC
");
$stmt->execute([$patientId]);
$history = $stmt->fetchAll();

// Stats
$completedCount = count($history);
$uniqueServices = count(array_unique(array_column($history, 'service')));
$uniqueDoctors  = count(array_unique(array_filter(array_column($history, 'doctor_name'))));

$pageTitle = 'Medical History – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
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
            <h5><i class="fa-solid fa-file-medical"></i> Completed Consultation Records</h5>
            <div class="search-bar" style="width:220px">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchHistory" placeholder="Search records..." oninput="renderHistory()" />
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Record / Appt.</th>
                  <th>Service</th>
                  <th>Doctor</th>
                  <th>Consultation Date</th>
                  <th>Diagnosis</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="historyTable">
                <?php if (empty($history)): ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fa-solid fa-file-circle-check"></i>
                      <p>No completed consultation records found</p>
                      <span style="font-size:12px;color:#888;">Once an appointment is completed by the RHU physician, your full clinical diagnosis and treatment notes will appear here.</span>
                    </div>
                  </td>
                </tr>
                <?php else: foreach ($history as $a): ?>
                <tr data-search="<?= htmlspecialchars(strtolower(($a['service'] ?? '') . ' ' . ($a['doctor_name'] ?? '') . ' ' . ($a['appt_no'] ?? '') . ' ' . ($a['diagnosis'] ?? ''))) ?>">
                  <td><span style="font-weight:600;color:var(--primary)"><?= htmlspecialchars($a['appt_no']) ?></span></td>
                  <td><?= htmlspecialchars($a['service']) ?></td>
                  <td><?= htmlspecialchars($a['doctor_name'] ?? 'RHU Physician') ?></td>
                  <td>
                    <div style="font-weight:500" class="fmt-date" data-date="<?= htmlspecialchars($a['consultation_date'] ?? $a['date']) ?>"></div>
                    <div style="font-size:12px;color:#888" class="fmt-time" data-time="<?= htmlspecialchars($a['consultation_time'] ?? $a['time']) ?>"></div>
                  </td>
                  <td>
                    <span style="font-weight:500;color:var(--gray-800);">
                      <?= htmlspecialchars($a['diagnosis'] ? (mb_strlen($a['diagnosis']) > 35 ? mb_substr($a['diagnosis'], 0, 35) . '...' : $a['diagnosis']) : 'Completed Consultation') ?>
                    </span>
                  </td>
                  <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></span></td>
                  <td><button class="btn btn-sm btn-info" onclick="viewHistory(<?= $a['id'] ?>)"><i class="fa-solid fa-eye"></i> View Record</button></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- View History / Consultation Record Modal -->
  <div class="modal-overlay" id="historyModal">
    <div class="modal-box xl">
      <div class="modal-header">
        <h5><i class="fa-solid fa-file-medical"></i> Official Medical Consultation Record</h5>
        <button class="modal-close" data-modal-close="historyModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body" id="historyModalBody" style="max-height:calc(85vh - 130px);overflow-y:auto;padding:22px;"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="historyModal">Close</button>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Record</button>
      </div>
    </div>
  </div>

<?php
$histJson = json_encode(array_map(fn($a) => [
    'id'                  => (int) $a['id'],
    'appt_no'             => $a['appt_no'],
    'service'             => $a['service'],
    'doctor_name'         => $a['doctor_name'] ?? 'RHU Physician',
    'date'                => $a['date'],
    'time'                => $a['time'],
    'reason'              => $a['reason'],
    'status'              => $a['status'],
    'consultation_id'     => $a['consultation_id'],
    'mode_of_transaction' => $a['mode_of_transaction'] ?? 'Online appointment',
    'consultation_date'   => $a['consultation_date'] ?? $a['date'],
    'consultation_time'   => $a['consultation_time'] ?? $a['time'],
    'patient_name'        => $a['patient_name'] ?? $fullName,
    'dob'                 => $a['dob'] ?? '',
    'age'                 => $a['age'] ?? '',
    'gender'              => $a['gender'] ?? '',
    'address'             => $a['address'] ?? '',
    'folder_no'           => $a['folder_no'] ?? ($session['patient_no'] ?? ''),
    'tin_no'              => $a['tin_no'] ?? '',
    'nature_of_visit'     => $a['nature_of_visit'] ?? $a['reason'],
    'chief_complaints'    => $a['chief_complaints'] ?? $a['reason'],
    'height'              => $a['height'] ?? '',
    'weight'              => $a['weight'] ?? '',
    'bp'                  => $a['bp'] ?? '',
    'rr'                  => $a['rr'] ?? '',
    'pr'                  => $a['pr'] ?? '',
    'temperature'         => $a['temperature'] ?? '',
    'history_of_illness'  => $a['history_of_illness'] ?? '',
    'past_medical_history'=> $a['past_medical_history'] ?? '',
    'pertinent_pe'        => $a['pertinent_pe'] ?? '',
    'diagnosis'           => $a['diagnosis'] ?? '',
    'treatment'           => $a['treatment'] ?? '',
    'lab_findings'        => $a['lab_findings'] ?? '',
], $history), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

ob_start();
?>
<script>
  const HISTORY = <?= $histJson ?>;

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
    const a = HISTORY.find(x => x.id === id); 
    if (!a) return;

    const dobFormatted = a.dob ? formatDate(a.dob) : '—';
    const consultDateFormatted = formatDate(a.consultation_date || a.date);
    const consultTimeFormatted = formatTime(a.consultation_time || a.time);

    document.getElementById('historyModalBody').innerHTML = `
      <div style="background:var(--primary-light);border-radius:12px;padding:18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
        <div style="display:flex;gap:14px;align-items:center;">
          <div style="font-size:38px;color:var(--primary)"><i class="fa-solid fa-file-waveform"></i></div>
          <div>
            <div style="font-size:11px;text-transform:uppercase;font-weight:700;color:var(--primary-dark);letter-spacing:0.5px;">Rural Health Unit of Rizal &middot; Consultation Record</div>
            <h4 style="font-size:18px;font-weight:700;color:var(--primary);margin:2px 0;">${a.service}</h4>
            <div style="font-size:13px;color:#555;">Attending Physician: <strong>${a.doctor_name}</strong> &middot; Date: ${consultDateFormatted} ${consultTimeFormatted}</div>
          </div>
        </div>
        <div style="text-align:right;">
          <span class="badge badge-completed">Completed</span>
          <div style="font-size:12px;color:#666;margin-top:4px;">Record ID: <strong>${a.appt_no}</strong></div>
        </div>
      </div>

      <div class="consultation-section-title"><i class="fa-solid fa-user"></i> Patient & Consultation Details</div>
      <div class="detail-list mb-3">
        <div class="detail-item"><div class="detail-label">Patient Name</div><div class="detail-value fw-600">${a.patient_name}</div></div>
        <div class="detail-item"><div class="detail-label">Folder / Patient ID</div><div class="detail-value fw-600 text-primary">${a.folder_no || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">DOB / Age / Sex</div><div class="detail-value">${dobFormatted} &middot; ${a.age ? a.age + ' yrs' : '—'} &middot; ${a.gender || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Mode of Transaction</div><div class="detail-value">${a.mode_of_transaction || 'Online appointment'}</div></div>
        <div class="detail-item"><div class="detail-label">TIN #</div><div class="detail-value">${a.tin_no || 'N/A'}</div></div>
        <div class="detail-item"><div class="detail-label">Address</div><div class="detail-value">${a.address || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Nature of Visit</div><div class="detail-value">${a.nature_of_visit || a.reason || '—'}</div></div>
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

      <div class="consultation-section-title"><i class="fa-solid fa-stethoscope"></i> Clinical Findings & Medical Management</div>
      <div class="detail-list">
        <div class="detail-item"><div class="detail-label">Chief Complaints</div><div class="detail-value">${a.chief_complaints || a.reason || 'None stated'}</div></div>
        <div class="detail-item"><div class="detail-label">History of Present Illness</div><div class="detail-value">${a.history_of_illness || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Past Medical History</div><div class="detail-value" style="white-space:pre-line;">${a.past_medical_history || 'No previous medical records noted'}</div></div>
        <div class="detail-item"><div class="detail-label">Pertinent Physical Exam</div><div class="detail-value">${a.pertinent_pe || '—'}</div></div>
        <div class="detail-item"><div class="detail-label">Diagnosis</div><div class="detail-value fw-600 text-primary" style="font-size:15px;">${a.diagnosis || 'Completed Consultation'}</div></div>
        <div class="detail-item"><div class="detail-label">Treatment & Prescription</div><div class="detail-value" style="font-weight:500;">${a.treatment || 'No prescription issued'}</div></div>
        <div class="detail-item"><div class="detail-label">Laboratory Findings / Impression</div><div class="detail-value">${a.lab_findings || 'None'}</div></div>
      </div>
    `;
    openModal('historyModal');
  }
</script>
<?php
$extraScripts = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';
?>
