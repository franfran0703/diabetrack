<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link href="/diabetrack/public/assets/css/caregiver_layout.css?<?= time() ?>" rel="stylesheet">
    <link href="/diabetrack/public/assets/css/caregiver_chip.css?<?= time() ?>" rel="stylesheet">
</head>
<body>
<div class="page-wrap">

    <!-- TOPBAR -->
    <div class="topbar">

        <!-- Floating Nav Pill -->
        <div class="floatnav">

            <div class="brand">
                <div class="brand-pill">
                    <img src="/diabetrack/public/assets/img/diabetrack-icon.png" 
                         alt="" style="width:22px;height:22px;object-fit:contain;">
                </div>
                <span class="brand-name">DiabeTrack</span>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/patients"
                class="nav-btn <?= ($activeMenu ?? '') === 'patients' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-users"></i></span>
                    My Patients
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/dashboard"
                class="nav-btn <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-layout-grid"></i></span>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/bloodsugar"
                class="nav-btn <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-droplet-half-2"></i></span>
                    Blood Sugar
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/medication"
                class="nav-btn <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-pill"></i></span>
                    Medication
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/meals"
                class="nav-btn <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-bowl-spoon"></i></span>
                    Meals
                </a>
            </div>
            <div class="nav-item">
                <?php
                if (empty($__cgUnread)) {
                    try {
                        $__db3 = (new Database())->connect();
                        $__db3->exec("CREATE TABLE IF NOT EXISTS `chat_messages` (`id` int(11) NOT NULL AUTO_INCREMENT,`caregiver_id` int(11) NOT NULL,`patient_id` int(11) NOT NULL,`sender_id` int(11) NOT NULL,`sender_type` enum('caregiver','patient') NOT NULL,`body` text NOT NULL,`sent_at` timestamp NOT NULL DEFAULT current_timestamp(),`read_at` timestamp NULL DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                        $__alertsUnread = $__db3->prepare("SELECT COUNT(*) FROM alerts a JOIN caregiver_links cl ON cl.patient_id = a.user_id WHERE cl.caregiver_id = :cid AND cl.status = 'accepted' AND a.is_read = 0");
                        $__alertsUnread->execute(['cid' => $_SESSION['user_id']]);
                        $__au = (int)$__alertsUnread->fetchColumn();
                        $__msgsUnread = $__db3->prepare("SELECT COUNT(*) FROM chat_messages WHERE caregiver_id = :cid AND sender_type = 'patient' AND read_at IS NULL");
                        $__msgsUnread->execute(['cid' => $_SESSION['user_id']]);
                        $__mu = (int)$__msgsUnread->fetchColumn();
                        $__cgUnread = $__au + $__mu;
                    } catch(Exception $e) { $__cgUnread = 0; }
                }
                ?>
                <a href="/diabetrack/public/caregiver/alerts"
                class="nav-btn <?= ($activeMenu ?? '') === 'alerts' ? 'active' : '' ?>"
                style="position:relative;">
                    <span class="nav-icon"><i class="ti ti-bell"></i></span>
                    Alerts
                    <?php if (!empty($__cgUnread) && $__cgUnread > 0): ?>
                    <span style="position:absolute;top:2px;right:2px;background:#f97447;color:#fff;font-size:9px;font-weight:900;border-radius:999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 3px;"><?= $__cgUnread ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/reports"
                class="nav-btn <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-report-medical"></i></span>
                    Reports
                </a>
            </div>

        </div>
        <!-- end floatnav -->

        <!-- Mobile hamburger (shown on <=640px) -->
        <button class="mobile-menu-btn" onclick="openMobileNav()" aria-label="Open menu">
            <i class="ti ti-menu-2"></i>
        </button>

        <!-- User Chip -->
        <div class="user-chip">
            <a href="/diabetrack/public/caregiver/profile" class="avatar" title="My Profile" style="text-decoration:none;color:inherit;"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></a>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars(ucwords(strtolower($_SESSION['user_name']))) ?></span>
                <span class="user-role">Caregiver</span>
            </div>
            <a href="/diabetrack/public/auth/logout" class="logout-btn" title="Logout">
                <i class="ti ti-logout"></i>
            </a>
        </div>

    </div>

    <!-- Page Content -->
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
            <img src="/diabetrack/public/assets/img/diabetrack-icon.png" alt="" style="width:26px;height:26px;object-fit:contain;">
            <span class="drawer-brand-name">DiabeTrack</span>
        </div>
        <button class="drawer-close" onclick="closeMobileNav()"><i class="ti ti-x"></i></button>
    </div>

    <div class="drawer-user">
        <div class="avatar" style="width:36px;height:36px;font-size:0.82rem;"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div class="drawer-user-info">
            <div class="drawer-user-name"><?= htmlspecialchars(ucwords(strtolower($_SESSION['user_name']))) ?></div>
            <div class="drawer-user-role">Caregiver</div>
        </div>
    </div>

    <div class="drawer-nav">
        <div class="drawer-nav-label">Main</div>
        <a href="/diabetrack/public/caregiver/patients" class="drawer-nav-link <?= ($activeMenu ?? '') === 'patients' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-users"></i></span> My Patients
        </a>
        <a href="/diabetrack/public/caregiver/dashboard" class="drawer-nav-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-layout-grid"></i></span> Dashboard
        </a>
        <a href="/diabetrack/public/caregiver/bloodsugar" class="drawer-nav-link <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-droplet-half-2"></i></span> Blood Sugar
        </a>
        <a href="/diabetrack/public/caregiver/medication" class="drawer-nav-link <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-pill"></i></span> Medication
        </a>
        <a href="/diabetrack/public/caregiver/meals" class="drawer-nav-link <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-bowl-spoon"></i></span> Meals
        </a>
        <a href="/diabetrack/public/caregiver/alerts" class="drawer-nav-link <?= ($activeMenu ?? '') === 'alerts' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-bell"></i></span> Alerts
            <?php if (!empty($__cgUnread) && $__cgUnread > 0): ?>
            <span class="drawer-nav-badge"><?= $__cgUnread ?></span>
            <?php endif; ?>
        </a>
        <a href="/diabetrack/public/caregiver/reports" class="drawer-nav-link <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
            <span class="drawer-nav-icon"><i class="ti ti-report-medical"></i></span> Reports
        </a>
    </div>

    <div class="drawer-footer">
        <a href="/diabetrack/public/caregiver/profile" class="drawer-nav-link">
            <span class="drawer-nav-icon"><i class="ti ti-user-circle"></i></span> My Profile
        </a>
        <a href="/diabetrack/public/auth/logout" class="drawer-logout">
            <span class="drawer-nav-icon" style="background:rgba(220,60,60,0.1);color:rgba(255,120,120,0.8);"><i class="ti ti-logout"></i></span> Log Out
        </a>
    </div>
</nav>

<script>
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

