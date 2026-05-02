<?php
$pageTitle  = 'Caregiver Requests';
$activeMenu = 'caregivers';
ob_start();
?>
	
<link href="/diabetrack/public/assets/css/caregiver_patients.css?v=<?= time() ?>" rel="stylesheet">

<div class="cgp-header">
    <div>
        <div class="cgp-eyebrow">Manage</div>
        <h1 class="cgp-title">Caregiver <span>Requests</span></h1>
        <p class="cgp-sub">Review who wants to monitor your health data.</p>
    </div>
</div>

<div class="cgp-layout">

    <!-- LEFT: PENDING REQUESTS -->
    <div class="cgp-directory">

        <div style="font-size:0.78rem;font-weight:700;letter-spacing:0.08em;color:rgba(255,255,255,0.35);margin-bottom:14px;">
            PENDING REQUESTS
        </div>

        <?php if (empty($pendingRequests)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon">📭</div>
            <div class="cgp-empty-title">No pending requests</div>
            <div class="cgp-empty-sub">When a caregiver sends you a request, it will appear here.</div>
        </div>
        <?php else: ?>
        <?php foreach ($pendingRequests as $cg): ?>
        <div class="cgp-patient-card">
            <div class="cgp-avatar"><?= strtoupper(substr($cg['name'], 0, 1)) ?></div>
            <div class="cgp-patient-info">
                <div class="cgp-patient-name"><?= htmlspecialchars($cg['name']) ?></div>
                <div class="cgp-patient-email">📧 <?= htmlspecialchars($cg['email']) ?></div>
                <div class="cgp-patient-meta">
                    <span class="cgp-meta-pill">⏳ Pending</span>
                    <span class="cgp-meta-pill">📅 <?= date('M d, Y', strtotime($cg['requested_at'])) ?></span>
                </div>
            </div>
            <div class="cgp-patient-actions">
                <a href="/diabetrack/public/patient/caregiverRequests?accept=<?= $cg['id'] ?>"
                class="cgp-btn-view"
                onclick="return confirm('Allow <?= htmlspecialchars($cg['name']) ?> to view your health data?')">
                    ✅ Accept
                </a>
                <a href="/diabetrack/public/patient/caregiverRequests?decline=<?= $cg['id'] ?>"
                class="cgp-btn-unlink"
                onclick="return confirm('Decline this request?')">
                    Decline
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div style="font-size:0.78rem;font-weight:700;letter-spacing:0.08em;color:rgba(255,255,255,0.35);margin:28px 0 14px;">
            ACTIVE CAREGIVERS
        </div>

        <?php if (empty($activeCaregivers)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon">🩺</div>
            <div class="cgp-empty-title">No active caregivers</div>
            <div class="cgp-empty-sub">Accepted caregivers will appear here.</div>
        </div>
        <?php else: ?>
        <?php foreach ($activeCaregivers as $cg): ?>
        <div class="cgp-patient-card">
            <div class="cgp-avatar"><?= strtoupper(substr($cg['name'], 0, 1)) ?></div>
            <div class="cgp-patient-info">
                <div class="cgp-patient-name"><?= htmlspecialchars($cg['name']) ?></div>
                <div class="cgp-patient-email">📧 <?= htmlspecialchars($cg['email']) ?></div>
                <div class="cgp-patient-meta">
                    <span class="cgp-meta-pill">✅ Active</span>
                    <span class="cgp-meta-pill">📅 Since <?= date('M d, Y', strtotime($cg['linked_at'])) ?></span>
                </div>
            </div>
            <div class="cgp-patient-actions">
                <a href="/diabetrack/public/patient/caregiverRequests?remove=<?= $cg['id'] ?>"
                class="cgp-btn-unlink"
                onclick="return confirm('Remove <?= htmlspecialchars($cg['name']) ?> from your caregivers? They will lose access to your data.')">
                    Remove
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- RIGHT: INFO PANEL -->
    <div class="cgp-form-panel">
        <div class="cgp-form-icon">🔒</div>
        <div class="cgp-form-title">Your Privacy</div>
        <div class="cgp-form-sub">
            Only caregivers you <strong style="color:rgba(255,200,160,0.8);">accept</strong> can view your health data.
            You can remove a caregiver at any time and they will immediately lose access.
        </div>

        <div style="margin-top:24px;">
            <div class="cgp-divider">summary</div>
            <div class="cgp-count-pill">
                <div class="cgp-count-num"><?= count($activeCaregivers) ?></div>
                <div class="cgp-count-text">active caregiver<?= count($activeCaregivers) !== 1 ? 's' : '' ?><br>monitoring you</div>
            </div>
            <?php if (count($pendingRequests) > 0): ?>
            <div class="cgp-count-pill" style="margin-top:12px;border-color:rgba(249,116,71,0.5);">
                <div class="cgp-count-num" style="color:#f97447;"><?= count($pendingRequests) ?></div>
                <div class="cgp-count-text">pending request<?= count($pendingRequests) !== 1 ? 's' : '' ?><br>awaiting your response</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>