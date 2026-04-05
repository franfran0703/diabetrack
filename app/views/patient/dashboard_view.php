<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

ob_start();

$hour = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');
?>

<link href="/diabetrack/public/assets/css/dashboard.css?<?= time() ?>" rel="stylesheet">

<!-- ── TOP ROW: Greeting + Quick Actions ─────────────── -->
<div class="db-top-row">

    <!-- GREETING -->
    <div class="db-greeting">
        <div class="db-greeting-eyebrow"><?= $timeGreeting ?></div>
        <h1 class="db-greeting-name">
            Greetings, <span class="name-highlight"><?= htmlspecialchars($name) ?></span> 
        </h1>
        <p class="db-greeting-sub">Here's your health summary for today.</p>
        <div class="db-date-pill">📅 <?= $todayLabel ?></div>
    </div>

    <!-- QUICK ACTIONS — top right -->
    <div class="db-qa-wrap">
        <button class="db-qa-trigger" id="qaTrigger" onclick="toggleQA()">
            <span class="qa-trigger-icon">+</span>
            Quick Actions
        </button>
        <div class="db-qa-items" id="qaItems">
            <a href="/diabetrack/public/patient/bloodsugar" class="db-qa-item">
                <div class="db-qa-bubble">🩸</div>
                <div class="db-qa-label">Blood Sugar</div>
            </a>
            <a href="/diabetrack/public/patient/medication" class="db-qa-item">
                <div class="db-qa-bubble">💊</div>
                <div class="db-qa-label">Medication</div>
            </a>
            <a href="/diabetrack/public/patient/meals" class="db-qa-item">
                <div class="db-qa-bubble">🥗</div>
                <div class="db-qa-label">Meals</div>
            </a>
            <a href="/diabetrack/public/patient/activity" class="db-qa-item">
                <div class="db-qa-bubble">🚶</div>
                <div class="db-qa-label">Activity</div>
            </a>
        </div>
    </div>

</div>

<!-- ── STAT CARDS — asymmetric grid (matches wireframe) ─ -->
<div class="db-section-heading">Today's Overview</div>
<div class="db-stats-grid">

    <!-- Card 1: tall left — most important -->
    <div class="db-stat-card card-coral card-pos-1" data-illus="🩸">
        <div class="db-stat-icon">🩸</div>
        <div class="db-stat-body">
            <div class="db-stat-val">
                <?= isset($latestBloodSugar) ? $latestBloodSugar . '<small>mg/dL</small>' : '—' ?>
            </div>
            <div class="db-stat-label">Latest Blood Sugar</div>
            <div class="db-stat-badge">
                <?= isset($latestBloodSugar) ? '🔵 Normal' : 'No logs yet' ?>
            </div>
        </div>
    </div>

    <!-- Card 2: wide top-right -->
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

    <!-- Cards 3 & 4: bottom-right pair in sub-grid -->
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

<script>
function toggleQA() {
    const trigger = document.getElementById('qaTrigger');
    const items   = document.getElementById('qaItems');
    const isOpen  = items.classList.contains('open');

    if (isOpen) {
        items.classList.remove('open');
        trigger.classList.remove('open');
    } else {
        items.classList.add('open');
        trigger.classList.add('open');
    }
}

// Close on outside click
document.addEventListener('click', (e) => {
    const wrap = document.querySelector('.db-qa-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('qaItems').classList.remove('open');
        document.getElementById('qaTrigger').classList.remove('open');
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>