<?php
$pageTitle  = 'Medication Monitor';
$activeMenu = 'medication';
ob_start();

$pendingCount = 0;
if (!empty($medications)) {
    $pendingCount = count(array_filter(
        $medications,
        fn($m) => !($loggedToday[$m['id']] ?? false)
    ));
}

// Sort medications by schedule_time
if (!empty($medications)) {
    usort($medications, fn($a,$b) => strtotime($a['schedule_time']) - strtotime($b['schedule_time']));
}

// Map logs by med id
$logMap = [];
foreach ($todayLogs as $tl) {
    $key = $tl['medication_id'] ?? null;
    if (!$key) {
        foreach ($medications as $m) {
            if ($m['name'] === $tl['name']) { $key = $m['id']; break; }
        }
    }
    if ($key) $logMap[$key] = $tl;
}
?>

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
            <div class="cgmed-patient-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
            <div class="cgmed-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div class="cgmed-no-patient">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to monitor medications.
    </div>
</div>

<?php else: ?>

<!-- ═══ TODAY'S DOSE BOARD ══════════════════════════════ -->
<div class="cgmed-board-wrap">

    <!-- Left: Dose cards -->
    <div class="cgmed-dose-board">
        <div class="cgmed-board-header">
            <div>
                <div class="cgmed-board-title">Today's Dose Board</div>
                <div class="cgmed-board-sub"><?= date('l, F j, Y') ?> · <?= count($medications) ?> medication<?= count($medications) !== 1 ? 's' : '' ?> scheduled</div>
            </div>
            <div class="cgmed-progress-ring-wrap" title="<?= $todayStats['taken'] ?? 0 ?> of <?= $todayStats['total'] ?? count($medications) ?> taken">
                <?php
                $total = max(1, count($medications));
                $taken = (int)($todayStats['taken'] ?? 0);
                $pct   = round($taken / $total * 100);
                $circ  = 2 * M_PI * 22; // r=22
                $dash  = $circ * $pct / 100;
                ?>
                <svg width="60" height="60" viewBox="0 0 60 60">
                    <circle cx="30" cy="30" r="22" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"/>
                    <circle cx="30" cy="30" r="22" fill="none" stroke="#22c55e" stroke-width="5"
                            stroke-dasharray="<?= round($dash,1) ?> <?= round($circ,1) ?>"
                            stroke-dashoffset="<?= round($circ/4, 1) ?>"
                            stroke-linecap="round"/>
                    <text x="30" y="34" text-anchor="middle" fill="#86efac"
                          style="font-family:'Cabinet Grotesk',sans-serif;font-weight:900;font-size:11px;"><?= $pct ?>%</text>
                </svg>
                <div class="cgmed-ring-label">Done</div>
            </div>
        </div>

        <?php if (empty($medications)): ?>
        <div class="cgmed-empty">
            <div class="cgmed-empty-icon">💊</div>
            <div class="cgmed-empty-text">No medications in schedule.</div>
        </div>
        <?php else: ?>
        <div class="cgmed-dose-list">
            <?php foreach ($medications as $med):
                $log       = $logMap[$med['id']] ?? null;
                $logged    = (bool)$log;
                $status    = $logged ? strtolower($log['status']) : 'pending';
                $logTime   = $log ? date('h:i A', strtotime($log['logged_at'])) : null;
                $schedTime = date('h:i A', strtotime($med['schedule_time']));

                // Is this dose overdue?
                $schedMin  = (int)date('G', strtotime($med['schedule_time'])) * 60 + (int)date('i', strtotime($med['schedule_time']));
                $nowMin    = (int)date('G') * 60 + (int)date('i');
                $overdue   = !$logged && ($nowMin > $schedMin + 30);
            ?>
            <div class="cgmed-dose-card <?= $status ?> <?= $overdue ? 'overdue' : '' ?>">

                <!-- Time column -->
                <div class="cgmed-dose-time-col">
                    <div class="cgmed-dose-hr"><?= date('h:i', strtotime($med['schedule_time'])) ?></div>
                    <div class="cgmed-dose-ampm"><?= date('A', strtotime($med['schedule_time'])) ?></div>
                </div>

                <!-- Status indicator -->
                <div class="cgmed-dose-status-col">
                    <div class="cgmed-dose-status-icon <?= $status ?>">
                        <?php if ($status === 'taken'): ?>
                            <i class="bi bi-check-lg"></i>
                        <?php elseif ($status === 'missed'): ?>
                            <i class="bi bi-x-lg"></i>
                        <?php else: ?>
                            <i class="bi bi-clock"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="cgmed-dose-info">
                    <div class="cgmed-dose-name"><?= htmlspecialchars($med['name']) ?></div>
                    <div class="cgmed-dose-meta">
                        <span class="cgmed-dose-meta-chip"><i class="bi bi-capsule"></i> <?= htmlspecialchars($med['dosage']) ?></span>
                        <span class="cgmed-dose-meta-chip"><i class="bi bi-arrow-repeat"></i> <?= $med['frequency'] ?></span>
                        <?php if ($logTime): ?>
                        <span class="cgmed-dose-meta-chip logged-at"><i class="bi bi-check2"></i> Logged at <?= $logTime ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status badge -->
                <div class="cgmed-dose-badge-col">
                    <?php if ($status === 'taken'): ?>
                        <span class="cgmed-dose-badge taken"><i class="bi bi-check-circle-fill"></i> Taken</span>
                    <?php elseif ($status === 'missed'): ?>
                        <span class="cgmed-dose-badge missed"><i class="bi bi-x-circle-fill"></i> Missed</span>
                    <?php elseif ($overdue): ?>
                        <span class="cgmed-dose-badge overdue"><i class="bi bi-exclamation-circle-fill"></i> Overdue</span>
                    <?php else: ?>
                        <span class="cgmed-dose-badge pending"><i class="bi bi-clock-fill"></i> Pending</span>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Status sidebar -->
    <div class="cgmed-status-stack">

        <div class="cgmed-missed-hero <?= ($todayStats['missed'] ?? 0) > 0 ? 'alert' : 'clear' ?>">
            <?php if (($todayStats['missed'] ?? 0) > 0): ?>
                <div class="cgmed-missed-eyebrow">⚠ Missed Today</div>
                <div class="cgmed-missed-count"><?= $todayStats['missed'] ?></div>
                <div class="cgmed-missed-sub">Dose<?= $todayStats['missed'] > 1 ? 's' : '' ?> missed — patient needs attention</div>
            <?php else: ?>
                <div class="cgmed-missed-eyebrow">✓ All Clear</div>
                <div class="cgmed-missed-count" style="color:#0f7a45;">0</div>
                <div class="cgmed-missed-sub">No missed doses today!</div>
            <?php endif; ?>
        </div>

        <div class="cgmed-stat-tile warm">
            <div class="cgmed-tile-icon"><i class="bi bi-capsule-pill"></i></div>
            <div>
                <div class="cgmed-tile-val"><?= count($medications) ?></div>
                <div class="cgmed-tile-label">Meds scheduled</div>
            </div>
        </div>
        <div class="cgmed-stat-tile green">
            <div class="cgmed-tile-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="cgmed-tile-val"><?= $todayStats['taken'] ?? 0 ?></div>
                <div class="cgmed-tile-label">Doses taken</div>
            </div>
        </div>
        <div class="cgmed-stat-tile glass">
            <div class="cgmed-tile-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="cgmed-tile-val"><?= $pendingCount ?></div>
                <div class="cgmed-tile-label">Still pending</div>
            </div>
        </div>
        <div class="cgmed-stat-tile danger">
            <div class="cgmed-tile-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="cgmed-tile-val"><?= $todayStats['missed'] ?? 0 ?></div>
                <div class="cgmed-tile-label">Missed today</div>
            </div>
        </div>

    </div>
</div>

<!-- HISTORY TABLE -->
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
                            <?= $log['status'] === 'Taken'
                                ? '<i class="bi bi-check-lg"></i>'
                                : '<i class="bi bi-x-lg"></i>' ?>
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