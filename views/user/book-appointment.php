<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireLogin('patient');
$session  = getPatientSession();
$fullName = $session['full_name'];
$initial  = strtoupper(mb_substr($fullName, 0, 1));

$pdo      = db();
$services = $pdo->query("SELECT id, name FROM services ORDER BY name")->fetchAll();
$doctors  = $pdo->query("SELECT id, name, specialty FROM doctors WHERE available = 1 ORDER BY name")->fetchAll();

$flash = getFlash('book_error');

$pageTitle = 'Book Appointment – RHU Rizal';
require_once __DIR__ . '/../../includes/header.php';
?>
<body>
  <div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/user-sidebar.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left" style="display:flex;align-items:center;gap:12px">
          <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');"><i class="fa-solid fa-bars"></i></button>
          <div><h4>Book Appointment</h4><p>Schedule a new medical appointment</p></div>
        </div>
        <div class="topbar-right">
          <div class="topbar-user" onclick="window.location.href='<?= BASE_URL ?>/views/user/profile.php'">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div><span class="user-name"><?= htmlspecialchars($fullName) ?></span>
          </div>
        </div>
      </header>

      <div class="page-content">
        <div class="grid-2" style="align-items:start">
          <!-- Booking Form -->
          <div class="card">
            <div class="card-header"><h5><i class="fa-solid fa-calendar-plus"></i> Appointment Details</h5></div>
            <div class="card-body">
              <?php if ($flash): ?>
              <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($flash['message']) ?></div>
              </div>
              <?php endif; ?>
              <form method="post" action="<?= BASE_URL ?>/actions/book-appointment.php" id="bookingForm">
              <?= csrfField() ?>
              <div class="form-group">
                <label class="form-label">Service Type *</label>
                <select class="form-select" id="serviceType" name="service_id" required>
                  <option value="">-- Select Service --</option>
                  <?php foreach ($services as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Doctor *</label>
                <select class="form-select" id="doctor" name="doctor_id" required>
                  <option value="">-- Select Doctor --</option>
                  <?php foreach ($doctors as $doc): ?>
                  <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?> – <?= htmlspecialchars($doc['specialty']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Preferred Date *</label>
                  <input type="date" class="form-control" id="aptDate" name="date" min="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Preferred Time *</label>
                  <select class="form-select" id="aptTime" name="time" required>
                    <option value="">-- Select Time --</option>
                    <option value="08:00">8:00 AM</option><option value="08:30">8:30 AM</option>
                    <option value="09:00">9:00 AM</option><option value="09:30">9:30 AM</option>
                    <option value="10:00">10:00 AM</option><option value="10:30">10:30 AM</option>
                    <option value="11:00">11:00 AM</option><option value="11:30">11:30 AM</option>
                    <option value="13:00">1:00 PM</option><option value="13:30">1:30 PM</option>
                    <option value="14:00">2:00 PM</option><option value="14:30">2:30 PM</option>
                    <option value="15:00">3:00 PM</option><option value="15:30">3:30 PM</option>
                    <option value="16:00">4:00 PM</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Reason / Chief Complaint *</label>
                <textarea class="form-control" id="aptReason" name="reason" rows="3" placeholder="Describe your reason for visit..." required></textarea>
              </div>
              <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <div>Please bring a valid ID and your health card on the day of your appointment.</div>
              </div>
              <div class="flex-gap">
                <button type="button" class="btn btn-outline-primary" onclick="checkAvailability()"><i class="fa-solid fa-magnifying-glass"></i> Check Availability</button>
                <button type="button" class="btn btn-primary" style="flex:1" onclick="previewBooking()"><i class="fa-solid fa-calendar-check"></i> Book Appointment</button>
              </div>
              </form>
            </div>
          </div>

          <!-- Calendar -->
          <div>
            <div class="card mb-3">
              <div class="card-header"><h5><i class="fa-solid fa-calendar"></i> Availability Calendar</h5></div>
              <div class="card-body" style="padding:0"><div id="bookingCalendar"></div></div>
            </div>
            <div class="card" id="timeSlotsCard" style="display:none">
              <div class="card-header"><h5><i class="fa-solid fa-clock"></i> Available Time Slots</h5></div>
              <div class="card-body">
                <div id="timeSlots" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
      <div class="modal-header">
        <h5><i class="fa-solid fa-circle-check"></i> Confirm Appointment</h5>
        <button class="modal-close" data-modal-close="confirmModal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-success mb-2"><i class="fa-solid fa-check-circle"></i><div>Please review your appointment details before confirming.</div></div>
        <div class="detail-list" id="confirmDetails"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-modal-close="confirmModal">Cancel</button>
        <button class="btn btn-primary" id="confirmBtn" onclick="document.getElementById('bookingForm').submit()"><i class="fa-solid fa-calendar-check"></i> Confirm Booking</button>
      </div>
    </div>
  </div>

<?php
$extraScripts = <<<'JS'
<script>
  const BASE = '/rhu-appointment-system';

  const cal = new RHUCalendar('bookingCalendar', {
    onSelect: (date) => {
      document.getElementById('aptDate').value = date;
      loadTimeSlots(date);
    }
  });

  document.getElementById('aptDate').addEventListener('change', function() {
    loadTimeSlots(this.value);
  });

  async function loadTimeSlots(date) {
    const card      = document.getElementById('timeSlotsCard');
    const container = document.getElementById('timeSlots');
    card.style.display = 'block';
    container.innerHTML = '<p style="text-align:center;color:#888;font-size:13px">Loading...</p>';

    let bookedTimes = [];
    try {
      const res  = await fetch(`${BASE}/actions/api/get-booked-dates.php?date=${date}`);
      const data = await res.json();
      bookedTimes = data.booked_times || [];
    } catch(e) {}

    const slots = [
      ['08:00','8:00 AM'],['08:30','8:30 AM'],['09:00','9:00 AM'],['09:30','9:30 AM'],
      ['10:00','10:00 AM'],['10:30','10:30 AM'],['11:00','11:00 AM'],['11:30','11:30 AM'],
      ['13:00','1:00 PM'],['13:30','1:30 PM'],['14:00','2:00 PM'],['14:30','2:30 PM'],
      ['15:00','3:00 PM'],['15:30','3:30 PM'],['16:00','4:00 PM']
    ];
    container.innerHTML = slots.map(([val, label]) => {
      const taken = bookedTimes.includes(val);
      return `<div onclick="${!taken ? `selectTimeSlot('${val}', this)` : ''}"
        style="padding:8px;text-align:center;border-radius:8px;font-size:12px;font-weight:500;
               cursor:${taken?'not-allowed':'pointer'};
               background:${taken?'#fdecea':'#e8f5ee'};
               color:${taken?'var(--danger)':'var(--primary)'};
               border:1.5px solid ${taken?'#f1948a':'#82e0aa'};transition:var(--transition);">
        ${label}<br><span style="font-size:10px;opacity:.7">${taken?'Taken':'Available'}</span>
      </div>`;
    }).join('');
  }

  function selectTimeSlot(time, el) {
    document.getElementById('aptTime').value = time;
    document.querySelectorAll('#timeSlots > div').forEach(d => d.style.outline = 'none');
    el.style.outline = '2px solid var(--primary)';
    showToast('Time slot selected: ' + formatTime(time), 'info');
  }

  function checkAvailability() {
    const date = document.getElementById('aptDate').value;
    if (!date) { showToast('Please select a date first.', 'warning'); return; }
    loadTimeSlots(date);
    showToast('Availability loaded for ' + formatDate(date), 'info');
  }

  function previewBooking() {
    const serviceEl = document.getElementById('serviceType');
    const doctorEl  = document.getElementById('doctor');
    const date      = document.getElementById('aptDate').value;
    const time      = document.getElementById('aptTime').value;
    const reason    = document.getElementById('aptReason').value.trim();

    const serviceText = serviceEl.options[serviceEl.selectedIndex]?.text;
    const doctorText  = doctorEl.options[doctorEl.selectedIndex]?.text;

    if (!serviceEl.value || !doctorEl.value || !date || !time || !reason) {
      showToast('Please fill in all required fields.', 'warning'); return;
    }

    document.getElementById('confirmDetails').innerHTML = `
      <div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">${serviceText}</div></div>
      <div class="detail-item"><div class="detail-label">Doctor</div><div class="detail-value">${doctorText}</div></div>
      <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">${formatDate(date)}</div></div>
      <div class="detail-item"><div class="detail-label">Time</div><div class="detail-value">${formatTime(time)}</div></div>
      <div class="detail-item"><div class="detail-label">Reason</div><div class="detail-value">${reason}</div></div>`;
    openModal('confirmModal');
  }
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
