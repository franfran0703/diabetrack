<?php $csrfToken = $_SESSION['csrf_token']; ?>
<script>const CSRF = '<?= $csrfToken ?>';</script>
<?php
$pageTitle  = 'Caregiver Access';
$activeMenu = 'caregivers';
ob_start();

$pendingCount = count($pendingRequests ?? []);
$activeCount  = count($activeCaregivers  ?? []);

/* ── Flash detection — controller redirects with ?flash= ── */
$flash = $_GET['flash'] ?? '';
$flashName = htmlspecialchars($_GET['name'] ?? 'Caregiver', ENT_QUOTES);

$accessScopes = [
    ['ti-droplet-half-2', 'Blood Sugar'],
    ['ti-pill',           'Medications'],
    ['ti-salad',          'Meals'],
    ['ti-run',            'Activity'],
    ['ti-clipboard-text', 'Reports'],
];

function cgInitials(string $name): string {
    $words = array_filter(explode(' ', trim($name)));
    return strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 2))));
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="/diabetrack/public/assets/css/caregiver_request.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="cgp-page-header">
    <div class="cgp-page-header-left">
        <div class="cgp-page-eyebrow">
            <i class="ti ti-users"></i> Manage
        </div>
        <h1 class="cgp-page-title">Caregiver <span>Access</span></h1>
        <p class="cgp-page-sub">Control who can monitor your health data.</p>
    </div>
    <div class="cgp-page-header-right">
        <?php if ($pendingCount > 0): ?>
        <div class="cgp-pending-badge">
            <i class="ti ti-bell-ringing"></i>
            <?= $pendingCount ?> request<?= $pendingCount !== 1 ? 's' : '' ?> pending
        </div>
        <?php endif; ?>
        <div class="cgp-active-badge <?= $activeCount > 0 ? 'has-active' : '' ?>">
            <i class="ti <?= $activeCount > 0 ? 'ti-shield-check' : 'ti-shield' ?>"></i>
            <?= $activeCount ?> active caregiver<?= $activeCount !== 1 ? 's' : '' ?>
        </div>
    </div>
</div>


<!-- ══ TOAST STACK ════════════════════════════════════════ -->
<div class="cgp-toast-stack" id="cgpToastStack" aria-live="polite"></div>

<!-- ══ SHARED CONFIRM OVERLAY (accept / decline / remove) ══ -->
<div class="cgp-confirm-overlay" id="cgpConfirmOverlay">
    <div class="cgp-confirm-box" id="cgpConfirmBox">
        <div class="cgp-confirm-icon" id="cgpConfirmIcon">
            <i class="ti ti-user-minus" id="cgpConfirmIconI"></i>
        </div>
        <div class="cgp-confirm-title" id="cgpConfirmTitle">Are you sure?</div>
        <div class="cgp-confirm-msg"   id="cgpConfirmMsg"></div>
        <div class="cgp-confirm-actions">
            <button class="cgp-confirm-cancel" id="cgpConfirmCancel">
                <i class="ti ti-x"></i> Cancel
            </button>
            <a class="cgp-confirm-proceed" id="cgpConfirmProceed" href="#">
                <!-- icon + label injected by JS -->
            </a>
        </div>
    </div>
</div>


<!-- ══ MAIN LAYOUT ═══════════════════════════════════════ -->
<div class="cgp-layout">

    <!-- ── LEFT: CAREGIVER LISTS ─────────────────────── -->
    <div class="cgp-directory">

        <!-- PENDING REQUESTS -->
        <div class="cgp-section-head">
            <div class="cgp-section-label cgp-section-label--pending">
                <i class="ti ti-clock"></i> Pending Requests
                <?php if ($pendingCount > 0): ?>
                <span class="cgp-section-count cgp-section-count--pending"><?= $pendingCount ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($pendingRequests)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon cgp-empty-icon--neutral">
                <i class="ti ti-mailbox"></i>
            </div>
            <div class="cgp-empty-title">No pending requests</div>
            <div class="cgp-empty-sub">When a caregiver sends you a request, it will appear here.</div>
        </div>
        <?php else: ?>
        <?php foreach ($pendingRequests as $cg):
            $initials = cgInitials($cg['name']);
        ?>
        <div class="cgp-card cgp-card--pending">
            <div class="cgp-card-stripe cgp-card-stripe--pending"></div>

            <div class="cgp-avatar cgp-avatar--pending">
                <?= htmlspecialchars($initials) ?>
            </div>

            <div class="cgp-card-info">
                <div class="cgp-card-name"><?= htmlspecialchars($cg['name']) ?></div>
                <div class="cgp-card-email">
                    <i class="ti ti-mail"></i>
                    <?= htmlspecialchars($cg['email']) ?>
                </div>
                <div class="cgp-card-meta">
                    <span class="cgp-meta-chip cgp-meta-chip--pending">
                        <i class="ti ti-clock"></i> Pending
                    </span>
                    <span class="cgp-meta-chip cgp-meta-chip--date">
                        <i class="ti ti-calendar"></i>
                        <?= date('M j, Y', strtotime($cg['requested_at'])) ?>
                    </span>
                </div>
                <div class="cgp-access-scopes">
                    <span class="cgp-access-label">Will see:</span>
                    <?php foreach ($accessScopes as [$icon, $label]): ?>
                    <span class="cgp-access-chip">
                        <i class="ti <?= $icon ?>"></i><?= $label ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cgp-card-actions">
                <a href="/diabetrack/public/patient/caregiverRequests?accept=<?= $cg['id'] ?>"
                   class="cgp-btn-accept"
                   onclick="openConfirm('accept', this.href, '<?= htmlspecialchars(addslashes($cg['name'])) ?>'); return false;">
                    <i class="ti ti-check"></i> Accept
                </a>
                <a href="/diabetrack/public/patient/caregiverRequests?decline=<?= $cg['id'] ?>"
                   class="cgp-btn-decline"
                   onclick="openConfirm('decline', this.href, '<?= htmlspecialchars(addslashes($cg['name'])) ?>'); return false;">
                    <i class="ti ti-x"></i> Decline
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>


        <!-- ACTIVE CAREGIVERS -->
        <div class="cgp-section-head" style="margin-top:32px;">
            <div class="cgp-section-label cgp-section-label--active">
                <i class="ti ti-shield-check"></i> Active Caregivers
                <?php if ($activeCount > 0): ?>
                <span class="cgp-section-count cgp-section-count--active"><?= $activeCount ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($activeCaregivers)): ?>
        <div class="cgp-empty">
            <div class="cgp-empty-icon cgp-empty-icon--neutral">
                <i class="ti ti-stethoscope"></i>
            </div>
            <div class="cgp-empty-title">No active caregivers</div>
            <div class="cgp-empty-sub">Caregivers you accept will appear here with full access to your health data.</div>
        </div>
        <?php else: ?>
        <?php foreach ($activeCaregivers as $cg):
            $initials   = cgInitials($cg['name']);
            $linkedAt   = date('M j, Y', strtotime($cg['linked_at']));
            $daysLinked = (int) round((time() - strtotime($cg['linked_at'])) / 86400);
        ?>
        <div class="cgp-card cgp-card--active">
            <div class="cgp-card-stripe cgp-card-stripe--active"></div>

            <div class="cgp-avatar cgp-avatar--active">
                <?= htmlspecialchars($initials) ?>
            </div>

            <div class="cgp-card-info">
                <div class="cgp-card-name">
                    <?= htmlspecialchars($cg['name']) ?>
                    <span class="cgp-trusted-badge">
                        <i class="ti ti-shield-check"></i> Trusted
                    </span>
                </div>
                <div class="cgp-card-email">
                    <i class="ti ti-mail"></i>
                    <?= htmlspecialchars($cg['email']) ?>
                </div>
                <div class="cgp-card-meta">
                    <span class="cgp-meta-chip cgp-meta-chip--active">
                        <i class="ti ti-circle-check"></i> Active
                    </span>
                    <span class="cgp-meta-chip cgp-meta-chip--date">
                        <i class="ti ti-calendar"></i> Since <?= $linkedAt ?>
                    </span>
                    <?php if ($daysLinked > 0): ?>
                    <span class="cgp-meta-chip cgp-meta-chip--days">
                        <i class="ti ti-clock"></i> <?= $daysLinked ?>d
                    </span>
                    <?php endif; ?>
                </div>
                <div class="cgp-access-scopes cgp-access-scopes--active">
                    <span class="cgp-access-label">Viewing:</span>
                    <?php foreach ($accessScopes as [$icon, $label]): ?>
                    <span class="cgp-access-chip cgp-access-chip--granted">
                        <i class="ti <?= $icon ?>"></i><?= $label ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cgp-card-actions">
                <button class="cgp-btn-remove"
                   onclick="openConfirm('remove',
                       '/diabetrack/public/patient/caregiverRequests?remove=<?= $cg['id'] ?>&_token=<?= $csrfToken ?>',
                       '<?= htmlspecialchars(addslashes($cg['name'])) ?>'
                   )">
                    <i class="ti ti-user-minus"></i> Remove Access
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>


    <!-- ── RIGHT: PRIVACY PANEL ──────────────────────── -->
    <div class="cgp-privacy-panel">

        <div class="cgp-privacy-shield">
            <div class="cgp-shield-ring">
                <div class="cgp-shield-icon">
                    <i class="ti ti-shield-check"></i>
                </div>
            </div>
        </div>

        <div class="cgp-privacy-title">Your Data, Your Control</div>
        <div class="cgp-privacy-sub">
            Only caregivers you <strong>accept</strong> can view your health data.
            You can revoke access at any time — it takes effect immediately.
        </div>

        <div class="cgp-privacy-counts">
            <div class="cgp-priv-count cgp-priv-count--active">
                <div class="cgp-priv-count-num"><?= $activeCount ?></div>
                <div class="cgp-priv-count-label">
                    <i class="ti ti-shield-check"></i> Active
                </div>
            </div>
            <div class="cgp-priv-count-divider"></div>
            <div class="cgp-priv-count <?= $pendingCount > 0 ? 'cgp-priv-count--pending' : '' ?>">
                <div class="cgp-priv-count-num"><?= $pendingCount ?></div>
                <div class="cgp-priv-count-label">
                    <i class="ti ti-clock"></i> Pending
                </div>
            </div>
        </div>

        <div class="cgp-privacy-divider">what they can see</div>

        <div class="cgp-scope-list">
            <?php foreach ([
                ['ti-droplet-half-2', '#c04a20', '#FDE8DC', 'Blood Sugar Readings',  'All logs and trends'],
                ['ti-pill',           '#0e7490', '#cffafe', 'Medication Records',     'Doses and schedules'],
                ['ti-salad',          '#0f7a45', '#d4f7e8', 'Meal & Nutrition Logs',  'Daily food intake'],
                ['ti-run',            '#d97706', '#fef3c7', 'Activity Data',          'Workouts and steps'],
                ['ti-clipboard-text', '#6d28d9', '#ede9fe', 'Doctor Reports',         'Health summaries'],
            ] as [$icon, $color, $bg, $title, $sub]): ?>
            <div class="cgp-scope-item">
                <div class="cgp-scope-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
                    <i class="ti <?= $icon ?>"></i>
                </div>
                <div class="cgp-scope-text">
                    <div class="cgp-scope-title"><?= $title ?></div>
                    <div class="cgp-scope-sub"><?= $sub ?></div>
                </div>
                <i class="ti ti-eye cgp-scope-eye" style="color:<?= $color ?>;"></i>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cgp-privacy-footer">
            <i class="ti ti-lock"></i>
            Caregivers cannot edit or delete your data — read-only access only.
        </div>

    </div>

</div>


<script>
/* ══════════════════════════════════════════════════════
   TOAST ENGINE
   ══════════════════════════════════════════════════════ */
const TOAST_ICONS = {
    success : 'ti-circle-check',
    error   : 'ti-alert-circle',
    warning : 'ti-alert-triangle',
    info    : 'ti-info-circle',
};

function showToast(type, title, message, duration = 5000) {
    const stack = document.getElementById('cgpToastStack');
    const id    = 'cgpT' + Date.now();

    const toast = document.createElement('div');
    toast.className = `cgp-toast cgp-toast--${type}`;
    toast.id = id;
    toast.innerHTML = `
        <div class="cgp-toast-icon"><i class="ti ${TOAST_ICONS[type] || 'ti-info-circle'}"></i></div>
        <div class="cgp-toast-body">
            <div class="cgp-toast-title">${title}</div>
            ${message ? `<div class="cgp-toast-msg">${message}</div>` : ''}
        </div>
        <button class="cgp-toast-close" onclick="dismissToast('${id}')" aria-label="Dismiss">
            <i class="ti ti-x"></i>
        </button>
        <div class="cgp-toast-progress" style="animation-duration:${duration}ms;"></div>
    `;

    stack.appendChild(toast);
    void toast.offsetWidth;
    toast.classList.add('show');

    if (duration > 0) setTimeout(() => dismissToast(id), duration);
    return id;
}

function dismissToast(id) {
    const t = document.getElementById(id);
    if (!t) return;
    t.classList.remove('show');
    t.classList.add('hide');
    setTimeout(() => t.remove(), 380);
}

/* ══════════════════════════════════════════════════════
   UNIFIED CONFIRM OVERLAY
   Works for accept / decline / remove — each gets its
   own icon, colours, title and proceed label.
   Navigate happens only after explicit user confirmation,
   so there are no race conditions or timing issues.
   ══════════════════════════════════════════════════════ */
const CONFIRM_CONFIG = {
    accept: {
        icon    : 'ti-check',
        iconBg  : '#d4f7e8',
        iconCol : '#0f7a45',
        boxBdr  : 'rgba(34,197,94,0.18)',
        title   : 'Grant Access?',
        msg     : (name) => `${name} will be able to view all your health data.`,
        btnBg   : 'linear-gradient(135deg,#16a34a,#22c55e)',
        btnShadow: 'rgba(34,197,94,0.38)',
        btnLabel: '<i class="ti ti-check"></i> Yes, Grant Access',
    },
    decline: {
        icon    : 'ti-x',
        iconBg  : '#fef3c7',
        iconCol : '#b45309',
        boxBdr  : 'rgba(217,119,6,0.18)',
        title   : 'Decline Request?',
        msg     : (name) => `${name}'s request will be declined and removed.`,
        btnBg   : 'linear-gradient(135deg,#92400e,#d97706)',
        btnShadow: 'rgba(217,119,6,0.38)',
        btnLabel: '<i class="ti ti-x"></i> Yes, Decline',
    },
    remove: {
        icon    : 'ti-user-minus',
        iconBg  : '#fde8e8',
        iconCol : '#b91c1c',
        boxBdr  : 'rgba(220,38,38,0.18)',
        title   : 'Remove Access?',
        msg     : (name) => `${name} will immediately lose access to all your health data.`,
        btnBg   : 'linear-gradient(135deg,#b91c1c,#ef4444)',
        btnShadow: 'rgba(220,38,38,0.38)',
        btnLabel: '<i class="ti ti-user-minus"></i> Yes, Remove',
    },
};

function openConfirm(action, href, name) {
    const cfg  = CONFIRM_CONFIG[action];
    const box  = document.getElementById('cgpConfirmBox');
    const icon = document.getElementById('cgpConfirmIcon');
    const iconI= document.getElementById('cgpConfirmIconI');
    const proceed = document.getElementById('cgpConfirmProceed');

    /* Apply config */
    icon.style.background  = cfg.iconBg;
    iconI.className        = 'ti ' + cfg.icon;
    iconI.style.color      = cfg.iconCol;
    box.style.borderColor  = cfg.boxBdr;
    document.getElementById('cgpConfirmTitle').textContent = cfg.title;
    document.getElementById('cgpConfirmMsg').textContent   = cfg.msg(name);
    proceed.innerHTML      = cfg.btnLabel;
    proceed.style.background   = cfg.btnBg;
    proceed.style.boxShadow    = `0 4px 16px ${cfg.btnShadow}`;
    proceed.href           = href;

    document.getElementById('cgpConfirmOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeConfirm() {
    document.getElementById('cgpConfirmOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('cgpConfirmCancel').addEventListener('click', closeConfirm);
document.getElementById('cgpConfirmOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConfirm(); });

/* ══════════════════════════════════════════════════════
   FLASH TOASTS on page load
   Reads PHP $success / $error, then URL ?flash= param
   ══════════════════════════════════════════════════════ */
window.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($success)): ?>
    showToast('success', 'Done!', '<?= addslashes(htmlspecialchars($success)) ?>');
    <?php elseif (!empty($error)): ?>
    showToast('error', 'Something went wrong', '<?= addslashes(htmlspecialchars($error)) ?>');
    <?php endif; ?>

    const params = new URLSearchParams(window.location.search);
    const flash  = params.get('flash');
    const name   = params.get('name') || 'Caregiver';

    const flashMap = {
        accepted : ['success', 'Access Granted',   `${name} can now view your health data.`],
        declined : ['warning', 'Request Declined',  `${name}'s request was declined.`],
        removed  : ['info',    'Access Removed',    `${name} can no longer access your data.`],
        error    : ['error',   'Something Went Wrong', params.get('msg') || 'Please try again.'],
    };

    if (flash && flashMap[flash]) {
        const [type, title, msg] = flashMap[flash];
        showToast(type, title, msg);
        window.history.replaceState({}, '', window.location.pathname);
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>