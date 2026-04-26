<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/diabetrack/public/assets/css/patient_layout.css?<?= time() ?>" rel="stylesheet">
</head>
<body>

<?php
// Always fetch pending caregiver request count for the nav badge
if (empty($pendingCaregiverRequests)) {
    require_once __DIR__ . '/../../../config/Database.php';
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
                    <img src="/diabetrack/public/assets/img/diabetrack-icon.png" 
                         alt="" style="width:22px;height:22px;object-fit:contain;">
                </div>
                <span class="brand-name">DiabeTrack</span>
            </div>  

            <div class="nav-item">
                <a href="/diabetrack/public/patient/dashboard"
                   class="nav-btn <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-grid-fill"></i></span>
                    Dashboard
                </a>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/patient/bloodsugar"
                   class="nav-btn <?= ($activeMenu ?? '') === 'bloodsugar' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-droplet-fill"></i></span>
                    Blood Sugar
                </a>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/patient/medication"
                   class="nav-btn <?= ($activeMenu ?? '') === 'medication' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-capsule"></i></span>
                    Medication
                </a>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/patient/meals"
                   class="nav-btn <?= ($activeMenu ?? '') === 'meals' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-egg-fried"></i></span>
                    Meals &amp; Carbs
                </a>
            </div>

            <div class="nav-item">
                <a href="/diabetrack/public/patient/activity"
                   class="nav-btn <?= ($activeMenu ?? '') === 'activity' ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="bi bi-activity"></i></span>
                    Activity
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
                        <a class="drop-row" href="/diabetrack/public/patient/appointments">
                            <div class="drop-icon"><i class="bi bi-calendar-check-fill"></i></div>
                            <div>
                                <div class="drop-title">Appointments</div>
                                <div class="drop-desc">View &amp; book doctor visits</div>
                            </div>
                        </a>
                        <a class="drop-row" href="/diabetrack/public/patient/reports">
                            <div class="drop-icon"><i class="bi bi-file-earmark-medical-fill"></i></div>
                            <div>
                                <div class="drop-title">Doctor Reports</div>
                                <div class="drop-desc">Your medical summaries</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <div class="drop-label">Resources</div>
                        <a class="drop-row" href="/diabetrack/public/patient/education">
                            <div class="drop-icon"><i class="bi bi-book-fill"></i></div>
                            <div>
                                <div class="drop-title">Education Hub</div>
                                <div class="drop-desc">Tips, guides &amp; articles</div>
                            </div>
                        </a>
                        <a class="drop-row" href="/diabetrack/public/patient/nearby">
                            <div class="drop-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="drop-title">Nearby Services</div>
                                <div class="drop-desc">Clinics &amp; pharmacies near you</div>
                            </div>
                            <a class="drop-row" href="/diabetrack/public/patient/caregiverRequests">
                            <div class="drop-icon" style="position:relative;">
                                <i class="bi bi-person-check-fill"></i>
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

        <!-- User chip -->
        <div class="user-chip">
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <span class="user-role">Patient</span>
            </div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <a href="/diabetrack/public/auth/logout" class="logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>

    </div>
    <!-- end topbar -->

    <!-- Page Content — your views handle their own greeting -->
    <div class="page-body">
        <?= $content ?? '' ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</script>
</body>
</html>