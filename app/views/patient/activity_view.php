<?php
$pageTitle  = 'Activity Monitor';
$activeMenu = 'activity';
ob_start();

// Chart data — last 7 days
$chartLabels = [];
$chartData   = [];

// Fill all 7 days even if no activity
$last7Map = [];
foreach ($last7Days as $d) {
    $last7Map[$d['log_date']] = $d['total_minutes'];
}
for ($i = 6; $i >= 0; $i--) {
    $date            = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[]   = date('M d', strtotime($date));
    $chartData[]     = $last7Map[$date] ?? 0;
}

// Ring progress — WHO recommends 30 min/day
$todayMins   = (int)($todayTotals['total_minutes'] ?? 0);
$ringGoal    = 30;
$ringPct     = min($todayMins / $ringGoal, 1);
$circumference = 2 * M_PI * 54; // radius 54
$dashOffset  = $circumference * (1 - $ringPct);
?>

<link href="/diabetrack/public/assets/css/activity.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="act-header">
    <h1>🏃 Activity Monitor</h1>
    <p>Track your daily physical activities and stay active.</p>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
<div class="act-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="act-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- UNIQUE STATS — Ring + Side Cards -->
<div class="act-stats-wrap">

    <!-- Ring — today's minutes with progress -->
    <div class="act-ring-panel">
        <div style="position:relative;width:140px;height:140px;">
            <svg class="act-ring-svg" viewBox="0 0 120 120">
                <circle class="act-ring-track" cx="60" cy="60" r="54"/>
                <circle class="act-ring-fill" cx="60" cy="60" r="54"
                    id="ringFill"
                    style="stroke-dasharray:<?= $circumference ?>;stroke-dashoffset:<?= $dashOffset ?>;">
                </circle>
            </svg>
            <div class="act-ring-center">
                <div class="act-ring-val"><?= $todayMins ?></div>
                <div class="act-ring-unit">MIN</div>
            </div>
        </div>
        <div class="act-ring-label">Active Minutes Today</div>
        <div class="act-ring-badge">
            <?= $todayMins >= $ringGoal ? '🎯 Goal Reached!' : '🎯 Goal: ' . $ringGoal . ' min' ?>
        </div>
    </div>

    <!-- Side cards — 2x2 grid -->
    <div class="act-side-stats">
        <div class="act-side-card card-a" data-illus="🏋️">
            <div class="act-side-icon">🏋️</div>
            <div>
                <div class="act-side-val"><?= $todayTotals['total_activities'] ?? 0 ?></div>
                <div class="act-side-label">Activities Today</div>
            </div>
        </div>
        <div class="act-side-card card-b" data-illus="📅">
            <div class="act-side-icon">📅</div>
            <div>
                <div class="act-side-val"><?= $weekTotals['week_activities'] ?? 0 ?></div>
                <div class="act-side-label">Activities This Week</div>
            </div>
        </div>
        <div class="act-side-card card-c" data-illus="⏱️">
            <div class="act-side-icon">⏱️</div>
            <div>
                <div class="act-side-val">
                    <?= $weekTotals['week_minutes'] ?? 0 ?><small>min</small>
                </div>
                <div class="act-side-label">Minutes This Week</div>
            </div>
        </div>
        <div class="act-side-card card-d" data-illus="🔥">
            <div class="act-side-icon">🔥</div>
            <div>
                <?php
                // Rough calorie estimate: 5 cal/min light, 8 moderate, 12 intense
                $cals = 0;
                foreach ($todayLogs as $l) {
                    $mult = $l['intensity'] === 'Light' ? 5 :
                           ($l['intensity'] === 'Moderate' ? 8 : 12);
                    $cals += $l['duration_minutes'] * $mult;
                }
                ?>
                <div class="act-side-val"><?= $cals ?><small>cal</small></div>
                <div class="act-side-label">Est. Calories Burned</div>
            </div>
        </div>
    </div>

</div>

<!-- CHART + TODAY SPLIT -->
<div class="act-main-grid">

    <!-- WEEKLY CHART -->
    <div class="act-chart-card">
        <div class="act-section-label">Last 7 Days — Active Minutes</div>
        <canvas id="actChart" height="120"></canvas>
    </div>

    <!-- TODAY'S ACTIVITIES -->
    <div class="act-today-card">
        <div class="act-section-label">Today's Activities</div>

        <?php if (empty($todayLogs)): ?>
        <div class="act-empty">
            <div class="act-empty-icon">🏃</div>
            <p>No activities logged today.</p>
            <span>Stay active — log your first activity!</span>
        </div>
        <?php else: ?>
        <?php foreach ($todayLogs as $log): ?>
        <div class="act-item">
            <div class="act-item-icon">
                <?php
                $icons = [
                    'Walking' => '🚶', 'Running' => '🏃', 'Cycling' => '🚴',
                    'Swimming' => '🏊', 'Exercise' => '🏋️', 'Yoga' => '🧘',
                    'Dancing' => '💃', 'Sports' => '⚽'
                ];
                $icon = '🏃';
                foreach ($icons as $key => $val) {
                    if (stripos($log['activity_name'], $key) !== false) {
                        $icon = $val;
                        break;
                    }
                }
                echo $icon;
                ?>
            </div>
            <div style="flex:1;">
                <div class="act-item-name"><?= htmlspecialchars($log['activity_name']) ?></div>
                <div class="act-item-meta">
                    <?= date('h:i A', strtotime($log['logged_at'])) ?>
                    <?php if ($log['notes']): ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($log['notes']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <span class="act-intensity <?= strtolower($log['intensity']) ?>">
                <?= $log['intensity'] ?>
            </span>
            <div class="act-item-duration">
                <?= $log['duration_minutes'] ?>
                <small>minutes</small>
            </div>
            <a href="/diabetrack/public/patient/activity?delete=<?= $log['id'] ?>"
               onclick="return confirm('Delete this activity?')"
               class="act-del-btn">🗑</a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- FULL LOG TABLE -->
<div class="act-table-card">
    <div class="act-section-label">All Activity History</div>

    <?php if (empty($logs)): ?>
    <div class="act-empty">
        <div class="act-empty-icon">📜</div>
        <p>No activity history yet.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="act-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Duration</th>
                    <th>Intensity</th>
                    <th>Notes</th>
                    <th>Date & Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="act-table-name"><?= htmlspecialchars($log['activity_name']) ?></td>
                    <td class="act-table-val"><?= $log['duration_minutes'] ?> min</td>
                    <td>
                        <span class="act-intensity <?= strtolower($log['intensity']) ?>">
                            <?= $log['intensity'] ?>
                        </span>
                    </td>
                    <td class="act-table-muted"><?= $log['notes'] ? htmlspecialchars($log['notes']) : '—' ?></td>
                    <td class="act-table-muted" style="white-space:nowrap;">
                        <?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?>
                    </td>
                    <td>
                        <a href="/diabetrack/public/patient/activity?delete=<?= $log['id'] ?>"
                           onclick="return confirm('Delete this activity?')"
                           class="act-del-btn">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- LOG ACTIVITY MODAL -->
<div id="actModal" class="act-modal-overlay">
    <div class="act-modal">
        <button class="act-modal-close" onclick="closeModal('actModal')">✕</button>
        <div class="act-modal-title">🏃 Log Activity</div>
        <div class="act-modal-sub">Record your physical activity for today.</div>

        <form method="POST" action="/diabetrack/public/patient/activity">

            <!-- Quick activity type -->
            <div class="act-form-group">
                <label class="act-form-label">Quick Select</label>
                <div class="act-quick-grid">
                    <?php
                    $quickActivities = [
                        ['icon' => '🚶', 'name' => 'Walking'],
                        ['icon' => '🏃', 'name' => 'Running'],
                        ['icon' => '🚴', 'name' => 'Cycling'],
                        ['icon' => '🏊', 'name' => 'Swimming'],
                        ['icon' => '🏋️', 'name' => 'Exercise'],
                        ['icon' => '🧘', 'name' => 'Yoga'],
                        ['icon' => '💃', 'name' => 'Dancing'],
                        ['icon' => '⚽', 'name' => 'Sports'],
                    ];
                    foreach ($quickActivities as $qa): ?>
                    <button type="button"
                            class="act-quick-btn"
                            onclick="selectActivity('<?= $qa['name'] ?>', this)">
                        <span><?= $qa['icon'] ?></span>
                        <?= $qa['name'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Activity name -->
            <div class="act-form-group">
                <label class="act-form-label">Activity Name</label>
                <input type="text" name="activity_name" id="activity-name-input"
                       class="act-form-input"
                       placeholder="e.g. Morning Walk, Gym Session"
                       required>
            </div>

            <!-- Duration + Intensity -->
            <div class="act-form-grid-2 act-form-group">
                <div>
                    <label class="act-form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes"
                           class="act-form-input"
                           placeholder="e.g. 30" min="1" required>
                </div>
                <div>
                    <label class="act-form-label">Intensity</label>
                    <div class="act-intensity-grid" style="margin-bottom:0;">
                        <button type="button" class="act-intensity-btn selected"
                                onclick="selectIntensity('Light', this)">
                            <span>🟢</span> Light
                        </button>
                        <button type="button" class="act-intensity-btn"
                                onclick="selectIntensity('Moderate', this)">
                            <span>🟡</span> Moderate
                        </button>
                        <button type="button" class="act-intensity-btn"
                                onclick="selectIntensity('Intense', this)">
                            <span>🔴</span> Intense
                        </button>
                    </div>
                    <input type="hidden" name="intensity" id="intensity-input" value="Light">
                </div>
            </div>

            <!-- Notes -->
            <div class="act-form-group">
                <label class="act-form-label">Notes <span style="font-size:0.65rem;color:rgba(184,146,126,0.6);font-weight:600;text-transform:lowercase;"> optional</span></label>
                <textarea name="notes" class="act-form-textarea" rows="2"
                          placeholder="e.g. Morning jog around the park"></textarea>
            </div>

            <button type="submit" class="act-save-btn">🏃 Save Activity</button>
        </form>
    </div>
</div>

<button class="patient-fab" onclick="openModal('actModal')">
    <span class="patient-fab-icon">🏃</span>
    <span class="patient-fab-label">Log Activity</span>
</button>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('actChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Active Minutes',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(249,116,71,0.25)',
            borderColor: '#F97447',
            borderWidth: 2,
            borderRadius: 10,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(249,116,71,0.06)' },
                ticks: { font: { size: 11 }, color: '#c4714a' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 }, color: '#c4714a' }
            }
        }
    }
});

// Modal
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.act-modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Quick activity select
function selectActivity(name, btn) {
    document.querySelectorAll('.act-quick-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('activity-name-input').value = name;
}

// Intensity select
function selectIntensity(val, btn) {
    document.querySelectorAll('.act-intensity-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('intensity-input').value = val;
}

// Focus styles
document.querySelectorAll('.act-form-input, .act-form-select, .act-form-textarea').forEach(f => {
    f.addEventListener('focus', () => {
        f.style.borderColor = '#F97447';
        f.style.boxShadow   = '0 0 0 3px rgba(249,116,71,0.12)';
    });
    f.addEventListener('blur', () => {
        f.style.borderColor = 'rgba(249,116,71,0.22)';
        f.style.boxShadow   = 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>