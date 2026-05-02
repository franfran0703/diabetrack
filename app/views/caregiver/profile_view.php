<?php
$pageTitle  = 'My Profile';
$activeMenu = 'profile';

ob_start();

$initial     = strtoupper(substr($user['name'], 0, 1));
$memberSince = date('F Y', strtotime($user['created_at']));
?>

<link href="/diabetrack/public/assets/css/caregiver_profile.css?<?= time() ?>" rel="stylesheet">

<div class="cgp-page">

    <!-- ══ Cover ══ -->
    <div class="cgp-cover">
        <div class="cgp-cover-line"></div>
    </div>

    <!-- ══ Identity strip ══ -->
    <div class="cgp-identity">
        <div class="cgp-avatar-wrap">
            <div class="cgp-avatar"><?= $initial ?></div>
        </div>
        <div class="cgp-identity-info">
            <div class="cgp-identity-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="cgp-identity-email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="cgp-identity-badge">
                <i class="bi bi-clipboard2-heart-fill"></i> Caregiver
            </span>
        </div>
        <div class="cgp-identity-since">
            <div class="cgp-identity-since-label">Member Since</div>
            <div class="cgp-identity-since-val"><?= $memberSince ?></div>
        </div>
    </div>

    <!-- ══ Stats bar ══ -->
    <div class="cgp-stats">
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['active_patients'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Active Patients</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="bi bi-bell-fill"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['alerts_sent'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Alerts Issued</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['reports_created'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Reports Created</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="bi bi-person-lines-fill"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['total_patients'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Total Patients</div>
            </div>
        </div>
    </div>

    <!-- ══ Settings layout ══ -->
    <div class="cgp-settings">

        <!-- Sidebar tabs -->
        <nav class="cgp-tabs">
            <button class="cgp-tab active" onclick="showSection('info', this)">
                <i class="bi bi-person-fill"></i> Personal Info
            </button>
            <button class="cgp-tab" onclick="showSection('password', this)">
                <i class="bi bi-shield-lock-fill"></i> Password
            </button>
            <div class="cgp-tab-divider"></div>
            <button class="cgp-tab" onclick="showSection('patients', this)">
                <i class="bi bi-people-fill"></i> My Patients
            </button>
            <div class="cgp-tab-divider"></div>
            <button class="cgp-tab" onclick="showSection('account', this)" style="color:#f87171;">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </button>
        </nav>

        <!-- Panels -->
        <div class="cgp-panels">

            <?php if (!empty($success)): ?>
                <div class="cgp-flash success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="cgp-flash error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Personal Information -->
            <div class="cgp-panel" id="section-info">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Personal Information</div>
                    <div class="cgp-panel-sub">Update your name and email address</div>
                </div>
                <div class="cgp-panel-body">
                    <form method="POST" action="/diabetrack/public/caregiver/updateProfile">
                        <input type="hidden" name="action" value="info">
                        <div class="cgp-form-2col">
                            <div class="cgp-field">
                                <label class="cgp-field-label">Full Name</label>
                                <input class="cgp-input" type="text" name="name"
                                       value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="cgp-field">
                                <label class="cgp-field-label">Email Address</label>
                                <input class="cgp-input" type="email" name="email"
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="cgp-field">
                            <label class="cgp-field-label">Role</label>
                            <input class="cgp-input" type="text" value="Caregiver" readonly>
                        </div>
                        <div class="cgp-btn-row">
                            <button class="cgp-btn cgp-btn-primary" type="submit">
                                <i class="bi bi-check2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="cgp-panel" id="section-password" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Change Password</div>
                    <div class="cgp-panel-sub">Choose a strong password to keep your account secure</div>
                </div>
                <div class="cgp-panel-body">
                    <form method="POST" action="/diabetrack/public/caregiver/updateProfile">
                        <input type="hidden" name="action" value="password">
                        <div class="cgp-field">
                            <label class="cgp-field-label">Current Password</label>
                            <input class="cgp-input" type="password" name="current_password"
                                   placeholder="Enter your current password" required>
                        </div>
                        <div class="cgp-form-2col">
                            <div class="cgp-field">
                                <label class="cgp-field-label">New Password</label>
                                <input class="cgp-input" type="password" name="new_password"
                                       id="newPw" placeholder="Min. 8 characters"
                                       oninput="updateStrength(this.value)" required>
                                <div class="cgp-pw-bar"><div class="cgp-pw-fill" id="pwFill"></div></div>
                            </div>
                            <div class="cgp-field">
                                <label class="cgp-field-label">Confirm New Password</label>
                                <input class="cgp-input" type="password" name="confirm_password"
                                       placeholder="Repeat new password" required>
                            </div>
                        </div>
                        <div class="cgp-btn-row">
                            <button class="cgp-btn cgp-btn-primary" type="submit">
                                <i class="bi bi-shield-check"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- My Patients -->
            <div class="cgp-panel" id="section-patients" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">My Patients</div>
                    <div class="cgp-panel-sub">Patients linked to your caregiver account</div>
                </div>
                <div class="cgp-panel-body">
                    <?php if (!empty($patients)): ?>
                        <div class="cgp-person-list">
                            <?php foreach ($patients as $p): ?>
                                <div class="cgp-person-row">
                                    <div class="cgp-person-av"><?= strtoupper(substr($p['name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="cgp-person-name"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="cgp-person-meta">
                                            <?= htmlspecialchars($p['email']) ?> · Linked <?= date('M d, Y', strtotime($p['linked_at'])) ?>
                                        </div>
                                    </div>
                                    <a href="/diabetrack/public/caregiver/dashboard?patient_id=<?= $p['id'] ?>"
                                       class="cgp-view-btn">
                                        <i class="bi bi-eye-fill"></i> View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="cgp-empty">
                            <i class="bi bi-person-x"></i>
                            <p>No patients linked to your account yet.</p>
                            <a href="/diabetrack/public/caregiver/patients" class="cgp-btn cgp-btn-ghost" style="display:inline-flex;">
                                <i class="bi bi-people"></i> Manage Patients
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account / Sign Out -->
            <div class="cgp-panel cgp-panel-danger" id="section-account" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Account Actions</div>
                    <div class="cgp-panel-sub">Manage your session</div>
                </div>
                <div class="cgp-panel-body">
                    <p class="cgp-danger-text">
                        Signing out will end your current session. Make sure you've saved any changes before leaving.
                    </p>
                    <a href="/diabetrack/public/auth/logout" class="cgp-btn cgp-btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </a>
                </div>
            </div>

        </div><!-- end cgp-panels -->
    </div><!-- end cgp-settings -->

</div><!-- end cgp-page -->

<script>
function showSection(id, btn) {
    document.querySelectorAll('.cgp-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.cgp-tab').forEach(t => t.classList.remove('active'));
    const el = document.getElementById('section-' + id);
    if (el) el.style.display = '';
    if (btn) btn.classList.add('active');
}
function updateStrength(val) {
    const fill = document.getElementById('pwFill');
    if (!fill) return;
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const widths = ['0%','25%','50%','75%','100%'];
    const colors = ['transparent','#ef4444','#f97316','#f59e0b','#22c55e'];
    fill.style.width      = widths[score];
    fill.style.background = colors[score];
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>