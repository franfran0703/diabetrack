<?php
$pageTitle  = 'Meal Monitor';
$activeMenu = 'meals';
ob_start();

// ── Limits (from controller, or defaults) ─────────────────
$lim = $limits ?? [
    'carbs'=>130,'calories'=>1800,'sugar'=>50,
    'protein'=>60,'fat'=>65,'fiber'=>25,'sodium'=>2300
];

// ── Today's totals shorthand ───────────────────────────────
$tCarbs   = (float)($todayTotals['total_carbs']    ?? 0);
$tCals    = (float)($todayTotals['total_calories'] ?? 0);
$tSugar   = (float)($todayTotals['total_sugar']    ?? 0);
$tProtein = (float)($todayTotals['total_protein']  ?? 0);
$tFat     = (float)($todayTotals['total_fat']      ?? 0);
$tFiber   = (float)($todayTotals['total_fiber']    ?? 0);
$tSodium  = (float)($todayTotals['total_sodium']   ?? 0);
$tMeals   = (int)  ($todayTotals['total_meals']    ?? 0);

// ── Status helpers ─────────────────────────────────────────
function macroStatus(float $val, float $max): string {
    if ($max <= 0) return 'dark';
    $p = $val / $max * 100;
    return $p >= 100 ? 'danger' : ($p >= 80 ? 'warn' : 'normal');
}
function macroPct(float $val, float $max): int {
    return $max > 0 ? min(100, (int)round($val / $max * 100)) : 0;
}
function macroColor(float $val, float $max): string {
    if ($max <= 0) return 'rgba(249,116,71,0.5)';
    $p = $val / $max * 100;
    return $p >= 100 ? '#ef4444' : ($p >= 80 ? '#f59e0b' : '#22c55e');
}
function barGradient(float $val, float $max, string $base): string {
    if ($max <= 0) return "rgba(249,116,71,0.4)";
    $p = $val / $max * 100;
    if ($p >= 100) return 'linear-gradient(90deg,#dc2626,#ef4444)';
    if ($p >= 80)  return 'linear-gradient(90deg,#d97706,#f59e0b)';
    return "linear-gradient(90deg,{$base})";
}

// ── How many limits are over? ──────────────────────────────
$overCount = 0;
foreach ([
    $tCarbs   => $lim['carbs'],   $tCals    => $lim['calories'],
    $tSugar   => $lim['sugar'],   $tFat     => $lim['fat'],
] as $v => $m) {
    if ($m > 0 && $v >= $m) $overCount++;
}

// ── Group today's meals by type ────────────────────────────
$mealTypeOrder = ['Breakfast','Lunch','Dinner','Snack'];
$mealsByType   = array_fill_keys($mealTypeOrder, []);
foreach ($todayLogs as $ml) {
    $t = $ml['meal_type'] ?? 'Snack';
    if (!isset($mealsByType[$t])) $mealsByType[$t] = [];
    $mealsByType[$t][] = $ml;
}

// ── Group all logs by date for drawer ─────────────────────
$logsByDate = [];
foreach ($allLogs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);

// ── Micro ring helper (SVG) ───────────────────────────────
function microRing(float $val, float $max, string $color, string $label, string $unit): string {
    $r    = 18; $circ = 2 * M_PI * $r;
    $pct  = $max > 0 ? min(100, $val / $max * 100) : 0;
    $dash = round($circ * $pct / 100, 1);
    if ($max > 0 && $val >= $max) $color = '#ef4444';
    elseif ($max > 0 && $val >= $max * 0.8) $color = '#f59e0b';
    $dispVal = $val < 1000 ? round($val) : round($val/1000,1).'k';
    $isOver  = $max > 0 && $val > $max;
    $amtClass = $isOver ? 'over' : ($val >= $max * 0.8 ? 'warn' : '');
    $limitDisp = $max < 1000 ? round($max) : round($max/1000,1).'k';
    ob_start(); ?>
    <div class="cgml-ring-col">
        <svg width="44" height="44" viewBox="0 0 44 44" class="cgml-ring-svg">
            <circle cx="22" cy="22" r="<?= $r ?>" class="cgml-ring-bg" stroke="rgba(255,255,255,0.07)" stroke-width="4"/>
            <circle cx="22" cy="22" r="<?= $r ?>" class="cgml-ring-fill"
                    stroke="<?= $color ?>" stroke-width="4"
                    stroke-dasharray="<?= $dash ?> <?= round($circ,1) ?>"
                    stroke-dashoffset="<?= round($circ/4,1) ?>"/>
            <text x="22" y="22" class="cgml-ring-center-val" fill="<?= $color ?>"><?= $dispVal ?></text>
        </svg>
        <div class="cgml-ring-label"><?= $label ?></div>
        <div class="cgml-ring-amount <?= $amtClass ?>"><?= round($val) ?>/<?= $limitDisp ?><?= $unit ?></div>
    </div>
    <?php return ob_get_clean();
}

$mealTypeIcons = ['Breakfast'=>'🌅','Lunch'=>'☀️','Dinner'=>'🌙','Snack'=>'🍎'];
$mealTypeClass = ['Breakfast'=>'breakfast','Lunch'=>'lunch','Dinner'=>'dinner','Snack'=>'snack'];
?>

<link href="/diabetrack/public/assets/css/caregiver_meals.css?v=<?= time() ?>" rel="stylesheet">

<!-- ══ HEADER ════════════════════════════════════════════ -->
<div class="cgml-header">
    <div>
        <div class="cgml-eyebrow"><i class="ti ti-salad"></i> Diet Monitor</div>
        <h1 class="cgml-title">Meal <span>Monitor</span></h1>
        <p class="cgml-sub"><?= date('l, F j') ?> &middot; Nutrition tracking &amp; limit management</p>
    </div>
    <?php if ($patient): ?>
    <div class="cgml-header-right">
        <?php if ($overCount > 0): ?>
        <div class="cgml-over-chip">
            <i class="ti ti-alert-circle"></i>
            <?= $overCount ?> limit<?= $overCount > 1 ? 's' : '' ?> exceeded today
        </div>
        <?php endif; ?>
        <div class="cgml-patient-chip">
            <div class="cgml-patient-avatar"><?= strtoupper(substr($patient['name'],0,1)) ?></div>
            <div>
                <div class="cgml-patient-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
                <div class="cgml-patient-label">Linked Patient</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div class="cgml-no-patient">
    <div class="cgml-no-patient-icon"><i class="ti ti-link"></i></div>
    <div class="cgml-no-patient-title">No Patient Linked</div>
    <div class="cgml-no-patient-sub"><a href="/diabetrack/public/caregiver/patients">Link a patient</a> to monitor their meals.</div>
</div>

<?php else: ?>

<!-- ══ STAT CARDS ═════════════════════════════════════════ -->
<?php
$calStatus  = macroStatus($tCals,  $lim['calories']);
$carbStatus = macroStatus($tCarbs, $lim['carbs']);
$sugStatus  = macroStatus($tSugar, $lim['sugar']);
$calPct     = macroPct($tCals,     $lim['calories']);
$circ54     = 2 * M_PI * 22;
$calDash    = round($circ54 * $calPct / 100, 1);
$calRingCol = $calStatus === 'danger' ? '#ef4444' : ($calStatus === 'warn' ? '#f59e0b' : '#22c55e');
?>
<div class="cgml-stat-row">

    <!-- 1. Calories — primary -->
    <div class="cgml-stat-card card-primary <?= $calStatus === 'danger' ? 'card-over' : '' ?>">
        <div class="cgml-stat-card-top">
            <div class="cgml-stat-icon-wrap"><i class="ti ti-flame"></i></div>
            <svg width="44" height="44" viewBox="0 0 54 54">
                <circle cx="27" cy="27" r="22" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="5"/>
                <circle cx="27" cy="27" r="22" fill="none" stroke="#fff" stroke-width="5"
                        stroke-dasharray="<?= $calDash ?> <?= round($circ54,1) ?>"
                        stroke-dashoffset="<?= round($circ54/4,1) ?>"
                        stroke-linecap="round" class="cgml-ring-arc"/>
                <text x="27" y="30" text-anchor="middle" fill="rgba(255,255,255,0.9)"
                      style="font-family:'Cabinet Grotesk',sans-serif;font-weight:900;font-size:9px;"><?= $calPct ?>%</text>
            </svg>
        </div>
        <div class="cgml-stat-val"><?= round($tCals) ?><small>kcal</small></div>
        <div class="cgml-stat-label">Calories Today</div>
        <div class="cgml-stat-sub white"><i class="ti ti-target"></i> limit <?= round($lim['calories']) ?> kcal</div>
    </div>

    <!-- 2. Carbs -->
    <div class="cgml-stat-card <?= $carbStatus === 'danger' ? 'card-danger' : ($carbStatus === 'warn' ? 'card-warn' : 'card-glass') ?>">
        <div class="cgml-stat-card-top">
            <div class="cgml-stat-icon-wrap <?= $carbStatus === 'danger' ? 'danger' : ($carbStatus === 'warn' ? 'warn' : 'glass') ?>">
                <i class="ti ti-bread"></i>
            </div>
        </div>
        <div class="cgml-stat-val <?= $carbStatus === 'normal' ? 'dark' : $carbStatus ?>"><?= round($tCarbs,1) ?><small>g</small></div>
        <div class="cgml-stat-label <?= $carbStatus === 'normal' ? 'dark' : $carbStatus ?>">Carbs</div>
        <div class="cgml-stat-sub <?= $carbStatus === 'normal' ? 'dark' : $carbStatus ?>">
            <i class="ti ti-<?= $carbStatus === 'danger' ? 'alert-circle' : 'target' ?>"></i>
            limit <?= $lim['carbs'] ?>g
        </div>
    </div>

    <!-- 3. Sugar -->
    <div class="cgml-stat-card <?= $sugStatus === 'danger' ? 'card-danger' : ($sugStatus === 'warn' ? 'card-warn' : 'card-glass') ?>">
        <div class="cgml-stat-card-top">
            <div class="cgml-stat-icon-wrap <?= $sugStatus === 'danger' ? 'danger' : ($sugStatus === 'warn' ? 'warn' : 'glass') ?>">
                <i class="ti ti-candy"></i>
            </div>
        </div>
        <div class="cgml-stat-val <?= $sugStatus === 'normal' ? 'dark' : $sugStatus ?>"><?= round($tSugar,1) ?><small>g</small></div>
        <div class="cgml-stat-label <?= $sugStatus === 'normal' ? 'dark' : $sugStatus ?>">Sugar</div>
        <div class="cgml-stat-sub <?= $sugStatus === 'normal' ? 'dark' : $sugStatus ?>">
            <i class="ti ti-<?= $sugStatus === 'danger' ? 'alert-circle' : 'target' ?>"></i>
            limit <?= $lim['sugar'] ?>g
        </div>
    </div>

    <!-- 4. Meals count -->
    <div class="cgml-stat-card card-normal">
        <div class="cgml-stat-card-top">
            <div class="cgml-stat-icon-wrap normal"><i class="ti ti-tools-kitchen-2"></i></div>
        </div>
        <div class="cgml-stat-val normal"><?= $tMeals ?></div>
        <div class="cgml-stat-label normal">Meals Logged</div>
        <div class="cgml-stat-sub normal"><i class="ti ti-calendar-today"></i> today</div>
    </div>

    <!-- 5. Protein -->
    <?php $protStatus = macroStatus($tProtein, $lim['protein']); ?>
    <div class="cgml-stat-card card-glass">
        <div class="cgml-stat-card-top">
            <div class="cgml-stat-icon-wrap glass"><i class="ti ti-meat"></i></div>
        </div>
        <div class="cgml-stat-val dark"><?= round($tProtein,1) ?><small>g</small></div>
        <div class="cgml-stat-label dark">Protein</div>
        <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:100px;overflow:hidden;margin-top:8px;">
            <div style="height:100%;width:<?= macroPct($tProtein,$lim['protein']) ?>%;background:<?= macroColor($tProtein,$lim['protein']) ?>;border-radius:100px;transition:width 1.2s ease;"></div>
        </div>
        <div class="cgml-stat-sub dark"><i class="ti ti-target"></i> <?= round($tProtein,1) ?> / <?= $lim['protein'] ?>g</div>
    </div>

</div><!-- /.cgml-stat-row -->


<!-- ══ MAIN GRID ══════════════════════════════════════════ -->
<div class="cgml-main-grid">

    <!-- ── LEFT: Meal Feed grouped by type ── -->
    <div class="cgml-feed-card">
        <div class="cgml-feed-header">
            <div>
                <div class="cgml-section-eyebrow">Today's Meals</div>
                <div class="cgml-section-title">Daily Feed</div>
            </div>
            <div>
                <span class="cgml-meal-count"><i class="ti ti-tools-kitchen-2"></i> <?= $tMeals ?> meal<?= $tMeals !== 1 ? 's' : '' ?> logged</span>
            </div>
        </div>

        <?php if (empty($todayLogs)): ?>
        <div class="cgml-feed-empty">
            <i class="ti ti-salad"></i>
            No meals logged for <?= date('M j') ?> yet.
        </div>
        <?php else: ?>
        <div>
            <?php foreach ($mealTypeOrder as $typeIdx => $type):
                if (empty($mealsByType[$type])) continue;
                $typeCals = array_sum(array_column($mealsByType[$type], 'calories'));
                $typeCarbs = array_sum(array_column($mealsByType[$type], 'carbs'));
            ?>
            <div class="cgml-type-group">
                <div class="cgml-type-header">
                    <div class="cgml-type-icon <?= $mealTypeClass[$type] ?>"><?= $mealTypeIcons[$type] ?></div>
                    <span class="cgml-type-name"><?= $type ?></span>
                    <?php if ($typeCals > 0): ?>
                    <span class="cgml-type-cal-total"><?= round($typeCals) ?> kcal · <?= round($typeCarbs) ?>g carbs</span>
                    <?php endif; ?>
                </div>

                <?php foreach ($mealsByType[$type] as $idx => $meal): ?>
                <div class="cgml-meal-row" style="animation-delay:<?= $idx * 0.05 ?>s">
                    <div class="cgml-meal-icon"><?= $mealTypeIcons[$type] ?></div>
                    <div class="cgml-meal-info">
                        <div class="cgml-meal-name"><?= htmlspecialchars($meal['meal_name']) ?></div>
                        <div class="cgml-macro-chips">
                            <?php if ($meal['carbs'] > 0): ?>
                            <span class="cgml-macro-chip carbs"><i class="ti ti-bread"></i><?= round($meal['carbs'],1) ?>g carbs</span>
                            <?php endif; ?>
                            <?php if ($meal['calories'] > 0): ?>
                            <span class="cgml-macro-chip cal"><i class="ti ti-flame"></i><?= round($meal['calories']) ?> kcal</span>
                            <?php endif; ?>
                            <?php if ($meal['protein'] > 0): ?>
                            <span class="cgml-macro-chip prot"><i class="ti ti-meat"></i><?= round($meal['protein'],1) ?>g</span>
                            <?php endif; ?>
                            <?php if ($meal['fat'] > 0): ?>
                            <span class="cgml-macro-chip fat"><i class="ti ti-droplet"></i><?= round($meal['fat'],1) ?>g fat</span>
                            <?php endif; ?>
                            <?php if ($meal['sugar'] > 0): ?>
                            <span class="cgml-macro-chip sugar"><i class="ti ti-candy"></i><?= round($meal['sugar'],1) ?>g sugar</span>
                            <?php endif; ?>
                            <?php if (!empty($meal['glycemic_index'])): ?>
                            <span class="cgml-macro-chip gi <?= $meal['glycemic_index'] >= 70 ? 'high' : '' ?>">
                                <i class="ti ti-chart-line"></i>GI <?= $meal['glycemic_index'] ?><?= $meal['glycemic_index'] >= 70 ? ' ⚠' : '' ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($meal['notes'])): ?>
                        <div class="cgml-meal-notes">"<?= htmlspecialchars($meal['notes']) ?>"</div>
                        <?php endif; ?>
                    </div>
                    <div class="cgml-meal-time-col">
                        <?= date('h:i A', strtotime($meal['logged_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div><!-- /.cgml-feed-card -->


    <!-- ── RIGHT: Summary Card ── -->
    <div class="cgml-summary-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:0;">
            <div>
                <div class="cgml-section-eyebrow">Today's Nutrition</div>
                <div class="cgml-section-title">vs. Your Limits</div>
            </div>
            <button class="cgml-limits-edit-btn" onclick="openLimitsModal()" title="Edit daily limits">
                <i class="ti ti-adjustments-horizontal"></i>
                <?php if (!empty($_GET['saved'])): ?>
                <span class="cgml-limits-saved-dot" title="Limits saved"></span>
                <?php endif; ?>
            </button>
        </div>
        <?php if (!empty($_GET['saved'])): ?>
        <div class="cgml-saved-inline"><i class="ti ti-circle-check"></i> Daily limits updated</div>
        <?php endif; ?>

        <!-- 5 Macro rings — the page's unique centrepiece -->
        <div class="cgml-rings-row">
            <?= microRing($tCarbs,   $lim['carbs'],    '#f97447', 'Carbs',    'g') ?>
            <?= microRing($tCals,    $lim['calories'], '#fbbf24', 'Cals',     '') ?>
            <?= microRing($tSugar,   $lim['sugar'],    '#ef4444', 'Sugar',    'g') ?>
            <?= microRing($tProtein, $lim['protein'],  '#22c55e', 'Protein',  'g') ?>
            <?= microRing($tFat,     $lim['fat'],      '#f59e0b', 'Fat',      'g') ?>
        </div>

        <!-- Nutrition bars -->
        <div class="cgml-nutrition-bars">
            <?php
            $nutrRows = [
                ['icon'=>'ti-bread',   'name'=>'Carbs',    'val'=>$tCarbs,   'max'=>$lim['carbs'],    'unit'=>'g',    'base'=>'#c04a20,#f97447'],
                ['icon'=>'ti-flame',   'name'=>'Calories', 'val'=>$tCals,    'max'=>$lim['calories'], 'unit'=>'kcal', 'base'=>'#d97706,#fbbf24'],
                ['icon'=>'ti-candy',   'name'=>'Sugar',    'val'=>$tSugar,   'max'=>$lim['sugar'],    'unit'=>'g',    'base'=>'#dc2626,#ef4444'],
                ['icon'=>'ti-meat',    'name'=>'Protein',  'val'=>$tProtein, 'max'=>$lim['protein'],  'unit'=>'g',    'base'=>'#15803d,#22c55e'],
                ['icon'=>'ti-droplet', 'name'=>'Fat',      'val'=>$tFat,     'max'=>$lim['fat'],      'unit'=>'g',    'base'=>'#b45309,#f59e0b'],
                ['icon'=>'ti-leaf',    'name'=>'Fiber',    'val'=>$tFiber,   'max'=>$lim['fiber'],    'unit'=>'g',    'base'=>'#166534,#4ade80'],
                ['icon'=>'ti-circles', 'name'=>'Sodium',   'val'=>$tSodium,  'max'=>$lim['sodium'],   'unit'=>'mg',   'base'=>'#1d4ed8,#60a5fa'],
            ];
            foreach ($nutrRows as $n):
                $pct  = macroPct($n['val'], $n['max']);
                $bg   = barGradient($n['val'], $n['max'], $n['base']);
                $isOver = ($n['max'] > 0 && $n['val'] > $n['max']);
            ?>
            <div class="cgml-nutr-row">
                <div class="cgml-nutr-top">
                    <span class="cgml-nutr-name"><i class="ti <?= $n['icon'] ?>"></i><?= $n['name'] ?></span>
                    <span class="cgml-nutr-vals">
                        <span class="<?= $isOver ? 'over-val' : 'val' ?>"><?= round($n['val'],1) ?></span>
                        <span style="color:rgba(255,200,160,0.25);"> / <?= round($n['max'],0) ?><?= $n['unit'] ?></span>
                    </span>
                </div>
                <div class="cgml-nutr-track">
                    <div class="cgml-nutr-fill" style="width:<?= $pct ?>%;background:<?= $bg ?>;opacity:0.75;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary rows (today snapshot) -->
        <div class="cgml-summary-rows">
            <div class="cgml-summary-row">
                <span class="cgml-sum-dot" style="background:#f97447;"></span>
                <span class="cgml-sum-key">Meals logged</span>
                <span class="cgml-sum-val"><?= $tMeals ?></span>
            </div>
            <div class="cgml-summary-row">
                <span class="cgml-sum-dot" style="background:<?= $overCount > 0 ? '#ef4444' : '#22c55e' ?>;"></span>
                <span class="cgml-sum-key">Limits exceeded</span>
                <span class="cgml-sum-val" style="color:<?= $overCount > 0 ? '#f87171' : '#4ade80' ?>;"><?= $overCount ?></span>
            </div>
            <div class="cgml-summary-row">
                <span class="cgml-sum-dot" style="background:#fbbf24;"></span>
                <span class="cgml-sum-key">Calorie balance</span>
                <span class="cgml-sum-val" style="font-size:0.85rem;"><?= round($tCals) ?><span class="cgml-sum-unit">kcal</span></span>
            </div>
        </div>

        <!-- History button -->
        <?php if (!empty($allLogs)): ?>
        <button class="cgml-history-btn" onclick="openDrawer()">
            <i class="ti ti-history"></i> Meal History
            <span class="cgml-history-count"><?= count($allLogs) ?></span>
        </button>
        <?php endif; ?>

    </div><!-- /.cgml-summary-card -->

</div><!-- /.cgml-main-grid -->


<!-- ══ HISTORY DRAWER ════════════════════════════════════ -->
<div class="cgml-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="cgml-drawer" id="historyDrawer" role="dialog" aria-modal="true">

    <div class="cgml-drawer-header">
        <div class="cgml-drawer-header-left">
            <div class="cgml-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="cgml-drawer-title">Meal History</div>
                <div class="cgml-drawer-sub"><?= count($allLogs) ?> total entries &middot; <?= count($logsByDate) ?> days</div>
            </div>
        </div>
        <button class="cgml-drawer-close" onclick="closeDrawer()"><i class="ti ti-x"></i></button>
    </div>

    <div class="cgml-drawer-controls">
        <div class="cgml-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search meal name…" oninput="filterDrawer()">
        </div>
        <div class="cgml-drawer-filters">
            <button class="cgml-drawer-filter active" onclick="setFilter('all',this)">All</button>
            <?php foreach ($mealTypeOrder as $t): ?>
            <button class="cgml-drawer-filter" onclick="setFilter('<?= strtolower($t) ?>',this)"><?= $t ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cgml-drawer-body" id="drawerBody">
        <?php if (empty($allLogs)): ?>
        <div class="cgml-drawer-empty"><i class="ti ti-salad"></i><p>No meal history yet.</p></div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayMeals):
            $isToday = $date === date('Y-m-d');
            $isYest  = $date === date('Y-m-d', strtotime('-1 day'));
            $dlabel  = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('l, M j', strtotime($date)));
            $dayCals = round(array_sum(array_column($dayMeals,'calories')));
            $dayCarbs= round(array_sum(array_column($dayMeals,'carbs')),1);
        ?>
        <div class="cgml-drawer-day-group" data-date="<?= $date ?>">
            <div class="cgml-drawer-day-header">
                <div class="cgml-drawer-day-label">
                    <?= $dlabel ?>
                    <?php if ($isToday): ?><span class="cgml-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="cgml-drawer-day-stats">
                    <span><?= count($dayMeals) ?> meal<?= count($dayMeals)>1?'s':'' ?></span>
                    <?php if ($dayCals > 0): ?>
                    <span class="cgml-day-flag"><i class="ti ti-flame" style="font-size:11px;"></i> <?= $dayCals ?> kcal</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cgml-tl-list">
                <?php foreach ($dayMeals as $meal): ?>
                <div class="cgml-tl-item"
                     data-type="<?= strtolower($meal['meal_type']) ?>"
                     data-search="<?= strtolower($meal['meal_name']) ?>">
                    <div class="cgml-tl-spine">
                        <div class="cgml-tl-dot"><?= $mealTypeIcons[$meal['meal_type']] ?? '🍽️' ?></div>
                        <div class="cgml-tl-line"></div>
                    </div>
                    <div class="cgml-tl-card">
                        <div class="cgml-tl-card-top">
                            <div class="cgml-tl-name"><?= htmlspecialchars($meal['meal_name']) ?></div>
                            <div class="cgml-tl-time"><i class="ti ti-clock"></i><?= date('h:i A', strtotime($meal['logged_at'])) ?></div>
                        </div>
                        <div class="cgml-tl-chips">
                            <?php if ($meal['carbs']   > 0): ?><span class="cgml-macro-chip carbs"><?= round($meal['carbs'],1) ?>g carbs</span><?php endif; ?>
                            <?php if ($meal['calories']> 0): ?><span class="cgml-macro-chip cal"><?= round($meal['calories']) ?> kcal</span><?php endif; ?>
                            <?php if ($meal['protein'] > 0): ?><span class="cgml-macro-chip prot"><?= round($meal['protein'],1) ?>g prot</span><?php endif; ?>
                            <?php if ($meal['fat']     > 0): ?><span class="cgml-macro-chip fat"><?= round($meal['fat'],1) ?>g fat</span><?php endif; ?>
                            <?php if ($meal['sugar']   > 0): ?><span class="cgml-macro-chip sugar"><?= round($meal['sugar'],1) ?>g sugar</span><?php endif; ?>
                            <span class="cgml-tl-type"><?= $meal['meal_type'] ?></span>
                        </div>
                        <?php if (!empty($meal['notes'])): ?>
                        <div class="cgml-meal-notes" style="margin-top:5px;">"<?= htmlspecialchars($meal['notes']) ?>"</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div class="cgml-drawer-no-results" id="drawerNoResults">
            <i class="ti ti-search-off"></i> No records match your search.
        </div>
    </div>

    <div class="cgml-drawer-footer">
        <div class="cgml-drawer-footer-stats">
            <span><i class="ti ti-tools-kitchen-2" style="color:#fbab6e;"></i> <?= count($allLogs) ?> Meals</span>
            <span><i class="ti ti-flame" style="color:#fbbf24;"></i> <?= round(array_sum(array_column($allLogs,'calories'))) ?> kcal total</span>
            <span><i class="ti ti-bread" style="color:#f97447;"></i> <?= round(array_sum(array_column($allLogs,'carbs')),1) ?>g carbs</span>
        </div>
    </div>

</div><!-- /.cgml-drawer -->


<!-- ══ NUTRITION LIMITS MODAL ════════════════════════════ -->
<div class="cgml-modal-overlay" id="limitsOverlay" onclick="handleModalOverlayClick(event)">
    <div class="cgml-modal" id="limitsModal">
        <div class="cgml-modal-header">
            <div class="cgml-modal-header-left">
                <div class="cgml-modal-icon"><i class="ti ti-adjustments-horizontal"></i></div>
                <div>
                    <div class="cgml-modal-title">Daily Nutrition Limits</div>
                    <div class="cgml-modal-sub">
                        <?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?> &middot; Personalized targets
                    </div>
                </div>
            </div>
            <button class="cgml-modal-close" onclick="closeLimitsModal()"><i class="ti ti-x"></i></button>
        </div>

        <div class="cgml-modal-body">
            <div class="cgml-modal-info">
                <i class="ti ti-info-circle"></i>
                Set personalized daily nutrition limits for this patient. Progress bars and status indicators
                throughout this page will update to reflect your targets. These are saved per-patient.
            </div>

            <form action="/diabetrack/public/caregiver/saveNutritionLimits" method="POST" id="limitsForm">
                <div class="cgml-limits-grid">

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-bread"></i> Carbohydrates</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="carbs" id="lim_carbs"
                                   value="<?= round($lim['carbs']) ?>" min="0" max="500" step="1" placeholder="130">
                            <span class="cgml-limit-unit">g / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-flame"></i> Calories</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="calories" id="lim_calories"
                                   value="<?= round($lim['calories']) ?>" min="0" max="5000" step="50" placeholder="1800">
                            <span class="cgml-limit-unit">kcal / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-candy"></i> Sugar</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="sugar" id="lim_sugar"
                                   value="<?= round($lim['sugar']) ?>" min="0" max="300" step="1" placeholder="50">
                            <span class="cgml-limit-unit">g / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-meat"></i> Protein</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="protein" id="lim_protein"
                                   value="<?= round($lim['protein']) ?>" min="0" max="400" step="1" placeholder="60">
                            <span class="cgml-limit-unit">g / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-droplet"></i> Fat</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="fat" id="lim_fat"
                                   value="<?= round($lim['fat']) ?>" min="0" max="300" step="1" placeholder="65">
                            <span class="cgml-limit-unit">g / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field">
                        <label><i class="ti ti-leaf"></i> Fiber</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="fiber" id="lim_fiber"
                                   value="<?= round($lim['fiber']) ?>" min="0" max="100" step="1" placeholder="25">
                            <span class="cgml-limit-unit">g / day</span>
                        </div>
                    </div>

                    <div class="cgml-limit-field" style="grid-column:1/-1;">
                        <label><i class="ti ti-circles"></i> Sodium</label>
                        <div class="cgml-limit-input-wrap">
                            <input type="number" name="sodium" id="lim_sodium"
                                   value="<?= round($lim['sodium']) ?>" min="0" max="10000" step="50" placeholder="2300">
                            <span class="cgml-limit-unit">mg / day</span>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        <div class="cgml-modal-footer">
            <button class="cgml-modal-reset" type="button" onclick="resetToDefaults()">
                <i class="ti ti-refresh"></i> Reset to defaults
            </button>
            <button class="cgml-modal-save" onclick="document.getElementById('limitsForm').submit()">
                <i class="ti ti-device-floppy"></i> Save Limits
            </button>
        </div>
    </div>
</div><!-- /.cgml-modal-overlay -->

<?php endif; ?>

<script>
// ── History drawer ─────────────────────────────────────
let activeFilter = 'all';
function openDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function filterDrawer() {
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), activeFilter);
}
function setFilter(f, btn) {
    activeFilter = f;
    document.querySelectorAll('.cgml-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), f);
}
function applyFilters(q, f) {
    let vis = 0;
    document.querySelectorAll('.cgml-tl-item').forEach(el => {
        const ok = (f === 'all' || el.dataset.type === f) && (!q || el.dataset.search.includes(q));
        el.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    document.querySelectorAll('.cgml-drawer-day-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.cgml-tl-item')].some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = vis === 0 ? 'flex' : 'none';
}

// ── Limits modal ───────────────────────────────────────
function openLimitsModal() {
    document.getElementById('limitsOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeLimitsModal() {
    document.getElementById('limitsOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function handleModalOverlayClick(e) {
    if (e.target === document.getElementById('limitsOverlay')) closeLimitsModal();
}
function resetToDefaults() {
    const defaults = {lim_carbs:130, lim_calories:1800, lim_sugar:50, lim_protein:60, lim_fat:65, lim_fiber:25, lim_sodium:2300};
    Object.entries(defaults).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (el) el.value = val;
    });
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeDrawer(); closeLimitsModal(); }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>
