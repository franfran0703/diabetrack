<?php
$pageTitle  = 'Alerts Feed';
$activeMenu = 'alerts';
ob_start();

// Group alerts by date
$grouped = [];
foreach ($allAlerts as $alert) {
    $date = date('Y-m-d', strtotime($alert['created_at']));
    $grouped[$date][] = $alert;
}

// Helper — get alert class and emoji
function alertClass($type) {
    if (str_contains($type, 'High') || str_contains($type, 'Sugar') && str_contains($type, 'High'))
        return ['class' => 'high',   'emoji' => '🔴', 'badge' => 'High Sugar'];
    if (str_contains($type, 'Low'))
        return ['class' => 'low',    'emoji' => '🟡', 'badge' => 'Low Sugar'];
    if (str_contains($type, 'Missed') || str_contains($type, 'Dose'))
        return ['class' => 'missed', 'emoji' => '💊', 'badge' => 'Missed Dose'];
    return    ['class' => 'other',   'emoji' => '🔔', 'badge' => $type];
}
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>" rel="stylesheet">
<link href="/diabetrack/public/assets/css/caregiver_alerts.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cga-header">
    <div>
        <div class="cga-eyebrow">Notifications</div>
        <h1 class="cga-title">🔔 Alerts <span>Feed</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cga-patient-chip">
        <div class="cga-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cga-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cga-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to see their alerts.
    </div>
</div>

<?php else: ?>

<!-- TOP BAR — stats as pills + legend strip -->
<div class="cga-topbar">

    <div class="cga-stat-pill total">
        <div class="cga-stat-pill-icon">🔔</div>
        <div class="cga-stat-pill-val"><?= $stats['total'] ?></div>
        <div class="cga-stat-pill-label">Total</div>
    </div>

    <div class="cga-stat-pill sugar">
        <div class="cga-stat-pill-icon">🔴</div>
        <div class="cga-stat-pill-val"><?= $stats['high'] ?></div>
        <div class="cga-stat-pill-label">Sugar Alerts</div>
    </div>

    <div class="cga-stat-pill missed">
        <div class="cga-stat-pill-icon">💊</div>
        <div class="cga-stat-pill-val"><?= $stats['missed'] ?></div>
        <div class="cga-stat-pill-label">Missed Doses</div>
    </div>

    <!-- Legend strip — right side -->
    <div class="cga-legend-strip">
        <?php
        $lowCount = array_reduce($allAlerts, fn($c,$a) => $c + (str_contains($a['type'],'Low') ? 1 : 0), 0);
        $legends = [
            ['dot' => 'high',   'name' => 'High Sugar',  'count' => $stats['high']],
            ['dot' => 'low',    'name' => 'Low Sugar',   'count' => $lowCount],
            ['dot' => 'missed', 'name' => 'Missed Dose', 'count' => $stats['missed']],
            ['dot' => 'other',  'name' => 'Other',       'count' => $stats['other']],
        ];
        foreach ($legends as $l): ?>
        <div class="cga-legend-item">
            <div class="cga-legend-dot <?= $l['dot'] ?>"></div>
            <div class="cga-legend-name"><?= $l['name'] ?></div>
            <div class="cga-legend-count"><?= $l['count'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- TIMELINE FEED — full width -->
<div class="cga-feed">
    <div class="cga-feed-label">All Alerts — <?= htmlspecialchars($patient['name']) ?></div>

    <?php if (empty($allAlerts)): ?>
    <div class="cga-empty">
        <div class="cga-empty-icon">✅</div>
        <div class="cga-empty-title">All clear!</div>
        <div class="cga-empty-sub">No alerts have been generated for this patient yet.</div>
    </div>

    <?php else: ?>
    <?php foreach ($grouped as $date => $alerts):
        $dateLabel = date('Y-m-d') === $date ? 'Today'
                   : (date('Y-m-d', strtotime('-1 day')) === $date ? 'Yesterday'
                   : date('F j, Y', strtotime($date)));
    ?>

    <!-- Date group label -->
    <div class="cga-date-group"><?= $dateLabel ?></div>

    <!-- Timeline track for this date -->
    <div class="cga-timeline">
        <?php foreach ($alerts as $alert):
            $a = alertClass($alert['type']);
        ?>
        <div class="cga-alert-item <?= $a['class'] ?>">
            <!-- Dot sitting on the vertical line -->
            <div class="cga-tl-dot <?= $a['class'] ?>"><?= $a['emoji'] ?></div>

            <div class="cga-alert-content">
                <div class="cga-alert-type"><?= htmlspecialchars($alert['type']) ?></div>
                <div class="cga-alert-msg"><?= htmlspecialchars($alert['message']) ?></div>
            </div>

            <div class="cga-alert-right">
                <div class="cga-alert-time">
                    <?= date('h:i A', strtotime($alert['created_at'])) ?>
                </div>
                <span class="cga-alert-badge"><?= $a['badge'] ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- INFO STRIP — full width at bottom -->
<div class="cga-info-strip">
    <div class="cga-info-strip-title">What Triggers Alerts</div>
    <?php
    $infos = [
        ['icon' => '🔴', 'text' => 'Blood sugar above 180 mg/dL'],
        ['icon' => '🟡', 'text' => 'Blood sugar below 70 mg/dL'],
        ['icon' => '💊', 'text' => 'Medication not logged on time'],
        ['icon' => '🍽️', 'text' => 'Daily carb limit exceeded'],
        ['icon' => '🏃', 'text' => 'No activity logged all day'],
        ['icon' => '📅', 'text' => 'Upcoming doctor appointment'],
    ];
    foreach ($infos as $info): ?>
    <div class="cga-info-item">
        <div class="cga-info-icon"><?= $info['icon'] ?></div>
        <div class="cga-info-text"><?= $info['text'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>