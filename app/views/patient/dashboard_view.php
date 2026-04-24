<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

ob_start();

$hour         = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$todayLabel   = date('l, F j, Y');

// ── Blood sugar ──────────────────────────────────────────
// Controller passes: $latestBloodSugar, $latestBloodSugarStatus, $last7
$bsEmoji  = '📊';
$bsStatus = '—';
if (isset($latestBloodSugar, $latestBloodSugarStatus)) {
    $bsEmoji  = $latestBloodSugarStatus === 'High' ? '🔴' :
               ($latestBloodSugarStatus === 'Low'  ? '🟡' : '🟢');
    $bsStatus = $latestBloodSugarStatus . ' · ' . $latestBloodSugar . ' mg/dL';
}

// Compute High/Low/Normal counts from $last7 (controller passes this)
$bsHigh   = 0; $bsLow = 0; $bsNormal = 0;
foreach ($last7 ?? [] as $log) {
    if ($log['status'] === 'High')       $bsHigh++;
    elseif ($log['status'] === 'Low')    $bsLow++;
    else                                  $bsNormal++;
}

$bsLabels = [];
$bsData   = [];
$bsColors = [];
foreach ($last7 ?? [] as $log) {
    $bsLabels[] = date('M d h:i A', strtotime($log['logged_at']));
    $bsData[]   = (float) $log['reading'];
    $bsColors[] = $log['status'] === 'High' ? '#ef4444' :
                 ($log['status'] === 'Low'  ? '#f59e0b' : '#22c55e');
}

// ── Medications ──────────────────────────────────────────
// Controller passes: $medications, $todayLogs, $todayStats, $loggedToday
$medTotal   = count($medications ?? []);
$medTaken   = $todayStats['taken']  ?? 0;
$medMissed  = $todayStats['missed'] ?? 0;
$medPending = max(0, $medTotal - $medTaken - $medMissed);
$medPct     = $medTotal > 0 ? round(($medTaken / $medTotal) * 100) : 0;
$medCircum  = round(2 * M_PI * 34, 4);
$medDash    = round($medCircum * ($medPct / 100), 2);

// ── Meals / Nutrition ────────────────────────────────────
// Controller passes: $todayTotals  (has total_carbs, total_calories, total_sugar, total_protein, total_fat, total_fiber)
$carbs    = (float) ($todayTotals['total_carbs']    ?? 0);
$calories = (float) ($todayTotals['total_calories'] ?? 0);
$sugar    = (float) ($todayTotals['total_sugar']    ?? 0);
$protein  = (float) ($todayTotals['total_protein']  ?? 0);
$fat      = (float) ($todayTotals['total_fat']      ?? 0);
$fiber    = (float) ($todayTotals['total_fiber']    ?? 0);
$carbPct  = $carbs > 0 ? min(round($carbs / 130 * 100), 100) : 0;
$hasNutrition = $carbs > 0;

// ── Activity ─────────────────────────────────────────────
// Controller passes: $activityToday (total minutes int|null), $last7Days
$actMinutes = (int) ($activityToday ?? 0);
$actGoal    = 60;
$actPct     = $actMinutes > 0 ? min(round($actMinutes / $actGoal * 100), 100) : 0;
$actCircum  = round(2 * M_PI * 52, 4);
$actDash    = round($actCircum * ($actPct / 100), 2);
// $todayLogs from the controller is med logs — activity today logs not separately passed,
// so we derive session count from activityToday being set
?>

<link href="/diabetrack/public/assets/css/dashboard.css?<?= time() ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ══ BANNER ═══════════════════════════════════════════ -->
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
        <div class="db-banner-status"><?= $bsEmoji ?> <?= $bsStatus ?></div>
    </div>
</div>

<!-- ══ QUICK ACTIONS ════════════════════════════════════ -->
<div class="db-qa-strip">
    <a href="/diabetrack/public/patient/bloodsugar" class="db-qa-link">
        <div class="db-qa-bubble">🩸</div>
        <div><div class="db-qa-label">Blood Sugar</div><div class="db-qa-sub">Log a reading</div></div>
    </a>
    <a href="/diabetrack/public/patient/medication" class="db-qa-link">
        <div class="db-qa-bubble">💊</div>
        <div><div class="db-qa-label">Medication</div><div class="db-qa-sub">Today's schedule</div></div>
    </a>
    <a href="/diabetrack/public/patient/meals" class="db-qa-link">
        <div class="db-qa-bubble">🥗</div>
        <div><div class="db-qa-label">Meals</div><div class="db-qa-sub">Log food intake</div></div>
    </a>
    <a href="/diabetrack/public/patient/activity" class="db-qa-link">
        <div class="db-qa-bubble">🚶</div>
        <div><div class="db-qa-label">Activity</div><div class="db-qa-sub">Track exercise</div></div>
    </a>
</div>

<!-- ══ STAT CARDS ════════════════════════════════════════ -->
<div class="db-section-heading">Today's Overview</div>
<div class="db-stats-grid">

    <!-- Card 1: Blood Sugar -->
    <div class="db-stat-card card-coral card-pos-1" data-illus="🩸"
         onclick="openModal('modal-bs')" role="button" tabindex="0">
        <div class="db-stat-icon">🩸</div>
        <div class="db-stat-body">
            <div class="db-stat-val">
                <?= isset($latestBloodSugar) ? $latestBloodSugar . '<small>mg/dL</small>' : '—' ?>
            </div>
            <div class="db-stat-label">Latest Blood Sugar</div>
            <div class="db-stat-badge">
                <?= isset($latestBloodSugar) ? ($bsEmoji . ' ' . ($latestBloodSugarStatus ?? 'Normal')) : 'No logs yet' ?>
            </div>
        </div>
        <div class="db-card-hint">Tap to view trend →</div>
    </div>

    <!-- Card 2: Medication -->
    <div class="db-stat-card card-terra card-pos-2" data-illus="💊"
         onclick="openModal('modal-med')" role="button" tabindex="0">
        <div class="db-stat-icon">💊</div>
        <div class="db-stat-body">
            <div class="db-stat-val"><?= $medTaken ?><small>/<?= $medTotal ?></small></div>
            <div class="db-stat-label">Doses Taken Today</div>
            <div class="db-stat-badge">
                <?= $medPending > 0 ? "⏳ {$medPending} pending" : ($medTotal > 0 ? '✅ All done' : 'No schedule') ?>
            </div>
        </div>
        <div class="db-card-hint">Tap for schedule →</div>
    </div>

    <!-- Cards 3 & 4 -->
    <div class="db-card-bottom-right">

        <div class="db-stat-card card-ember" data-illus="🥗"
             onclick="openModal('modal-nut')" role="button" tabindex="0">
            <div class="db-stat-icon">🥗</div>
            <div class="db-stat-body">
                <div class="db-stat-val">
                    <?= $carbs > 0 ? round($carbs) . '<small>g</small>' : '—' ?>
                </div>
                <div class="db-stat-label">Carbs Today</div>
                <div class="db-stat-badge">
                    <?= $carbs > 0 ? '🍽️ Logged' : 'No meals yet' ?>
                </div>
            </div>
            <div class="db-card-hint">Tap for nutrition →</div>
        </div>

        <div class="db-stat-card card-blush" data-illus="🚶"
             onclick="openModal('modal-act')" role="button" tabindex="0">
            <div class="db-stat-icon">🚶</div>
            <div class="db-stat-body">
                <div class="db-stat-val">
                    <?= $actMinutes > 0 ? $actMinutes . '<small>min</small>' : '—' ?>
                </div>
                <div class="db-stat-label">Activity Today</div>
                <div class="db-stat-badge">
                    <?= $actMinutes > 0 ? '🏃 Active' : 'No activity' ?>
                </div>
            </div>
            <div class="db-card-hint">Tap for details →</div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     MODAL 1 — BLOOD SUGAR TREND
     Uses: $last7 (controller variable)
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-bs" onclick="overlayClose(event,'modal-bs')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon coral">🩸</div>
        <div>
          <div class="db-modal-title">Blood Sugar Trend</div>
          <div class="db-modal-subtitle">Last 7 readings</div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-bs')">✕</button>
    </div>
    <div class="db-modal-body">

      <?php if (!empty($last7)): ?>
      <div class="db-bs-zones">
        <div class="db-bs-zone"><div class="db-bs-zone-dot" style="background:#22c55e;"></div> Normal (70–180)</div>
        <div class="db-bs-zone"><div class="db-bs-zone-dot" style="background:#ef4444;"></div> High (&gt;180)</div>
        <div class="db-bs-zone"><div class="db-bs-zone-dot" style="background:#f59e0b;"></div> Low (&lt;70)</div>
      </div>
      <div class="db-bs-chart-wrap">
        <canvas id="bsModalChart" height="140"></canvas>
      </div>
      <div class="db-bs-stat-row">
        <div class="db-bs-stat">
          <div class="db-bs-stat-val" style="color:#22c55e;"><?= $bsNormal ?></div>
          <div class="db-bs-stat-label">Normal</div>
        </div>
        <div class="db-bs-stat">
          <div class="db-bs-stat-val" style="color:#ef4444;"><?= $bsHigh ?></div>
          <div class="db-bs-stat-label">High</div>
        </div>
        <div class="db-bs-stat">
          <div class="db-bs-stat-val" style="color:#f59e0b;"><?= $bsLow ?></div>
          <div class="db-bs-stat-label">Low</div>
        </div>
      </div>
      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon">📈</div>
        <div class="db-modal-empty-text">No readings yet — log your first one!</div>
        <a href="/diabetrack/public/patient/bloodsugar" class="db-modal-link">Log a Reading →</a>
      </div>
      <?php endif; ?>

      <a href="/diabetrack/public/patient/bloodsugar" class="db-modal-link" style="margin-top:16px;">View all readings →</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     MODAL 2 — MEDICATION SCHEDULE
     Uses: $medications, $todayLogs, $todayStats, $loggedToday
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-med" onclick="overlayClose(event,'modal-med')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon terra">💊</div>
        <div>
          <div class="db-modal-title">Today's Medications</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?></div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-med')">✕</button>
    </div>
    <div class="db-modal-body">

      <?php if ($medTotal > 0): ?>
      <!-- Progress ring -->
      <div class="db-med-progress">
        <div class="db-med-ring">
          <svg viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="34" fill="none" stroke="#FDE8DC" stroke-width="7"/>
            <circle cx="40" cy="40" r="34" fill="none" stroke="#F97447" stroke-width="7"
              stroke-linecap="round"
              stroke-dasharray="<?= $medDash ?> <?= $medCircum ?>"/>
          </svg>
          <div class="db-med-ring-label"><?= $medPct ?>%</div>
        </div>
        <div class="db-med-progress-info">
          <div class="db-med-progress-title"><?= $medTaken ?> of <?= $medTotal ?> doses taken</div>
          <div class="db-med-progress-sub">
            <?php if ($medMissed > 0): ?>⚠️ <?= $medMissed ?> missed · <?= $medPending ?> pending
            <?php elseif ($medPending > 0): ?>⏳ <?= $medPending ?> still pending
            <?php else: ?>✅ All doses completed!
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Med list — uses $medications + $todayLogs + $loggedToday from controller -->
      <?php foreach ($medications as $med):
        $logStatus = null;
        $logTime   = null;
        foreach ($todayLogs as $tl) {
            // $todayLogs rows have medication_id or name to match
            if (($tl['medication_id'] ?? null) == $med['id'] ||
                ($tl['name'] ?? '') === $med['name']) {
                $logStatus = $tl['status'];
                $logTime   = $tl['logged_at'];
                break;
            }
        }
        $logged   = $loggedToday[$med['id']] ?? false;
        $dotClass = $logged ? strtolower($logStatus ?? 'taken') : 'pending';
        $pillText = $logged ? ucfirst($logStatus ?? 'Taken') : 'Pending';
      ?>
      <div class="db-med-item">
        <div class="db-med-dot <?= $dotClass ?>"></div>
        <div style="flex:1;">
          <div class="db-med-name"><?= htmlspecialchars($med['name']) ?></div>
          <div class="db-med-meta">
            💊 <?= htmlspecialchars($med['dosage']) ?>
            · 🕐 <?= date('h:i A', strtotime($med['schedule_time'])) ?>
            · <?= htmlspecialchars($med['frequency']) ?>
            <?php if ($logTime): ?> · Logged <?= date('h:i A', strtotime($logTime)) ?><?php endif; ?>
          </div>
        </div>
        <span class="db-med-pill <?= $dotClass ?>"><?= $pillText ?></span>
      </div>
      <?php endforeach; ?>

      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon">💊</div>
        <div class="db-modal-empty-text">No medications scheduled yet.</div>
        <a href="/diabetrack/public/patient/medication" class="db-modal-link">Set up schedule →</a>
      </div>
      <?php endif; ?>

      <a href="/diabetrack/public/patient/medication" class="db-modal-link" style="margin-top:16px;">Full schedule →</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     MODAL 3 — NUTRITION BREAKDOWN
     Uses: $todayTotals (controller variable)
     Keys: total_carbs, total_calories, total_sugar, total_protein, total_fat, total_fiber
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-nut" onclick="overlayClose(event,'modal-nut')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon ember">🥗</div>
        <div>
          <div class="db-modal-title">Today's Nutrition</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?></div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-nut')">✕</button>
    </div>
    <div class="db-modal-body">

      <?php if ($hasNutrition): ?>
      <!-- Carb hero -->
      <div class="db-nut-hero">
        <div>
          <div class="db-nut-hero-val"><?= round($carbs) ?><small>g</small></div>
          <div class="db-nut-hero-label">Carbohydrates</div>
          <div class="db-nut-bar-track" style="width:200px;margin-top:10px;">
            <div class="db-nut-bar-fill" style="width:<?= $carbPct ?>%;"></div>
          </div>
        </div>
        <div class="db-nut-hero-right">
          <div class="db-nut-pct"><?= $carbPct ?>%</div>
          <div class="db-nut-limit">of 130g limit</div>
        </div>
      </div>

      <!-- Nutrient rows -->
      <?php
      $nutrients = [
        ['icon'=>'🔥','name'=>'Calories','val'=>round($calories),'unit'=>'kcal','max'=>2000,'color'=>'#F97447'],
        ['icon'=>'🍬','name'=>'Sugar',   'val'=>round($sugar),   'unit'=>'g',   'max'=>50,  'color'=>'#ef4444'],
        ['icon'=>'🥩','name'=>'Protein', 'val'=>round($protein), 'unit'=>'g',   'max'=>60,  'color'=>'#22c55e'],
        ['icon'=>'🧈','name'=>'Fat',     'val'=>round($fat),     'unit'=>'g',   'max'=>65,  'color'=>'#f59e0b'],
        ['icon'=>'🌾','name'=>'Fiber',   'val'=>round($fiber),   'unit'=>'g',   'max'=>25,  'color'=>'#86efac'],
      ];
      foreach ($nutrients as $n):
        $pct = $n['max'] > 0 ? min(round($n['val'] / $n['max'] * 100), 100) : 0;
      ?>
      <div class="db-nut-row">
        <div class="db-nut-icon"><?= $n['icon'] ?></div>
        <div class="db-nut-name"><?= $n['name'] ?></div>
        <div class="db-nut-mini-track">
          <div class="db-nut-mini-fill" style="width:<?= $pct ?>%;background:<?= $n['color'] ?>;opacity:0.75;"></div>
        </div>
        <div class="db-nut-val"><?= $n['val'] ?><?= $n['unit'] ?></div>
      </div>
      <?php endforeach; ?>

      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon">🍽️</div>
        <div class="db-modal-empty-text">No meals logged today yet.</div>
        <a href="/diabetrack/public/patient/meals" class="db-modal-link">Log a meal →</a>
      </div>
      <?php endif; ?>

      <a href="/diabetrack/public/patient/meals" class="db-modal-link" style="margin-top:16px;">Full meal log →</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════
     MODAL 4 — ACTIVITY SUMMARY
     Uses: $activityToday (minutes), $last7Days (weekly data)
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-act" onclick="overlayClose(event,'modal-act')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon blush">🏃</div>
        <div>
          <div class="db-modal-title">Activity Summary</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?></div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-act')">✕</button>
    </div>
    <div class="db-modal-body">

      <!-- Activity ring + stats -->
      <div class="db-act-ring-wrap">
        <div class="db-act-ring">
          <svg viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="52" fill="none" stroke="#FDE8DC" stroke-width="10"/>
            <circle cx="60" cy="60" r="52" fill="none" stroke="#c04a20" stroke-width="10"
              stroke-linecap="round"
              stroke-dasharray="<?= $actDash ?> <?= $actCircum ?>"/>
          </svg>
          <div class="db-act-ring-center">
            <div class="db-act-ring-val"><?= $actPct ?>%</div>
            <div class="db-act-ring-unit">of goal</div>
          </div>
        </div>
        <div class="db-act-ring-stats">
          <div class="db-act-stat">
            <div class="db-act-stat-icon">⏱️</div>
            <div>
              <div class="db-act-stat-val"><?= $actMinutes ?> min</div>
              <div class="db-act-stat-label">Active today</div>
            </div>
          </div>
          <div class="db-act-stat">
            <div class="db-act-stat-icon">🎯</div>
            <div>
              <div class="db-act-stat-val"><?= $actGoal ?> min</div>
              <div class="db-act-stat-label">Daily goal</div>
            </div>
          </div>
          <?php
          // Weekly total from $last7Days — sum up minutes
          $weekTotal = 0;
          foreach ($last7Days ?? [] as $day) {
              $weekTotal += (int)($day['total_minutes'] ?? $day['minutes'] ?? 0);
          }
          ?>
          <div class="db-act-stat">
            <div class="db-act-stat-icon">📅</div>
            <div>
              <div class="db-act-stat-val"><?= $weekTotal ?> min</div>
              <div class="db-act-stat-label">This week</div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($actMinutes > 0): ?>
      <!-- Weekly bar chart from $last7Days -->
      <?php if (!empty($last7Days)): ?>
      <div class="db-bs-chart-wrap" style="margin-top:0;">
        <div style="font-size:0.6rem;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#c4714a;margin-bottom:14px;">Last 7 Days</div>
        <div style="display:flex;align-items:flex-end;gap:8px;height:80px;">
          <?php
          $maxMin = max(array_map(fn($d) => (int)($d['total_minutes'] ?? $d['minutes'] ?? 0), $last7Days));
          $maxMin = max($maxMin, 1);
          foreach ($last7Days as $day):
            $mins    = (int)($day['total_minutes'] ?? $day['minutes'] ?? 0);
            $barPct  = round($mins / $maxMin * 100);
            $barH    = max(4, round($barPct * 0.72)); // max 72px
            $isToday = date('Y-m-d') === ($day['date'] ?? '');
          ?>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <div style="font-size:0.58rem;color:#b8927e;font-weight:700;"><?= $mins ?>m</div>
            <div style="width:100%;height:<?= $barH ?>px;background:<?= $isToday ? '#F97447' : '#FDE8DC' ?>;border-radius:6px 6px 0 0;border:1.5px solid <?= $isToday ? 'rgba(249,116,71,0.4)' : 'rgba(249,116,71,0.15)' ?>;transition:height 1s ease;"></div>
            <div style="font-size:0.55rem;color:#b8927e;font-weight:700;"><?= date('D', strtotime($day['date'] ?? 'today')) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon">🏃</div>
        <div class="db-modal-empty-text">No activity logged today yet.</div>
        <a href="/diabetrack/public/patient/activity" class="db-modal-link">Log activity →</a>
      </div>
      <?php endif; ?>

      <a href="/diabetrack/public/patient/activity" class="db-modal-link" style="margin-top:16px;">Full activity log →</a>
    </div>
  </div>
</div>

<!-- ══ SCRIPTS ═══════════════════════════════════════════ -->
<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function overlayClose(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.db-modal-overlay.open').forEach(m => {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

<?php if (!empty($last7)): ?>
// Build chart only on first open so canvas has dimensions
let bsChartBuilt = false;
document.getElementById('modal-bs').addEventListener('transitionend', function () {
    if (!bsChartBuilt && this.classList.contains('open')) {
        bsChartBuilt = true;
        new Chart(document.getElementById('bsModalChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($bsLabels) ?>,
                datasets: [{
                    label: 'Blood Sugar (mg/dL)',
                    data: <?= json_encode($bsData) ?>,
                    borderColor: '#F97447',
                    backgroundColor: 'rgba(249,116,71,0.07)',
                    borderWidth: 2.5,
                    pointBackgroundColor: <?= json_encode($bsColors) ?>,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a0800',
                        titleColor: '#fbab6e',
                        bodyColor: '#fff8f5',
                        borderColor: 'rgba(249,116,71,0.2)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: 'rgba(249,116,71,0.06)' },
                        ticks: { font: { size: 10 }, color: '#b8927e' },
                        border: { color: 'transparent' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: '#b8927e', maxRotation: 30 },
                        border: { color: 'transparent' }
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>  