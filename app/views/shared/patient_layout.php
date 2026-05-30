<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="<?= BASE_URL ?>/assets/css/patient_layout.css?<?= time() ?>" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/patient_chip.css?<?= time() ?>" rel="stylesheet">
</head>
<body>

<?php
// Always fetch pending caregiver request count for the nav badge
if (empty($pendingCaregiverRequests)) {
    require_once __DIR__ . '/../../../config/database.php';
    $__db = (new Database())->connect();
    $__s = $__db->prepare("SELECT COUNT(*) FROM caregiver_links WHERE patient_id = :pid AND status = 'pending'");
    $__s->execute(['pid' => $_SESSION['user_id']]);
    $pendingCaregiverRequests = $__s->fetchColumn();
}
?>

<div class="page-wrap">

    <!-- Top bar: nav left, user right -->
    <div class="topbar">

        <!-- Floating Nav Pill -->
        <div class="floatnav">

        <div class="brand">
                <div class="brand-pill">
                    <img src="<?= BASE_URL ?>/assets/img/diabetrack-icon.png" 
                         alt="" style="width:22px;height:22px;object-fit:contain;">
                </div>
                <span class="brand-name">DiabeTrack</span>
            </div>  

            <div class="nav-item">
                <a href="<?= BASE_URL ?>/patient/dashboard"
                   class="nav-btn <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-layout-grid"></i></span>
                    Dashboard
                </a>
            </div>

            <div class="nav-item">
                <a href="<?= BASE_URL ?>/patient/bloodsugar"
                   class="nav-btn <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-droplet-half-2"></i></span>
                    Blood Sugar
                </a>
            </div>

            <div class="nav-item">
                <a href="<?= BASE_URL ?>/patient/medication"
                   class="nav-btn <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-pill"></i></span>
                    Medication
                </a>
            </div>

            <div class="nav-item">
                <a href="<?= BASE_URL ?>/patient/meals"
                   class="nav-btn <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-bowl-spoon"></i></span>
                    Meals &amp; Carbs
                </a>
            </div>

            <div class="nav-item">
                <a href="<?= BASE_URL ?>/patient/activity"
                   class="nav-btn <?= ($activeMenu ?? '') === 'activity' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-activity"></i></span>
                    Activity
                </a>
            </div>

            <div class="nav-item">
                <?php
                // Unread message count for patient
                if (empty($__chatUnread)) {
                    try {
                        $__db2 = (new Database())->connect();
                        $__db2->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (`id` int(11) NOT NULL AUTO_INCREMENT,`caregiver_id` int(11) NOT NULL,`patient_id` int(11) NOT NULL,`sender_id` int(11) NOT NULL,`sender_type` enum('caregiver','patient') NOT NULL,`body` text NOT NULL,`sent_at` timestamp NOT NULL DEFAULT current_timestamp(),`read_at` timestamp NULL DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                        $__cu = $__db2->prepare("SELECT COUNT(*) FROM chat_messages WHERE patient_id = :pid AND sender_type = 'caregiver' AND read_at IS NULL");
                        $__cu->execute(['pid' => $_SESSION['user_id']]);
                        $__chatUnread = (int)$__cu->fetchColumn();
                    } catch(Exception $e) { $__chatUnread = 0; }
                }
                ?>
                <a href="<?= BASE_URL ?>/patient/messages"
                   class="nav-btn <?= ($activeMenu ?? '') === 'messages' ? 'active' : '' ?>"
                   style="position:relative;">
                    <span class="nav-icon"><i class="ti ti-message-circle"></i></span>
                    Messages
                    <?php if (!empty($__chatUnread) && $__chatUnread > 0): ?>
                    <span style="position:absolute;top:2px;right:2px;background:#f97447;color:#fff;font-size:9px;font-weight:900;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;"><?= $__chatUnread ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- More -->
            <div class="nav-item" id="more-item">
                <button class="nav-btn <?= in_array(($activeMenu ?? ''), ['appointments','reports','education','nearby']) ? 'active' : '' ?>"
                        id="more-btn" onclick="toggleMore(event)">
                    More
                    <svg class="chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <polyline points="4,6 8,10 12,6"/>
                    </svg>
                </button>

                <div class="dropdown more-panel" id="more-dropdown">
                    <div class="col">
                        <div class="drop-label">Schedule</div>
                        <a class="drop-row" href="<?= BASE_URL ?>/patient/appointments">
                            <div class="drop-icon"><i class="ti ti-calendar-check"></i></div>
                            <div>
                                <div class="drop-title">Appointments</div>
                                <div class="drop-desc">View &amp; book doctor visits</div>
                            </div>
                        </a>
                        <a class="drop-row" href="<?= BASE_URL ?>/patient/reports">
                            <div class="drop-icon"><i class="ti ti-report-medical"></i></div>
                            <div>
                                <div class="drop-title">Doctor Reports</div>
                                <div class="drop-desc">Your medical summaries</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <div class="drop-label">Resources</div>
                        <a class="drop-row" href="<?= BASE_URL ?>/patient/education">
                            <div class="drop-icon"><i class="ti ti-book"></i></div>
                            <div>
                                <div class="drop-title">Education Hub</div>
                                <div class="drop-desc">Tips, guides &amp; articles</div>
                            </div>
                        </a>
                        <a class="drop-row" href="<?= BASE_URL ?>/patient/nearby">
                            <div class="drop-icon"><i class="ti ti-map-pin"></i></div>
                            <div>
                                <div class="drop-title">Nearby Services</div>
                                <div class="drop-desc">Clinics &amp; pharmacies near you</div>
                            </div>
                        </a>
                        <a class="drop-row" href="<?= BASE_URL ?>/patient/caregiverRequests">
                            <div class="drop-icon" style="position:relative;">
                                <i class="ti ti-user-check"></i>
                                <?php if (!empty($pendingCaregiverRequests) && $pendingCaregiverRequests > 0): ?>
                                <span style="position:absolute;top:-4px;right:-4px;background:#f97447;color:#fff;font-size:0.6rem;font-weight:700;border-radius:999px;width:14px;height:14px;display:flex;align-items:center;justify-content:center;">
                                    <?= $pendingCaregiverRequests ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="drop-title">
                                    Caregivers
                                    <?php if (!empty($pendingCaregiverRequests) && $pendingCaregiverRequests > 0): ?>
                                    <span style="background:#f97447;color:#fff;font-size:0.65rem;font-weight:700;border-radius:999px;padding:1px 7px;margin-left:6px;">
                                        <?= $pendingCaregiverRequests ?> new
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="drop-desc">Manage who monitors your data</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
        <!-- end floatnav -->

        <!-- Mobile hamburger (shown on <=640px) -->
        <button class="mobile-menu-btn" onclick="openMobileNav()" aria-label="Open menu">
            <i class="ti ti-menu-2"></i>
        </button>

        <!-- User chip -->
        <div class="user-chip">
            <a href="<?= BASE_URL ?>/patient/profile" class="avatar" title="My Profile" style="text-decoration:none;color:inherit;"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></a>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars(ucwords(strtolower($_SESSION['user_name']))) ?></span>
                <span class="user-role">Patient</span>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout" class="logout-btn" title="Logout">
                <i class="ti ti-logout"></i>
            </a>
        </div>

    </div>
    <!-- end topbar -->

    <!-- Page Content — your views handle their own greeting -->
    <div class="page-body">
        <?= $content ?? '' ?>
    </div>

</div>


<!-- Mobile Nav Overlay -->
<div class="mobile-nav-overlay" id="mobile-nav-overlay" onclick="closeMobileNav()"></div>

<!-- Mobile Nav Drawer -->
<nav class="mobile-nav-drawer" id="mobile-nav-drawer" aria-label="Mobile navigation">
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="<?= BASE_URL ?>/assets/img/diabetrack-icon.png" alt="" style="width:26px;height:26px;object-fit:contain;">
            <span class="drawer-brand-name">DiabeTrack</span>
        </div>
        <button class="drawer-close" onclick="closeMobileNav()"><i class="ti ti-x"></i></button>
    </div>

    <div class="drawer-user">
        <div class="avatar" style="width:36px;height:36px;font-size:0.82rem;"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div class="drawer-user-info">
            <div class="drawer-user-name"><?= htmlspecialchars(ucwords(strtolower($_SESSION['user_name']))) ?></div>
            <div class="drawer-user-role">Patient</div>
        </div>
    </div>

    <div class="drawer-nav">
        <div class="drawer-nav-label">Main</div>
        <a href="<?= BASE_URL ?>/patient/dashboard" class="drawer-nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-layout-grid"></i></span> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/patient/bloodsugar" class="drawer-nav-link <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-droplet-half-2"></i></span> Blood Sugar
        </a>
        <a href="<?= BASE_URL ?>/patient/medication" class="drawer-nav-link <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-pill"></i></span> Medication
        </a>
        <a href="<?= BASE_URL ?>/patient/meals" class="drawer-nav-link <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-bowl-spoon"></i></span> Meals &amp; Carbs
        </a>
        <a href="<?= BASE_URL ?>/patient/activity" class="drawer-nav-link <?= ($activeMenu ?? '') === 'activity' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-activity"></i></span> Activity
        </a>
        <a href="<?= BASE_URL ?>/patient/messages" class="drawer-nav-link <?= ($activeMenu ?? '') === 'messages' ? 'active' : '' ?>" style="position:relative;">
            <span class="drawer-nav-icon"><i class="ti ti-message-circle"></i></span> Messages
            <?php if (!empty($__chatUnread) && $__chatUnread > 0): ?>
            <span class="drawer-nav-badge"><?= $__chatUnread ?></span>
            <?php endif; ?>
        </a>

        <div class="drawer-nav-label">Schedule</div>
        <a href="<?= BASE_URL ?>/patient/appointments" class="drawer-nav-link <?= ($activeMenu ?? '') === 'appointments' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-calendar-check"></i></span> Appointments
        </a>
        <a href="<?= BASE_URL ?>/patient/reports" class="drawer-nav-link <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-report-medical"></i></span> Doctor Reports
        </a>

        <div class="drawer-nav-label">Resources</div>
        <a href="<?= BASE_URL ?>/patient/education" class="drawer-nav-link <?= ($activeMenu ?? '') === 'education' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-book"></i></span> Education Hub
        </a>
        <a href="<?= BASE_URL ?>/patient/nearby" class="drawer-nav-link <?= ($activeMenu ?? '') === 'nearby' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-map-pin"></i></span> Nearby Services
        </a>
        <a href="<?= BASE_URL ?>/patient/caregiverRequests" class="drawer-nav-link <?= ($activeMenu ?? '') === 'caregiverRequests' ? 'active' : '' ?>">
            <span class="drawer-nav-icon" style="position:relative;"><i class="ti ti-user-check"></i></span> Caregivers
            <?php if (!empty($pendingCaregiverRequests) && $pendingCaregiverRequests > 0): ?>
            <span class="drawer-nav-badge"><?= $pendingCaregiverRequests ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="drawer-footer">
        <a href="<?= BASE_URL ?>/patient/profile" class="drawer-nav-link">
            <span class="drawer-nav-icon"><i class="ti ti-user-circle"></i></span> My Profile
        </a>
        <a href="<?= BASE_URL ?>/auth/logout" class="drawer-logout">
            <span class="drawer-nav-icon" style="background:rgba(220,60,60,0.08);color:#c0392b;"><i class="ti ti-logout"></i></span> Log Out
        </a>
    </div>
</nav>

<script>
function toggleMore(e) {
    e.stopPropagation();
    const btn = document.getElementById('more-btn');
    const drop = document.getElementById('more-dropdown');
    const isOpen = drop.classList.contains('show');
    closeAll();
    if (!isOpen) {
        drop.classList.add('show');
        btn.classList.add('open');
    }
}
function closeAll() {
    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('open'));
}
document.addEventListener('click', () => closeAll());

// ── Mobile Nav Drawer ──
function openMobileNav() {
    document.getElementById('mobile-nav-drawer').classList.add('open');
    document.getElementById('mobile-nav-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeMobileNav() {
    document.getElementById('mobile-nav-drawer').classList.remove('open');
    document.getElementById('mobile-nav-overlay').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMobileNav(); });
</script>
</body>
</html>