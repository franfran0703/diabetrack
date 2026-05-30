<?php
$pageTitle  = 'My Profile';
$activeMenu = 'profile';

ob_start();

$displayName = ucwords(strtolower($user['name']));
$initial     = strtoupper(substr($user['name'], 0, 1));
$memberSince = date('F Y', strtotime($user['created_at']));

$flashSuccess = $success ?? (isset($_GET['success']) ? urldecode($_GET['success']) : null);
$flashError   = $error   ?? (isset($_GET['error'])   ? urldecode($_GET['error'])   : null);

// Fetch 2FA status
require_once __DIR__ . '/../../../config/database.php';
$__db = (new Database())->connect();
$__s  = $__db->prepare("SELECT two_fa_enabled FROM users WHERE id = :id");
$__s->execute(['id' => $_SESSION['user_id']]);
$twoFaEnabled = (bool) $__s->fetchColumn();
?>

<link href="<?= BASE_URL ?>/assets/css/caregiver_profile.css?<?= time() ?>" rel="stylesheet">

<div class="cgp-page">

    <div class="cgp-cover"><div class="cgp-cover-line"></div></div>

    <div class="cgp-identity">
        <div class="cgp-avatar-wrap">
            <div class="cgp-avatar"><?= $initial ?></div>
        </div>
        <div class="cgp-identity-info">
            <div class="cgp-identity-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="cgp-identity-email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="cgp-identity-badge">
                <i class="ti ti-clipboard-heart"></i> Caregiver
            </span>
        </div>
        <div class="cgp-identity-since">
            <div class="cgp-identity-since-label">Member Since</div>
            <div class="cgp-identity-since-val"><?= $memberSince ?></div>
        </div>
    </div>

    <div class="cgp-stats">
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="ti ti-users"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['active_patients'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Active Patients</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="ti ti-bell"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['alerts_sent'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Alerts Issued</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="ti ti-file-text"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['reports_created'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Reports Created</div>
            </div>
        </div>
        <div class="cgp-stat-chip">
            <div class="cgp-stat-chip-icon"><i class="ti ti-id-badge"></i></div>
            <div>
                <div class="cgp-stat-chip-num"><?= $stats['total_patients'] ?? 0 ?></div>
                <div class="cgp-stat-chip-label">Total Patients</div>
            </div>
        </div>
    </div>

    <div class="cgp-settings">

        <nav class="cgp-tabs">
            <button class="cgp-tab active" onclick="showSection('info', this)">
                <i class="ti ti-user"></i> Personal Info
            </button>
            <button class="cgp-tab" onclick="showSection('password', this)">
                <i class="ti ti-shield-lock"></i> Password
            </button>
            <div class="cgp-tab-divider"></div>
            <button class="cgp-tab" onclick="showSection('patients', this)">
                <i class="ti ti-users"></i> My Patients
            </button>
            <div class="cgp-tab-divider"></div>
            <button class="cgp-tab" onclick="showSection('twofa', this)">
                <i class="ti ti-shield-check"></i> Two-Factor Auth
                <?php if ($twoFaEnabled): ?>
                <span style="background:#22c55e;color:#fff;font-size:0.55rem;font-weight:800;padding:2px 7px;border-radius:999px;margin-left:4px;">ON</span>
                <?php endif; ?>
            </button>
            <div class="cgp-tab-divider"></div>
            <button class="cgp-tab" onclick="showSection('account', this)" style="color:#f87171;">
                <i class="ti ti-logout"></i> Sign Out
            </button>
        </nav>

        <div class="cgp-panels">

            <?php if (!empty($flashSuccess)): ?>
                <div class="cgp-flash success"><i class="ti ti-circle-check"></i> <?= htmlspecialchars($flashSuccess) ?></div>
            <?php elseif (!empty($flashError)): ?>
                <div class="cgp-flash error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <!-- Personal Info -->
            <div class="cgp-panel" id="section-info">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Personal Information</div>
                    <div class="cgp-panel-sub">Update your name and email address</div>
                </div>
                <div class="cgp-panel-body">
                    <form method="POST" action="/caregiver/updateProfile">
                        <input type="hidden" name="action" value="info">
                        <div class="cgp-form-2col">
                            <div class="cgp-field">
                                <label class="cgp-field-label">Full Name</label>
                                <input class="cgp-input" type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="cgp-field">
                                <label class="cgp-field-label">Email Address</label>
                                <input class="cgp-input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="cgp-field">
                            <label class="cgp-field-label">Role</label>
                            <input class="cgp-input" type="text" value="Caregiver" readonly>
                        </div>
                        <div class="cgp-btn-row">
                            <button class="cgp-btn cgp-btn-primary" type="submit"><i class="ti ti-check"></i> Save Changes</button>
                        </div>
                    </form>

                    <div class="cgp-panel-head" style="margin-top:2rem;">
                        <div class="cgp-panel-title">Caregiver Details</div>
                        <div class="cgp-panel-sub">Your contact and relationship info</div>
                    </div>
                    <form method="POST" action="/caregiver/updateProfile">
                        <input type="hidden" name="action" value="caregiver_profile">
                        <div class="cgp-form-2col">
                            <div class="cgp-field">
                                <label class="cgp-field-label">Relationship to Patient</label>
                                <input class="cgp-input" type="text" name="relationship_to_patient"
                                       placeholder="e.g. Son, Nurse, Spouse"
                                       value="<?= htmlspecialchars($profile['relationship_to_patient'] ?? '') ?>">
                            </div>
                            <div class="cgp-field">
                                <label class="cgp-field-label">Contact Number</label>
                                <input class="cgp-input" type="text" name="contact_number"
                                       value="<?= htmlspecialchars($profile['contact_number'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="cgp-field">
                            <label class="cgp-field-label">Address</label>
                            <input class="cgp-input" type="text" name="address"
                                   value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
                        </div>
                        <div class="cgp-btn-row">
                            <button class="cgp-btn cgp-btn-primary" type="submit">
                                <i class="ti ti-check"></i> Save Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password -->
            <div class="cgp-panel" id="section-password" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Change Password</div>
                    <div class="cgp-panel-sub">Choose a strong password to keep your account secure</div>
                </div>
                <div class="cgp-panel-body">
                    <form method="POST" action="/caregiver/updateProfile">
                        <input type="hidden" name="action" value="password">
                        <div class="cgp-field">
                            <label class="cgp-field-label">Current Password</label>
                            <input class="cgp-input" type="password" name="current_password" placeholder="Enter your current password" required>
                        </div>
                        <div class="cgp-form-2col">
                            <div class="cgp-field">
                                <label class="cgp-field-label">New Password</label>
                                <input class="cgp-input" type="password" name="new_password" id="newPw" placeholder="Min. 8 characters" oninput="updateStrength(this.value)" required>
                                <div class="cgp-pw-bar"><div class="cgp-pw-fill" id="pwFill"></div></div>
                            </div>
                            <div class="cgp-field">
                                <label class="cgp-field-label">Confirm New Password</label>
                                <input class="cgp-input" type="password" name="confirm_password" placeholder="Repeat new password" required>
                            </div>
                        </div>
                        <div class="cgp-btn-row">
                            <button class="cgp-btn cgp-btn-primary" type="submit"><i class="ti ti-shield-check"></i> Update Password</button>
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
                                        <div class="cgp-person-name"><?= htmlspecialchars(ucwords(strtolower($p['name']))) ?></div>
                                        <div class="cgp-person-meta"><?= htmlspecialchars($p['email']) ?> · Linked <?= date('M d, Y', strtotime($p['linked_at'])) ?></div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/caregiver/switchPatient?pid=<?= $p['id'] ?>&redirect=<?= urlencode('/caregiver/dashboard') ?>" class="cgp-view-btn">
                                        <i class="ti ti-eye"></i> View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="cgp-empty">
                            <i class="ti ti-user-x"></i>
                            <p>No patients linked yet.</p>
                            <a href="<?= BASE_URL ?>/caregiver/patients" class="cgp-btn cgp-btn-ghost" style="display:inline-flex;">
                                <i class="ti ti-users"></i> Manage Patients
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two-Factor Auth -->
            <div class="cgp-panel" id="section-twofa" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Two-Factor Authentication</div>
                    <div class="cgp-panel-sub">Add an extra layer of security to your account</div>
                </div>
                <div class="cgp-panel-body">
                    <?php if ($twoFaEnabled): ?>
                        <div style="display:flex;align-items:center;gap:14px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:16px;padding:18px 20px;margin-bottom:20px;">
                            <span style="font-size:1.5rem;">🛡️</span>
                            <div>
                                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:0.9rem;color:#4ade80;">2FA is Active</div>
                                <div style="font-size:0.75rem;color:rgba(255,200,160,0.4);margin-top:2px;">Your account is protected with two-factor authentication.</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/caregiver/disable2fa"
                           class="cgp-btn cgp-btn-danger"
                           onclick="return confirm('Disable 2FA? Your account will be less secure.')"
                           style="display:inline-flex;">
                            <i class="ti ti-shield-x"></i> Disable 2FA
                        </a>
                    <?php else: ?>
                        <div style="display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:18px 20px;margin-bottom:20px;">
                            <span style="font-size:1.5rem;">🔓</span>
                            <div>
                                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:0.9rem;color:#ffe8d6;">2FA is Disabled</div>
                                <div style="font-size:0.75rem;color:rgba(255,200,160,0.4);margin-top:2px;">Enable 2FA to protect your account with Google Authenticator.</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/caregiver/setup2fa" class="cgp-btn cgp-btn-primary" style="display:inline-flex;">
                            <i class="ti ti-shield-check"></i> Enable 2FA
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sign Out -->
            <div class="cgp-panel cgp-panel-danger" id="section-account" style="display:none;">
                <div class="cgp-panel-head">
                    <div class="cgp-panel-title">Account Actions</div>
                    <div class="cgp-panel-sub">Manage your session</div>
                </div>
                <div class="cgp-panel-body">
                    <p class="cgp-danger-text">Signing out will end your current session. Make sure you've saved any changes before leaving.</p>
                    <a href="<?= BASE_URL ?>/auth/logout" class="cgp-btn cgp-btn-danger">
                        <i class="ti ti-logout"></i> Sign Out
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

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
// Auto-open 2FA tab if redirected from setup
if (window.location.search.includes('success') || window.location.search.includes('error')) {
    const twoFaBtn = document.querySelector('.cgp-tab:nth-child(5)');
    const successMsg = document.querySelector('.cgp-flash');
    if (successMsg && successMsg.textContent.includes('2FA')) {
        document.querySelectorAll('.cgp-tab')[4]?.click();
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>