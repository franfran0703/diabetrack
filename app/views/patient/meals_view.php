<?php
$pageTitle  = 'Meals & Carbs';
$activeMenu = 'meals';
ob_start();

/* ── Base variables ──────────────────────────────────── */
$firstName   = ucfirst(strtolower(explode(' ', trim($name))[0]));
$totalMeals  = (int)($todayTotals['total_meals']    ?? 0);
$totalCarbs  = (float)($todayTotals['total_carbs']  ?? 0);
$totalCals   = (float)($todayTotals['total_calories']?? 0);
$totalProt   = (float)($todayTotals['total_protein'] ?? 0);
$totalFat    = (float)($todayTotals['total_fat']     ?? 0);
$totalSugar  = (float)($todayTotals['total_sugar']   ?? 0);
$totalFiber  = (float)($todayTotals['total_fiber']   ?? 0);
$logCount    = count($logs ?? []);

/* ── Carb zone ───────────────────────────────────────── */
$carbPct  = $totalCarbs > 0 ? min(round($totalCarbs / 130 * 100), 100) : 0;
$carbZone = $carbPct >= 100 ? 'over' : ($carbPct >= 75 ? 'warn' : 'good');

/* ── Clinical flags ──────────────────────────────────── */
$avgProtPerMeal = $totalMeals > 0 ? $totalProt / $totalMeals : 0;
$lowProtein     = $avgProtPerMeal > 0 && $avgProtPerMeal < 15;
$fiberPct       = min(round($totalFiber / 25 * 100), 100);
$fiberGood      = $totalFiber >= 25;

/* ── Time-of-day clinical tip ────────────────────────── */
$hour    = (int) date('G');
$timeTip = null;
$hasType = [];
foreach ($todayLogs ?? [] as $l) { $hasType[$l['meal_type']] = true; }
if ($hour >= 6  && $hour < 10  && empty($hasType['Breakfast']))
    $timeTip = ['icon'=>'ti-sunrise', 'text'=>'Good morning! A high-protein breakfast stabilizes your blood sugar for the rest of the day. Aim for 20g+ protein.'];
elseif ($hour >= 11 && $hour < 14 && empty($hasType['Lunch']))
    $timeTip = ['icon'=>'ti-sun',     'text'=>'Lunch time — include fiber and protein to slow glucose absorption and avoid a post-meal spike.'];
elseif ($hour >= 17 && $hour < 20 && empty($hasType['Dinner']))
    $timeTip = ['icon'=>'ti-moon',    'text'=>'Avoid heavy carbs at dinner. Your body metabolizes carbohydrates less efficiently in the evening.'];
elseif ($hour >= 15 && $hour < 17)
    $timeTip = ['icon'=>'ti-apple',   'text'=>'Afternoon snack window. A small protein-rich snack now prevents blood sugar dips before dinner.'];

/* ── Meal slot tracker (today's 4 slots) ──────────────── */
$mealSlots = [
    ['type'=>'Breakfast','icon'=>'ti-sunrise','hour_start'=>6, 'hour_end'=>10],
    ['type'=>'Lunch',    'icon'=>'ti-sun',    'hour_start'=>11,'hour_end'=>14],
    ['type'=>'Dinner',   'icon'=>'ti-moon',   'hour_start'=>17,'hour_end'=>20],
    ['type'=>'Snack',    'icon'=>'ti-apple',  'hour_start'=>14,'hour_end'=>17],
];
$loggedByType = [];
foreach ($todayLogs ?? [] as $l) {
    $t = $l['meal_type'];
    if (!isset($loggedByType[$t])) $loggedByType[$t] = ['count'=>0,'carbs'=>0,'name'=>''];
    $loggedByType[$t]['count']++;
    $loggedByType[$t]['carbs'] += (float)$l['carbs'];
    $loggedByType[$t]['name']   = $l['meal_name']; // last logged name
}

/* ── Nutrition ring (macro % breakdown) ───────────────── */
$totalMacros   = $totalProt + $totalFat + ($totalCarbs > 0 ? $totalCarbs * 0.4 : 0); // weighted
$carbRingPct   = $totalCals > 0 ? round(($totalCarbs * 4 / $totalCals) * 100)  : 0;
$protRingPct   = $totalCals > 0 ? round(($totalProt  * 4 / $totalCals) * 100)  : 0;
$fatRingPct    = $totalCals > 0 ? round(($totalFat   * 9 / $totalCals) * 100)  : 0;
// ADA targets for type-2 diabetics:
$carbTarget    = 130;  // g/day max
$protTarget    = 50;   // g/day minimum
$fatTarget     = 65;   // g/day
$fiberTarget   = 25;   // g/day
$sugarTarget   = 25;   // g/day max (added sugar)

// Circumference for SVG ring (r=54): 2π×54 ≈ 339.3
$circ = 339.3;
$carbArc   = min($carbRingPct,  100) / 100 * $circ;
$protArc   = min($protRingPct,  100) / 100 * $circ;
$fatArc    = min($fatRingPct,   100) / 100 * $circ;

// Carb offset from top, protein follows, fat follows
$carbOffset = 0;
$protOffset = $carbArc;
$fatOffset  = $carbArc + $protArc;

// Clinical insight
$insight = null;
if ($totalMeals > 0) {
    if ($carbPct >= 100)
        $insight = ['icon'=>'ti-alert-triangle','badge'=>'bad',  'badgeText'=>'Action needed','text'=>'You\'ve hit your <strong>130g carb limit</strong> for today. Avoid starchy foods and sugary drinks for the rest of the day.'];
    elseif ($carbPct >= 75)
        $insight = ['icon'=>'ti-alert-circle',  'badge'=>'warn', 'badgeText'=>'Near limit',  'text'=>'You\'re at <strong>'.$carbPct.'%</strong> of your daily carb limit. Choose low-carb options for remaining meals.'];
    elseif ($totalFiber > 0 && $totalFiber >= 25)
        $insight = ['icon'=>'ti-leaf',           'badge'=>'good', 'badgeText'=>'Great job',   'text'=>'Excellent fiber intake today (<strong>'.round($totalFiber,1).'g</strong>). Fiber significantly slows glucose absorption after meals.'];
    elseif ($totalFiber > 0 && $totalFiber < 10)
        $insight = ['icon'=>'ti-leaf',           'badge'=>'warn', 'badgeText'=>'Low fiber',   'text'=>'Only <strong>'.round($totalFiber,1).'g</strong> fiber so far. Add vegetables or legumes — fiber is critical for post-meal glucose control.'];
    elseif ($lowProtein)
        $insight = ['icon'=>'ti-meat',           'badge'=>'warn', 'badgeText'=>'Low protein', 'text'=>'Low protein per meal (<strong>'.round($avgProtPerMeal,1).'g avg</strong>). Protein slows digestion and reduces blood sugar spikes.'];
    else
        $insight = ['icon'=>'ti-circle-check',   'badge'=>'good', 'badgeText'=>'On track',   'text'=>'Good nutritional balance today. Keep including protein and fiber with each meal to maintain stable blood glucose.'];
}

/* ── 7-day chart data (rebuild week buckets for bar chart) ── */
$carbsByDate = [];
foreach ($logs ?? [] as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $carbsByDate[$d]['carbs'] = ($carbsByDate[$d]['carbs'] ?? 0) + (float)$l['carbs'];
    $carbsByDate[$d]['count'] = ($carbsByDate[$d]['count'] ?? 0) + 1;
}
$weekDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dc   = $carbsByDate[$date] ?? ['carbs' => 0, 'count' => 0];
    $c    = (float)$dc['carbs'];
    $weekDays[] = ['date'=>$date,'carbs'=>$c,'count'=>$dc['count'],
        'state'=> $dc['count']===0 ? 'none' : ($c>130 ? 'over' : ($c>100 ? 'warn' : 'good'))];
}
$chartLabels = array_map(fn($d) => date('M d', strtotime($d['date'])), $weekDays);
$chartData   = array_map(fn($d) => round($d['carbs'], 1), $weekDays);
$chartColors = array_map(fn($d) => $d['state'] === 'over' ? '#ef4444' : ($d['state'] === 'warn' ? '#f59e0b' : '#F97447'), $weekDays);

/* ── Group all logs by date for drawer ───────────────── */
$logsByDate = [];
foreach ($logs ?? [] as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);

/* ── Drawer stats ────────────────────────────────────── */
$drawerBreakfast = count(array_filter($logs ?? [], fn($l) => $l['meal_type'] === 'Breakfast'));
$drawerLunch     = count(array_filter($logs ?? [], fn($l) => $l['meal_type'] === 'Lunch'));
$drawerDinner    = count(array_filter($logs ?? [], fn($l) => $l['meal_type'] === 'Dinner'));
$drawerSnack     = count(array_filter($logs ?? [], fn($l) => $l['meal_type'] === 'Snack'));

/* ── Flash detection ─────────────────────────────────── */
$flashDeleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="/diabetrack/public/assets/css/meals.css?v=<?= time() ?>" rel="stylesheet">

<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="meal-page-header">
    <div class="meal-page-header-left">
        <div class="meal-page-eyebrow">
            <i class="ti ti-salad"></i> Meals &amp; Carbs Tracker
        </div>
        <h1 class="meal-page-title">Daily <span>Nutrition</span></h1>
        <p class="meal-page-sub">Log your meals and track carbohydrate intake, <?= htmlspecialchars($firstName) ?>.</p>
    </div>
    <div class="meal-page-header-right">
        <div class="meal-today-badge <?= $carbPct >= 100 ? 'warn' : ($totalMeals > 0 ? 'logged' : 'not-logged') ?>">
            <i class="ti <?= $carbPct >= 100 ? 'ti-alert-triangle' : ($totalMeals > 0 ? 'ti-circle-check' : 'ti-clock') ?>"></i>
            <?php if ($carbPct >= 100): ?>Carb limit exceeded
            <?php elseif ($totalMeals > 0): ?><?= $totalMeals ?> meal<?= $totalMeals > 1 ? 's' : '' ?> logged today
            <?php else: ?>No meals logged today<?php endif; ?>
        </div>
        <?php if ($logCount > 0): ?>
        <button class="meal-history-btn" onclick="openHistoryDrawer()" aria-label="View all meal logs">
            <i class="ti ti-history"></i> All Meals
            <span class="meal-history-count"><?= $logCount ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ══ STAT CARDS ════════════════════════════════════════ -->
<div class="meal-stats-row">

    <!-- Carbs — primary -->
    <div class="meal-scard meal-scard-primary">
        <div class="meal-scard-top">
            <div class="meal-scard-icon"><i class="ti ti-grain"></i></div>
            <div class="meal-scard-badge">
                <i class="ti ti-<?= $carbZone === 'over' ? 'alert-triangle' : ($carbZone === 'warn' ? 'alert-circle' : 'circle-check') ?>"></i>
                <?= $carbZone === 'over' ? 'Exceeded' : ($carbZone === 'warn' ? 'Near Limit' : 'On Track') ?>
            </div>
        </div>
        <div class="meal-scard-val"><?= round($totalCarbs, 1) ?><small>g</small></div>
        <div class="meal-scard-label">Total Carbs Today</div>
        <div class="meal-scard-progress">
            <div class="meal-scard-track">
                <div class="meal-scard-fill <?= $carbZone ?>" style="width:<?= $carbPct ?>%;"></div>
            </div>
            <div class="meal-scard-progress-label"><?= $carbPct ?>% of 130g diabetic daily limit</div>
        </div>
        <div class="meal-scard-meta">
            <?php if ($totalFiber > 0): ?>
            <span class="meal-pill pill-good"><i class="ti ti-leaf"></i> <?= round($totalFiber,1) ?>g fiber (<?= $fiberPct ?>% of 25g goal)</span>
            <?php elseif ($totalMeals > 0): ?>
            <span class="meal-pill pill-warn"><i class="ti ti-leaf"></i> No fiber logged — add vegetables</span>
            <?php else: ?>
            <span class="meal-pill pill-neutral"><i class="ti ti-minus"></i> No meals yet</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Calories — secondary -->
    <div class="meal-scard meal-scard-secondary">
        <div class="meal-scard-top">
            <div class="meal-scard-icon"><i class="ti ti-flame"></i></div>
        </div>
        <div class="meal-scard-val"><?= round($totalCals) ?><small>kcal</small></div>
        <div class="meal-scard-label">Calories Consumed</div>
        <div class="meal-scard-meta">
            <?php if ($totalCals > 0):
                $calStatus = $totalCals < 1200 ? 'Low' : ($totalCals > 2200 ? 'High' : 'Normal'); ?>
            <span class="meal-pill <?= $totalCals > 2200 ? 'pill-warn' : 'pill-good' ?>">
                <i class="ti ti-chart-bar"></i> <?= $calStatus ?> intake
            </span>
            <?php if ($totalMeals > 0): ?>
            <span class="meal-pill pill-neutral"><i class="ti ti-divide"></i> <?= round($totalCals / $totalMeals) ?> kcal avg/meal</span>
            <?php endif; ?>
            <?php else: ?>
            <span class="meal-pill pill-neutral"><i class="ti ti-minus"></i> No data yet</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Protein + Fat — tertiary -->
    <div class="meal-scard meal-scard-tertiary">
        <div class="meal-scard-top">
            <div class="meal-scard-icon"><i class="ti ti-meat"></i></div>
        </div>
        <div class="meal-scard-val"><?= round($totalProt, 1) ?><small>g</small></div>
        <div class="meal-scard-label">Protein Today</div>
        <div class="meal-scard-meta">
            <?php if ($totalProt > 0): ?>
            <span class="meal-pill <?= $lowProtein ? 'pill-warn' : 'pill-good' ?>">
                <i class="ti ti-<?= $lowProtein ? 'alert-circle' : 'circle-check' ?>"></i>
                <?= $lowProtein ? 'Low — aim for 15g+/meal' : 'Good protein balance' ?>
            </span>
            <span class="meal-pill pill-neutral"><i class="ti ti-droplet"></i> <?= round($totalFat,1) ?>g fat</span>
            <?php else: ?>
            <span class="meal-pill pill-neutral"><i class="ti ti-minus"></i> No data yet</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══ MEAL SLOT TRACKER ════════════════════════════════ -->
<div class="meal-slot-strip">
    <?php
    $slotOrder = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
    $slotIcons = ['Breakfast'=>'ti-sunrise','Lunch'=>'ti-sun','Dinner'=>'ti-moon','Snack'=>'ti-apple'];
    foreach ($slotOrder as $sType):
        $logged   = isset($loggedByType[$sType]);
        $isPast   = false;
        $upcoming = false;
        if ($sType === 'Breakfast') { $isPast = $hour >= 10; $upcoming = $hour < 6; }
        elseif ($sType === 'Lunch')   { $isPast = $hour >= 14; $upcoming = $hour < 11; }
        elseif ($sType === 'Dinner')  { $isPast = $hour >= 20; $upcoming = $hour < 17; }
        elseif ($sType === 'Snack')   { $isPast = $hour >= 17; $upcoming = $hour < 14; }
        $state = $logged ? 'logged' : ($upcoming ? 'upcoming' : 'missed');
    ?>
    <div class="meal-slot <?= $state ?>">
        <?php if ($logged): ?>
        <div class="meal-slot-check"><i class="ti ti-check"></i></div>
        <?php endif; ?>
        <div class="meal-slot-icon"><i class="ti <?= $slotIcons[$sType] ?>"></i></div>
        <div class="meal-slot-name"><?= $sType ?></div>
        <?php if ($logged): ?>
        <div class="meal-slot-detail">
            <?= $loggedByType[$sType]['count'] ?> meal<?= $loggedByType[$sType]['count']>1?'s':'' ?><br>
            <?= round($loggedByType[$sType]['carbs'],1) ?>g carbs
        </div>
        <?php elseif ($upcoming): ?>
        <div class="meal-slot-detail">Upcoming</div>
        <?php else: ?>
        <div class="meal-slot-detail">Not logged</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══ MAIN GRID ════════════════════════════════════════ -->
<div class="meal-main-grid">

    <!-- Today's Meals Card -->
    <div class="meal-today-card">

        <!-- Orange gradient hero -->
        <div class="meal-today-hero">
            <div class="meal-today-hero-top">
                <div>
                    <div class="meal-today-hero-label">Today's Meals</div>
                    <div class="meal-today-hero-date"><?= date('l, F j') ?></div>
                </div>
                <?php if ($carbPct >= 100): ?>
                <div class="meal-today-hero-badge over"><i class="ti ti-alert-triangle"></i> Over limit</div>
                <?php elseif ($carbPct >= 75): ?>
                <div class="meal-today-hero-badge warn"><i class="ti ti-alert-circle"></i> Near limit</div>
                <?php elseif ($totalMeals > 0): ?>
                <div class="meal-today-hero-badge"><i class="ti ti-circle-check"></i> On track</div>
                <?php endif; ?>
            </div>

            <?php if ($totalMeals > 0): ?>
            <div class="meal-macro-bar">
                <div class="meal-macro-item">
                    <div class="meal-macro-val"><?= round($totalCarbs,1) ?><span class="meal-macro-unit">g</span></div>
                    <div class="meal-macro-name">Carbs</div>
                </div>
                <div class="meal-macro-item">
                    <div class="meal-macro-val"><?= round($totalProt,1) ?><span class="meal-macro-unit">g</span></div>
                    <div class="meal-macro-name">Protein</div>
                </div>
                <div class="meal-macro-item">
                    <div class="meal-macro-val"><?= round($totalFat,1) ?><span class="meal-macro-unit">g</span></div>
                    <div class="meal-macro-name">Fat</div>
                </div>
                <div class="meal-macro-item">
                    <div class="meal-macro-val"><?= round($totalFiber,1) ?><span class="meal-macro-unit">g</span></div>
                    <div class="meal-macro-name">Fiber</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($timeTip): ?>
            <div class="meal-tip-row">
                <i class="ti <?= $timeTip['icon'] ?>"></i>
                <div class="meal-tip-text"><?= htmlspecialchars($timeTip['text']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Meal list -->
        <div class="meal-today-body">
            <?php if (empty($todayLogs)): ?>
            <div class="meal-empty">
                <i class="ti ti-salad" style="font-size:3rem;color:rgba(249,116,71,0.3);"></i>
                <p>No meals logged today yet.</p>
                <p style="font-size:11px;color:#c4a090;">Tap the button below to log your first meal.</p>
                <button class="meal-empty-cta" onclick="openAddMealModal()">
                    <i class="ti ti-plus"></i> Log First Meal
                </button>
            </div>
            <?php else: ?>
            <div id="today-meals-list">
                <?php foreach ($todayLogs as $meal):
                    $typeIcon = match($meal['meal_type']) { 'Breakfast'=>'ti-sunrise','Lunch'=>'ti-sun','Dinner'=>'ti-moon',default=>'ti-apple' };
                ?>
                <div class="meal-item" id="meal-row-<?= $meal['id'] ?>">
                    <div class="meal-item-icon"><i class="ti <?= $typeIcon ?>"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="meal-item-name"><?= htmlspecialchars($meal['meal_name']) ?></div>
                        <div class="meal-item-meta">
                            <?php if ($meal['calories']): ?><span><i class="ti ti-flame"></i><?= $meal['calories'] ?> kcal</span><?php endif; ?>
                            <?php if ($meal['protein']):  ?><span><i class="ti ti-meat"></i><?= $meal['protein'] ?>g protein</span><?php endif; ?>
                            <?php if (!empty($meal['fiber']) && $meal['fiber'] > 0): ?><span><i class="ti ti-leaf"></i><?= $meal['fiber'] ?>g fiber</span><?php endif; ?>
                            <span><i class="ti ti-clock"></i><?= date('h:i A', strtotime($meal['logged_at'])) ?></span>
                        </div>
                    </div>
                    <span class="meal-type-badge"><?= $meal['meal_type'] ?></span>
                    <div class="meal-item-carbs"><?= $meal['carbs'] ?><small>g carbs</small></div>
                    <button class="meal-del-btn"
                            data-id="<?= $meal['id'] ?>"
                            data-name="<?= htmlspecialchars($meal['meal_name']) ?>"
                            onclick="confirmDeleteMeal(this)"
                            aria-label="Delete meal">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /.meal-today-card -->

    <!-- Quick Add Panel — sticky sidebar -->
    <div class="meal-qa-panel" id="qaPanel">
        <div class="meal-qa-panel-header">
            <div class="meal-section-label" style="margin-bottom:0;">
                <i class="ti ti-bolt"></i> Quick Add
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button class="qa-save-btn" onclick="openSavePresetModal()">
                    <i class="ti ti-bookmark"></i> Save Meal
                </button>
                <button class="meal-qa-panel-close" id="qaPanelClose" onclick="closeQaPanel()" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>

        <div class="qa-tabs">
            <button class="qa-tab active" onclick="switchQaTab('suggested',this)"><i class="ti ti-star"></i> Suggested</button>
            <button class="qa-tab" onclick="switchQaTab('saved',this)"><i class="ti ti-bookmark"></i> My Meals</button>
        </div>

        <!-- Suggested presets -->
        <div class="qa-list" id="qa-suggested">
            <?php foreach ($defaultPresets as $preset): ?>
            <div class="qa-list-item" onclick="quickAdd(<?= htmlspecialchars(json_encode($preset)) ?>)">
                <div class="qa-list-emoji"><?= $preset['emoji'] ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="qa-list-name"><?= htmlspecialchars($preset['meal_name']) ?></div>
                    <div class="qa-list-meta"><?= $preset['carbs'] ?>g carbs · <?= $preset['calories'] ?> kcal · <?= $preset['meal_type'] ?></div>
                </div>
                <div class="qa-list-add"><i class="ti ti-plus"></i></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Saved presets -->
        <div class="qa-list" id="qa-saved" style="display:none;">
            <?php if (empty($userPresets)): ?>
            <div class="qa-empty">
                <i class="ti ti-bookmark-off"></i>
                <div class="qa-empty-text">No saved meals yet.</div>
                <div class="qa-empty-sub">Click "Save Meal" to add favorites for fast logging.</div>
            </div>
            <?php else: ?>
            <?php foreach ($userPresets as $preset): ?>
            <div class="qa-list-item" id="preset-row-<?= $preset['id'] ?>"
                 onclick="quickAdd(<?= htmlspecialchars(json_encode([
                     'meal_name'=>$preset['meal_name'],'meal_type'=>$preset['meal_type'],
                     'carbs'=>$preset['carbs'],'calories'=>$preset['calories'],
                     'sugar'=>$preset['sugar'],'protein'=>$preset['protein'],
                     'fat'=>$preset['fat'],'fiber'=>$preset['fiber'],'sodium'=>$preset['sodium'],
                 ])) ?>)">
                <div class="qa-list-icon"><i class="ti ti-bookmark-filled"></i></div>
                <div style="flex:1;min-width:0;">
                    <div class="qa-list-name"><?= htmlspecialchars($preset['meal_name']) ?></div>
                    <div class="qa-list-meta"><?= $preset['carbs'] ?>g carbs<?= $preset['calories'] ? ' · '.$preset['calories'].' kcal' : '' ?> · <?= $preset['meal_type'] ?></div>
                </div>
                <a href="#" class="qa-list-del"
                   onclick="event.stopPropagation(); deletePreset(<?= $preset['id'] ?>, this); return false;"
                   title="Remove">
                    <i class="ti ti-x"></i>
                </a>
                <div class="qa-list-add"><i class="ti ti-plus"></i></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div><!-- /.meal-qa-panel -->

</div><!-- /.meal-main-grid -->

<!-- ══ NUTRITION TRIGGER BAR ═════════════════════════════ -->
<button class="meal-nutrition-trigger" onclick="openModal('nutritionModal')" <?= $totalMeals === 0 ? 'disabled style="opacity:0.55;cursor:default;"' : '' ?>>
    <div class="meal-nutrition-trigger-icon"><i class="ti ti-chart-pie"></i></div>
    <div>
        <div class="meal-nutrition-trigger-title">Today's Nutrition Breakdown</div>
        <div class="meal-nutrition-trigger-sub">
            <?= $totalMeals > 0 ? 'Macros, targets &amp; dietary insights — tap to view' : 'Log a meal to unlock your nutrition breakdown' ?>
        </div>
    </div>
    <?php if ($totalMeals > 0): ?>
    <div class="meal-nutrition-trigger-pills">
        <span class="meal-nutrition-trigger-pill"><i class="ti ti-grain"></i><?= round($totalCarbs, 1) ?>g carbs</span>
        <span class="meal-nutrition-trigger-pill"><i class="ti ti-meat"></i><?= round($totalProt, 1) ?>g protein</span>
        <?php if ($totalCals > 0): ?><span class="meal-nutrition-trigger-pill"><i class="ti ti-flame"></i><?= round($totalCals) ?> kcal</span><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="meal-nutrition-trigger-arrow"><i class="ti ti-chevron-right"></i></div>
</button>


<!-- ══ 7-DAY CARB CHART ══════════════════════════════════ -->
<div class="meal-chart-card">
    <div class="meal-chart-header">
        <div class="meal-section-label" style="margin-bottom:0;">
            <i class="ti ti-chart-bar"></i> 7-Day Carb Trend
        </div>
        <div class="meal-chart-legend">
            <div class="meal-chart-legend-item"><span class="meal-chart-legend-dot" style="background:#F97447;"></span> Under limit</div>
            <div class="meal-chart-legend-item"><span class="meal-chart-legend-dot" style="background:#f59e0b;"></span> Near 130g</div>
            <div class="meal-chart-legend-item"><span class="meal-chart-legend-dot" style="background:#ef4444;"></span> Over limit</div>
        </div>
    </div>
    <div class="meal-chart-wrap">
        <canvas id="carbChart"></canvas>
    </div>
</div>

<!-- ══ HISTORY DRAWER OVERLAY ════════════════════════════ -->
<div class="meal-drawer-overlay" id="drawerOverlay" onclick="closeHistoryDrawer()"></div>

<!-- ══ HISTORY DRAWER ════════════════════════════════════ -->
<div class="meal-drawer" id="historyDrawer" role="dialog" aria-label="All Meal Logs" aria-modal="true">

    <div class="meal-drawer-header">
        <div class="meal-drawer-header-left">
            <div class="meal-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="meal-drawer-title">All Meal Logs</div>
                <div class="meal-drawer-sub"><?= $logCount ?> total · <?= $drawerBreakfast ?> breakfast · <?= $drawerLunch ?> lunch · <?= $drawerDinner ?> dinner</div>
            </div>
        </div>
        <button class="meal-drawer-close" onclick="closeHistoryDrawer()" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <div class="meal-drawer-controls">
        <div class="meal-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search meals, notes…" oninput="filterDrawer()">
        </div>
        <div class="meal-drawer-filters">
            <button class="meal-drawer-filter active" data-mdf="all"       onclick="setDrawerFilter('all',this)">All</button>
            <button class="meal-drawer-filter"         data-mdf="Breakfast" onclick="setDrawerFilter('Breakfast',this)">
                <span class="meal-df-dot breakfast"></span> Breakfast
            </button>
            <button class="meal-drawer-filter"         data-mdf="Lunch"     onclick="setDrawerFilter('Lunch',this)">
                <span class="meal-df-dot lunch"></span> Lunch
            </button>
            <button class="meal-drawer-filter"         data-mdf="Dinner"    onclick="setDrawerFilter('Dinner',this)">
                <span class="meal-df-dot dinner"></span> Dinner
            </button>
            <button class="meal-drawer-filter"         data-mdf="Snack"     onclick="setDrawerFilter('Snack',this)">
                <span class="meal-df-dot snack"></span> Snack
            </button>
        </div>
    </div>

    <div class="meal-drawer-body" id="drawerBody">
        <?php if (empty($logs)): ?>
        <div class="meal-drawer-empty">
            <i class="ti ti-salad"></i>
            <p>No meal logs yet.</p>
        </div>
        <?php else: ?>

        <?php foreach ($logsByDate as $date => $dayLogs):
            $dayCarbs    = array_sum(array_column($dayLogs, 'carbs'));
            $dayMeals    = count($dayLogs);
            $dayOver     = $dayCarbs > 130;
            $dayWarn     = !$dayOver && $dayCarbs > 100;
            $isDateToday = $date === date('Y-m-d');
            $isYesterday = $date === date('Y-m-d', strtotime('-1 day'));
            $dateLabel   = $isDateToday ? 'Today' : ($isYesterday ? 'Yesterday' : date('l, M j', strtotime($date)));
        ?>
        <div class="meal-drawer-day-group" data-date="<?= $date ?>">
            <div class="meal-drawer-day-header">
                <div class="meal-drawer-day-label">
                    <?= $dateLabel ?>
                    <?php if ($isDateToday): ?><span class="meal-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="meal-drawer-day-stats">
                    <span><?= $dayMeals ?> meal<?= $dayMeals > 1 ? 's' : '' ?></span>
                    <span class="meal-drawer-day-carbs"><?= round($dayCarbs, 1) ?>g carbs</span>
                    <?php if ($dayOver): ?>
                    <span class="meal-drawer-day-flag over"><i class="ti ti-alert-triangle"></i> Over limit</span>
                    <?php elseif ($dayWarn): ?>
                    <span class="meal-drawer-day-flag warn"><i class="ti ti-alert-circle"></i> Near limit</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="meal-timeline">
                <?php foreach ($dayLogs as $log):
                    $carbs     = (float)$log['carbs'];
                    $dotState  = $carbs > 45 ? 'over' : ($carbs > 30 ? 'warn' : 'good');
                    $dotIcon   = $dotState === 'over' ? 'ti-alert-triangle' : ($dotState === 'warn' ? 'ti-alert-circle' : 'ti-circle-check');
                    $barPct    = min(round(($carbs / 60) * 100), 100);
                    $typeIcon  = match($log['meal_type']) { 'Breakfast'=>'ti-sunrise','Lunch'=>'ti-sun','Dinner'=>'ti-moon',default=>'ti-apple' };
                    $carbLabel = $carbs > 45 ? 'over' : ($carbs > 30 ? 'warn' : 'good');
                    $carbText  = $carbs > 45 ? 'High Carb' : ($carbs > 30 ? 'Moderate' : 'Low Carb');
                ?>
                <div class="meal-timeline-item"
                     data-type="<?= $log['meal_type'] ?>"
                     data-search="<?= strtolower(htmlspecialchars($log['meal_name'] . ' ' . $log['meal_type'] . ' ' . ($log['notes'] ?? ''))) ?>">
                    <div class="meal-timeline-spine">
                        <div class="meal-timeline-dot <?= $dotState ?>">
                            <i class="ti <?= $dotIcon ?>"></i>
                        </div>
                        <div class="meal-timeline-line"></div>
                    </div>
                    <div class="meal-timeline-card">
                        <div class="meal-tl-top">
                            <div class="meal-tl-name"><?= htmlspecialchars($log['meal_name']) ?></div>
                            <div class="meal-tl-right">
                                <span class="meal-tl-time">
                                    <i class="ti ti-clock"></i>
                                    <?= date('h:i A', strtotime($log['logged_at'])) ?>
                                </span>
                                <button class="meal-tl-del"
                                        data-id="<?= $log['id'] ?>"
                                        data-name="<?= htmlspecialchars($log['meal_name']) ?>"
                                        onclick="confirmDeleteLog(this)"
                                        aria-label="Delete meal">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="meal-tl-bar-track">
                            <div class="meal-tl-bar-fill <?= $carbLabel ?>" style="width:<?= $barPct ?>%;"></div>
                            <div class="meal-tl-bar-line" style="left:50%;"></div>
                        </div>
                        <div class="meal-tl-meta">
                            <span class="meal-tl-type"><i class="ti <?= $typeIcon ?>"></i><?= $log['meal_type'] ?></span>
                            <span class="meal-tl-carbs"><?= $log['carbs'] ?>g carbs</span>
                            <span class="meal-tl-status <?= $carbLabel ?>"><?= $carbText ?></span>
                            <?php if ($log['calories']): ?>
                            <span style="color:#b8927e;font-size:11px;display:flex;align-items:center;gap:3px;"><i class="ti ti-flame" style="font-size:12px;"></i><?= $log['calories'] ?> kcal</span>
                            <?php endif; ?>
                            <?php if (!empty($log['notes'])): ?>
                            <span style="color:#b8927e;font-style:italic;font-size:11px;display:flex;align-items:center;gap:3px;overflow:hidden;max-width:160px;white-space:nowrap;text-overflow:ellipsis;">
                                <i class="ti ti-notes" style="font-size:12px;flex-shrink:0;"></i>
                                <?= htmlspecialchars($log['notes']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <div class="meal-drawer-footer">
        <div class="meal-drawer-no-results" id="drawerNoResults">
            <i class="ti ti-search-off"></i> No meals match your search.
        </div>
        <div class="meal-drawer-footer-stats">
            <span><i class="ti ti-sunrise" style="color:#F97447;"></i> <?= $drawerBreakfast ?> Breakfast</span>
            <span><i class="ti ti-sun"     style="color:#d95f2b;"></i> <?= $drawerLunch ?> Lunch</span>
            <span><i class="ti ti-moon"    style="color:#c04a20;"></i> <?= $drawerDinner ?> Dinner</span>
            <span><i class="ti ti-apple"   style="color:#a83818;"></i> <?= $drawerSnack ?> Snack</span>
        </div>
    </div>

</div><!-- /.meal-drawer -->

<!-- ══ NUTRITION BREAKDOWN MODAL ════════════════════════ -->
<div class="meal-modal-overlay" id="nutritionModal" onclick="overlayCloseModal(event,'nutritionModal')" aria-modal="true" role="dialog">
    <div class="meal-modal" style="max-width:600px;">
        <div class="meal-modal-header">
            <div class="meal-modal-header-left">
                <div class="meal-modal-icon"><i class="ti ti-chart-pie"></i></div>
                <div>
                    <div class="meal-modal-title">Nutrition Breakdown</div>
                    <div class="meal-modal-sub"><?= date('l, F j') ?> · <?= $totalMeals ?> meal<?= $totalMeals !== 1 ? 's' : '' ?> · <?= round($totalCals) ?> kcal</div>
                </div>
            </div>
            <button class="meal-modal-close" onclick="closeModal('nutritionModal')" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <div class="meal-modal-body" style="padding-bottom:24px;">

            <?php if ($totalMeals > 0): ?>
            <div class="meal-nutrition-content">

                <!-- Left: stacked macro bars -->
                <div class="meal-macro-stack">
                    <?php
                    $macroRows = [
                        ['label'=>'Carbohydrates','icon'=>'ti-grain',  'val'=>$totalCarbs,'target'=>$carbTarget, 'class'=>'carbs',  'unit'=>'g'],
                        ['label'=>'Protein',      'icon'=>'ti-meat',   'val'=>$totalProt, 'target'=>$protTarget, 'class'=>'protein','unit'=>'g'],
                        ['label'=>'Fat',          'icon'=>'ti-droplet','val'=>$totalFat,  'target'=>$fatTarget,  'class'=>'fat',    'unit'=>'g'],
                        ['label'=>'Fiber',        'icon'=>'ti-leaf',   'val'=>$totalFiber,'target'=>$fiberTarget,'class'=>'fiber',  'unit'=>'g'],
                        ['label'=>'Sugar',        'icon'=>'ti-candy',  'val'=>$totalSugar,'target'=>$sugarTarget,'class'=>'sugar',  'unit'=>'g'],
                    ];
                    foreach ($macroRows as $row):
                        $pct = $row['target'] > 0 ? min(round($row['val'] / $row['target'] * 100), 100) : 0;
                    ?>
                    <div class="meal-macro-row">
                        <div class="meal-macro-row-top">
                            <div class="meal-macro-row-name"><i class="ti <?= $row['icon'] ?>"></i> <?= $row['label'] ?></div>
                            <div class="meal-macro-row-val"><?= round($row['val'],1) ?><?= $row['unit'] ?></div>
                        </div>
                        <div class="meal-macro-row-track">
                            <div class="meal-macro-row-fill <?= $row['class'] ?>" style="width:<?= $pct ?>%;"></div>
                        </div>
                        <div class="meal-macro-row-goal"><?= $pct ?>% of <?= $row['target'] ?><?= $row['unit'] ?> daily target</div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right: donut ring -->
                <div class="meal-macro-ring-wrap">
                    <div class="meal-ring-svg-wrap">
                        <svg class="meal-ring-svg" width="140" height="140" viewBox="0 0 140 140">
                            <circle class="meal-ring-track" cx="70" cy="70" r="54"/>
                            <circle class="meal-ring-segment" cx="70" cy="70" r="54"
                                stroke="#F97447"
                                stroke-dasharray="<?= $carbArc ?> <?= $circ - $carbArc ?>"
                                stroke-dashoffset="0"/>
                            <circle class="meal-ring-segment" cx="70" cy="70" r="54"
                                stroke="#d95f2b"
                                stroke-dasharray="<?= $protArc ?> <?= $circ - $protArc ?>"
                                stroke-dashoffset="-<?= $protOffset ?>"/>
                            <circle class="meal-ring-segment" cx="70" cy="70" r="54"
                                stroke="#c04a20"
                                stroke-dasharray="<?= $fatArc ?> <?= $circ - $fatArc ?>"
                                stroke-dashoffset="-<?= $fatOffset ?>"/>
                        </svg>
                        <div class="meal-ring-center">
                            <div class="meal-ring-center-val"><?= round($totalCals) ?></div>
                            <div class="meal-ring-center-sub">kcal</div>
                        </div>
                    </div>
                    <div class="meal-ring-legend">
                        <div class="meal-ring-legend-item"><span class="meal-ring-legend-dot" style="background:#F97447;"></span> Carbs (<?= $carbRingPct ?>%)</div>
                        <div class="meal-ring-legend-item"><span class="meal-ring-legend-dot" style="background:#d95f2b;"></span> Protein (<?= $protRingPct ?>%)</div>
                        <div class="meal-ring-legend-item"><span class="meal-ring-legend-dot" style="background:#c04a20;"></span> Fat (<?= $fatRingPct ?>%)</div>
                    </div>
                </div>
            </div>

            <?php if ($insight): ?>
            <div class="meal-insight-row" style="margin-top:18px;">
                <div class="meal-insight-icon"><i class="ti <?= $insight['icon'] ?>"></i></div>
                <div class="meal-insight-text"><?= $insight['text'] ?></div>
                <span class="meal-insight-badge <?= $insight['badge'] ?>"><?= $insight['badgeText'] ?></span>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="meal-empty" style="padding:48px 0;">
                <i class="ti ti-chart-pie" style="font-size:2.5rem;color:rgba(249,116,71,0.3);"></i>
                <p>Log your first meal to see your nutrition breakdown.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ══ ADD MEAL MODAL ══════════════════════════════════════ -->
<div class="meal-modal-overlay" id="addMealModal" onclick="overlayCloseModal(event,'addMealModal')" aria-modal="true" role="dialog">
    <div class="meal-modal">
        <div class="meal-modal-header">
            <div class="meal-modal-header-left">
                <div class="meal-modal-icon"><i class="ti ti-salad"></i></div>
                <div>
                    <div class="meal-modal-title">Log a Meal</div>
                    <div class="meal-modal-sub">Record your meal and nutritional details</div>
                </div>
            </div>
            <button class="meal-modal-close" onclick="closeModal('addMealModal')" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <form method="POST" action="/diabetrack/public/patient/meals" id="addMealForm">
            <div class="meal-modal-body">
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-pencil"></i> Meal Name <span class="meal-required">*</span></label>
                    <input type="text" name="meal_name" id="add-meal-name" class="meal-form-input" placeholder="e.g. Sinangag, Adobo, Rice and Chicken" required>
                </div>
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-category"></i> Meal Type <span class="meal-required">*</span></label>
                    <div class="meal-type-grid">
                        <button type="button" class="meal-type-btn selected" onclick="selectMealType('Breakfast',this,'add-meal-type')"><i class="ti ti-sunrise"></i> Breakfast</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Lunch',this,'add-meal-type')"><i class="ti ti-sun"></i> Lunch</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Dinner',this,'add-meal-type')"><i class="ti ti-moon"></i> Dinner</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Snack',this,'add-meal-type')"><i class="ti ti-apple"></i> Snack</button>
                    </div>
                    <input type="hidden" name="meal_type" id="add-meal-type" value="Breakfast">
                </div>
                <div class="meal-form-divider">Required</div>
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-grain"></i> Carbohydrates (g) <span class="meal-required">*</span></label>
                    <input type="number" step="0.01" name="carbs" id="add-meal-carbs" class="meal-form-input" placeholder="e.g. 45" min="0" required>
                </div>
                <div class="meal-form-divider">Optional Nutrition</div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div>
                        <label class="meal-form-label">Calories <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="calories" id="add-meal-calories" class="meal-form-input" placeholder="e.g. 350" min="0">
                    </div>
                    <div>
                        <label class="meal-form-label">Sugar (g) <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="sugar" id="add-meal-sugar" class="meal-form-input" placeholder="e.g. 12" min="0">
                    </div>
                </div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div>
                        <label class="meal-form-label">Protein (g) <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="protein" id="add-meal-protein" class="meal-form-input" placeholder="e.g. 25" min="0">
                    </div>
                    <div>
                        <label class="meal-form-label">Fat (g) <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="fat" id="add-meal-fat" class="meal-form-input" placeholder="e.g. 8" min="0">
                    </div>
                </div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div>
                        <label class="meal-form-label">Fiber (g) <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="fiber" id="add-meal-fiber" class="meal-form-input" placeholder="e.g. 3" min="0">
                    </div>
                    <div>
                        <label class="meal-form-label">Sodium (mg) <span class="meal-optional">optional</span></label>
                        <input type="number" step="0.01" name="sodium" id="add-meal-sodium" class="meal-form-input" placeholder="e.g. 420" min="0">
                    </div>
                </div>
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-notes"></i> Notes <span class="meal-label-optional">(optional)</span></label>
                    <textarea name="notes" class="meal-form-textarea" rows="2" placeholder="e.g. Home cooked, restaurant, portion size…"></textarea>
                </div>
            </div>
            <div class="meal-modal-footer">
                <button type="button" class="meal-modal-cancel" onclick="closeModal('addMealModal')">Cancel</button>
                <button type="submit" class="meal-save-btn"><i class="ti ti-device-floppy"></i> Log Meal</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ SAVE PRESET MODAL ══════════════════════════════════ -->
<div class="meal-modal-overlay" id="savePresetModal" onclick="overlayCloseModal(event,'savePresetModal')" aria-modal="true" role="dialog">
    <div class="meal-modal">
        <div class="meal-modal-header">
            <div class="meal-modal-header-left">
                <div class="meal-modal-icon"><i class="ti ti-bookmark"></i></div>
                <div>
                    <div class="meal-modal-title">Save a Meal</div>
                    <div class="meal-modal-sub">Add to your quick-add list for fast logging</div>
                </div>
            </div>
            <button class="meal-modal-close" onclick="closeModal('savePresetModal')" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <form method="POST" action="/diabetrack/public/patient/meals">
            <input type="hidden" name="action" value="save_preset">
            <div class="meal-modal-body">
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-pencil"></i> Meal Name <span class="meal-required">*</span></label>
                    <input type="text" name="meal_name" class="meal-form-input" placeholder="e.g. Sinangag, Adobo…" required>
                </div>
                <div class="meal-form-group">
                    <label class="meal-form-label"><i class="ti ti-category"></i> Meal Type <span class="meal-required">*</span></label>
                    <div class="meal-type-grid">
                        <button type="button" class="meal-type-btn selected" onclick="selectMealType('Breakfast',this,'preset-meal-type')"><i class="ti ti-sunrise"></i> Breakfast</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Lunch',this,'preset-meal-type')"><i class="ti ti-sun"></i> Lunch</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Dinner',this,'preset-meal-type')"><i class="ti ti-moon"></i> Dinner</button>
                        <button type="button" class="meal-type-btn" onclick="selectMealType('Snack',this,'preset-meal-type')"><i class="ti ti-apple"></i> Snack</button>
                    </div>
                    <input type="hidden" name="meal_type" id="preset-meal-type" value="Breakfast">
                </div>
                <div class="meal-form-divider">Nutrition</div>
                <div class="meal-form-group">
                    <label class="meal-form-label">Carbohydrates (g) <span class="meal-required">*</span></label>
                    <input type="number" step="0.01" name="carbs" class="meal-form-input" placeholder="e.g. 45" min="0" required>
                </div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div><label class="meal-form-label">Calories <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="calories" class="meal-form-input" placeholder="e.g. 350" min="0"></div>
                    <div><label class="meal-form-label">Protein (g) <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="protein" class="meal-form-input" placeholder="e.g. 25" min="0"></div>
                </div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div><label class="meal-form-label">Fat (g) <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="fat" class="meal-form-input" placeholder="e.g. 8" min="0"></div>
                    <div><label class="meal-form-label">Fiber (g) <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="fiber" class="meal-form-input" placeholder="e.g. 3" min="0"></div>
                </div>
                <div class="meal-form-grid-2 meal-form-group">
                    <div><label class="meal-form-label">Sugar (g) <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="sugar" class="meal-form-input" placeholder="e.g. 12" min="0"></div>
                    <div><label class="meal-form-label">Sodium (mg) <span class="meal-optional">optional</span></label><input type="number" step="0.01" name="sodium" class="meal-form-input" placeholder="e.g. 420" min="0"></div>
                </div>
            </div>
            <div class="meal-modal-footer">
                <button type="button" class="meal-modal-cancel" onclick="closeModal('savePresetModal')">Cancel</button>
                <button type="submit" class="meal-save-btn"><i class="ti ti-bookmark"></i> Save to My Meals</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ TOASTS ════════════════════════════════════════════ -->
<!-- Save success toast -->
<div class="meal-toast meal-toast-success" id="saveToast" aria-live="polite">
    <i class="ti ti-circle-check"></i>
    <span>Meal logged successfully</span>
    <button class="meal-toast-close" onclick="hideToast('saveToast')" aria-label="Dismiss"><i class="ti ti-x"></i></button>
</div>

<!-- Delete toast with undo -->
<div class="meal-toast" id="deleteToast" aria-live="polite">
    <i class="ti ti-trash"></i>
    <span id="toastMsg">Meal deleted</span>
    <button class="meal-toast-undo" id="toastUndo">Undo</button>
    <button class="meal-toast-close" id="toastClose" aria-label="Dismiss"><i class="ti ti-x"></i></button>
</div>

<!-- ══ FAB ════════════════════════════════════════════════ -->
<button class="patient-fab" onclick="openAddMealModal()" aria-label="Log a meal">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Log Meal</span>
</button>

<!-- ══ CHART JS ══════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const ctx = document.getElementById('carbChart').getContext('2d');
    const limitPlugin = {
        id: 'limitLine',
        beforeDraw(chart) {
            const { ctx, chartArea: { left, right }, scales: { y } } = chart;
            if (!y) return;
            const y130 = y.getPixelForValue(130);
            ctx.save();
            ctx.setLineDash([6, 4]);
            ctx.lineWidth = 1.5;
            ctx.strokeStyle = 'rgba(239,68,68,0.45)';
            ctx.beginPath(); ctx.moveTo(left, y130); ctx.lineTo(right, y130); ctx.stroke();
            ctx.fillStyle = 'rgba(239,68,68,0.6)';
            ctx.font = '700 10px DM Sans';
            ctx.fillText('130g limit', right - 68, y130 - 5);
            ctx.restore();
        }
    };
    new Chart(ctx, {
        type: 'bar',
        plugins: [limitPlugin],
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Carbs (g)',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: <?= json_encode($chartColors) ?>,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a0800',
                    titleColor: '#fbab6e',
                    bodyColor: '#fff8f5',
                    borderColor: 'rgba(249,116,71,0.2)',
                    borderWidth: 1, padding: 12, cornerRadius: 12,
                    callbacks: {
                        label: c => ` ${c.parsed.y}g carbs${c.parsed.y > 130 ? ' — over daily limit!' : ''}`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(249,116,71,0.07)' }, ticks: { font: { size:11, family:'DM Sans' }, color:'#b8927e' }, border: { color:'transparent' } },
                x: { grid: { display: false }, ticks: { font: { size:11, family:'DM Sans' }, color:'#b8927e' }, border: { color:'transparent' } }
            }
        }
    });
})();
</script>

<!-- ══ INTERACTIVITY ══════════════════════════════════════ -->
<script>
/* ── Toast helpers ─────────────────────────────────────── */
function showToast(id, duration) {
    const t = document.getElementById(id);
    if (!t) return;
    t.classList.add('show');
    if (duration > 0) setTimeout(() => t.classList.remove('show'), duration);
}
function hideToast(id) {
    const t = document.getElementById(id);
    if (t) t.classList.remove('show');
}

/* Flash → toast on load (mirrors bloodsugar sessionStorage pattern) */
document.getElementById('addMealForm').addEventListener('submit', () => {
    sessionStorage.setItem('meal_saved', '1');
});
document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem('meal_saved') === '1') {
        sessionStorage.removeItem('meal_saved');
        showToast('saveToast', 3500);
    }
    <?php if ($flashDeleted): ?>
    document.getElementById('toastMsg').textContent = 'Meal deleted successfully';
    document.getElementById('toastUndo').style.display = 'none';
    showToast('deleteToast', 3500);
    <?php endif; ?>
});

/* ── Modal helpers ─────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }
function overlayCloseModal(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

function openAddMealModal()   { openModal('addMealModal'); }
function openSavePresetModal(){ openModal('savePresetModal'); }

/* ── Meal type selector ─────────────────────────────────── */
function selectMealType(type, btn, inputId) {
    btn.closest('.meal-type-grid').querySelectorAll('.meal-type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById(inputId).value = type;
}

/* ── Quick Add tabs ─────────────────────────────────────── */
function switchQaTab(tab, btn) {
    document.querySelectorAll('.qa-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('qa-suggested').style.display = tab === 'suggested' ? '' : 'none';
    document.getElementById('qa-saved').style.display     = tab === 'saved'     ? '' : 'none';
}

/* ── Quick Add — pre-fill modal then open ─────────────── */
function quickAdd(preset) {
    document.getElementById('add-meal-name').value     = preset.meal_name || '';
    document.getElementById('add-meal-carbs').value    = preset.carbs     || '';
    document.getElementById('add-meal-calories').value = preset.calories  || '';
    document.getElementById('add-meal-sugar').value    = preset.sugar     || '';
    document.getElementById('add-meal-protein').value  = preset.protein   || '';
    document.getElementById('add-meal-fat').value      = preset.fat       || '';
    document.getElementById('add-meal-fiber').value    = preset.fiber     || '';
    document.getElementById('add-meal-sodium').value   = preset.sodium    || '';
    const t = (preset.meal_type || 'Breakfast').toLowerCase();
    document.querySelector('#addMealModal .meal-type-grid').querySelectorAll('.meal-type-btn').forEach(b => {
        b.classList.toggle('selected', b.textContent.trim().toLowerCase().includes(t));
    });
    document.getElementById('add-meal-type').value = preset.meal_type || 'Breakfast';
    openAddMealModal();
}

/* ── Quick Add Panel (mobile) ───────────────────────────── */
function closeQaPanel() {
    if (window.innerWidth <= 1024) {
        document.getElementById('qaPanel').classList.remove('open');
        document.body.style.overflow = '';
    }
}

/* ── History drawer ─────────────────────────────────────── */
let drawerFilterActive = 'all';

function openHistoryDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeHistoryDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function filterDrawerByDate(date) {
    openHistoryDrawer();
    setTimeout(() => {
        const group = document.querySelector(`.meal-drawer-day-group[data-date="${date}"]`);
        if (group) group.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 320);
}
function filterDrawer() {
    applyDrawerFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), drawerFilterActive);
}
function setDrawerFilter(filter, btn) {
    drawerFilterActive = filter;
    document.querySelectorAll('.meal-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyDrawerFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), filter);
}
function applyDrawerFilters(q, filter) {
    let visible = 0;
    document.querySelectorAll('.meal-timeline-item').forEach(item => {
        const ok = (filter === 'all' || item.dataset.type === filter)
                && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.meal-drawer-day-group').forEach(group => {
        group.style.display = [...group.querySelectorAll('.meal-timeline-item')].some(i => i.style.display !== 'none') ? '' : 'none';
    });
    const noRes = document.getElementById('drawerNoResults');
    if (noRes) noRes.style.display = visible === 0 ? 'flex' : 'none';
}

/* ── Delete with undo toast ─────────────────────────────── */
let deleteTimer   = null;
let pendingDelete = null;

function confirmDeleteMeal(btn) {
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const row  = document.getElementById('meal-row-' + id);
    if (row) row.classList.add('removing');
    showDeleteToast(id, name, [row]);
}
function confirmDeleteLog(btn) {
    const id     = btn.dataset.id;
    const name   = btn.dataset.name;
    const tlItem = btn.closest('.meal-timeline-item');
    const todRow = document.getElementById('meal-row-' + id);
    if (tlItem) tlItem.classList.add('meal-item-deleting');
    if (todRow) todRow.classList.add('removing');
    showDeleteToast(id, name, [tlItem, todRow]);
}
function showDeleteToast(id, name, rows) {
    document.getElementById('toastUndo').style.display = '';
    document.getElementById('toastMsg').textContent = `"${name}" removed`;
    showToast('deleteToast', 0);
    pendingDelete = { id, rows };
    clearTimeout(deleteTimer);
    deleteTimer = setTimeout(() => {
        window.location.href = '/diabetrack/public/patient/meals?delete=' + id + '&deleted=1';
    }, 5000);
}

document.getElementById('toastUndo').addEventListener('click', () => {
    clearTimeout(deleteTimer);
    if (pendingDelete) {
        pendingDelete.rows.forEach(r => { if (r) { r.classList.remove('removing'); r.classList.remove('meal-item-deleting'); } });
        pendingDelete = null;
    }
    hideToast('deleteToast');
});
document.getElementById('toastClose').addEventListener('click', () => {
    if (pendingDelete) {
        window.location.href = '/diabetrack/public/patient/meals?delete=' + pendingDelete.id + '&deleted=1';
        pendingDelete = null;
    }
    hideToast('deleteToast');
    clearTimeout(deleteTimer);
});

/* ── Delete preset ─────────────────────────────────────── */
function deletePreset(id, link) {
    const item = link.closest('.qa-list-item');
    fetch('/diabetrack/public/patient/meals?delete_preset=' + id)
        .then(r => {
            if (r.ok) {
                if (item) { item.classList.add('removing'); setTimeout(() => item.remove(), 300); }
            }
        })
        .catch(() => {});
}

/* ── Global keyboard handler ───────────────────────────── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal('addMealModal');
        closeModal('savePresetModal');
        closeModal('nutritionModal');
        closeHistoryDrawer();
        closeQaPanel();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>
