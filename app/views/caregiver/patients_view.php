<?php
$pageTitle  = 'My Patients';
$activeMenu = 'patients';
ob_start();
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>" rel="stylesheet">
<link href="/diabetrack/public/assets/css/caregiver_patients.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgp-header">
    <div>
        <div class="cgp-eyebrow">Manage</div>
        <h1 class="cgp-title">My <span>Patients</span></h1>
        <p class="cgp-sub">Link and manage the patients under your care.</p>
    </div>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
<div class="cgp-alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="cgp-alert error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- MAIN LAYOUT -->
<div class="cgp-layout">

    <!-- LEFT: PATIENT DIRECTORY -->
    <div class="cgp-directory">

        <?php if (empty($linkedPatients)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon">🔗</div>
            <div class="cgp-empty-title">No patients linked yet</div>
            <div class="cgp-empty-sub">
                Use the form on the right to link a patient using their registered email address.
            </div>
        </div>

        <?php else: ?>
        <?php foreach ($linkedPatients as $p): ?>
        <div class="cgp-patient-card">

            <!-- Avatar -->
            <div class="cgp-avatar">
                <?= strtoupper(substr($p['name'], 0, 1)) ?>
            </div>

            <!-- Info -->
            <div class="cgp-patient-info">
                <div class="cgp-patient-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="cgp-patient-email">📧 <?= htmlspecialchars($p['email']) ?></div>
                <div class="cgp-patient-meta">
                    <span class="cgp-meta-pill">🔗 Linked Patient</span>
                    <span class="cgp-meta-pill">
                        📅 Since <?= date('M d, Y', strtotime($p['linked_at'])) ?>
                    </span>
                    <span class="cgp-meta-pill">🩺 Under Your Care</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="cgp-patient-actions">
                <a href="/diabetrack/public/caregiver/bloodsugar" class="cgp-btn-view">
                    🩸 View Logs →
                </a>
                <a href="/diabetrack/public/caregiver/patients?unlink=<?= $p['id'] ?>"
                   onclick="return confirm('Unlink <?= htmlspecialchars($p['name']) ?>? This cannot be undone.')"
                   class="cgp-btn-unlink">
                    Unlink
                </a>
            </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- RIGHT: LINK FORM -->
    <div class="cgp-form-panel">

        <div class="cgp-form-icon">🔗</div>
        <div class="cgp-form-title">Link a Patient</div>
        <div class="cgp-form-sub">
            Enter the patient's registered email address to connect their account to yours.
        </div>

        <form method="POST" action="/diabetrack/public/caregiver/patients">
            <label class="cgp-form-label">Patient Email Address</label>
            <input
                type="email"
                name="patient_email"
                class="cgp-form-input"
                placeholder="patient@email.com"
                required
            >

            <div class="cgp-form-info">
                💡 The patient must already have a registered DiabeTrack account with the
                <strong style="color:rgba(255,200,160,0.7);">Patient</strong> role.
            </div>

            <button type="submit" class="cgp-form-btn">
                🔗 Link Patient
            </button>
        </form>

        <div class="cgp-divider">currently linked</div>

        <div class="cgp-count-pill">
            <div class="cgp-count-num"><?= count($linkedPatients) ?></div>
            <div class="cgp-count-text">
                patient<?= count($linkedPatients) !== 1 ? 's' : '' ?> linked<br>
                to your account
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>