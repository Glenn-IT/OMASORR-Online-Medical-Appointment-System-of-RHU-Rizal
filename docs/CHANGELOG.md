# RHU Rizal Appointment System — Changelog

---

## [2026-06-02] Fix Patient Records — "Registered" Date Shows "Invalid"

### Module

Patient Records (`views/admin/patients.php`, `assets/js/app.js`)

### Type

- Bug Fix

### Issue

The **Registered** column in the Patient Records table and the **Registered** field inside the patient details modal both displayed "Invalid Date" instead of a human-readable date.

The `formatDate()` utility in `app.js` assumes its input is a plain date string (`YYYY-MM-DD`) and appends `"T00:00:00"` before constructing a `Date` object. However, the `created_at` column in MySQL is a `DATETIME`, so the value passed to the function was `"2026-05-15 10:30:00"`. Appending `"T00:00:00"` to that produced `"2026-05-15 10:30:00T00:00:00"`, which is not a valid ISO 8601 string, causing `new Date()` to return `Invalid Date`.

### Solution

Updated `formatDate()` to strip the time portion (everything after the first space or `T`) before constructing the date:

```js
const datePart = String(dateStr).split(/[T ]/)[0]; // strip time from DATETIME strings
const d = new Date(datePart + "T00:00:00");
```

This handles both plain date strings (`YYYY-MM-DD`) and full datetime strings (`YYYY-MM-DD HH:MM:SS`) from MySQL.

### Files Modified

- `assets/js/app.js`

### Status

✅ Completed

---

## [2026-06-02] Fix Reports Tab — Corrupted File Causing PHP Parse Errors and Wrong Admin Name

### Module

Reports (`views/admin/reports.php`)

### Type

- Bug Fix

### Issue

The Reports page failed to load entirely due to file corruption. The `require_once` statement for `config/database.php` had HTML from the topbar section injected into the middle of the string literal, producing a PHP parse error on every request:

```
Parse error: syntax error, unexpected token in views/admin/reports.php on line 3
```

Additional issues in the corrupted file:
- The topbar's avatar and username were hardcoded as `"A"` / `"Admin"` instead of using the `$initial` / `$adminName` PHP variables.
- The closing `</div>` block for the page content and charts section was duplicated, resulting in broken HTML structure.
- The Export CSV button was inside the corrupted `require_once` block and was therefore lost.

### Solution

Rewrote `reports.php` from scratch, restoring:
- Correct `require_once` includes (`config/auth.php`, `config/database.php`)
- Dynamic topbar avatar and username from `getAdminSession()`
- Export CSV button (restored, uses existing `actions/admin/export-report.php`)
- Clean, single closing HTML structure (removed duplicated block)
- All filter logic, stats calculation, summary table, and Chart.js charts preserved

### Files Modified

- `views/admin/reports.php`

### Status

✅ Completed

---

## [2026-06-02] Fix Admin Appointments — Invalid CSRF Token on View Modal Actions

### Module

Manage Appointments (`views/admin/appointments.php`)

### Type

- Bug Fix

### Issue

Clicking **Approve**, **Reject**, or **Cancel** inside the "Appointment Details" view modal failed with:

> Invalid CSRF token. Please go back and try again.

The same buttons in the data-grid table worked correctly. The root cause was PHP heredoc variable interpolation. The JavaScript `viewAppointment()` function built the modal's action forms as a JS template literal inside a PHP `<<<SCRIPTS` heredoc. Inside heredocs, PHP interprets `${NAME}` as a PHP variable — so `${CSRF_TOKEN}` (meant as a JS template expression) was resolved by PHP as the undefined variable `$CSRF_TOKEN`, producing an empty string. The form therefore submitted an empty token, which failed `hash_equals()`.

### Solution

Rewrote the `viewAppointment()` HTML-building code to use plain JS string concatenation (`+`) instead of template literals. The JS `CSRF_TOKEN` constant is now safely concatenated at runtime:

```js
// Before (broken — PHP ate ${CSRF_TOKEN})
acts.innerHTML += `<input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">`;

// After (correct — pure JS string concatenation, no heredoc conflict)
acts.innerHTML +=
  '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">';
```

### Files Modified

- `views/admin/appointments.php`

### Status

✅ Completed

---

## [2026-06-02] Feature — Admin Must Provide Reason When Cancelling or Rejecting an Appointment

### Module

Manage Appointments (`views/admin/appointments.php`, `actions/admin/update-appointment.php`)  
My Appointments — Patient View (`views/user/my-appointments.php`)  
Database (`database/schema.sql`)

### Type

- Feature / Enhancement

### Issue

The Cancel and Reject actions had no way for the admin to record why an appointment was declined or cancelled. The buttons submitted immediately with no explanation, leaving patients with no context when viewing their appointment status.

### Solution

**UI — Reason modal:**  
Clicking **Reject** (Pending row) or **Cancel** (Approved row) — whether from the data-grid table or from inside the "Appointment Details" view modal — now opens a dedicated "Reason" modal before submission. The modal:

- Shows a contextual title ("Cancel Appointment" / "Reject Appointment")
- Requires a non-empty reason via a `<textarea required>`
- Submits a standard POST form with the CSRF token, appointment ID, new status, and the typed reason as `note`

**Server — validation + storage:**  
`update-appointment.php` now:

- Rejects the request server-side if `note` is empty for Cancelled/Rejected transitions
- Saves the reason to the new `admin_note` column on the `appointments` row (in addition to the existing `appointment_logs.note` audit entry)

**Patient view — display:**  
`views/user/my-appointments.php` fetches `admin_note` and displays it as **"Reason from Admin"** (in red) inside the appointment detail modal for any Cancelled or Rejected appointment.

**Database:**  
Added `admin_note TEXT` column to the `appointments` table.  
Run on existing databases:

```sql
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS admin_note TEXT;
```

### Files Modified

- `views/admin/appointments.php`
- `actions/admin/update-appointment.php`
- `views/user/my-appointments.php`
- `database/schema.sql`

### Status

✅ Completed

---

## [2026-06-02] Remove Demo Credentials from Login Page

### Module

User Login (`index.php`)

### Type

- Security Update

### Issue

The patient login page displayed a visible "Demo Credentials" info box showing a sample username and password (`juandc` / `patient123`). This exposed test account credentials to all visitors.

### Solution

Removed the demo credentials `<div class="alert alert-info">` block entirely from `index.php`.

### Files Modified

- `index.php`

### Status

✅ Completed

---

## [2026-06-02] Fix My Appointments — Appointments Not Displayed

### Module

My Appointments (`views/user/my-appointments.php`)

### Type

- Bug Fix

### Issue

The My Appointments page was blank — no appointment records appeared in the table, and no error message was shown. The root cause was a wrong session variable access pattern:

```php
// Broken — getPatientSession() returns a flat array; there is no nested 'patient' key
$patient   = $session['patient'];
$fullName  = trim($patient['first_name'] . ' ' . $patient['last_name']); // keys don't exist
$patientId = (int) $patient['id'];
```

`getPatientSession()` returns `$_SESSION['patient']` directly — a flat array with keys `id`, `patient_no`, `full_name`, `username`, `status`. Accessing `$session['patient']` yielded `null`, so `$patientId` was `0`, and the SQL query `WHERE a.patient_id = 0` returned no rows.

A duplicate `id="deleteModal"` element was also removed from the page.

### Solution

Fixed session access to use the flat array keys directly:

```php
$fullName  = $session['full_name'];
$initial   = strtoupper(mb_substr($fullName, 0, 1));
$patientId = (int) $session['id'];
```

Removed the duplicate `deleteModal` div.

### Files Modified

- `views/user/my-appointments.php`

### Status

✅ Completed

---

## [2026-06-02] Enhance Book Appointment — Time Slot Availability Awareness

### Module

Book Appointment (`views/user/book-appointment.php`)

### Type

- Enhancement

### Issue

The Preferred Time `<select>` dropdown showed all 15 time slots as selectable regardless of whether they were already booked on the chosen date. Users could unknowingly attempt to book a taken slot, only receiving a rejection after form submission. The visual time-slot grid on the right already showed availability, but the dropdown did not reflect this.

### Solution

- Extracted time slots into a shared `TIME_SLOTS` constant used by both the dropdown and the visual grid.
- `loadTimeSlots(date)` now rebuilds the `<select>` options on every date change:
  - Booked slots are `disabled` and labelled `"— Booked"` so they cannot be chosen.
  - Available slots remain selectable as normal.
- If the user had already selected a time that becomes booked after switching dates, the selection is cleared and an inline `#timeNotice` warning banner appears below the select field.
- The visual grid slot cards now include a `title` tooltip for accessibility.
- The select default placeholder changed from `"-- Select Time --"` to `"-- Select a date first --"` to guide users to pick a date before a time.

### Files Modified

- `views/user/book-appointment.php`

### Status

✅ Completed

---

## [2026-06-02] Fix Medical History — Session Access Warnings and Blank Page

### Module

Medical History (`views/user/medical-history.php`)

### Type

- Bug Fix

### Issue

Three PHP warnings were thrown on every page load:

```
Warning: Undefined array key "patient" on line 8
Warning: Trying to access array offset on value of type null on line 9
Warning: Trying to access array offset on value of type null on line 11
```

Same root cause as the My Appointments bug: the page used `$session['patient']` (always `null`) and then accessed `$patient['first_name']`, `$patient['last_name']`, and `$patient['id']` on that null value. `$patientId` resolved to `0`, so the history query returned no rows.

### Solution

Applied the same flat-array fix:

```php
$fullName  = $session['full_name'];
$initial   = strtoupper(mb_substr($fullName, 0, 1));
$patientId = (int) $session['id'];
```

Also cast `id` to `(int)` in the `$histJson` payload so the JS `===` comparison works correctly.

### Files Modified

- `views/user/medical-history.php`

### Status

✅ Completed

---

## [2026-06-02] Fix My Appointments — View Button Not Opening Modal

### Module

My Appointments (`views/user/my-appointments.php`)

### Type

- Bug Fix

### Issue

Clicking the View (eye) button on any appointment row did nothing. The `viewAppointment(id)` function searched the `APPTS` array using strict equality:

```js
const a = APPTS.find((x) => x.id === id);
```

PDO returns all column values as strings by default, so `APPTS[i].id` was the string `"5"` in the JSON. The inline `onclick` passed the PHP integer echo directly as a JS number literal (`5`). The strict comparison `"5" === 5` is always `false`, so `find()` always returned `undefined` and the modal never opened.

### Solution

Cast `id` to `(int)` in the `array_map` that builds the JSON payload, ensuring JSON encodes it as a number (`5`) not a string (`"5"`):

```php
'id' => (int) $a['id'],
```

Applied the same cast to `medical-history.php`'s `$histJson` for the same reason.

### Files Modified

- `views/user/my-appointments.php`
- `views/user/medical-history.php`

### Status

✅ Completed

---

## [2026-06-02] Fix View Button — JS SyntaxError Caused by Escaped Backticks in PHP Heredoc

### Module

My Appointments (`views/user/my-appointments.php`), Medical History (`views/user/medical-history.php`)

### Type

- Bug Fix

### Issue

Both pages used a non-quoted PHP heredoc (`<<<JS`) to embed JavaScript. Inside those heredocs, template literal backticks were written as `\`` (backslash + backtick). PHP's heredoc does **not** treat `\`` as an escape sequence — it outputs both characters literally. The browser received this malformed JavaScript:

```js
element.innerHTML = \`  // ← SyntaxError: stray backslash outside string
  ...
\`;
```

This caused the entire `<script>` block to fail parsing silently. All functions defined in that block — including `viewAppointment` and `viewHistory` — were never registered. Clicking the View button produced:

```
Uncaught ReferenceError: viewAppointment is not defined
```

### Solution

Replaced all JS template literals in both `$extraScripts` blocks with regular string concatenation using `+`. This eliminates any backtick in the PHP heredoc and avoids the parse failure entirely. No change to the PHP heredoc delimiter was needed.

### Files Modified

- `views/user/my-appointments.php`
- `views/user/medical-history.php`

### Status

✅ Completed

---

## [2026-06-02] Enhance Cancel Appointment — Replace Native confirm() with Modal

### Module

My Appointments (`views/user/my-appointments.php`)

### Type

- Enhancement

### Issue

The Cancel button used the browser's native `confirm()` dialog (`onsubmit="return confirm(...)"`), which is visually inconsistent with the rest of the app's custom modal system.

### Solution

- Changed cancel button from `type="submit"` with `confirm()` to `type="button"` calling `openCancelModal(id, apptNo)`.
- Updated `deleteModal` to display the appointment number (`APT-xxx`) in the confirmation message and an inline warning alert.
- `openCancelModal()` populates the modal and binds the "Yes, Cancel It" button to submit the correct hidden form (`#cancelFormN`) for that row.
- `confirmDelete()` removed in favour of the inline binding in `openCancelModal`.

### Files Modified

- `views/user/my-appointments.php`

### Status

✅ Completed
