<?php
$pageTitle  = 'My Patients';
$activeMenu = 'patients';
ob_start();

$accepted = array_filter($linkedPatients, fn($p) => ($p['status'] ?? '') === 'accepted');
$pending  = array_filter($linkedPatients, fn($p) => ($p['status'] ?? '') === 'pending');
?>

<link href="/diabetrack/public/assets/css/caregiver_patients.css?v=<?= time() ?>" rel="stylesheet">

<!-- ══ HEADER ════════════════════════════════════════════ -->
<div class="cgp-header">
    <div>
        <div class="cgp-eyebrow"><i class="ti ti-users"></i> Patient Management</div>
        <h1 class="cgp-title">My <span>Patients</span></h1>
        <p class="cgp-sub"><?= date('l, F j') ?> &middot; Manage the patients under your care</p>
    </div>
</div>

<!-- ══ ALERTS ════════════════════════════════════════════ -->
<?php if ($success): ?>
<div class="cgp-alert success">
    <i class="ti ti-circle-check"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="cgp-alert error">
    <i class="ti ti-alert-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- ══ MAIN LAYOUT ════════════════════════════════════════ -->
<div class="cgp-layout">

    <!-- ── LEFT: Patient Directory ── -->
    <div class="cgp-directory">

        <?php if (empty($linkedPatients)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon"><i class="ti ti-user-off"></i></div>
            <div class="cgp-empty-title">No patients linked yet</div>
            <div class="cgp-empty-sub">Use the form on the right to link a patient using their registered email address.</div>
        </div>

        <?php else: ?>

        <?php
        // Show accepted first, then pending
        $ordered = array_merge(
            array_values($accepted),
            array_values($pending)
        );
        foreach ($ordered as $idx => $p):
            $isAccepted = ($p['status'] ?? '') === 'accepted';
            $isPending  = ($p['status'] ?? '') === 'pending';
            $initials   = strtoupper(substr($p['name'], 0, 1));
            $linkedDate = $isAccepted && $p['linked_at']
                ? date('M j, Y', strtotime($p['linked_at']))
                : date('M j, Y', strtotime($p['requested_at'] ?? 'now'));
        ?>
        <div class="cgp-patient-card <?= $isAccepted ? 'accepted' : 'pending' ?>"
             style="animation-delay: <?= $idx * 0.06 ?>s">

            <!-- Status badge -->
            <div class="cgp-status-badge <?= $isAccepted ? 'accepted' : 'pending' ?>">
                <?= $isAccepted ? 'Active' : 'Pending' ?>
            </div>

            <!-- Avatar -->
            <div class="cgp-avatar <?= $isPending ? 'pending-avatar' : '' ?>">
                <?= $initials ?>
            </div>

            <!-- Info -->
            <div class="cgp-patient-info">
                <div class="cgp-patient-name"><?= htmlspecialchars(ucwords(strtolower($p['name']))) ?></div>
                <div class="cgp-patient-email">
                    <i class="ti ti-mail"></i>
                    <?= htmlspecialchars($p['email']) ?>
                </div>
                <div class="cgp-patient-meta">
                    <?php if ($isAccepted): ?>
                    <span class="cgp-meta-pill"><i class="ti ti-link"></i> Linked</span>
                    <span class="cgp-meta-pill"><i class="ti ti-calendar"></i> Since <?= $linkedDate ?></span>
                    <?php if (!empty($p['relationship_to_patient'])): ?>
                    <span class="cgp-meta-pill"><i class="ti ti-heart"></i> <?= htmlspecialchars(ucfirst($p['relationship_to_patient'])) ?></span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="cgp-meta-pill pending-pill"><i class="ti ti-clock"></i> Awaiting patient approval</span>
                    <span class="cgp-meta-pill pending-pill"><i class="ti ti-calendar"></i> Sent <?= $linkedDate ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="cgp-patient-actions">
                <?php if ($isAccepted): ?>
                <a href="/diabetrack/public/caregiver/switchPatient?pid=<?= $p['id'] ?>&redirect=<?= urlencode('/diabetrack/public/caregiver/dashboard') ?>"
                   class="cgp-btn-view">
                    <i class="ti ti-layout-dashboard"></i> View Dashboard
                </a>
                <a href="/diabetrack/public/caregiver/patients?unlink=<?= $p['id'] ?>"
                   onclick="return confirm('Unlink <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>? This cannot be undone.')"
                   class="cgp-btn-unlink">
                    <i class="ti ti-unlink"></i> Unlink
                </a>
                <?php else: ?>
                <div class="cgp-btn-pending">
                    <i class="ti ti-hourglass"></i> Awaiting Approval
                </div>
                <a href="/diabetrack/public/caregiver/patients?unlink=<?= $p['id'] ?>"
                   onclick="return confirm('Cancel the link request to <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?')"
                   class="cgp-btn-unlink">
                    <i class="ti ti-x"></i> Cancel
                </a>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /.cgp-directory -->


    <!-- ── RIGHT: Link Form Panel ── -->
    <div class="cgp-form-panel">

        <div class="cgp-form-panel-header">
            <div class="cgp-form-panel-icon"><i class="ti ti-user-plus"></i></div>
            <div>
                <div class="cgp-form-panel-title">Link a Patient</div>
                <div class="cgp-form-panel-sub">Connect a patient account to yours</div>
            </div>
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

            <label class="cgp-form-label">Your Relationship <span style="color:rgba(255,180,130,0.22);font-size:8px;">optional</span></label>
            <input
                type="text"
                name="relationship_to_patient"
                class="cgp-form-input"
                placeholder="e.g. Family member, Nurse, Spouse…"
            >

            <div class="cgp-form-info">
                <i class="ti ti-info-circle"></i>
                The patient must already have a registered DiabeTrack account with the
                <strong style="color:rgba(255,200,160,0.65);">Patient</strong> role.
                They'll receive a request they must accept before you can view their data.
            </div>

            <button type="submit" class="cgp-form-btn">
                <i class="ti ti-link"></i> Send Link Request
            </button>

        </form>

        <div class="cgp-divider">account summary</div>

        <div class="cgp-panel-summary">
            <div class="cgp-panel-summary-item">
                <div class="cgp-panel-summary-num"><?= count($linkedPatients) ?></div>
                <div class="cgp-panel-summary-label">Total Linked</div>
            </div>
            <div class="cgp-panel-summary-divider"></div>
            <div class="cgp-panel-summary-item">
                <div class="cgp-panel-summary-num" style="color:#4ade80;"><?= count($accepted) ?></div>
                <div class="cgp-panel-summary-label">Active</div>
            </div>
            <div class="cgp-panel-summary-divider"></div>
            <div class="cgp-panel-summary-item">
                <div class="cgp-panel-summary-num" style="color:#fcd34d;"><?= count($pending) ?></div>
                <div class="cgp-panel-summary-label">Pending</div>
            </div>
        </div>

    </div><!-- /.cgp-form-panel -->

</div><!-- /.cgp-layout -->

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>