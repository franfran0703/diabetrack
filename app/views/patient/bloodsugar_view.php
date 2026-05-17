<?php
$pageTitle  = 'Blood Sugar Logger';
$activeMenu = 'bloodsugar';

ob_start();

// ── Chart data (last 7) ───────────────────────────────────
$chartLabels = [];
$chartData   = [];
$chartColors = [];
foreach ($last7 as $log) {
    $chartLabels[] = date('M d, h:i A', strtotime($log['logged_at']));
    $chartData[]   = (float) $log['reading'];
    $r = (float) $log['reading'];
    $chartColors[] = $r < 70 ? '#f59e0b' : ($r > 180 ? '#ef4444' : '#22c55e');
}

// ── Computed stats ────────────────────────────────────────
$totalLogs     = count($logs);
$abnormalCount = count(array_filter($logs, fn($l) => $l['status'] !== 'Normal'));
$normalCount   = $totalLogs - $abnormalCount;

$avg7 = null;
if (!empty($last7)) {
    $avg7 = round(array_sum(array_column($last7, 'reading')) / count($last7));
}
$readings7 = array_column($last7, 'reading');
$min7 = !empty($readings7) ? min($readings7) : null;
$max7 = !empty($readings7) ? max($readings7) : null;

// Today logging status
$loggedToday = false;
$todayCount  = 0;
foreach ($logs as $l) {
    if (date('Y-m-d', strtotime($l['logged_at'])) === date('Y-m-d')) {
        $todayCount++;
        $loggedToday = true;
    }
}

// Latest reading context
$latestStatus = $latest['status'] ?? null;
$latestVal    = $latest['reading'] ?? null;
$latestTime   = $latest ? date('h:i A', strtotime($latest['logged_at'])) : null;
$latestDate   = $latest ? date('M d', strtotime($latest['logged_at'])) : null;
$isToday      = $latest && date('Y-m-d', strtotime($latest['logged_at'])) === date('Y-m-d');

// Trend
$trendIcon = 'ti-minus'; $trendLabel = 'Stable'; $trendClass = 'trend-stable';
if (count($last7) >= 2) {
    $prev = (float)$last7[count($last7)-2]['reading'];
    $curr = (float)$last7[count($last7)-1]['reading'];
    if ($curr > $prev + 5)  { $trendIcon = 'ti-trending-up';   $trendLabel = 'Rising';  $trendClass = 'trend-up'; }
    if ($curr < $prev - 5)  { $trendIcon = 'ti-trending-down'; $trendLabel = 'Falling'; $trendClass = 'trend-down'; }
}

// ── Week heatmap (last 7 days) ────────────────────────────
// Build a map: date => [readings]
$readingsByDate = [];
foreach ($logs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $readingsByDate[$d][] = $l;
}
$weekDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date    = date('Y-m-d', strtotime("-{$i} days"));
    $dayLogs = $readingsByDate[$date] ?? [];
    $hasHigh = !empty(array_filter($dayLogs, fn($l) => $l['status'] === 'High'));
    $hasLow  = !empty(array_filter($dayLogs, fn($l) => $l['status'] === 'Low'));
    $weekDays[] = [
        'date'    => $date,
        'label'   => date('D', strtotime($date)),
        'num'     => date('j',  strtotime($date)),
        'logs'    => $dayLogs,
        'count'   => count($dayLogs),
        'state'   => empty($dayLogs) ? 'none' :
                     ($hasHigh ? 'high' : ($hasLow ? 'low' : 'normal')),
        'isToday' => $date === date('Y-m-d'),
        'avg'     => !empty($dayLogs) ? round(array_sum(array_column($dayLogs, 'reading')) / count($dayLogs)) : null,
    ];
}

// ── Group all logs by date for history drawer ─────────────
$logsByDate = [];
foreach ($logs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
// Sort dates descending
krsort($logsByDate);

// ── Type breakdown for last 7 ─────────────────────────────
$typeCounts = [];
foreach ($last7 as $l) {
    $t = $l['reading_type'] ?? 'Other';
    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
}

// ── Flash detection via query string (deleted only — save uses sessionStorage) ──
$flashDeleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';?>

<link href="/diabetrack/public/assets/css/bloodsugar.css?<?= time() ?>" rel="stylesheet">

<!-- ══ PAGE HEADER — no Log button ══════════════════════ -->
<div class="bs-page-header">
    <div class="bs-page-header-left">
        <div class="bs-page-eyebrow">
            <i class="ti ti-droplet-half-2"></i> Blood Sugar Logger
        </div>
        <h1 class="bs-page-title">Glucose <span>Tracker</span></h1>
        <p class="bs-page-sub">Monitor your blood glucose levels and spot trends over time.</p>
    </div>
    <div class="bs-page-header-right">
        <div class="bs-today-badge <?= $loggedToday ? 'logged' : 'not-logged' ?>">
            <i class="ti <?= $loggedToday ? 'ti-circle-check' : 'ti-clock' ?>"></i>
            <?= $loggedToday
                ? $todayCount . ' reading' . ($todayCount > 1 ? 's' : '') . ' logged today'
                : 'No reading logged today' ?>
        </div>
        <?php if (!empty($logs)): ?>
        <button class="bs-history-btn" onclick="openHistoryDrawer()" aria-label="View all readings">
            <i class="ti ti-history"></i> All Readings
            <span class="bs-history-count"><?= $totalLogs ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ══ STAT CARDS ════════════════════════════════════════ -->
<div class="bs-stats-row">
    <!-- Latest Reading -->
    <div class="bs-scard bs-scard-primary">
        <div class="bs-scard-top">
            <div class="bs-scard-icon-wrap"><i class="ti ti-droplet-half-2"></i></div>
            <div class="bs-scard-trend <?= $trendClass ?>">
                <i class="ti <?= $trendIcon ?>"></i> <?= $trendLabel ?>
            </div>
        </div>
        <div class="bs-scard-val"><?= $latestVal ? $latestVal . '<small>mg/dL</small>' : '—' ?></div>
        <div class="bs-scard-label">Latest Reading</div>
        <div class="bs-scard-meta">
            <?php if ($latestVal): ?>
            <span class="bs-pill <?= $latestStatus === 'High' ? 'pill-danger' : ($latestStatus === 'Low' ? 'pill-warn' : 'pill-good') ?>">
                <i class="ti <?= $latestStatus === 'High' ? 'ti-alert-triangle' : ($latestStatus === 'Low' ? 'ti-alert-circle' : 'ti-circle-check') ?>"></i>
                <?= $latestStatus ?>
            </span>
            <span class="bs-scard-time">
                <i class="ti ti-clock"></i>
                <?= $isToday ? 'Today, ' . $latestTime : $latestDate . ', ' . $latestTime ?>
            </span>
            <?php else: ?>
            <span class="bs-pill pill-neutral"><i class="ti ti-minus"></i> No logs yet</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- 7-Day Average -->
    <div class="bs-scard bs-scard-secondary">
        <div class="bs-scard-top">
            <div class="bs-scard-icon-wrap secondary"><i class="ti ti-chart-line"></i></div>
        </div>
        <div class="bs-scard-val"><?= $avg7 ? $avg7 . '<small>mg/dL</small>' : '—' ?></div>
        <div class="bs-scard-label">7-Day Average</div>
        <div class="bs-scard-meta">
            <?php if ($avg7):
                $avgStatus = $avg7 < 70 ? 'Low' : ($avg7 > 180 ? 'High' : 'Normal'); ?>
            <span class="bs-pill <?= $avgStatus === 'High' ? 'pill-danger' : ($avgStatus === 'Low' ? 'pill-warn' : 'pill-good') ?>">
                <i class="ti ti-chart-bar"></i> Avg <?= $avgStatus ?>
            </span>
            <span class="bs-scard-range">
                <i class="ti ti-arrows-vertical"></i> <?= $min7 ?>–<?= $max7 ?> range
            </span>
            <?php else: ?>
            <span class="bs-pill pill-neutral"><i class="ti ti-minus"></i> Not enough data</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Total Readings -->
    <div class="bs-scard bs-scard-tertiary">
        <div class="bs-scard-top">
            <div class="bs-scard-icon-wrap tertiary"><i class="ti ti-clipboard-list"></i></div>
        </div>
        <div class="bs-scard-val"><?= $totalLogs ?></div>
        <div class="bs-scard-label">Total Readings</div>
        <div class="bs-scard-meta">
            <?php if ($totalLogs > 0): ?>
            <span class="bs-pill pill-good"><i class="ti ti-circle-check"></i> <?= $normalCount ?> normal</span>
            <?php if ($abnormalCount > 0): ?>
            <span class="bs-pill pill-warn"><i class="ti ti-alert-triangle"></i> <?= $abnormalCount ?> abnormal</span>
            <?php endif; ?>
            <?php else: ?>
            <span class="bs-pill pill-neutral"><i class="ti ti-minus"></i> No data yet</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ WEEK HEATMAP STRIP ════════════════════════════════ -->
<div class="bs-heatmap-card">
    <div class="bs-heatmap-label">
        <i class="ti ti-calendar-week"></i> This Week's Overview
    </div>
    <div class="bs-heatmap-strip">
        <?php foreach ($weekDays as $day): ?>
        <div class="bs-heatmap-day state-<?= $day['state'] ?> <?= $day['isToday'] ? 'is-today' : '' ?>"
             <?= $day['count'] > 0 ? 'onclick="filterHistoryByDate(\'' . $day['date'] . '\')"' : '' ?>
             title="<?= $day['count'] > 0 ? $day['count'] . ' reading' . ($day['count'] > 1 ? 's' : '') . ($day['avg'] ? ' · avg ' . $day['avg'] . ' mg/dL' : '') : 'No readings' ?>"
             role="<?= $day['count'] > 0 ? 'button' : 'presentation' ?>">
            <div class="bs-heatmap-day-name"><?= $day['label'] ?></div>
            <div class="bs-heatmap-day-num"><?= $day['num'] ?></div>
            <div class="bs-heatmap-day-dot"></div>
            <?php if ($day['count'] > 0): ?>
            <div class="bs-heatmap-day-count"><?= $day['count'] ?></div>
            <?php if ($day['avg']): ?>
            <div class="bs-heatmap-day-avg"><?= $day['avg'] ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="bs-heatmap-day-empty">—</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="bs-heatmap-legend">
        <span class="bs-heatmap-leg none"></span>No data
        <span class="bs-heatmap-leg normal" style="margin-left:12px;"></span>Normal
        <span class="bs-heatmap-leg high"   style="margin-left:12px;"></span>Has High
        <span class="bs-heatmap-leg low"    style="margin-left:12px;"></span>Has Low
    </div>
</div>

<!-- ══ CHART + LOG PANEL ════════════════════════════════ -->
<div class="bs-main-grid">

    <!-- Chart Card -->
    <div class="bs-chart-card">
        <div class="bs-chart-card-header">
            <div class="bs-section-label">
                <i class="ti ti-chart-line"></i> 7-Day Trend
            </div>
            <?php if (!empty($typeCounts)): ?>
            <div class="bs-type-breakdown">
                <?php foreach ($typeCounts as $type => $cnt):
                    $typeIcon = match($type) {
                        'Before Meal' => 'ti-soup',
                        'After Meal'  => 'ti-bowl-spoon',
                        'Fasting'     => 'ti-moon',
                        'Bedtime'     => 'ti-bed',
                        default       => 'ti-tag',
                    };
                ?>
                <div class="bs-type-chip">
                    <i class="ti <?= $typeIcon ?>"></i> <?= htmlspecialchars($type) ?> <strong>(<?= $cnt ?>)</strong>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($last7)): ?>
        <div class="bs-chart-legend">
            <div class="bs-legend-item"><span class="bs-legend-dot" style="background:#f59e0b;"></span> Low &lt;70</div>
            <div class="bs-legend-item"><span class="bs-legend-dot" style="background:#22c55e;"></span> Normal 70–180</div>
            <div class="bs-legend-item"><span class="bs-legend-dot" style="background:#ef4444;"></span> High &gt;180</div>
        </div>
        <div class="bs-chart-wrap">
            <canvas id="sugarChart"></canvas>
        </div>
        <?php else: ?>
        <div class="bs-empty">
            <i class="ti ti-chart-line" style="font-size:3rem;color:rgba(249,116,71,0.3);"></i>
            <p>No readings yet — your trend chart will appear here.</p>
            <p class="bs-empty-hint">Tap the <strong>+</strong> button to log your first reading.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Log Panel — always visible on desktop, slide-in on mobile -->
    <div class="bs-log-panel" id="logPanel">
        <div class="bs-log-panel-header">
            <div class="bs-section-label" style="margin-bottom:0;">
                <i class="ti ti-pencil"></i> Log New Reading
            </div>
            <!-- Close only shown on mobile (position:fixed overlay mode) -->
            <button class="bs-log-panel-close" id="logPanelClose" onclick="closeLogPanel()" aria-label="Close panel" style="display:none;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <form method="POST" action="/diabetrack/public/patient/bloodsugar" id="bs-form">

            <!-- Read-only display — no editable input, slider is the control -->
            <div class="bs-dial-wrap" id="dialWrap">
                <div class="bs-dial-inner">
                    <div class="bs-dial-number" id="dialNum" aria-live="polite" aria-label="Current value">120</div>
                    <div class="bs-dial-unit">mg/dL</div>
                </div>
                <div class="bs-dial-status" id="dialStatus">
                    <i class="ti ti-circle-check" id="dialStatusIcon"></i>
                    <span id="dialStatusText">Normal</span>
                </div>
            </div>

            <div class="bs-slider-wrap">
                <input type="range" class="bs-glucose-range" min="40" max="400" value="120" id="glucoseSlider"
                       aria-label="Adjust blood sugar value">
                <!-- Ticks positioned at mathematically correct % offsets:
                     40  → 0%      (40-40)/360*100
                     70  → 8.33%   (70-40)/360*100
                     180 → 38.89%  (180-40)/360*100
                     400 → 100%    (400-40)/360*100  -->
                <div class="bs-range-ticks">
                    <span class="bs-tick" style="left:0%;">40</span>
                    <span class="bs-tick" style="left:8.33%;">70</span>
                    <span class="bs-tick bs-tick-normal" style="left:23.5%;">Normal</span>
                    <span class="bs-tick" style="left:38.89%;">180</span>
                    <span class="bs-tick" style="left:100%;transform:translateX(-100%);">400</span>
                </div>
            </div>

            <input type="hidden" name="reading" id="readingInput" value="120">

            <div class="bs-form-label"><i class="ti ti-tag"></i> Reading Type</div>
            <div class="bs-type-grid">
                <button type="button" class="bs-type-btn selected" data-value="Before Meal">
                    <i class="ti ti-soup"></i> Before Meal
                </button>
                <button type="button" class="bs-type-btn" data-value="After Meal">
                    <i class="ti ti-bowl-spoon"></i> After Meal
                </button>
                <button type="button" class="bs-type-btn" data-value="Fasting">
                    <i class="ti ti-moon"></i> Fasting
                </button>
                <button type="button" class="bs-type-btn" data-value="Bedtime">
                    <i class="ti ti-bed"></i> Bedtime
                </button>
            </div>
            <input type="hidden" name="reading_type" id="readingTypeInput" value="Before Meal">

            <div class="bs-form-label" style="margin-top:14px;">
                <i class="ti ti-notes"></i> Notes
                <span class="bs-label-optional">(optional)</span>
            </div>
            <textarea name="notes" class="bs-notes" rows="2"
                      placeholder="e.g. felt dizzy, had a large meal..."></textarea>

            <button type="submit" class="bs-save-btn">
                <i class="ti ti-device-floppy"></i> Save Reading
            </button>

            <div class="bs-confirm-preview">
                Saving: <strong id="previewVal">120 mg/dL</strong> ·
                <strong id="previewType">Before Meal</strong> ·
                <span id="previewStatus" style="color:#22c55e;">Normal</span>
            </div>

        </form>
    </div>

</div><!-- /.bs-main-grid -->


<!-- ══════════════════════════════════════════════════════
     LOG MODAL — opened by FAB, centred overlay
     Same slider form, separate IDs (modal- prefix)
     ══════════════════════════════════════════════════════ -->
<div class="bs-modal-overlay" id="logModal" onclick="overlayCloseModal(event)" aria-modal="true" role="dialog" aria-label="Log New Reading">
    <div class="bs-modal">

        <div class="bs-modal-header">
            <div class="bs-modal-header-left">
                <div class="bs-modal-icon"><i class="ti ti-droplet-half-2"></i></div>
                <div>
                    <div class="bs-modal-title">Log New Reading</div>
                    <div class="bs-modal-sub">Use the slider to set your glucose level</div>
                </div>
            </div>
            <button class="bs-modal-close" onclick="closeLogModal()" aria-label="Close">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="bs-modal-body">
            <form method="POST" action="/diabetrack/public/patient/bloodsugar" id="bs-modal-form">

                <!-- Read-only display -->
                <div class="bs-dial-wrap" id="mDialWrap">
                    <div class="bs-dial-inner">
                        <div class="bs-dial-number" id="mDialNum" aria-live="polite">120</div>
                        <div class="bs-dial-unit">mg/dL</div>
                    </div>
                    <div class="bs-dial-status" id="mDialStatus">
                        <i class="ti ti-circle-check" id="mDialStatusIcon"></i>
                        <span id="mDialStatusText">Normal</span>
                    </div>
                </div>

                <div class="bs-slider-wrap">
                    <input type="range" class="bs-glucose-range" min="40" max="400" value="120"
                           id="mGlucoseSlider" aria-label="Adjust blood sugar value">
                    <div class="bs-range-ticks">
                        <span class="bs-tick" style="left:0%;">40</span>
                        <span class="bs-tick" style="left:8.33%;">70</span>
                        <span class="bs-tick bs-tick-normal" style="left:23.5%;">Normal</span>
                        <span class="bs-tick" style="left:38.89%;">180</span>
                        <span class="bs-tick" style="left:100%;transform:translateX(-100%);">400</span>
                    </div>
                </div>

                <input type="hidden" name="reading" id="mReadingInput" value="120">

                <div class="bs-form-label"><i class="ti ti-tag"></i> Reading Type</div>
                <div class="bs-type-grid">
                    <button type="button" class="bs-type-btn selected" data-modal-value="Before Meal">
                        <i class="ti ti-soup"></i> Before Meal
                    </button>
                    <button type="button" class="bs-type-btn" data-modal-value="After Meal">
                        <i class="ti ti-bowl-spoon"></i> After Meal
                    </button>
                    <button type="button" class="bs-type-btn" data-modal-value="Fasting">
                        <i class="ti ti-moon"></i> Fasting
                    </button>
                    <button type="button" class="bs-type-btn" data-modal-value="Bedtime">
                        <i class="ti ti-bed"></i> Bedtime
                    </button>
                </div>
                <input type="hidden" name="reading_type" id="mReadingTypeInput" value="Before Meal">

                <div class="bs-form-label" style="margin-top:14px;">
                    <i class="ti ti-notes"></i> Notes
                    <span class="bs-label-optional">(optional)</span>
                </div>
                <textarea name="notes" class="bs-notes" rows="2"
                          placeholder="e.g. felt dizzy, had a large meal..."></textarea>

            </form>
        </div>

        <div class="bs-modal-footer">
            <button type="button" class="bs-modal-cancel" onclick="closeLogModal()">Cancel</button>
            <button type="submit" form="bs-modal-form" class="bs-save-btn bs-modal-save-btn" id="mSaveBtn">
                <i class="ti ti-device-floppy"></i> Save Reading
            </button>
        </div>

    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     HISTORY DRAWER — slides in from the right
     Full reading timeline, grouped by date
     ══════════════════════════════════════════════════════ -->
<div class="bs-drawer-overlay" id="drawerOverlay" onclick="closeHistoryDrawer()"></div>
<div class="bs-drawer" id="historyDrawer" role="dialog" aria-label="All Readings" aria-modal="true">

    <!-- Drawer header -->
    <div class="bs-drawer-header">
        <div class="bs-drawer-header-left">
            <div class="bs-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="bs-drawer-title">All Readings</div>
                <div class="bs-drawer-sub"><?= $totalLogs ?> total · <?= $normalCount ?> normal · <?= $abnormalCount ?> abnormal</div>
            </div>
        </div>
        <button class="bs-drawer-close" onclick="closeHistoryDrawer()" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <!-- Drawer search + filter -->
    <div class="bs-drawer-controls">
        <div class="bs-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search by value, type, notes…" oninput="filterDrawer()">
        </div>
        <div class="bs-drawer-filters">
            <button class="bs-drawer-filter active" data-df="all"    onclick="setDrawerFilter('all',    this)">All</button>
            <button class="bs-drawer-filter"         data-df="Normal" onclick="setDrawerFilter('Normal', this)">
                <span class="bs-df-dot normal"></span> Normal
            </button>
            <button class="bs-drawer-filter"         data-df="High"   onclick="setDrawerFilter('High',   this)">
                <span class="bs-df-dot high"></span> High
            </button>
            <button class="bs-drawer-filter"         data-df="Low"    onclick="setDrawerFilter('Low',    this)">
                <span class="bs-df-dot low"></span> Low
            </button>
        </div>
    </div>

    <!-- Timeline -->
    <div class="bs-drawer-body" id="drawerBody">
        <?php if (empty($logs)): ?>
        <div class="bs-drawer-empty">
            <i class="ti ti-droplet-half-2"></i>
            <p>No readings yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayLogs):
            $dayAvg   = round(array_sum(array_column($dayLogs, 'reading')) / count($dayLogs));
            $dayHigh  = count(array_filter($dayLogs, fn($l) => $l['status'] === 'High'));
            $dayLow   = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Low'));
            $isDateToday = $date === date('Y-m-d');
            $dateLabel   = $isDateToday ? 'Today' : ($date === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('l, M j', strtotime($date)));
        ?>
        <div class="bs-drawer-day-group" data-date="<?= $date ?>">
            <!-- Date header -->
            <div class="bs-drawer-day-header">
                <div class="bs-drawer-day-label">
                    <?= $dateLabel ?>
                    <?php if ($isDateToday): ?>
                    <span class="bs-today-chip">Today</span>
                    <?php endif; ?>
                </div>
                <div class="bs-drawer-day-stats">
                    <span><?= count($dayLogs) ?> reading<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <span class="bs-drawer-day-avg">avg <?= $dayAvg ?> mg/dL</span>
                    <?php if ($dayHigh > 0): ?>
                    <span class="bs-drawer-day-flag high"><?= $dayHigh ?> high</span>
                    <?php endif; ?>
                    <?php if ($dayLow > 0): ?>
                    <span class="bs-drawer-day-flag low"><?= $dayLow ?> low</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Readings in this day -->
            <div class="bs-timeline">
                <?php foreach ($dayLogs as $log):
                    $r          = (float)$log['reading'];
                    $statusCls  = $log['status'] === 'High' ? 'high' : ($log['status'] === 'Low' ? 'low' : 'normal');
                    $statusIcon = $log['status'] === 'High' ? 'ti-alert-triangle' : ($log['status'] === 'Low' ? 'ti-alert-circle' : 'ti-circle-check');
                    $typeIcon   = match($log['reading_type'] ?? '') {
                        'Before Meal' => 'ti-soup',
                        'After Meal'  => 'ti-bowl-spoon',
                        'Fasting'     => 'ti-moon',
                        'Bedtime'     => 'ti-bed',
                        default       => 'ti-tag',
                    };
                    $barPct = min(round(($r / 400) * 100), 100);
                ?>
                <div class="bs-timeline-item"
                     data-status="<?= $log['status'] ?>"
                     data-search="<?= strtolower($log['reading'] . ' ' . $log['reading_type'] . ' ' . ($log['notes'] ?? '')) ?>">

                    <!-- Timeline spine -->
                    <div class="bs-timeline-spine">
                        <div class="bs-timeline-dot <?= $statusCls ?>">
                            <i class="ti <?= $statusIcon ?>"></i>
                        </div>
                        <div class="bs-timeline-line"></div>
                    </div>

                    <!-- Reading card -->
                    <div class="bs-timeline-card">
                        <div class="bs-timeline-card-top">
                            <div class="bs-timeline-val">
                                <span class="bs-tl-num"><?= $log['reading'] ?></span>
                                <span class="bs-tl-unit">mg/dL</span>
                            </div>
                            <div class="bs-timeline-card-right">
                                <span class="bs-tl-time">
                                    <i class="ti ti-clock"></i>
                                    <?= date('h:i A', strtotime($log['logged_at'])) ?>
                                </span>
                                <button class="bs-tl-del"
                                        data-id="<?= $log['id'] ?>"
                                        data-val="<?= $log['reading'] ?>"
                                        onclick="confirmDelete(this)"
                                        aria-label="Delete reading">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Mini bar -->
                        <div class="bs-tl-bar-track">
                            <div class="bs-tl-bar-fill <?= $statusCls ?>" style="width:<?= $barPct ?>%"></div>
                            <div class="bs-tl-bar-line" style="left:17.5%"></div>
                            <div class="bs-tl-bar-line" style="left:45%"></div>
                        </div>

                        <!-- Meta -->
                        <div class="bs-tl-meta">
                            <span class="bs-tl-type">
                                <i class="ti <?= $typeIcon ?>"></i>
                                <?= htmlspecialchars($log['reading_type'] ?? 'Unknown') ?>
                            </span>
                            <span class="bs-tl-status <?= $statusCls ?>">
                                <?= $log['status'] ?>
                            </span>
                            <?php if (!empty($log['notes'])): ?>
                            <span class="bs-tl-note">
                                <i class="ti ti-notes"></i>
                                <?= htmlspecialchars($log['notes']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /.bs-timeline-item -->
                <?php endforeach; ?>
            </div><!-- /.bs-timeline -->
        </div><!-- /.bs-drawer-day-group -->
        <?php endforeach; ?>
        <?php endif; ?>
    </div><!-- /.bs-drawer-body -->

    <!-- Drawer footer -->
    <div class="bs-drawer-footer">
        <div class="bs-drawer-no-results" id="drawerNoResults" style="display:none;">
            <i class="ti ti-search-off"></i> No readings match your search.
        </div>
        <div class="bs-drawer-footer-stats">
            <span><i class="ti ti-circle-check" style="color:#22c55e;"></i> <?= $normalCount ?> Normal</span>
            <span><i class="ti ti-alert-triangle" style="color:#ef4444;"></i> <?= count(array_filter($logs, fn($l) => $l['status'] === 'High')) ?> High</span>
            <span><i class="ti ti-alert-circle" style="color:#f59e0b;"></i> <?= count(array_filter($logs, fn($l) => $l['status'] === 'Low')) ?> Low</span>
        </div>
    </div>

</div><!-- /.bs-drawer -->


<!-- ══ TOASTS ════════════════════════════════════════════ -->
<!-- Save toast -->
<div class="bs-toast bs-toast-success" id="saveToast" aria-live="polite">
    <i class="ti ti-circle-check"></i>
    <span>Reading saved successfully</span>
    <button class="bs-toast-close" onclick="hideToast('saveToast')" aria-label="Dismiss">
        <i class="ti ti-x"></i>
    </button>
</div>

<!-- Delete toast (undo) -->
<div class="bs-toast" id="deleteToast" aria-live="polite">
    <i class="ti ti-trash"></i>
    <span id="toastMsg">Reading deleted</span>
    <button class="bs-toast-undo" id="toastUndo">Undo</button>
    <button class="bs-toast-close" id="toastClose" aria-label="Dismiss">
        <i class="ti ti-x"></i>
    </button>
</div>

<!-- ══ FAB — opens log modal ══════════════════════════════ -->
<button class="patient-fab" onclick="openLogModal()" aria-label="Log new reading">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Log Reading</span>
</button>


<!-- ══ CHART JS ══════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if (!empty($last7)): ?>
<script>
(function() {
    const ctx = document.getElementById('sugarChart').getContext('2d');
    const thresholdPlugin = {
        id: 'thresholds',
        beforeDraw(chart) {
            const { ctx, chartArea: { left, right, top, bottom }, scales: { y } } = chart;
            if (!y) return;
            const y70  = y.getPixelForValue(70);
            const y180 = y.getPixelForValue(180);
            ctx.save();
            ctx.fillStyle = 'rgba(245,158,11,0.06)';
            ctx.fillRect(left, y70, right - left, bottom - y70);
            ctx.fillStyle = 'rgba(34,197,94,0.05)';
            ctx.fillRect(left, y180, right - left, y70 - y180);
            ctx.fillStyle = 'rgba(239,68,68,0.06)';
            ctx.fillRect(left, top, right - left, y180 - top);
            ctx.restore();
            ctx.save();
            ctx.setLineDash([5, 4]);
            ctx.lineWidth = 1.5;
            ctx.strokeStyle = 'rgba(245,158,11,0.55)';
            ctx.beginPath(); ctx.moveTo(left, y70); ctx.lineTo(right, y70); ctx.stroke();
            ctx.strokeStyle = 'rgba(239,68,68,0.55)';
            ctx.beginPath(); ctx.moveTo(left, y180); ctx.lineTo(right, y180); ctx.stroke();
            ctx.restore();
        }
    };
    new Chart(ctx, {
        type: 'line',
        plugins: [thresholdPlugin],
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Blood Sugar (mg/dL)',
                data: <?= json_encode($chartData) ?>,
                borderColor: '#F97447',
                backgroundColor: 'rgba(249,116,71,0.07)',
                borderWidth: 2.5,
                pointBackgroundColor: <?= json_encode($chartColors) ?>,
                pointBorderColor: '#fff',
                pointBorderWidth: 2.5,
                pointRadius: 6,
                pointHoverRadius: 9,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a0800',
                    titleColor: '#fbab6e',
                    bodyColor: '#fff8f5',
                    borderColor: 'rgba(249,116,71,0.2)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 12,
                    callbacks: {
                        label: ctx => {
                            const v = ctx.parsed.y;
                            const s = v < 70 ? 'Low' : (v > 180 ? 'High' : 'Normal');
                            return ` ${v} mg/dL — ${s}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(249,116,71,0.07)' },
                    ticks: { font: { size: 11, family: 'DM Sans' }, color: '#b8927e' },
                    border: { color: 'transparent' },
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, family: 'DM Sans' }, color: '#b8927e', maxRotation: 20 },
                    border: { color: 'transparent' },
                }
            }
        }
    });
})();
</script>
<?php endif; ?>

<!-- ══ INTERACTIVITY ══════════════════════════════════════ -->
<script>
// ── Zone logic ───────────────────────────────────────────
function getZone(v) {
    if (v < 70)   return { zone:'low',    label:'Low',    icon:'ti-alert-circle',   color:'#f59e0b' };
    if (v <= 180) return { zone:'normal', label:'Normal', icon:'ti-circle-check',   color:'#22c55e' };
                  return { zone:'high',   label:'High',   icon:'ti-alert-triangle', color:'#ef4444' };
}

// ── Toast helpers ────────────────────────────────────────
function showToast(id, duration = 4500) {
    const t = document.getElementById(id);
    if (!t) return;
    t.classList.add('show');
    if (duration > 0) setTimeout(() => t.classList.remove('show'), duration);
}
function hideToast(id) {
    const t = document.getElementById(id);
    if (t) t.classList.remove('show');
}

// ── sessionStorage save toast ────────────────────────────
// Plant flag before either form navigates away, read on next load.
function plantSaveFlag(formId) {
    const val  = document.getElementById(
        formId === 'bs-form' ? 'readingInput' : 'mReadingInput'
    ).value;
    const type = document.getElementById(
        formId === 'bs-form' ? 'readingTypeInput' : 'mReadingTypeInput'
    ).value;
    const { label } = getZone(parseInt(val));
    sessionStorage.setItem('bs_saved_msg', `${val} mg/dL · ${type} · ${label}`);
}
document.getElementById('bs-form').addEventListener('submit',       () => plantSaveFlag('bs-form'));
document.getElementById('bs-modal-form').addEventListener('submit', () => plantSaveFlag('bs-modal-form'));

document.addEventListener('DOMContentLoaded', () => {
    const msg = sessionStorage.getItem('bs_saved_msg');
    if (msg) {
        sessionStorage.removeItem('bs_saved_msg');
        const span = document.querySelector('#saveToast span');
        if (span) span.textContent = `Saved: ${msg}`;
        showToast('saveToast');
    }

    <?php if ($flashDeleted): ?>
    document.getElementById('toastMsg').textContent = 'Reading deleted successfully';
    document.getElementById('toastUndo').style.display = 'none';
    showToast('deleteToast');
    <?php endif; ?>
});

// ══ SIDE PANEL (desktop sticky, mobile overlay) ══════════

// ── Panel slider sync — dialNum is now a DIV display ─────
const slider     = document.getElementById('glucoseSlider');
const dialNumDiv = document.getElementById('dialNum');        // display div
const dialWrap   = document.getElementById('dialWrap');
const statusIcon = document.getElementById('dialStatusIcon');
const statusText = document.getElementById('dialStatusText');
const readingHid = document.getElementById('readingInput');
const previewVal = document.getElementById('previewVal');
const previewSts = document.getElementById('previewStatus');

function syncPanel(v) {
    v = Math.max(40, Math.min(400, parseInt(v) || 120));
    const { zone, label, icon, color } = getZone(v);
    dialNumDiv.textContent = v;          // update the display div
    slider.value           = v;
    readingHid.value       = v;
    previewVal.textContent = v + ' mg/dL';
    previewSts.textContent = label;
    previewSts.style.color = color;
    dialWrap.dataset.zone  = zone;
    statusText.textContent = label;
    statusIcon.className   = 'ti ' + icon;
    slider.style.setProperty('--thumb-color', color);
}
slider.addEventListener('input', () => syncPanel(slider.value));
syncPanel(120);

// ── Panel type buttons ───────────────────────────────────
const typeInput   = document.getElementById('readingTypeInput');
const previewType = document.getElementById('previewType');
document.querySelectorAll('.bs-type-btn[data-value]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.bs-type-btn[data-value]').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        typeInput.value         = btn.dataset.value;
        previewType.textContent = btn.dataset.value;
    });
});

// ── Panel open/close (mobile only) ──────────────────────
function openLogPanel() {
    if (window.innerWidth <= 1024) {
        document.getElementById('logPanel').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}
function closeLogPanel() {
    if (window.innerWidth <= 1024) {
        document.getElementById('logPanel').classList.remove('open');
        document.body.style.overflow = '';
    }
}

// ══ LOG MODAL (opened by FAB) ════════════════════════════

const mSlider      = document.getElementById('mGlucoseSlider');
const mDialNumDiv  = document.getElementById('mDialNum');
const mDialWrap    = document.getElementById('mDialWrap');
const mStatusIcon  = document.getElementById('mDialStatusIcon');
const mStatusText  = document.getElementById('mDialStatusText');
const mReadingHid  = document.getElementById('mReadingInput');

function syncModal(v) {
    v = Math.max(40, Math.min(400, parseInt(v) || 120));
    const { zone, label, icon, color } = getZone(v);
    mDialNumDiv.textContent = v;
    mSlider.value           = v;
    mReadingHid.value       = v;
    mDialWrap.dataset.zone  = zone;
    mStatusText.textContent = label;
    mStatusIcon.className   = 'ti ' + icon;
    mSlider.style.setProperty('--thumb-color', color);
}
mSlider.addEventListener('input', () => syncModal(mSlider.value));

// Modal type buttons (data-modal-value attribute)
document.querySelectorAll('.bs-type-btn[data-modal-value]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.bs-type-btn[data-modal-value]').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('mReadingTypeInput').value = btn.dataset.modalValue;
    });
});

function openLogModal() {
    // Reset to 120 each open
    syncModal(120);
    document.querySelectorAll('.bs-type-btn[data-modal-value]').forEach(b => b.classList.remove('selected'));
    const first = document.querySelector('.bs-type-btn[data-modal-value="Before Meal"]');
    if (first) first.classList.add('selected');
    document.getElementById('mReadingTypeInput').value = 'Before Meal';

    document.getElementById('logModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLogModal() {
    document.getElementById('logModal').classList.remove('open');
    document.body.style.overflow = '';
}
function overlayCloseModal(e) {
    if (e.target === document.getElementById('logModal')) closeLogModal();
}

// ── History drawer ───────────────────────────────────────
let drawerFilterActive = 'all';

function openHistoryDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeHistoryDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function filterHistoryByDate(date) {
    openHistoryDrawer();
    setTimeout(() => {
        const group = document.querySelector(`.bs-drawer-day-group[data-date="${date}"]`);
        if (group) group.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 320);
}
function filterDrawer() {
    applyDrawerFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), drawerFilterActive);
}
function setDrawerFilter(filter, btn) {
    drawerFilterActive = filter;
    document.querySelectorAll('.bs-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyDrawerFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), filter);
}
function applyDrawerFilters(q, filter) {
    let visible = 0;
    document.querySelectorAll('.bs-timeline-item').forEach(item => {
        const ok = (filter === 'all' || item.dataset.status === filter)
                && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.bs-drawer-day-group').forEach(group => {
        group.style.display = [...group.querySelectorAll('.bs-timeline-item')]
            .some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = visible === 0 ? 'flex' : 'none';
}

// ── Delete with undo toast ───────────────────────────────
let deleteTimer   = null;
let pendingDelete = null;

function confirmDelete(btn) {
    const id   = btn.dataset.id;
    const val  = btn.dataset.val;
    const item = btn.closest('.bs-timeline-item');
    item.classList.add('bs-item-deleting');
    document.getElementById('toastUndo').style.display = '';
    document.getElementById('toastMsg').textContent = `${val} mg/dL reading removed`;
    showToast('deleteToast', 0);
    pendingDelete = { id, item };
    clearTimeout(deleteTimer);
    deleteTimer = setTimeout(() => {
        window.location.href = '/diabetrack/public/patient/bloodsugar?delete=' + id + '&deleted=1';
    }, 5000);
}
document.getElementById('toastUndo').addEventListener('click', () => {
    clearTimeout(deleteTimer);
    if (pendingDelete) { pendingDelete.item.classList.remove('bs-item-deleting'); pendingDelete = null; }
    hideToast('deleteToast');
});
document.getElementById('toastClose').addEventListener('click', () => {
    if (pendingDelete) {
        window.location.href = '/diabetrack/public/patient/bloodsugar?delete=' + pendingDelete.id + '&deleted=1';
        pendingDelete = null;
    }
    hideToast('deleteToast');
    clearTimeout(deleteTimer);
});

// ── Global keyboard handler ──────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeLogModal();
        closeLogPanel();
        closeHistoryDrawer();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>