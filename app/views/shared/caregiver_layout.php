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
                <a href="/diabetrack/public/caregiver/alerts"
                class="nav-btn <?= ($activeMenu ?? '') === 'alerts' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="ti ti-bell"></i></span>
                    Alerts
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


</body>
</html>