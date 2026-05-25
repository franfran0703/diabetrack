<?php
$pageTitle  = 'Doctor Reports';
$activeMenu = 'reports';
ob_start();

$pid = $_SESSION['user_id'] ?? null;

/* ── Resolve data from multiple possible variable names ── */
$allLogs  = $bsLogs ?? $logs ?? [];
$weekMins = $weekTotals['total_minutes'] ?? 0;

/* ── Composite health score ──────────────────────────────
   A simple heuristic signal for the patient:
   Blood sugar normal → +2 | meds logged → +2 | meals logged → +1 | activity goal → +2
   Max 7 → map to Good / Fair / Needs Attention                              */
$healthScore = 0;
if (isset($latestBS) && strtolower($latestBS['status']) === 'normal') $healthScore += 2;
if (isset($todayStats['total']) && $todayStats['total'] > 0)         $healthScore += 2;
if (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) $healthScore += 1;
if ($weekMins >= 150)      $healthScore += 2;
elseif ($weekMins >= 60)   $healthScore += 1;

$scoreLabel = $healthScore >= 6 ? 'Great'   : ($healthScore >= 3 ? 'Fair' : 'Needs Attention');
$scoreColor = $healthScore >= 6 ? '#0f7a45' : ($healthScore >= 3 ? '#d97706' : '#b91c1c');
$scoreBg    = $healthScore >= 6 ? '#d4f7e8' : ($healthScore >= 3 ? '#fef3c7' : '#fde8e8');
$scoreIcon  = $healthScore >= 6 ? 'ti-rosette' : ($healthScore >= 3 ? 'ti-trending-up' : 'ti-alert-triangle');
$scoreMax   = 7;
$scorePct   = round($healthScore / $scoreMax * 100);

/* ── Active range tab (default 7 days) ── */
$activeDays = 7;

/* ── Patient display name initials ── */
$patientName = $_SESSION['user_name'] ?? 'Patient';
$initials    = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', trim($patientName)), 0, 2))));
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="/diabetrack/public/assets/css/reports.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="rep-page-header">
    <div class="rep-page-header-left">
        <div class="rep-page-eyebrow">
            <i class="ti ti-clipboard-text"></i> Medical
        </div>
        <h1 class="rep-page-title">Doctor <span>Reports</span></h1>
        <p class="rep-page-sub">Health summaries and trends — ready to share with your doctor.</p>
    </div>
    <div class="rep-page-header-right">
        <div class="rep-generated-badge">
            <i class="ti ti-clock"></i>
            Generated <?= date('M j, Y · g:i A') ?>
        </div>
        <button class="rep-print-btn" onclick="window.print()">
            <i class="ti ti-printer"></i> Print Report
        </button>
    </div>
</div>


<!-- ══ CONSULTATION BAND — the page's unique signature ══ -->
<div class="rep-consult-band">
    <div class="rep-consult-band-left">
        <div class="rep-consult-band-icon"><i class="ti ti-stethoscope"></i></div>
        <div>
            <div class="rep-consult-band-title">Prepared for Medical Consultation</div>
            <div class="rep-consult-band-sub">Share this summary with your physician at your next visit</div>
        </div>
    </div>
    <div class="rep-consult-band-right">
        <div class="rep-range-tabs" id="rangeTabs">
            <button class="rep-range-tab active" data-days="7">7 Days</button>
            <button class="rep-range-tab" data-days="14">14 Days</button>
            <button class="rep-range-tab" data-days="30">30 Days</button>
            <button class="rep-range-tab" data-days="90">3 Months</button>
        </div>
    </div>
</div>


<!-- ══ SECTION 01 — PATIENT IDENTITY ═════════════════════ -->
<div class="rep-section-head">
    <div class="rep-section-num">01</div>
    <div class="rep-section-title">Patient Identity</div>
    <div class="rep-section-line"></div>
</div>

<div class="rep-identity-row">

    <!-- Patient card -->
    <div class="rep-identity-card">
        <div class="rep-identity-avatar"><?= $initials ?></div>
        <div class="rep-identity-info">
            <div class="rep-identity-name"><?= htmlspecialchars($patientName) ?></div>
            <div class="rep-identity-role">
                <i class="ti ti-heart-rate-monitor"></i>
                DiabeTrack Patient Record
            </div>
            <div class="rep-identity-badges">
                <span class="rep-id-badge"><i class="ti ti-calendar"></i> <?= date('Y') ?></span>
                <span class="rep-id-badge"><i class="ti ti-calendar-pin"></i> <?= date('F j') ?></span>
                <span class="rep-id-badge rep-id-badge--type">
                    <i class="ti ti-drop"></i> Diabetes Management
                </span>
            </div>
        </div>
    </div>

    <!-- Health overview strip -->
    <div class="rep-health-overview">
        <div class="rep-health-overview-top">
            <div>
                <div class="rep-health-overview-label">Overall Health Status</div>
                <div class="rep-health-overview-score" style="color:<?= $scoreColor ?>">
                    <?= $scoreLabel ?>
                </div>
            </div>
            <div class="rep-health-score-badge" style="background:<?= $scoreBg ?>;color:<?= $scoreColor ?>;">
                <i class="ti <?= $scoreIcon ?>"></i>
                <?= $healthScore ?>/<?= $scoreMax ?>
            </div>
        </div>
        <div class="rep-health-bar-track">
            <div class="rep-health-bar-fill" style="width:0%;background:<?= $scoreColor ?>;" data-target="<?= $scorePct ?>%"></div>
        </div>
        <div class="rep-health-pillars">
            <div class="rep-pillar <?= (isset($latestBS) && strtolower($latestBS['status']) === 'normal') ? 'ok' : 'off' ?>">
                <i class="ti ti-droplet-half-2"></i> Glucose
            </div>
            <div class="rep-pillar <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? 'ok' : 'off' ?>">
                <i class="ti ti-pill"></i> Meds
            </div>
            <div class="rep-pillar <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? 'ok' : 'off' ?>">
                <i class="ti ti-salad"></i> Meals
            </div>
            <div class="rep-pillar <?= $weekMins >= 150 ? 'ok' : ($weekMins > 0 ? 'warn' : 'off') ?>">
                <i class="ti ti-run"></i> Activity
            </div>
        </div>
    </div>

</div>


<!-- ══ SECTION 02 — HEALTH SUMMARY ══════════════════════ -->
<div class="rep-section-head">
    <div class="rep-section-num">02</div>
    <div class="rep-section-title">Health Summary</div>
    <div class="rep-section-line"></div>
</div>

<div class="rep-summary-grid">

    <!-- Blood Sugar -->
    <div class="rep-sum-card rep-sum-card--blood">
        <div class="rep-sum-card-stripe"></div>
        <div class="rep-sum-header">
            <div class="rep-sum-icon rep-sum-icon--blood">
                <i class="ti ti-droplet-half-2"></i>
            </div>
            <div class="rep-sum-header-text">
                <div class="rep-sum-title">Blood Glucose</div>
                <div class="rep-sum-period" id="bsPeriodLabel">Last 7 Days</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= isset($latestBS) ? $latestBS['reading'] . '<small>mg/dL</small>' : '—' ?>
                </div>
                <div class="rep-sum-meta">Latest</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= $bsAvg ?? '—' ?></div>
                <div class="rep-sum-meta">7-Day Avg</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= $bsCount ?? count($allLogs) ?></div>
                <div class="rep-sum-meta">Total Logs</div>
            </div>
        </div>
        <div class="rep-sum-status-row <?= isset($latestBS) ? (strtolower($latestBS['status']) === 'normal' ? 'ok' : 'warn') : 'none' ?>">
            <i class="ti <?= isset($latestBS) ? (strtolower($latestBS['status']) === 'normal' ? 'ti-circle-check' : 'ti-alert-triangle') : 'ti-minus' ?>"></i>
            <?= isset($latestBS) ? ($latestBS['status'] === 'Normal' ? 'Within target range' : $latestBS['status'] . ' — consult doctor') : 'No recent data' ?>
        </div>
    </div>

    <!-- Medications -->
    <div class="rep-sum-card rep-sum-card--med">
        <div class="rep-sum-card-stripe"></div>
        <div class="rep-sum-header">
            <div class="rep-sum-icon rep-sum-icon--med">
                <i class="ti ti-pill"></i>
            </div>
            <div class="rep-sum-header-text">
                <div class="rep-sum-title">Medications</div>
                <div class="rep-sum-period">Adherence Overview</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $medCount ?? (isset($medications) ? count($medications) : '—') ?>
                </div>
                <div class="rep-sum-meta">Active</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $medsTaken ?? ($todayStats['total'] ?? '—') ?>
                </div>
                <div class="rep-sum-meta">Today</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= isset($adherencePct) ? $adherencePct . '<small>%</small>' : '—' ?>
                </div>
                <div class="rep-sum-meta">Adherence</div>
            </div>
        </div>
        <div class="rep-sum-status-row <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? 'ok' : 'none' ?>">
            <i class="ti <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? 'ti-circle-check' : 'ti-minus' ?>"></i>
            <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? 'Doses logged today' : 'Log medications daily' ?>
        </div>
    </div>

    <!-- Meals -->
    <div class="rep-sum-card rep-sum-card--meals">
        <div class="rep-sum-card-stripe"></div>
        <div class="rep-sum-header">
            <div class="rep-sum-icon rep-sum-icon--meals">
                <i class="ti ti-salad"></i>
            </div>
            <div class="rep-sum-header-text">
                <div class="rep-sum-title">Meals & Nutrition</div>
                <div class="rep-sum-period">Today's Intake</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= isset($todayTotals['total_carbs']) ? $todayTotals['total_carbs'] . '<small>g</small>' : '—' ?>
                </div>
                <div class="rep-sum-meta">Carbs</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $todayTotals['total_calories'] ?? '—' ?>
                </div>
                <div class="rep-sum-meta">Calories</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $todayTotals['total_meals'] ?? '—' ?>
                </div>
                <div class="rep-sum-meta">Meals</div>
            </div>
        </div>
        <div class="rep-sum-status-row <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? 'ok' : 'none' ?>">
            <i class="ti <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? 'ti-circle-check' : 'ti-minus' ?>"></i>
            <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? 'Meals recorded today' : 'No meals logged today' ?>
        </div>
    </div>

    <!-- Activity -->
    <div class="rep-sum-card rep-sum-card--activity">
        <div class="rep-sum-card-stripe"></div>
        <div class="rep-sum-header">
            <div class="rep-sum-icon rep-sum-icon--activity">
                <i class="ti ti-run"></i>
            </div>
            <div class="rep-sum-header-text">
                <div class="rep-sum-title">Physical Activity</div>
                <div class="rep-sum-period">This Week</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $weekMins > 0 ? $weekMins . '<small>min</small>' : '—' ?>
                </div>
                <div class="rep-sum-meta">Week Total</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $todayActivityMins ?? ($activityTodayMins ?? '—') ?>
                </div>
                <div class="rep-sum-meta">Today</div>
            </div>
            <div class="rep-sum-divider"></div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val">
                    <?= $weekTotals['total_activities'] ?? '—' ?>
                </div>
                <div class="rep-sum-meta">Sessions</div>
            </div>
        </div>
        <div class="rep-sum-status-row <?= $weekMins >= 150 ? 'ok' : ($weekMins > 0 ? 'warn' : 'none') ?>">
            <i class="ti <?= $weekMins >= 150 ? 'ti-trophy' : ($weekMins > 0 ? 'ti-alert-triangle' : 'ti-minus') ?>"></i>
            <?= $weekMins >= 150 ? 'Weekly goal met (150 min)' : ($weekMins > 0 ? $weekMins . ' / 150 min goal' : 'No activity this week') ?>
        </div>
    </div>

</div>


<!-- ══ SECTION 03 — BLOOD GLUCOSE LOG ════════════════════ -->
<div class="rep-section-head" style="margin-top:36px;">
    <div class="rep-section-num">03</div>
    <div class="rep-section-title">Blood Glucose Log</div>
    <div class="rep-section-line"></div>
</div>

<div class="rep-table-card">
    <?php if (!empty($allLogs)): ?>
    <div class="rep-table-wrap">
        <table class="rep-table">
            <thead>
                <tr>
                    <th><i class="ti ti-calendar-event"></i> Date & Time</th>
                    <th><i class="ti ti-droplet-half-2"></i> Reading</th>
                    <th><i class="ti ti-tag"></i> Type</th>
                    <th><i class="ti ti-circle-check"></i> Status</th>
                    <th><i class="ti ti-notes"></i> Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($allLogs, 0, 15) as $log):
                    $status = strtolower($log['status']);
                ?>
                <tr>
                    <td class="rep-td-date">
                        <div class="rep-td-date-main"><?= date('M j, Y', strtotime($log['logged_at'])) ?></div>
                        <div class="rep-td-date-sub"><?= date('g:i A', strtotime($log['logged_at'])) ?></div>
                    </td>
                    <td class="rep-td-val"><?= $log['reading'] ?><small>mg/dL</small></td>
                    <td class="rep-td-type"><?= htmlspecialchars($log['reading_type'] ?? '—') ?></td>
                    <td>
                        <span class="rep-status-chip rep-status-chip--<?= $status ?>">
                            <i class="ti <?= $status === 'normal' ? 'ti-circle-check' : ($status === 'high' ? 'ti-trending-up' : 'ti-trending-down') ?>"></i>
                            <?= $log['status'] ?>
                        </span>
                    </td>
                    <td class="rep-td-notes"><?= htmlspecialchars($log['notes'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($allLogs) > 15): ?>
    <div class="rep-table-more">
        <i class="ti ti-dots"></i> <?= count($allLogs) - 15 ?> more entries — print for full log
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="rep-empty">
        <div class="rep-empty-icon"><i class="ti ti-droplet-off"></i></div>
        <div class="rep-empty-title">No readings yet</div>
        <div class="rep-empty-sub">Start logging blood sugar readings to generate your report.</div>
        <a href="/diabetrack/public/patient/bloodsugar" class="rep-empty-link">
            Go to Blood Sugar Logger <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>


<!-- ══ SECTION 04 — APPOINTMENT HISTORY ══════════════════ -->
<div class="rep-section-head" style="margin-top:36px;">
    <div class="rep-section-num">04</div>
    <div class="rep-section-title">Appointment History</div>
    <div class="rep-section-line"></div>
</div>

<div class="rep-table-card">
    <?php if (!empty($appts)): ?>
    <div class="rep-table-wrap">
        <table class="rep-table">
            <thead>
                <tr>
                    <th><i class="ti ti-calendar-event"></i> Date & Time</th>
                    <th><i class="ti ti-user-heart"></i> Doctor</th>
                    <th><i class="ti ti-circle-check"></i> Status</th>
                    <th><i class="ti ti-notes"></i> Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($appts, 0, 10) as $appt):
                    $aStatus = strtolower($appt['status']);
                ?>
                <tr>
                    <td class="rep-td-date">
                        <div class="rep-td-date-main"><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></div>
                        <div class="rep-td-date-sub"><?= date('g:i A', strtotime($appt['appointment_date'])) ?></div>
                    </td>
                    <td class="rep-td-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td>
                        <span class="rep-status-chip rep-status-chip--<?= $aStatus ?>">
                            <i class="ti <?= $aStatus === 'upcoming' ? 'ti-calendar-event' : ($aStatus === 'completed' ? 'ti-circle-check' : 'ti-calendar-x') ?>"></i>
                            <?= $appt['status'] ?>
                        </span>
                    </td>
                    <td class="rep-td-notes"><?= htmlspecialchars($appt['notes'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="rep-empty">
        <div class="rep-empty-icon"><i class="ti ti-calendar-search"></i></div>
        <div class="rep-empty-title">No appointments</div>
        <div class="rep-empty-sub">Schedule doctor visits to track them here.</div>
        <a href="/diabetrack/public/patient/appointments" class="rep-empty-link">
            Go to Appointments <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>


<!-- ══ PRINT FOOTER ═══════════════════════════════════════ -->
<div class="rep-print-footer">
    <div class="rep-print-footer-left">
        <i class="ti ti-printer"></i>
        <div>
            <strong>Bring this report to your next visit</strong> — use the Print button above or press
            <kbd>Ctrl</kbd><span>+</span><kbd>P</kbd>.
        </div>
    </div>
    <div class="rep-print-footer-badge">
        <i class="ti ti-shield-check"></i> DiabeTrack Health Record
    </div>
</div>

<!-- ── Print-only watermark ── -->
<div class="rep-print-watermark">DIABETRACK — CONFIDENTIAL HEALTH RECORD</div>


<script>
/* ── Range tab switching ── */
document.querySelectorAll('.rep-range-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.rep-range-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const labels = { '7':'Last 7 Days', '14':'Last 14 Days', '30':'Last 30 Days', '90':'Last 3 Months' };
        const el = document.getElementById('bsPeriodLabel');
        if (el) el.textContent = labels[this.dataset.days] || 'Last 7 Days';
    });
});

/* ── Health bar animate on load ── */
window.addEventListener('load', () => {
    const fill = document.querySelector('.rep-health-bar-fill');
    if (fill) {
        setTimeout(() => { fill.style.width = fill.dataset.target; }, 200);
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>