<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
ob_start();

$hour         = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');
$firstName    = ucfirst(strtolower(explode(' ', trim($name))[0]));

// ── Health score (safe default before patient block) ───
$score      = 100;
$scoreColor = '#4ade80';

// ── Chart data ─────────────────────────────────────────
$sparkLabels = [];
$sparkValues = [];
$sparkColors = [];
foreach (($sparkline ?? []) as $s) {
    $sparkLabels[] = date('M d h:i A', strtotime($s['logged_at']));
    $sparkValues[] = (float) $s['reading'];
    $sparkColors[] = $s['status'] === 'High' ? '#f87171'
                  : ($s['status'] === 'Low'  ? '#fbbf24' : '#4ade80');
}

// ── Patient-context values ─────────────────────────────
if ($patient ?? null) {
    $score -= min(40, ($abnormalReadings ?? 0) * 10);
    $score -= min(30, ($missedMeds       ?? 0) * 10);
    if (($unreadAlerts ?? 0) > 2) $score -= 10;
    $score      = max(0, $score);
    $scoreColor = $score >= 80 ? '#4ade80' : ($score >= 50 ? '#fbbf24' : '#f87171');
}

// ── Alert urgency ──────────────────────────────────────
$isUrgent       = ($latestSugar['status'] ?? '') === 'High'
               || ($latestSugar['status'] ?? '') === 'Low';
$urgentMsg      = '';
if (($latestSugar['status'] ?? '') === 'High') {
    $urgentMsg = 'High blood sugar: ' . ($latestSugar['reading'] ?? '') . ' mg/dL — review needed';
} elseif (($latestSugar['status'] ?? '') === 'Low') {
    $urgentMsg = 'Low blood sugar: ' . ($latestSugar['reading'] ?? '') . ' mg/dL — act now';
}

// ── Last reading freshness ─────────────────────────────
$lastReadingAgo = '';
if (!empty($latestSugar['logged_at'])) {
    $diff = time() - strtotime($latestSugar['logged_at']);
    if ($diff < 3600)      $lastReadingAgo = round($diff / 60) . 'm ago';
    elseif ($diff < 86400) $lastReadingAgo = round($diff / 3600) . 'h ago';
    else                   $lastReadingAgo = round($diff / 86400) . 'd ago';
}

// ── Timeline: merge meals + activity + alerts ──────────
$timeline = [];
foreach (($recentMeals ?? []) as $m) {
    $timeline[] = [
        'type'  => 'meal',
        'icon'  => 'ti-salad',
        'color' => 'green',
        'title' => htmlspecialchars($m['meal_name']),
        'sub'   => $m['meal_type'] . ' · ' . $m['carbs'] . 'g carbs',
        'time'  => $m['logged_at'],
    ];
}
foreach (($recentActivity ?? []) as $a) {
    $timeline[] = [
        'type'  => 'activity',
        'icon'  => 'ti-run',
        'color' => 'blue',
        'title' => htmlspecialchars($a['activity_name']),
        'sub'   => $a['duration_minutes'] . ' min · ' . $a['intensity'],
        'time'  => $a['logged_at'],
    ];
}
foreach (($recentAlerts ?? []) as $al) {
    $c = str_contains($al['type'], 'High') ? 'red'
       : (str_contains($al['type'], 'Low') ? 'yellow' : 'orange');
    $i = str_contains($al['type'], 'High') ? 'ti-alert-triangle'
       : (str_contains($al['type'], 'Low') ? 'ti-alert-circle'  : 'ti-bell');
    $timeline[] = [
        'type'  => 'alert',
        'icon'  => $i,
        'color' => $c,
        'title' => htmlspecialchars($al['type']),
        'sub'   => htmlspecialchars(mb_strimwidth($al['message'], 0, 68, '…')),
        'time'  => $al['created_at'],
    ];
}
usort($timeline, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
$timeline = array_slice($timeline, 0, 8);

// ── Nav items (Tabler icons) ───────────────────────────
$navItems = [
    ['/caregiver/bloodsugar', 'ti-droplet-half-2', 'Blood Sugar',  'Monitor readings',    'rgba(239,68,68,0.15)',    '#f87171'],
    ['/caregiver/medication', 'ti-pill',           'Medication',   'Today\'s schedule',   'rgba(249,116,71,0.15)',   '#fbab6e'],
    ['/caregiver/meals',      'ti-salad',          'Meals',        'Diet & nutrition',    'rgba(34,197,94,0.12)',    '#4ade80'],
    ['/caregiver/patients',   'ti-users',          'My Patients',  'Manage linked',       'rgba(139,92,246,0.12)',   '#c4b5fd'],
    ['/caregiver/alerts',     'ti-bell',           'Alerts',       'Full alert history',  'rgba(234,179,8,0.12)',    '#fbbf24'],
    ['/caregiver/reports',    'ti-file-analytics', 'Reports',      'Generate summaries',  'rgba(14,165,233,0.12)',   '#38bdf8'],
];
?>

<link href="<?= BASE_URL ?>/assets/css/caregiver_dashboard.css?v=<?= time() ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php if ($isUrgent): ?>
<!-- ══ URGENT ALERT BANNER ════════════════════════════ -->
<div class="cgd-urgent-banner">
    <div class="cgd-urgent-left">
        <div class="cgd-urgent-icon">
            <i class="ti ti-alert-triangle"></i>
        </div>
        <div>
            <div class="cgd-urgent-label">Urgent — Patient Alert</div>
            <div class="cgd-urgent-msg"><?= $urgentMsg ?></div>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/caregiver/bloodsugar" class="cgd-urgent-btn">
        <i class="ti ti-arrow-right"></i> View Readings
    </a>
</div>
<?php endif; ?>

<!-- ══ TOP ROW ════════════════════════════════════════ -->
<div class="cgd-top-row">

    <!-- BANNER -->
    <div class="cgd-banner">
        <div class="cgd-banner-orb cgd-orb-1"></div>
        <div class="cgd-banner-orb cgd-orb-2"></div>
        <div class="cgd-banner-content">
            <div class="cgd-eyebrow">
                <span class="cgd-pulse-dot"></span>
                <?= $timeGreeting ?>, Caregiver
            </div>
            <h1 class="cgd-title">
                Welcome back,<br>
                <span class="cgd-gradient-name"><?= htmlspecialchars($firstName) ?></span>
            </h1>
            <p class="cgd-sub">Real-time health monitoring at a glance.</p>
            <div class="cgd-date-pill">
                <i class="ti ti-calendar-event"></i>
                <?= $todayLabel ?>
            </div>
        </div>
        <div class="cgd-banner-logo-wrap">
            <img src="<?= BASE_URL ?>/assets/img/diabetrack-icon.png" class="cgd-banner-logo" alt="DiabeTrack">
            <div class="cgd-banner-logo-ring"></div>
        </div>
    </div>

    <!-- PATIENT IDENTITY CARD -->
    <?php if ($patient ?? null): ?>
    <div class="cgd-identity-card <?= $isUrgent ? 'is-urgent' : '' ?>">
        <div class="cgd-identity-glow"></div>

        <!-- Top: avatar + info + manage -->
        <div class="cgd-identity-top">
            <div class="cgd-identity-avatar">
                <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                <span class="cgd-online-dot"></span>
            </div>
            <div class="cgd-identity-info">
                <div class="cgd-identity-label">Active Patient</div>
                <div class="cgd-identity-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
                <div class="cgd-identity-email"><?= htmlspecialchars($patient['email']) ?></div>
            </div>
            <a href="<?= BASE_URL ?>/caregiver/patients" class="cgd-identity-manage" title="Manage patients">
                <i class="ti ti-settings"></i>
            </a>
        </div>

        <!-- Health score ring — pure SVG, no Chart.js overhead -->
        <?php
        $svgR      = 26;
        $svgCircum = round(2 * M_PI * $svgR, 2);
        $svgDash   = round($svgCircum * ($score / 100), 2);
        ?>
        <div class="cgd-health-score-wrap">
            <div class="cgd-health-ring-container" aria-label="Health score <?= $score ?> out of 100">
                <svg viewBox="0 0 64 64" width="80" height="80" style="transform:rotate(-90deg);">
                    <circle cx="32" cy="32" r="<?= $svgR ?>" fill="none"
                            stroke="rgba(255,255,255,0.07)" stroke-width="6"/>
                    <circle cx="32" cy="32" r="<?= $svgR ?>" fill="none"
                            stroke="<?= $scoreColor ?>" stroke-width="6"
                            stroke-linecap="round"
                            stroke-dasharray="<?= $svgDash ?> <?= $svgCircum ?>"/>
                </svg>
                <div class="cgd-health-ring-label">
                    <span style="color:<?= $scoreColor ?>"><?= $score ?></span>
                    <small>Health<br>Score</small>
                </div>
            </div>
            <div class="cgd-health-score-details">
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#4ade80;"></span>
                    <span class="cgd-score-key">Normal readings</span>
                    <span class="cgd-score-val"><?= max(0, ($totalLogs ?? 0) - ($abnormalReadings ?? 0)) ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#f87171;"></span>
                    <span class="cgd-score-key">Abnormal</span>
                    <span class="cgd-score-val"><?= $abnormalReadings ?? 0 ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#fbbf24;"></span>
                    <span class="cgd-score-key">Missed doses</span>
                    <span class="cgd-score-val"><?= $missedMeds ?? 0 ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#fb923c;"></span>
                    <span class="cgd-score-key">Unread alerts</span>
                    <span class="cgd-score-val"><?= $unreadAlerts ?? 0 ?></span>
                </div>
            </div>
        </div>

        <!-- Latest reading -->
        <?php if ($latestSugar ?? null): ?>
        <div class="cgd-latest-reading <?= $isUrgent ? 'reading-urgent' : '' ?>">
            <div>
                <div class="cgd-reading-eyebrow">
                    Latest Reading <?= $lastReadingAgo ? '· ' . $lastReadingAgo : '' ?>
                </div>
                <div class="cgd-reading-big">
                    <?= $latestSugar['reading'] ?>
                    <span class="cgd-reading-unit">mg/dL</span>
                </div>
                <div class="cgd-reading-time">
                    <?= date('M d · h:i A', strtotime($latestSugar['logged_at'])) ?>
                </div>
            </div>
            <div class="cgd-reading-badge <?= strtolower($latestSugar['status']) ?>">
                <i class="ti <?= $latestSugar['status'] === 'High' ? 'ti-alert-triangle'
                               : ($latestSugar['status'] === 'Low'  ? 'ti-alert-circle'
                               : 'ti-circle-check') ?>"></i>
                <?= $latestSugar['status'] ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- NO PATIENT STATE -->
    <div class="cgd-no-patient">
        <div class="cgd-no-patient-icon">
            <i class="ti ti-link"></i>
        </div>
        <div class="cgd-no-patient-title">No Patient Linked</div>
        <div class="cgd-no-patient-sub">Send a request to start monitoring a patient's health.</div>
        <a href="<?= BASE_URL ?>/caregiver/patients" class="cgd-action-btn">
            <i class="ti ti-user-plus"></i> Link a Patient
        </a>
    </div>
    <?php endif; ?>

</div><!-- /.cgd-top-row -->


<!-- ══ MIDDLE ROW ════════════════════════════════════ -->
<div class="cgd-mid-row">

    <!-- BLOOD SUGAR CHART -->
    <div class="cgd-chart-card">
        <div class="cgd-card-header">
            <div>
                <div class="cgd-card-eyebrow">Last 7 Readings</div>
                <div class="cgd-card-title">Blood Sugar Trend</div>
            </div>
            <a href="<?= BASE_URL ?>/caregiver/bloodsugar" class="cgd-card-link">
                Full Log <i class="ti ti-arrow-right" style="font-size:11px;"></i>
            </a>
        </div>

        <?php if (empty($sparkline ?? [])): ?>
        <div class="cgd-empty-chart">
            <i class="ti ti-chart-line" style="font-size:2rem;opacity:0.18;display:block;margin-bottom:8px;"></i>
            No readings yet
        </div>
        <?php else: ?>
        <!-- Threshold zones legend -->
        <div class="cgd-chart-legend">
            <span><span class="cgd-legend-dot" style="background:#4ade80;"></span> Normal</span>
            <span><span class="cgd-legend-dot" style="background:#f87171;"></span> High</span>
            <span><span class="cgd-legend-dot" style="background:#fbbf24;"></span> Low</span>
        </div>
        <div class="cgd-chart-wrap">
            <canvas id="bsChart"></canvas>
        </div>
        <?php endif; ?>
    </div>

    <!-- MED COMPLIANCE -->
    <div class="cgd-med-card">
        <div class="cgd-card-header">
            <div>
                <div class="cgd-card-eyebrow">This Week</div>
                <div class="cgd-card-title">Medication</div>
            </div>
            <a href="<?= BASE_URL ?>/caregiver/medication" class="cgd-card-link">
                View <i class="ti ti-arrow-right" style="font-size:11px;"></i>
            </a>
        </div>

        <?php
        $rate       = $medRate ?? 0;
        $rateColor  = $rate >= 80 ? '#4ade80' : ($rate >= 50 ? '#fbbf24' : '#f87171');
        $rateGrad   = $rate >= 80 ? 'linear-gradient(90deg,#4ade80,#22c55e)'
                    : ($rate >= 50 ? 'linear-gradient(90deg,#fbbf24,#f59e0b)'
                    : 'linear-gradient(90deg,#f87171,#ef4444)');
        ?>
        <div class="cgd-med-rate-wrap">
            <div class="cgd-med-rate-num" style="color:<?= $rateColor ?>"><?= $rate ?>%</div>
            <div class="cgd-med-rate-label">Compliance Rate</div>
        </div>
        <div class="cgd-med-bar-wrap">
            <div class="cgd-med-bar">
                <div class="cgd-med-bar-fill" style="width:<?= $rate ?>%;background:<?= $rateGrad ?>;"></div>
            </div>
        </div>
        <div class="cgd-med-counts">
            <div class="cgd-med-count taken">
                <span class="cgd-med-count-num"><?= $medTaken ?? 0 ?></span>
                <span class="cgd-med-count-label">Taken</span>
            </div>
            <div class="cgd-med-divider"></div>
            <div class="cgd-med-count missed">
                <span class="cgd-med-count-num"><?= $medMissed ?? 0 ?></span>
                <span class="cgd-med-count-label">Missed</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/caregiver/medication" class="cgd-action-btn" style="margin-top:auto;">
            <i class="ti ti-calendar-check"></i> Check Schedule
        </a>
    </div>

    <!-- STATS COLUMN -->
    <div class="cgd-stats-col">
        <div class="cgd-mini-stat" data-c="blue">
            <div class="cgd-mini-icon"><i class="ti ti-chart-line"></i></div>
            <div>
                <div class="cgd-mini-num"><?= $totalLogs ?? 0 ?></div>
                <div class="cgd-mini-label">Logs This Week</div>
            </div>
        </div>
        <div class="cgd-mini-stat" data-c="red">
            <div class="cgd-mini-icon"><i class="ti ti-alert-triangle"></i></div>
            <div>
                <div class="cgd-mini-num"><?= $abnormalReadings ?? 0 ?></div>
                <div class="cgd-mini-label">Abnormal</div>
            </div>
        </div>
        <div class="cgd-mini-stat <?= ($unreadAlerts ?? 0) > 0 ? 'has-alert' : '' ?>" data-c="yellow">
            <div class="cgd-mini-icon"><i class="ti ti-bell"></i></div>
            <div>
                <div class="cgd-mini-num"><?= $unreadAlerts ?? 0 ?></div>
                <div class="cgd-mini-label">Unread Alerts</div>
            </div>
        </div>
        <div class="cgd-mini-stat <?= ($missedMeds ?? 0) > 0 ? 'has-alert' : '' ?>" data-c="orange">
            <div class="cgd-mini-icon"><i class="ti ti-pill"></i></div>
            <div>
                <div class="cgd-mini-num"><?= $missedMeds ?? 0 ?></div>
                <div class="cgd-mini-label">Missed Today</div>
            </div>
        </div>
    </div>

</div><!-- /.cgd-mid-row -->


<!-- ══ BOTTOM ROW ════════════════════════════════════ -->
<div class="cgd-bot-row">

    <!-- ACTIVITY TIMELINE -->
    <div class="cgd-timeline-card">
        <div class="cgd-card-header" style="margin-bottom:20px;">
            <div>
                <div class="cgd-card-eyebrow">Recent Activity</div>
                <div class="cgd-card-title">Patient Timeline</div>
            </div>
        </div>

        <?php if (empty($timeline)): ?>
        <div class="cgd-empty-state">
            <i class="ti ti-clipboard-list" style="font-size:2.5rem;color:rgba(255,200,160,0.15);display:block;margin-bottom:10px;"></i>
            <div class="cgd-empty-text">No recent activity to show.</div>
        </div>
        <?php else: ?>
        <div class="cgd-timeline">
            <?php foreach ($timeline as $i => $item): ?>
            <div class="cgd-timeline-item">
                <div class="cgd-tl-icon-wrap <?= $item['color'] ?>">
                    <i class="ti <?= $item['icon'] ?>"></i>
                </div>
                <div class="cgd-tl-line <?= $i === count($timeline) - 1 ? 'last' : '' ?>"></div>
                <div class="cgd-tl-body">
                    <div class="cgd-tl-title"><?= $item['title'] ?></div>
                    <div class="cgd-tl-sub"><?= $item['sub'] ?></div>
                </div>
                <div class="cgd-tl-time">
                    <?= date('h:i A', strtotime($item['time'])) ?>
                    <br><span><?= date('M d', strtotime($item['time'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- QUICK NAV -->
    <div class="cgd-quick-nav">
        <div class="cgd-card-eyebrow" style="margin-bottom:14px;">Quick Access</div>
        <?php foreach ($navItems as [$url, $icon, $title, $sub, $bg, $iconColor]): ?>
        <a href="<?= $url ?>" class="cgd-qnav-item">
            <div class="cgd-qnav-icon" style="background:<?= $bg ?>;">
                <i class="ti <?= $icon ?>" style="font-size:17px;color:<?= $iconColor ?>;"></i>
            </div>
            <div>
                <div class="cgd-qnav-title"><?= $title ?></div>
                <div class="cgd-qnav-sub"><?= $sub ?></div>
            </div>
            <i class="ti ti-arrow-right cgd-qnav-arrow"></i>
        </a>
        <?php endforeach; ?>
    </div>

</div><!-- /.cgd-bot-row -->


<!-- ══ SCRIPTS ═══════════════════════════════════════ -->
<script>
// ── BLOOD SUGAR CHART (threshold-zone plugin) ─────────
const bsLabels = <?= json_encode($sparkLabels) ?>;
const bsValues = <?= json_encode($sparkValues) ?>;
const bsColors = <?= json_encode($sparkColors) ?>;

const bctx = document.getElementById('bsChart')?.getContext('2d');
if (bctx && bsValues.length > 0) {

    const thresholdPlugin = {
        id: 'cgdThresholds',
        beforeDraw(chart) {
            const { ctx, chartArea: { left, right, top, bottom }, scales: { y } } = chart;
            if (!y) return;
            const y70  = y.getPixelForValue(70);
            const y180 = y.getPixelForValue(180);
            ctx.save();
            // Low zone
            ctx.fillStyle = 'rgba(251,191,36,0.055)';
            ctx.fillRect(left, y70, right - left, bottom - y70);
            // Normal zone
            ctx.fillStyle = 'rgba(74,222,128,0.04)';
            ctx.fillRect(left, y180, right - left, y70 - y180);
            // High zone
            ctx.fillStyle = 'rgba(248,113,113,0.055)';
            ctx.fillRect(left, top, right - left, y180 - top);
            // Threshold lines
            ctx.setLineDash([5, 4]);
            ctx.lineWidth = 1.2;
            ctx.strokeStyle = 'rgba(251,191,36,0.35)';
            ctx.beginPath(); ctx.moveTo(left, y70); ctx.lineTo(right, y70); ctx.stroke();
            ctx.strokeStyle = 'rgba(248,113,113,0.35)';
            ctx.beginPath(); ctx.moveTo(left, y180); ctx.lineTo(right, y180); ctx.stroke();
            ctx.restore();
        }
    };

    const gradient = bctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, 'rgba(249,116,71,0.22)');
    gradient.addColorStop(1, 'rgba(249,116,71,0)');

    new Chart(bctx, {
        type: 'line',
        plugins: [thresholdPlugin],
        data: {
            labels: bsLabels,
            datasets: [{
                data: bsValues,
                borderColor: '#f97447',
                backgroundColor: gradient,
                borderWidth: 2.5,
                pointBackgroundColor: bsColors,
                pointBorderColor: '#1c0f0a',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8,
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
                        label: ctx => {
                            const v = ctx.parsed.y;
                            const s = v < 70 ? ' · Low' : (v > 180 ? ' · High' : ' · Normal');
                            return ` ${v} mg/dL${s}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(255,200,160,0.35)', font: { size: 10, family: 'DM Sans' } },
                    border: { color: 'transparent' }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(255,200,160,0.35)', font: { size: 10, family: 'DM Sans' } },
                    border: { color: 'transparent' },
                    suggestedMin: 50,
                    suggestedMax: 300,
                }
            }
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>