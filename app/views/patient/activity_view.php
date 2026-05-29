<?php $csrfToken = $_SESSION['csrf_token']; ?>
<script>const CSRF = '<?= $csrfToken ?>';</script>
<?php
$pageTitle  = 'Activity Monitor';
$activeMenu = 'activity';
ob_start();

/* ── Controller-provided — null-safe defaults ───────────── */
// patientWeightKg  : float|null  — null means not yet set in profile
// activityGoalMins : int         — personalised goal (default 30 per ADA)
$patientWeightKg  = $patientWeightKg  ?? null;
$activityGoalMins = $activityGoalMins ?? 30;

/* ── Calorie multipliers — MET values per intensity ────────
   MET (Metabolic Equivalent of Task):
     Light    ≈ 3.0  (walking, gentle yoga)
     Moderate ≈ 5.0  (brisk walking, cycling)
     Intense  ≈ 8.0  (running, HIIT, heavy lifting)
   Formula with weight: kcal = MET × weight_kg × (duration_min / 60)
   Formula without weight: rough estimate using 70 kg average
   All estimates labelled accordingly in the UI.              */
$MET = ['Light' => 3.0, 'Moderate' => 5.0, 'Intense' => 8.0];
$calMult = ['Light' => 5, 'Moderate' => 8, 'Intense' => 12]; // legacy fallback

/**
 * Calculate estimated calories burned.
 * Uses proper MET formula when weight is known; falls back to
 * the original flat multiplier with a clear "~est." label.
 *
 * @param  int        $mins      Duration in minutes
 * @param  string     $intensity Light|Moderate|Intense
 * @param  float|null $weightKg  Patient weight, null if unknown
 * @return array{kcal:int, precise:bool}
 */
function calcCals(int $mins, string $intensity, ?float $weightKg): array {
    global $MET, $calMult;
    if ($weightKg !== null && $weightKg > 0) {
        $met  = $MET[$intensity] ?? 5.0;
        $kcal = (int) round($met * $weightKg * ($mins / 60));
        return ['kcal' => $kcal, 'precise' => true];
    }
    // Fallback: original flat multiplier (assumes ~70 kg)
    return ['kcal' => $mins * ($calMult[$intensity] ?? 8), 'precise' => false];
}

/* ── Today stats ─────────────────────────────────────────── */
$todayMins = (int)($todayTotals['total_minutes']    ?? 0);
$todayActs = (int)($todayTotals['total_activities'] ?? 0);
$ringGoal  = $activityGoalMins; // personalised — set in profile

$calsToday = 0;
$calsHaveWeight = $patientWeightKg !== null;
foreach ($todayLogs ?? [] as $l) {
    $c = calcCals((int)$l['duration_minutes'], $l['intensity'], $patientWeightKg);
    $calsToday += $c['kcal'];
}

/* ── Ring ────────────────────────────────────────────────── */
$ringR         = 63;
$circumference = round(2 * M_PI * $ringR, 4);
$ringPct       = $ringGoal > 0 ? min($todayMins / $ringGoal, 1) : 0;
$dashOffset    = round($circumference * (1 - $ringPct), 4);
$ringReached   = $todayMins >= $ringGoal;
$todayGoalPct  = min(round($todayMins / $ringGoal * 100), 100);

/* ── Week totals ─────────────────────────────────────────── */
$weekMins = (int)($weekTotals['week_minutes']    ?? 0);
$weekActs = (int)($weekTotals['week_activities'] ?? 0);
$weekGoal = $ringGoal * 7;
$weekPct  = $weekGoal > 0 ? min(round($weekMins / $weekGoal * 100), 100) : 0;

/* ── Calories this week ──────────────────────────────────── */
$calsWeek = 0;
foreach ($logs ?? [] as $l) {
    if (strtotime($l['logged_at']) >= strtotime('-7 days')) {
        $c = calcCals((int)$l['duration_minutes'], $l['intensity'], $patientWeightKg);
        $calsWeek += $c['kcal'];
    }
}

/* ── Current streak ──────────────────────────────────────── */
$streak    = 0;
$checkDate = date('Y-m-d');
$logDates  = array_unique(array_map(fn($l) => date('Y-m-d', strtotime($l['logged_at'])), $logs ?? []));
while (in_array($checkDate, $logDates)) {
    $streak++;
    $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
}

/* ── Best streak ever ────────────────────────────────────── */
$bestStreak = $streak;
$tempStreak = 0;
$allDates   = array_unique(array_map(fn($l) => date('Y-m-d', strtotime($l['logged_at'])), $logs ?? []));
sort($allDates);
for ($i = 0; $i < count($allDates); $i++) {
    $tempStreak = 1;
    while ($i + 1 < count($allDates) &&
           date('Y-m-d', strtotime($allDates[$i] . ' +1 day')) === $allDates[$i + 1]) {
        $tempStreak++;
        $i++;
    }
    if ($tempStreak > $bestStreak) $bestStreak = $tempStreak;
}

/* ── Last logged activity (for modal prefill) ────────────── */
$lastActivity = null;
if (!empty($logs)) {
    $lastActivity = $logs[0]; // already sorted DESC by controller
}

/* ── Smart time-of-day modal defaults ───────────────────── */
$hour = (int) date('H');
$smartDefaultType = match(true) {
    $hour >= 5  && $hour < 9  => 'Walk',     // morning walk
    $hour >= 9  && $hour < 12 => 'Run',      // mid-morning run
    $hour >= 12 && $hour < 14 => 'Walk',     // post-lunch walk
    $hour >= 14 && $hour < 17 => 'Gym',      // afternoon gym
    $hour >= 17 && $hour < 20 => 'Cycling',  // evening ride
    $hour >= 20 && $hour < 23 => 'Yoga',     // evening wind-down
    default                    => 'Walk',
};
$smartDefaultDuration  = $lastActivity ? (int)$lastActivity['duration_minutes'] : 30;
$smartDefaultIntensity = $lastActivity ? $lastActivity['intensity'] : 'Light';

/* ── Week bar strip ──────────────────────────────────────── */
$minsByDate = [];
foreach ($logs ?? [] as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $minsByDate[$d] = ($minsByDate[$d] ?? 0) + $l['duration_minutes'];
}
$weekStrip = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $mins  = $minsByDate[$date] ?? 0;
    $weekStrip[] = [
        'date'    => $date,
        'label'   => date('D', strtotime($date)),
        'num'     => date('j',  strtotime($date)),
        'mins'    => $mins,
        'state'   => $mins === 0 ? 'none' : ($mins >= $ringGoal ? 'reached' : 'partial'),
        'isToday' => $date === date('Y-m-d'),
        'pct'     => $ringGoal > 0 ? min(round($mins / $ringGoal * 100), 100) : 0,
    ];
}

/* ── Chart data ──────────────────────────────────────────── */
$chartLabels = [];
$chartMins   = [];
$chartCals   = [];
$last7Map    = [];
foreach ($last7Days ?? [] as $d) { $last7Map[$d['log_date']] = $d['total_minutes']; }

$calsByDate = [];
foreach ($logs ?? [] as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $calsByDate[$d] = ($calsByDate[$d] ?? 0) + $l['duration_minutes'] * ($calMult[$l['intensity']] ?? 5);
}
for ($i = 6; $i >= 0; $i--) {
    $date          = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D', strtotime($date));
    $chartMins[]   = $last7Map[$date] ?? 0;
    $chartCals[]   = $calsByDate[$date] ?? 0;
}

/* ── Intensity breakdown ─────────────────────────────────── */
$intensityCounts = ['Light' => 0, 'Moderate' => 0, 'Intense' => 0];
foreach ($logs ?? [] as $l) {
    $i = $l['intensity'] ?? 'Light';
    $intensityCounts[$i] = ($intensityCounts[$i] ?? 0) + 1;
}
$totalLogs = count($logs ?? []);

/* ── Group all logs by date for drawer ───────────────────── */
$logsByDate = [];
foreach ($logs ?? [] as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);

/* ── Clinical impact tip ─────────────────────────────────── */
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

if ($todayMins >= $ringGoal) {
    $impactTip = "Daily goal reached! <strong>{$todayMins} minutes</strong> of activity can lower blood glucose by <strong>20–40 mg/dL</strong>. Keep it up — consistency is key for Type 2 diabetes management.";
} elseif ($todayMins > 0) {
    $remaining = $ringGoal - $todayMins;
    $impactTip = "Just <strong>{$remaining} more minutes</strong> to hit your goal. Even a short walk after meals reduces post-meal glucose spikes by up to <strong>30%</strong>.";
} else {
    $impactTip = "Exercise is one of the most powerful tools for diabetes control. Just <strong>{$ringGoal} minutes</strong> today can lower blood glucose by <strong>20–40 mg/dL</strong> for up to 24 hours.";
}

/* ── Activity icon map ───────────────────────────────────── */
function actIcon(string $name): string {
    $map = [
        'walk'    => 'ti-walk',          'run'     => 'ti-run',
        'cycl'    => 'ti-bike',          'swim'    => 'ti-droplets',
        'gym'     => 'ti-barbell',       'exerc'   => 'ti-barbell',
        'yoga'    => 'ti-leaf',          'danc'    => 'ti-music',
        'footbal' => 'ti-ball-football', 'bask'    => 'ti-ball-basketball',
        'hik'     => 'ti-mountain',      'jump'    => 'ti-arrows-up',
        'stretch' => 'ti-leaf',          'box'     => 'ti-boxing-glove',
        'tennis'  => 'ti-ripple',        'aerob'   => 'ti-heart-rate-monitor',
    ];
    $lower = strtolower($name);
    foreach ($map as $k => $v) { if (str_contains($lower, $k)) return $v; }
    return 'ti-activity';
}

/* ── Flash params ────────────────────────────────────────── */
$flashSaved   = isset($_GET['saved'])   && $_GET['saved']   === '1';
$flashDeleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="/diabetrack/public/assets/css/activity.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ══════════════════════════════════════════ -->
<div class="act-page-header">
    <div class="act-page-header-left">
        <div class="act-page-eyebrow">
            <i class="ti ti-run"></i> Activity Monitor
        </div>
        <h1 class="act-page-title">Move <span>Better</span></h1>
        <p class="act-page-sub">Track every workout, walk, and rep — your body's dashboard.</p>
    </div>
    <div class="act-page-header-right">
        <div class="act-today-status-badge <?= $todayActs > 0 ? 'logged' : 'not-logged' ?>">
            <i class="ti <?= $todayActs > 0 ? 'ti-circle-check' : 'ti-clock' ?>"></i>
            <?= $todayActs > 0
                ? $todayActs . ' activit' . ($todayActs === 1 ? 'y' : 'ies') . ' logged today'
                : 'No activity logged today' ?>
        </div>
        <?php if ($totalLogs > 0): ?>
        <button class="act-page-history-btn" onclick="openDrawer()" aria-label="View all activity logs">
            <i class="ti ti-history"></i> All Activity
            <span class="act-page-history-count"><?= $totalLogs ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>


<!-- ══ WEIGHT NUDGE BANNER (shown only when weight is unset) ══ -->
<?php if ($patientWeightKg === null): ?>
<div class="act-weight-nudge">
    <div class="act-weight-nudge-left">
        <i class="ti ti-scale"></i>
        <div>
            <div class="act-weight-nudge-title">Add your weight for accurate calorie estimates</div>
            <div class="act-weight-nudge-sub">
                Calorie calculations are currently rough estimates based on an average 70 kg person.
                Setting your weight makes them personalised and up to 3× more accurate.
            </div>
        </div>
    </div>
    <a href="/diabetrack/public/patient/profile#health-settings" class="act-weight-nudge-btn">
        <i class="ti ti-settings"></i> Set Weight
    </a>
</div>
<?php endif; ?>

<!-- ══ HERO CARD ════════════════════════════════════════════ -->
<div class="act-hero-header">
    <div class="act-hero-header-left">
        <div class="act-hero-greeting">
            <?= $greeting ?>, <?= htmlspecialchars(ucfirst(strtolower(explode(' ', trim($name))[0]))) ?>!
            <?= $todayActs > 0
                ? "You've logged {$todayActs} activit" . ($todayActs === 1 ? 'y' : 'ies') . " today."
                : "No activities yet — let's get moving!" ?>
        </div>
        <div class="act-impact-tip">
            <i class="ti ti-heart-rate-monitor"></i>
            <div class="act-impact-tip-text"><?= $impactTip ?></div>
        </div>
        <div class="act-hero-header-controls">
            <div class="act-streak-badge <?= $streak > 0 ? 'active' : 'none' ?>">
                <i class="ti <?= $streak > 0 ? 'ti-flame' : 'ti-moon' ?>"></i>
                <?= $streak > 0 ? $streak . '-day streak' : 'No streak yet' ?>
            </div>
            <?php if ($bestStreak > $streak && $bestStreak > 0): ?>
            <div class="act-streak-badge act-streak-best">
                <i class="ti ti-trophy"></i>
                Best: <?= $bestStreak ?> days
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="act-hero-header-ring">
        <div class="act-ring-wrap">
            <svg class="act-ring-svg" viewBox="0 0 148 148">
                <circle class="act-ring-bg" cx="74" cy="74" r="72"/>
                <circle class="act-ring-track" cx="74" cy="74" r="<?= $ringR ?>"/>
                <circle class="act-ring-fill <?= $ringReached ? 'reached' : '' ?>"
                        cx="74" cy="74" r="<?= $ringR ?>"
                        style="stroke-dasharray:<?= $circumference ?>;stroke-dashoffset:<?= $circumference ?>;"/>
            </svg>
            <div class="act-ring-center">
                <div class="act-ring-val"><?= $todayMins ?></div>
                <div class="act-ring-unit">min</div>
            </div>
        </div>
        <div class="act-ring-sub">of <?= $ringGoal ?>-min daily goal
            <?php if ($activityGoalMins !== 30): ?>
            <span class="act-ring-personalised"><i class="ti ti-user-check"></i></span>
            <?php endif; ?>
        </div>
        <div class="act-goal-chip <?= $ringReached ? 'reached' : '' ?>">
            <i class="ti <?= $ringReached ? 'ti-trophy' : 'ti-target' ?>"></i>
            <?= $ringReached ? 'Goal reached!' : ($ringGoal - $todayMins) . ' min to go' ?>
        </div>
    </div>
</div>


<!-- ══ QUICK STAT STRIP ═════════════════════════════════════ -->
<div class="act-stats-strip">
    <div class="act-stat-pill <?= $streak > 0 ? 'act-stat-pill--fire' : '' ?>">
        <div class="act-stat-pill-icon">
            <i class="ti <?= $streak > 0 ? 'ti-flame' : 'ti-moon' ?>"></i>
        </div>
        <div class="act-stat-pill-body">
            <div class="act-stat-pill-val"><?= $streak ?></div>
            <div class="act-stat-pill-lbl">day streak</div>
        </div>
    </div>
    <div class="act-stat-pill <?= $calsToday > 0 ? 'act-stat-pill--orange' : '' ?>">
        <div class="act-stat-pill-icon">
            <i class="ti ti-flame"></i>
        </div>
        <div class="act-stat-pill-body">
            <div class="act-stat-pill-val"><?= $calsToday ?></div>
            <div class="act-stat-pill-lbl">cal today</div>
        </div>
    </div>
    <div class="act-stat-pill <?= $weekMins > 0 ? 'act-stat-pill--green' : '' ?>">
        <div class="act-stat-pill-icon">
            <i class="ti ti-calendar-week"></i>
        </div>
        <div class="act-stat-pill-body">
            <div class="act-stat-pill-val"><?= $weekMins ?></div>
            <div class="act-stat-pill-lbl">min this week</div>
        </div>
    </div>
</div>


<!-- ══ TODAY'S ACTIVITIES ═══════════════════════════════════ -->
<div class="act-today-section">
    <div class="act-today-section-head">
        <div>
            <div class="act-section-label">
                <i class="ti ti-sun"></i> Today's Activities
                <?php if ($todayActs > 0): ?>
                <span class="act-today-badge"><?= $todayActs ?></span>
                <?php endif; ?>
            </div>
            <!-- Today goal progress bar -->
            <?php if ($todayMins > 0 || $todayActs === 0): ?>
            <div class="act-today-goal-bar">
                <div class="act-today-goal-track">
                    <div class="act-today-goal-fill <?= $ringReached ? 'reached' : '' ?>"
                         style="width:0%;" data-target="<?= $todayGoalPct ?>%"></div>
                </div>
                <span class="act-today-goal-label">
                    <?= $todayMins ?>/<span class="act-today-goal-val"><?= $ringGoal ?></span> min
                    <?php if ($ringReached): ?>
                    <i class="ti ti-circle-check" style="color:#22c55e;font-size:12px;margin-left:2px;"></i>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <button class="act-add-inline-btn" onclick="openModal()">
            <i class="ti ti-plus"></i> Log Activity
        </button>
    </div>

    <?php if (empty($todayLogs)): ?>
    <div class="act-today-empty-state">
        <div class="act-today-empty-icon"><i class="ti ti-run"></i></div>
        <p>No activities yet today</p>
        <span>Start moving and log your first session below.</span>
        <button class="act-today-cta" onclick="openModal()">
            <i class="ti ti-plus"></i> Log Activity
        </button>
    </div>
    <?php else: ?>
    <div class="act-feed act-feed--full">
        <?php foreach ($todayLogs as $log):
            $intensity  = strtolower($log['intensity']);
            $icon       = actIcon($log['activity_name']);
            $logCalData = calcCals((int)$log['duration_minutes'], $log['intensity'], $patientWeightKg);
            $logCals    = $logCalData['kcal'];
            $logCalPrecise = $logCalData['precise'];
        ?>
        <div class="act-feed-card">
            <div class="act-feed-icon <?= $intensity ?>">
                <i class="ti <?= $icon ?>"></i>
            </div>
            <div class="act-feed-body">
                <div class="act-feed-name"><?= htmlspecialchars($log['activity_name']) ?></div>
                <div class="act-feed-meta">
                    <span><i class="ti ti-clock"></i><?= date('h:i A', strtotime($log['logged_at'])) ?></span>
                    <span class="act-intensity-pip <?= $intensity ?>"><?= $log['intensity'] ?></span>
                    <?php if ($log['notes']): ?>
                    <span><i class="ti ti-notes"></i><?= htmlspecialchars(mb_strimwidth($log['notes'], 0, 26, '…')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="act-feed-right">
                <div class="act-feed-duration">
                    <?= $log['duration_minutes'] ?><small>min</small>
                </div>
                <div class="act-feed-cal <?= !$logCalPrecise ? 'act-feed-cal--est' : '' ?>">
                    <i class="ti ti-flame"></i>
                    <?= $logCalPrecise ? '' : '~' ?><?= $logCals ?> cal
                    <?php if (!$logCalPrecise): ?><span class="act-cal-est-tag">est.</span><?php endif; ?>
                </div>
                <button class="act-feed-del"
                        data-id="<?= $log['id'] ?>"
                        data-val="<?= htmlspecialchars($log['activity_name']) ?>"
                        onclick="confirmDelete(this)"
                        title="Delete">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>


<!-- ══ INSIGHTS ROW ═════════════════════════════════════════ -->
<div class="act-insights-row">

    <button class="act-insight-card" onclick="openWeekModal()" aria-label="View this week's activity">
        <div class="act-insight-card-inner">
            <div class="act-insight-icon-wrap act-insight-icon-wrap--week">
                <i class="ti ti-calendar-week"></i>
            </div>
            <div class="act-insight-body">
                <div class="act-insight-title">This Week</div>
                <div class="act-insight-sub">
                    <?= $weekMins ?> min
                    <?php if ($weekActs > 0): ?> · <?= $weekActs ?> session<?= $weekActs > 1 ? 's' : '' ?><?php endif; ?>
                </div>
                <div class="act-insight-progress-wrap">
                    <div class="act-insight-progress-track">
                        <div class="act-insight-progress-fill" style="width:<?= $weekPct ?>%"></div>
                    </div>
                    <span class="act-insight-progress-pct"><?= $weekPct ?>%</span>
                </div>
            </div>
            <i class="ti ti-chevron-right act-insight-chevron"></i>
        </div>
    </button>

    <button class="act-insight-card" onclick="openTrendsModal()" aria-label="View 7-day trends">
        <div class="act-insight-card-inner">
            <div class="act-insight-icon-wrap act-insight-icon-wrap--trends">
                <i class="ti ti-chart-bar"></i>
            </div>
            <div class="act-insight-body">
                <div class="act-insight-title">7-Day Trends</div>
                <div class="act-insight-sub">
                    <?php if (array_sum($chartMins) > 0): ?>
                        ~<?= round($calsWeek) ?> cal burned this week
                    <?php else: ?>
                        Start logging to see your trend
                    <?php endif; ?>
                </div>
                <?php if (array_sum(array_values($intensityCounts)) > 0): ?>
                <div class="act-insight-chips">
                    <?php
                    $chipMap = ['Light' => ['ti-leaf','#0f7a45','#d4f7e8'], 'Moderate' => ['ti-bolt','#d97706','#fef3c7'], 'Intense' => ['ti-flame','#dc2626','#fde8e8']];
                    foreach ($intensityCounts as $lvl => $cnt):
                        if (!$cnt) continue;
                        [$ico, $col, $bg] = $chipMap[$lvl];
                    ?>
                    <span class="act-insight-chip" style="color:<?= $col ?>;background:<?= $bg ?>;">
                        <i class="ti <?= $ico ?>"></i><?= $cnt ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <i class="ti ti-chevron-right act-insight-chevron"></i>
        </div>
    </button>

</div>


<!-- ══ WEEK MODAL ═══════════════════════════════════════════ -->
<div class="act-modal-overlay" id="weekModal" aria-modal="true" role="dialog">
    <div class="act-modal act-modal--wide">
        <div class="act-modal-head">
            <div class="act-modal-head-left">
                <div class="act-modal-icon"><i class="ti ti-calendar-week"></i></div>
                <div>
                    <div class="act-modal-title">This Week</div>
                    <div class="act-modal-sub"><?= $weekMins ?> min total · <?= $weekPct ?>% of weekly goal</div>
                </div>
            </div>
            <button class="act-modal-close" onclick="closeWeekModal()" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="act-modal-body act-modal-body--padded">
            <div class="act-week-strip act-week-strip--modal">
                <?php foreach ($weekStrip as $day): ?>
                <div class="act-week-day <?= $day['isToday'] ? 'is-today' : '' ?> <?= $day['mins'] > 0 ? 'clickable' : '' ?>"
                     <?= $day['mins'] > 0 ? 'onclick="closeWeekModal();openDrawerAndScrollTo(\'' . $day['date'] . '\')" title="' . $day['mins'] . ' min — click to view"' : '' ?>>
                    <div class="act-week-bar-wrap">
                        <div class="act-week-bar-goalline"></div>
                        <div class="act-week-bar-fill state-<?= $day['state'] ?>"
                             style="height:0%;" data-target="<?= $day['pct'] ?>%"></div>
                    </div>
                    <div class="act-week-day-num"><?= $day['num'] ?></div>
                    <div class="act-week-day-label"><?= $day['label'] ?></div>
                    <div class="act-week-day-mins"><?= $day['mins'] > 0 ? $day['mins'] . 'm' : '—' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="act-week-modal-footer">
                <div class="act-week-legend">
                    <div class="act-week-leg"><div class="act-week-leg-dot none"></div> No activity</div>
                    <div class="act-week-leg"><div class="act-week-leg-dot partial"></div> Partial</div>
                    <div class="act-week-leg"><div class="act-week-leg-dot reached"></div> Goal reached</div>
                </div>
                <?php if ($weekMins > 0): ?>
                <div class="act-week-total">
                    <i class="ti ti-sum"></i> <?= $weekMins ?> min · <?= $weekActs ?> sessions
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- ══ TRENDS MODAL ════════════════════════════════════════ -->
<div class="act-modal-overlay" id="trendsModal" aria-modal="true" role="dialog">
    <div class="act-modal act-modal--wide">
        <div class="act-modal-head">
            <div class="act-modal-head-left">
                <div class="act-modal-icon act-modal-icon--trends"><i class="ti ti-chart-bar"></i></div>
                <div>
                    <div class="act-modal-title">7-Day Trends</div>
                    <div class="act-modal-sub">Minutes & calories over the past week</div>
                </div>
            </div>
            <button class="act-modal-close" onclick="closeTrendsModal()" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="act-modal-body act-modal-body--padded">
            <?php if (array_sum(array_values($intensityCounts)) > 0): ?>
            <div class="act-trends-intensity-row">
                <?php
                $chipMap = ['Light' => 'ti-leaf', 'Moderate' => 'ti-bolt', 'Intense' => 'ti-flame'];
                foreach ($intensityCounts as $lvl => $cnt):
                    if (!$cnt) continue;
                ?>
                <div class="act-type-chip">
                    <i class="ti <?= $chipMap[$lvl] ?>"></i> <?= $lvl ?> (<?= $cnt ?>)
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="act-chart-legend">
                <div class="act-legend-item"><div class="act-legend-dot" style="background:#F97447;border-radius:50%;"></div> Minutes</div>
                <div class="act-legend-item"><div class="act-legend-dot" style="background:rgba(192,74,32,0.35);border-radius:2px;"></div> Est. Calories</div>
                <div class="act-legend-item"><div class="act-legend-dot" style="background:#22c55e;"></div> Goal reached</div>
            </div>

            <?php if (array_sum($chartMins) > 0): ?>
            <div class="act-chart-wrap act-chart-wrap--modal">
                <canvas id="actChart"></canvas>
            </div>
            <?php else: ?>
            <div class="act-chart-empty">
                <i class="ti ti-chart-bar"></i>
                <p>No activity this week yet.</p>
                <span>Your 7-day trend will appear here once you start logging.</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- ══ LOG MODAL ════════════════════════════════════════════ -->
<div class="act-modal-overlay" id="actModal" aria-modal="true" role="dialog">
    <div class="act-modal">
        <div class="act-modal-head">
            <div class="act-modal-head-left">
                <div class="act-modal-icon"><i class="ti ti-run"></i></div>
                <div>
                    <div class="act-modal-title">Log Activity</div>
                    <div class="act-modal-sub">Record a workout, walk, or any movement.</div>
                </div>
            </div>
            <button class="act-modal-close" onclick="closeModal()" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <div class="act-modal-body">
            <?php if ($lastActivity): ?>
            <!-- Quick-repeat chip — one tap fills all fields from last log -->
            <div class="act-repeat-chip-wrap">
                <span class="act-repeat-label"><i class="ti ti-history"></i> Last logged:</span>
                <button type="button" class="act-repeat-chip" onclick="repeatLastActivity()"
                    data-name="<?= htmlspecialchars($lastActivity['activity_name']) ?>"
                    data-duration="<?= (int)$lastActivity['duration_minutes'] ?>"
                    data-intensity="<?= htmlspecialchars($lastActivity['intensity']) ?>">
                    <i class="ti <?= actIcon($lastActivity['activity_name']) ?>"></i>
                    <?= htmlspecialchars($lastActivity['activity_name']) ?> ·
                    <?= $lastActivity['duration_minutes'] ?>min ·
                    <?= $lastActivity['intensity'] ?>
                    <i class="ti ti-corner-down-left act-repeat-enter"></i>
                </button>
            </div>
            <?php endif; ?>

            <form method="POST" action="/diabetrack/public/patient/activity?saved=1" id="actForm">

                <!-- Activity type -->
                <div class="act-form-group">
                    <div class="act-form-label"><i class="ti ti-grid-3x3"></i> Activity Type</div>
                    <div class="act-type-grid">
                        <?php
                        $quickTypes = [
                            ['icon'=>'ti-walk',     'label'=>'Walk'],
                            ['icon'=>'ti-run',      'label'=>'Run'],
                            ['icon'=>'ti-bike',     'label'=>'Cycling'],
                            ['icon'=>'ti-droplets', 'label'=>'Swim'],
                            ['icon'=>'ti-barbell',  'label'=>'Gym'],
                            ['icon'=>'ti-leaf',     'label'=>'Yoga'],
                            ['icon'=>'ti-mountain', 'label'=>'Hiking'],
                            ['icon'=>'ti-dots',     'label'=>'Other'],
                        ];
                        foreach ($quickTypes as $qt):
                            $isDefault = $qt['label'] === $smartDefaultType;
                        ?>
                        <button type="button" class="act-type-btn <?= $isDefault ? 'selected' : '' ?>"
                                onclick="selectType('<?= $qt['label'] ?>', this)">
                            <i class="ti <?= $qt['icon'] ?>"></i>
                            <?= $qt['label'] ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Custom name -->
                <div class="act-form-group" id="customNameGroup" style="display:none;">
                    <div class="act-form-label"><i class="ti ti-pencil"></i> Activity Name</div>
                    <input type="text" id="customNameInput" class="act-form-input"
                           placeholder="e.g. Pilates, Jump Rope, HIIT…">
                </div>
                <input type="hidden" name="activity_name" id="activityNameHidden" value="<?= htmlspecialchars($smartDefaultType) ?>">

                <!-- Duration -->
                <div class="act-form-group">
                    <div class="act-form-label"><i class="ti ti-clock"></i> Duration</div>

                    <!-- Quick preset pills -->
                    <div class="act-duration-presets">
                        <?php foreach ([10, 15, 20, 30, 45, 60] as $preset): ?>
                        <button type="button"
                                class="act-preset-pill <?= $preset === $smartDefaultDuration ? 'active' : '' ?>"
                                onclick="setPreset(<?= $preset ?>, this)">
                            <?= $preset ?>m
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="act-duration-stepper">
                        <button type="button" class="act-step-btn" onclick="stepDuration(-5)">
                            <i class="ti ti-minus"></i>
                        </button>
                        <div class="act-step-divider"></div>
                        <div class="act-duration-display">
                            <div class="act-duration-val" id="durationDisplay"><?= $smartDefaultDuration ?></div>
                            <div class="act-duration-unit">minutes</div>
                        </div>
                        <div class="act-step-divider"></div>
                        <button type="button" class="act-step-btn" onclick="stepDuration(5)">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" name="duration_minutes" id="durationHidden" value="<?= $smartDefaultDuration ?>">
                </div>

                <!-- Intensity -->
                <div class="act-form-group">
                    <div class="act-form-label"><i class="ti ti-flame"></i> Intensity</div>
                    <div class="act-intensity-row">
                        <?php
                        $intensityDefs = [
                            'Light'    => ['ti-leaf',  'sel-light'],
                            'Moderate' => ['ti-bolt',  'sel-moderate'],
                            'Intense'  => ['ti-flame', 'sel-intense'],
                        ];
                        foreach ($intensityDefs as $iVal => [$iIco, $iCls]):
                            $iActive = $iVal === $smartDefaultIntensity ? $iCls : '';
                        ?>
                        <button type="button" class="act-intensity-btn <?= $iActive ?>"
                                onclick="selectIntensity('<?= $iVal ?>',this)">
                            <i class="ti <?= $iIco ?>"></i> <?= $iVal ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="intensity" id="intensityHidden" value="<?= htmlspecialchars($smartDefaultIntensity) ?>">
                </div>

                <!-- Notes -->
                <div class="act-form-group">
                    <div class="act-form-label">
                        <i class="ti ti-notes"></i> Notes <span class="opt">(optional)</span>
                    </div>
                    <textarea name="notes" class="act-form-input act-form-textarea" rows="2"
                              placeholder="e.g. felt great, ran outdoors, skipped warm-up…"></textarea>
                </div>

                <!-- Live calorie preview -->
                <div class="act-cal-preview">
                    <div>
                        <div class="act-cal-preview-val" id="previewCals">—</div>
                        <div class="act-cal-preview-label">est. calories</div>
                    </div>
                    <div class="act-cal-preview-divider"></div>
                    <div class="act-cal-preview-bs">
                        <strong id="previewActivity"><?= htmlspecialchars($smartDefaultType) ?></strong> ·
                        <span id="previewDuration"><?= $smartDefaultDuration ?> min</span> ·
                        <span id="previewIntensity" style="color:#0f7a45;font-weight:800;"><?= $smartDefaultIntensity ?></span><br>
                        May lower glucose by <strong id="previewGlucoseDrop">—</strong>
                    </div>
                </div>

            </form>
        </div>

        <div class="act-modal-foot">
            <button type="button" class="act-cancel-btn" onclick="closeModal()">Cancel</button>
            <button type="submit" form="actForm" class="act-save-btn">
                <i class="ti ti-device-floppy"></i> Save Activity
            </button>
        </div>
    </div>
</div>


<!-- ══ HISTORY DRAWER ═══════════════════════════════════════ -->
<div class="act-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="act-drawer" id="historyDrawer" role="dialog" aria-label="Activity History" aria-modal="true">

    <div class="act-drawer-head">
        <div class="act-drawer-head-left">
            <div class="act-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="act-drawer-title">Activity History</div>
                <div class="act-drawer-sub"><?= $totalLogs ?> logs · <?= $weekActs ?> this week · <?= $streak ?> day streak</div>
            </div>
        </div>
        <button class="act-drawer-close" onclick="closeDrawer()" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>

    <div class="act-drawer-controls">
        <div class="act-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search by activity, intensity, notes…" oninput="filterDrawer()">
        </div>
        <div class="act-drawer-filters">
            <button class="act-drawer-filter active" data-df="all"      onclick="setFilter('all',this)">All</button>
            <button class="act-drawer-filter"         data-df="Light"    onclick="setFilter('Light',this)"><span class="act-df-dot light"></span> Light</button>
            <button class="act-drawer-filter"         data-df="Moderate" onclick="setFilter('Moderate',this)"><span class="act-df-dot moderate"></span> Moderate</button>
            <button class="act-drawer-filter"         data-df="Intense"  onclick="setFilter('Intense',this)"><span class="act-df-dot intense"></span> Intense</button>
        </div>
    </div>

    <div class="act-drawer-body" id="drawerBody">
        <?php if (empty($logs)): ?>
        <div class="act-drawer-empty">
            <i class="ti ti-run"></i>
            <p>No activity history yet.<br>Start logging!</p>
        </div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayLogs):
            $dayMins = array_sum(array_column($dayLogs, 'duration_minutes'));
            $dayCals = 0;
            foreach ($dayLogs as $l) { $c = calcCals((int)$l['duration_minutes'], $l['intensity'], $patientWeightKg); $dayCals += $c['kcal']; }
            $isToday   = $date === date('Y-m-d');
            $isYest    = $date === date('Y-m-d', strtotime('-1 day'));
            $dateLabel = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('l, M j', strtotime($date)));
        ?>
        <div class="act-drawer-group" data-date="<?= $date ?>">
            <div class="act-drawer-day-head">
                <div class="act-drawer-day-label">
                    <?= $dateLabel ?>
                    <?php if ($isToday): ?><span class="act-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="act-drawer-day-stats">
                    <span><?= count($dayLogs) ?> session<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <span class="act-drawer-day-total"><?= $dayMins ?> min</span>
                    <span class="act-di-cal"><i class="ti ti-flame"></i> ~<?= $dayCals ?> cal</span>
                    <?php if ($dayMins >= $ringGoal): ?>
                    <span class="act-drawer-day-goal"><i class="ti ti-check"></i> Goal</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="act-drawer-items">
                <?php foreach ($dayLogs as $log):
                    $intensity     = strtolower($log['intensity']);
                    $icon          = actIcon($log['activity_name']);
                    $logCalData    = calcCals((int)$log['duration_minutes'], $log['intensity'], $patientWeightKg);
                    $logCals       = $logCalData['kcal'];
                    $logCalPrecise = $logCalData['precise'];
                ?>
                <div class="act-drawer-item"
                     data-intensity="<?= $log['intensity'] ?>"
                     data-search="<?= strtolower(htmlspecialchars($log['activity_name'] . ' ' . $log['intensity'] . ' ' . ($log['notes'] ?? ''))) ?>">
                    <div class="act-di-icon <?= $intensity ?>">
                        <i class="ti <?= $icon ?>"></i>
                    </div>
                    <div class="act-di-body">
                        <div class="act-di-name"><?= htmlspecialchars($log['activity_name']) ?></div>
                        <div class="act-di-meta">
                            <span><i class="ti ti-clock"></i><?= date('h:i A', strtotime($log['logged_at'])) ?></span>
                            <span class="act-intensity-pip <?= $intensity ?>"><?= $log['intensity'] ?></span>
                            <?php if ($log['notes']): ?>
                            <span><i class="ti ti-notes"></i><?= htmlspecialchars(mb_strimwidth($log['notes'], 0, 30, '…')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="act-di-right">
                        <div class="act-di-dur"><?= $log['duration_minutes'] ?><small>min</small></div>
                        <div class="act-di-cal <?= !$logCalPrecise ? 'act-feed-cal--est' : '' ?>">
                            <i class="ti ti-flame"></i>
                            <?= $logCalPrecise ? '' : '~' ?><?= $logCals ?>
                            <?php if (!$logCalPrecise): ?><span class="act-cal-est-tag">est.</span><?php endif; ?>
                        </div>
                        <button class="act-di-del"
                                data-id="<?= $log['id'] ?>"
                                data-val="<?= htmlspecialchars($log['activity_name']) ?>"
                                onclick="confirmDelete(this)"
                                title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="act-drawer-foot">
        <div class="act-drawer-no-results" id="drawerNoResults">
            <i class="ti ti-search-off"></i> No activities match your search.
        </div>
        <div class="act-drawer-foot-stats">
            <span><i class="ti ti-leaf"  style="color:#22c55e;"></i> <?= $intensityCounts['Light'] ?> Light</span>
            <span><i class="ti ti-bolt"  style="color:#f59e0b;"></i> <?= $intensityCounts['Moderate'] ?> Moderate</span>
            <span><i class="ti ti-flame" style="color:#ef4444;"></i> <?= $intensityCounts['Intense'] ?> Intense</span>
        </div>
    </div>
</div>


<!-- ══ TOASTS ═══════════════════════════════════════════════ -->
<div class="act-toast act-toast-success" id="saveToast" aria-live="polite">
    <i class="ti ti-circle-check"></i>
    <span id="saveToastMsg">Activity saved!</span>
    <button class="act-toast-close" onclick="hideToast('saveToast')" aria-label="Dismiss"><i class="ti ti-x"></i></button>
</div>
<div class="act-toast" id="deleteToast" aria-live="polite">
    <i class="ti ti-trash"></i>
    <span id="toastDeleteMsg">Activity removed</span>
    <button class="act-toast-undo" id="toastUndo">Undo</button>
    <button class="act-toast-close" id="toastClose" aria-label="Dismiss"><i class="ti ti-x"></i></button>
</div>

<!-- ══ FAB ══════════════════════════════════════════════════ -->
<button class="patient-fab" onclick="openModal()" aria-label="Log new activity">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Log Activity</span>
</button>


<!-- ══ CHART JS ════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (array_sum($chartMins) > 0): ?>
<script>
(function () {
    const labels    = <?= json_encode($chartLabels) ?>;
    const mins      = <?= json_encode($chartMins) ?>;
    const cals      = <?= json_encode($chartCals) ?>;
    const goal      = <?= $ringGoal ?>;
    const barColors = mins.map(v => v >= goal ? 'rgba(34,197,94,0.75)' : 'rgba(249,116,71,0.78)');
    const barBorder = mins.map(v => v >= goal ? '#16a34a' : '#F97447');

    const goalPlugin = {
        id: 'goalLine',
        beforeDraw(chart) {
            const { ctx, chartArea: { left, right }, scales: { y } } = chart;
            if (!y) return;
            const yg = y.getPixelForValue(goal);
            ctx.save();
            ctx.setLineDash([6,4]); ctx.lineWidth = 1.8;
            ctx.strokeStyle = 'rgba(249,116,71,0.4)';
            ctx.beginPath(); ctx.moveTo(left, yg); ctx.lineTo(right, yg); ctx.stroke();
            ctx.fillStyle = 'rgba(249,116,71,0.55)';
            ctx.font = '700 10px DM Sans';
            ctx.fillText('Daily Goal', right - 70, yg - 5);
            ctx.restore();
        }
    };

    window._actChartConfig = {
        type: 'bar', plugins: [goalPlugin],
        data: { labels, datasets: [
            { label:'Minutes', data:mins, backgroundColor:barColors, borderColor:barBorder, borderWidth:2, borderRadius:8, borderSkipped:false, yAxisID:'y', order:1 },
            { label:'Est. Calories', data:cals, type:'line', borderColor:'rgba(192,74,32,0.5)', backgroundColor:'rgba(192,74,32,0.07)', borderWidth:2, pointBackgroundColor:'rgba(192,74,32,0.7)', pointRadius:4, tension:0.4, fill:true, yAxisID:'y2', order:0 }
        ]},
        options: {
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins: { legend:{display:false}, tooltip:{ backgroundColor:'#1a0800', titleColor:'#fbab6e', bodyColor:'#fff8f5', borderColor:'rgba(249,116,71,0.2)', borderWidth:1, padding:12, cornerRadius:12,
                callbacks:{ label: ctx => ctx.dataset.yAxisID==='y' ? ` ${ctx.parsed.y} min${ctx.parsed.y>=goal?' ✓ Goal':` · ${Math.max(0,goal-ctx.parsed.y)} to goal`}` : ` ~${ctx.parsed.y} cal burned` }
            }},
            scales: {
                y:  { beginAtZero:true, position:'left',  grid:{color:'rgba(249,116,71,0.07)'}, ticks:{font:{size:11,family:'DM Sans'},color:'#b8927e'}, border:{color:'transparent'}, title:{display:true,text:'Minutes',color:'#c4714a',font:{size:10,weight:'700'}} },
                y2: { beginAtZero:true, position:'right', grid:{drawOnChartArea:false}, ticks:{font:{size:10,family:'DM Sans'},color:'#c4714a'}, border:{color:'transparent'}, title:{display:true,text:'Calories',color:'#c04a20',font:{size:10,weight:'700'}} },
                x:  { grid:{display:false}, ticks:{font:{size:11,family:'DM Sans'},color:'#b8927e'}, border:{color:'transparent'} }
            }
        }
    };
})();
</script>
<?php endif; ?>


<!-- ══ INTERACTIVITY ═════════════════════════════════════════ -->
<script>
/* ── Ring animation ───────────────────────────────────────── */
window.addEventListener('load', () => {
    const ring = document.querySelector('.act-ring-fill');
    if (ring) {
        const final = <?= $dashOffset ?>;
        const circ  = <?= $circumference ?>;
        ring.style.strokeDashoffset = circ;
        requestAnimationFrame(() => setTimeout(() => { ring.style.strokeDashoffset = final; }, 120));
    }
    // Animate today goal bar
    document.querySelectorAll('.act-today-goal-fill[data-target]').forEach(el => {
        setTimeout(() => { el.style.width = el.dataset.target; }, 300);
    });
});

/* ── Modal state ──────────────────────────────────────────── */
let selectedType      = <?= json_encode($smartDefaultType) ?>;
let selectedIntensity = <?= json_encode($smartDefaultIntensity) ?>;
let duration          = <?= $smartDefaultDuration ?>;
// MET values (Metabolic Equivalent of Task per intensity band)
const MET_VALUES  = { Light: 3.0, Moderate: 5.0, Intense: 8.0 };
const WEIGHT_KG   = <?= $patientWeightKg !== null ? $patientWeightKg : 'null' ?>;
const GOAL_MINS   = <?= $activityGoalMins ?>;
const calMult     = { Light: 5, Moderate: 8, Intense: 12 }; // fallback

function estimateCals(durationMins, intensity) {
    if (WEIGHT_KG) {
        const met = MET_VALUES[intensity] || 5.0;
        return Math.round(met * WEIGHT_KG * (durationMins / 60));
    }
    // No weight: rough flat estimate, labelled as such in UI
    return durationMins * (calMult[intensity] || 8);
}

function openModal() {
    // Restore from sessionStorage if present (preserves last manual selection)
    const saved = sessionStorage.getItem('act_last');
    if (saved) {
        try {
            const { type, dur, intensity } = JSON.parse(saved);
            if (type)      setTypeByName(type);
            if (dur)       setDurationValue(parseInt(dur));
            if (intensity) setIntensityByName(intensity);
        } catch(e) {}
    }
    document.getElementById('actModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    updatePreview();
}
function closeModal() {
    document.getElementById('actModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('actModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

/* ── Quick-repeat last activity ───────────────────────────── */
function repeatLastActivity() {
    const btn = document.querySelector('.act-repeat-chip');
    if (!btn) return;
    setTypeByName(btn.dataset.name);
    setDurationValue(parseInt(btn.dataset.duration));
    setIntensityByName(btn.dataset.intensity);
    btn.classList.add('act-repeat-chip--used');
    setTimeout(() => btn.classList.remove('act-repeat-chip--used'), 600);
    updatePreview();
}

/* ── Type selection ───────────────────────────────────────── */
function selectType(name, btn) {
    document.querySelectorAll('.act-type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    selectedType = name;
    const cg = document.getElementById('customNameGroup');
    if (name === 'Other') {
        cg.style.display = 'block';
        document.getElementById('activityNameHidden').value = '';
        document.getElementById('customNameInput').focus();
    } else {
        cg.style.display = 'none';
        document.getElementById('activityNameHidden').value = name;
    }
    saveLastToStorage();
    updatePreview();
}
function setTypeByName(name) {
    const btn = [...document.querySelectorAll('.act-type-btn')]
        .find(b => b.textContent.trim() === name);
    if (btn) {
        selectType(name, btn);
    } else {
        // custom activity — select "Other" and fill input
        const otherBtn = [...document.querySelectorAll('.act-type-btn')]
            .find(b => b.textContent.trim() === 'Other');
        if (otherBtn) {
            selectType('Other', otherBtn);
            document.getElementById('customNameInput').value = name;
            document.getElementById('activityNameHidden').value = name;
        }
    }
}
document.getElementById('customNameInput').addEventListener('input', function() {
    document.getElementById('activityNameHidden').value = this.value;
    document.getElementById('previewActivity').textContent = this.value || 'Custom';
});

/* ── Duration quick presets ───────────────────────────────── */
function setPreset(val, btn) {
    document.querySelectorAll('.act-preset-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    setDurationValue(val);
}
function setDurationValue(val) {
    duration = Math.max(5, Math.min(240, val));
    document.getElementById('durationDisplay').textContent = duration;
    document.getElementById('durationHidden').value        = duration;
    // sync preset pills
    document.querySelectorAll('.act-preset-pill').forEach(b => {
        b.classList.toggle('active', parseInt(b.textContent) === duration);
    });
    saveLastToStorage();
    updatePreview();
}
function stepDuration(delta) {
    setDurationValue(duration + delta);
}

/* ── Intensity selection ──────────────────────────────────── */
const intensityColors = { Light: '#0f7a45', Moderate: '#d97706', Intense: '#dc2626' };
function selectIntensity(val, btn) {
    document.querySelectorAll('.act-intensity-btn').forEach(b => b.classList.remove('sel-light','sel-moderate','sel-intense'));
    btn.classList.add('sel-' + val.toLowerCase());
    selectedIntensity = val;
    document.getElementById('intensityHidden').value = val;
    saveLastToStorage();
    updatePreview();
}
function setIntensityByName(name) {
    const btn = [...document.querySelectorAll('.act-intensity-btn')]
        .find(b => b.textContent.trim() === name);
    if (btn) selectIntensity(name, btn);
}

/* ── sessionStorage persistence ──────────────────────────── */
function saveLastToStorage() {
    sessionStorage.setItem('act_last', JSON.stringify({
        type: selectedType, dur: duration, intensity: selectedIntensity
    }));
}

/* ── Live preview ─────────────────────────────────────────── */
function updatePreview() {
    const estCals    = estimateCals(duration, selectedIntensity);
    const glucoseDrop = (estCals * 0.05).toFixed(1);
    // Show precision indicator
    const precisionNote = WEIGHT_KG
        ? '' : ' <span style="font-size:9px;opacity:0.6;">(rough est.)</span>';
    const displayName = selectedType === 'Other'
        ? (document.getElementById('customNameInput').value || 'Custom')
        : selectedType;
    document.getElementById('previewActivity').textContent    = displayName;
    document.getElementById('previewDuration').textContent    = duration + ' min';
    document.getElementById('previewIntensity').textContent   = selectedIntensity;
    document.getElementById('previewIntensity').style.color   = intensityColors[selectedIntensity];
    document.getElementById('previewCals').innerHTML = estCals + (WEIGHT_KG ? '' : '<span class="act-preview-est-sup"> ~</span>');
    document.getElementById('previewGlucoseDrop').textContent = '~' + glucoseDrop + ' mg/dL';
}
updatePreview();

/* ── Week modal ───────────────────────────────────────────── */
let weekBarsAnimated = false;
function openWeekModal() {
    document.getElementById('weekModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    if (!weekBarsAnimated) {
        weekBarsAnimated = true;
        document.querySelectorAll('#weekModal .act-week-bar-fill[data-target]').forEach(b => {
            setTimeout(() => { b.style.height = b.dataset.target; }, 180);
        });
    }
}
function closeWeekModal() {
    document.getElementById('weekModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('weekModal').addEventListener('click', function(e) { if (e.target === this) closeWeekModal(); });

/* ── Trends modal ─────────────────────────────────────────── */
let chartBuilt = false;
function openTrendsModal() {
    document.getElementById('trendsModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    if (!chartBuilt && window._actChartConfig) {
        chartBuilt = true;
        setTimeout(() => {
            const canvas = document.getElementById('actChart');
            if (canvas) new Chart(canvas.getContext('2d'), window._actChartConfig);
        }, 120);
    }
}
function closeTrendsModal() {
    document.getElementById('trendsModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('trendsModal').addEventListener('click', function(e) { if (e.target === this) closeTrendsModal(); });

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal(); closeDrawer(); closeWeekModal(); closeTrendsModal(); }
});

/* ── Drawer ───────────────────────────────────────────────── */
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
function openDrawerAndScrollTo(date) {
    openDrawer();
    setTimeout(() => {
        const group = document.querySelector(`.act-drawer-group[data-date="${date}"]`);
        if (group) group.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 320);
}
function filterDrawer() {
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), activeFilter);
}
function setFilter(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('.act-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), filter);
}
function applyFilters(q, filter) {
    let count = 0;
    document.querySelectorAll('.act-drawer-item').forEach(item => {
        const ok = (filter === 'all' || item.dataset.intensity === filter) && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) count++;
    });
    document.querySelectorAll('.act-drawer-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.act-drawer-item')].some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = count === 0 ? 'flex' : 'none';
}

/* ── Toasts ───────────────────────────────────────────────── */
function showToast(id, dur = 4000) {
    const t = document.getElementById(id);
    t.classList.add('show');
    if (dur) setTimeout(() => t.classList.remove('show'), dur);
}
function hideToast(id) { document.getElementById(id).classList.remove('show'); }

<?php if ($flashSaved): ?>
window.addEventListener('DOMContentLoaded', () => showToast('saveToast'));
<?php endif; ?>

/* ── Delete with undo ─────────────────────────────────────── */
let deleteTimer = null, pendingDelete = null;
function confirmDelete(btn) {
    const id  = btn.dataset.id;
    const val = btn.dataset.val;
    const row = btn.closest('.act-feed-card') || btn.closest('.act-drawer-item');
    if (row) row.classList.add('act-item-deleting');
    const mirrorFeed   = document.querySelector(`.act-feed-card [data-id="${id}"]`)?.closest('.act-feed-card');
    const mirrorDrawer = document.querySelector(`.act-drawer-item [data-id="${id}"]`)?.closest('.act-drawer-item');
    if (mirrorFeed   && mirrorFeed   !== row) mirrorFeed.classList.add('act-item-deleting');
    if (mirrorDrawer && mirrorDrawer !== row) mirrorDrawer.classList.add('act-item-deleting');
    document.getElementById('toastUndo').style.display = '';
    document.getElementById('toastDeleteMsg').textContent = `"${val}" removed`;
    showToast('deleteToast', 0);
    pendingDelete = { id, rows: [row, mirrorFeed, mirrorDrawer].filter(Boolean) };
    clearTimeout(deleteTimer);
    deleteTimer = setTimeout(() => {
        window.location.href = '/diabetrack/public/patient/activity?delete=' + id + '&_token=' + CSRF + '&deleted=1';
    }, 5000);
}
document.getElementById('toastUndo').addEventListener('click', () => {
    clearTimeout(deleteTimer);
    pendingDelete?.rows.forEach(r => r.classList.remove('act-item-deleting'));
    pendingDelete = null;
    hideToast('deleteToast');
});
document.getElementById('toastClose').addEventListener('click', () => {
    if (pendingDelete) {
        window.location.href = '/diabetrack/public/patient/activity?delete=' + pendingDelete.id + '&_token=' + CSRF + '&deleted=1';
        pendingDelete = null;
    }
    hideToast('deleteToast');
    clearTimeout(deleteTimer);
});

<?php if ($flashDeleted): ?>
window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('toastDeleteMsg').textContent = 'Activity deleted successfully';
    document.getElementById('toastUndo').style.display = 'none';
    showToast('deleteToast');
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>