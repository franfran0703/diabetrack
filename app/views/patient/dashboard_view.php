<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

ob_start();

$hour         = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');

// Blood sugar status for banner badge
$bsStatus = '—';
$bsEmoji  = '📊';
if (isset($latestBloodSugar, $latestBloodSugarStatus)) {
    $bsEmoji  = $latestBloodSugarStatus === 'High' ? '🔴' :
               ($latestBloodSugarStatus === 'Low'  ? '🟡' : '🟢');
    $bsStatus = $latestBloodSugarStatus . ' · ' . $latestBloodSugar . ' mg/dL';
}
?>

<link href="/diabetrack/public/assets/css/dashboard.css?<?= time() ?>" rel="stylesheet">

<!-- ═══════════════════════════════════════════════════════
     WELCOME BANNER — full width
     ═══════════════════════════════════════════════════════ -->
<div class="db-banner">

    <div class="db-banner-left">
        <div class="db-greeting-eyebrow"><?= $timeGreeting ?></div>
        <h1 class="db-greeting-name">
            Hello, <span class="name-highlight"><?= htmlspecialchars($name) ?></span>
        </h1>
        <p class="db-greeting-sub">Here's your health summary for today.</p>
        <div class="db-date-pill">📅 <?= $todayLabel ?></div>
    </div>

    <div class="db-banner-right">
        <div class="db-banner-illus">🩺</div>
        <div class="db-banner-status">
            <?= $bsEmoji ?> <?= $bsStatus ?>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════
     QUICK ACTIONS STRIP — replaces dropdown
     ═══════════════════════════════════════════════════════ -->
<div class="db-qa-strip">

    <a href="/diabetrack/public/patient/bloodsugar" class="db-qa-link">
        <div class="db-qa-bubble">🩸</div>
        <div>
            <div class="db-qa-label">Blood Sugar</div>
            <div class="db-qa-sub">Log a reading</div>
        </div>
    </a>

    <a href="/diabetrack/public/patient/medication" class="db-qa-link">
        <div class="db-qa-bubble">💊</div>
        <div>
            <div class="db-qa-label">Medication</div>
            <div class="db-qa-sub">Today's schedule</div>
        </div>
    </a>

    <a href="/diabetrack/public/patient/meals" class="db-qa-link">
        <div class="db-qa-bubble">🥗</div>
        <div>
            <div class="db-qa-label">Meals</div>
            <div class="db-qa-sub">Log food intake</div>
        </div>
    </a>

    <a href="/diabetrack/public/patient/activity" class="db-qa-link">
        <div class="db-qa-bubble">🚶</div>
        <div>
            <div class="db-qa-label">Activity</div>
            <div class="db-qa-sub">Track exercise</div>
        </div>
    </a>

</div>

<!-- ═══════════════════════════════════════════════════════
     STAT CARDS — asymmetric grid (unchanged logic)
     ═══════════════════════════════════════════════════════ -->
<div class="db-section-heading">Today's Overview</div>
<div class="db-stats-grid">

    <!-- Card 1: tall left — latest blood sugar -->
    <div class="db-stat-card card-coral card-pos-1" data-illus="🩸">
        <div class="db-stat-icon">🩸</div>
        <div class="db-stat-body">
            <div class="db-stat-val">
                <?= isset($latestBloodSugar)
                    ? $latestBloodSugar . '<small>mg/dL</small>'
                    : '—' ?>
            </div>
            <div class="db-stat-label">Latest Blood Sugar</div>
            <div class="db-stat-badge">
                <?= isset($latestBloodSugar) ? ($bsEmoji . ' ' . ($latestBloodSugarStatus ?? 'Normal')) : 'No logs yet' ?>
            </div>
        </div>
    </div>

    <!-- Card 2: wide top-right — medications -->
    <div class="db-stat-card card-terra card-pos-2" data-illus="💊">
        <div class="db-stat-icon">💊</div>
        <div class="db-stat-body">
            <div class="db-stat-val">
                <?= isset($medsToday) ? $medsToday : '—' ?>
            </div>
            <div class="db-stat-label">Medications Today</div>
            <div class="db-stat-badge">
                <?= isset($medsToday) ? '✓ Logged' : 'No schedule' ?>
            </div>
        </div>
    </div>

    <!-- Cards 3 & 4: bottom-right pair -->
    <div class="db-card-bottom-right">

        <div class="db-stat-card card-ember" data-illus="🥗">
            <div class="db-stat-icon">🥗</div>
            <div class="db-stat-body">
                <div class="db-stat-val">
                    <?= isset($carbsToday) ? $carbsToday . '<small>g</small>' : '—' ?>
                </div>
                <div class="db-stat-label">Carbs Today</div>
                <div class="db-stat-badge">
                    <?= isset($carbsToday) ? '🍽️ Logged' : 'No meals yet' ?>
                </div>
            </div>
        </div>

        <div class="db-stat-card card-blush" data-illus="🚶">
            <div class="db-stat-icon">🚶</div>
            <div class="db-stat-body">
                <div class="db-stat-val">
                    <?= isset($activityToday) ? $activityToday . '<small>min</small>' : '—' ?>
                </div>
                <div class="db-stat-label">Activity Today</div>
                <div class="db-stat-badge">
                    <?= isset($activityToday) ? '🏃 Active' : 'No activity' ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>