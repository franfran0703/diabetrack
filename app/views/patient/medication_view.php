<?php
$pageTitle  = 'Medication Tracker';
$activeMenu = 'medication';
ob_start();
?>

<link href="/diabetrack/public/assets/css/medication.css?v=<?= time() ?>" rel="stylesheet">

<div class="bs-header">
    <h4> Medication Tracker</h4>
    <p>Manage your medications and log daily doses.</p>
    <button class="btn-add btn-add-med" onclick="openModal('addModal')" style="border:none;">
        <span class="btn-add-icon">+</span>
        Add Medication
    </button>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
<div class="med-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="med-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="med-stats-grid">
    <div class="med-stat-card card-1" data-illus="💊">
        <div class="med-stat-icon">💊</div>
        <div class="med-stat-body">
            <div class="med-stat-val"><?= count($medications) ?></div>
            <div class="med-stat-label">Total Medications</div>
            <div class="med-stat-badge">📋 Active Schedule</div>
        </div>
    </div>
    <div class="med-stat-card card-2" data-illus="✅">
        <div class="med-stat-icon">✅</div>
        <div class="med-stat-body">
            <div class="med-stat-val"><?= $todayStats['taken'] ?? 0 ?></div>
            <div class="med-stat-label">Taken Today</div>
        </div>
    </div>
    <div class="med-stat-card card-3" data-illus="❌">
        <div class="med-stat-icon">❌</div>
        <div class="med-stat-body">
            <div class="med-stat-val"><?= $todayStats['missed'] ?? 0 ?></div>
            <div class="med-stat-label">Missed Today</div>
        </div>
    </div>
    <div class="med-stat-card card-4" data-illus="📅">
        <div class="med-stat-icon">📅</div>
        <div class="med-stat-body">
            <div class="med-stat-val"><?= $todayStats['total'] ?? 0 ?></div>
            <div class="med-stat-label">Total Today</div>
        </div>
    </div>
</div>

<!-- SCHEDULE + TODAY LOG -->
<div class="med-grid-2">

    <!-- SCHEDULE -->
    <div class="med-card">
        <div class="med-section-label">Medication Schedule</div>

        <?php if (empty($medications)): ?>
        <div class="med-empty">
            <div class="med-empty-icon">💊</div>
            <p>No medications added yet.</p>
            <span>Click "Add Medication" to get started.</span>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($medications as $med):
                $logged = $loggedToday[$med['id']] ?? false;
            ?>
            <div class="med-item">
                <div class="med-item-icon">💊</div>
                <div style="flex:1;">
                    <div class="med-item-name"><?= htmlspecialchars($med['name']) ?></div>
                    <div class="med-item-meta">
                        <?= htmlspecialchars($med['dosage']) ?> &nbsp;·&nbsp;
                        <?= date('h:i A', strtotime($med['schedule_time'])) ?> &nbsp;·&nbsp;
                        <?= $med['frequency'] ?>
                    </div>
                </div>
                <div class="med-item-actions">
                    <?php if (!$logged): ?>
                    <form method="POST" action="/diabetrack/public/patient/medication" style="margin:0;">
                        <input type="hidden" name="action"  value="log">
                        <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                        <input type="hidden" name="status"  value="Taken">
                        <button type="submit" class="btn-taken">✅ Taken</button>
                    </form>
                    <form method="POST" action="/diabetrack/public/patient/medication" style="margin:0;">
                        <input type="hidden" name="action"  value="log">
                        <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                        <input type="hidden" name="status"  value="Missed">
                        <button type="submit" class="btn-missed">❌ Missed</button>
                    </form>
                    <?php else: ?>
                    <span class="btn-logged">✓ Logged Today</span>
                    <?php endif; ?>
                    <button onclick='openEdit(<?= json_encode($med) ?>)' class="btn-icon btn-icon-edit">✏️</button>
                    <a href="/diabetrack/public/patient/medication?delete=<?= $med['id'] ?>"
                       onclick="return confirm('Delete <?= htmlspecialchars($med['name']) ?>?')"
                       class="btn-icon btn-icon-delete">🗑</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- TODAY'S LOG -->
    <div class="med-card">
        <div class="med-section-label">Today's Log — <?= date('M d, Y') ?></div>

        <?php if (empty($todayLogs)): ?>
        <div class="med-empty">
            <div class="med-empty-icon">📋</div>
            <p>No doses logged today yet.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($todayLogs as $log): ?>
            <div class="log-item <?= strtolower($log['status']) ?>">
                <div style="font-size:1.2rem;"><?= $log['status']==='Taken' ? '✅' : '❌' ?></div>
                <div style="flex:1;">
                    <div class="log-item-name"><?= htmlspecialchars($log['name']) ?></div>
                    <div class="log-item-meta"><?= htmlspecialchars($log['dosage']) ?> · <?= date('h:i A', strtotime($log['logged_at'])) ?></div>
                </div>
                <span class="log-badge <?= strtolower($log['status']) ?>"><?= $log['status'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- HISTORY TABLE -->
<div class="med-card">
    <div class="med-section-label">Recent Dose History</div>

    <?php if (empty($allLogs)): ?>
    <div class="med-empty">
        <div class="med-empty-icon">📜</div>
        <p>No dose history yet.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="med-table">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allLogs as $log): ?>
                <tr>
                    <td class="med-table-name"><?= htmlspecialchars($log['name']) ?></td>
                    <td class="med-table-meta"><?= htmlspecialchars($log['dosage']) ?></td>
                    <td class="med-table-meta"><?= date('h:i A', strtotime($log['schedule_time'])) ?></td>
                    <td>
                        <span class="log-badge <?= strtolower($log['status']) ?>">
                            <?= $log['status']==='Taken' ? '✅' : '❌' ?> <?= $log['status'] ?>
                        </span>
                    </td>
                    <td class="med-table-meta" style="white-space:nowrap;">
                        <?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ADD MODAL -->
<div id="addModal" class="med-modal-overlay">
    <div class="med-modal">
        <button class="med-modal-close" onclick="closeModal('addModal')">✕</button>
        <div class="med-modal-title">💊 Add Medication</div>
        <div class="med-modal-sub">Set up a new medication in your schedule.</div>
        <form method="POST" action="/diabetrack/public/patient/medication">
            <input type="hidden" name="action" value="add">
            <?= medFormFields() ?>
            <button type="submit" class="med-save-btn">Save Medication</button>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="med-modal-overlay">
    <div class="med-modal">
        <button class="med-modal-close" onclick="closeModal('editModal')">✕</button>
        <div class="med-modal-title">✏️ Edit Medication</div>
        <div class="med-modal-sub">Update your medication details.</div>
        <form method="POST" action="/diabetrack/public/patient/medication">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="med_id" id="edit-med-id">
            <?= medFormFields('edit-') ?>
            <button type="submit" class="med-save-btn">Update Medication</button>
        </form>
    </div>
</div>

<?php
function medFormFields($prefix = '') { ob_start(); ?>
    <div class="med-form-group">
        <label class="med-form-label">Medication Name</label>
        <input type="text" name="name" id="<?= $prefix ?>med-name"
               class="med-form-input" placeholder="e.g. Metformin" required>
    </div>
    <div class="med-form-group">
        <label class="med-form-label">Dosage</label>
        <input type="text" name="dosage" id="<?= $prefix ?>med-dosage"
               class="med-form-input" placeholder="e.g. 500mg" required>
    </div>
    <div class="med-form-grid med-form-group">
        <div>
            <label class="med-form-label">Schedule Time</label>
            <input type="time" name="schedule_time" id="<?= $prefix ?>med-time"
                   class="med-form-input" required>
        </div>
        <div>
            <label class="med-form-label">Frequency</label>
            <select name="frequency" id="<?= $prefix ?>med-freq" class="med-form-select" required>
                <option value="Daily">Daily</option>
                <option value="Twice a day">Twice a day</option>
                <option value="Three times a day">Three times a day</option>
                <option value="Weekly">Weekly</option>
            </select>
        </div>
    </div>
<?php return ob_get_clean(); }
?>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
function openEdit(med) {
    document.getElementById('edit-med-id').value     = med.id;
    document.getElementById('edit-med-name').value   = med.name;
    document.getElementById('edit-med-dosage').value = med.dosage;
    document.getElementById('edit-med-time').value   = med.schedule_time;
    document.getElementById('edit-med-freq').value   = med.frequency;
    openModal('editModal');
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>