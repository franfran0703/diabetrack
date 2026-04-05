<?php
$pageTitle  = 'Meals & Carbs';
$activeMenu = 'meals';
ob_start();
?>

<link href="/diabetrack/public/assets/css/meals.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="meal-header">
    <h1>🥗 Meals & Carbs Tracker</h1>
    <p>Log your meals and track your daily nutritional intake.</p>
    <button class="btn-add-meal" onclick="openModal('addMealModal')">
        <span class="btn-add-icon">+</span>
        Log a Meal
    </button>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
<div class="meal-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="meal-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- STAT STRIP — horizontal nutrition panels -->
<div class="meal-stat-strip">

    <!-- Big featured — Carbs -->
    <div class="meal-strip-main">
        <div class="meal-strip-main-top">
            <div class="meal-strip-icon">🍚</div>
            <div class="meal-strip-badge">Today's Key Nutrient</div>
        </div>
        <div class="meal-strip-main-val">
            <?= round($todayTotals['total_carbs'], 1) ?>
            <span class="meal-strip-main-unit">g carbs</span>
        </div>
        <div class="meal-strip-main-label">Total Carbohydrates</div>
        <!-- Carb progress bar — recommended limit 130g/day for diabetics -->
        <?php $carbPct = min(round((float)$todayTotals['total_carbs'] / 130 * 100), 100); ?>
        <div class="meal-strip-progress-wrap">
            <div class="meal-strip-progress-track">
                <div class="meal-strip-progress-bar <?= $carbPct >= 100 ? 'over' : ($carbPct >= 75 ? 'warn' : 'good') ?>"
                     style="width:<?= $carbPct ?>%;"></div>
            </div>
            <div class="meal-strip-progress-label">
                <?= $carbPct ?>% of 130g daily limit
            </div>
        </div>
    </div>

    <!-- Right mini stack -->
    <div class="meal-strip-stack">

        <div class="meal-strip-mini meal-strip-mini-1">
            <div class="meal-strip-mini-icon">🔥</div>
            <div>
                <div class="meal-strip-mini-val"><?= round($todayTotals['total_calories']) ?><small>kcal</small></div>
                <div class="meal-strip-mini-label">Calories</div>
            </div>
        </div>

        <div class="meal-strip-mini meal-strip-mini-2">
            <div class="meal-strip-mini-icon">🍬</div>
            <div>
                <div class="meal-strip-mini-val"><?= round($todayTotals['total_sugar'], 1) ?><small>g</small></div>
                <div class="meal-strip-mini-label">Sugar</div>
            </div>
        </div>

        <div class="meal-strip-mini meal-strip-mini-3">
            <div class="meal-strip-mini-icon">🥩</div>
            <div>
                <div class="meal-strip-mini-val"><?= round($todayTotals['total_protein'], 1) ?><small>g</small></div>
                <div class="meal-strip-mini-label">Protein</div>
            </div>
        </div>

        <div class="meal-strip-mini meal-strip-mini-4">
            <div class="meal-strip-mini-icon">🍽️</div>
            <div>
                <div class="meal-strip-mini-val"><?= $todayTotals['total_meals'] ?></div>
                <div class="meal-strip-mini-label">Meals Today</div>
            </div>
        </div>

    </div>

</div>

<!-- TODAY'S NUTRITION SUMMARY -->
<?php if ($todayTotals['total_meals'] > 0): ?>
<div class="meal-card">
    <div class="meal-section-label">Today's Nutrition Summary</div>
    <div class="meal-nutrition-bar">
        <div class="meal-nutrition-item">
            <div class="meal-nutrition-val"><?= round($todayTotals['total_carbs'], 1) ?><span class="meal-nutrition-unit">g</span></div>
            <div class="meal-nutrition-name">Carbs</div>
        </div>
        <div class="meal-nutrition-item">
            <div class="meal-nutrition-val"><?= round($todayTotals['total_calories']) ?><span class="meal-nutrition-unit">kcal</span></div>
            <div class="meal-nutrition-name">Calories</div>
        </div>
        <div class="meal-nutrition-item">
            <div class="meal-nutrition-val"><?= round($todayTotals['total_sugar'], 1) ?><span class="meal-nutrition-unit">g</span></div>
            <div class="meal-nutrition-name">Sugar</div>
        </div>
        <div class="meal-nutrition-item">
            <div class="meal-nutrition-val"><?= round($todayTotals['total_protein'], 1) ?><span class="meal-nutrition-unit">g</span></div>
            <div class="meal-nutrition-name">Protein</div>
        </div>
        <div class="meal-nutrition-item">
            <div class="meal-nutrition-val"><?= round($todayTotals['total_fat'], 1) ?><span class="meal-nutrition-unit">g</span></div>
            <div class="meal-nutrition-name">Fat</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- TODAY'S MEALS -->
<div class="meal-card">
    <div class="meal-section-label">Today's Meals — <?= date('M d, Y') ?></div>

    <?php if (empty($todayLogs)): ?>
    <div class="meal-empty">
        <div class="meal-empty-icon">🥗</div>
        <p>No meals logged today yet.</p>
        <span>Click "Log a Meal" to get started.</span>
    </div>
    <?php else: ?>
    <?php foreach ($todayLogs as $meal): ?>
    <div class="meal-item">
        <div class="meal-item-icon">
            <?= $meal['meal_type'] === 'Breakfast' ? '🌅' :
               ($meal['meal_type'] === 'Lunch'     ? '☀️' :
               ($meal['meal_type'] === 'Dinner'    ? '🌙' : '🍎')) ?>
        </div>
        <div style="flex:1;">
            <div class="meal-item-name"><?= htmlspecialchars($meal['meal_name']) ?></div>
            <div class="meal-item-meta">
                <?php if ($meal['calories']): ?>🔥 <?= $meal['calories'] ?> kcal &nbsp;·&nbsp; <?php endif; ?>
                <?php if ($meal['protein']): ?>🥩 <?= $meal['protein'] ?>g protein &nbsp;·&nbsp; <?php endif; ?>
                <?= date('h:i A', strtotime($meal['logged_at'])) ?>
            </div>
        </div>
        <span class="meal-type-badge"><?= $meal['meal_type'] ?></span>
        <div class="meal-item-carbs">
            <?= $meal['carbs'] ?>
            <small>g carbs</small>
        </div>
        <a href="/diabetrack/public/patient/meals?delete=<?= $meal['id'] ?>"
           onclick="return confirm('Delete this meal log?')"
           class="meal-del-btn">🗑</a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ALL LOGS TABLE -->
<div class="meal-card">
    <div class="meal-section-label">All Meal History</div>

    <?php if (empty($logs)): ?>
    <div class="meal-empty">
        <div class="meal-empty-icon">📜</div>
        <p>No meal history yet.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="meal-table">
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
                    <th>Date & Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="meal-table-name"><?= htmlspecialchars($log['meal_name']) ?></td>
                    <td>
                        <span class="meal-type-badge"><?= $log['meal_type'] ?></span>
                    </td>
                    <td class="meal-table-val"><?= $log['carbs'] ?>g</td>
                    <td class="meal-table-muted"><?= $log['calories'] ? $log['calories'] . ' kcal' : '—' ?></td>
                    <td class="meal-table-muted"><?= $log['sugar'] ? $log['sugar'] . 'g' : '—' ?></td>
                    <td class="meal-table-muted"><?= $log['protein'] ? $log['protein'] . 'g' : '—' ?></td>
                    <td class="meal-table-muted"><?= $log['fat'] ? $log['fat'] . 'g' : '—' ?></td>
                    <td class="meal-table-muted"><?= $log['glycemic_index'] ?? '—' ?></td>
                    <td class="meal-table-muted" style="white-space:nowrap;"><?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?></td>
                    <td>
                        <a href="/diabetrack/public/patient/meals?delete=<?= $log['id'] ?>"
                           onclick="return confirm('Delete this meal?')"
                           class="meal-del-btn">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ADD MEAL MODAL -->
<div id="addMealModal" class="meal-modal-overlay">
    <div class="meal-modal">
        <button class="meal-modal-close" onclick="closeModal('addMealModal')">✕</button>
        <div class="meal-modal-title">🥗 Log a Meal</div>
        <div class="meal-modal-sub">Record your meal and nutritional details.</div>

        <form method="POST" action="/diabetrack/public/patient/meals">

            <!-- Meal Name -->
            <div class="meal-form-group">
                <label class="meal-form-label">
                    Meal Name <span class="meal-required">*</span>
                </label>
                <input type="text" name="meal_name" class="meal-form-input"
                       placeholder="e.g. Sinangag, Adobo, Rice and Chicken"
                       required>
            </div>

            <!-- Meal Type Selector -->
            <div class="meal-form-group">
                <label class="meal-form-label">Meal Type <span class="meal-required">*</span></label>
                <div class="meal-type-grid">
                    <button type="button" class="meal-type-btn selected" onclick="selectMealType('Breakfast', this)">
                        <span>🌅</span> Breakfast
                    </button>
                    <button type="button" class="meal-type-btn" onclick="selectMealType('Lunch', this)">
                        <span>☀️</span> Lunch
                    </button>
                    <button type="button" class="meal-type-btn" onclick="selectMealType('Dinner', this)">
                        <span>🌙</span> Dinner
                    </button>
                    <button type="button" class="meal-type-btn" onclick="selectMealType('Snack', this)">
                        <span>🍎</span> Snack
                    </button>
                </div>
                <input type="hidden" name="meal_type" id="meal-type-input" value="Breakfast">
            </div>

            <div class="meal-form-divider">Required Nutrition</div>

            <!-- Carbs — required -->
            <div class="meal-form-group">
                <label class="meal-form-label">
                    Carbohydrates (g) <span class="meal-required">*</span>
                </label>
                <input type="number" step="0.01" name="carbs" class="meal-form-input"
                       placeholder="e.g. 45" required>
            </div>

            <div class="meal-form-divider">Optional Nutrition</div>

            <!-- Calories + Sugar -->
            <div class="meal-form-grid-2 meal-form-group">
                <div>
                    <label class="meal-form-label">Calories (kcal) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="calories" class="meal-form-input" placeholder="e.g. 350">
                </div>
                <div>
                    <label class="meal-form-label">Sugar (g) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="sugar" class="meal-form-input" placeholder="e.g. 12">
                </div>
            </div>

            <!-- Fiber + Protein -->
            <div class="meal-form-grid-2 meal-form-group">
                <div>
                    <label class="meal-form-label">Fiber (g) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="fiber" class="meal-form-input" placeholder="e.g. 3">
                </div>
                <div>
                    <label class="meal-form-label">Protein (g) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="protein" class="meal-form-input" placeholder="e.g. 25">
                </div>
            </div>

            <!-- Fat + Sodium -->
            <div class="meal-form-grid-2 meal-form-group">
                <div>
                    <label class="meal-form-label">Fat (g) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="fat" class="meal-form-input" placeholder="e.g. 8">
                </div>
                <div>
                    <label class="meal-form-label">Sodium (mg) <span class="meal-optional">optional</span></label>
                    <input type="number" step="0.01" name="sodium" class="meal-form-input" placeholder="e.g. 420">
                </div>
            </div>

            <!-- Glycemic Index -->
            <div class="meal-form-group">
                <label class="meal-form-label">Glycemic Index <span class="meal-optional">optional</span></label>
                <input type="number" name="glycemic_index" class="meal-form-input"
                       placeholder="e.g. 55 (Low <55, Medium 56-69, High ≥70)" min="0" max="100">
            </div>

            <!-- Notes -->
            <div class="meal-form-group">
                <label class="meal-form-label">Notes <span class="meal-optional">optional</span></label>
                <textarea name="notes" class="meal-form-textarea" rows="2"
                          placeholder="e.g. Home cooked, ate at restaurant, etc."></textarea>
            </div>

            <button type="submit" class="meal-save-btn">🥗 Save Meal</button>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
document.querySelectorAll('.meal-modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

function selectMealType(type, btn) {
    document.querySelectorAll('.meal-type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('meal-type-input').value = type;
}

// Focus styles
document.querySelectorAll('.meal-form-input, .meal-form-select, .meal-form-textarea').forEach(f => {
    f.addEventListener('focus', () => {
        f.style.borderColor = '#F97447';
        f.style.boxShadow = '0 0 0 3px rgba(249,116,71,0.12)';
    });
    f.addEventListener('blur', () => {
        f.style.borderColor = 'rgba(249,116,71,0.22)';
        f.style.boxShadow = 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>