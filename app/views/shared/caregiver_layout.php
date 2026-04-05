<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/diabetrack/public/assets/css/caregiver_layout.css?<?= time() ?>" rel="stylesheet">
</head>
<body>
<div class="page-wrap">

    <!-- TOPBAR -->
    <div class="topbar">

        <!-- Floating Nav Pill -->
        <div class="floatnav">
            <div class="brand">
                <div class="brand-pill">🩺</div>
                <span class="brand-name">DiabeTrack</span>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/dashboard"
                class="nav-btn <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-grid-fill"></i></span>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/patients"
                class="nav-btn <?= ($activeMenu ?? '') === 'patients' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
                    My Patients
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/bloodsugar"
                class="nav-btn <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-droplet-fill"></i></span>
                    Blood Sugar
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/medication"
                class="nav-btn <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-capsule"></i></span>
                    Medication
                </a>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/meals"
                class="nav-btn <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-egg-fried"></i></span>
                    Meals
                </a>
            </div>
            
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/alerts"
                class="nav-btn <?= ($activeMenu ?? '') === 'alerts' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-bell-fill"></i></span>
                    Alerts
                </a>
            </div>
            <div class="nav-item">
                <a href="/diabetrack/public/caregiver/reports"
                class="nav-btn <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-file-earmark-medical-fill"></i></span>
                    Reports
                </a>
            </div>
        </div>

        <!-- User Chip -->
        <div class="user-chip">
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <span class="user-role">Caregiver</span>
            </div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <a href="/diabetrack/public/auth/logout" class="logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>

    </div>

    <!-- Page Content -->
    <div class="page-body">
        <?= $content ?? '' ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>