<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

ob_start();

$hour = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>" rel="stylesheet">
<link href="/diabetrack/public/assets/css/caregiver_dashboard.css?v=<?= time() ?>" rel="stylesheet">

<!-- GREETING -->
<div class="cgd-greeting">
    <div class="cgd-eyebrow"><?= $timeGreeting ?>, Caregiver</div>
    <h1 class="cgd-title">
        <span class="highlight"><?= htmlspecialchars($name) ?></span> 
    </h1>
    <p class="cgd-sub">Here's your patient's health overview for today.</p>
    <div class="cgd-date-pill">📅 <?= $todayLabel ?></div>
</div>

<!-- BIG PATIENT CARD -->
<?php if ($patient): ?>
<div class="cgd-patient-card">
    <div class="cgd-patient-avatar">
        <?= strtoupper(substr($patient['name'], 0, 1)) ?>
    </div>
    <div class="cgd-patient-info">
        <div class="cgd-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
        <div class="cgd-patient-email">📧 <?= htmlspecialchars($patient['email']) ?></div>
        <div class="cgd-patient-tags">
            <span class="cgd-patient-tag">🔗 Linked Patient</span>
            <span class="cgd-patient-tag">🩺 Under Your Care</span>
            <?php if ($latestSugar): ?>
            <span class="cgd-patient-tag">
                Last logged: <?= date('M d, h:i A', strtotime($latestSugar['logged_at'])) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Latest reading panel -->
    <div class="cgd-patient-reading">
        <div class="cgd-reading-label">Latest Blood Sugar</div>
        <?php if ($latestSugar): ?>
        <div class="cgd-reading-val">
            <?= $latestSugar['reading'] ?><span class="cgd-reading-unit">mg/dL</span>
        </div>
        <div class="cgd-reading-status <?= strtolower($latestSugar['status']) ?>">
            <?= $latestSugar['status']==='High' ? '🔴' : ($latestSugar['status']==='Low' ? '🟡' : '🟢') ?>
            <?= $latestSugar['status'] ?>
        </div>
        <?php else: ?>
        <div class="cgd-reading-val" style="font-size:2.4rem;color:#d4917a;">—</div>
        <div class="cgd-reading-status none">No logs yet</div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="cgd-no-patient">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div class="cgd-no-patient-title">No Patient Linked Yet</div>
    <div class="cgd-no-patient-sub">
        <a href="/diabetrack/public/caregiver/patients"
           style="color:#fbab6e;font-weight:700;">Link a patient</a>
        to start monitoring their health.
    </div>
</div>
<?php endif; ?>

<!-- MINI STAT ROW -->
<div class="cgd-stat-row">
    <div class="cgd-stat-mini glass">
        <div class="cgd-stat-mini-icon">📊</div>
        <div class="cgd-stat-mini-val"><?= $totalLogs ?></div>
        <div class="cgd-stat-mini-label">Logs This Week</div>
    </div>
    <div class="cgd-stat-mini peach">
        <div class="cgd-stat-mini-icon">💊</div>
        <div class="cgd-stat-mini-val"><?= $missedMeds ?></div>
        <div class="cgd-stat-mini-label">Missed Doses Today</div>
    </div>
    <div class="cgd-stat-mini glass-warm">
        <div class="cgd-stat-mini-icon">🔔</div>
        <div class="cgd-stat-mini-val"><?= $unreadAlerts ?></div>
        <div class="cgd-stat-mini-label">Unread Alerts</div>
    </div>
    <div class="cgd-stat-mini danger-glass">
        <div class="cgd-stat-mini-icon">⚠️</div>
        <div class="cgd-stat-mini-val">
        <?= $abnormalReadings ?? 0 ?>
        </div>
        <div class="cgd-stat-mini-label">Abnormal Readings</div>
    </div>
</div>

<!-- BOTTOM ROW: ALERT FEED + QUICK NAV -->
<div class="cgd-bottom">

    <!-- ALERT FEED -->
    <div class="cgd-alert-panel">
        <div class="cgd-panel-label">Recent Alerts</div>

        <?php if (empty($recentAlerts)): ?>
        <div class="cgd-alert-empty">
            <div class="cgd-alert-empty-icon">✅</div>
            <div class="cgd-alert-empty-text">All clear — no alerts right now.</div>
        </div>
        <?php else: ?>
        <?php foreach ($recentAlerts as $alert):
            $dotClass = str_contains($alert['type'], 'High')   ? 'high'   :
                       (str_contains($alert['type'], 'Low')    ? 'low'    :
                       (str_contains($alert['type'], 'Missed') ? 'missed' : 'good'));
            $dotEmoji = $dotClass === 'high'   ? '🔴' :
                       ($dotClass === 'low'    ? '🟡' :
                       ($dotClass === 'missed' ? '💊' : '✅'));
        ?>
        <div class="cgd-alert-item">
            <div class="cgd-alert-dot <?= $dotClass ?>"><?= $dotEmoji ?></div>
            <div style="flex:1;">
                <div class="cgd-alert-title"><?= htmlspecialchars($alert['type']) ?></div>
                <div class="cgd-alert-msg"><?= htmlspecialchars($alert['message']) ?></div>
            </div>
            <div class="cgd-alert-time">
                <?= date('h:i A', strtotime($alert['created_at'])) ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- QUICK NAV -->
    <div class="cgd-nav-stack">
        <a href="/diabetrack/public/caregiver/bloodsugar" class="cgd-nav-item peach">
            <div class="cgd-nav-icon">🩸</div>
            <div>
                <div class="cgd-nav-title">Blood Sugar</div>
                <div class="cgd-nav-sub">Monitor patient readings</div>
            </div>
            <div class="cgd-nav-arrow">→</div>
        </a>
        <a href="/diabetrack/public/caregiver/medication" class="cgd-nav-item glass-warm">
            <div class="cgd-nav-icon">💊</div>
            <div>
                <div class="cgd-nav-title">Medication</div>
                <div class="cgd-nav-sub">Check today's schedule</div>
            </div>
            <div class="cgd-nav-arrow">→</div>
        </a>
        <a href="/diabetrack/public/caregiver/patients" class="cgd-nav-item glass">
            <div class="cgd-nav-icon">👥</div>
            <div>
                <div class="cgd-nav-title">My Patients</div>
                <div class="cgd-nav-sub">Manage linked patients</div>
            </div>
            <div class="cgd-nav-arrow">→</div>
        </a>
        <a href="/diabetrack/public/caregiver/alerts" class="cgd-nav-item glass">
            <div class="cgd-nav-icon">🔔</div>
            <div>
                <div class="cgd-nav-title">All Alerts</div>
                <div class="cgd-nav-sub">View full alert history</div>
            </div>
            <div class="cgd-nav-arrow">→</div>
        </a>
        <a href="/diabetrack/public/caregiver/reports" class="cgd-nav-item glass">
            <div class="cgd-nav-icon">📄</div>
            <div>
                <div class="cgd-nav-title">Reports</div>
                <div class="cgd-nav-sub">View health summaries</div>
            </div>
            <div class="cgd-nav-arrow">→</div>
        </a>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>