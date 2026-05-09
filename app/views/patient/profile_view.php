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
require_once __DIR__ . '/../../../config/Database.php';
$__db = (new Database())->connect();
$__s  = $__db->prepare("SELECT two_fa_enabled FROM users WHERE id = :id");
$__s->execute(['id' => $_SESSION['user_id']]);
$twoFaEnabled = (bool) $__s->fetchColumn();
?>

<link href="/diabetrack/public/assets/css/patient_profile.css?<?= time() ?>" rel="stylesheet">

<div class="pp-page">

    <div class="pp-cover"></div>

    <div class="pp-identity">
        <div class="pp-avatar-wrap">
            <div class="pp-avatar"><?= $initial ?></div>
        </div>
        <div class="pp-identity-info">
            <div class="pp-identity-name"><?= htmlspecialchars($displayName) ?></div>
            <div class="pp-identity-email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="pp-identity-badge">
                <i class="bi bi-heart-pulse-fill"></i> Patient
            </span>
        </div>
        <div class="pp-identity-since">
            <div class="pp-identity-since-label">Member Since</div>
            <div class="pp-identity-since-val"><?= $memberSince ?></div>
        </div>
    </div>

    <div class="pp-stats">
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="bi bi-droplet-fill"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['blood_sugar_logs'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">BS Readings</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="bi bi-capsule"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['medications'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Meds Tracked</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="bi bi-egg-fried"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['meal_logs'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Meal Entries</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="bi bi-person-heart"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['caregivers'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Caregivers</div>
            </div>
        </div>
    </div>

    <div class="pp-settings">

        <nav class="pp-tabs">
            <button class="pp-tab active" onclick="showSection('info', this)">
                <i class="bi bi-person-fill"></i> Personal Info
            </button>
            <button class="pp-tab" onclick="showSection('password', this)">
                <i class="bi bi-shield-lock-fill"></i> Password
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('caregivers', this)">
                <i class="bi bi-person-heart"></i> Caregivers
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('twofa', this)">
                <i class="bi bi-shield-check"></i> Two-Factor Auth
                <?php if ($twoFaEnabled): ?>
                <span style="background:#22c55e;color:#fff;font-size:0.55rem;font-weight:800;padding:2px 7px;border-radius:999px;margin-left:4px;">ON</span>
                <?php endif; ?>
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('account', this)" style="color:#c0392b;">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </button>
        </nav>

        <div class="pp-panels">

            <?php if (!empty($flashSuccess)): ?>
                <div class="pp-flash success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($flashSuccess) ?></div>
            <?php elseif (!empty($flashError)): ?>
                <div class="pp-flash error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <!-- Personal Info -->
            <div class="pp-panel" id="section-info">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Personal Information</div>
                    <div class="pp-panel-sub">Update your name and email address</div>
                </div>
                <div class="pp-panel-body">
                    <form method="POST" action="/diabetrack/public/patient/updateProfile">
                        <input type="hidden" name="action" value="info">
                        <div class="pp-form-2col">
                            <div class="pp-field">
                                <label class="pp-field-label">Full Name</label>
                                <input class="pp-input" type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="pp-field">
                                <label class="pp-field-label">Email Address</label>
                                <input class="pp-input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="pp-field">
                            <label class="pp-field-label">Role</label>
                            <input class="pp-input" type="text" value="Patient" readonly>
                        </div>
                        <div class="pp-btn-row">
                            <button class="pp-btn pp-btn-primary" type="submit"><i class="bi bi-check2"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password -->
            <div class="pp-panel" id="section-password" style="display:none;">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Change Password</div>
                    <div class="pp-panel-sub">Choose a strong password to keep your account secure</div>
                </div>
                <div class="pp-panel-body">
                    <form method="POST" action="/diabetrack/public/patient/updateProfile">
                        <input type="hidden" name="action" value="password">
                        <div class="pp-field">
                            <label class="pp-field-label">Current Password</label>
                            <input class="pp-input" type="password" name="current_password" placeholder="Enter your current password" required>
                        </div>
                        <div class="pp-form-2col">
                            <div class="pp-field">
                                <label class="pp-field-label">New Password</label>
                                <input class="pp-input" type="password" name="new_password" id="newPw" placeholder="Min. 8 characters" oninput="updateStrength(this.value)" required>
                                <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
                            </div>
                            <div class="pp-field">
                                <label class="pp-field-label">Confirm New Password</label>
                                <input class="pp-input" type="password" name="confirm_password" placeholder="Repeat new password" required>
                            </div>
                        </div>
                        <div class="pp-btn-row">
                            <button class="pp-btn pp-btn-primary" type="submit"><i class="bi bi-shield-check"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Caregivers -->
            <div class="pp-panel" id="section-caregivers" style="display:none;">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">My Caregivers</div>
                    <div class="pp-panel-sub">People who monitor your health data</div>
                </div>
                <div class="pp-panel-body">
                    <?php if (!empty($caregivers)): ?>
                        <div class="pp-person-list">
                            <?php foreach ($caregivers as $cg): ?>
                                <div class="pp-person-row">
                                    <div class="pp-person-av"><?= strtoupper(substr($cg['name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="pp-person-name"><?= htmlspecialchars(ucwords(strtolower($cg['name']))) ?></div>
                                        <div class="pp-person-meta"><?= htmlspecialchars($cg['email']) ?> · Linked <?= date('M d, Y', strtotime($cg['linked_at'])) ?></div>
                                    </div>
                                    <?php if ($cg['status'] === 'accepted'): ?>
                                        <span class="pp-badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="pp-badge-pending"><?= ucfirst($cg['status']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pp-empty">
                            <i class="bi bi-person-x"></i>
                            <p>No caregivers linked yet.</p>
                            <a href="/diabetrack/public/patient/caregiverRequests" class="pp-btn pp-btn-ghost">
                                <i class="bi bi-person-plus"></i> Manage Requests
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Two-Factor Auth -->
            <div class="pp-panel" id="section-twofa" style="display:none;">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Two-Factor Authentication</div>
                    <div class="pp-panel-sub">Add an extra layer of security to your account</div>
                </div>
                <div class="pp-panel-body">
                    <?php if ($twoFaEnabled): ?>
                        <div style="display:flex;align-items:center;gap:14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.22);border-radius:16px;padding:18px 20px;margin-bottom:20px;">
                            <span style="font-size:1.5rem;">🛡️</span>
                            <div>
                                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:0.9rem;color:#15803d;">2FA is Active</div>
                                <div style="font-size:0.75rem;color:#a0714f;margin-top:2px;">Your account is protected with two-factor authentication.</div>
                            </div>
                        </div>
                        <a href="/diabetrack/public/patient/disable2fa"
                           class="pp-btn pp-btn-danger"
                           onclick="return confirm('Disable 2FA? Your account will be less secure.')"
                           style="display:inline-flex;">
                            <i class="bi bi-shield-x"></i> Disable 2FA
                        </a>
                    <?php else: ?>
                        <div style="display:flex;align-items:center;gap:14px;background:rgba(249,116,71,0.06);border:1px solid rgba(249,116,71,0.14);border-radius:16px;padding:18px 20px;margin-bottom:20px;">
                            <span style="font-size:1.5rem;">🔓</span>
                            <div>
                                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:0.9rem;color:#1a0800;">2FA is Disabled</div>
                                <div style="font-size:0.75rem;color:#a0714f;margin-top:2px;">Enable 2FA to protect your account with Google Authenticator.</div>
                            </div>
                        </div>
                        <a href="/diabetrack/public/patient/setup2fa" class="pp-btn pp-btn-primary" style="display:inline-flex;">
                            <i class="bi bi-shield-check"></i> Enable 2FA
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sign Out -->
            <div class="pp-panel pp-panel-danger" id="section-account" style="display:none;">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Account Actions</div>
                    <div class="pp-panel-sub">Manage your session</div>
                </div>
                <div class="pp-panel-body">
                    <p class="pp-danger-text">Signing out will end your current session. Make sure you've saved any changes before leaving.</p>
                    <a href="/diabetrack/public/auth/logout" class="pp-btn pp-btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function showSection(id, btn) {
    document.querySelectorAll('.pp-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.pp-tab').forEach(t => t.classList.remove('active'));
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
require_once __DIR__ . '/../shared/patient_layout.php';
?>