<?php $csrfToken = $_SESSION['csrf_token']; ?>
<script>const CSRF = '<?= $csrfToken ?>';</script>
<?php
$pageTitle  = 'Appointments';
$activeMenu = 'appointments';
ob_start();

$apptDates = [];
foreach ($all as $appt) {
    $d = date('Y-m-d', strtotime($appt['appointment_date']));
    $apptDates[$d] = true;
}

$today        = date('Y-m-d');
$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

/* ── Countdown to next appointment ───────────────────────── */
$countdown     = null;
$countdownUrgent = false;
if ($next) {
    $nextDate = date('Y-m-d', strtotime($next['appointment_date']));
    $diffDays = (int)round((strtotime($nextDate) - strtotime($today)) / 86400);
    if ($diffDays === 0)      { $countdown = 'Today'; $countdownUrgent = true; }
    elseif ($diffDays === 1)  { $countdown = 'Tomorrow'; $countdownUrgent = true; }
    else                      { $countdown = 'In ' . $diffDays . ' days'; }
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="<?= BASE_URL ?>/assets/css/appointments.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ═════════════════════════════════════ -->
<div class="appt-page-header">
    <div class="appt-page-header-left">
        <div class="appt-page-eyebrow">
            <i class="ti ti-calendar-event"></i> Schedule
        </div>
        <h1 class="appt-page-title">My <span>Appointments</span></h1>
        <p class="appt-page-sub">View, book, and manage your doctor visits.</p>
    </div>
    <div class="appt-page-header-right">
        <?php if ($countdown): ?>
        <div class="appt-countdown-badge <?= $countdownUrgent ? 'urgent' : '' ?>">
            <i class="ti <?= $countdownUrgent ? 'ti-alarm' : 'ti-clock' ?>"></i>
            Next visit: <strong><?= $countdown ?></strong>
        </div>
        <?php elseif ($counts['upcoming'] === 0): ?>
        <div class="appt-countdown-badge none">
            <i class="ti ti-calendar-off"></i>
            No upcoming visits
        </div>
        <?php endif; ?>
        <button class="appt-page-add-btn" onclick="openModal('apptModal')">
            <i class="ti ti-plus"></i> Add Appointment
        </button>
    </div>
</div>


<!-- ══ ALERTS ══════════════════════════════════════════ -->
<?php if ($success): ?>
<div class="appt-alert appt-alert--success">
    <i class="ti ti-circle-check"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="appt-alert appt-alert--error">
    <i class="ti ti-alert-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>


<!-- ══ STAT CARDS ══════════════════════════════════════ -->
<div class="appt-stats-row">

    <div class="appt-scard appt-scard--upcoming">
        <div class="appt-scard-top">
            <div class="appt-scard-icon"><i class="ti ti-calendar-event"></i></div>
            <?php if ($counts['upcoming'] > 0): ?>
            <div class="appt-scard-trend upcoming"><i class="ti ti-clock"></i> Scheduled</div>
            <?php endif; ?>
        </div>
        <div class="appt-scard-val"><?= $counts['upcoming'] ?></div>
        <div class="appt-scard-label">Upcoming</div>
        <div class="appt-scard-sub">
            <?= $counts['upcoming'] > 0
                ? $counts['upcoming'] . ' visit' . ($counts['upcoming'] > 1 ? 's' : '') . ' ahead'
                : 'None scheduled' ?>
        </div>
    </div>

    <div class="appt-scard appt-scard--completed">
        <div class="appt-scard-top">
            <div class="appt-scard-icon"><i class="ti ti-circle-check"></i></div>
            <?php if ($counts['completed'] > 0): ?>
            <div class="appt-scard-trend completed"><i class="ti ti-check"></i> Done</div>
            <?php endif; ?>
        </div>
        <div class="appt-scard-val"><?= $counts['completed'] ?></div>
        <div class="appt-scard-label">Completed</div>
        <div class="appt-scard-sub">
            <?= $counts['completed'] > 0
                ? $counts['completed'] . ' visit' . ($counts['completed'] > 1 ? 's' : '') . ' attended'
                : 'None yet' ?>
        </div>
    </div>

    <div class="appt-scard appt-scard--cancelled">
        <div class="appt-scard-top">
            <div class="appt-scard-icon"><i class="ti ti-calendar-x"></i></div>
            <?php if ($counts['cancelled'] > 0): ?>
            <div class="appt-scard-trend cancelled"><i class="ti ti-x"></i> Missed</div>
            <?php endif; ?>
        </div>
        <div class="appt-scard-val"><?= $counts['cancelled'] ?></div>
        <div class="appt-scard-label">Cancelled</div>
        <div class="appt-scard-sub">
            <?= $counts['cancelled'] > 0
                ? $counts['cancelled'] . ' cancelled'
                : 'None cancelled' ?>
        </div>
    </div>

</div>


<!-- ══ SPLIT LAYOUT ════════════════════════════════════ -->
<div class="appt-split">

    <!-- LEFT — CALENDAR ─────────────────────────────── -->
    <div class="appt-calendar-panel">
        <div class="cal-nav">
            <button class="cal-nav-btn" onclick="prevMonth()" aria-label="Previous month">
                <i class="ti ti-chevron-left"></i>
            </button>
            <div class="cal-month" id="calMonthLabel"></div>
            <button class="cal-nav-btn" onclick="nextMonth()" aria-label="Next month">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>

        <div class="cal-weekdays">
            <?php foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d): ?>
            <div class="cal-weekday"><?= $d ?></div>
            <?php endforeach; ?>
        </div>

        <div class="cal-grid" id="calGrid"></div>

        <div class="cal-footer">
            <div class="cal-legend">
                <div class="cal-legend-item">
                    <div class="cal-legend-dot cal-legend-dot--today"></div> Today
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot cal-legend-dot--appt"></div> Appointment
                </div>
            </div>
            <div class="cal-selected-wrap">
                <div class="cal-selected-label"><i class="ti ti-calendar-pin"></i> Selected</div>
                <div class="cal-selected-date" id="calSelectedDate"><?= date('F j, Y') ?></div>
            </div>
        </div>
    </div>


    <!-- RIGHT — DETAILS ─────────────────────────────── -->
    <div class="appt-right">

        <!-- Next appointment hero card -->
        <div class="appt-section-label">
            <i class="ti ti-calendar-due"></i> Next Appointment
        </div>

        <?php if ($next): ?>
        <div class="appt-next-card">
            <div class="appt-next-date-box">
                <div class="appt-next-month"><?= date('M', strtotime($next['appointment_date'])) ?></div>
                <div class="appt-next-day"><?= date('d', strtotime($next['appointment_date'])) ?></div>
                <div class="appt-next-weekday"><?= date('D', strtotime($next['appointment_date'])) ?></div>
            </div>
            <div class="appt-next-info">
                <div class="appt-next-eyebrow">Upcoming Visit</div>
                <div class="appt-next-doctor">Dr. <?= htmlspecialchars($next['doctor_name']) ?></div>
                <div class="appt-next-time">
                    <i class="ti ti-clock"></i>
                    <?= date('h:i A', strtotime($next['appointment_date'])) ?>
                    &nbsp;·&nbsp;
                    <?= date('l', strtotime($next['appointment_date'])) ?>
                </div>
                <?php if ($next['notes']): ?>
                <div class="appt-next-note">
                    <i class="ti ti-notes"></i>
                    <?= htmlspecialchars($next['notes']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($countdown): ?>
            <div class="appt-next-countdown <?= $countdownUrgent ? 'urgent' : '' ?>">
                <div class="appt-next-countdown-val"><?= $countdown ?></div>
                <div class="appt-next-countdown-lbl">
                    <i class="ti ti-hourglass"></i>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="appt-no-next">
            <div class="appt-no-next-icon"><i class="ti ti-calendar-off"></i></div>
            <div class="appt-no-next-title">No upcoming appointments</div>
            <div class="appt-no-next-sub">Tap the button below to schedule one.</div>
            <button class="appt-no-next-cta" onclick="openModal('apptModal')">
                <i class="ti ti-plus"></i> Book a Visit
            </button>
        </div>
        <?php endif; ?>


        <!-- All appointments list -->
        <div class="appt-section-label" style="margin-top:8px;">
            <i class="ti ti-list"></i> All Appointments
        </div>

        <div class="appt-list-panel">
            <?php if (empty($all)): ?>
            <div class="appt-empty">
                <div class="appt-empty-icon"><i class="ti ti-calendar-search"></i></div>
                <div class="appt-empty-title">No appointments yet</div>
                <div class="appt-empty-sub">Book your first visit to get started.</div>
            </div>
            <?php else: ?>
            <?php foreach ($all as $appt):
                $apptStatus = strtolower($appt['status']);
                $isPast     = strtotime($appt['appointment_date']) < time();
            ?>
            <div class="appt-row appt-row--<?= $apptStatus ?>">
                <div class="appt-row-date">
                    <div class="appt-row-month"><?= date('M', strtotime($appt['appointment_date'])) ?></div>
                    <div class="appt-row-day"><?= date('d', strtotime($appt['appointment_date'])) ?></div>
                </div>
                <div class="appt-row-info">
                    <div class="appt-row-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></div>
                    <div class="appt-row-meta">
                        <i class="ti ti-clock"></i>
                        <?= date('h:i A', strtotime($appt['appointment_date'])) ?>
                        <?php if ($appt['notes']): ?>
                        &nbsp;·&nbsp;
                        <i class="ti ti-notes"></i>
                        <?= htmlspecialchars(mb_strimwidth($appt['notes'], 0, 32, '…')) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="appt-row-actions">
                    <span class="appt-status-chip appt-status-chip--<?= $apptStatus ?>">
                        <i class="ti <?= $apptStatus === 'upcoming' ? 'ti-calendar-event' : ($apptStatus === 'completed' ? 'ti-circle-check' : 'ti-calendar-x') ?>"></i>
                        <?= $appt['status'] ?>
                    </span>
                    <?php if ($appt['status'] === 'Upcoming'): ?>
                    <form method="POST" action="/patient/appointments" style="margin:0;">
                        <input type="hidden" name="action"  value="status">
                        <input type="hidden" name="appt_id" value="<?= $appt['id'] ?>">
                        <input type="hidden" name="status"  value="Completed">
                        <button type="submit" class="appt-action-btn appt-action-btn--done" title="Mark as completed">
                            <i class="ti ti-check"></i>
                        </button>
                    </form>
                    <form method="POST" action="/patient/appointments" style="margin:0;">
                        <input type="hidden" name="action"  value="status">
                        <input type="hidden" name="appt_id" value="<?= $appt['id'] ?>">
                        <input type="hidden" name="status"  value="Cancelled">
                        <button type="submit" class="appt-action-btn appt-action-btn--cancel" title="Cancel appointment">
                            <i class="ti ti-x"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/patient/appointments?delete=<?= $appt['id'] ?>&_token=<?= $_SESSION['csrf_token'] ?>"
                       onclick="return confirm('Delete this appointment?')"
                       class="appt-action-btn appt-action-btn--delete"
                       title="Delete">
                        <i class="ti ti-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>


        <!-- Before your visit tips -->
        <div class="appt-tips-panel">
            <div class="appt-tips-header">
                <div class="appt-section-label" style="margin-bottom:0;">
                    <i class="ti ti-stethoscope"></i> Before Your Visit
                </div>
            </div>
            <div class="appt-tips-grid">
                <?php foreach ([
                    ['ti-droplet-half-2', '#c04a20', '#FDE8DC', 'Bring your blood sugar trend report'],
                    ['ti-pill',           '#d97706', '#fef3c7', 'List all current medications & dosages'],
                    ['ti-salad',          '#0f7a45', '#d4f7e8', 'Note any diet changes or concerns'],
                    ['ti-message-question','#7c3aed', '#ede9fe', 'Prepare questions for your doctor'],
                ] as [$icon, $color, $bg, $text]): ?>
                <div class="appt-tip-card" style="--tip-color:<?= $color ?>;--tip-bg:<?= $bg ?>;">
                    <div class="appt-tip-icon"><i class="ti <?= $icon ?>"></i></div>
                    <div class="appt-tip-text"><?= $text ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>


<!-- ══ ADD APPOINTMENT MODAL ══════════════════════════ -->
<div id="apptModal" class="appt-modal-overlay" aria-modal="true" role="dialog">
    <div class="appt-modal">
        <div class="appt-modal-head">
            <div class="appt-modal-head-left">
                <div class="appt-modal-icon"><i class="ti ti-calendar-plus"></i></div>
                <div>
                    <div class="appt-modal-title">Book Appointment</div>
                    <div class="appt-modal-sub">Schedule your next doctor visit.</div>
                </div>
            </div>
            <button class="appt-modal-close" onclick="closeModal('apptModal')" aria-label="Close">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="appt-modal-body">
        <form method="POST" action="/patient/appointments" id="apptForm">
                <input type="hidden" name="action" value="add">

                <div class="appt-form-group">
                    <div class="appt-form-label">
                        <i class="ti ti-user-heart"></i> Doctor's Name
                    </div>
                    <input type="text" name="doctor_name" id="doctorNameInput"
                           class="appt-form-input" placeholder="e.g. Dr. Santos" required>
                </div>

                <div class="appt-form-group">
                    <div class="appt-form-label">
                        <i class="ti ti-calendar-time"></i> Date & Time
                    </div>
                    <input type="datetime-local" name="appointment_date"
                           id="apptDateInput" class="appt-form-input" required
                           min="<?= date('Y-m-d\TH:i') ?>">
                </div>

                <div class="appt-form-group">
                    <div class="appt-form-label">
                        <i class="ti ti-notes"></i> Notes
                        <span class="appt-form-optional">optional</span>
                    </div>
                    <textarea name="notes" class="appt-form-input appt-form-textarea" rows="2"
                              placeholder="e.g. Bring blood sugar log, fasting required…"></textarea>
                </div>

                <!-- Checklist reminder inside modal -->
                <div class="appt-modal-reminder">
                    <div class="appt-modal-reminder-title">
                        <i class="ti ti-checklist"></i> Remember to bring
                    </div>
                    <div class="appt-modal-reminder-items">
                        <span><i class="ti ti-droplet-half-2"></i> Glucose log</span>
                        <span><i class="ti ti-pill"></i> Medication list</span>
                        <span><i class="ti ti-id-badge"></i> Health card</span>
                    </div>
                </div>

            </form>
        </div>

        <div class="appt-modal-foot">
            <button type="button" class="appt-cancel-btn" onclick="closeModal('apptModal')">Cancel</button>
            <button type="submit" form="apptForm" class="appt-save-btn">
                <i class="ti ti-calendar-plus"></i> Save Appointment
            </button>
        </div>
    </div>
</div>


<!-- ══ FAB ═════════════════════════════════════════════ -->
<button class="patient-fab" onclick="openModal('apptModal')" aria-label="Add appointment">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Add Appointment</span>
</button>


<script>
const apptDates  = <?= json_encode(array_keys($apptDates)) ?>;
const todayStr   = '<?= $today ?>';
let   viewYear   = <?= $currentYear ?>;
let   viewMonth  = <?= $currentMonth ?>;
let   selectedDate = todayStr;

const monthNames = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

function renderCalendar() {
    document.getElementById('calMonthLabel').textContent =
        monthNames[viewMonth - 1] + ' ' + viewYear;

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    const firstDay    = new Date(viewYear, viewMonth - 1, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();

    for (let i = 0; i < firstDay; i++) {
        const e = document.createElement('div');
        e.className = 'cal-day empty';
        grid.appendChild(e);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = viewYear + '-'
            + String(viewMonth).padStart(2,'0') + '-'
            + String(d).padStart(2,'0');

        const cell = document.createElement('div');
        cell.className = 'cal-day';
        cell.textContent = d;

        if (dateStr === todayStr)        cell.classList.add('today');
        if (dateStr === selectedDate)    cell.classList.add('selected');
        if (apptDates.includes(dateStr)) cell.classList.add('has-appt');
        if (dateStr < todayStr)          cell.classList.add('past');

        cell.addEventListener('click', () => selectDay(dateStr));
        grid.appendChild(cell);
    }
}

function selectDay(dateStr) {
    selectedDate = dateStr;
    renderCalendar();
    const d = new Date(dateStr + 'T00:00:00');
    document.getElementById('calSelectedDate').textContent =
        d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
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

function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

document.querySelectorAll('.appt-modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) { this.classList.remove('open'); document.body.style.overflow = ''; }
    });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal('apptModal'); });

renderCalendar();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>