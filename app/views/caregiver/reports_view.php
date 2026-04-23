<?php
$pageTitle  = 'Health Reports';
$activeMenu = 'reports';
ob_start();

$pdfUrl = '/diabetrack/public/caregiver/reports?pdf=1&range=' . $range
        . '&date_from=' . $dateFrom . '&date_to=' . $dateTo;
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>" rel="stylesheet">
<link href="/diabetrack/public/assets/css/caregiver_reports.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgr-header">
    <div>
        <div class="cgr-eyebrow">Health Reports</div>
        <h1 class="cgr-title">📄 Patient <span>Reports</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cgr-patient-chip">
        <div class="cgr-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cgr-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cgr-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to generate reports.
    </div>
</div>

<?php else: ?>

<!-- RANGE SELECTOR + DOWNLOAD -->
<div class="cgr-range-bar">
    <a href="/diabetrack/public/caregiver/reports?range=7"
       class="cgr-range-btn <?= $range === '7' && !isset($_GET['date_from']) ? 'active' : '' ?>">
       Last 7 Days
    </a>
    <a href="/diabetrack/public/caregiver/reports?range=30"
       class="cgr-range-btn <?= $range === '30' && !isset($_GET['date_from']) ? 'active' : '' ?>">
       Last 30 Days
    </a>

    <!-- Custom range -->
    <form method="GET" action="/diabetrack/public/caregiver/reports" class="cgr-custom-form">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="date_from" class="cgr-date-input"
               value="<?= $dateFrom ?>" max="<?= date('Y-m-d') ?>">
        <span class="cgr-custom-sep">→</span>
        <input type="date" name="date_to" class="cgr-date-input"
               value="<?= $dateTo ?>" max="<?= date('Y-m-d') ?>">
        <button type="submit" class="cgr-custom-submit">Apply</button>
    </form>

    <!-- Download PDF -->
    <a href="<?= $pdfUrl ?>" class="cgr-download-btn" target="_blank">
        ⬇️ Download PDF
    </a>
</div>

<!-- STATS ROW -->
<div class="cgr-stats-row">
    <div class="cgr-stat peach" data-illus="🩸">
        <div class="cgr-stat-icon">🩸</div>
        <div class="cgr-stat-val"><?= $stats['avg_sugar'] ?: '—' ?><span style="font-size:0.9rem;"> mg/dL</span></div>
        <div class="cgr-stat-label">Avg Blood Sugar</div>
        <div class="cgr-stat-sub"><?= $stats['total_readings'] ?> readings · <?= $stats['high_readings'] ?> high · <?= $stats['low_readings'] ?> low</div>
    </div>
    <div class="cgr-stat glass-warm" data-illus="💊">
        <div class="cgr-stat-icon">💊</div>
        <div class="cgr-stat-val">
            <?= $stats['total_doses'] > 0 ? round($stats['taken_doses'] / $stats['total_doses'] * 100) . '%' : '—' ?>
        </div>
        <div class="cgr-stat-label">Medication Rate</div>
        <div class="cgr-stat-sub"><?= $stats['taken_doses'] ?> taken · <?= $stats['missed_doses'] ?> missed</div>
    </div>
    <div class="cgr-stat glass" data-illus="🥗">
        <div class="cgr-stat-icon">🥗</div>
        <div class="cgr-stat-val"><?= $stats['avg_carbs'] ?: '—' ?><span style="font-size:0.9rem;"> g</span></div>
        <div class="cgr-stat-label">Avg Daily Carbs</div>
        <div class="cgr-stat-sub"><?= $stats['total_meals'] ?> meals logged</div>
    </div>
    <div class="cgr-stat glass-green" data-illus="🏃">
        <div class="cgr-stat-icon">🏃</div>
        <div class="cgr-stat-val"><?= $stats['total_minutes'] ?: '—' ?><span style="font-size:0.9rem;"> min</span></div>
        <div class="cgr-stat-label">Total Activity</div>
        <div class="cgr-stat-sub"><?= $stats['total_activities'] ?> sessions</div>
    </div>
</div>

<!-- BLOOD SUGAR SECTION -->
<div class="cgr-section">
    <div class="cgr-section-label">🩸 Blood Sugar Logs</div>
    <?php if (empty($bloodSugar)): ?>
    <div class="cgr-empty">No blood sugar logs in this period.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgr-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Reading</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloodSugar as $r): ?>
                <tr>
                    <td class="cgr-table-muted"><?= date('M d, Y h:i A', strtotime($r['logged_at'])) ?></td>
                    <td class="cgr-table-val"><?= $r['reading'] ?> mg/dL</td>
                    <td style="color:rgba(255,200,160,0.6);"><?= $r['reading_type'] ?></td>
                    <td>
                        <span class="cgr-pill <?= strtolower($r['status']) ?>">
                            <?= $r['status'] === 'High' ? '🔴' : ($r['status'] === 'Low' ? '🟡' : '🟢') ?>
                            <?= $r['status'] ?>
                        </span>
                    </td>
                    <td style="color:rgba(255,200,160,0.45);"><?= $r['notes'] ?? '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- MEDICATION SECTION -->
<div class="cgr-section">
    <div class="cgr-section-label">💊 Medication Logs</div>
    <?php if (empty($medications)): ?>
    <div class="cgr-empty">No medication logs in this period.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgr-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medications as $m): ?>
                <tr>
                    <td class="cgr-table-muted"><?= date('M d, Y h:i A', strtotime($m['logged_at'])) ?></td>
                    <td class="cgr-table-name"><?= htmlspecialchars($m['name']) ?></td>
                    <td style="color:rgba(255,200,160,0.6);"><?= htmlspecialchars($m['dosage']) ?></td>
                    <td style="color:rgba(255,200,160,0.6);"><?= $m['frequency'] ?></td>
                    <td>
                        <span class="cgr-pill <?= strtolower($m['status']) ?>">
                            <?= $m['status'] === 'Taken' ? '✅' : '❌' ?> <?= $m['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- MEALS SECTION -->
<div class="cgr-section">
    <div class="cgr-section-label">🥗 Meal Logs</div>
    <?php if (empty($meals)): ?>
    <div class="cgr-empty">No meal logs in this period.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgr-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Meal</th>
                    <th>Type</th>
                    <th>Carbs</th>
                    <th>Calories</th>
                    <th>Sugar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meals as $m): ?>
                <tr>
                    <td class="cgr-table-muted"><?= date('M d, Y h:i A', strtotime($m['logged_at'])) ?></td>
                    <td class="cgr-table-name"><?= htmlspecialchars($m['meal_name']) ?></td>
                    <td style="color:rgba(255,200,160,0.6);"><?= $m['meal_type'] ?></td>
                    <td class="cgr-table-val"><?= $m['carbs'] ?>g</td>
                    <td style="color:rgba(255,200,160,0.6);"><?= $m['calories'] ? $m['calories'] . ' kcal' : '—' ?></td>
                    <td style="color:rgba(255,200,160,0.6);"><?= $m['sugar'] ? $m['sugar'] . 'g' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ACTIVITY SECTION -->
<div class="cgr-section">
    <div class="cgr-section-label">🏃 Activity Logs</div>
    <?php if (empty($activities)): ?>
    <div class="cgr-empty">No activity logs in this period.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgr-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Activity</th>
                    <th>Duration</th>
                    <th>Intensity</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $a): ?>
                <tr>
                    <td class="cgr-table-muted"><?= date('M d, Y h:i A', strtotime($a['logged_at'])) ?></td>
                    <td class="cgr-table-name"><?= htmlspecialchars($a['activity_name']) ?></td>
                    <td class="cgr-table-val"><?= $a['duration_minutes'] ?> min</td>
                    <td>
                        <span class="cgr-pill <?= strtolower($a['intensity']) ?>">
                            <?= $a['intensity'] ?>
                        </span>
                    </td>
                    <td style="color:rgba(255,200,160,0.45);"><?= $a['notes'] ?? '—' ?></td>
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