<?php
$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';

ob_start();

$hour         = (int) date('H');
$timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName    = ucfirst(strtolower(explode(' ', trim($name))[0]));
$todayLabel   = date('l, F j, Y');

// ── Blood sugar ──────────────────────────────────────────
$bsStatusIcon  = 'ti-activity';
$bsStatusColor = 'status-normal';
if (isset($latestBloodSugar, $latestBloodSugarStatus)) {
    $bsStatusIcon  = $latestBloodSugarStatus === 'High' ? 'ti-alert-triangle' :
                    ($latestBloodSugarStatus === 'Low'  ? 'ti-alert-circle'   : 'ti-circle-check');
    $bsStatusColor = $latestBloodSugarStatus === 'High' ? 'status-high' :
                    ($latestBloodSugarStatus === 'Low'  ? 'status-low'   : 'status-normal');
}

// Last logged — time ago
$bsLastLoggedAgo = null;
if (!empty($last7)) {
    $diff = time() - strtotime($last7[count($last7) - 1]['logged_at']);
    if ($diff < 3600)      $bsLastLoggedAgo = round($diff / 60) . 'm ago';
    elseif ($diff < 86400) $bsLastLoggedAgo = round($diff / 3600) . 'h ago';
    else                   $bsLastLoggedAgo = round($diff / 86400) . 'd ago';
}

// Trend: compare last two readings
$bsTrend = 'stable';
if (count($last7 ?? []) >= 2) {
    $prev    = (float) $last7[count($last7) - 2]['reading'];
    $curr    = (float) $last7[count($last7) - 1]['reading'];
    $bsTrend = $curr > $prev + 5 ? 'up' : ($curr < $prev - 5 ? 'down' : 'stable');
}

$bsHigh = 0; $bsLow = 0; $bsNormal = 0;
foreach ($last7 ?? [] as $log) {
    if ($log['status'] === 'High')    $bsHigh++;
    elseif ($log['status'] === 'Low') $bsLow++;
    else                               $bsNormal++;
}
$bsLabels = []; $bsData = []; $bsColors = [];
foreach ($last7 ?? [] as $log) {
    $bsLabels[] = date('M d h:i A', strtotime($log['logged_at']));
    $bsData[]   = (float) $log['reading'];
    $bsColors[] = $log['status'] === 'High' ? '#ef4444' :
                 ($log['status'] === 'Low'  ? '#f59e0b' : '#22c55e');
}

// ── Medications ──────────────────────────────────────────
$medTotal    = count($medications ?? []);
$medTaken    = $todayStats['taken']  ?? 0;
$medMissed   = $todayStats['missed'] ?? 0;
$medPending  = max(0, $medTotal - $medTaken - $medMissed);
$medPct      = $medTotal > 0 ? round(($medTaken / $medTotal) * 100) : 0;
$medCircumLg = round(2 * M_PI * 34, 4);
$medDashLg   = round($medCircumLg * ($medPct / 100), 2);
$medCircum   = round(2 * M_PI * 26, 4);
$medDash     = round($medCircum * ($medPct / 100), 2);

// Next pending dose
$nextMedName = null; $nextMedTime = null; $nextMedUrgent = false;
foreach ($medications ?? [] as $med) {
    if ($loggedToday[$med['id']] ?? false) continue;
    $schedTime = strtotime(date('Y-m-d') . ' ' . $med['schedule_time']);
    $minsUntil = ($schedTime - time()) / 60;
    if ($minsUntil >= 0 && ($nextMedTime === null || $schedTime < $nextMedTime)) {
        $nextMedName   = $med['name'];
        $nextMedTime   = $schedTime;
        $nextMedUrgent = $minsUntil <= 30;
    }
}

// ── Meals / Nutrition ────────────────────────────────────
$carbs        = (float) ($todayTotals['total_carbs']    ?? 0);
$calories     = (float) ($todayTotals['total_calories'] ?? 0);
$sugar        = (float) ($todayTotals['total_sugar']    ?? 0);
$protein      = (float) ($todayTotals['total_protein']  ?? 0);
$fat          = (float) ($todayTotals['total_fat']      ?? 0);
$fiber        = (float) ($todayTotals['total_fiber']    ?? 0);
$carbPct      = $carbs > 0    ? min(round($carbs    / 130  * 100), 100) : 0;
$caloriesPct  = $calories > 0 ? min(round($calories / 2000 * 100), 100) : 0;
$hasNutrition = $carbs > 0;

// ── Activity ─────────────────────────────────────────────
$actMinutes = (int) ($activityToday ?? 0);
$actGoal    = 60;
$actPct     = $actMinutes > 0 ? min(round($actMinutes / $actGoal * 100), 100) : 0;
$actCircum  = round(2 * M_PI * 52, 4);
$actDash    = round($actCircum * ($actPct / 100), 2);
$weekTotal  = 0;
foreach ($last7Days ?? [] as $day) {
    $weekTotal += (int)($day['total_minutes'] ?? $day['minutes'] ?? 0);
}

// ── Overall day status (banner ribbon) ───────────────────
$dayStatus      = 'on-track';
$dayStatusLabel = 'On Track';
$dayStatusIcon  = 'ti-circle-check';
if (isset($latestBloodSugarStatus)) {
    if ($latestBloodSugarStatus === 'High') {
        $dayStatus = 'alert'; $dayStatusLabel = 'High Blood Sugar'; $dayStatusIcon = 'ti-alert-triangle';
    } elseif ($latestBloodSugarStatus === 'Low') {
        $dayStatus = 'watch'; $dayStatusLabel = 'Low Blood Sugar';  $dayStatusIcon = 'ti-alert-circle';
    }
}
if ($medMissed > 0 && $dayStatus === 'on-track') {
    $dayStatus = 'watch'; $dayStatusLabel = 'Missed Dose'; $dayStatusIcon = 'ti-pill';
}

// ── Smart next action ─────────────────────────────────────
$nextAction = null;
if (!isset($latestBloodSugar)) {
    $nextAction = ['icon'=>'ti-droplet-half-2','label'=>'Log your first blood sugar reading today','href'=>'/diabetrack/public/patient/bloodsugar','cta'=>'Log Reading','urgency'=>'high'];
} elseif ($nextMedUrgent && $nextMedName) {
    $minsLeft   = round(($nextMedTime - time()) / 60);
    $nextAction = ['icon'=>'ti-pill','label'=>$nextMedName . ' is due in ' . $minsLeft . ' min','href'=>'/diabetrack/public/patient/medication','cta'=>'Mark Taken','urgency'=>'urgent'];
} elseif ($nextMedName) {
    $nextAction = ['icon'=>'ti-pill','label'=>'Next dose: ' . $nextMedName . ' at ' . date('h:i A', $nextMedTime),'href'=>'/diabetrack/public/patient/medication','cta'=>'View Schedule','urgency'=>'normal'];
} elseif (!$hasNutrition) {
    $nextAction = ['icon'=>'ti-bowl','label'=>"You haven't logged any meals today",'href'=>'/diabetrack/public/patient/meals','cta'=>'Log Meal','urgency'=>'normal'];
} elseif ($actMinutes === 0) {
    $nextAction = ['icon'=>'ti-run','label'=>'No activity logged yet — stay active!','href'=>'/diabetrack/public/patient/activity','cta'=>'Log Activity','urgency'=>'low'];
} else {
    $nextAction = ['icon'=>'ti-circle-check','label'=>'Great job! All key health data logged today.','href'=>null,'cta'=>null,'urgency'=>'done'];
}

// ── Streak (controller should pass $streak) ───────────────
$streak = (int)($streak ?? 0);
?>

<link href="/diabetrack/public/assets/css/dashboard.css?<?= time() ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ══ BANNER ═══════════════════════════════════════════ -->
<div class="db-banner">
    <div class="db-banner-left">
        <div class="db-greeting-eyebrow"><?= $timeGreeting ?></div>
        <h1 class="db-greeting-name">
            Hello, <span class="name-highlight"><?= htmlspecialchars($firstName) ?></span>
        </h1>
        <p class="db-greeting-sub">Here's your health summary for today.</p>
        <div class="db-banner-meta">
            <div class="db-date-pill">
                <i class="ti ti-calendar-event"></i>
                <?= $todayLabel ?>
            </div>
            <div class="db-day-status <?= $dayStatus ?>">
                <i class="ti <?= $dayStatusIcon ?>"></i>
                <?= $dayStatusLabel ?>
            </div>
            <?php if ($streak > 0): ?>
            <div class="db-streak-pill">
                <i class="ti ti-flame"></i>
                <?= $streak ?>-day streak
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="db-banner-illus">
        <img src="/diabetrack/public/assets/img/diabetrack-icon.png"
             style="width:90px;height:auto;object-fit:contain;opacity:0.85;" alt="Diabetrack">
    </div>
</div>

<!-- ══ SMART NEXT ACTION ════════════════════════════════ -->
<?php if ($nextAction): ?>
<div class="db-next-action urgency-<?= $nextAction['urgency'] ?>">
    <div class="db-next-action-left">
        <div class="db-next-action-icon">
            <i class="ti <?= $nextAction['icon'] ?>"></i>
        </div>
        <div class="db-next-action-text">
            <span class="db-next-action-eyebrow">
                <?php if ($nextAction['urgency'] === 'urgent'): ?>
                    <i class="ti ti-alert-triangle"></i> Action needed
                <?php elseif ($nextAction['urgency'] === 'high'): ?>
                    <i class="ti ti-chevron-right"></i> Up next
                <?php elseif ($nextAction['urgency'] === 'done'): ?>
                    <i class="ti ti-circle-check"></i> All done
                <?php else: ?>
                    <i class="ti ti-chevron-right"></i> Reminder
                <?php endif; ?>
            </span>
            <?= htmlspecialchars($nextAction['label']) ?>
        </div>
    </div>
    <?php if ($nextAction['href'] && $nextAction['cta']): ?>
    <a href="<?= $nextAction['href'] ?>" class="db-next-action-btn">
        <?= $nextAction['cta'] ?> <i class="ti ti-arrow-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ STAT CARDS ════════════════════════════════════════ -->
<div class="db-section-heading">Today's Overview</div>
<div class="db-stats-grid">

    <!-- ── Blood Sugar — orange gradient, tall left ── -->
    <div class="db-stat-card card-coral card-pos-1"
         onclick="openModal('modal-bs')" role="button" tabindex="0"
         aria-label="View blood sugar trend">

        <div class="db-card-top-row">
            <div class="db-stat-icon">
                <i class="ti ti-droplet-half-2"></i>
            </div>
            <?php if (!empty($last7)): ?>
            <div class="db-trend-badge trend-<?= $bsTrend ?>">
                <i class="ti <?= $bsTrend === 'up' ? 'ti-trending-up' : ($bsTrend === 'down' ? 'ti-trending-down' : 'ti-minus') ?>"></i>
                <?= ucfirst($bsTrend) ?>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="db-stat-val">
                <?= isset($latestBloodSugar) ? $latestBloodSugar . '<small>mg/dL</small>' : '—' ?>
            </div>
            <div class="db-stat-label">Latest Blood Sugar</div>
            <div class="db-stat-badge">
                <i class="ti <?= $bsStatusIcon ?>"></i>
                <?= isset($latestBloodSugar) ? htmlspecialchars($latestBloodSugarStatus ?? 'Normal') : 'No logs yet' ?>
            </div>
            <?php if ($bsLastLoggedAgo): ?>
            <div class="db-freshness">
                <i class="ti ti-clock"></i> <?= $bsLastLoggedAgo ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($last7)): ?>
        <div class="db-bs-mini-summary">
            <div class="db-bs-mini-item">
                <span class="db-bs-mini-val" style="color:rgba(255,255,255,0.95);"><?= $bsNormal ?></span>
                <span class="db-bs-mini-label">Normal</span>
            </div>
            <div class="db-bs-mini-sep"></div>
            <div class="db-bs-mini-item">
                <span class="db-bs-mini-val" style="color:#fca5a5;"><?= $bsHigh ?></span>
                <span class="db-bs-mini-label">High</span>
            </div>
            <div class="db-bs-mini-sep"></div>
            <div class="db-bs-mini-item">
                <span class="db-bs-mini-val" style="color:#fde68a;"><?= $bsLow ?></span>
                <span class="db-bs-mini-label">Low</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="db-card-hint">View trend <i class="ti ti-arrow-right"></i></div>
    </div>

    <!-- ── Right column ── -->
    <div class="db-right-col">

        <!-- Medication -->
        <div class="db-stat-card card-flat card-med <?= $nextMedUrgent ? 'card-urgent' : '' ?>"
             onclick="openModal('modal-med')" role="button" tabindex="0"
             aria-label="View medication schedule">
            <?php if ($nextMedUrgent): ?>
            <div class="db-urgent-pulse" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="db-med-icon-box <?= $medMissed > 0 ? 'icon-alert' : ($medPct === 100 ? 'icon-done' : '') ?>">
                <i class="ti ti-pill"></i>
            </div>
            <div class="db-med-body">
                <div class="db-flat-title">Medications</div>
                <div class="db-flat-val"><?= $medTaken ?><small>/<?= $medTotal ?></small></div>
                <div class="db-flat-label">doses taken today</div>
                <div class="db-flat-badge <?= $medMissed > 0 ? 'badge-alert' : ($medPending > 0 ? 'badge-pending' : 'badge-done') ?>">
                    <?php if ($medMissed > 0): ?>
                        <i class="ti ti-alert-triangle"></i> <?= $medMissed ?> missed
                    <?php elseif ($nextMedUrgent && $nextMedName): ?>
                        <i class="ti ti-alarm"></i> Due soon!
                    <?php elseif ($medPending > 0): ?>
                        <i class="ti ti-clock"></i> <?= $medPending ?> pending
                    <?php elseif ($medTotal > 0): ?>
                        <i class="ti ti-circle-check"></i> All done
                    <?php else: ?>
                        <i class="ti ti-minus"></i> No schedule
                    <?php endif; ?>
                </div>
                <?php if ($nextMedName && !$nextMedUrgent): ?>
                <div class="db-flat-next">
                    <i class="ti ti-clock"></i> Next: <?= htmlspecialchars($nextMedName) ?> · <?= date('h:i A', $nextMedTime) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="db-med-ring-wrap" aria-hidden="true">
                <svg viewBox="0 0 64 64" width="64" height="64" style="transform:rotate(-90deg);">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="rgba(249,116,71,0.1)" stroke-width="6"/>
                    <circle cx="32" cy="32" r="26" fill="none" stroke="#F97447" stroke-width="6"
                        stroke-linecap="round"
                        stroke-dasharray="<?= $medDash ?> <?= $medCircum ?>"/>
                </svg>
                <div class="db-med-ring-label"><?= $medPct ?>%</div>
            </div>
        </div>

        <!-- Nutrition + Activity -->
        <div class="db-card-bottom-row">

            <div class="db-stat-card card-flat"
                 onclick="openModal('modal-nut')" role="button" tabindex="0"
                 aria-label="View nutrition">
                <div class="db-flat-top">
                    <div class="db-flat-icon"><i class="ti ti-bowl"></i></div>
                    <span class="db-flat-title">Nutrition</span>
                </div>
                <div class="db-flat-val">
                    <?= $carbs > 0 ? round($carbs) . '<small>g</small>' : '<span class="db-flat-empty">—</span>' ?>
                </div>
                <div class="db-flat-label">carbs today</div>
                <?php if ($hasNutrition): ?>
                <div class="db-flat-secondary"><?= round($calories) ?> kcal · <?= round($protein) ?>g protein</div>
                <div class="db-dual-bars">
                    <div class="db-dual-bar-row">
                        <span class="db-dual-bar-name">Carbs</span>
                        <div class="db-flat-bar-track">
                            <div class="db-flat-bar-fill" style="width:<?= $carbPct ?>%;"></div>
                        </div>
                        <span class="db-dual-pct"><?= $carbPct ?>%</span>
                    </div>
                    <div class="db-dual-bar-row">
                        <span class="db-dual-bar-name">Cal</span>
                        <div class="db-flat-bar-track">
                            <div class="db-flat-bar-fill" style="width:<?= $caloriesPct ?>%;background:linear-gradient(90deg,#f59e0b,#fbbf24);"></div>
                        </div>
                        <span class="db-dual-pct"><?= $caloriesPct ?>%</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="db-flat-empty-hint"><i class="ti ti-plus"></i> Log first meal</div>
                <?php endif; ?>
                <div class="db-card-hint-flat">Details <i class="ti ti-arrow-right"></i></div>
            </div>

            <div class="db-stat-card card-flat"
                 onclick="openModal('modal-act')" role="button" tabindex="0"
                 aria-label="View activity">
                <div class="db-flat-top">
                    <div class="db-flat-icon"><i class="ti ti-run"></i></div>
                    <span class="db-flat-title">Activity</span>
                </div>
                <div class="db-flat-val">
                    <?= $actMinutes > 0 ? $actMinutes . '<small>min</small>' : '<span class="db-flat-empty">—</span>' ?>
                </div>
                <div class="db-flat-label">active today</div>
                <?php if ($actMinutes > 0): ?>
                <div class="db-flat-secondary"><?= max(0, $actGoal - $actMinutes) ?> min to goal · <?= $weekTotal ?>m week</div>
                <div class="db-act-arc-wrap" aria-hidden="true">
                    <svg viewBox="0 0 80 46" width="80" height="46">
                        <path d="M8 42 A34 34 0 0 1 72 42" fill="none" stroke="rgba(249,116,71,0.12)" stroke-width="7" stroke-linecap="round"/>
                        <path d="M8 42 A34 34 0 0 1 72 42" fill="none" stroke="#F97447" stroke-width="7"
                              stroke-linecap="round" opacity="0.85"
                              stroke-dasharray="<?= round(107 * ($actPct / 100)) ?> 107"/>
                    </svg>
                    <div class="db-act-arc-label"><?= $actPct ?>%</div>
                </div>
                <?php else: ?>
                <div class="db-flat-empty-hint"><i class="ti ti-plus"></i> Log activity</div>
                <?php endif; ?>
                <div class="db-card-hint-flat">Details <i class="ti ti-arrow-right"></i></div>
            </div>

        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL 1 — BLOOD SUGAR
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-bs" onclick="overlayClose(event,'modal-bs')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon coral"><i class="ti ti-droplet-half-2"></i></div>
        <div>
          <div class="db-modal-title">Blood Sugar</div>
          <div class="db-modal-subtitle">Last 7 readings<?= $bsLastLoggedAgo ? ' · ' . $bsLastLoggedAgo : '' ?></div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-bs')" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="db-modal-cta-bar">
        <a href="/diabetrack/public/patient/bloodsugar" class="db-modal-cta-btn">
            <i class="ti ti-plus"></i> Log New Reading
        </a>
        <a href="/diabetrack/public/patient/bloodsugar" class="db-modal-cta-link">
            View all <i class="ti ti-arrow-right"></i>
        </a>
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
      <?php if ($bsHigh > 0): ?>
      <div class="db-modal-tip tip-alert">
          <i class="ti ti-alert-triangle"></i>
          <?= $bsHigh ?> high reading<?= $bsHigh > 1 ? 's' : '' ?> in the last 7 entries. Review your meal intake and consult your doctor if this persists.
      </div>
      <?php elseif ($bsLow > 0): ?>
      <div class="db-modal-tip tip-watch">
          <i class="ti ti-alert-circle"></i>
          <?= $bsLow ?> low reading<?= $bsLow > 1 ? 's' : '' ?> recently. Eat regularly and keep a fast-acting sugar source on hand.
      </div>
      <?php else: ?>
      <div class="db-modal-tip tip-good">
          <i class="ti ti-circle-check"></i>
          All recent readings are in the normal range. Keep it up!
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon"><i class="ti ti-chart-line"></i></div>
        <div class="db-modal-empty-text">No readings yet — log your first one!</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL 2 — MEDICATIONS
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-med" onclick="overlayClose(event,'modal-med')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon terra"><i class="ti ti-pill"></i></div>
        <div>
          <div class="db-modal-title">Medications</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?> · <?= $medTaken ?>/<?= $medTotal ?> taken</div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-med')" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="db-modal-cta-bar">
        <a href="/diabetrack/public/patient/medication" class="db-modal-cta-btn">
            <i class="ti ti-clipboard-check"></i> Update Schedule
        </a>
        <a href="/diabetrack/public/patient/medication" class="db-modal-cta-link">
            Full schedule <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <div class="db-modal-body">
      <?php if ($medTotal > 0): ?>
      <div class="db-med-progress">
        <div class="db-med-ring">
          <svg viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="34" fill="none" stroke="#FDE8DC" stroke-width="7"/>
            <circle cx="40" cy="40" r="34" fill="none" stroke="#F97447" stroke-width="7"
              stroke-linecap="round"
              stroke-dasharray="<?= $medDashLg ?> <?= $medCircumLg ?>"/>
          </svg>
          <div class="db-med-ring-label"><?= $medPct ?>%</div>
        </div>
        <div class="db-med-progress-info">
          <div class="db-med-progress-title"><?= $medTaken ?> of <?= $medTotal ?> doses taken</div>
          <div class="db-med-progress-sub">
            <?php if ($medMissed > 0): ?>
                <i class="ti ti-alert-triangle" style="color:#ef4444;"></i> <?= $medMissed ?> missed · <?= $medPending ?> pending
            <?php elseif ($medPending > 0): ?>
                <i class="ti ti-clock" style="color:#f59e0b;"></i> <?= $medPending ?> still pending
            <?php else: ?>
                <i class="ti ti-circle-check" style="color:#22c55e;"></i> All doses completed!
            <?php endif; ?>
          </div>
          <?php if ($nextMedName): ?>
          <div class="db-med-next-up <?= $nextMedUrgent ? 'next-urgent' : '' ?>">
              <i class="ti <?= $nextMedUrgent ? 'ti-alarm' : 'ti-clock' ?>"></i>
              <?= $nextMedUrgent ? 'Due soon: ' : 'Up next: ' ?><?= htmlspecialchars($nextMedName) ?> · <?= date('h:i A', $nextMedTime) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php foreach ($medications as $med):
        $logStatus = null; $logTime = null;
        foreach ($todayLogs as $tl) {
            if (($tl['medication_id'] ?? null) == $med['id'] || ($tl['name'] ?? '') === $med['name']) {
                $logStatus = $tl['status']; $logTime = $tl['logged_at']; break;
            }
        }
        $logged   = $loggedToday[$med['id']] ?? false;
        $dotClass = $logged ? strtolower($logStatus ?? 'taken') : 'pending';
        $pillText = $logged ? ucfirst($logStatus ?? 'Taken') : 'Pending';
        $dotIcon  = $dotClass === 'taken' ? 'ti-circle-check' : ($dotClass === 'missed' ? 'ti-circle-x' : 'ti-clock');
      ?>
      <div class="db-med-item">
        <div class="db-med-status-icon <?= $dotClass ?>">
            <i class="ti <?= $dotIcon ?>"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div class="db-med-name"><?= htmlspecialchars($med['name']) ?></div>
          <div class="db-med-meta">
            <span><i class="ti ti-pill"></i> <?= htmlspecialchars($med['dosage']) ?></span>
            <span><i class="ti ti-clock"></i> <?= date('h:i A', strtotime($med['schedule_time'])) ?></span>
            <span><?= htmlspecialchars($med['frequency']) ?></span>
            <?php if ($logTime): ?><span><i class="ti ti-check"></i> Logged <?= date('h:i A', strtotime($logTime)) ?></span><?php endif; ?>
          </div>
        </div>
        <span class="db-med-pill <?= $dotClass ?>"><?= $pillText ?></span>
      </div>
      <?php endforeach; ?>

      <?php if ($medMissed > 0): ?>
      <div class="db-modal-tip tip-alert" style="margin-top:12px;">
          <i class="ti ti-alert-triangle"></i>
          You missed <?= $medMissed ?> dose<?= $medMissed > 1 ? 's' : '' ?> today. Contact your doctor if this happens regularly.
      </div>
      <?php endif; ?>

      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon"><i class="ti ti-pill"></i></div>
        <div class="db-modal-empty-text">No medications scheduled yet.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL 3 — NUTRITION
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-nut" onclick="overlayClose(event,'modal-nut')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon ember"><i class="ti ti-bowl"></i></div>
        <div>
          <div class="db-modal-title">Nutrition</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?> · <?= $hasNutrition ? round($calories) . ' kcal logged' : 'No meals yet' ?></div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-nut')" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="db-modal-cta-bar">
        <a href="/diabetrack/public/patient/meals" class="db-modal-cta-btn">
            <i class="ti ti-plus"></i> Log a Meal
        </a>
        <a href="/diabetrack/public/patient/meals" class="db-modal-cta-link">
            Full log <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <div class="db-modal-body">
      <?php if ($hasNutrition): ?>
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
      <?php
      $nutrients = [
        ['icon'=>'ti-flame',   'name'=>'Calories','val'=>round($calories),'unit'=>'kcal','max'=>2000,'color'=>'#F97447'],
        ['icon'=>'ti-candy',   'name'=>'Sugar',   'val'=>round($sugar),   'unit'=>'g',   'max'=>50,  'color'=>'#ef4444'],
        ['icon'=>'ti-meat',    'name'=>'Protein', 'val'=>round($protein), 'unit'=>'g',   'max'=>60,  'color'=>'#22c55e'],
        ['icon'=>'ti-droplet', 'name'=>'Fat',     'val'=>round($fat),     'unit'=>'g',   'max'=>65,  'color'=>'#f59e0b'],
        ['icon'=>'ti-plant',   'name'=>'Fiber',   'val'=>round($fiber),   'unit'=>'g',   'max'=>25,  'color'=>'#86efac'],
      ];
      foreach ($nutrients as $n):
        $pct = $n['max'] > 0 ? min(round($n['val'] / $n['max'] * 100), 100) : 0;
      ?>
      <div class="db-nut-row">
        <div class="db-nut-icon"><i class="ti <?= $n['icon'] ?>"></i></div>
        <div class="db-nut-name"><?= $n['name'] ?></div>
        <div class="db-nut-mini-track">
          <div class="db-nut-mini-fill" style="width:<?= $pct ?>%;background:<?= $n['color'] ?>;opacity:0.75;"></div>
        </div>
        <div class="db-nut-val"><?= $n['val'] ?><?= $n['unit'] ?></div>
      </div>
      <?php endforeach; ?>
      <?php if ($sugar > 40): ?>
      <div class="db-modal-tip tip-watch" style="margin-top:12px;">
          <i class="ti ti-info-circle"></i>
          Sugar intake is approaching the daily limit. Be mindful of high-sugar foods for the rest of the day.
      </div>
      <?php elseif ($carbPct >= 90): ?>
      <div class="db-modal-tip tip-alert" style="margin-top:12px;">
          <i class="ti ti-alert-triangle"></i>
          You're near your carb limit for today. Excess carbs can spike blood sugar levels.
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="db-modal-empty">
        <div class="db-modal-empty-icon"><i class="ti ti-bowl"></i></div>
        <div class="db-modal-empty-text">No meals logged today yet.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL 4 — ACTIVITY
     ══════════════════════════════════════════════════════ -->
<div class="db-modal-overlay" id="modal-act" onclick="overlayClose(event,'modal-act')">
  <div class="db-modal">
    <div class="db-modal-header">
      <div class="db-modal-header-left">
        <div class="db-modal-icon blush"><i class="ti ti-run"></i></div>
        <div>
          <div class="db-modal-title">Activity</div>
          <div class="db-modal-subtitle"><?= date('l, M d') ?> · <?= $actMinutes ?>/<?= $actGoal ?> min goal</div>
        </div>
      </div>
      <button class="db-modal-close" onclick="closeModal('modal-act')" aria-label="Close"><i class="ti ti-x"></i></button>
    </div>
    <div class="db-modal-cta-bar">
        <a href="/diabetrack/public/patient/activity" class="db-modal-cta-btn">
            <i class="ti ti-plus"></i> Log Activity
        </a>
        <a href="/diabetrack/public/patient/activity" class="db-modal-cta-link">
            Full log <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <div class="db-modal-body">
      <div class="db-act-ring-wrap">
        <div class="db-act-ring">
          <svg viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="52" fill="none" stroke="#FDE8DC" stroke-width="10"/>
            <circle cx="60" cy="60" r="52" fill="none" stroke="#F97447" stroke-width="10"
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
            <div class="db-act-stat-icon"><i class="ti ti-clock"></i></div>
            <div><div class="db-act-stat-val"><?= $actMinutes ?> min</div><div class="db-act-stat-label">Active today</div></div>
          </div>
          <div class="db-act-stat">
            <div class="db-act-stat-icon"><i class="ti ti-target"></i></div>
            <div><div class="db-act-stat-val"><?= $actGoal ?> min</div><div class="db-act-stat-label">Daily goal</div></div>
          </div>
          <div class="db-act-stat">
            <div class="db-act-stat-icon"><i class="ti ti-calendar-week"></i></div>
            <div><div class="db-act-stat-val"><?= $weekTotal ?> min</div><div class="db-act-stat-label">This week</div></div>
          </div>
        </div>
      </div>
      <?php if (!empty($last7Days)): ?>
      <div class="db-bs-chart-wrap" style="margin-top:0;">
        <div class="db-act-week-label">Last 7 Days</div>
        <div class="db-act-bars">
          <?php
          $maxMin = max(array_map(fn($d) => (int)($d['total_minutes'] ?? $d['minutes'] ?? 0), $last7Days));
          $maxMin = max($maxMin, 1);
          foreach ($last7Days as $day):
            $mins    = (int)($day['total_minutes'] ?? $day['minutes'] ?? 0);
            $barH    = max(4, round(($mins / $maxMin) * 72));
            $isToday = date('Y-m-d') === ($day['date'] ?? '');
          ?>
          <div class="db-act-bar-col">
            <div class="db-act-bar-val"><?= $mins ?>m</div>
            <div class="db-act-bar" style="height:<?= $barH ?>px;<?= $isToday ? 'background:#F97447;border-color:rgba(249,116,71,0.3);' : 'background:#FDE8DC;border-color:rgba(249,116,71,0.15);' ?>"></div>
            <div class="db-act-bar-day <?= $isToday ? 'day-today' : '' ?>"><?= date('D', strtotime($day['date'] ?? 'today')) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($actPct >= 100): ?>
      <div class="db-modal-tip tip-good" style="margin-top:12px;">
          <i class="ti ti-circle-check"></i>
          Daily goal reached! Regular exercise is one of the best ways to manage blood sugar.
      </div>
      <?php elseif ($actMinutes === 0): ?>
      <div class="db-modal-tip tip-watch" style="margin-top:12px;">
          <i class="ti ti-info-circle"></i>
          Even a 15-minute walk helps regulate blood sugar. Try to move a little today!
      </div>
      <?php endif; ?>
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
        document.querySelectorAll('.db-modal-overlay.open').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
    }
});

<?php if (!empty($last7)): ?>
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