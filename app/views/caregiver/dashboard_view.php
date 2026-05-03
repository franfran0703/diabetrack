<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
ob_start();

$hour         = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');
$firstName    = ucfirst(strtolower(explode(' ', trim($name))[0]));

// Build sparkline data for JS
$sparkLabels  = [];
$sparkValues  = [];
$sparkColors  = [];
foreach (($sparkline ?? []) as $s) {
    $sparkLabels[] = date('M d', strtotime($s['logged_at']));
    $sparkValues[] = (float) $s['reading'];
    $sparkColors[] = $s['status'] === 'High' ? '#f87171' : ($s['status'] === 'Low' ? '#fbbf24' : '#4ade80');
}
?>

<link href="/diabetrack/public/assets/css/caregiver_dashboard.css?v=<?= time() ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- ══ TOP ROW: BANNER + PATIENT CARD ════════════════════ -->
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
            <div class="cgd-date-pill">📅 <?= $todayLabel ?></div>
        </div>
        <div class="cgd-banner-logo-wrap">
            <img src="/diabetrack/public/assets/img/diabetrack-icon.png" class="cgd-banner-logo" alt="">
            <div class="cgd-banner-logo-ring"></div>
        </div>
    </div>

    <!-- PATIENT IDENTITY CARD -->
    <?php if ($patient): ?>
    <div class="cgd-identity-card">
        <div class="cgd-identity-glow"></div>
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
            <a href="/diabetrack/public/caregiver/patients" class="cgd-identity-manage">⚙</a>
        </div>

        <!-- HEALTH SCORE RING -->
        <div class="cgd-health-score-wrap">
            <div class="cgd-health-ring-container">
                <canvas id="healthRing" width="110" height="110"></canvas>
                <div class="cgd-health-ring-label">
                    <?php
                    $score = 100;
                    if (($abnormalReadings ?? 0) > 0) $score -= min(40, $abnormalReadings * 10);
                    if (($missedMeds ?? 0) > 0)       $score -= min(30, $missedMeds * 10);
                    if (($unreadAlerts ?? 0) > 2)      $score -= 10;
                    $score = max(0, $score);
                    $scoreColor = $score >= 80 ? '#4ade80' : ($score >= 50 ? '#fbbf24' : '#f87171');
                    ?>
                    <span style="color:<?= $scoreColor ?>"><?= $score ?></span>
                    <small>Health<br>Score</small>
                </div>
            </div>
            <div class="cgd-health-score-details">
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#4ade80;"></span>
                    <span class="cgd-score-key">Normal readings</span>
                    <span class="cgd-score-val"><?= max(0, $totalLogs - ($abnormalReadings ?? 0)) ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#f87171;"></span>
                    <span class="cgd-score-key">Abnormal</span>
                    <span class="cgd-score-val"><?= $abnormalReadings ?? 0 ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#fbbf24;"></span>
                    <span class="cgd-score-key">Missed doses</span>
                    <span class="cgd-score-val"><?= $missedMeds ?></span>
                </div>
                <div class="cgd-score-row">
                    <span class="cgd-score-dot" style="background:#fb923c;"></span>
                    <span class="cgd-score-key">Unread alerts</span>
                    <span class="cgd-score-val"><?= $unreadAlerts ?></span>
                </div>
            </div>
        </div>

        <?php if ($latestSugar): ?>
        <div class="cgd-latest-reading">
            <div>
                <div class="cgd-reading-eyebrow">Latest Reading</div>
                <div class="cgd-reading-big">
                    <?= $latestSugar['reading'] ?>
                    <span class="cgd-reading-unit">mg/dL</span>
                </div>
                <div class="cgd-reading-time"><?= date('M d · h:i A', strtotime($latestSugar['logged_at'])) ?></div>
            </div>
            <div class="cgd-reading-badge <?= strtolower($latestSugar['status']) ?>">
                <?= $latestSugar['status']==='High' ? '🔴' : ($latestSugar['status']==='Low' ? '🟡' : '🟢') ?>
                <?= $latestSugar['status'] ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="cgd-no-patient">
        <div class="cgd-no-patient-icon">🔗</div>
        <div class="cgd-no-patient-title">No Patient Linked</div>
        <div class="cgd-no-patient-sub">Send a request to start monitoring.</div>
        <a href="/diabetrack/public/caregiver/patients" class="cgd-action-btn">Link a Patient →</a>
    </div>
    <?php endif; ?>

</div>

<!-- ══ MIDDLE ROW: CHARTS + STATS ════════════════════════ -->
<div class="cgd-mid-row">

    <!-- BLOOD SUGAR CHART -->
    <div class="cgd-chart-card">
        <div class="cgd-card-header">
            <div>
                <div class="cgd-card-eyebrow">Last 7 Readings</div>
                <div class="cgd-card-title">Blood Sugar Trend</div>
            </div>
            <a href="/diabetrack/public/caregiver/bloodsugar" class="cgd-card-link">Full Log →</a>
        </div>
        <?php if (empty($sparkline)): ?>
        <div class="cgd-empty-chart">No readings yet</div>
        <?php else: ?>
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
            <a href="/diabetrack/public/caregiver/medication" class="cgd-card-link">View →</a>
        </div>
        <div class="cgd-med-rate-wrap">
            <div class="cgd-med-rate-num" style="color:<?= ($medRate??0) >= 80 ? '#4ade80' : (($medRate??0) >= 50 ? '#fbbf24' : '#f87171') ?>">
                <?= $medRate ?? 0 ?>%
            </div>
            <div class="cgd-med-rate-label">Compliance Rate</div>
        </div>
        <div class="cgd-med-bar-wrap">
            <div class="cgd-med-bar">
                <div class="cgd-med-bar-fill" style="width:<?= $medRate ?? 0 ?>%;background:<?= ($medRate??0) >= 80 ? 'linear-gradient(90deg,#4ade80,#22c55e)' : (($medRate??0) >= 50 ? 'linear-gradient(90deg,#fbbf24,#f59e0b)' : 'linear-gradient(90deg,#f87171,#ef4444)') ?>"></div>
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
        <a href="/diabetrack/public/caregiver/medication" class="cgd-action-btn" style="margin-top:auto;">Check Schedule →</a>
    </div>

    <!-- STATS COLUMN -->
    <div class="cgd-stats-col">
        <div class="cgd-mini-stat" data-c="blue">
            <div class="cgd-mini-icon">📊</div>
            <div>
                <div class="cgd-mini-num"><?= $totalLogs ?></div>
                <div class="cgd-mini-label">Logs This Week</div>
            </div>
        </div>
        <div class="cgd-mini-stat" data-c="red">
            <div class="cgd-mini-icon">⚠️</div>
            <div>
                <div class="cgd-mini-num"><?= $abnormalReadings ?? 0 ?></div>
                <div class="cgd-mini-label">Abnormal</div>
            </div>
        </div>
        <div class="cgd-mini-stat" data-c="yellow">
            <div class="cgd-mini-icon">🔔</div>
            <div>
                <div class="cgd-mini-num"><?= $unreadAlerts ?></div>
                <div class="cgd-mini-label">Unread Alerts</div>
            </div>
        </div>
        <div class="cgd-mini-stat" data-c="orange">
            <div class="cgd-mini-icon">💊</div>
            <div>
                <div class="cgd-mini-num"><?= $missedMeds ?></div>
                <div class="cgd-mini-label">Missed Today</div>
            </div>
        </div>
    </div>

</div>

<!-- ══ BOTTOM ROW: ACTIVITY TIMELINE + QUICK NAV ════════ -->
<div class="cgd-bot-row">

    <!-- ACTIVITY TIMELINE -->
    <div class="cgd-timeline-card">
        <div class="cgd-card-header" style="margin-bottom:20px;">
            <div>
                <div class="cgd-card-eyebrow">Recent Activity</div>
                <div class="cgd-card-title">Patient Timeline</div>
            </div>
        </div>

        <?php
        // Merge recent meals, activity, alerts into one timeline
        $timeline = [];
        foreach (($recentMeals ?? []) as $m) {
            $timeline[] = ['type'=>'meal',     'icon'=>'🥗', 'title'=>htmlspecialchars($m['meal_name']), 'sub'=>$m['meal_type'].' · '.$m['carbs'].'g carbs', 'time'=>$m['logged_at'], 'color'=>'green'];
        }
        foreach (($recentActivity ?? []) as $a) {
            $timeline[] = ['type'=>'activity', 'icon'=>'🏃', 'title'=>htmlspecialchars($a['activity_name']), 'sub'=>$a['duration_minutes'].' min · '.$a['intensity'], 'time'=>$a['logged_at'], 'color'=>'blue'];
        }
        foreach (($recentAlerts ?? []) as $al) {
            $c = str_contains($al['type'],'High') ? 'red' : (str_contains($al['type'],'Low') ? 'yellow' : 'orange');
            $e = str_contains($al['type'],'High') ? '🔴' : (str_contains($al['type'],'Low') ? '🟡' : '🔔');
            $timeline[] = ['type'=>'alert', 'icon'=>$e, 'title'=>htmlspecialchars($al['type']), 'sub'=>htmlspecialchars($al['message']), 'time'=>$al['created_at'], 'color'=>$c];
        }
        usort($timeline, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));
        $timeline = array_slice($timeline, 0, 8);
        ?>

        <?php if (empty($timeline)): ?>
        <div class="cgd-empty-state">
            <div class="cgd-empty-icon">📋</div>
            <div class="cgd-empty-text">No recent activity to show.</div>
        </div>
        <?php else: ?>
        <div class="cgd-timeline">
            <?php foreach ($timeline as $i => $item): ?>
            <div class="cgd-timeline-item">
                <div class="cgd-tl-icon-wrap <?= $item['color'] ?>"><?= $item['icon'] ?></div>
                <div class="cgd-tl-line <?= $i === count($timeline)-1 ? 'last' : '' ?>"></div>
                <div class="cgd-tl-body">
                    <div class="cgd-tl-title"><?= $item['title'] ?></div>
                    <div class="cgd-tl-sub"><?= $item['sub'] ?></div>
                </div>
                <div class="cgd-tl-time"><?= date('h:i A', strtotime($item['time'])) ?><br><span><?= date('M d', strtotime($item['time'])) ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- QUICK NAV -->
    <div class="cgd-quick-nav">
        <div class="cgd-card-eyebrow" style="margin-bottom:14px;">Quick Access</div>
        <?php
        $navItems = [
            ['/diabetrack/public/caregiver/bloodsugar', '🩸', 'Blood Sugar',  'Monitor readings',     'rgba(239,68,68,0.15)'],
            ['/diabetrack/public/caregiver/medication', '💊', 'Medication',   'Today\'s schedule',    'rgba(249,116,71,0.15)'],
            ['/diabetrack/public/caregiver/meals',      '🥗', 'Meals',        'Diet & nutrition',     'rgba(34,197,94,0.12)'],
            ['/diabetrack/public/caregiver/patients',   '👥', 'My Patients',  'Manage linked',        'rgba(139,92,246,0.12)'],
            ['/diabetrack/public/caregiver/alerts',     '🔔', 'Alerts',       'Full alert history',   'rgba(234,179,8,0.12)'],
            ['/diabetrack/public/caregiver/reports',    '📄', 'Reports',      'Generate summaries',   'rgba(14,165,233,0.12)'],
        ];
        foreach ($navItems as [$url, $icon, $title, $sub, $bg]): ?>
        <a href="<?= $url ?>" class="cgd-qnav-item">
            <div class="cgd-qnav-icon" style="background:<?= $bg ?>"><?= $icon ?></div>
            <div>
                <div class="cgd-qnav-title"><?= $title ?></div>
                <div class="cgd-qnav-sub"><?= $sub ?></div>
            </div>
            <div class="cgd-qnav-arrow">→</div>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<script>
// ── HEALTH SCORE RING ─────────────────────────────
const score = <?= $score ?? 100 ?>;
const scoreColor = score >= 80 ? '#4ade80' : (score >= 50 ? '#fbbf24' : '#f87171');
const rctx = document.getElementById('healthRing')?.getContext('2d');
if (rctx) {
    new Chart(rctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [scoreColor, 'rgba(255,255,255,0.05)'],
                borderWidth: 0,
                borderRadius: 8,
            }]
        },
        options: {
            cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            animation: { animateRotate: true, duration: 1000 }
        }
    });
}

// ── BLOOD SUGAR CHART ─────────────────────────────
const bsLabels = <?= json_encode($sparkLabels) ?>;
const bsValues = <?= json_encode($sparkValues) ?>;
const bsColors = <?= json_encode($sparkColors) ?>;
const bctx = document.getElementById('bsChart')?.getContext('2d');
if (bctx && bsValues.length > 0) {
    const gradient = bctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(249,116,71,0.3)');
    gradient.addColorStop(1, 'rgba(249,116,71,0)');
    new Chart(bctx, {
        type: 'line',
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
                pointHoverRadius: 7,
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
                    callbacks: { label: ctx => ` ${ctx.parsed.y} mg/dL` }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,200,160,0.4)', font: { size: 11 } } },
                y: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(255,200,160,0.4)', font: { size: 11 } },
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