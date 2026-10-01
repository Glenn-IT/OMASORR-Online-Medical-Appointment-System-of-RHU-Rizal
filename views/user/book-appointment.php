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
$doctors  = $pdo->query("SELECT id, name, specialty, schedule FROM doctors WHERE available = 1 ORDER BY name")->fetchAll();

$prefillDoc  = (int) ($_GET['doctor_id'] ?? 0);
$prefillDate = trim($_GET['date'] ?? '');
$prefillTime = trim($_GET['time'] ?? '');

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
                  <?php 
                    $docDutyDays = parseDoctorScheduleDays($doc['schedule'] ?? '');
                    $docDutyStr  = implode(', ', $docDutyDays);
                  ?>
                  <option value="<?= $doc['id'] ?>" 
                          data-schedule="<?= htmlspecialchars($doc['schedule'] ?? 'Mon-Fri') ?>" 
                          data-days='<?= htmlspecialchars(json_encode($docDutyDays), ENT_QUOTES, 'UTF-8') ?>' 
                          <?= $doc['id'] == $prefillDoc ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doc['name']) ?> – <?= htmlspecialchars($doc['specialty']) ?> (<?= htmlspecialchars($docDutyStr) ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
                <div class="alert alert-info mt-1" id="doctorDutyNotice" style="display:none;margin-bottom:0;padding:8px 12px;font-size:12px;">
                  <i class="fa-solid fa-calendar-check"></i>
                  <span id="doctorDutyText"></span>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Preferred Date * <small style="color:var(--gray-600);font-weight:normal;">(Weekdays only)</small></label>
                  <input type="date" class="form-control" id="aptDate" name="date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($prefillDate) ?>" required />
                  <div class="alert alert-warning mt-1" id="dateNotice" style="display:none;margin-bottom:0;padding:8px 12px;font-size:12px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span id="dateNoticeText"></span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Preferred Time *</label>
                  <select class="form-select" id="aptTime" name="time" required>
                    <option value="">-- Select a date first --</option>
                  </select>
                  <div class="alert alert-warning mt-1" id="timeNotice" style="display:none;margin-bottom:0;padding:8px 12px">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>That time slot is already booked. Please choose another.</div>
                  </div>
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

  const TIME_SLOTS = [
    ['08:00','8:00 AM'],['08:30','8:30 AM'],['09:00','9:00 AM'],['09:30','9:30 AM'],
    ['10:00','10:00 AM'],['10:30','10:30 AM'],['11:00','11:00 AM'],['11:30','11:30 AM'],
    ['13:00','1:00 PM'],['13:30','1:30 PM'],['14:00','2:00 PM'],['14:30','2:30 PM'],
    ['15:00','3:00 PM'],['15:30','3:30 PM'],['16:00','4:00 PM']
  ];

  const cal = new RHUCalendar('bookingCalendar', {
    onSelect: (date) => {
      document.getElementById('aptDate').value = date;
      handleDateOrDoctorChange();
    }
  });

  function getSelectedDoctorDays() {
    const doctorEl = document.getElementById('doctor');
    const opt = doctorEl?.options[doctorEl.selectedIndex];
    if (!opt || !opt.value) return null;
    try {
      return JSON.parse(opt.getAttribute('data-days') || '[]');
    } catch(e) {
      return null;
    }
  }

  function validateSchedule(dateStr) {
    if (!dateStr) return { valid: false, message: 'Please select a date.' };
    const d = new Date(dateStr + 'T00:00:00');
    const dayOfWeek = d.getDay(); // 0 = Sun, 6 = Sat

    if (dayOfWeek === 0 || dayOfWeek === 6) {
      return {
        valid: false,
        isWeekend: true,
        message: 'The RHU is closed on weekends (Saturday and Sunday). Please select a weekday (Monday to Friday).'
      };
    }

    const doctorDays = getSelectedDoctorDays();
    if (doctorDays && doctorDays.length > 0) {
      const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const shortDay = dayNames[dayOfWeek];
      if (!doctorDays.includes(shortDay)) {
        const fullDay = d.toLocaleDateString('en-US', { weekday: 'long' });
        return {
          valid: false,
          isDoctorOff: true,
          message: `The selected doctor is not on duty on ${fullDay}. Regular schedule: ${doctorDays.join(', ')}.`
        };
      }
    }

    return { valid: true };
  }

  function updateDoctorDutyBanner() {
    const doctorEl = document.getElementById('doctor');
    const noticeEl = document.getElementById('doctorDutyNotice');
    const textEl   = document.getElementById('doctorDutyText');
    const opt      = doctorEl?.options[doctorEl.selectedIndex];

    if (!opt || !opt.value) {
      if (noticeEl) noticeEl.style.display = 'none';
      cal.setDoctorFilter(null);
      return;
    }

    const dutyDays = getSelectedDoctorDays();
    if (dutyDays && dutyDays.length > 0) {
      if (noticeEl && textEl) {
        textEl.innerHTML = `<strong>Doctor Schedule:</strong> Active on <strong>${dutyDays.join(', ')}</strong> (Weekdays only, 8:00 AM – 5:00 PM).`;
        noticeEl.style.display = 'flex';
      }
      cal.setDoctorFilter(dutyDays);
    } else {
      if (noticeEl) noticeEl.style.display = 'none';
      cal.setDoctorFilter(null);
    }
  }

  function handleDateOrDoctorChange() {
    const date = document.getElementById('aptDate').value;
    const dateNotice = document.getElementById('dateNotice');
    const dateNoticeText = document.getElementById('dateNoticeText');

    if (!date) {
      if (dateNotice) dateNotice.style.display = 'none';
      return;
    }

    const check = validateSchedule(date);
    if (!check.valid) {
      if (dateNotice && dateNoticeText) {
        dateNoticeText.textContent = check.message;
        dateNotice.style.display = 'flex';
      }
      // Show closed status in slot container
      showClosedScheduleWarning(check.message);
      return;
    }

    if (dateNotice) dateNotice.style.display = 'none';
    loadTimeSlots(date);
  }

  function showClosedScheduleWarning(message) {
    const card      = document.getElementById('timeSlotsCard');
    const container = document.getElementById('timeSlots');
    const selectEl  = document.getElementById('aptTime');
    const notice    = document.getElementById('timeNotice');
    if (notice) notice.style.display = 'none';

    card.style.display = 'block';
    selectEl.innerHTML = '<option value="">-- No slots available (Closed / Off Duty) --</option>';
    selectEl.value = '';

    container.innerHTML = `
      <div style="grid-column:1/-1;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:10px;padding:16px 20px;text-align:center;">
        <div style="font-size:20px;margin-bottom:6px;"><i class="fa-solid fa-calendar-xmark text-danger"></i></div>
        <strong style="display:block;font-size:14px;margin-bottom:4px;">Date Unavailable for Booking</strong>
        <p style="margin:0;font-size:13px;line-height:1.5;">${message}</p>
      </div>
    `;
  }

  document.getElementById('aptDate').addEventListener('change', handleDateOrDoctorChange);

  document.getElementById('doctor')?.addEventListener('change', function() {
    updateDoctorDutyBanner();
    handleDateOrDoctorChange();
  });

  async function loadTimeSlots(date) {
    const card      = document.getElementById('timeSlotsCard');
    const container = document.getElementById('timeSlots');
    const selectEl  = document.getElementById('aptTime');
    const notice    = document.getElementById('timeNotice');
    const doctorEl  = document.getElementById('doctor');
    const doctorId  = doctorEl ? doctorEl.value : '';
    const prevVal   = selectEl.value;

    card.style.display = 'block';
    container.innerHTML = '<p style="text-align:center;color:#888;font-size:13px"><i class="fa-solid fa-spinner fa-spin"></i> Loading availability...</p>';

    let bookedTimes = [];
    let pastTimes   = [];
    try {
      const res  = await fetch(`${BASE}/actions/api/get-booked-dates.php?date=${date}&doctor_id=${doctorId}`);
      const data = await res.json();
      if (data.is_closed) {
        showClosedScheduleWarning(data.closed_reason || 'Clinic is closed on this date.');
        return;
      }
      bookedTimes = data.booked_times || [];
      pastTimes   = data.past_times || [];
    } catch(e) {}

    // Check if date is today in local time
    const now = new Date();
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const isToday = (date === todayStr);
    const currentHourMin = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

    let availableCount = 0;

    // Rebuild select options to reflect availability for the chosen date
    selectEl.innerHTML = '<option value="">-- Select Time --</option>' +
      TIME_SLOTS.map(([val, label]) => {
        const isPast = isToday && (val <= currentHourMin || pastTimes.includes(val));
        const taken  = bookedTimes.includes(val);
        const disabled = taken || isPast;

        let suffix = '';
        if (isPast) {
          suffix = ' — Passed';
        } else if (taken) {
          suffix = ' — Booked';
        } else {
          availableCount++;
        }

        return `<option value="${val}"${disabled ? ' disabled' : ''}>${label}${suffix}</option>`;
      }).join('');

    // Restore previous selection if it is still available; warn if it is now taken/passed
    const prevIsUnavailable = prevVal && (bookedTimes.includes(prevVal) || (isToday && (prevVal <= currentHourMin || pastTimes.includes(prevVal))));
    if (prevIsUnavailable) {
      selectEl.value = '';
      if (notice) notice.style.display = 'flex';
    } else if (prevVal) {
      selectEl.value = prevVal;
      if (notice) notice.style.display = 'none';
    } else {
      if (notice) notice.style.display = 'none';
    }

    // Notice if all slots today have ended
    let noticeHtml = '';
    if (isToday && availableCount === 0) {
      noticeHtml = `<div style="grid-column:1/-1;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:8px;padding:10px 14px;font-size:12px;margin-bottom:8px;">
        <i class="fa-solid fa-clock"></i> <strong>Today's appointment hours have ended.</strong> Please choose tomorrow or a future clinic date.
      </div>`;
    }

    // Update visual time-slot grid
    container.innerHTML = noticeHtml + TIME_SLOTS.map(([val, label]) => {
      const isPast = isToday && (val <= currentHourMin || pastTimes.includes(val));
      const taken  = bookedTimes.includes(val);
      const isAvailable = !taken && !isPast;

      let bg = '#e8f5ee';
      let color = 'var(--primary)';
      let border = '1.5px solid #82e0aa';
      let cursor = 'pointer';
      let statusText = 'Available';
      let title = 'Click to select this slot';

      if (isPast) {
        bg = '#f3f4f6';
        color = '#9ca3af';
        border = '1.5px solid #e5e7eb';
        cursor = 'not-allowed';
        statusText = 'Passed';
        title = 'This time slot has already passed for today';
      } else if (taken) {
        bg = '#fdecea';
        color = 'var(--danger)';
        border = '1.5px solid #f1948a';
        cursor = 'not-allowed';
        statusText = 'Taken';
        title = 'This slot is already booked';
      }

      return `<div onclick="${isAvailable ? `selectTimeSlot('${val}', this)` : ''}"
        style="padding:8px;text-align:center;border-radius:8px;font-size:12px;font-weight:500;
               cursor:${cursor};
               background:${bg};
               color:${color};
               border:${border};transition:var(--transition);"
        title="${title}">
        ${label}<br><span style="font-size:10px;opacity:.8">${statusText}</span>
      </div>`;
    }).join('');
  }

  function selectTimeSlot(time, el) {
    document.getElementById('aptTime').value = time;
    const notice = document.getElementById('timeNotice');
    if (notice) notice.style.display = 'none';
    document.querySelectorAll('#timeSlots > div').forEach(d => d.style.outline = 'none');
    el.style.outline = '2px solid var(--primary)';
    showToast('Time slot selected: ' + formatTime(time), 'info');
  }

  function checkAvailability() {
    const date = document.getElementById('aptDate').value;
    if (!date) { showToast('Please select a date first.', 'warning'); return; }
    const check = validateSchedule(date);
    if (!check.valid) {
      showToast(check.message, 'warning');
      handleDateOrDoctorChange();
      return;
    }
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

    const check = validateSchedule(date);
    if (!check.valid) {
      showToast(check.message, 'warning');
      document.getElementById('aptDate').focus();
      return;
    }

    document.getElementById('confirmDetails').innerHTML = `
      <div class="detail-item"><div class="detail-label">Service</div><div class="detail-value">${serviceText}</div></div>
      <div class="detail-item"><div class="detail-label">Doctor</div><div class="detail-value">${doctorText}</div></div>
      <div class="detail-item"><div class="detail-label">Date</div><div class="detail-value">${formatDate(date)}</div></div>
      <div class="detail-item"><div class="detail-label">Time</div><div class="detail-value">${formatTime(time)}</div></div>
      <div class="detail-item"><div class="detail-label">Reason</div><div class="detail-value">${reason}</div></div>`;
    openModal('confirmModal');
  }

  // Pre-fill from Guest Schedule Viewer if passed
  const PREFILL_DATE = '<?= htmlspecialchars($prefillDate) ?>';
  const PREFILL_TIME = '<?= htmlspecialchars($prefillTime) ?>';

  // Initialize doctor duty banner if prefilled
  document.addEventListener('DOMContentLoaded', () => {
    updateDoctorDutyBanner();
    if (PREFILL_DATE) {
      handleDateOrDoctorChange();
      if (PREFILL_TIME) {
        setTimeout(() => {
          document.getElementById('aptTime').value = PREFILL_TIME;
          const matchingSlot = Array.from(document.querySelectorAll('#timeSlots > div')).find(d => d.textContent.includes(formatTime(PREFILL_TIME)));
          if (matchingSlot) {
            matchingSlot.style.outline = '2px solid var(--primary)';
          }
        }, 300);
      }
    }
  });
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>
