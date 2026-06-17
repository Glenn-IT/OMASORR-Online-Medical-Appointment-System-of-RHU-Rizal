<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
requireLogin('admin');
$admin = getAdminSession();
$adminName = htmlspecialchars($admin['full_name']);
$initial   = strtoupper($adminName[0]);

$pdo = db();

// Stats
$totalAppts     = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppts   = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Pending'")->fetchColumn();
$completedAppts = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Completed'")->fetchColumn();
$totalPatients  = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// Monthly counts (current year)
$year = date('Y');
$monthlyStmt = $pdo->prepare("SELECT MONTH(date) AS m, COUNT(*) AS cnt FROM appointments WHERE YEAR(date)=? GROUP BY MONTH(date)");
$monthlyStmt->execute([$year]);
$monthlyCounts = array_fill(1, 12, 0);
foreach ($monthlyStmt->fetchAll() as $row) { $monthlyCounts[(int)$row['m']] = (int)$row['cnt']; }

// Status distribution
$statusRows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM appointments GROUP BY status")->fetchAll();
$statusCounts = ['Pending'=>0,'Approved'=>0,'Completed'=>0,'Rejected'=>0,'Cancelled'=>0];
foreach ($statusRows as $row) { if (isset($statusCounts[$row['status']])) $statusCounts[$row['status']] = (int)$row['cnt']; }

// Top services
$serviceRows = $pdo->query("SELECT service, COUNT(*) AS cnt FROM appointments GROUP BY service ORDER BY cnt DESC LIMIT 6")->fetchAll();

// Recent 5 appointments
$recent = $pdo->query("
    SELECT a.id, a.appt_no, p.full_name AS patient_name, a.service, a.date, a.status
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    ORDER BY a.created_at DESC LIMIT 5
")->fetchAll();

// Pending notifications (5 most recent)
$pendingNotifs = $pdo->query("
    SELECT a.id, p.full_name AS patient_name, a.service, a.date
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.status = 'Pending'
    ORDER BY a.created_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard – RHU Rizal';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
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
          <h4>Admin Dashboard</h4>
          <p id="currentDate">Loading...</p>
        </div>
      </div>
      <div class="topbar-right">
        <button class="topbar-icon-btn" onclick="openModal('notifModal')">
          <i class="fa-solid fa-bell"></i><span class="notif-dot"></span>
        </button>
        <div class="topbar-user" onclick="window.location.href='profile.php'">
          <div class="avatar"><?= $initial ?></div>
          <span class="user-name"><?= $adminName ?></span>
          <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#999;"></i>
        </div>
      </div>
    </header>

    <div class="page-content">
      <!-- Welcome Banner -->
      <div class="card mb-3" style="background:linear-gradient(135deg,#1a202c 0%,#2d3748 100%);border:none;">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
          <div style="color:#fff;">
            <p style="font-size:13px;opacity:.7;margin-bottom:4px;">Welcome back,</p>
            <h2 style="font-size:22px;font-weight:700;color:#fff;margin-bottom:6px;"><?= $adminName ?></h2>
            <p style="font-size:13px;opacity:.6;"><i class="fa-solid fa-shield-halved"></i> System Administrator &nbsp;|&nbsp; <span id="dateInfo"></span></p>
          </div>
          <div style="text-align:right;color:rgba(255,255,255,.15);font-size:64px;">
            <i class="fa-solid fa-gauge-high"></i>
          </div>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="grid-4 mb-3">
        <div class="stat-card">
          <div class="stat-icon primary"><i class="fa-solid fa-calendar-check"></i></div>
          <div class="stat-info">
            <div class="value"><?= $totalAppts ?></div>
            <div class="label">Total Appointments</div>
            <div class="change up"><i class="fa-solid fa-arrow-up"></i> +12% this month</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div>
          <div class="stat-info">
            <div class="value"><?= $pendingAppts ?></div>
            <div class="label">Pending</div>
            <div class="change down"><i class="fa-solid fa-arrow-down"></i> needs action</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-info">
            <div class="value"><?= $completedAppts ?></div>
            <div class="label">Completed</div>
            <div class="change up"><i class="fa-solid fa-arrow-up"></i> +8% this month</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon info"><i class="fa-solid fa-users"></i></div>
          <div class="stat-info">
            <div class="value"><?= $totalPatients ?></div>
            <div class="label">Registered Patients</div>
            <div class="change up"><i class="fa-solid fa-arrow-up"></i> +3 new this week</div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid-2 mb-3">
        <div class="card">
          <div class="card-header"><h5><i class="fa-solid fa-chart-line"></i> Monthly Appointments</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="lineChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><h5><i class="fa-solid fa-chart-pie"></i> Appointment Status</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="doughnutChart"></canvas></div></div>
        </div>
      </div>

      <!-- Bar Chart + Recent -->
      <div class="grid-2 mb-3">
        <div class="card">
          <div class="card-header"><h5><i class="fa-solid fa-chart-bar"></i> Services This Month</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="barChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <h5><i class="fa-solid fa-clock-rotate-left"></i> Recent Appointments</h5>
            <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body" style="padding:0;">
            <?php if (empty($recent)): ?>
              <p class="text-muted text-center" style="padding:20px;">No recent appointments</p>
            <?php else: foreach ($recent as $r): ?>
              <div style="display:flex;gap:12px;align-items:center;padding:12px 20px;border-bottom:1px solid var(--gray-100);">
                <div style="width:36px;height:36px;background:var(--primary-light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:14px;flex-shrink:0;">
                  <i class="fa-solid fa-calendar"></i>
                </div>
                <div style="flex:1;min-width:0;">
                  <p style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($r['patient_name']) ?></p>
                  <p style="font-size:12px;color:#888;"><?= htmlspecialchars($r['service']) ?> &middot; <span class="fmt-date" data-date="<?= htmlspecialchars($r['date']) ?>"></span></p>
                </div>
                <div><span class="status-badge-wrap" data-status="<?= htmlspecialchars($r['status']) ?>"></span></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Notifications Modal -->
<div class="modal-overlay" id="notifModal">
  <div class="modal-box sm">
    <div class="modal-header">
      <h5><i class="fa-solid fa-bell"></i> System Notifications</h5>
      <button class="modal-close" data-modal-close="notifModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <?php if (empty($pendingNotifs)): ?>
        <p class="text-muted text-center">No new notifications</p>
      <?php else: foreach ($pendingNotifs as $n): ?>
        <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
          <div style="color:var(--accent);font-size:16px;"><i class="fa-solid fa-clock"></i></div>
          <div>
            <p style="font-size:13px;font-weight:600;">Pending: <?= htmlspecialchars($n['patient_name']) ?></p>
            <p style="font-size:12px;color:#888;"><?= htmlspecialchars($n['service']) ?> &middot; <span class="fmt-date" data-date="<?= htmlspecialchars($n['date']) ?>"></span></p>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php
$monthly_json  = json_encode(array_values($monthlyCounts), JSON_HEX_TAG);
$status_labels = json_encode(array_keys($statusCounts), JSON_HEX_TAG);
$status_data   = json_encode(array_values($statusCounts), JSON_HEX_TAG);
$svc_labels    = json_encode(array_column($serviceRows, 'service'), JSON_HEX_TAG);
$svc_data      = json_encode(array_column($serviceRows, 'cnt'), JSON_HEX_TAG);

$extraScripts = <<<SCRIPTS
<script>
  // Date display
  const now = new Date();
  const dateStr = now.toLocaleDateString("en-PH", { weekday:"long", year:"numeric", month:"long", day:"numeric" });
  document.getElementById("currentDate").textContent = dateStr;
  document.getElementById("dateInfo").textContent    = dateStr;

  // Format rendered rows
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".fmt-date[data-date]").forEach(el => el.textContent = formatDate(el.dataset.date));
    document.querySelectorAll(".status-badge-wrap[data-status]").forEach(el => el.innerHTML = statusBadge(el.dataset.status));
  });

  // Charts
  const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  new Chart(document.getElementById("lineChart"), {
    type: "line",
    data: {
      labels: months,
      datasets: [{ label:"Appointments", data:{$monthly_json}, borderColor:"#1a6b3c", backgroundColor:"rgba(26,107,60,.1)", fill:true, tension:0.4, pointBackgroundColor:"#1a6b3c", pointRadius:4 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false } } } }
  });

  new Chart(document.getElementById("doughnutChart"), {
    type: "doughnut",
    data: { labels:{$status_labels}, datasets:[{ data:{$status_data}, backgroundColor:["#f39c12","#3498db","#2ecc71","#e74c3c","#95a5a6"], borderWidth:2, borderColor:"#fff" }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:"bottom", labels:{ padding:12, font:{ size:11 } } } } }
  });

  new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: {
      labels: {$svc_labels},
      datasets: [{ label:"Appointments", data:{$svc_data}, backgroundColor:["#1a6b3c","#2ecc71","#3498db","#f39c12","#e74c3c","#9b59b6"], borderRadius:6 }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false }, ticks:{ font:{ size:11 } } } } }
  });
</script>
SCRIPTS;
require_once __DIR__ . '/../../includes/footer.php';
?>
