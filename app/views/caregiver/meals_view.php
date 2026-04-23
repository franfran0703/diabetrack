<?php
$pageTitle  = 'Meal Monitor';
$activeMenu = 'meals';
ob_start();

$carbPct   = $todayTotals['total_meals'] > 0
    ? min(round((float)$todayTotals['total_carbs'] / 130 * 100), 100)
    : 0;
$carbClass = $carbPct >= 100 ? 'over' : ($carbPct >= 75 ? 'warn' : 'good');

// Bento tile helper
function bentoStatus($val, $max) {
    $pct = $max > 0 ? ($val / $max * 100) : 0;
    if ($pct >= 100) return ['badge' => 'Over limit', 'class' => 'over'];
    if ($pct >= 75)  return ['badge' => 'Near limit', 'class' => 'warn'];
    if ($pct > 0)    return ['badge' => 'On track',   'class' => 'good'];
    return                  ['badge' => 'No data',    'class' => 'neutral'];
}
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>" rel="stylesheet">
<link href="/diabetrack/public/assets/css/caregiver_meals.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgml-header">
    <div>
        <div class="cgml-eyebrow">Diet Monitor</div>
        <h1 class="cgml-title">🥗 Meal <span>Monitor</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cgml-patient-chip">
        <div class="cgml-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cgml-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cgml-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to monitor their meals.
    </div>
</div>

<?php else: ?>

<!-- ═══════════════════════════════════════════════════════
     HERO — full-width carb progress
     ═══════════════════════════════════════════════════════ -->
<div class="cgml-carb-hero">

    <!-- Left: icon + big number -->
    <div class="cgml-carb-hero-left">
        <div class="cgml-carb-circle">🍚</div>
        <div>
            <div class="cgml-carb-val">
                <?= round($todayTotals['total_carbs'], 1) ?><small>g</small>
            </div>
            <div class="cgml-carb-sublabel">Total Carbs Today</div>
        </div>
    </div>

    <div class="cgml-carb-hero-divider"></div>

    <!-- Centre: progress bar fills remaining space -->
    <div class="cgml-carb-hero-bar">
        <div class="cgml-bar-header">
            <div class="cgml-bar-pct"><?= $carbPct ?>%</div>
            <div class="cgml-bar-limit">of 130g daily limit</div>
        </div>
        <div class="cgml-bar-track">
            <div class="cgml-bar-fill <?= $carbClass ?>" style="width:<?= $carbPct ?>%;"></div>
        </div>
        <div class="cgml-bar-note">
            <?php if ($carbPct >= 100): ?>
                ⚠️ Daily carb limit exceeded!
            <?php elseif ($carbPct >= 75): ?>
                ⚠️ Approaching the daily limit
            <?php else: ?>
                ✅ Within safe range
            <?php endif; ?>
        </div>
    </div>

    <div class="cgml-carb-hero-divider"></div>

    <!-- Right: quick summary pills -->
    <div class="cgml-carb-hero-right">
        <div class="cgml-hero-pill">
            <span style="font-size:0.9rem;">🍽️</span>
            <span class="cgml-hero-pill-val"><?= $todayTotals['total_meals'] ?></span>
            <span class="cgml-hero-pill-label">Meals today</span>
        </div>
        <div class="cgml-hero-pill">
            <span style="font-size:0.9rem;">🔥</span>
            <span class="cgml-hero-pill-val"><?= round($todayTotals['total_calories']) ?></span>
            <span class="cgml-hero-pill-label">kcal</span>
        </div>
        <div class="cgml-hero-pill">
            <span style="font-size:0.9rem;">📅</span>
            <span class="cgml-hero-pill-label"><?= date('l, M d') ?></span>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════
     BENTO — 3-column macro tiles
     ═══════════════════════════════════════════════════════ -->
<?php
$tiles = [
    [
        'icon'  => '🥩',
        'label' => 'Protein',
        'val'   => round($todayTotals['total_protein'], 1),
        'unit'  => 'g',
        'max'   => 60,
        'color' => '#22c55e',
    ],
    [
        'icon'  => '🧈',
        'label' => 'Fat',
        'val'   => round($todayTotals['total_fat'], 1),
        'unit'  => 'g',
        'max'   => 65,
        'color' => '#f59e0b',
    ],
    [
        'icon'  => '🌾',
        'label' => 'Fiber',
        'val'   => round($todayTotals['total_fiber'], 1),
        'unit'  => 'g',
        'max'   => 25,
        'color' => '#86efac',
    ],
];
?>
<div class="cgml-bento">
    <?php foreach ($tiles as $t):
        $pct    = $t['max'] > 0 ? min(round($t['val'] / $t['max'] * 100), 100) : 0;
        $status = bentoStatus($t['val'], $t['max']);
    ?>
    <div class="cgml-bento-tile">
        <div class="cgml-bento-top">
            <div class="cgml-bento-icon"><?= $t['icon'] ?></div>
            <span class="cgml-bento-badge <?= $status['class'] ?>"><?= $status['badge'] ?></span>
        </div>
        <div>
            <div class="cgml-bento-val"><?= $t['val'] ?><small><?= $t['unit'] ?></small></div>
            <div class="cgml-bento-label"><?= $t['label'] ?></div>
        </div>
        <div class="cgml-bento-bar-track">
            <div class="cgml-bento-bar-fill"
                 style="width:<?= $pct ?>%;background:<?= $t['color'] ?>;"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════════════
     BODY — meal feed (left) + nutrition sidebar (right)
     ═══════════════════════════════════════════════════════ -->
<div class="cgml-body">

    <!-- LEFT: MEAL FEED -->
    <div class="cgml-feed-panel">
        <div class="cgml-panel-label">
            Today's Meal Feed — <?= date('M d, Y') ?>
        </div>

        <?php if (empty($todayLogs)): ?>
        <div class="cgml-empty">
            <div class="cgml-empty-icon">🥗</div>
            <div class="cgml-empty-text">No meals logged today yet.</div>
        </div>
        <?php else: ?>
        <?php foreach ($todayLogs as $meal): ?>
        <div class="cgml-feed-item">
            <div class="cgml-feed-icon">
                <?= $meal['meal_type'] === 'Breakfast' ? '🌅' :
                   ($meal['meal_type'] === 'Lunch'     ? '☀️' :
                   ($meal['meal_type'] === 'Dinner'    ? '🌙' : '🍎')) ?>
            </div>
            <div style="flex:1;">
                <div class="cgml-feed-name"><?= htmlspecialchars($meal['meal_name']) ?></div>
                <div class="cgml-feed-meta">
                    <?php if ($meal['calories']): ?>🔥 <?= $meal['calories'] ?> kcal &nbsp;·&nbsp; <?php endif; ?>
                    <?php if ($meal['protein']):  ?>🥩 <?= $meal['protein'] ?>g protein &nbsp;·&nbsp; <?php endif; ?>
                    <?php if ($meal['sugar']):    ?>🍬 <?= $meal['sugar'] ?>g sugar &nbsp;·&nbsp; <?php endif; ?>
                    <?php if ($meal['notes']):    ?>"<?= htmlspecialchars($meal['notes']) ?>"<?php endif; ?>
                </div>
                <span class="cgml-feed-type"><?= $meal['meal_type'] ?></span>
            </div>
            <div class="cgml-feed-right">
                <div class="cgml-feed-carbs">
                    <?= $meal['carbs'] ?><small>g carbs</small>
                </div>
                <div class="cgml-feed-time">
                    <?= date('h:i A', strtotime($meal['logged_at'])) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- RIGHT: NUTRITION SIDEBAR -->
    <div class="cgml-nutrition-panel">

        <!-- Nutrient breakdown -->
        <div class="cgml-breakdown">
            <div class="cgml-panel-label">Today's Nutrition</div>
            <?php
            $nutrients = [
                ['icon' => '🍚', 'name' => 'Carbohydrates', 'val' => round($todayTotals['total_carbs'],   1), 'unit' => 'g', 'max' => 130, 'color' => '#F97447'],
                ['icon' => '🍬', 'name' => 'Sugar',          'val' => round($todayTotals['total_sugar'],   1), 'unit' => 'g', 'max' => 50,  'color' => '#ef4444'],
                ['icon' => '🥩', 'name' => 'Protein',        'val' => round($todayTotals['total_protein'], 1), 'unit' => 'g', 'max' => 60,  'color' => '#22c55e'],
                ['icon' => '🧈', 'name' => 'Fat',             'val' => round($todayTotals['total_fat'],     1), 'unit' => 'g', 'max' => 65,  'color' => '#f59e0b'],
                ['icon' => '🌾', 'name' => 'Fiber',           'val' => round($todayTotals['total_fiber'],   1), 'unit' => 'g', 'max' => 25,  'color' => '#86efac'],
            ];
            foreach ($nutrients as $n):
                $pct = $n['max'] > 0 ? min(round($n['val'] / $n['max'] * 100), 100) : 0;
            ?>
            <div class="cgml-nutrient-row">
                <div class="cgml-nutrient-top">
                    <span class="cgml-nutrient-name"><?= $n['icon'] ?> <?= $n['name'] ?></span>
                    <span class="cgml-nutrient-val"><?= $n['val'] ?><?= $n['unit'] ?></span>
                </div>
                <div class="cgml-nutrient-track">
                    <div class="cgml-nutrient-fill"
                         style="width:<?= $pct ?>%;background:<?= $n['color'] ?>;opacity:0.7;">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Daily limits -->
        <div class="cgml-safe-zone">
            <div class="cgml-panel-label">Daily Limits for Diabetics</div>
            <?php
            $limits = [
                ['icon' => '🍚', 'name' => 'Carbohydrates', 'limit' => '≤ 130g'],
                ['icon' => '🍬', 'name' => 'Sugar',          'limit' => '≤ 50g'],
                ['icon' => '🧈', 'name' => 'Fat',             'limit' => '≤ 65g'],
                ['icon' => '🧂', 'name' => 'Sodium',          'limit' => '≤ 2300mg'],
                ['icon' => '🔥', 'name' => 'Calories',        'limit' => '1500–2000 kcal'],
            ];
            foreach ($limits as $l): ?>
            <div class="cgml-safe-item">
                <span class="cgml-safe-name"><?= $l['icon'] ?> <?= $l['name'] ?></span>
                <span class="cgml-safe-limit"><?= $l['limit'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     HISTORY TABLE — full width
     ═══════════════════════════════════════════════════════ -->
<div class="cgml-table-panel">
    <div class="cgml-panel-label">
        Meal History — <?= htmlspecialchars($patient['name']) ?>
        <span style="margin-left:auto;font-size:0.7rem;font-weight:700;color:#b8927e;letter-spacing:0;text-transform:none;">Last 30 entries</span>
    </div>

    <?php if (empty($allLogs)): ?>
    <div class="cgml-empty">
        <div class="cgml-empty-icon">📜</div>
        <div class="cgml-empty-text">No meal history yet.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgml-table">
            <thead>
                <tr>
                    <th>Meal</th>
                    <th>Type</th>
                    <th>Carbs</th>
                    <th>Calories</th>
                    <th>Sugar</th>
                    <th>Protein</th>
                    <th>Fat</th>
                    <th>GI</th>
                    <th>Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allLogs as $log): ?>
                <tr>
                    <td class="cgml-table-name"><?= htmlspecialchars($log['meal_name']) ?></td>
                    <td><span class="cgml-type-pill"><?= $log['meal_type'] ?></span></td>
                    <td class="cgml-table-val"><?= $log['carbs'] ?>g</td>
                    <td class="cgml-table-muted"><?= $log['calories']       ? $log['calories'] . ' kcal' : '—' ?></td>
                    <td class="cgml-table-muted"><?= $log['sugar']          ? $log['sugar']    . 'g'     : '—' ?></td>
                    <td class="cgml-table-muted"><?= $log['protein']        ? $log['protein']  . 'g'     : '—' ?></td>
                    <td class="cgml-table-muted"><?= $log['fat']            ? $log['fat']      . 'g'     : '—' ?></td>
                    <td class="cgml-table-muted"><?= $log['glycemic_index'] ?? '—' ?></td>
                    <td class="cgml-table-muted" style="white-space:nowrap;">
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

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>