<?php
$pageTitle  = 'Blood Sugar Monitor';
$activeMenu = 'bloodsugar';

ob_start();

$chartLabels = [];
$chartData   = [];
$chartColors = [];
foreach ($last7 as $log) {
    $chartLabels[] = date('M d, h:i A', strtotime($log['logged_at']));
    $chartData[]   = $log['reading'];
    $chartColors[] = $log['status'] === 'High' ? '#ef4444' :
                    ($log['status'] === 'Low'  ? '#f59e0b' : '#22c55e');
}
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>">
<link href="/diabetrack/public/assets/css/caregiver_bloodsugar.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgbs-header">
    <div>
        <div class="cgbs-eyebrow">Live Monitor</div>
        <h1 class="cgbs-title">Blood Sugar <span>Monitor</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cgbs-patient-chip">
        <div class="cgbs-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cgbs-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cgbs-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<!-- NO PATIENT -->
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to start monitoring.
    </div>
</div>

<?php else: ?>

<!-- TOP ROW: HERO + MINI STACK -->
<div class="cgbs-top">

    <!-- HERO — big reading -->
    <div class="cgbs-hero">
        <div class="cgbs-hero-top">
            <div class="cgbs-hero-eyebrow">Latest Reading</div>
            <?php if ($latest): ?>
            <div class="cgbs-hero-time"><?= date('M d, Y · h:i A', strtotime($latest['logged_at'])) ?></div>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($latest): ?>
            <div class="cgbs-hero-reading">
                <?= $latest['reading'] ?><span class="cgbs-hero-unit">mg/dL</span>
            </div>
            <?php else: ?>
            <div class="cgbs-hero-reading" style="font-size:5rem;color:#d4917a;">—</div>
            <?php endif; ?>
        </div>

        <div class="cgbs-hero-bottom">
            <div class="cgbs-hero-type">
                <?= $latest ? htmlspecialchars($latest['reading_type']) : 'No readings yet' ?>
                <?php if ($latest && $latest['notes']): ?>
                &nbsp;·&nbsp; "<?= htmlspecialchars($latest['notes']) ?>"
                <?php endif; ?>
            </div>
            <?php if ($latest): ?>
            <div class="cgbs-status-pill <?= strtolower($latest['status']) ?>">
                <?= $latest['status']==='High' ? '🔴' : ($latest['status']==='Low' ? '🟡' : '🟢') ?>
                <?= $latest['status'] ?>
            </div>
            <?php else: ?>
            <div class="cgbs-status-pill none">No Data</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MINI STACK -->
    <div class="cgbs-mini-stack">

        <!-- Total readings — glass -->
        <div class="cgbs-mini-glass">
            <div class="cgbs-mini-label">Total Readings</div>
            <div class="cgbs-mini-val"><?= $stats['total'] ?></div>
            <div class="cgbs-mini-sub">All time logs</div>
        </div>

        <!-- Abnormal — warm glass -->
        <div class="cgbs-mini-warm">
            <div class="cgbs-mini-label">Abnormal</div>
            <div class="cgbs-mini-val"><?= $stats['high'] + $stats['low'] ?></div>
            <div class="cgbs-mini-sub">
                🔴 <?= $stats['high'] ?> High &nbsp;·&nbsp; 🟡 <?= $stats['low'] ?> Low
            </div>
        </div>

        <!-- Normal rate — peach -->
        <?php
        $normalRate = $stats['total'] > 0
            ? round(($stats['normal'] / $stats['total']) * 100)
            : 0;
        ?>
        <div style="
            background:linear-gradient(145deg,#FDE8DC,#fdd5be);
            border:1.5px solid rgba(249,116,71,0.2);
            border-radius:22px;
            padding:22px;
            box-shadow:0 8px 28px rgba(0,0,0,0.25);
            transition:transform 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'"
            onmouseout="this.style.transform='none'">
            <div class="cgbs-mini-label" style="color:#c4714a;">Normal Rate</div>
            <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:900;font-size:2.8rem;color:#c04a20;line-height:1;letter-spacing:-2px;">
                <?= $normalRate ?><span style="font-size:1.2rem;color:#d4917a;font-weight:600;">%</span>
            </div>
            <!-- Mini progress bar -->
            <div style="height:6px;background:rgba(249,116,71,0.12);border-radius:100px;overflow:hidden;margin-top:12px;">
                <div style="height:100%;width:<?= $normalRate ?>%;background:linear-gradient(90deg,#22c55e,#86efac);border-radius:100px;transition:width 1s ease;"></div>
            </div>
            <div style="font-size:0.72rem;color:#b8927e;font-weight:600;margin-top:6px;">
                <?= $stats['normal'] ?> of <?= $stats['total'] ?> readings
            </div>
        </div>

    </div>
</div>

<!-- CHART PANEL — full width glass -->
<div class="cgbs-chart-panel">
    <div class="cgbs-chart-label">Trend — Last 7 Readings</div>

    <div class="cgbs-legend">
        <div class="cgbs-legend-item">
            <div class="cgbs-legend-dot" style="background:#22c55e;"></div> Normal (70–180)
        </div>
        <div class="cgbs-legend-item">
            <div class="cgbs-legend-dot" style="background:#ef4444;"></div> High (>180)
        </div>
        <div class="cgbs-legend-item">
            <div class="cgbs-legend-dot" style="background:#f59e0b;"></div> Low (<70)
        </div>
    </div>

    <?php if (!empty($last7)): ?>
    <canvas id="sugarChart" height="80"></canvas>
    <?php else: ?>
    <div class="cgbs-empty">
        <div class="cgbs-empty-icon">📈</div>
        <div class="cgbs-empty-text">No readings yet — chart will appear here.</div>
    </div>
    <?php endif; ?>
</div>

<!-- TABLE PANEL — full width peach -->
<div class="cgbs-table-panel">
    <div class="cgbs-table-label">
        All Readings — <?= htmlspecialchars($patient['name']) ?>
    </div>

    <?php if (empty($logs)): ?>
    <div class="cgbs-empty">
        <div class="cgbs-empty-icon">🩸</div>
        <div class="cgbs-empty-text">No readings logged yet for this patient.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgbs-table">
            <thead>
                <tr>
                    <th>Reading</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <span class="cgbs-table-val"><?= $log['reading'] ?></span>
                        <span class="cgbs-table-unit">mg/dL</span>
                    </td>
                    <td class="cgbs-table-muted"><?= $log['reading_type'] ?></td>
                    <td>
                        <span class="cgbs-tpill <?= $log['status']==='High' ? 'danger' : ($log['status']==='Low' ? 'warn' : 'good') ?>">
                            <?= $log['status']==='High' ? '🔴' : ($log['status']==='Low' ? '🟡' : '🟢') ?>
                            <?= $log['status'] ?>
                        </span>
                    </td>
                    <td class="cgbs-table-muted"><?= $log['notes'] ? htmlspecialchars($log['notes']) : '—' ?></td>
                    <td class="cgbs-table-muted" style="white-space:nowrap;">
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

<?php if (!empty($last7)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('sugarChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Blood Sugar (mg/dL)',
            data: <?= json_encode($chartData) ?>,
            borderColor: '#fbab6e',
            backgroundColor: 'rgba(249,116,71,0.06)',
            borderWidth: 2.5,
            pointBackgroundColor: <?= json_encode($chartColors) ?>,
            pointBorderColor: '#1C0F0A',
            pointBorderWidth: 2.5,
            pointRadius: 7,
            pointHoverRadius: 9,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(28,15,10,0.9)',
                titleColor: '#fbab6e',
                bodyColor: '#ffe8d6',
                borderColor: 'rgba(249,116,71,0.2)',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 12,
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: {
                    font: { size: 11, family: 'Cabinet Grotesk' },
                    color: 'rgba(255,200,160,0.4)'
                },
                border: { color: 'transparent' }
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 10, family: 'Cabinet Grotesk' },
                    color: 'rgba(255,200,160,0.4)'
                },
                border: { color: 'transparent' }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>
