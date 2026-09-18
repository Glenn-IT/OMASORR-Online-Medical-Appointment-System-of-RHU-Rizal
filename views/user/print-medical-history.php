<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

// Authentication guard: Allow logged-in patient or admin
$isPatient = isLoggedIn('patient');
$isAdmin   = isLoggedIn('admin');

if (!$isPatient && !$isAdmin) {
    requireLogin('patient');
}

$apptId = (int) ($_GET['id'] ?? 0);
if ($apptId <= 0) {
    if ($isPatient) {
        redirectTo('/views/user/medical-history.php');
    } else {
        redirectTo('/views/admin/appointments.php');
    }
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT a.id, a.appt_no, a.service, a.date, a.time, a.reason, a.status,
           p.id AS patient_id, p.full_name AS patient_name, p.patient_no, p.birthdate, p.gender, p.address,
           d.name AS doctor_name, d.specialty AS doctor_specialty,
           c.id AS consultation_id, c.mode_of_transaction, c.consultation_date, c.consultation_time,
           c.patient_name AS consult_patient_name, c.dob AS consult_dob, c.age AS consult_age,
           c.gender AS consult_gender, c.address AS consult_address, c.folder_no, c.tin_no,
           c.nature_of_visit, c.chief_complaints, c.height, c.weight, c.bp, c.rr, c.pr, c.temperature,
           c.history_of_illness, c.past_medical_history, c.pertinent_pe, c.diagnosis, c.treatment,
           c.lab_findings, c.created_at AS consult_created_at
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN consultation_records c ON c.appointment_id = a.id
    WHERE a.id = ? AND a.status = 'Completed'
");
$stmt->execute([$apptId]);
$record = $stmt->fetch();

if (!$record) {
    http_response_code(404);
    die('<!DOCTYPE html><html><head><title>Record Not Found</title><link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css"></head><body style="padding:40px;text-align:center;">' .
        '<h3>Clinical Record Not Found</h3><p>This appointment record does not exist or has not been completed by the attending physician yet.</p>' .
        '<a class="btn btn-primary" href="' . ($isPatient ? BASE_URL . '/views/user/medical-history.php' : BASE_URL . '/views/admin/appointments.php') . '">Go Back</a></body></html>');
}

// Security: If logged in as patient, ensure this record belongs to them
if ($isPatient && !$isAdmin) {
    $patientSession = getPatientSession();
    if ((int) $record['patient_id'] !== (int) $patientSession['id']) {
        http_response_code(403);
        die('Unauthorized access to medical record.');
    }
}

// Resolve data fields with fallbacks
$patientName = $record['consult_patient_name'] ?: $record['patient_name'];
$dob         = $record['consult_dob'] ?: $record['birthdate'];
$gender      = $record['consult_gender'] ?: $record['gender'];
$address     = $record['consult_address'] ?: $record['address'];
$folderNo    = $record['folder_no'] ?: $record['patient_no'];
$tinNo       = $record['tin_no'] ?? '';
$modeTrans   = $record['mode_of_transaction'] ?: 'Online appointment';
$consultDate = $record['consultation_date'] ?: $record['date'];
$consultTime = $record['consultation_time'] ?: $record['time'];
$nature      = $record['nature_of_visit'] ?: $record['reason'] ?: $record['service'];
$doctorName  = $record['doctor_name'] ?: 'RHU Attending Physician';

// Age calculation
$age = $record['consult_age'];
if (!$age && !empty($dob)) {
    try {
        $bDate = new DateTime($dob);
        $cDate = new DateTime($consultDate ?: 'now');
        $age = $bDate->diff($cDate)->y;
    } catch (Exception $e) {
        $age = '';
    }
}

// Name parser: Last Name, First Name, Middle Name
function splitPatientName(string $raw): array {
    $raw = trim($raw);
    if (empty($raw)) return ['', '', ''];
    if (str_contains($raw, ',')) {
        $parts = explode(',', $raw, 2);
        $last  = trim($parts[0]);
        $rest  = trim($parts[1]);
        $sub   = preg_split('/\s+/', $rest);
        $first = array_shift($sub) ?? '';
        $mid   = implode(' ', $sub);
        return [$last, $first, $mid];
    }
    $words = preg_split('/\s+/', $raw);
    if (count($words) === 1) return [$words[0], '', ''];
    if (count($words) === 2) return [$words[1], $words[0], ''];
    $last  = array_pop($words);
    $first = array_shift($words);
    $mid   = implode(' ', $words);
    return [$last, $first, $mid];
}

[$lastName, $firstName, $middleName] = splitPatientName($patientName);

// Format dates
$dobDisplay = !empty($dob) ? date('m/d/Y', strtotime($dob)) : '';
$consultDateDisplay = !empty($consultDate) ? date('m/d/Y', strtotime($consultDate)) : '';
$consultTimeDisplay = !empty($consultTime) ? date('h:i A', strtotime($consultTime)) : '';

// Mode flags
$isWalkIn   = stripos($modeTrans, 'walk') !== false;
$isVisited  = stripos($modeTrans, 'visit') !== false && !$isWalkIn;
$isReferral = stripos($modeTrans, 'refer') !== false;
$isOnline   = stripos($modeTrans, 'online') !== false || (!$isWalkIn && !$isVisited && !$isReferral);

// Nature of visit flags
$isFollowUp    = stripos($nature, 'follow') !== false;
$isAdmission   = stripos($nature, 'admiss') !== false;
$isNewConsult  = !$isFollowUp && !$isAdmission;

// Vitals
$height = $record['height'] ?? '';
$weight = $record['weight'] ?? '';
$bp     = $record['bp'] ?? '';
$rr     = $record['rr'] ?? '';
$pr     = $record['pr'] ?? '';
$temp   = $record['temperature'] ?? '';

// Clinical text fields
$chiefComplaints = trim($record['chief_complaints'] ?? ($record['reason'] ?? ''));
$historyIllness  = trim($record['history_of_illness'] ?? '');
$pastHistory     = trim($record['past_medical_history'] ?? '');
$pertinentPe     = trim($record['pertinent_pe'] ?? '');
$diagnosis       = trim($record['diagnosis'] ?? 'Completed Clinical Consultation');
$treatment       = trim($record['treatment'] ?? '');
$labFindings     = trim($record['lab_findings'] ?? '');

$autoPrint = isset($_GET['auto']) || isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ITR - <?= htmlspecialchars($record['appt_no']) ?> - <?= htmlspecialchars($patientName) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ── BASE & SCREEN STYLES ───────────────────────────────── */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: Arial, Helvetica, "Nimbus Sans L", sans-serif;
      background-color: #525659;
      color: #000;
      font-size: 11pt;
      line-height: 1.25;
      padding-bottom: 40px;
    }

    /* Top interactive toolbar for screen display */
    .screen-toolbar {
      position: sticky;
      top: 0;
      z-index: 999;
      background: #1e293b;
      color: #fff;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .toolbar-left, .toolbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .toolbar-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: background 0.15s ease;
    }
    .btn-back {
      background: #334155;
      color: #e2e8f0;
    }
    .btn-back:hover {
      background: #475569;
      color: #fff;
    }
    .btn-print {
      background: #0284c7;
      color: #fff;
    }
    .btn-print:hover {
      background: #0369a1;
    }
    .toolbar-tip {
      font-size: 12px;
      color: #94a3b8;
    }

    /* Page container styled like an official paper document */
    .itr-container {
      width: 8.5in;
      min-height: 11in;
      margin: 20px auto;
      background: #fff;
      padding: 0.35in 0.45in;
      box-shadow: 0 4px 18px rgba(0,0,0,0.35);
      border: 1px solid #ccc;
    }

    /* ── ITR DOCUMENT HEADER ───────────────────────────────── */
    .itr-header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }
    .itr-header-table td {
      vertical-align: bottom;
      padding: 0;
    }
    .itr-title {
      text-align: center;
      font-size: 15pt;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      font-family: "Arial Black", Arial, Helvetica, sans-serif;
    }
    .itr-folder {
      text-align: right;
      font-size: 10pt;
      font-weight: 700;
      white-space: nowrap;
    }
    .itr-folder-line {
      display: inline-block;
      min-width: 130px;
      border-bottom: 1.5px solid #000;
      text-align: center;
      padding: 0 4px;
      font-weight: 700;
    }

    /* ── BORDER GRID & BOXES ───────────────────────────────── */
    .itr-box {
      border: 1.5px solid #000;
      margin-bottom: -1.5px; /* collapse adjacent outer borders */
    }

    .row-border-bottom {
      border-bottom: 1.5px solid #000;
    }
    .col-border-right {
      border-right: 1.5px solid #000;
    }

    /* Demographics lines */
    .demo-table {
      width: 100%;
      border-collapse: collapse;
    }
    .demo-table td {
      border: 1.5px solid #000;
      padding: 3px 6px;
      font-size: 9pt;
      vertical-align: top;
    }

    .field-label {
      font-size: 8pt;
      font-weight: 700;
      color: #000;
      text-transform: uppercase;
    }
    .field-sublabel {
      font-size: 7.5pt;
      font-style: italic;
      color: #333;
      display: block;
      margin-top: 1px;
    }
    .field-value {
      font-size: 9.5pt;
      font-weight: 600;
      color: #000;
    }
    .field-line {
      border-bottom: 1px solid #000;
      display: inline-block;
      width: 100%;
      min-height: 16px;
    }

    /* Checkbox styling */
    .checkbox-box {
      display: inline-block;
      width: 11px;
      height: 11px;
      border: 1.5px solid #000;
      text-align: center;
      line-height: 10px;
      font-size: 9pt;
      font-weight: bold;
      vertical-align: middle;
      margin-right: 3px;
      margin-bottom: 2px;
    }

    /* Middle section: Chief Complaints & Illness vs Vitals */
    .mid-table {
      width: 100%;
      border-collapse: collapse;
    }
    .mid-table td {
      vertical-align: top;
      padding: 0;
    }
    .mid-left {
      width: 73%;
      border-right: 1.5px solid #000;
    }
    .mid-right {
      width: 27%;
    }

    .section-box {
      padding: 4px 6px;
    }
    .section-title {
      font-size: 8.5pt;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .section-content {
      font-size: 9.5pt;
      min-height: 52px;
      line-height: 1.35;
      white-space: pre-wrap;
      word-break: break-word;
    }

    /* Vitals list */
    .vitals-box {
      padding: 6px 8px;
    }
    .vital-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      font-size: 9.5pt;
      margin-bottom: 5px;
    }
    .vital-label {
      font-weight: 700;
      width: 48px;
    }
    .vital-val {
      flex: 1;
      border-bottom: 1px solid #000;
      text-align: center;
      font-weight: 600;
      margin: 0 4px;
      min-height: 16px;
    }
    .vital-unit {
      font-size: 8.5pt;
      width: 40px;
      text-align: right;
    }

    /* Full width clinical sections */
    .full-section {
      border: 1.5px solid #000;
      margin-top: -1.5px;
      padding: 4px 6px;
    }
    .full-section .section-content {
      min-height: 38px;
    }
    .full-section.diagnosis-box .section-content {
      font-weight: 700;
    }

    /* ── LEGAL, PHILHEALTH & CONSENT BOTTOM SECTION ────────── */
    .consent-paragraph {
      border: 1.5px solid #000;
      margin-top: -1.5px;
      padding: 4px 6px;
      font-size: 7pt;
      line-height: 1.18;
      text-align: justify;
    }
    .consent-paragraph strong {
      font-weight: 700;
    }

    .bottom-split-table {
      width: 100%;
      border-collapse: collapse;
      border: 1.5px solid #000;
      margin-top: -1.5px;
    }
    .bottom-split-table td {
      vertical-align: top;
      padding: 4px 6px;
      font-size: 7.5pt;
    }
    .bottom-left-col {
      width: 42%;
      border-right: 1.5px solid #000;
    }
    .bottom-right-col {
      width: 58%;
    }

    .philhealth-title {
      font-size: 8pt;
      font-weight: 700;
      text-decoration: underline;
    }
    .beneficiary-sub {
      font-size: 7pt;
      font-style: italic;
      margin-bottom: 6px;
    }

    .signature-area {
      margin-top: 14px;
      text-align: center;
    }
    .sig-line {
      border-top: 1.2px solid #000;
      width: 90%;
      margin: 0 auto 2px auto;
    }
    .sig-sub {
      font-size: 7.5pt;
    }

    .physician-bar {
      border: 1.5px solid #000;
      margin-top: -1.5px;
      padding: 4px 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 8.5pt;
      background: #fafafa;
    }

    /* ── PRINT MEDIA STYLES ────────────────────────────────── */
    @media print {
      @page {
        size: portrait;
        margin: 6mm 8mm;
      }
      body {
        background: #fff !important;
        padding: 0 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .screen-toolbar {
        display: none !important;
      }
      .itr-container {
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
      }
      .itr-box, .demo-table td, .mid-table td, .full-section, .consent-paragraph, .bottom-split-table, .bottom-split-table td, .physician-bar {
        border-color: #000 !important;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Toolbar -->
  <div class="screen-toolbar">
    <div class="toolbar-left">
      <a href="<?= $isPatient ? BASE_URL . '/views/user/medical-history.php' : BASE_URL . '/views/admin/appointments.php' ?>" class="toolbar-btn btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back to <?= $isPatient ? 'Medical History' : 'Appointments' ?>
      </a>
      <span class="toolbar-tip">
        <i class="fa-solid fa-circle-info"></i> Basis: RHU Rizal Official Individual Treatment Record (Sample.pdf)
      </span>
    </div>
    <div class="toolbar-right">
      <button onclick="window.print()" class="toolbar-btn btn-print">
        <i class="fa-solid fa-print"></i> Print Document / Save as PDF
      </button>
    </div>
  </div>

  <!-- Main ITR Document Sheet -->
  <div class="itr-container">

    <!-- Header -->
    <table class="itr-header-table">
      <tr>
        <td style="width:25%;">
          <div style="font-size:7.5pt;line-height:1.2;">
            RHU Form No. 1<br>
            <strong>RHU RIZAL, CAGAYAN</strong>
          </div>
        </td>
        <td style="width:50%;">
          <div class="itr-title">INDIVIDUAL TREATMENT RECORD</div>
        </td>
        <td style="width:25%;">
          <div class="itr-folder">
            FOLDER NUMBER:
            <span class="itr-folder-line"><?= htmlspecialchars($folderNo ?: '—') ?></span>
          </div>
        </td>
      </tr>
    </table>

    <!-- Demographics Table -->
    <table class="demo-table">
      <!-- Row 1: Name parts -->
      <tr>
        <td style="width:33.33%;">
          <span class="field-value"><?= htmlspecialchars($lastName ?: $patientName) ?></span>
          <span class="field-sublabel">Last Name</span>
        </td>
        <td style="width:33.33%;">
          <span class="field-value"><?= htmlspecialchars($firstName ?: '—') ?></span>
          <span class="field-sublabel">First Name</span>
        </td>
        <td style="width:33.34%;">
          <span class="field-value"><?= htmlspecialchars($middleName ?: '—') ?></span>
          <span class="field-sublabel">Middle Name</span>
        </td>
      </tr>

      <!-- Row 2: DOB & TIN -->
      <tr>
        <td colspan="2">
          <span class="field-label">DATE OF BIRTH:</span>
          <span class="field-value" style="margin-left:8px;"><?= htmlspecialchars($dobDisplay ?: '—') ?></span>
        </td>
        <td>
          <span class="field-label">TIN #:</span>
          <span class="field-value" style="margin-left:8px;"><?= htmlspecialchars($tinNo ?: 'N/A') ?></span>
        </td>
      </tr>

      <!-- Row 3: Address -->
      <tr>
        <td colspan="3">
          <span class="field-label">COMPLETE ADDRESS:</span>
          <span class="field-value" style="margin-left:8px;"><?= htmlspecialchars($address ?: 'Poblacion, Rizal, Cagayan') ?></span>
        </td>
      </tr>

      <!-- Row 4: Mode of Transaction & Consultation Info -->
      <tr>
        <td colspan="3" style="padding:4px 6px;">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
            <div>
              <span class="field-label">Mode of Transaction:</span>
              <span style="margin-left:6px;"><span class="checkbox-box"><?= $isWalkIn ? '✓' : '' ?></span> Walk-in</span>
              <span style="margin-left:10px;"><span class="checkbox-box"><?= $isVisited ? '✓' : '' ?></span> Visited</span>
              <span style="margin-left:10px;"><span class="checkbox-box"><?= $isReferral ? '✓' : '' ?></span> Referral</span>
              <span style="margin-left:10px;"><span class="checkbox-box"><?= $isOnline ? '✓' : '' ?></span> Online Appointment</span>
            </div>
            <div style="white-space:nowrap;">
              <span class="field-label">Date of Consultation:</span>
              <span class="field-value" style="margin-right:12px;"><?= htmlspecialchars($consultDateDisplay) ?></span>

              <span class="field-label">Time:</span>
              <span class="field-value" style="margin-right:12px;"><?= htmlspecialchars($consultTimeDisplay) ?></span>

              <span class="field-label">Age/sex:</span>
              <span class="field-value"><?= htmlspecialchars(($age !== '' ? $age : '—') . '/' . ($gender ?: '—')) ?></span>
            </div>
          </div>
        </td>
      </tr>

      <!-- Row 5: Nature of Visit -->
      <tr>
        <td colspan="3" style="padding:4px 6px;">
          <span class="field-label">Nature of visit:</span>
          <span style="margin-left:14px;"><span class="checkbox-box"><?= $isNewConsult ? '✓' : '' ?></span> New Consultation</span>
          <span style="margin-left:18px;"><span class="checkbox-box"><?= $isAdmission ? '✓' : '' ?></span> New Admission</span>
          <span style="margin-left:18px;"><span class="checkbox-box"><?= $isFollowUp ? '✓' : '' ?></span> Follow-up Visit</span>
        </td>
      </tr>
    </table>

    <!-- Middle Section: Chief Complaints & History of Illness (Left) + Vitals Box (Right) -->
    <table class="mid-table itr-box">
      <tr>
        <td class="mid-left">
          <!-- Chief Complaints -->
          <div class="section-box row-border-bottom">
            <div class="section-title">Chief Complaints:</div>
            <div class="section-content"><?= htmlspecialchars($chiefComplaints ?: 'None stated') ?></div>
          </div>
          <!-- History of Patient Illness -->
          <div class="section-box">
            <div class="section-title">History of Patient Illness:</div>
            <div class="section-content"><?= htmlspecialchars($historyIllness ?: 'No prior history of current illness noted.') ?></div>
          </div>
        </td>
        <td class="mid-right">
          <!-- Vitals Box -->
          <div class="vitals-box">
            <div class="vital-row">
              <span class="vital-label">Ht:</span>
              <span class="vital-val"><?= htmlspecialchars($height ?: '—') ?></span>
              <span class="vital-unit">cms.</span>
            </div>
            <div class="vital-row">
              <span class="vital-label">Wt:</span>
              <span class="vital-val"><?= htmlspecialchars($weight ?: '—') ?></span>
              <span class="vital-unit">kg</span>
            </div>
            <div class="vital-row">
              <span class="vital-label">BP:</span>
              <span class="vital-val"><?= htmlspecialchars($bp ?: '—') ?></span>
              <span class="vital-unit">mmHg</span>
            </div>
            <div class="vital-row">
              <span class="vital-label">RR:</span>
              <span class="vital-val"><?= htmlspecialchars($rr ?: '—') ?></span>
              <span class="vital-unit">cpm</span>
            </div>
            <div class="vital-row">
              <span class="vital-label">PR:</span>
              <span class="vital-val"><?= htmlspecialchars($pr ?: '—') ?></span>
              <span class="vital-unit">bpm</span>
            </div>
            <div class="vital-row">
              <span class="vital-label">Temp:</span>
              <span class="vital-val"><?= htmlspecialchars($temp ?: '—') ?></span>
              <span class="vital-unit">°C</span>
            </div>
          </div>
        </td>
      </tr>
    </table>

    <!-- Past Medical History / Family History -->
    <div class="full-section">
      <div class="section-title">Past Medical History/ Family History</div>
      <div class="section-content"><?= htmlspecialchars($pastHistory ?: 'None recorded.') ?></div>
    </div>

    <!-- Pertinent P.E. -->
    <div class="full-section">
      <div class="section-title">Pertinent P.E.</div>
      <div class="section-content"><?= htmlspecialchars($pertinentPe ?: 'Physical examination completed within normal limits.') ?></div>
    </div>

    <!-- Diagnosis -->
    <div class="full-section diagnosis-box">
      <div class="section-title">Diagnosis:</div>
      <div class="section-content"><?= htmlspecialchars($diagnosis ?: 'Completed Clinical Consultation') ?></div>
    </div>

    <!-- Treatment -->
    <div class="full-section">
      <div class="section-title">Treatment:</div>
      <div class="section-content"><?= htmlspecialchars($treatment ?: 'Prescriptions & home management as advised.') ?></div>
    </div>

    <!-- Laboratory Findings/Impression -->
    <div class="full-section">
      <div class="section-title">Laboratory Findings/Impression:</div>
      <div class="section-content"><?= htmlspecialchars($labFindings ?: 'Routine clinical assessment completed.') ?></div>
    </div>

    <!-- Consent Statement Paragraph (Exact phrasing from Sample.pdf) -->
    <div class="consent-paragraph">
      <strong>A FILIPINO</strong> Aking nabasa at naintindihan ang impormasyon ng Pasyente matapos akong bigyang-kaalaman ng mga nilalaman nito. Sa pamamagitan ng aking pakikipag-ugnayan at ang pag-uusap kasama ang kinatawan ng CHU/RHU, ako ay binigyang-paunawa nang mahusay tungkol sa kakayahan at kahalagahan ng Integrated Clinic (iClinicSys). Lahat ng aking mga katanungan sa panahon ng pag-uusap ay nasagot ng sapat at ako ay binigyan ng sapat na pagkakataon at oras upang magpasya nito. Higit pa rito, pinapayagan ko ang CHU/RHU upang i-encode ang mga impormasyon patungkol sa aking kalusugan at pati na rin at ang mga nakolektang impormasyon tungkol sa mga sintomas ng aking sakit at konsultasyong kaugnay dito para sa nasaad na electronic medical information system. Nais kong malaman at maipaalam sa aking direktang kapamilya ang aking mga medikal na resulta. Gayundin, nauunawaan ko na maaari kong kanselahin ang aking pahintulot sa CHU/RHU anumang oras na walang ibinibigay na dahilan at walang kinalaman sa anumang obligasyon o kawalan para sa aking medikal na pagpapagamot.
    </div>

    <!-- PhilHealth & Patient Acknowledgment Split Box -->
    <table class="bottom-split-table">
      <tr>
        <td class="bottom-left-col">
          <div class="philhealth-title">PhilHealth Konsulta Registration Form</div>
          <div class="beneficiary-sub">(to be filled-out by the Beneficiary)</div>
          <div style="margin-top:8px;">
            <span><span class="checkbox-box">✓</span> Member</span>
            <span style="margin-left:16px;"><span class="checkbox-box"></span> Dependent</span>
            <span style="font-size:7pt;color:#444;margin-left:4px;">(please check)</span>
          </div>
          <div style="margin-top:12px;">
            <span style="font-weight:700;">PhilHealth Id No:</span>
            <span style="border-bottom:1px solid #000;display:inline-block;min-width:140px;padding:0 4px;font-weight:600;">
              <?= htmlspecialchars($tinNo ?: ($folderNo ?: '—')) ?>
            </span>
          </div>
        </td>
        <td class="bottom-right-col">
          <div style="line-height:1.25;">
            1. I have been properly informed and clearly explained by the health personnel of the Rural Health Unit (RHU) of Rizal, Cagayan regarding the No Balance Billing (NBB) Policy and the availability of free health services.
          </div>
          <div class="signature-area">
            <div class="sig-line"></div>
            <div class="sig-sub">Signature over printed name of Patient/Guardian</div>
          </div>
        </td>
      </tr>
    </table>

    <!-- Attending Physician Certification Bar -->
    <div class="physician-bar">
      <div>
        <strong>Attending Physician:</strong> Dr. <?= htmlspecialchars($doctorName) ?>
      </div>
      <div>
        <strong>Record No:</strong> <?= htmlspecialchars($record['appt_no']) ?> &middot; <?= htmlspecialchars($record['service']) ?>
      </div>
      <div>
        <strong>Date:</strong> <?= htmlspecialchars($consultDateDisplay) ?>
      </div>
    </div>

  </div>

  <?php if ($autoPrint): ?>
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      setTimeout(() => { window.print(); }, 400);
    });
  </script>
  <?php endif; ?>

</body>
</html>
