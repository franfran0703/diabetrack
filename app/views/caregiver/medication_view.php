<?php
$pageTitle  = 'Medication Monitor';
$activeMenu = 'medication';
ob_start();

// Hour slots shown on the ruler — 6 AM to 9 PM
$rulerHours = [6, 9, 12, 15, 18, 21];
$dayStart   = 6;   // 6 AM
$dayEnd     = 22;  // 10 PM  — range = 16 hours
$dayRange   = $dayEnd - $dayStart;

// Current hour as percentage across the ruler
$nowHour = (int)date('G') + ((int)date('i') / 60);
$nowPct  = max(0, min(100, ($nowHour - $dayStart) / $dayRange * 100));

// Helper — schedule_time string → left % on the ruler
function timeToPercent($timeStr, $dayStart, $dayRange) {
    $h       = (int)date('G', strtotime($timeStr));
    $m       = (int)date('i', strtotime($timeStr));
    $decimal = $h + ($m / 60);
    return max(0, min(96, ($decimal - $dayStart) / $dayRange * 100));
}

$pendingCount = 0;
if (!empty($medications)) {
    $pendingCount = count(array_filter(
        $medications,
        fn($m) => !($loggedToday[$m['id']] ?? false)
    ));
}
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>">
<link href="/diabetrack/public/assets/css/caregiver_medication.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgmed-header">
    <div>
        <div class="cgmed-eyebrow">Schedule Monitor</div>
        <h1 class="cgmed-title">Medication <span>Monitor</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cgmed-patient-chip">
        <div class="cgmed-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cgmed-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cgmed-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to monitor medications.
    </div>
</div>

<?php else: ?>

<!-- ═══════════════════════════════════════════════════════
     PLANNER — full-width time-track
     ═══════════════════════════════════════════════════════ -->
<div class="cgmed-planner">

    <div class="cgmed-planner-header">
        <div class="cgmed-planner-label">Today's Schedule</div>
        <div class="cgmed-planner-date"><?= date('l, F j, Y') ?></div>
    </div>

    <!-- Time ruler — offset left to match label column width -->
    <div class="cgmed-ruler-track">
        <?php foreach ($rulerHours as $h):
            $isNow = ($nowHour >= $h && $nowHour < $h + 3);
        ?>
        <div class="cgmed-ruler-tick <?= $isNow ? 'now' : '' ?>">
            <?= date('g A', mktime($h, 0, 0)) ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($medications)): ?>
    <div class="cgmed-empty">
        <div class="cgmed-empty-icon">💊</div>
        <div class="cgmed-empty-text">No medications in schedule.</div>
    </div>
    <?php else: ?>

    <div class="cgmed-track-rows">
        <?php foreach ($medications as $med):
            $logStatus = null;
            $logTime   = null;
            foreach ($todayLogs as $tl) {
                if (($tl['medication_id'] ?? null) == $med['id'] ||
                    $tl['name'] === $med['name']) {
                    $logStatus = $tl['status'];
                    $logTime   = $tl['logged_at'];
                    break;
                }
            }
            $logged    = $loggedToday[$med['id']] ?? false;
            $pillClass = $logged ? strtolower($logStatus ?? 'taken') : 'pending';
            $pillEmoji = $pillClass === 'taken' ? '✅' : ($pillClass === 'missed' ? '❌' : '⏳');
            $leftPct   = timeToPercent($med['schedule_time'], $dayStart, $dayRange);
        ?>
        <div class="cgmed-track-row">

            <div class="cgmed-track-label">
                <div class="cgmed-track-med-name"><?= htmlspecialchars($med['name']) ?></div>
                <div class="cgmed-track-med-dose"><?= htmlspecialchars($med['dosage']) ?></div>
            </div>

            <div class="cgmed-track-bar">
                <!-- Grid lines -->
                <div class="cgmed-track-grid">
                    <?php for ($i = 0; $i < count($rulerHours); $i++): ?>
                    <div class="cgmed-track-grid-col"></div>
                    <?php endfor; ?>
                </div>

                <!-- NOW line -->
                <div class="cgmed-now-indicator" style="left:<?= $nowPct ?>%;"></div>

                <!-- Med pill -->
                <div class="cgmed-track-pill <?= $pillClass ?>"
                     style="left:<?= $leftPct ?>%;"
                     title="<?= htmlspecialchars($med['name']) ?> · <?= date('h:i A', strtotime($med['schedule_time'])) ?>">
                    <div class="cgmed-pill-dot"></div>
                    <?= $pillEmoji ?>
                    <?= htmlspecialchars($med['name']) ?>
                    <span class="cgmed-pill-time"><?= date('h:i A', strtotime($med['schedule_time'])) ?></span>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════
     BODY — med list (left) + status sidebar (right)
     ═══════════════════════════════════════════════════════ -->
<div class="cgmed-body">

    <!-- LEFT: MED LIST -->
    <div class="cgmed-med-list">
        <div class="cgmed-panel-label">
            All Medications — <?= date('M d, Y') ?>
        </div>

        <?php if (empty($medications)): ?>
        <div class="cgmed-empty">
            <div class="cgmed-empty-icon">💊</div>
            <div class="cgmed-empty-text">No medications in schedule.</div>
        </div>
        <?php else: ?>
        <?php foreach ($medications as $med):
            $logStatus = null;
            $logTime   = null;
            foreach ($todayLogs as $tl) {
                if (($tl['medication_id'] ?? null) == $med['id'] ||
                    $tl['name'] === $med['name']) {
                    $logStatus = $tl['status'];
                    $logTime   = $tl['logged_at'];
                    break;
                }
            }
            $logged    = $loggedToday[$med['id']] ?? false;
            $cardClass = $logged ? strtolower($logStatus ?? 'taken') : 'pending';
            $icon      = $cardClass === 'taken' ? '✅' : ($cardClass === 'missed' ? '❌' : '⏳');
        ?>
        <div class="cgmed-med-card <?= $cardClass ?>">
            <div class="cgmed-med-icon"><?= $icon ?></div>
            <div class="cgmed-med-info">
                <div class="cgmed-med-name"><?= htmlspecialchars($med['name']) ?></div>
                <div class="cgmed-med-meta">
                    <span>💊 <?= htmlspecialchars($med['dosage']) ?></span>
                    <span>·</span>
                    <span>🕐 <?= date('h:i A', strtotime($med['schedule_time'])) ?></span>
                    <span>·</span>
                    <span><?= htmlspecialchars($med['frequency']) ?></span>
                    <?php if ($logTime): ?>
                    <span>·</span>
                    <span style="color:rgba(255,200,160,0.28);">Logged <?= date('h:i A', strtotime($logTime)) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cgmed-med-right">
                <div class="cgmed-med-time"><?= date('h:i A', strtotime($med['schedule_time'])) ?></div>
                <span class="cgmed-med-badge <?= $cardClass ?>">
                    <?= $logged ? ($logStatus ?? 'Taken') : 'Pending' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- RIGHT: STATUS SIDEBAR -->
    <div class="cgmed-status-stack">

        <div class="cgmed-missed-hero">
            <div class="cgmed-missed-eyebrow">Missed Today</div>
            <div class="cgmed-missed-count"><?= $todayStats['missed'] ?? 0 ?></div>
            <div class="cgmed-missed-sub">
                <?= (($todayStats['missed'] ?? 0) > 0) ? 'Patient needs attention' : 'All clear for today!' ?>
            </div>
        </div>

        <div class="cgmed-stat-tile warm">
            <div class="cgmed-tile-icon">📋</div>
            <div>
                <div class="cgmed-tile-val"><?= count($medications) ?></div>
                <div class="cgmed-tile-label">Medications scheduled</div>
            </div>
        </div>

        <div class="cgmed-stat-tile green">
            <div class="cgmed-tile-icon">✅</div>
            <div>
                <div class="cgmed-tile-val"><?= $todayStats['taken'] ?? 0 ?></div>
                <div class="cgmed-tile-label">Doses taken today</div>
            </div>
        </div>

        <div class="cgmed-stat-tile glass">
            <div class="cgmed-tile-icon">⏳</div>
            <div>
                <div class="cgmed-tile-val"><?= $pendingCount ?></div>
                <div class="cgmed-tile-label">Still pending</div>
            </div>
        </div>

        <div class="cgmed-stat-tile danger">
            <div class="cgmed-tile-icon">📅</div>
            <div>
                <div class="cgmed-tile-val"><?= $todayStats['total'] ?? 0 ?></div>
                <div class="cgmed-tile-label">Logged today</div>
            </div>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     HISTORY TABLE — full width
     ═══════════════════════════════════════════════════════ -->
<div class="cgmed-table-panel">
    <div class="cgmed-panel-label">
        Dose History — <?= htmlspecialchars($patient['name']) ?>
        <span style="margin-left:auto;font-size:0.7rem;font-weight:700;color:#b8927e;letter-spacing:0;text-transform:none;">Last 50 entries</span>
    </div>

    <?php if (empty($allLogs)): ?>
    <div class="cgmed-empty">
        <div class="cgmed-empty-icon">📜</div>
        <div class="cgmed-empty-text">No dose history yet.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgmed-table">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Scheduled</th>
                    <th>Status</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allLogs as $log): ?>
                <tr>
                    <td class="cgmed-table-name"><?= htmlspecialchars($log['name']) ?></td>
                    <td class="cgmed-table-muted"><?= htmlspecialchars($log['dosage']) ?></td>
                    <td class="cgmed-table-muted"><?= date('h:i A', strtotime($log['schedule_time'])) ?></td>
                    <td>
                        <span class="cgmed-tpill <?= strtolower($log['status']) ?>">
                            <?= $log['status'] === 'Taken' ? '✅' : '❌' ?>
                            <?= $log['status'] ?>
                        </span>
                    </td>
                    <td class="cgmed-table-muted" style="white-space:nowrap;">
                        <?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>