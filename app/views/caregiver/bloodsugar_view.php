<?php
$pageTitle  = 'Blood Sugar Monitor';
$activeMenu = 'bloodsugar';
ob_start();

// ── Chart data ────────────────────────────────────────
$chartLabels = [];
$chartData   = [];
$chartColors = [];
foreach ($last7 as $log) {
    $chartLabels[] = date('M d, h:i A', strtotime($log['logged_at']));
    $chartData[]   = (float) $log['reading'];
    $chartColors[] = $log['status'] === 'High' ? '#f87171'
                  : ($log['status'] === 'Low'  ? '#fbbf24' : '#4ade80');
}

// ── Computed stats ────────────────────────────────────
$normalRate   = $stats['total'] > 0
    ? round(($stats['normal'] / $stats['total']) * 100) : 0;
$latestStatus = $latest ? strtolower($latest['status']) : 'none';

// ── Trend ─────────────────────────────────────────────
$trendIcon  = 'ti-minus';
$trendLabel = 'Stable';
$trendClass = 'stable';
if (count($last7) >= 2) {
    $prev = (float) $last7[count($last7)-2]['reading'];
    $curr = (float) $last7[count($last7)-1]['reading'];
    if ($curr > $prev + 5)  { $trendIcon = 'ti-trending-up';   $trendLabel = 'Rising';  $trendClass = 'up'; }
    if ($curr < $prev - 5)  { $trendIcon = 'ti-trending-down'; $trendLabel = 'Falling'; $trendClass = 'down'; }
}

// ── Last reading freshness ────────────────────────────
$freshnessAgo = '';
if (!empty($latest['logged_at'])) {
    $diff = time() - strtotime($latest['logged_at']);
    if ($diff < 3600)      $freshnessAgo = round($diff / 60) . 'm ago';
    elseif ($diff < 86400) $freshnessAgo = round($diff / 3600) . 'h ago';
    else                   $freshnessAgo = round($diff / 86400) . 'd ago';
}

// ── Average of last 7 ─────────────────────────────────
$avg7 = null;
$min7 = null;
$max7 = null;
if (!empty($last7)) {
    $vals = array_column($last7, 'reading');
    $avg7 = round(array_sum($vals) / count($vals));
    $min7 = min($vals);
    $max7 = max($vals);
}

// ── Group logs by date for drawer ────────────────────
$logsByDate = [];
foreach ($logs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);

// ── Is urgent ─────────────────────────────────────────
$isUrgent = in_array($latest['status'] ?? '', ['High', 'Low']);
?>

<link href="<?= BASE_URL ?>/assets/css/caregiver_bloodsugar.css?v=<?= time() ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- ══ PAGE HEADER ═══════════════════════════════════ -->
<div class="cgbs-header">
    <div class="cgbs-header-left">
        <div class="cgbs-eyebrow">
            <i class="ti ti-droplet-half-2"></i> Live Monitor
        </div>
        <h1 class="cgbs-title">Blood Sugar <span>Monitor</span></h1>
        <p class="cgbs-sub">Real-time glucose tracking for your linked patient.</p>
    </div>
    <?php if ($patient): ?>
    <div class="cgbs-header-right">
        <?php if ($isUrgent): ?>
        <div class="cgbs-urgent-chip">
            <i class="ti ti-alert-triangle"></i>
            <?= $latest['status'] === 'High' ? 'High' : 'Low' ?> Alert — <?= $latest['reading'] ?> mg/dL
        </div>
        <?php endif; ?>
        <div class="cgbs-patient-chip">
            <div class="cgbs-patient-avatar">
                <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                <span class="cgbs-patient-dot"></span>
            </div>
            <div>
                <div class="cgbs-patient-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
                <div class="cgbs-patient-label">Linked Patient · <?= $stats['total'] ?> total readings</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<!-- ══ NO PATIENT ════════════════════════════════════ -->
<div class="cgbs-no-patient">
    <div class="cgbs-no-patient-icon"><i class="ti ti-link"></i></div>
    <div class="cgbs-no-patient-title">No Patient Linked</div>
    <div class="cgbs-no-patient-sub">Link a patient to start monitoring their glucose levels.</div>
    <a href="<?= BASE_URL ?>/caregiver/patients" class="cgbs-action-btn">
        <i class="ti ti-user-plus"></i> Link a Patient
    </a>
</div>

<?php else: ?>

<!-- ══ STAT CARDS ════════════════════════════════════ -->
<div class="cgbs-stat-row">

    <!-- Latest Reading — primary orange gradient -->
    <div class="cgbs-stat-card card-primary <?= $isUrgent ? 'card-urgent' : '' ?>">
        <div class="cgbs-stat-card-top">
            <div class="cgbs-stat-icon-wrap">
                <i class="ti ti-droplet-half-2"></i>
            </div>
            <div class="cgbs-trend-badge trend-<?= $trendClass ?>">
                <i class="ti <?= $trendIcon ?>"></i> <?= $trendLabel ?>
            </div>
        </div>
        <div class="cgbs-stat-val">
            <?= $latest ? $latest['reading'] . '<small>mg/dL</small>' : '—' ?>
        </div>
        <div class="cgbs-stat-label">Latest Reading</div>
        <div class="cgbs-stat-meta">
            <span class="cgbs-status-pill <?= $latestStatus ?>">
                <i class="ti <?= $latestStatus === 'high' ? 'ti-alert-triangle'
                               : ($latestStatus === 'low'  ? 'ti-alert-circle'
                               : 'ti-circle-check') ?>"></i>
                <?= $latest ? $latest['status'] : 'No data' ?>
            </span>
            <?php if ($freshnessAgo): ?>
            <span class="cgbs-freshness">
                <i class="ti ti-clock"></i> <?= $freshnessAgo ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- 7-Day Average -->
    <div class="cgbs-stat-card card-glass">
        <div class="cgbs-stat-card-top">
            <div class="cgbs-stat-icon-wrap glass"><i class="ti ti-chart-line"></i></div>
        </div>
        <div class="cgbs-stat-val dark"><?= $avg7 ? $avg7 . '<small>mg/dL</small>' : '—' ?></div>
        <div class="cgbs-stat-label dark">7-Day Average</div>
        <?php if ($avg7): ?>
        <div class="cgbs-stat-range">
            <i class="ti ti-arrows-vertical"></i> <?= $min7 ?>–<?= $max7 ?> mg/dL range
        </div>
        <?php endif; ?>
    </div>

    <!-- High Readings -->
    <div class="cgbs-stat-card card-danger">
        <div class="cgbs-stat-card-top">
            <div class="cgbs-stat-icon-wrap danger"><i class="ti ti-alert-triangle"></i></div>
        </div>
        <div class="cgbs-stat-val danger"><?= $stats['high'] ?></div>
        <div class="cgbs-stat-label danger">High Readings</div>
        <div class="cgbs-stat-sub danger">
            <i class="ti ti-arrow-up-right"></i> Above 180 mg/dL
        </div>
    </div>

    <!-- Low Readings -->
    <div class="cgbs-stat-card card-warn">
        <div class="cgbs-stat-card-top">
            <div class="cgbs-stat-icon-wrap warn"><i class="ti ti-alert-circle"></i></div>
        </div>
        <div class="cgbs-stat-val warn"><?= $stats['low'] ?></div>
        <div class="cgbs-stat-label warn">Low Readings</div>
        <div class="cgbs-stat-sub warn">
            <i class="ti ti-arrow-down-right"></i> Below 70 mg/dL
        </div>
    </div>

    <!-- Normal Rate -->
    <div class="cgbs-stat-card card-normal">
        <div class="cgbs-stat-card-top">
            <div class="cgbs-stat-icon-wrap normal"><i class="ti ti-circle-check"></i></div>
        </div>
        <div class="cgbs-stat-val normal"><?= $normalRate ?><small>%</small></div>
        <div class="cgbs-stat-label normal">Normal Rate</div>
        <div class="cgbs-normal-bar-track">
            <div class="cgbs-normal-bar-fill" style="width:<?= $normalRate ?>%;"></div>
        </div>
        <div class="cgbs-stat-sub normal"><?= $stats['normal'] ?> of <?= $stats['total'] ?> readings</div>
    </div>

</div>

<!-- ══ CHART + HISTORY ══════════════════════════════ -->
<div class="cgbs-main-grid">

    <!-- Chart card -->
    <div class="cgbs-chart-card">
        <div class="cgbs-chart-card-header">
            <div>
                <div class="cgbs-section-eyebrow">Last 7 Readings</div>
                <div class="cgbs-section-title">Glucose Trend</div>
            </div>
            <div class="cgbs-chart-legend">
                <span><span class="cgbs-legend-dot" style="background:#4ade80;"></span> Normal</span>
                <span><span class="cgbs-legend-dot" style="background:#f87171;"></span> High</span>
                <span><span class="cgbs-legend-dot" style="background:#fbbf24;"></span> Low</span>
            </div>
        </div>

        <?php if (!empty($last7)): ?>
        <!-- Reading type breakdown -->
        <?php
        $typeCounts = [];
        foreach ($last7 as $l) {
            $t = $l['reading_type'] ?? 'Other';
            $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
        }
        $typeIcons = [
            'Before Meal' => 'ti-soup',
            'After Meal'  => 'ti-bowl-spoon',
            'Fasting'     => 'ti-moon',
            'Bedtime'     => 'ti-bed',
        ];
        ?>
        <div class="cgbs-type-chips">
            <?php foreach ($typeCounts as $type => $cnt): ?>
            <span class="cgbs-type-chip">
                <i class="ti <?= $typeIcons[$type] ?? 'ti-tag' ?>"></i>
                <?= htmlspecialchars($type) ?> (<?= $cnt ?>)
            </span>
            <?php endforeach; ?>
        </div>
        <div class="cgbs-chart-wrap">
            <canvas id="sugarChart"></canvas>
        </div>
        <?php else: ?>
        <div class="cgbs-empty-chart">
            <i class="ti ti-chart-line"></i>
            <p>No readings yet — chart will appear here.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- History drawer trigger + summary -->
    <div class="cgbs-summary-card">
        <div class="cgbs-section-eyebrow">All Time</div>
        <div class="cgbs-section-title" style="margin-bottom:20px;">Summary</div>

        <!-- Donut — pure SVG -->
        <?php
        $total = max($stats['total'], 1);
        $nPct  = round($stats['normal'] / $total * 100);
        $hPct  = round($stats['high']   / $total * 100);
        $lPct  = round($stats['low']    / $total * 100);
        $svgR  = 36; $svgC = round(2 * M_PI * $svgR, 2);
        // Segments: normal (green), high (red), low (amber)
        $nDash = round($svgC * $nPct / 100, 2);
        $hDash = round($svgC * $hPct / 100, 2);
        $lDash = round($svgC * $lPct / 100, 2);
        $nOff  = 0;
        $hOff  = -$nDash;
        $lOff  = -($nDash + $hDash);
        ?>
        <div class="cgbs-donut-wrap">
            <svg viewBox="0 0 80 80" width="120" height="120" style="transform:rotate(-90deg);">
                <circle cx="40" cy="40" r="<?= $svgR ?>" fill="none"
                        stroke="rgba(255,255,255,0.05)" stroke-width="10"/>
                <?php if ($nPct > 0): ?>
                <circle cx="40" cy="40" r="<?= $svgR ?>" fill="none"
                        stroke="#4ade80" stroke-width="10" stroke-linecap="butt"
                        stroke-dasharray="<?= $nDash ?> <?= $svgC ?>"
                        stroke-dashoffset="<?= $nOff ?>"/>
                <?php endif; ?>
                <?php if ($hPct > 0): ?>
                <circle cx="40" cy="40" r="<?= $svgR ?>" fill="none"
                        stroke="#f87171" stroke-width="10" stroke-linecap="butt"
                        stroke-dasharray="<?= $hDash ?> <?= $svgC ?>"
                        stroke-dashoffset="<?= $hOff ?>"/>
                <?php endif; ?>
                <?php if ($lPct > 0): ?>
                <circle cx="40" cy="40" r="<?= $svgR ?>" fill="none"
                        stroke="#fbbf24" stroke-width="10" stroke-linecap="butt"
                        stroke-dasharray="<?= $lDash ?> <?= $svgC ?>"
                        stroke-dashoffset="<?= $lOff ?>"/>
                <?php endif; ?>
            </svg>
            <div class="cgbs-donut-center">
                <div class="cgbs-donut-val"><?= $stats['total'] ?></div>
                <div class="cgbs-donut-label">Total</div>
            </div>
        </div>

        <div class="cgbs-summary-rows">
            <div class="cgbs-summary-row">
                <span class="cgbs-sum-dot" style="background:#4ade80;"></span>
                <span class="cgbs-sum-key">Normal</span>
                <span class="cgbs-sum-val"><?= $stats['normal'] ?></span>
                <span class="cgbs-sum-pct"><?= $nPct ?>%</span>
            </div>
            <div class="cgbs-summary-row">
                <span class="cgbs-sum-dot" style="background:#f87171;"></span>
                <span class="cgbs-sum-key">High</span>
                <span class="cgbs-sum-val"><?= $stats['high'] ?></span>
                <span class="cgbs-sum-pct"><?= $hPct ?>%</span>
            </div>
            <div class="cgbs-summary-row">
                <span class="cgbs-sum-dot" style="background:#fbbf24;"></span>
                <span class="cgbs-sum-key">Low</span>
                <span class="cgbs-sum-val"><?= $stats['low'] ?></span>
                <span class="cgbs-sum-pct"><?= $lPct ?>%</span>
            </div>
        </div>

        <?php if (!empty($logs)): ?>
        <button class="cgbs-history-btn" onclick="openDrawer()">
            <i class="ti ti-history"></i> All Readings
            <span class="cgbs-history-count"><?= count($logs) ?></span>
        </button>
        <?php endif; ?>
    </div>

</div><!-- /.cgbs-main-grid -->


<!-- ══ HISTORY DRAWER ════════════════════════════════ -->
<div class="cgbs-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="cgbs-drawer" id="historyDrawer" role="dialog" aria-modal="true" aria-label="All Readings">

    <div class="cgbs-drawer-header">
        <div class="cgbs-drawer-header-left">
            <div class="cgbs-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="cgbs-drawer-title">All Readings</div>
                <div class="cgbs-drawer-sub">
                    <?= count($logs) ?> total · <?= $stats['normal'] ?> normal · <?= $stats['high'] ?> high · <?= $stats['low'] ?> low
                </div>
            </div>
        </div>
        <button class="cgbs-drawer-close" onclick="closeDrawer()" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <div class="cgbs-drawer-controls">
        <div class="cgbs-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search by value, type, notes…" oninput="filterDrawer()">
        </div>
        <div class="cgbs-drawer-filters">
            <button class="cgbs-drawer-filter active" data-df="all"    onclick="setDrawerFilter('all',    this)">All</button>
            <button class="cgbs-drawer-filter"         data-df="Normal" onclick="setDrawerFilter('Normal', this)">
                <span class="cgbs-df-dot" style="background:#4ade80;"></span> Normal
            </button>
            <button class="cgbs-drawer-filter"         data-df="High"   onclick="setDrawerFilter('High',   this)">
                <span class="cgbs-df-dot" style="background:#f87171;"></span> High
            </button>
            <button class="cgbs-drawer-filter"         data-df="Low"    onclick="setDrawerFilter('Low',    this)">
                <span class="cgbs-df-dot" style="background:#fbbf24;"></span> Low
            </button>
        </div>
    </div>

    <div class="cgbs-drawer-body" id="drawerBody">
        <?php if (empty($logs)): ?>
        <div class="cgbs-drawer-empty">
            <i class="ti ti-droplet-half-2"></i>
            <p>No readings logged yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayLogs):
            $isToday = $date === date('Y-m-d');
            $isYest  = $date === date('Y-m-d', strtotime('-1 day'));
            $dlabel  = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('l, M j', strtotime($date)));
            $dayAvg  = round(array_sum(array_column($dayLogs, 'reading')) / count($dayLogs));
            $dayHigh = count(array_filter($dayLogs, fn($l) => $l['status'] === 'High'));
            $dayLow  = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Low'));
        ?>
        <div class="cgbs-drawer-day-group" data-date="<?= $date ?>">
            <div class="cgbs-drawer-day-header">
                <div class="cgbs-drawer-day-label">
                    <?= $dlabel ?>
                    <?php if ($isToday): ?><span class="cgbs-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="cgbs-drawer-day-stats">
                    <span><?= count($dayLogs) ?> reading<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <span class="cgbs-day-avg">avg <?= $dayAvg ?> mg/dL</span>
                    <?php if ($dayHigh): ?><span class="cgbs-day-flag high"><?= $dayHigh ?> high</span><?php endif; ?>
                    <?php if ($dayLow):  ?><span class="cgbs-day-flag low"><?= $dayLow ?>  low</span><?php endif; ?>
                </div>
            </div>

            <div class="cgbs-timeline">
                <?php foreach ($dayLogs as $log):
                    $r          = (float) $log['reading'];
                    $sCls       = $log['status'] === 'High' ? 'high' : ($log['status'] === 'Low' ? 'low' : 'normal');
                    $sIcon      = $log['status'] === 'High' ? 'ti-alert-triangle'
                                : ($log['status'] === 'Low'  ? 'ti-alert-circle' : 'ti-circle-check');
                    $typeIcon   = match($log['reading_type'] ?? '') {
                        'Before Meal' => 'ti-soup',
                        'After Meal'  => 'ti-bowl-spoon',
                        'Fasting'     => 'ti-moon',
                        'Bedtime'     => 'ti-bed',
                        default       => 'ti-tag',
                    };
                    $barPct = min(round(($r / 400) * 100), 100);
                ?>
                <div class="cgbs-tl-item"
                     data-status="<?= $log['status'] ?>"
                     data-search="<?= strtolower($log['reading'] . ' ' . $log['reading_type'] . ' ' . ($log['notes'] ?? '')) ?>">

                    <div class="cgbs-tl-spine">
                        <div class="cgbs-tl-dot <?= $sCls ?>">
                            <i class="ti <?= $sIcon ?>"></i>
                        </div>
                        <div class="cgbs-tl-line"></div>
                    </div>

                    <div class="cgbs-tl-card">
                        <div class="cgbs-tl-card-top">
                            <div class="cgbs-tl-val">
                                <span class="cgbs-tl-num"><?= $log['reading'] ?></span>
                                <span class="cgbs-tl-unit">mg/dL</span>
                            </div>
                            <div class="cgbs-tl-right">
                                <span class="cgbs-tl-time">
                                    <i class="ti ti-clock"></i>
                                    <?= date('h:i A', strtotime($log['logged_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="cgbs-tl-bar-track">
                            <div class="cgbs-tl-bar-fill <?= $sCls ?>" style="width:<?= $barPct ?>%;"></div>
                            <div class="cgbs-tl-bar-line" style="left:17.5%;" title="70 mg/dL"></div>
                            <div class="cgbs-tl-bar-line" style="left:45%;"   title="180 mg/dL"></div>
                        </div>
                        <div class="cgbs-tl-meta">
                            <span class="cgbs-tl-type">
                                <i class="ti <?= $typeIcon ?>"></i>
                                <?= htmlspecialchars($log['reading_type'] ?? 'Unknown') ?>
                            </span>
                            <span class="cgbs-tl-status <?= $sCls ?>"><?= $log['status'] ?></span>
                            <?php if (!empty($log['notes'])): ?>
                            <span class="cgbs-tl-note">
                                <i class="ti ti-notes"></i>
                                <?= htmlspecialchars(mb_strimwidth($log['notes'], 0, 36, '…')) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /.cgbs-tl-item -->
                <?php endforeach; ?>
            </div>

        </div><!-- /.cgbs-drawer-day-group -->
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cgbs-drawer-footer">
        <div class="cgbs-drawer-no-results" id="drawerNoResults" style="display:none;">
            <i class="ti ti-search-off"></i> No readings match your search.
        </div>
        <div class="cgbs-drawer-footer-stats">
            <span><i class="ti ti-circle-check" style="color:#4ade80;"></i> <?= $stats['normal'] ?> Normal</span>
            <span><i class="ti ti-alert-triangle" style="color:#f87171;"></i> <?= $stats['high'] ?> High</span>
            <span><i class="ti ti-alert-circle"   style="color:#fbbf24;"></i> <?= $stats['low'] ?> Low</span>
        </div>
    </div>

</div><!-- /.cgbs-drawer -->

<?php endif; ?>


<!-- ══ CHART JS ══════════════════════════════════════ -->
<?php if (!empty($last7)): ?>
<script>
(function() {
    const ctx = document.getElementById('sugarChart')?.getContext('2d');
    if (!ctx) return;

    const thresholdPlugin = {
        id: 'cgbsThresholds',
        beforeDraw(chart) {
            const { ctx, chartArea: { left, right, top, bottom }, scales: { y } } = chart;
            if (!y) return;
            const y70  = y.getPixelForValue(70);
            const y180 = y.getPixelForValue(180);
            ctx.save();
            ctx.fillStyle = 'rgba(251,191,36,0.055)';
            ctx.fillRect(left, y70, right - left, bottom - y70);
            ctx.fillStyle = 'rgba(74,222,128,0.04)';
            ctx.fillRect(left, y180, right - left, y70 - y180);
            ctx.fillStyle = 'rgba(248,113,113,0.055)';
            ctx.fillRect(left, top, right - left, y180 - top);
            ctx.setLineDash([5, 4]);
            ctx.lineWidth = 1.2;
            ctx.strokeStyle = 'rgba(251,191,36,0.35)';
            ctx.beginPath(); ctx.moveTo(left, y70);  ctx.lineTo(right, y70);  ctx.stroke();
            ctx.strokeStyle = 'rgba(248,113,113,0.35)';
            ctx.beginPath(); ctx.moveTo(left, y180); ctx.lineTo(right, y180); ctx.stroke();
            ctx.restore();
        }
    };

    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(249,116,71,0.25)');
    grad.addColorStop(1, 'rgba(249,116,71,0)');

    new Chart(ctx, {
        type: 'line',
        plugins: [thresholdPlugin],
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                data: <?= json_encode($chartData) ?>,
                borderColor: '#f97447',
                backgroundColor: grad,
                borderWidth: 2.5,
                pointBackgroundColor: <?= json_encode($chartColors) ?>,
                pointBorderColor: '#1c0f0a',
                pointBorderWidth: 2,
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
                    backgroundColor: '#2a0e00',
                    borderColor: 'rgba(249,116,71,0.3)',
                    borderWidth: 1,
                    titleColor: '#fbab6e',
                    bodyColor: '#ffe8d6',
                    padding: 12,
                    cornerRadius: 12,
                    callbacks: {
                        label: c => {
                            const v = c.parsed.y;
                            const s = v < 70 ? ' · Low' : (v > 180 ? ' · High' : ' · Normal');
                            return ` ${v} mg/dL${s}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    suggestedMin: 50, suggestedMax: 300,
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(255,200,160,0.35)', font: { size: 10, family: 'DM Sans' } },
                    border: { color: 'transparent' },
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,200,160,0.35)', font: { size: 10, family: 'DM Sans' }, maxRotation: 20 },
                    border: { color: 'transparent' },
                }
            }
        }
    });
})();
</script>
<?php endif; ?>

<!-- ══ DRAWER SCRIPT ════════════════════════════════ -->
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
function setDrawerFilter(f, btn) {
    activeFilter = f;
    document.querySelectorAll('.cgbs-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), f);
}
function applyFilters(q, f) {
    let visible = 0;
    document.querySelectorAll('.cgbs-tl-item').forEach(item => {
        const ok = (f === 'all' || item.dataset.status === f)
                && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.cgbs-drawer-day-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.cgbs-tl-item')]
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