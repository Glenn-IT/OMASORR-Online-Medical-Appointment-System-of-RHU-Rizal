<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin('admin');
$admin     = getAdminSession();
$adminName = htmlspecialchars($admin['full_name'] ?? $admin['username']);
$initial   = strtoupper(substr($adminName, 0, 1));

// --- Filters from GET ---
$fMonth   = $_GET['month']     ?? date('m');
$fStatus  = $_GET['status']    ?? '';
$fService = $_GET['service']   ?? '';
$fDoctor  = (int)($_GET['doctor_id'] ?? 0);

// --- Build query ---
$where  = ['1=1'];
$params = [];
if ($fMonth) {
    $where[]          = 'MONTH(a.date) = :month';
    $params[':month'] = (int)$fMonth;
}
if ($fStatus) {
    $where[]           = 'a.status = :status';
    $params[':status'] = $fStatus;
}
if ($fService) {
    $where[]            = 'a.service = :service';
    $params[':service'] = $fService;
}
if ($fDoctor > 0) {
    $where[]            = 'a.doctor_id = :doctor_id';
    $params[':doctor_id'] = $fDoctor;
}
$sql = 'SELECT a.appt_no, p.full_name AS patient_name, a.service,
               COALESCE(d.name,"N/A") AS doctor_name, a.date, a.time, a.status
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        LEFT JOIN doctors d ON d.id = a.doctor_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY a.date DESC, a.time DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$appts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Stats ---
$total     = count($appts);
$pending   = 0; $completed = 0; $cancelled = 0;
$serviceCounts = []; $statusCounts = [];
foreach ($appts as $a) {
    if ($a['status'] === 'Pending')   $pending++;
    if ($a['status'] === 'Completed') $completed++;
    if (in_array($a['status'], ['Cancelled', 'Rejected'])) $cancelled++;
    $serviceCounts[$a['service']] = ($serviceCounts[$a['service']] ?? 0) + 1;
    $statusCounts[$a['status']]   = ($statusCounts[$a['status']]   ?? 0) + 1;
}
arsort($serviceCounts);
$topServices = array_slice($serviceCounts, 0, 6, true);

// --- Services & Doctors list for filter dropdowns ---
$services   = db()->query("SELECT name FROM services ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$allDoctors = db()->query("SELECT id, name, specialty FROM doctors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Doctor Client Volume Breakdown ---
$docVolumeSql = "
    SELECT d.id, d.name, d.specialty,
           COUNT(a.id) AS total_appts,
           SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
           SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
           SUM(CASE WHEN a.status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
           SUM(CASE WHEN a.status IN ('Cancelled', 'Rejected') THEN 1 ELSE 0 END) AS cancelled_count
    FROM doctors d
    LEFT JOIN appointments a ON a.doctor_id = d.id " . ($fMonth ? "AND MONTH(a.date) = " . (int)$fMonth : "") . "
    GROUP BY d.id, d.name, d.specialty
    ORDER BY total_appts DESC, d.name ASC
";
$doctorPerformance = db()->query($docVolumeSql)->fetchAll(PDO::FETCH_ASSOC);

$CHART_SERVICE_LABELS = json_encode(array_keys($topServices),   JSON_HEX_TAG | JSON_HEX_AMP);
$CHART_SERVICE_DATA   = json_encode(array_values($topServices), JSON_HEX_TAG | JSON_HEX_AMP);
$CHART_STATUS_LABELS  = json_encode(array_keys($statusCounts),  JSON_HEX_TAG | JSON_HEX_AMP);
$CHART_STATUS_DATA    = json_encode(array_values($statusCounts), JSON_HEX_TAG | JSON_HEX_AMP);

$pageTitle = 'Reports – RHU Rizal Admin';
$extraHead = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  @media print {
    .no-print { display: none !important; }
    .sidebar, .topbar, .sidebar-overlay { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 10px !important; }
  }
</style>
HTML;
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="app-wrapper">
  <?php require_once __DIR__ . '/../../includes/admin-sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar no-print">
      <div class="topbar-left" style="display:flex;align-items:center;gap:12px;">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h4>Reports</h4>
          <p>Generate and print system reports</p>
        </div>
      </div>
      <div class="topbar-right">
        <button class="btn btn-primary btn-sm no-print" onclick="window.print()">
          <i class="fa-solid fa-print"></i> Print Report
        </button>
        <a href="/rhu-appointment-system/actions/admin/export-report.php?month=<?= urlencode($fMonth) ?>&status=<?= urlencode($fStatus) ?>&service=<?= urlencode($fService) ?>&doctor_id=<?= urlencode((string)$fDoctor) ?>" class="btn btn-secondary btn-sm no-print">
          <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <div class="topbar-user">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
        </div>
      </div>
    </header>

    <div class="page-content">
      <!-- Print Header -->
      <div style="display:none;" class="print-header" id="printHeader">
        <div style="text-align:center;margin-bottom:20px;border-bottom:2px solid #1a6b3c;padding-bottom:16px;">
          <h2 style="color:#1a6b3c;font-size:20px;">Rural Health Unit – Municipality of Rizal</h2>
          <h3 style="font-size:16px;margin-top:4px;">Appointment System – Official Report</h3>
          <p style="font-size:12px;color:#666;margin-top:4px;">Generated: <span id="printDate"></span></p>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid-4 mb-3">
        <div class="stat-card"><div class="stat-icon primary"><i class="fa-solid fa-calendar-check"></i></div><div class="stat-info"><div class="value"><?= $total ?></div><div class="label">Total Appointments</div></div></div>
        <div class="stat-card"><div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><div class="value"><?= $pending ?></div><div class="label">Pending</div></div></div>
        <div class="stat-card"><div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><div class="value"><?= $completed ?></div><div class="label">Completed</div></div></div>
        <div class="stat-card"><div class="stat-icon danger"><i class="fa-solid fa-ban"></i></div><div class="stat-info"><div class="value"><?= $cancelled ?></div><div class="label">Cancelled/Rejected</div></div></div>
      </div>

      <!-- Filter Controls -->
      <div class="card mb-3 no-print">
        <div class="card-body" style="padding:16px 22px;">
          <form method="GET" action="" class="flex-gap" id="filterForm" style="flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Filter by Month</label>
              <select class="form-select" name="month" onchange="this.form.submit()" style="width:150px;">
                <option value="">All Months</option>
                <?php $months = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
                foreach ($months as $v => $lbl): ?>
                <option value="<?= $v ?>"<?= $fMonth === $v ? ' selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Filter by Doctor</label>
              <select class="form-select" name="doctor_id" onchange="this.form.submit()" style="width:200px;">
                <option value="0">All Doctors</option>
                <?php foreach ($allDoctors as $doc): ?>
                <option value="<?= $doc['id'] ?>"<?= $fDoctor === (int)$doc['id'] ? ' selected' : '' ?>>
                  <?= htmlspecialchars($doc['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Filter by Service</label>
              <select class="form-select" name="service" onchange="this.form.submit()" style="width:180px;">
                <option value="">All Services</option>
                <?php foreach ($services as $svc): ?>
                <option<?= $fService === $svc ? ' selected' : '' ?>><?= htmlspecialchars($svc) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">Filter by Status</label>
              <select class="form-select" name="status" onchange="this.form.submit()" style="width:150px;">
                <option value="">All Status</option>
                <?php foreach (['Pending', 'Approved', 'Completed', 'Rejected', 'Cancelled'] as $s): ?>
                <option<?= $fStatus === $s ? ' selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if (isset($_GET['month']) || $fStatus || $fService || $fDoctor > 0): ?>
            <div class="form-group" style="margin-bottom:0;">
              <a href="?" class="btn btn-secondary btn-sm">Clear Filters</a>
            </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Doctors and Their Total Clients Performance Table -->
      <div class="card mb-3">
        <div class="card-header">
          <h5><i class="fa-solid fa-user-doctor text-primary"></i> Doctors &amp; Total Clients Volume</h5>
          <span style="font-size:12px;color:#888;">Summary by Attending Physician</span>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Doctor</th>
                <th>Specialty</th>
                <th style="text-align:center;">Total Clients</th>
                <th style="text-align:center;">Completed</th>
                <th style="text-align:center;">Approved / Pending</th>
                <th style="text-align:center;">Cancelled / Rejected</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($doctorPerformance)): ?>
              <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-user-doctor"></i><p>No doctor records found</p></div></td></tr>
              <?php else: foreach ($doctorPerformance as $dp): ?>
              <tr>
                <td><strong><?= htmlspecialchars($dp['name']) ?></strong></td>
                <td><span class="badge badge-info" style="font-size:12px;"><?= htmlspecialchars($dp['specialty']) ?></span></td>
                <td style="text-align:center;font-weight:700;font-size:15px;color:var(--primary);"><?= (int)$dp['total_appts'] ?></td>
                <td style="text-align:center;"><span class="badge badge-success"><?= (int)$dp['completed_count'] ?></span></td>
                <td style="text-align:center;"><span class="badge badge-warning"><?= (int)$dp['approved_count'] + (int)$dp['pending_count'] ?></span></td>
                <td style="text-align:center;"><span class="badge badge-danger"><?= (int)$dp['cancelled_count'] ?></span></td>
                <td style="text-align:right;">
                  <a href="?doctor_id=<?= $dp['id'] ?>&month=<?= urlencode($fMonth) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-filter"></i> View Records
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Summary Table -->
      <div class="card mb-3">
        <div class="card-header">
          <h5><i class="fa-solid fa-table"></i> Appointments Summary Report</h5>
          <span style="font-size:12px;color:#888;"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></span>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Appt. ID</th><th>Patient Name</th><th>Service</th><th>Doctor</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if (empty($appts)): ?>
              <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-chart-line"></i><p>No appointment records match the selected filter criteria.</p></div></td></tr>
              <?php else: foreach ($appts as $a): ?>
              <tr>
                <td style="font-weight:600;color:var(--primary);"><?= htmlspecialchars($a['appt_no']) ?></td>
                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                <td><?= htmlspecialchars($a['service']) ?></td>
                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                <td class="fmt-date"><?= htmlspecialchars($a['date']) ?></td>
                <td><span class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></span></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><h5><i class="fa-solid fa-chart-bar"></i> By Service</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="reportBarChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><h5><i class="fa-solid fa-chart-pie"></i> By Status</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="reportPieChart"></canvas></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  document.getElementById("printDate").textContent = new Date().toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" });

  document.querySelectorAll(".fmt-date").forEach(el => { el.textContent = formatDate(el.textContent.trim()); });
  document.querySelectorAll(".status-badge-wrap").forEach(el => { el.innerHTML = statusBadge(el.dataset.status); });

  new Chart(document.getElementById("reportBarChart"), {
    type: "bar",
    data: {
      labels: {$CHART_SERVICE_LABELS},
      datasets: [{ label: "Count", data: {$CHART_SERVICE_DATA}, backgroundColor: "#1a6b3c", borderRadius: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
  });

  new Chart(document.getElementById("reportPieChart"), {
    type: "pie",
    data: {
      labels: {$CHART_STATUS_LABELS},
      datasets: [{ data: {$CHART_STATUS_DATA}, backgroundColor: ["#f39c12","#3498db","#2ecc71","#e74c3c","#95a5a6"], borderWidth: 2, borderColor: "#fff" }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { padding: 12, font: { size: 11 } } } } }
  });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
