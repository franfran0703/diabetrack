<?php
$pageTitle  = 'Blood Sugar Logger';
$activeMenu = 'bloodsugar';

ob_start();

$chartLabels = [];
$chartData   = [];
$chartColors = [];
foreach ($last7 as $log) {
    $chartLabels[] = date('M d', strtotime($log['logged_at']));
    $chartData[]   = $log['reading'];
    // Chart point colors match the 5-zone indicator card
    $r = $log['reading'];
    $chartColors[] = $r < 70    ? '#e74c3c' :
                    ($r <= 126  ? '#3498db' :
                    ($r <= 180  ? '#27ae60' :
                    ($r <= 248  ? '#1a5276' : '#f39c12')));
}

// Count abnormal (anything not Normal/Borderline)
$abnormalCount = count(array_filter($logs, fn($l) => $l['status'] !== 'Normal'));
?>

<link href="/diabetrack/public/assets/css/bloodsugar.css?<?= time() ?>" rel="stylesheet">

<!-- Header -->
<div class="bs-header">
    <div>
        <h4>🩸 Blood Sugar Logger</h4>
        <p>Track and monitor your blood glucose levels.</p>
    </div>
</div>

<!-- Stat Cards -->
<div class="bs-stats-row">
    <div class="bs-scard" data-illus="🩸">
        <div class="bs-scard-icon">🩸</div>
        <div class="bs-scard-val">
            <?= $latest ? $latest['reading'] . ' <small>mg/dL</small>' : '—' ?>
        </div>
        <div class="bs-scard-label">Latest Reading</div>
        <?php if ($latest): ?>
            <div class="bs-badge <?= $latest['status'] === 'High' ? 'danger' : ($latest['status'] === 'Low' ? 'warn' : 'good') ?>">
                <?= $latest['status'] === 'Normal' ? '✓' : '!' ?> <?= $latest['status'] ?>
            </div>
        <?php else: ?>
            <div class="bs-badge warn">No logs yet</div>
        <?php endif; ?>
    </div>

    <div class="bs-scard" data-illus="📋">
        <div class="bs-scard-icon">📋</div>
        <div class="bs-scard-val"><?= count($logs) ?></div>
        <div class="bs-scard-label">Total Logs</div>
        <div class="bs-badge good">All time</div>
    </div>

    <div class="bs-scard" data-illus="⚠️">
        <div class="bs-scard-icon">⚠️</div>
        <div class="bs-scard-val"><?= $abnormalCount ?></div>
        <div class="bs-scard-label">Abnormal Readings</div>
        <div class="bs-badge warn">High or Low</div>
    </div>
</div>

<!-- Chart + Form -->
<div class="bs-grid">

    <!-- Chart -->
    <div class="bs-chart-card">
        <div class="bs-section-label">Last 7 Readings — Trend</div>
        <?php if (!empty($last7)): ?>
            <canvas id="sugarChart" height="160"></canvas>
        <?php else: ?>
            <div class="bs-empty">
                <div class="bs-empty-icon">📈</div>
                <p>No readings yet — your chart will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Log Form -->
    <div class="bs-form-card" id="bs-form-section">
        <div class="bs-section-label">Log New Reading</div>

        <form method="POST" action="/diabetrack/public/patient/bloodsugar" id="bs-form">

            <!-- Dial display -->
            <div class="bs-dial-wrap" id="dialWrap">
                <div class="bs-dial-number" id="dialNum">120</div>
                <div class="bs-dial-unit">mg/dL</div>
                <div class="bs-dial-status" id="dialStatus">Normal</div>
            </div>

            <!-- Slider -->
            <input type="range" class="bs-glucose-range" min="40" max="400" value="120" id="glucoseSlider">
            <div class="bs-range-labels">
                <span class="bs-range-label-low">● Low &lt;70</span>
                <span class="bs-range-label-normal">● Normal 72–126</span>
                <span class="bs-range-label-borderline">● Borderline 127–180</span>
                <span class="bs-range-label-high">● High 181–248</span>
                <span class="bs-range-label-dangerous">● Dangerous &gt;248</span>
            </div>

            <!-- Hidden actual input -->
            <input type="hidden" name="reading" id="readingInput" value="120">

            <!-- Type selector -->
            <div class="bs-type-grid">
                <button type="button" class="bs-type-btn selected" data-value="Before Meal">Before Meal</button>
                <button type="button" class="bs-type-btn" data-value="After Meal">After Meal</button>
                <button type="button" class="bs-type-btn" data-value="Fasting">Fasting</button>
                <button type="button" class="bs-type-btn" data-value="Bedtime">Bedtime</button>
            </div>
            <input type="hidden" name="reading_type" id="readingTypeInput" value="Before Meal">

            <!-- Notes -->
            <textarea name="notes" class="bs-notes" rows="2"
                      placeholder="Notes (optional) — e.g. felt dizzy..."></textarea>

            <button type="submit" class="bs-save-btn">💾 Save Reading</button>

        </form>
    </div>

</div>

<!-- Table -->
<div class="bs-table-card">
    <div class="bs-section-label">All Readings</div>

    <?php if (empty($logs)): ?>
        <div class="bs-empty">
            <div class="bs-empty-icon">🩸</div>
            <p>No readings yet. Add your first one!</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="bs-table">
                <thead>
                    <tr>
                        <th>Reading</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Date & Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <span class="bs-reading-val"><?= $log['reading'] ?></span>
                            <span class="bs-reading-unit">mg/dL</span>
                        </td>
                        <td><?= htmlspecialchars($log['reading_type']) ?></td>
                        <td>
                            <span class="bs-badge <?= $log['status'] === 'High' ? 'danger' : ($log['status'] === 'Low' ? 'warn' : 'good') ?>">
                                <?= $log['status'] ?>
                            </span>
                        </td>
                        <td><?= $log['notes'] ? htmlspecialchars($log['notes']) : '—' ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?></td>
                        <td>
                            <a href="/diabetrack/public/patient/bloodsugar?delete=<?= $log['id'] ?>"
                               onclick="return confirm('Delete this reading?')"
                               class="bs-del-btn">🗑</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Chart JS -->
<?php if (!empty($last7)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('sugarChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Blood Sugar (mg/dL)',
            data: <?= json_encode($chartData) ?>,
            borderColor: '#F97447',
            backgroundColor: 'rgba(249,116,71,0.07)',
            borderWidth: 2.5,
            pointBackgroundColor: <?= json_encode($chartColors) ?>,
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: false,
                grid: { color: 'rgba(0,0,0,0.06)' },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        }
    }
});
</script>
<?php endif; ?>

<!-- Shared zone helper + slider interactions -->
<script>
// ── Shared zone logic (used by both inline + modal sliders) ──
function getZone(v) {
    if (v < 70)   return { zone: 'low',        label: '🔴 Low',        thumb: '#e74c3c' };
    if (v <= 126) return { zone: 'normal',      label: '🔵 Normal',     thumb: '#2980b9' };
    if (v <= 180) return { zone: 'borderline',  label: '🟢 Borderline', thumb: '#27ae60' };
    if (v <= 248) return { zone: 'high',        label: '🔷 High',       thumb: '#1a5276' };
                  return { zone: 'dangerous',   label: '🟡 Dangerous',  thumb: '#e67e22' };
}

// ── Inline form slider ──
const slider       = document.getElementById('glucoseSlider');
const dialNum      = document.getElementById('dialNum');
const dialStatus   = document.getElementById('dialStatus');
const dialWrap     = document.getElementById('dialWrap');
const readingInput = document.getElementById('readingInput');

function updateInlineSlider() {
    const v = parseInt(slider.value);
    const { zone, label } = getZone(v);

    dialNum.textContent    = v;
    readingInput.value     = v;
    dialStatus.textContent = label;
    dialWrap.className     = 'bs-dial-wrap zone-' + zone;
    dialStatus.className   = 'bs-dial-status zone-' + zone;
}

slider.addEventListener('input', updateInlineSlider);
updateInlineSlider(); // set correct state on page load

// ── Inline type buttons ──
document.querySelectorAll('.bs-type-btn[data-value]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.bs-type-btn[data-value]').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('readingTypeInput').value = btn.dataset.value;
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>

<!-- Add Reading Modal -->
<div class="modal fade" id="addReadingModal" tabindex="-1" aria-labelledby="addReadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bs-modal-content">

            <div class="modal-header bs-modal-header">
                <h5 class="modal-title" id="addReadingModalLabel">🩸 Log New Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body bs-modal-body">
                <form method="POST" action="/diabetrack/public/patient/bloodsugar" id="bs-modal-form">

                    <!-- Dial display -->
                    <div class="bs-dial-wrap" id="modalDialWrap">
                        <div class="bs-dial-number" id="modalDialNum">120</div>
                        <div class="bs-dial-unit">mg/dL</div>
                        <div class="bs-dial-status" id="modalDialStatus">🔵 Normal</div>
                    </div>

                    <!-- Slider -->
                    <input type="range" class="bs-glucose-range" min="40" max="400" value="120" id="modalGlucoseSlider">
                    <div class="bs-range-labels">
                        <span class="bs-range-label-low">● Low &lt;70</span>
                        <span class="bs-range-label-normal">● Normal 72–126</span>
                        <span class="bs-range-label-borderline">● Borderline 127–180</span>
                        <span class="bs-range-label-high">● High 181–248</span>
                        <span class="bs-range-label-dangerous">● Dangerous &gt;248</span>
                    </div>

                    <input type="hidden" name="reading" id="modalReadingInput" value="120">

                    <!-- Type selector -->
                    <div class="bs-type-grid">
                        <button type="button" class="bs-type-btn selected" data-modal-type="Before Meal">Before Meal</button>
                        <button type="button" class="bs-type-btn" data-modal-type="After Meal">After Meal</button>
                        <button type="button" class="bs-type-btn" data-modal-type="Fasting">Fasting</button>
                        <button type="button" class="bs-type-btn" data-modal-type="Bedtime">Bedtime</button>
                    </div>
                    <input type="hidden" name="reading_type" id="modalReadingTypeInput" value="Before Meal">

                    <!-- Notes -->
                    <textarea name="notes" class="bs-notes" rows="2"
                        placeholder="Notes (optional) — e.g. felt dizzy..."></textarea>

                    <button type="submit" class="bs-save-btn">💾 Save Reading</button>

                </form>
            </div>

        </div>
    </div>
</div>  

<button class="patient-fab" data-bs-toggle="modal" data-bs-target="#addReadingModal">
    <span class="patient-fab-icon">🩸</span>
    <span class="patient-fab-label">Add Reading</span>
</button>

<!-- Modal slider + type interaction -->
<script>
const modalSlider       = document.getElementById('modalGlucoseSlider');
const modalDialNum      = document.getElementById('modalDialNum');
const modalDialStatus   = document.getElementById('modalDialStatus');
const modalDialWrap     = document.getElementById('modalDialWrap');
const modalReadingInput = document.getElementById('modalReadingInput');

function updateModalSlider() {
    const v = parseInt(modalSlider.value);
    const { zone, label } = getZone(v); // reuses shared getZone()

    modalDialNum.textContent      = v;
    modalReadingInput.value       = v;
    modalDialStatus.textContent   = label;
    modalDialWrap.className       = 'bs-dial-wrap zone-' + zone;
    modalDialStatus.className     = 'bs-dial-status zone-' + zone;
}

modalSlider.addEventListener('input', updateModalSlider);
updateModalSlider(); // set correct state on modal open

// Reset modal slider state each time modal opens
document.getElementById('addReadingModal').addEventListener('show.bs.modal', () => {
    modalSlider.value = 120;
    updateModalSlider();
    document.querySelectorAll('[data-modal-type]').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-modal-type="Before Meal"]').classList.add('selected');
    document.getElementById('modalReadingTypeInput').value = 'Before Meal';
});

// Modal type buttons
document.querySelectorAll('[data-modal-type]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('[data-modal-type]').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('modalReadingTypeInput').value = btn.dataset.modalType;
    });
});

</script>