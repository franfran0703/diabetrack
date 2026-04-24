<?php
$pageTitle  = 'Doctor Reports';
$activeMenu = 'reports';
ob_start();

// Build summary data from available variables
$pid = $_SESSION['user_id'] ?? null;
?>

<link href="/diabetrack/public/assets/css/reports.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="rep-header">
    <div>
        <div class="rep-eyebrow">📋 Medical</div>
        <h1 class="rep-title">Doctor <span>Reports</span></h1>
        <p class="rep-sub">Your health summaries, trends, and printable reports for doctor visits.</p>
    </div>
    <div class="rep-header-actions">
        <button class="rep-print-btn" onclick="window.print()">🖨 Print Report</button>
    </div>
</div>

<!-- DATE RANGE SELECTOR -->
<div class="rep-range-bar">
    <span class="rep-range-label">Report Period:</span>
    <div class="rep-range-tabs" id="rangeTabs">
        <button class="rep-range-tab active" data-days="7">Last 7 Days</button>
        <button class="rep-range-tab" data-days="14">14 Days</button>
        <button class="rep-range-tab" data-days="30">30 Days</button>
        <button class="rep-range-tab" data-days="90">3 Months</button>
    </div>
    <div class="rep-print-date">Generated: <?= date('F j, Y, g:i A') ?></div>
</div>

<!-- PATIENT INFO CARD -->
<div class="rep-patient-card">
    <div class="rep-patient-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'P', 0, 2)) ?></div>
    <div class="rep-patient-info">
        <div class="rep-patient-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Patient') ?></div>
        <div class="rep-patient-meta">Patient · DiabeTrack Health Record</div>
    </div>
    <div class="rep-patient-stats">
        <div class="rep-patient-stat">
            <div class="rep-patient-stat-val"><?= date('Y') ?></div>
            <div class="rep-patient-stat-label">Year</div>
        </div>
        <div class="rep-patient-stat-divider"></div>
        <div class="rep-patient-stat">
            <div class="rep-patient-stat-val"><?= date('M j') ?></div>
            <div class="rep-patient-stat-label">As of Today</div>
        </div>
    </div>
</div>

<!-- SUMMARY GRID -->
<div class="rep-section-label">Health Summary</div>
<div class="rep-summary-grid" id="summaryGrid">

    <!-- Blood Sugar Summary -->
    <div class="rep-sum-card card-blood">
        <div class="rep-sum-header">
            <div class="rep-sum-icon">🩸</div>
            <div>
                <div class="rep-sum-title">Blood Glucose</div>
                <div class="rep-sum-period" id="bsPeriodLabel">Last 7 Days</div>
            </div>
        </div>
        <?php
        $bloodSugarModel = null;
        try {
            require_once __DIR__ . '/../../models/BloodSugarModel.php';
            $db = require_once __DIR__ . '/../../../config/database.php';
        } catch (Exception $e) {}
        ?>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val" id="bsLatestVal">
                    <?= isset($latestBS) ? $latestBS['reading'] . ' <small>mg/dL</small>' : '—' ?>
                </div>
                <div class="rep-sum-meta">Latest Reading</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($bsAvg) ? $bsAvg : '—' ?></div>
                <div class="rep-sum-meta">7-Day Avg</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($bsCount) ? $bsCount : (isset($logs) ? count($logs) : '—') ?></div>
                <div class="rep-sum-meta">Total Logs</div>
            </div>
        </div>
        <div class="rep-sum-status <?= isset($latestBS) ? (strtolower($latestBS['status']) === 'normal' ? 'status-good' : 'status-warn') : 'status-none' ?>">
            <?= isset($latestBS) ? ($latestBS['status'] === 'Normal' ? '✅ Within Target' : '⚠️ ' . $latestBS['status']) : '— No recent data' ?>
        </div>
    </div>

    <!-- Medications Summary -->
    <div class="rep-sum-card card-med">
        <div class="rep-sum-header">
            <div class="rep-sum-icon">💊</div>
            <div>
                <div class="rep-sum-title">Medications</div>
                <div class="rep-sum-period">Adherence Overview</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($medCount) ? $medCount : (isset($medications) ? count($medications) : '—') ?></div>
                <div class="rep-sum-meta">Active Meds</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($medsTaken) ? $medsTaken : (isset($todayStats['total']) ? $todayStats['total'] : '—') ?></div>
                <div class="rep-sum-meta">Doses Today</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($adherencePct) ? $adherencePct . '%' : '—' ?></div>
                <div class="rep-sum-meta">Adherence</div>
            </div>
        </div>
        <div class="rep-sum-status <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? 'status-good' : 'status-none' ?>">
            <?= (isset($todayStats['total']) && $todayStats['total'] > 0) ? '✅ Doses logged today' : '— Log medications daily' ?>
        </div>
    </div>

    <!-- Meals Summary -->
    <div class="rep-sum-card card-meals">
        <div class="rep-sum-header">
            <div class="rep-sum-icon">🥗</div>
            <div>
                <div class="rep-sum-title">Meals & Nutrition</div>
                <div class="rep-sum-period">Today's Intake</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($todayTotals['total_carbs']) ? $todayTotals['total_carbs'] . '<small>g</small>' : '—' ?></div>
                <div class="rep-sum-meta">Carbs</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($todayTotals['total_calories']) ? $todayTotals['total_calories'] : '—' ?></div>
                <div class="rep-sum-meta">Calories</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($todayTotals['total_meals']) ? $todayTotals['total_meals'] : '—' ?></div>
                <div class="rep-sum-meta">Meals Logged</div>
            </div>
        </div>
        <div class="rep-sum-status <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? 'status-good' : 'status-none' ?>">
            <?= (isset($todayTotals['total_meals']) && $todayTotals['total_meals'] > 0) ? '✅ Meals recorded today' : '— No meals logged today' ?>
        </div>
    </div>

    <!-- Activity Summary -->
    <div class="rep-sum-card card-activity">
        <div class="rep-sum-header">
            <div class="rep-sum-icon">🏃</div>
            <div>
                <div class="rep-sum-title">Physical Activity</div>
                <div class="rep-sum-period">This Week</div>
            </div>
        </div>
        <div class="rep-sum-body">
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($weekTotals['total_minutes']) ? $weekTotals['total_minutes'] . '<small>min</small>' : '—' ?></div>
                <div class="rep-sum-meta">Week Total</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($todayActivityMins) ? $todayActivityMins : (isset($activityTodayMins) ? $activityTodayMins : '—') ?></div>
                <div class="rep-sum-meta">Today (min)</div>
            </div>
            <div class="rep-sum-stat">
                <div class="rep-sum-val"><?= isset($weekTotals['total_activities']) ? $weekTotals['total_activities'] : '—' ?></div>
                <div class="rep-sum-meta">Sessions</div>
            </div>
        </div>
        <?php $weekMins = $weekTotals['total_minutes'] ?? 0; ?>
        <div class="rep-sum-status <?= $weekMins >= 150 ? 'status-good' : ($weekMins > 0 ? 'status-warn' : 'status-none') ?>">
            <?= $weekMins >= 150 ? '✅ Met weekly goal (150 min)' : ($weekMins > 0 ? '⚠️ Goal: 150 min/week' : '— No activity this week') ?>
        </div>
    </div>

</div>

<!-- DETAILED BLOOD SUGAR LOG TABLE -->
<div class="rep-section-label" style="margin-top: 32px;">Recent Blood Glucose Log</div>
<div class="rep-table-card">
    <?php if (!empty($bsLogs)): ?>
    <table class="rep-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Reading</th>
                <th>Type</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($bsLogs, 0, 15) as $log): ?>
            <tr>
                <td><?= date('M j, Y · g:i A', strtotime($log['logged_at'])) ?></td>
                <td class="rep-td-val"><?= $log['reading'] ?> <span>mg/dL</span></td>
                <td><?= htmlspecialchars($log['reading_type'] ?? '—') ?></td>
                <td>
                    <span class="rep-status-badge <?= strtolower($log['status']) ?>">
                        <?= $log['status'] === 'Normal' ? '✅' : ($log['status'] === 'High' ? '🔴' : '🟡') ?>
                        <?= $log['status'] ?>
                    </span>
                </td>
                <td class="rep-td-notes"><?= htmlspecialchars($log['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (!empty($logs)): ?>
    <table class="rep-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Reading</th>
                <th>Type</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($logs ?? [], 0, 15) as $log): ?>
            <tr>
                <td><?= date('M j, Y · g:i A', strtotime($log['logged_at'])) ?></td>
                <td class="rep-td-val"><?= $log['reading'] ?> <span>mg/dL</span></td>
                <td><?= htmlspecialchars($log['reading_type'] ?? '—') ?></td>
                <td>
                    <span class="rep-status-badge <?= strtolower($log['status']) ?>">
                        <?= $log['status'] === 'Normal' ? '✅' : ($log['status'] === 'High' ? '🔴' : '🟡') ?>
                        <?= $log['status'] ?>
                    </span>
                </td>
                <td class="rep-td-notes"><?= htmlspecialchars($log['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="rep-empty">
        <div class="rep-empty-icon">📋</div>
        <div class="rep-empty-title">No logs yet</div>
        <div class="rep-empty-sub">Start logging blood sugar readings to generate your report.</div>
        <a href="/diabetrack/public/patient/bloodsugar" class="rep-empty-btn">Go to Blood Sugar Logger →</a>
    </div>
    <?php endif; ?>
</div>

<!-- APPOINTMENTS HISTORY -->
<div class="rep-section-label" style="margin-top: 32px;">Appointment History</div>
<div class="rep-table-card">
    <?php if (!empty($appts)): ?>
    <table class="rep-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($appts, 0, 10) as $appt): ?>
            <tr>
                <td><?= date('M j, Y · g:i A', strtotime($appt['appointment_date'])) ?></td>
                <td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                <td>
                    <span class="rep-status-badge <?= strtolower($appt['status']) ?>">
                        <?= $appt['status'] === 'Upcoming' ? '📅' : ($appt['status'] === 'Completed' ? '✅' : '❌') ?>
                        <?= $appt['status'] ?>
                    </span>
                </td>
                <td class="rep-td-notes"><?= htmlspecialchars($appt['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="rep-empty">
        <div class="rep-empty-icon">📅</div>
        <div class="rep-empty-title">No appointments</div>
        <div class="rep-empty-sub">Schedule doctor visits to track them here.</div>
        <a href="/diabetrack/public/patient/appointments" class="rep-empty-btn">Go to Appointments →</a>
    </div>
    <?php endif; ?>
</div>

<!-- PRINTABLE NOTE -->
<div class="rep-print-note">
    <div class="rep-print-note-icon">🖨</div>
    <div>
        <strong>Print this report</strong> to bring to your next doctor visit. Click the <em>Print Report</em> button above or use your browser's print function (Ctrl+P / Cmd+P).
    </div>
</div>

<script>
// Range tab selection (visual only — full implementation needs backend filtering)
document.querySelectorAll('.rep-range-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rep-range-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const days = this.dataset.days;
        const labels = { '7': 'Last 7 Days', '14': 'Last 14 Days', '30': 'Last 30 Days', '90': 'Last 3 Months' };
        const el = document.getElementById('bsPeriodLabel');
        if (el) el.textContent = labels[days] || 'Last 7 Days';
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>