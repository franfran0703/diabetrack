<?php
$pageTitle  = 'Appointments';
$activeMenu = 'appointments';
ob_start();

// Build appointment dates map for calendar dots
$apptDates = [];
foreach ($all as $appt) {
    $d = date('Y-m-d', strtotime($appt['appointment_date']));
    $apptDates[$d] = true;
}

$today        = date('Y-m-d');
$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');
?>

<link href="/diabetrack/public/assets/css/appointments.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="appt-header">
    <div>
        <div class="appt-eyebrow">Schedule</div>
        <h1 class="appt-title">📅 My <span>Appointments</span></h1>
    </div>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
<div class="appt-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="appt-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- STAT PILLS -->
<div class="appt-pills">
    <div class="appt-pill upcoming">
        <span class="appt-pill-val"><?= $counts['upcoming'] ?></span>
        <span class="appt-pill-label">📅 Upcoming</span>
    </div>
    <div class="appt-pill completed">
        <span class="appt-pill-val"><?= $counts['completed'] ?></span>
        <span class="appt-pill-label">✅ Completed</span>
    </div>
    <div class="appt-pill cancelled">
        <span class="appt-pill-val"><?= $counts['cancelled'] ?></span>
        <span class="appt-pill-label">❌ Cancelled</span>
    </div>
</div>

<!-- SPLIT LAYOUT -->
<div class="appt-split">

    <!-- LEFT — INTERACTIVE CALENDAR -->
    <div class="appt-calendar-panel">
        <div class="cal-nav">
            <button class="cal-nav-btn" onclick="prevMonth()">‹</button>
            <div class="cal-month" id="calMonthLabel"></div>
            <button class="cal-nav-btn" onclick="nextMonth()">›</button>
        </div>

        <div class="cal-weekdays">
            <?php foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d): ?>
            <div class="cal-weekday"><?= $d ?></div>
            <?php endforeach; ?>
        </div>

        <div class="cal-grid" id="calGrid"></div>

        <div class="cal-selected-label">Selected</div>
        <div class="cal-selected-date" id="calSelectedDate">
            <?= date('F j, Y') ?>
        </div>
    </div>

    <!-- RIGHT — APPOINTMENT DETAILS -->
    <div class="appt-right">

        <!-- Next appointment hero -->
        <div class="appt-section-label">Next Appointment</div>
        <?php if ($next): ?>
        <div class="appt-next-card">
            <div class="appt-next-date-box">
                <div class="appt-next-month"><?= date('M', strtotime($next['appointment_date'])) ?></div>
                <div class="appt-next-day"><?= date('d', strtotime($next['appointment_date'])) ?></div>
                <div class="appt-next-weekday"><?= date('D', strtotime($next['appointment_date'])) ?></div>
            </div>
            <div class="appt-next-info">
                <div class="appt-next-label">Upcoming Visit</div>
                <div class="appt-next-doctor">Dr. <?= htmlspecialchars($next['doctor_name']) ?></div>
                <div class="appt-next-time">
                    🕐 <?= date('h:i A', strtotime($next['appointment_date'])) ?>
                    &nbsp;·&nbsp;
                    <?= date('l', strtotime($next['appointment_date'])) ?>
                </div>
                <?php if ($next['notes']): ?>
                <div class="appt-next-badge">📝 <?= htmlspecialchars($next['notes']) ?></div>
                <?php else: ?>
                <div class="appt-next-badge">📅 Scheduled</div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="appt-no-next">
            <div class="appt-no-next-icon">📅</div>
            <div class="appt-no-next-title">No upcoming appointments</div>
            <div class="appt-no-next-sub">Use the + button to schedule one.</div>
        </div>
        <?php endif; ?>

        <!-- Upcoming list -->
        <div class="appt-section-label" style="margin-top:8px;">All Appointments</div>
        <div class="appt-list-panel">
            <?php if (empty($all)): ?>
            <div class="appt-empty">
                <div class="appt-empty-icon">📜</div>
                <div class="appt-empty-text">No appointments yet.</div>
            </div>
            <?php else: ?>
            <?php foreach ($all as $appt): ?>
            <div class="appt-row">
                <div class="appt-row-date">
                    <div class="appt-row-month"><?= date('M', strtotime($appt['appointment_date'])) ?></div>
                    <div class="appt-row-day"><?= date('d', strtotime($appt['appointment_date'])) ?></div>
                </div>
                <div class="appt-row-info">
                    <div class="appt-row-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></div>
                    <div class="appt-row-meta">
                        🕐 <?= date('h:i A', strtotime($appt['appointment_date'])) ?>
                        <?php if ($appt['notes']): ?>
                        &nbsp;·&nbsp; <?= htmlspecialchars($appt['notes']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="appt-row-actions">
                    <span class="appt-status <?= strtolower($appt['status']) ?>">
                        <?= $appt['status'] === 'Upcoming' ? '📅' :
                           ($appt['status'] === 'Completed' ? '✅' : '❌') ?>
                        <?= $appt['status'] ?>
                    </span>
                    <?php if ($appt['status'] === 'Upcoming'): ?>
                    <form method="POST" action="/diabetrack/public/patient/appointments" style="margin:0;">
                        <input type="hidden" name="action"  value="status">
                        <input type="hidden" name="appt_id" value="<?= $appt['id'] ?>">
                        <input type="hidden" name="status"  value="Completed">
                        <button type="submit" class="appt-btn appt-btn-done">✅</button>
                    </form>
                    <form method="POST" action="/diabetrack/public/patient/appointments" style="margin:0;">
                        <input type="hidden" name="action"  value="status">
                        <input type="hidden" name="appt_id" value="<?= $appt['id'] ?>">
                        <input type="hidden" name="status"  value="Cancelled">
                        <button type="submit" class="appt-btn appt-btn-cancel">✕</button>
                    </form>
                    <?php endif; ?>
                    <a href="/diabetrack/public/patient/appointments?delete=<?= $appt['id'] ?>"
                       onclick="return confirm('Delete this appointment?')"
                       class="appt-del-btn">🗑</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Tips -->
        <div class="appt-tips-panel">
            <div class="appt-tips-title">Before Your Visit</div>
            <?php
            $tips = [
                ['🩸', 'Bring your blood sugar trend report'],
                ['💊', 'List all current medications & dosages'],
                ['🥗', 'Note any diet changes or concerns'],
                ['❓', 'Prepare questions for your doctor'],
            ];
            foreach ($tips as [$icon, $text]): ?>
            <div class="appt-tip-item">
                <div class="appt-tip-icon"><?= $icon ?></div>
                <div class="appt-tip-text"><?= $text ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ADD APPOINTMENT MODAL -->
<div id="apptModal" class="appt-modal-overlay">
    <div class="appt-modal">
        <button class="appt-modal-close" onclick="closeModal('apptModal')">✕</button>
        <div class="appt-modal-title">📅 Add Appointment</div>
        <div class="appt-modal-sub">Schedule your next doctor visit.</div>
        <form method="POST" action="/diabetrack/public/patient/appointments">
            <input type="hidden" name="action" value="add">
            <div class="appt-form-group">
                <label class="appt-form-label">Doctor's Name</label>
                <input type="text" name="doctor_name" id="doctorNameInput"
                       class="appt-form-input" placeholder="e.g. Dr. Santos" required>
            </div>
            <div class="appt-form-group">
                <label class="appt-form-label">Date & Time</label>
                <input type="datetime-local" name="appointment_date"
                       id="apptDateInput" class="appt-form-input" required
                       min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="appt-form-group">
                <label class="appt-form-label">Notes <span style="font-size:0.65rem;color:rgba(184,146,126,0.5);text-transform:lowercase;font-weight:600;">optional</span></label>
                <textarea name="notes" class="appt-form-textarea" rows="2"
                          placeholder="e.g. Bring blood sugar log, fasting required"></textarea>
            </div>
            <button type="submit" class="appt-save-btn">📅 Save Appointment</button>
        </form>
    </div>
</div>

<!-- FAB -->
<button class="patient-fab" onclick="openModal('apptModal')">
    <span class="patient-fab-icon">📅</span>
    <span class="patient-fab-label">Add Appointment</span>
</button>

<script>
// ── CALENDAR ────────────────────────────────────────────
const apptDates  = <?= json_encode(array_keys($apptDates)) ?>;
const todayStr   = '<?= $today ?>';
let   viewYear   = <?= $currentYear ?>;
let   viewMonth  = <?= $currentMonth ?>; // 1-based
let   selectedDate = todayStr;

const monthNames = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

function renderCalendar() {
    document.getElementById('calMonthLabel').textContent =
        monthNames[viewMonth - 1] + ' ' + viewYear;

    const grid     = document.getElementById('calGrid');
    grid.innerHTML = '';

    const firstDay  = new Date(viewYear, viewMonth - 1, 1).getDay(); // 0=Sun
    const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();

    // Empty slots
    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'cal-day empty';
        grid.appendChild(empty);
    }

    // Day cells
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = viewYear + '-'
            + String(viewMonth).padStart(2,'0') + '-'
            + String(d).padStart(2,'0');

        const cell = document.createElement('div');
        cell.className = 'cal-day';
        cell.textContent = d;

        if (dateStr === todayStr)      cell.classList.add('today');
        if (dateStr === selectedDate)  cell.classList.add('selected');
        if (apptDates.includes(dateStr)) cell.classList.add('has-appt');
        if (dateStr < todayStr)        cell.classList.add('past');

        cell.addEventListener('click', () => selectDay(dateStr, d));
        grid.appendChild(cell);
    }
}

function selectDay(dateStr, day) {
    selectedDate = dateStr;
    renderCalendar();

    // Update label
    const d = new Date(dateStr + 'T00:00:00');
    document.getElementById('calSelectedDate').textContent =
        d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

    // Pre-fill the modal date
    const input = document.getElementById('apptDateInput');
    if (input) input.value = dateStr + 'T09:00';
}

function prevMonth() {
    viewMonth--;
    if (viewMonth < 1) { viewMonth = 12; viewYear--; }
    renderCalendar();
}
function nextMonth() {
    viewMonth++;
    if (viewMonth > 12) { viewMonth = 1; viewYear++; }
    renderCalendar();
}

// ── MODAL ────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.appt-modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Input focus styles
document.querySelectorAll('.appt-form-input, .appt-form-textarea').forEach(f => {
    f.addEventListener('focus', () => {
        f.style.borderColor = '#F97447';
        f.style.boxShadow   = '0 0 0 3px rgba(249,116,71,0.12)';
    });
    f.addEventListener('blur', () => {
        f.style.borderColor = 'rgba(249,116,71,0.22)';
        f.style.boxShadow   = 'none';
    });
});

// Init
renderCalendar();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>