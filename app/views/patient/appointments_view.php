<?php
$pageTitle  = 'Appointments';
$activeMenu = 'appointments';
ob_start();
?>

<link href="/diabetrack/public/assets/css/appointments.css?v=<?= time() ?>" rel="stylesheet">

<!-- ALERTS -->
<?php if ($success): ?>
<div class="appt-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="appt-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- TOP — Next appointment hero + count stack -->
<div class="appt-top">

    <!-- Next appointment hero -->
    <?php if ($next): ?>
    <div class="appt-next-hero">
        <div class="appt-next-date-box">
            <div class="appt-next-month"><?= date('M', strtotime($next['appointment_date'])) ?></div>
            <div class="appt-next-day"><?= date('d', strtotime($next['appointment_date'])) ?></div>
            <div class="appt-next-weekday"><?= date('D', strtotime($next['appointment_date'])) ?></div>
        </div>
        <div class="appt-next-info">
            <div class="appt-next-label">Next Appointment</div>
            <div class="appt-next-doctor">Dr. <?= htmlspecialchars($next['doctor_name']) ?></div>
            <div class="appt-next-time">
                🕐 <?= date('h:i A', strtotime($next['appointment_date'])) ?>
                &nbsp;·&nbsp;
                <?= date('l', strtotime($next['appointment_date'])) ?>
            </div>
            <?php if ($next['notes']): ?>
            <div class="appt-next-badge">
                📝 <?= htmlspecialchars($next['notes']) ?>
            </div>
            <?php else: ?>
            <div class="appt-next-badge">📅 Upcoming Visit</div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="appt-no-next">
        <div class="appt-no-next-icon">📅</div>
        <div class="appt-no-next-title">No upcoming appointments</div>
        <div class="appt-no-next-sub">Schedule your next doctor visit using the button above.</div>
    </div>
    <?php endif; ?>

    <!-- Count stack -->
    <div class="appt-count-stack">
        <div class="appt-count-card upcoming">
            <div class="appt-count-icon">📅</div>
            <div>
                <div class="appt-count-num"><?= $counts['upcoming'] ?></div>
                <div class="appt-count-label">Upcoming</div>
            </div>
        </div>
        <div class="appt-count-card completed">
            <div class="appt-count-icon">✅</div>
            <div>
                <div class="appt-count-num"><?= $counts['completed'] ?></div>
                <div class="appt-count-label">Completed</div>
            </div>
        </div>
        <div class="appt-count-card cancelled">
            <div class="appt-count-icon">❌</div>
            <div>
                <div class="appt-count-num"><?= $counts['cancelled'] ?></div>
                <div class="appt-count-label">Cancelled</div>
            </div>
        </div>
    </div>

</div>

<!-- MAIN GRID — upcoming list + tips -->
<div class="appt-main-grid">

    <!-- UPCOMING LIST -->
    <div class="appt-card">
        <div class="appt-section-label">Upcoming Appointments</div>

        <?php if (empty($upcoming)): ?>
        <div class="appt-empty">
            <div class="appt-empty-icon">📅</div>
            <p>No upcoming appointments scheduled.</p>
        </div>
        <?php else: ?>
        <?php foreach ($upcoming as $appt): ?>
        <div class="appt-item">
            <div class="appt-item-date">
                <div class="appt-item-month"><?= date('M', strtotime($appt['appointment_date'])) ?></div>
                <div class="appt-item-day"><?= date('d', strtotime($appt['appointment_date'])) ?></div>
            </div>
            <div class="appt-item-info">
                <div class="appt-item-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></div>
                <div class="appt-item-meta">
                    🕐 <?= date('h:i A', strtotime($appt['appointment_date'])) ?>
                    &nbsp;·&nbsp;
                    <?= date('l', strtotime($appt['appointment_date'])) ?>
                    <?php if ($appt['notes']): ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($appt['notes']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <span class="appt-status upcoming">Upcoming</span>
            <div class="appt-actions">
                <form method="POST" action="/diabetrack/public/patient/appointments" style="margin:0;">
                    <input type="hidden" name="action"   value="status">
                    <input type="hidden" name="appt_id"  value="<?= $appt['id'] ?>">
                    <input type="hidden" name="status"   value="Completed">
                    <button type="submit" class="appt-btn appt-btn-done">✅ Done</button>
                </form>
                <form method="POST" action="/diabetrack/public/patient/appointments" style="margin:0;">
                    <input type="hidden" name="action"   value="status">
                    <input type="hidden" name="appt_id"  value="<?= $appt['id'] ?>">
                    <input type="hidden" name="status"   value="Cancelled">
                    <button type="submit" class="appt-btn appt-btn-cancel">Cancel</button>
                </form>
                <a href="/diabetrack/public/patient/appointments?delete=<?= $appt['id'] ?>"
                   onclick="return confirm('Delete this appointment?')"
                   class="appt-del-btn">🗑</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- TIPS CARD -->
    <div class="appt-tips-card">
        <div class="appt-section-label">Before Your Visit</div>
        <?php
        $tips = [
            ['icon' => '📋', 'text' => 'Bring your DiabeTrack health report to share with your doctor.'],
            ['icon' => '💊', 'text' => 'List all medications and dosages you are currently taking.'],
            ['icon' => '🩸', 'text' => 'Note your recent blood sugar readings and any unusual patterns.'],
            ['icon' => '🥗', 'text' => 'Mention any diet changes or foods that affected your glucose levels.'],
            ['icon' => '🏃', 'text' => 'Share your weekly activity summary with your physician.'],
            ['icon' => '❓', 'text' => 'Prepare questions to ask your doctor during the visit.'],
        ];
        foreach ($tips as $tip): ?>
        <div class="appt-tip-item">
            <div class="appt-tip-icon"><?= $tip['icon'] ?></div>
            <div class="appt-tip-text"><?= $tip['text'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ALL APPOINTMENTS TABLE -->
<div class="appt-table-card">
    <div class="appt-section-label">All Appointments</div>

    <?php if (empty($all)): ?>
    <div class="appt-empty">
        <div class="appt-empty-icon">📜</div>
        <p>No appointments yet.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="appt-table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $appt): ?>
                <tr>
                    <td class="appt-table-doctor">Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td class="appt-table-muted"><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></td>
                    <td class="appt-table-muted"><?= date('h:i A', strtotime($appt['appointment_date'])) ?></td>
                    <td>
                        <span class="appt-status <?= strtolower($appt['status']) ?>">
                            <?= $appt['status'] === 'Upcoming'  ? '📅' :
                               ($appt['status'] === 'Completed' ? '✅' : '❌') ?>
                            <?= $appt['status'] ?>
                        </span>
                    </td>
                    <td class="appt-table-muted"><?= $appt['notes'] ? htmlspecialchars($appt['notes']) : '—' ?></td>
                    <td>
                        <a href="/diabetrack/public/patient/appointments?delete=<?= $appt['id'] ?>"
                           onclick="return confirm('Delete this appointment?')"
                           class="appt-del-btn">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ADD APPOINTMENT MODAL -->
<div id="apptModal" class="appt-modal-overlay">
    <div class="appt-modal">
        <button class="appt-modal-close" onclick="closeModal('apptModal')">✕</button>
        <div class="appt-modal-title">📅 Add Appointment</div>
        <div class="appt-modal-sub">Schedule your next doctor visit.</div>

        <form method="POST" action="/diabetrack/public/patient/appointments">
            <input type="hidden" name="action" value="add">

            <div class="appt-form-group">
                <label class="appt-form-label">Doctor's Name</label>
                <input type="text" name="doctor_name" class="appt-form-input"
                       placeholder="e.g. Dr. Santos" required>
            </div>

            <div class="appt-form-group">
                <label class="appt-form-label">Date & Time</label>
                <input type="datetime-local" name="appointment_date"
                       class="appt-form-input" required
                       min="<?= date('Y-m-d\TH:i') ?>">
            </div>

            <div class="appt-form-group">
                <label class="appt-form-label">Notes
                    <span style="font-size:0.65rem;color:rgba(184,146,126,0.6);font-weight:600;text-transform:lowercase;"> optional</span>
                </label>
                <textarea name="notes" class="appt-form-textarea" rows="2"
                          placeholder="e.g. Bring blood sugar log, fasting required"></textarea>
            </div>

            <button type="submit" class="appt-save-btn">📅 Save Appointment</button>
        </form>
    </div>
</div>

<div class="appt-fab" onclick="openModal('apptModal')" title="Add Appointment">
    <div class="appt-fab-icon">📅</div>
    <span class="appt-fab-label">Add Appointment</span>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.appt-modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

document.querySelectorAll('.appt-form-input, .appt-form-textarea').forEach(f => {
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