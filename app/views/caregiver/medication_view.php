<?php
$pageTitle  = 'Medication Monitor';
$activeMenu = 'medication';
ob_start();

// ── Sort by schedule_time ──────────────────────────────────
if (!empty($medications)) {
    usort($medications, fn($a,$b) => strtotime($a['schedule_time']) - strtotime($b['schedule_time']));
}

// ── Map today's logs by med id ─────────────────────────────
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

// ── Current time helpers ───────────────────────────────────
$nowMin  = (int)date('G') * 60 + (int)date('i');

// ── Pending count ──────────────────────────────────────────
$pendingCount = 0;
foreach ($medications as $m) {
    if (!($logMap[$m['id']] ?? false)) $pendingCount++;
}

// ── Next upcoming medication ───────────────────────────────
$nextMed = null;
foreach ($medications as $med) {
    if ($logMap[$med['id']] ?? false) continue;
    $medMin = (int)date('G', strtotime($med['schedule_time'])) * 60 + (int)date('i', strtotime($med['schedule_time']));
    if ($medMin >= $nowMin) { $nextMed = $med; break; }
}

// ── 7-day adherence ────────────────────────────────────────
$adherenceRate = 0; $weekTaken = 0; $weekTotal = 0;
if (!empty($allLogs)) {
    $cutoff = strtotime('-7 days');
    foreach ($allLogs as $l) {
        if (strtotime($l['logged_at']) >= $cutoff) {
            $weekTotal++;
            if (strtolower($l['status']) === 'taken') $weekTaken++;
        }
    }
    $adherenceRate = $weekTotal > 0 ? round($weekTaken / $weekTotal * 100) : 0;
}

// ── 7-day heatmap ──────────────────────────────────────────
$heatmap = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $heatmap[$day] = ['taken' => 0, 'missed' => 0, 'total' => 0, 'date' => $day];
}
foreach ($allLogs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    if (isset($heatmap[$d])) {
        $heatmap[$d]['total']++;
        if (strtolower($l['status']) === 'taken') $heatmap[$d]['taken']++;
        else $heatmap[$d]['missed']++;
    }
}

// ── Today stats ────────────────────────────────────────────
$total   = max(1, count($medications));
$taken   = (int)($todayStats['taken']  ?? 0);
$missed  = (int)($todayStats['missed'] ?? 0);
$pct     = round($taken / $total * 100);
$circ    = 2 * M_PI * 28; $dash = $circ * $pct / 100;
$ringColor = $pct >= 80 ? '#22c55e' : ($pct >= 50 ? '#f59e0b' : '#ef4444');

// ── Overdue count ──────────────────────────────────────────
$overdueCount = 0;
foreach ($medications as $med) {
    $log = $logMap[$med['id']] ?? null;
    if ($log) continue;
    $medMin = (int)date('G', strtotime($med['schedule_time'])) * 60 + (int)date('i', strtotime($med['schedule_time']));
    if ($nowMin > $medMin + 30) $overdueCount++;
}

// ── Group medications into time blocks ─────────────────────
$timeBlocks = [
    'morning'   => ['label' => 'Morning',   'icon' => 'ti-sunrise',   'range' => [0,   719],  'meds' => []],
    'afternoon' => ['label' => 'Afternoon', 'icon' => 'ti-sun',       'range' => [720, 1079], 'meds' => []],
    'evening'   => ['label' => 'Evening',   'icon' => 'ti-sunset',    'range' => [1080,1259], 'meds' => []],
    'night'     => ['label' => 'Night',     'icon' => 'ti-moon',      'range' => [1260,1439], 'meds' => []],
];
foreach ($medications as $idx => $med) {
    $schedMin = (int)date('G', strtotime($med['schedule_time'])) * 60 + (int)date('i', strtotime($med['schedule_time']));
    $log      = $logMap[$med['id']] ?? null;
    $logged   = (bool)$log;
    $status   = $logged ? strtolower($log['status']) : 'pending';
    $overdue  = !$logged && ($nowMin > $schedMin + 30);
    $isNext   = ($nextMed && $nextMed['id'] === $med['id']);
    $cardStatus = $overdue ? 'overdue' : $status;
    $logTime  = $log ? date('h:i A', strtotime($log['logged_at'])) : null;
    foreach ($timeBlocks as $key => &$block) {
        if ($schedMin >= $block['range'][0] && $schedMin <= $block['range'][1]) {
            $block['meds'][] = compact('med','cardStatus','status','logTime','isNext','schedMin');
            break;
        }
    }
    unset($block);
}

// ── Determine which blocks the "now" divider falls between ─
$nowBlockKey = 'morning';
foreach ($timeBlocks as $key => $block) {
    if ($nowMin >= $block['range'][0] && $nowMin <= $block['range'][1]) {
        $nowBlockKey = $key; break;
    } elseif ($nowMin > $block['range'][1]) {
        $nowBlockKey = $key;
    }
}

// ── Day progress pct ───────────────────────────────────────
$dayPct = min(100, round($nowMin / 1440 * 100));

// ── All-time taken/missed for drawer footer ────────────────
$histTaken  = count(array_filter($allLogs, fn($l) => strtolower($l['status']) === 'taken'));
$histMissed = count($allLogs) - $histTaken;

// ── Group allLogs by date for drawer ──────────────────────
$logsByDate = [];
foreach ($allLogs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);
?>

<link href="<?= BASE_URL ?>/assets/css/caregiver_medication.css?v=<?= time() ?>" rel="stylesheet">

<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="cgmed-header">
    <div>
        <div class="cgmed-eyebrow"><i class="ti ti-pill"></i> Schedule Monitor</div>
        <h1 class="cgmed-title">Medication <span>Monitor</span></h1>
        <p class="cgmed-sub"><?= date('l, F j') ?> &middot; Real-time adherence tracking</p>
    </div>
    <?php if ($patient): ?>
    <div class="cgmed-header-right">
        <?php if ($pendingCount > 0): ?>
        <div class="cgmed-pending-chip">
            <i class="ti ti-clock-exclamation"></i>
            <?= $pendingCount ?> dose<?= $pendingCount > 1 ? 's' : '' ?> pending
        </div>
        <?php endif; ?>
        <div class="cgmed-patient-chip">
            <div class="cgmed-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
            <div>
                <div class="cgmed-patient-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
                <div class="cgmed-patient-label">Linked Patient</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<!-- ══ NO PATIENT ════════════════════════════════════════ -->
<div class="cgmed-no-patient">
    <div class="cgmed-no-patient-icon"><i class="ti ti-link"></i></div>
    <div class="cgmed-no-patient-title">No Patient Linked</div>
    <div class="cgmed-no-patient-sub">
        <a href="<?= BASE_URL ?>/caregiver/patients">Link a patient</a> to begin monitoring medications.
    </div>
</div>

<?php else: ?>

<!-- ══ STAT CARDS ════════════════════════════════════════ -->
<div class="cgmed-stat-row">

    <!-- 1. Today's Progress (primary) -->
    <div class="cgmed-stat-card card-primary <?= $missed > 0 ? 'has-alert' : '' ?>">
        <div class="cgmed-stat-card-top">
            <div class="cgmed-stat-icon-wrap">
                <i class="ti ti-pill"></i>
            </div>
            <div class="cgmed-ring-wrap">
                <svg width="44" height="44" viewBox="0 0 56 56">
                    <circle cx="28" cy="28" r="22" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="5"/>
                    <circle cx="28" cy="28" r="22" fill="none"
                            stroke="#fff" stroke-width="5"
                            stroke-dasharray="<?= round($dash / ($circ) * 138.2, 1) ?> 138.2"
                            stroke-dashoffset="<?= round(138.2 / 4, 1) ?>"
                            stroke-linecap="round"
                            class="cgmed-ring-arc"/>
                </svg>
            </div>
        </div>
        <div class="cgmed-stat-val"><?= $taken ?><small> / <?= $total ?></small></div>
        <div class="cgmed-stat-label">Today's Progress</div>
        <div class="cgmed-stat-meta">
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 11px;border-radius:100px;background:rgba(255,255,255,0.18);color:#fff;">
                <i class="ti ti-percentage" style="font-size:12px;"></i> <?= $pct ?>% done
            </span>
        </div>
    </div>

    <!-- 2. 7-Day Adherence (glass) -->
    <div class="cgmed-stat-card card-glass">
        <div class="cgmed-stat-card-top">
            <div class="cgmed-stat-icon-wrap glass"><i class="ti ti-chart-bar"></i></div>
        </div>
        <div class="cgmed-stat-val dark"><?= $adherenceRate ?><small>%</small></div>
        <div class="cgmed-stat-label dark">7-Day Adherence</div>
        <div class="cgmed-adh-bar-track">
            <div class="cgmed-adh-bar-fill" style="width:<?= $adherenceRate ?>%;background:<?=
                $adherenceRate >= 80 ? 'linear-gradient(90deg,#4ade80,#22c55e)'
              : ($adherenceRate >= 50 ? 'linear-gradient(90deg,#fbbf24,#f59e0b)'
              : 'linear-gradient(90deg,#f87171,#ef4444)') ?>;"></div>
        </div>
        <div class="cgmed-stat-sub dark" style="color:rgba(255,200,160,0.3);">
            <i class="ti ti-history"></i> <?= $weekTaken ?> of <?= $weekTotal ?> doses
        </div>
    </div>

    <!-- 3. Missed Today (danger/normal) -->
    <div class="cgmed-stat-card <?= $missed > 0 ? 'card-danger' : 'card-normal' ?>">
        <div class="cgmed-stat-card-top">
            <div class="cgmed-stat-icon-wrap <?= $missed > 0 ? 'danger' : 'normal' ?>">
                <i class="ti ti-<?= $missed > 0 ? 'alert-circle' : 'circle-check' ?>"></i>
            </div>
        </div>
        <div class="cgmed-stat-val <?= $missed > 0 ? 'danger' : 'normal' ?>"><?= $missed ?></div>
        <div class="cgmed-stat-label <?= $missed > 0 ? 'danger' : 'normal' ?>">Missed Today</div>
        <div class="cgmed-stat-sub <?= $missed > 0 ? 'danger' : 'normal' ?>">
            <i class="ti ti-<?= $missed > 0 ? 'alert-triangle' : 'thumb-up' ?>"></i>
            <?= $missed > 0 ? 'needs attention' : 'all clear!' ?>
        </div>
    </div>

    <!-- 4. Overdue Now (warn) -->
    <div class="cgmed-stat-card card-warn">
        <div class="cgmed-stat-card-top">
            <div class="cgmed-stat-icon-wrap warn"><i class="ti ti-alarm"></i></div>
        </div>
        <div class="cgmed-stat-val warn"><?= $overdueCount ?></div>
        <div class="cgmed-stat-label warn">Overdue Now</div>
        <div class="cgmed-stat-sub warn">
            <i class="ti ti-clock-x"></i> past schedule time
        </div>
    </div>

    <!-- 5. Pending (normal/glass) -->
    <div class="cgmed-stat-card card-normal">
        <div class="cgmed-stat-card-top">
            <div class="cgmed-stat-icon-wrap normal"><i class="ti ti-hourglass"></i></div>
        </div>
        <div class="cgmed-stat-val normal"><?= $pendingCount ?></div>
        <div class="cgmed-stat-label normal">Still Pending</div>
        <div class="cgmed-rate-bar-track">
            <div class="cgmed-rate-bar-fill" style="width:<?= $total > 0 ? round(($total - $pendingCount) / $total * 100) : 0 ?>%;"></div>
        </div>
        <div class="cgmed-stat-sub normal">
            <i class="ti ti-check"></i> <?= $total - $pendingCount ?> of <?= $total ?> done
        </div>
    </div>

</div><!-- /.cgmed-stat-row -->


<!-- ══ MAIN GRID ═════════════════════════════════════════ -->
<div class="cgmed-main-grid">

    <!-- ── LEFT: Schedule Board ── -->
    <div class="cgmed-schedule-card">
        <div class="cgmed-schedule-header">
            <div>
                <div class="cgmed-section-eyebrow">Today's Doses</div>
                <div class="cgmed-section-title">Daily Schedule</div>
            </div>
            <div class="cgmed-legend">
                <span><span class="cgmed-legend-dot" style="background:#22c55e;"></span> Taken</span>
                <span><span class="cgmed-legend-dot" style="background:#ef4444;"></span> Missed</span>
                <span><span class="cgmed-legend-dot" style="background:#f59e0b;"></span> Overdue</span>
                <span><span class="cgmed-legend-dot" style="background:#f97447;opacity:0.65;"></span> Pending</span>
            </div>
        </div>

        <!-- Day scrubber -->
        <div class="cgmed-day-scrubber">
            <div class="cgmed-scrubber-labels">
                <span>12 AM</span><span>6 AM</span><span>12 PM</span><span>6 PM</span><span>11 PM</span>
            </div>
            <div class="cgmed-scrubber-track">
                <div class="cgmed-scrubber-fill" style="width:<?= $dayPct ?>%;"></div>
                <div class="cgmed-scrubber-now" style="left:<?= $dayPct ?>%;">
                    <span class="cgmed-scrubber-now-label"><?= date('g:i A') ?></span>
                </div>
            </div>
        </div>

        <?php if (empty($medications)): ?>
        <div class="cgmed-schedule-empty">
            <i class="ti ti-pill-off"></i>
            No medications scheduled for this patient.
        </div>
        <?php else: ?>
        <div class="cgmed-block-list">
            <?php
            $blockKeys   = array_keys($timeBlocks);
            $nowInserted = false;

            foreach ($timeBlocks as $blockKey => $block):
                if (empty($block['meds'])) continue;
            ?>
            <!-- Now divider BEFORE this block if nowBlock is this one and we haven't inserted yet -->
            <?php if (!$nowInserted && $blockKey === $nowBlockKey && $nowMin <= $block['range'][1]): ?>
                <?php $nowInserted = true; ?>
            <?php elseif (!$nowInserted && $nowMin > $block['range'][1]): ?>
                <!-- current time is after this block, divider goes after it — handled below -->
            <?php endif; ?>

            <div class="cgmed-time-block">
                <div class="cgmed-block-label">
                    <i class="ti <?= $block['icon'] ?>"></i>
                    <?= $block['label'] ?>
                    <span class="cgmed-block-count"><?= count($block['meds']) ?> dose<?= count($block['meds']) !== 1 ? 's' : '' ?></span>
                </div>

                <?php foreach ($block['meds'] as $entry):
                    $med    = $entry['med'];
                    $cs     = $entry['cardStatus'];
                    $logTm  = $entry['logTime'];
                    $isNext = $entry['isNext'];
                    $sIcon  = match($cs) {
                        'taken'   => 'ti-check',
                        'missed'  => 'ti-x',
                        'overdue' => 'ti-alert-triangle',
                        default   => 'ti-clock',
                    };
                ?>
                <div class="cgmed-dose-row <?= $cs ?><?= $isNext ? ' next-dose' : '' ?>">
                    <?php if ($isNext): ?>
                    <div class="cgmed-next-badge">NEXT</div>
                    <?php endif; ?>
                    <div class="cgmed-dose-status-icon <?= $cs ?>">
                        <i class="ti <?= $sIcon ?>"></i>
                    </div>
                    <div class="cgmed-dose-info">
                        <div class="cgmed-dose-name"><?= htmlspecialchars($med['name']) ?></div>
                        <div class="cgmed-dose-meta">
                            <span class="cgmed-meta-chip"><i class="ti ti-pill"></i> <?= htmlspecialchars($med['dosage']) ?></span>
                            <span class="cgmed-meta-chip"><i class="ti ti-refresh"></i> <?= $med['frequency'] ?></span>
                            <?php if ($logTm): ?>
                            <span class="cgmed-meta-chip logged"><i class="ti ti-check"></i> <?= $logTm ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="cgmed-dose-time-col">
                        <div class="cgmed-dose-time-val"><?= date('h:i', strtotime($med['schedule_time'])) ?></div>
                        <div class="cgmed-dose-time-ampm"><?= date('A', strtotime($med['schedule_time'])) ?></div>
                    </div>
                    <div class="cgmed-dose-badge <?= $cs ?>">
                        <?php if ($cs === 'taken'):   ?><i class="ti ti-circle-check"></i> Taken
                        <?php elseif ($cs === 'missed'):  ?><i class="ti ti-circle-x"></i> Missed
                        <?php elseif ($cs === 'overdue'): ?><i class="ti ti-alert-circle"></i> Overdue
                        <?php else: ?><i class="ti ti-clock"></i> Pending<?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.cgmed-schedule-card -->


    <!-- ── RIGHT: Summary Card ── -->
    <div class="cgmed-summary-card">
        <div class="cgmed-section-eyebrow">This Week</div>
        <div class="cgmed-section-title" style="margin-bottom:0;">Adherence</div>

        <!-- 7-day bar chart — distinct from blood sugar's donut -->
        <div class="cgmed-week-bars">
            <?php foreach ($heatmap as $day => $data):
                $isToday = ($day === date('Y-m-d'));
                $dayName = date('D', strtotime($day));
                $state   = 'empty';
                $heightPct = 0;
                if ($data['total'] > 0) {
                    $r = $data['taken'] / $data['total'];
                    $state     = $r >= 1 ? 'full' : ($r >= 0.5 ? 'partial' : 'low');
                    $heightPct = round($r * 100);
                }
            ?>
            <div class="cgmed-week-bar-col <?= $isToday ? 'is-today' : '' ?>">
                <div class="cgmed-week-bar-track">
                    <div class="cgmed-week-bar-fill <?= $state ?>"
                         style="height:<?= $heightPct ?>%;"></div>
                </div>
                <div class="cgmed-week-bar-day"><?= $dayName ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary rows -->
        <div class="cgmed-summary-rows">
            <?php
            $takenPct   = $weekTotal > 0 ? round($weekTaken / $weekTotal * 100) : 0;
            $missedNum  = $weekTotal - $weekTaken;
            $missedPct  = $weekTotal > 0 ? round($missedNum  / $weekTotal * 100) : 0;
            $pendPct    = $total > 0 ? round($pendingCount / $total * 100) : 0;
            ?>
            <div class="cgmed-summary-row">
                <span class="cgmed-sum-dot" style="background:#22c55e;"></span>
                <span class="cgmed-sum-key">Taken</span>
                <span class="cgmed-sum-val"><?= $weekTaken ?></span>
                <span class="cgmed-sum-pct"><?= $takenPct ?>%</span>
            </div>
            <div class="cgmed-summary-row">
                <span class="cgmed-sum-dot" style="background:#ef4444;"></span>
                <span class="cgmed-sum-key">Missed</span>
                <span class="cgmed-sum-val"><?= $missedNum ?></span>
                <span class="cgmed-sum-pct"><?= $missedPct ?>%</span>
            </div>
            <div class="cgmed-summary-row">
                <span class="cgmed-sum-dot" style="background:#f97447;opacity:0.65;"></span>
                <span class="cgmed-sum-key">Pending today</span>
                <span class="cgmed-sum-val"><?= $pendingCount ?></span>
                <span class="cgmed-sum-pct"><?= $pendPct ?>%</span>
            </div>
        </div>

        <!-- Next dose panel -->
        <?php if ($nextMed): ?>
        <div class="cgmed-next-panel">
            <div class="cgmed-next-panel-icon"><i class="ti ti-clock-arrow-right"></i></div>
            <div>
                <div class="cgmed-next-eyebrow">Up Next</div>
                <div class="cgmed-next-name"><?= htmlspecialchars($nextMed['name']) ?></div>
                <div class="cgmed-next-time-text">
                    <?= date('h:i A', strtotime($nextMed['schedule_time'])) ?>
                    &middot; <?= htmlspecialchars($nextMed['dosage']) ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="cgmed-next-panel done">
            <div class="cgmed-next-panel-icon"><i class="ti ti-circle-check"></i></div>
            <div>
                <div class="cgmed-next-eyebrow">Schedule Done</div>
                <div class="cgmed-next-name">All doses logged</div>
                <div class="cgmed-next-time-text">No more doses for today</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- History button — same as blood sugar -->
        <?php if (!empty($allLogs)): ?>
        <button class="cgmed-history-btn" onclick="openDrawer()">
            <i class="ti ti-history"></i> Dose History
            <span class="cgmed-history-count"><?= count($allLogs) ?></span>
        </button>
        <?php endif; ?>

    </div><!-- /.cgmed-summary-card -->

</div><!-- /.cgmed-main-grid -->


<!-- ══ HISTORY DRAWER — same pattern as blood sugar ═════ -->
<div class="cgmed-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="cgmed-drawer" id="historyDrawer" role="dialog" aria-modal="true" aria-label="Dose History">

    <div class="cgmed-drawer-header">
        <div class="cgmed-drawer-header-left">
            <div class="cgmed-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="cgmed-drawer-title">Dose History</div>
                <div class="cgmed-drawer-sub">
                    <?= count($allLogs) ?> total &middot; <?= $histTaken ?> taken &middot; <?= $histMissed ?> missed
                </div>
            </div>
        </div>
        <button class="cgmed-drawer-close" onclick="closeDrawer()" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <div class="cgmed-drawer-controls">
        <div class="cgmed-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search medication, dosage…" oninput="filterDrawer()">
        </div>
        <div class="cgmed-drawer-filters">
            <button class="cgmed-drawer-filter active" data-df="all"    onclick="setFilter('all',    this)">All</button>
            <button class="cgmed-drawer-filter"         data-df="taken"  onclick="setFilter('taken',  this)">
                <span class="cgmed-df-dot" style="background:#22c55e;"></span> Taken
            </button>
            <button class="cgmed-drawer-filter"         data-df="missed" onclick="setFilter('missed', this)">
                <span class="cgmed-df-dot" style="background:#ef4444;"></span> Missed
            </button>
        </div>
    </div>

    <div class="cgmed-drawer-body" id="drawerBody">
        <?php if (empty($allLogs)): ?>
        <div class="cgmed-drawer-empty">
            <i class="ti ti-pill-off"></i>
            <p>No dose history logged yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayLogs):
            $isToday   = $date === date('Y-m-d');
            $isYest    = $date === date('Y-m-d', strtotime('-1 day'));
            $dlabel    = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('l, M j', strtotime($date)));
            $dayTaken  = count(array_filter($dayLogs, fn($l) => strtolower($l['status']) === 'taken'));
            $dayMissed = count($dayLogs) - $dayTaken;
        ?>
        <div class="cgmed-drawer-day-group" data-date="<?= $date ?>">
            <div class="cgmed-drawer-day-header">
                <div class="cgmed-drawer-day-label">
                    <?= $dlabel ?>
                    <?php if ($isToday): ?><span class="cgmed-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="cgmed-drawer-day-stats">
                    <span><?= count($dayLogs) ?> dose<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <?php if ($dayTaken):  ?><span class="cgmed-day-flag taken"><?= $dayTaken ?> taken</span><?php endif; ?>
                    <?php if ($dayMissed): ?><span class="cgmed-day-flag missed"><?= $dayMissed ?> missed</span><?php endif; ?>
                </div>
            </div>

            <div class="cgmed-tl-list">
                <?php foreach ($dayLogs as $log):
                    $ls    = strtolower($log['status']);
                    $sIcon = $ls === 'taken' ? 'ti-circle-check' : 'ti-circle-x';
                ?>
                <div class="cgmed-tl-item"
                     data-status="<?= $ls ?>"
                     data-search="<?= strtolower(($log['name'] ?? '') . ' ' . ($log['dosage'] ?? '')) ?>">
                    <div class="cgmed-tl-spine">
                        <div class="cgmed-tl-dot <?= $ls ?>">
                            <i class="ti <?= $sIcon ?>"></i>
                        </div>
                        <div class="cgmed-tl-line"></div>
                    </div>
                    <div class="cgmed-tl-card">
                        <div class="cgmed-tl-card-top">
                            <div class="cgmed-tl-name"><?= htmlspecialchars($log['name']) ?></div>
                            <div class="cgmed-tl-time">
                                <i class="ti ti-clock"></i>
                                <?= date('h:i A', strtotime($log['logged_at'])) ?>
                            </div>
                        </div>
                        <div class="cgmed-tl-meta">
                            <span class="cgmed-tl-dosage"><?= htmlspecialchars($log['dosage']) ?></span>
                            <span class="cgmed-tl-sched">
                                <i class="ti ti-calendar-time"></i>
                                Scheduled <?= date('h:i A', strtotime($log['schedule_time'])) ?>
                            </span>
                            <span class="cgmed-tl-status <?= $ls ?>"><?= ucfirst($ls) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div class="cgmed-drawer-no-results" id="drawerNoResults">
            <i class="ti ti-search-off"></i> No records match your search.
        </div>
    </div>

    <div class="cgmed-drawer-footer">
        <div class="cgmed-drawer-footer-stats">
            <span><i class="ti ti-circle-check" style="color:#4ade80;"></i> <?= $histTaken ?> Taken</span>
            <span><i class="ti ti-circle-x"     style="color:#f87171;"></i> <?= $histMissed ?> Missed</span>
            <span><i class="ti ti-pill"          style="color:#fbab6e;"></i> <?= count($allLogs) ?> Total</span>
        </div>
    </div>

</div><!-- /.cgmed-drawer -->

<?php endif; ?>

<!-- ══ SCRIPTS ═══════════════════════════════════════════ -->
<script>
let activeFilter = 'all';

function openDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function filterDrawer() {
    const q = document.getElementById('drawerSearch').value.toLowerCase().trim();
    applyFilters(q, activeFilter);
}
function setFilter(f, btn) {
    activeFilter = f;
    document.querySelectorAll('.cgmed-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), f);
}
function applyFilters(q, f) {
    let visible = 0;
    document.querySelectorAll('.cgmed-tl-item').forEach(item => {
        const ok = (f === 'all' || item.dataset.status === f)
                && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.cgmed-drawer-day-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.cgmed-tl-item')]
            .some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = visible === 0 ? 'flex' : 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>