<?php require_once __DIR__ . '/../../components/under-construction.php'; ?>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('patient');
$session   = getPatientSession();
$fullName  = $session['full_name'];
$initial   = strtoupper(mb_substr($fullName, 0, 1));

// Fetch stats
$pdo   = db();
$pid   = (int) $session['id'];

$stats = $pdo->prepare("
    SELECT
      COUNT(*) AS total,
      SUM(status='Pending')   AS pending,
      SUM(status='Approved')  AS approved,
      SUM(status='Completed') AS completed
    FROM appointments WHERE patient_id = ?
");
$stats->execute([$pid]);
$counts = $stats->fetch();

// Upcoming (next 3 non-terminal)
$upcoming = $pdo->prepare("
    SELECT a.appt_no, a.date, a.time, a.service, a.status, d.name AS doctor_name
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE a.patient_id = ? AND a.status IN ('Pending','Approved')
    ORDER BY a.date ASC, a.time ASC
    LIMIT 3
");
$upcoming->execute([$pid]);
$upcomingAppts = $upcoming->fetchAll();

// Notifications (last 4 appointments)
$notifStmt = $pdo->prepare("
    SELECT appt_no, service, date, status FROM appointments
    WHERE patient_id = ?
    ORDER BY created_at DESC LIMIT 4
");
$notifStmt->execute([$pid]);
$notifs = $notifStmt->fetchAll();

$pageTitle = 'Dashboard – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/user-sidebar.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');">
            <i class="fa-solid fa-bars"></i>
          </button>
          <div style="margin-left:12px">
            <h4>Dashboard</h4>
            <p id="currentDate">Loading...</p>
          </div>
        </div>
        <div class="topbar-right">
          <button class="topbar-icon-btn" onclick="openModal('notifModal')">
            <i class="fa-solid fa-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <div class="topbar-user" onclick="window.location.href='<?= BASE_URL ?>/views/user/profile.php'">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div>
            <span class="user-name"><?= htmlspecialchars($fullName) ?></span>
            <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#999"></i>
          </div>
        </div>
      </header>

      <div class="page-content">
        <!-- Welcome Banner -->
        <div class="card mb-3" style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);border:none;overflow:visible;">
          <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="color:#fff">
              <p style="font-size:13px;opacity:.8;margin-bottom:4px">Good morning,</p>
              <h2 id="welcomeName" style="font-size:24px;font-weight:700;color:#fff;margin-bottom:6px"><?= htmlspecialchars($fullName) ?>!</h2>
              <p style="font-size:13px;opacity:.75">
                <i class="fa-solid fa-location-dot"></i> RHU Rizal, Cagayan Valley &nbsp;|&nbsp;
                <i class="fa-solid fa-calendar"></i> <span id="welcomeDate"></span>
              </p>
            </div>
            <div style="text-align:center;color:rgba(255,255,255,.3);font-size:64px;">
              <i class="fa-solid fa-hospital-user"></i>
            </div>
          </div>
        </div>

        <!-- Stats Row -->
        <div class="grid-3 mb-3">
          <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-info"><div class="value"><?= (int)$counts['total'] ?></div><div class="label">Total Appointments</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info"><div class="value"><?= (int)$counts['pending'] ?></div><div class="label">Pending</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info"><div class="value"><?= (int)$counts['completed'] ?></div><div class="label">Completed</div></div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
          <div class="card-header"><h5><i class="fa-solid fa-bolt"></i> Quick Actions</h5></div>
          <div class="card-body">
            <div class="grid-3">
              <a class="quick-action" href="<?= BASE_URL ?>/views/user/book-appointment.php">
                <div class="qa-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                <div class="qa-title">Book Appointment</div>
                <div class="qa-desc">Schedule a new visit</div>
              </a>
              <a class="quick-action" href="<?= BASE_URL ?>/views/user/my-appointments.php">
                <div class="qa-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="qa-title">My Appointments</div>
                <div class="qa-desc">View &amp; manage bookings</div>
              </a>
              <a class="quick-action" href="<?= BASE_URL ?>/views/user/medical-history.php">
                <div class="qa-icon"><i class="fa-solid fa-file-medical"></i></div>
                <div class="qa-title">Medical History</div>
                <div class="qa-desc">View past records</div>
              </a>
            </div>
          </div>
        </div>

        <!-- Upcoming + Announcements -->
        <div class="grid-2">
          <div class="card">
            <div class="card-header">
              <h5><i class="fa-solid fa-calendar-day"></i> Upcoming Appointments</h5>
              <a href="<?= BASE_URL ?>/views/user/my-appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body" id="upcomingList">
              <?php if (empty($upcomingAppts)): ?>
              <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>No upcoming appointments</p></div>
              <?php else: foreach ($upcomingAppts as $a):
                $d = new DateTime($a['date']);
              ?>
              <div style="display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--gray-100);">
                <div style="width:44px;height:44px;background:var(--primary-light);border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
                  <span style="font-size:14px;font-weight:700;color:var(--primary);"><?= $d->format('j') ?></span>
                  <span style="font-size:9px;color:var(--primary);text-transform:uppercase;"><?= $d->format('M') ?></span>
                </div>
                <div>
                  <p style="font-size:13px;font-weight:600;margin-bottom:2px"><?= htmlspecialchars($a['service']) ?></p>
                  <p style="font-size:12px;color:#888"><?= htmlspecialchars($a['doctor_name'] ?? 'TBA') ?> &middot; <span class="fmt-time" data-time="<?= htmlspecialchars($a['time']) ?>"></span></p>
                  <div style="margin-top:4px" class="status-badge-wrap" data-status="<?= htmlspecialchars($a['status']) ?>"></div>
                </div>
              </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h5><i class="fa-solid fa-bullhorn"></i> Announcements</h5></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:14px">
                <div style="display:flex;gap:12px;align-items:flex-start">
                  <div style="width:38px;height:38px;background:#e8f5ee;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:16px;flex-shrink:0"><i class="fa-solid fa-syringe"></i></div>
                  <div><p style="font-size:13px;font-weight:600;margin-bottom:2px">Free Immunization Drive</p><p style="font-size:12px;color:#888">April 20–24, 2026 at RHU Main Building</p></div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                  <div style="width:38px;height:38px;background:#ebf5fb;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--info);font-size:16px;flex-shrink:0"><i class="fa-solid fa-heart-pulse"></i></div>
                  <div><p style="font-size:13px;font-weight:600;margin-bottom:2px">Free Blood Pressure Monitoring</p><p style="font-size:12px;color:#888">Every Wednesday 8AM–12NN</p></div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                  <div style="width:38px;height:38px;background:#fef3cd;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:16px;flex-shrink:0"><i class="fa-solid fa-triangle-exclamation"></i></div>
                  <div><p style="font-size:13px;font-weight:600;margin-bottom:2px">RHU Holiday Notice</p><p style="font-size:12px;color:#888">Closed on April 9 – Araw ng Kagitingan</p></div>
                </div>
                <div style="display:flex;gap:12px;align-items:flex-start">
                  <div style="width:38px;height:38px;background:#fdecea;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--danger);font-size:16px;flex-shrink:0"><i class="fa-solid fa-pills"></i></div>
                  <div><p style="font-size:13px;font-weight:600;margin-bottom:2px">TB-DOTS Schedule Update</p><p style="font-size:12px;color:#888">Monday to Friday, 8AM–3PM only</p></div>
                </div>
              </div>
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
        <h5><i class="fa-solid fa-bell"></i> Notifications</h5>
        <button class="modal-close" data-modal-close="notifModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div id="notifList">
          <?php if (empty($notifs)): ?>
          <p class="text-muted text-center">No notifications</p>
          <?php else: foreach ($notifs as $n): ?>
          <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
            <div style="color:var(--primary);font-size:16px;"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
              <p style="font-size:13px;font-weight:600">Appointment: <?= htmlspecialchars($n['service']) ?></p>
              <p style="font-size:12px;color:#888"><span class="fmt-date" data-date="<?= htmlspecialchars($n['date']) ?>"></span> &middot; <span class="status-badge-wrap" data-status="<?= htmlspecialchars($n['status']) ?>"></span></p>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  // Date display
  const now = new Date();
  const dateStr = now.toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
  document.getElementById('currentDate').textContent = dateStr;
  document.getElementById('welcomeDate').textContent = dateStr;

  // Format time slots
  document.querySelectorAll('.fmt-time').forEach(el => {
    el.textContent = formatTime(el.dataset.time);
  });

  // Format dates
  document.querySelectorAll('.fmt-date').forEach(el => {
    el.textContent = formatDate(el.dataset.date);
  });

  // Status badges
  document.querySelectorAll('.status-badge-wrap').forEach(el => {
    el.innerHTML = statusBadge(el.dataset.status);
  });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
