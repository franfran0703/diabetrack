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
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to start monitoring.
    </div>
</div>

<?php else: ?>

<?php
$normalRate = $stats['total'] > 0
    ? round(($stats['normal'] / $stats['total']) * 100)
    : 0;
$latestStatus = $latest ? strtolower($latest['status']) : 'none';
?>

<!-- ═══════════════════════════════════════════════════════
     CHART HERO — full width, leads the page
     ═══════════════════════════════════════════════════════ -->
<div class="cgbs-chart-hero">

    <!-- Top bar: latest reading + legend -->
    <div class="cgbs-chart-hero-top">

        <!-- Latest reading embedded in the chart header -->
        <div class="cgbs-chart-hero-reading">
            <div class="cgbs-reading-num">
                <?= $latest ? $latest['reading'] : '—' ?><small>mg/dL</small>
            </div>
            <div class="cgbs-reading-meta">
                <div class="cgbs-reading-status <?= $latestStatus ?>">
                    <?= $latest ? ($latest['status']==='High' ? '🔴' : ($latest['status']==='Low' ? '🟡' : '🟢')) : '' ?>
                    <?= $latest ? $latest['status'] : 'No Data' ?>
                </div>
                <div class="cgbs-reading-type">
                    <?php if ($latest): ?>
                        <?= htmlspecialchars($latest['reading_type']) ?>
                        <?php if ($latest['notes']): ?> · "<?= htmlspecialchars($latest['notes']) ?>"<?php endif; ?>
                        · <?= date('M d, h:i A', strtotime($latest['logged_at'])) ?>
                    <?php else: ?>
                        No readings logged yet
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="cgbs-chart-legend">
            <div class="cgbs-legend-item">
                <div class="cgbs-legend-dot" style="background:#22c55e;"></div> Normal (70–180)
            </div>
            <div class="cgbs-legend-item">
                <div class="cgbs-legend-dot" style="background:#ef4444;"></div> High (&gt;180)
            </div>
            <div class="cgbs-legend-item">
                <div class="cgbs-legend-dot" style="background:#f59e0b;"></div> Low (&lt;70)
            </div>
        </div>

    </div>

    <!-- Chart sublabel -->
    <div class="cgbs-chart-sublabel">Trend — Last 7 Readings</div>

    <!-- Chart or empty -->
    <?php if (!empty($last7)): ?>
    <canvas id="sugarChart" height="90"></canvas>
    <?php else: ?>
    <div class="cgbs-empty">
        <div class="cgbs-empty-icon">📈</div>
        <div class="cgbs-empty-text">No readings yet — chart will appear here.</div>
    </div>
    <?php endif; ?>

</div>

<!-- ═══════════════════════════════════════════════════════
     STAT ROW — 4 equal tiles
     ═══════════════════════════════════════════════════════ -->
<div class="cgbs-stat-row">

    <!-- Total readings -->
    <div class="cgbs-stat-tile glass">
        <div class="cgbs-tile-label">Total Readings</div>
        <div class="cgbs-tile-val"><?= $stats['total'] ?></div>
        <div class="cgbs-tile-sub">All time logs</div>
    </div>

    <!-- High readings -->
    <div class="cgbs-stat-tile danger">
        <div class="cgbs-tile-label">High Readings</div>
        <div class="cgbs-tile-val"><?= $stats['high'] ?></div>
        <div class="cgbs-tile-sub">🔴 Above 180 mg/dL</div>
    </div>

    <!-- Low readings -->
    <div class="cgbs-stat-tile warm">
        <div class="cgbs-tile-label">Low Readings</div>
        <div class="cgbs-tile-val"><?= $stats['low'] ?></div>
        <div class="cgbs-tile-sub">🟡 Below 70 mg/dL</div>
    </div>

    <!-- Normal rate — peach with progress bar -->
    <div class="cgbs-stat-tile peach">
        <div class="cgbs-tile-label">Normal Rate</div>
        <div class="cgbs-tile-val">
            <?= $normalRate ?><small>%</small>
        </div>
        <div class="cgbs-tile-bar-track">
            <div class="cgbs-tile-bar-fill" style="width:<?= $normalRate ?>%;"></div>
        </div>
        <div class="cgbs-tile-sub"><?= $stats['normal'] ?> of <?= $stats['total'] ?> readings</div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════
     LOG TABLE — full width peach
     ═══════════════════════════════════════════════════════ -->
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
                    <th>Date &amp; Time</th>
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
            fill: true,
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
            },
            /* Safe zone bands */
            annotation: undefined,
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: { color: 'rgba(255,255,255,0.04)' },
                ticks: {
                    font: { size: 11, family: 'Cabinet Grotesk' },
                    color: 'rgba(255,200,160,0.4)',
                },
                border: { color: 'transparent' },
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 10, family: 'Cabinet Grotesk' },
                    color: 'rgba(255,200,160,0.4)',
                    maxRotation: 30,
                },
                border: { color: 'transparent' },
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