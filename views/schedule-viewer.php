<?php
// ============================================================
// RHU Rizal — Public Schedule & Vacant Slot Board (Guest Viewer)
// ============================================================
// Publicly accessible page: No login required.
// Allows patients and visitors to view doctor schedules, clinic
// operating hours, holidays, and real-time vacant slots.
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$isPatient = isLoggedIn('patient');
$isAdmin   = isLoggedIn('admin');
$patient   = getPatientSession();
$admin     = getAdminSession();

$pdo = db();

// Fetch doctors
$doctors = $pdo->query("
    SELECT id, name, specialty, schedule, available 
    FROM doctors 
    ORDER BY available DESC, name ASC
")->fetchAll();

// Fetch services
$services = $pdo->query("
    SELECT id, name, description 
    FROM services 
    WHERE active = 1 
    ORDER BY name ASC
")->fetchAll();

// Fetch upcoming holidays
$holidays = $pdo->query("
    SELECT date, name 
    FROM holidays 
    WHERE date >= CURDATE() 
    ORDER BY date ASC 
    LIMIT 6
")->fetchAll();

// Get summary stats
$totalDoctors = count($doctors);
$activeDoctors = count(array_filter($doctors, fn($d) => (int)$d['available'] === 1));
$totalServices = count($services);

$pageTitle = 'Public Schedule & Vacant Slots – RHU Rizal';
$extraHead = <<<'HTML'
<style>
  .guest-nav {
    background: #ffffff;
    border-bottom: 1px solid var(--gray-300);
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  }
  .guest-nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }
  .guest-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--dark);
  }
  .guest-brand-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--primary);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  .guest-brand-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--primary-dark);
    line-height: 1.2;
  }
  .guest-brand-sub {
    font-size: 12px;
    color: var(--gray-600);
  }
  .guest-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .guest-hero {
    background: linear-gradient(135deg, #1a6b3c 0%, #145530 50%, #0c3820 100%);
    color: #ffffff;
    padding: 48px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .guest-hero::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 320px;
    height: 320px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
  }
  .guest-hero-container {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }
  .guest-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
    backdrop-filter: blur(4px);
  }
  .guest-hero h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
  }
  .guest-hero p {
    font-size: 15px;
    color: #e2f0e8;
    max-width: 680px;
    margin: 0 auto 24px;
    line-height: 1.6;
  }
  .hero-chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
    margin-top: 10px;
  }
  .hero-chip {
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .viewer-body {
    max-width: 1200px;
    margin: 32px auto 60px;
    padding: 0 20px;
  }
  .section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
  }
  .section-heading h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
  }
  .stat-card {
    background: #ffffff;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow-sm);
  }
  .stat-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  .slot-filter-card {
    background: #ffffff;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
  }
  .slot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    margin-top: 18px;
  }
  .slot-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    transition: var(--transition);
    position: relative;
    border: 1.5px solid var(--gray-300);
    user-select: none;
  }
  .slot-card.vacant {
    border-color: #82e0aa;
    background: #f4fbf7;
    cursor: pointer;
  }
  .slot-card.vacant:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,107,60,0.15);
  }
  .slot-card.booked {
    border-color: #f5b7b1;
    background: #fdf5f4;
    opacity: 0.85;
    cursor: not-allowed;
  }
  .slot-card.closed {
    border-color: var(--gray-300);
    background: var(--gray-100);
    opacity: 0.65;
    cursor: not-allowed;
  }
  .slot-time {
    font-size: 15px;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 6px;
  }
  .slot-status-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .slot-status-pill.vacant {
    background: #d4edda;
    color: #155724;
  }
  .slot-status-pill.booked {
    background: #f8d7da;
    color: #721c24;
  }
  .slot-status-pill.closed {
    background: #e2e3e5;
    color: #383d41;
  }
  .doctor-card {
    background: #ffffff;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: var(--transition);
  }
  .doctor-card:hover {
    box-shadow: var(--shadow);
    border-color: var(--primary);
  }
  .doctor-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 12px;
  }
  .doctor-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-light);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    flex-shrink: 0;
  }
  .doctor-info h4 {
    margin: 0 0 4px;
    font-size: 15px;
    color: var(--gray-800);
  }
  .doctor-info p {
    margin: 0;
    font-size: 12px;
    color: var(--gray-600);
  }
  .service-card {
    background: #ffffff;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid var(--primary);
    transition: var(--transition);
  }
  .service-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }
  .guest-footer {
    background: #ffffff;
    border-top: 1px solid var(--gray-300);
    padding: 40px 20px 24px;
    margin-top: 60px;
  }
  .guest-footer-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 32px;
    margin-bottom: 30px;
  }
  .modal-overlay {
    background: rgba(0, 0, 0, 0.6) !important;
  }
  .modal-box, .modal-card {
    background: #ffffff !important;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4) !important;
    border: 1px solid var(--gray-300);
    position: relative;
    z-index: 1002;
  }
  .modal-header {
    background: #ffffff !important;
    border-bottom: 1px solid var(--gray-200);
  }
  .modal-body {
    background: #ffffff !important;
  }
  @media (max-width: 768px) {
    .guest-hero h1 { font-size: 24px; }
    .guest-actions { flex-direction: column; width: 100%; }
    .guest-nav-container { flex-direction: column; text-align: center; }
  }
</style>
HTML;

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Public Navigation -->
<nav class="guest-nav">
  <div class="guest-nav-container">
    <a href="<?= BASE_URL ?>/schedule.php" class="guest-brand">
      <div class="guest-brand-icon"><i class="fa-solid fa-hospital"></i></div>
      <div>
        <div class="guest-brand-title">RHU Rizal Schedule Board</div>
        <div class="guest-brand-sub">Rural Health Unit – Municipality of Rizal</div>
      </div>
    </a>

    <div class="guest-actions">
      <?php if ($isPatient): ?>
        <div style="font-size:13px;color:var(--gray-700);margin-right:4px;">
          <i class="fa-solid fa-circle-user text-primary"></i> <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
        </div>
        <a href="<?= BASE_URL ?>/views/user/dashboard.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-gauge"></i> My Dashboard
        </a>
        <a href="<?= BASE_URL ?>/views/user/book-appointment.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-calendar-plus"></i> Book Now
        </a>
      <?php elseif ($isAdmin): ?>
        <div style="font-size:13px;color:var(--gray-700);margin-right:4px;">
          <i class="fa-solid fa-shield-halved text-primary"></i> <strong><?= htmlspecialchars($admin['full_name']) ?></strong>
        </div>
        <a href="<?= BASE_URL ?>/views/admin/dashboard.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-gauge"></i> Admin Panel
        </a>
      <?php else: ?>
        <span class="badge badge-info" style="font-size:12px;padding:6px 12px;">
          <i class="fa-solid fa-eye"></i> Guest Mode
        </span>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-right-to-bracket"></i> Patient Sign In
        </a>
        <a href="<?= BASE_URL ?>/views/user/signup.php" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero Announcement Banner -->
<header class="guest-hero">
  <div class="guest-hero-container">
    <div class="guest-hero-badge">
      <i class="fa-solid fa-stethoscope"></i> Live Doctor Availability &amp; Vacant Slots
    </div>
    <h1>Check RHU Schedules in Real-Time</h1>
    <p>
      Browse doctor duties, health programs, and available appointment slots without signing in. 
      When you find a convenient date and time, log in or create an account to finalize your booking.
    </p>

    <div class="hero-chips">
      <div class="hero-chip">
        <i class="fa-regular fa-clock"></i> <strong>Clinic Hours:</strong> Mon – Fri: 8:00 AM – 5:00 PM
      </div>
      <div class="hero-chip">
        <i class="fa-solid fa-location-dot"></i> RHU Rizal Health Center, Poblacion, Rizal
      </div>
      <div class="hero-chip">
        <i class="fa-solid fa-shield-heart"></i> Triage &amp; Emergency Care Available Daily
      </div>
    </div>
  </div>
</header>

<!-- Main Viewer Content -->
<main class="viewer-body">

  <!-- Overview Stat Counters -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#e8f5ee;color:var(--primary);">
        <i class="fa-solid fa-user-doctor"></i>
      </div>
      <div>
        <div style="font-size:24px;font-weight:700;color:var(--dark);"><?= $activeDoctors ?> / <?= $totalDoctors ?></div>
        <div style="font-size:12px;color:var(--gray-600);">Doctors Active on Schedule</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#e3f2fd;color:var(--info);">
        <i class="fa-solid fa-briefcase-medical"></i>
      </div>
      <div>
        <div style="font-size:24px;font-weight:700;color:var(--dark);"><?= $totalServices ?></div>
        <div style="font-size:12px;color:var(--gray-600);">Active Medical Services</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#fef9e7;color:var(--accent);">
        <i class="fa-regular fa-calendar-check"></i>
      </div>
      <div>
        <div style="font-size:24px;font-weight:700;color:var(--dark);">15 Slots / Day</div>
        <div style="font-size:12px;color:var(--gray-600);">Standard Appointment Capacity</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon-wrap" style="background:#fdf2e9;color:#d35400;">
        <i class="fa-solid fa-bell"></i>
      </div>
      <div>
        <div style="font-size:24px;font-weight:700;color:var(--dark);"><?= count($holidays) ?></div>
        <div style="font-size:12px;color:var(--gray-600);">Upcoming Holidays Noted</div>
      </div>
    </div>
  </div>

  <!-- SECTION 1: LIVE VACANT SLOTS CHECKER -->
  <section id="vacant-slots" class="slot-filter-card">
    <div class="section-heading" style="margin-bottom:16px;">
      <div>
        <h3><i class="fa-solid fa-calendar-day text-primary"></i> Real-Time Vacant Slots Explorer</h3>
        <p style="font-size:13px;color:var(--gray-600);margin:2px 0 0;">
          Select a date and doctor below to see which appointment slots are vacant or booked.
        </p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDate(0)">Today</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDate(1)">Tomorrow</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setNextWeekday()">Next Weekday</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;align-items:end;">
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label" style="font-weight:600;"><i class="fa-regular fa-calendar"></i> Target Date</label>
        <input type="date" class="form-control" id="targetDate" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" />
      </div>

      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label" style="font-weight:600;"><i class="fa-solid fa-user-doctor"></i> Filter by Doctor</label>
        <select class="form-select" id="doctorFilter">
          <option value="0">All Available Doctors</option>
          <?php foreach ($doctors as $doc): ?>
            <option value="<?= $doc['id'] ?>" data-schedule="<?= htmlspecialchars($doc['schedule'] ?? '') ?>" <?= $doc['available'] ? '' : 'disabled' ?>>
              <?= htmlspecialchars($doc['name']) ?> – <?= htmlspecialchars($doc['specialty']) ?> <?= $doc['available'] ? '' : '(Unavailable)' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <button type="button" class="btn btn-primary btn-block" onclick="refreshSlots()">
          <i class="fa-solid fa-arrows-rotate"></i> Check Vacancy
        </button>
      </div>
    </div>

    <!-- Date Status Advisory Banner -->
    <div id="dateStatusBanner" class="alert alert-info mt-3" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <i class="fa-solid fa-info-circle" style="font-size:18px;"></i>
        <div>
          <strong id="selectedDateDisplay">Loading date...</strong>
          <div id="dateSubText" style="font-size:12px;opacity:0.9;">Checking schedule and holiday calendar...</div>
        </div>
      </div>
      <div id="vacancySummaryBadge">
        <span class="badge badge-success" style="font-size:12px;padding:6px 12px;">Checking slots...</span>
      </div>
    </div>

    <!-- Live Slots Container -->
    <div id="slotsContainer" class="slot-grid">
      <!-- Injected via JavaScript -->
      <div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--gray-600);">
        <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
        <p style="margin-top:8px;">Loading vacant schedules...</p>
      </div>
    </div>

    <!-- Legend -->
    <div style="display:flex;align-items:center;justify-content:center;gap:24px;margin-top:20px;flex-wrap:wrap;font-size:13px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <div style="width:14px;height:14px;border-radius:4px;background:#d4edda;border:1px solid #82e0aa;"></div>
        <span><strong>Vacant:</strong> Available for booking</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div style="width:14px;height:14px;border-radius:4px;background:#f8d7da;border:1px solid #f5b7b1;"></div>
        <span><strong>Booked:</strong> Already taken by patient</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div style="width:14px;height:14px;border-radius:4px;background:#e2e3e5;border:1px solid var(--gray-400);"></div>
        <span><strong>Closed / Holiday:</strong> Clinic not in session</span>
      </div>
    </div>
  </section>

  <!-- SECTION 2: DOCTOR SCHEDULE ROSTER -->
  <section id="doctors-roster" style="margin-top:48px;">
    <div class="section-heading">
      <div>
        <h3><i class="fa-solid fa-user-doctor text-primary"></i> Doctor Duty &amp; Schedule Roster</h3>
        <p style="font-size:13px;color:var(--gray-600);margin:2px 0 0;">
          Physician operating days, medical specialties, and current availability status.
        </p>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:20px;">
      <?php foreach ($doctors as $doc): ?>
        <?php 
          $isAvail = (int)$doc['available'] === 1;
          $sched = $doc['schedule'] ?: 'Mon-Fri';
          $initial = strtoupper(mb_substr($doc['name'], 0, 1));
        ?>
        <div class="doctor-card">
          <div>
            <div class="doctor-header">
              <div class="doctor-avatar"><?= htmlspecialchars($initial) ?></div>
              <div class="doctor-info">
                <h4><?= htmlspecialchars($doc['name']) ?></h4>
                <p><i class="fa-solid fa-stethoscope"></i> <?= htmlspecialchars($doc['specialty']) ?></p>
              </div>
            </div>

            <div style="background:var(--gray-100);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;">
              <div style="color:var(--gray-600);margin-bottom:3px;">
                <i class="fa-regular fa-calendar-days text-primary"></i> Regular Schedule:
              </div>
              <strong style="color:var(--gray-800);"><?= htmlspecialchars($sched) ?></strong>
            </div>
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--gray-200);padding-top:12px;">
            <div>
              <?php if ($isAvail): ?>
                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Available / On Duty</span>
              <?php else: ?>
                <span class="badge badge-secondary"><i class="fa-solid fa-circle-minus"></i> On Leave / Off Duty</span>
              <?php endif; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectDoctorAndScroll(<?= $doc['id'] ?>)">
              Check Slots <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECTION 3: RHU SERVICES DIRECTORY -->
  <section id="services-directory" style="margin-top:48px;">
    <div class="section-heading">
      <div>
        <h3><i class="fa-solid fa-briefcase-medical text-primary"></i> Medical Services &amp; Programs Offered</h3>
        <p style="font-size:13px;color:var(--gray-600);margin:2px 0 0;">
          All programs provided free or subsidized by the Rural Health Unit of Rizal.
        </p>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(270px, 1fr));gap:16px;">
      <?php foreach ($services as $s): ?>
        <div class="service-card">
          <h4 style="font-size:15px;color:var(--gray-800);margin-bottom:6px;">
            <i class="fa-solid fa-heart-pulse text-primary" style="margin-right:6px;"></i>
            <?= htmlspecialchars($s['name']) ?>
          </h4>
          <p style="font-size:13px;color:var(--gray-600);margin:0;line-height:1.5;">
            <?= htmlspecialchars($s['description'] ?: 'Comprehensive care and consultation provided by RHU medical staff.') ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECTION 4: HOLIDAYS & CLINIC GUIDELINES -->
  <section style="margin-top:48px;display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px;">
    <!-- Holidays Card -->
    <div class="card">
      <div class="card-header">
        <h5><i class="fa-solid fa-calendar-xmark text-danger"></i> Official Holidays &amp; Clinic Closures</h5>
      </div>
      <div class="card-body">
        <p style="font-size:13px;color:var(--gray-600);margin-bottom:14px;">
          The clinic does not hold regular appointment sessions on the following dates:
        </p>
        <?php if (empty($holidays)): ?>
          <p style="font-size:13px;color:var(--gray-500);font-style:italic;">No upcoming scheduled holidays recorded.</p>
        <?php else: ?>
          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($holidays as $h): ?>
              <li style="display:flex;align-items:center;justify-content:space-between;background:var(--gray-100);padding:10px 14px;border-radius:8px;font-size:13px;">
                <div>
                  <strong><?= htmlspecialchars($h['name']) ?></strong>
                  <div style="font-size:11px;color:var(--gray-600);"><?= date('l, F j, Y', strtotime($h['date'])) ?></div>
                </div>
                <span class="badge badge-danger">Closed</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Patient Guidelines Card -->
    <div class="card">
      <div class="card-header">
        <h5><i class="fa-solid fa-clipboard-check text-primary"></i> Patient Visit Guidelines</h5>
      </div>
      <div class="card-body" style="font-size:13px;color:var(--gray-700);line-height:1.6;">
        <ul style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:8px;">
          <li><strong>Arrival:</strong> Please arrive <strong>10–15 minutes</strong> before your scheduled appointment slot for vital signs triage.</li>
          <li><strong>Identification:</strong> Bring at least one (1) valid government-issued ID or Barangay Certification.</li>
          <li><strong>PhilHealth:</strong> If you are an active PhilHealth member, present your MDR or Member ID for Konsulta benefits.</li>
          <li><strong>Medical Records:</strong> Bring past prescriptions, lab results, or immunization cards if applicable.</li>
          <li><strong>Cancellation:</strong> If you cannot attend, please cancel your appointment online at least 4 hours in advance.</li>
        </ul>
      </div>
    </div>
  </section>

</main>

<!-- Reservation / Booking Prompt Modal for Guests -->
<div class="modal-overlay" id="slotActionModal">
  <div class="modal-box sm" style="background:#ffffff;box-shadow:0 25px 60px rgba(0,0,0,0.35);border:1px solid var(--gray-300);position:relative;z-index:1001;">
    <div class="modal-header">
      <h5><i class="fa-solid fa-calendar-check text-primary"></i> Reserve Appointment Slot</h5>
      <button class="modal-close" data-modal-close="slotActionModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align:center;padding:24px 20px;">
      <div style="width:54px;height:54px;border-radius:50%;background:#e8f5ee;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px;">
        <i class="fa-regular fa-clock"></i>
      </div>
      
      <h4 id="modalSlotTitle" style="font-size:18px;color:var(--gray-800);margin-bottom:8px;">Selected Slot</h4>
      <p id="modalSlotDetails" style="font-size:13px;color:var(--gray-600);margin-bottom:20px;">
        Date and Time Information
      </p>

      <div class="alert alert-info" style="font-size:13px;text-align:left;margin-bottom:20px;">
        <i class="fa-solid fa-circle-info"></i>
        <div>An active patient account is required to confirm and record your medical appointment.</div>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <a id="modalSignInBtn" href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-block">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In to Confirm Slot
        </a>
        <a href="<?= BASE_URL ?>/views/user/signup.php" class="btn btn-outline-primary btn-block">
          <i class="fa-solid fa-user-plus"></i> Create Free Patient Account
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="guest-footer">
  <div class="guest-footer-container">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <div style="width:36px;height:36px;border-radius:8px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;">
          <i class="fa-solid fa-hospital"></i>
        </div>
        <strong style="font-size:16px;color:var(--gray-800);">RHU Rizal Cagayan</strong>
      </div>
      <p style="font-size:13px;color:var(--gray-600);line-height:1.6;">
        Rural Health Unit of the Municipality of Rizal, Cagayan Valley. Providing accessible, community-centered primary healthcare.
      </p>
    </div>

    <div>
      <h5 style="font-size:14px;color:var(--gray-800);margin-bottom:12px;">Quick Access</h5>
      <ul style="list-style:none;padding:0;margin:0;font-size:13px;display:flex;flex-direction:column;gap:8px;">
        <li><a href="#vacant-slots" class="link-primary"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> Vacant Slot Checker</a></li>
        <li><a href="#doctors-roster" class="link-primary"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> Doctor Schedules</a></li>
        <li><a href="#services-directory" class="link-primary"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> Available Services</a></li>
        <li><a href="<?= BASE_URL ?>/index.php" class="link-primary"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> Patient Portal Login</a></li>
        <li><a href="<?= BASE_URL ?>/views/admin/login.php" class="link-primary"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i> Healthcare Staff Login</a></li>
      </ul>
    </div>

    <div>
      <h5 style="font-size:14px;color:var(--gray-800);margin-bottom:12px;">Emergency &amp; Contacts</h5>
      <div style="font-size:13px;color:var(--gray-600);display:flex;flex-direction:column;gap:8px;">
        <div><i class="fa-solid fa-phone text-primary"></i> (078) 123-4567 / 0917-000-0000</div>
        <div><i class="fa-solid fa-envelope text-primary"></i> support@rhurizal.gov.ph</div>
        <div><i class="fa-solid fa-clock text-primary"></i> Monday – Friday: 8:00 AM – 5:00 PM</div>
      </div>
    </div>
  </div>

  <div style="text-align:center;border-top:1px solid var(--gray-300);padding-top:20px;font-size:12px;color:var(--gray-500);">
    &copy; <?= date('Y') ?> RHU Rizal Online Medical Appointment System (OMASORR). All rights reserved.
  </div>
</footer>

<!-- Scripts -->
<script>
  const BASE = '<?= BASE_URL ?>';
  const IS_PATIENT = <?= $isPatient ? 'true' : 'false' ?>;

  const TIME_SLOTS = [
    ['08:00','8:00 AM'],['08:30','8:30 AM'],['09:00','9:00 AM'],['09:30','9:30 AM'],
    ['10:00','10:00 AM'],['10:30','10:30 AM'],['11:00','11:00 AM'],['11:30','11:30 AM'],
    ['13:00','1:00 PM'],['13:30','1:30 PM'],['14:00','2:00 PM'],['14:30','2:30 PM'],
    ['15:00','3:00 PM'],['15:30','3:30 PM'],['16:00','4:00 PM']
  ];

  // Holidays array from PHP
  const HOLIDAYS = <?= json_encode(array_column($holidays, 'date')) ?>;

  function setQuickDate(offsetDays) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const dateStr = d.toISOString().split('T')[0];
    document.getElementById('targetDate').value = dateStr;
    refreshSlots();
  }

  function setNextWeekday() {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    while (d.getDay() === 0 || d.getDay() === 6) { // Skip Saturday and Sunday
      d.setDate(d.getDate() + 1);
    }
    document.getElementById('targetDate').value = d.toISOString().split('T')[0];
    refreshSlots();
  }

  function selectDoctorAndScroll(docId) {
    const select = document.getElementById('doctorFilter');
    select.value = docId;
    document.getElementById('vacant-slots').scrollIntoView({ behavior: 'smooth' });
    refreshSlots();
  }

  async function refreshSlots() {
    const dateInput = document.getElementById('targetDate');
    const doctorInput = document.getElementById('doctorFilter');
    const container = document.getElementById('slotsContainer');
    const dateDisplay = document.getElementById('selectedDateDisplay');
    const dateSubText = document.getElementById('dateSubText');
    const badgeContainer = document.getElementById('vacancySummaryBadge');

    const dateVal = dateInput.value;
    const doctorId = parseInt(doctorInput.value, 10) || 0;

    if (!dateVal) {
      alert('Please select a valid date.');
      return;
    }

    const dateObj = new Date(dateVal + 'T00:00:00');
    const dayOfWeek = dateObj.getDay(); // 0 = Sunday, 6 = Saturday
    const isSunday = (dayOfWeek === 0);
    const isSaturday = (dayOfWeek === 6);
    const isHoliday = HOLIDAYS.includes(dateVal);

    // Format date string
    const formattedDate = dateObj.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    dateDisplay.textContent = formattedDate;

    // Check closed status
    if (isSunday || isHoliday) {
      const reason = isSunday ? 'Clinic is closed on Sundays.' : 'Clinic is closed for an official holiday.';
      dateSubText.textContent = reason;
      badgeContainer.innerHTML = '<span class="badge badge-danger" style="font-size:12px;padding:6px 12px;"><i class="fa-solid fa-lock"></i> Clinic Closed</span>';
      
      container.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:40px 20px;background:#fdf2e9;border-radius:10px;border:1px solid #f5cba7;">
          <i class="fa-solid fa-calendar-xmark text-danger" style="font-size:32px;margin-bottom:10px;"></i>
          <h4 style="color:#b9770e;margin-bottom:6px;">Clinic Closed on this Date</h4>
          <p style="color:var(--gray-700);margin:0;font-size:13px;">${reason} Please select a weekday (Monday to Friday) to view open appointment slots.</p>
        </div>
      `;
      return;
    }

    dateSubText.textContent = isSaturday ? 'Saturday: Emergency and special scheduled consultations only.' : 'Regular clinic hours: 8:00 AM – 5:00 PM.';
    container.innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--gray-600);">
        <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
        <p style="margin-top:8px;">Fetching vacancy data...</p>
      </div>
    `;

    let bookedTimes = [];
    try {
      const res = await fetch(`${BASE}/actions/api/get-booked-dates.php?date=${dateVal}&doctor_id=${doctorId}`);
      const data = await res.json();
      bookedTimes = data.booked_times || [];
    } catch(e) {
      console.error('Error fetching booked dates', e);
    }

    let vacantCount = 0;
    const now = new Date();
    const isToday = (now.toISOString().split('T')[0] === dateVal);
    const currentHourMin = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');

    const html = TIME_SLOTS.map(([timeVal, label]) => {
      const isTaken = bookedTimes.includes(timeVal);
      const isPast = isToday && (timeVal < currentHourMin);

      if (isPast) {
        return `
          <div class="slot-card closed" title="Time has passed for today">
            <div class="slot-time">${label}</div>
            <span class="slot-status-pill closed">Elapsed</span>
          </div>
        `;
      }

      if (isTaken) {
        return `
          <div class="slot-card booked" title="Already booked by another patient">
            <div class="slot-time">${label}</div>
            <span class="slot-status-pill booked"><i class="fa-solid fa-circle-xmark"></i> Booked</span>
          </div>
        `;
      }

      vacantCount++;
      const docName = doctorInput.options[doctorInput.selectedIndex].text;
      return `
        <div class="slot-card vacant" onclick="handleSlotClick('${dateVal}', '${timeVal}', '${label}', '${doctorId}', '${encodeURIComponent(docName)}')" title="Click to reserve this slot">
          <div class="slot-time">${label}</div>
          <span class="slot-status-pill vacant"><i class="fa-solid fa-circle-check"></i> Vacant</span>
          <div style="font-size:10px;color:var(--primary);margin-top:6px;font-weight:600;">Click to Book &rarr;</div>
        </div>
      `;
    }).join('');

    container.innerHTML = html;

    badgeContainer.innerHTML = `
      <span class="badge ${vacantCount > 0 ? 'badge-success' : 'badge-danger'}" style="font-size:12px;padding:6px 12px;">
        <i class="fa-solid ${vacantCount > 0 ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
        ${vacantCount} of ${TIME_SLOTS.length} Slots Vacant
      </span>
    `;
  }

  function handleSlotClick(date, time, timeLabel, doctorId, encodedDocName) {
    const docName = decodeURIComponent(encodedDocName);

    if (IS_PATIENT) {
      // Direct booking for logged-in patient
      const docParam = doctorId > 0 ? `&doctor_id=${doctorId}` : '';
      window.location.href = `${BASE}/views/user/book-appointment.php?date=${date}&time=${time}${docParam}`;
      return;
    }

    // Guest prompt modal
    document.getElementById('modalSlotTitle').textContent = `Slot: ${timeLabel}`;
    document.getElementById('modalSlotDetails').innerHTML = `
      <strong>Date:</strong> ${date}<br>
      <strong>Time:</strong> ${timeLabel}<br>
      <strong>Doctor:</strong> ${docName}
    `;

    const redirectUrl = encodeURIComponent(`${BASE}/views/user/book-appointment.php?date=${date}&time=${time}${doctorId > 0 ? '&doctor_id=' + doctorId : ''}`);
    document.getElementById('modalSignInBtn').href = `${BASE}/index.php?redirect=${redirectUrl}`;

    openModal('slotActionModal');
  }

  // Initial load on page ready
  document.addEventListener('DOMContentLoaded', () => {
    refreshSlots();
    document.getElementById('targetDate').addEventListener('change', refreshSlots);
    document.getElementById('doctorFilter').addEventListener('change', refreshSlots);
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
