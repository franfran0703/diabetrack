<?php
$pageTitle  = 'My Profile';
$activeMenu = 'profile';

ob_start();

$displayName = ucwords(strtolower($user['name']));
$initial     = strtoupper(substr($user['name'], 0, 1));
$memberSince = date('F Y', strtotime($user['created_at']));

$flashSuccess = $success ?? (isset($_GET['success']) ? urldecode($_GET['success']) : null);
$flashError   = $error   ?? (isset($_GET['error'])   ? urldecode($_GET['error'])   : null);

// Fetch 2FA status + health settings (weight, goal)
require_once __DIR__ . '/../../../config/database.php';
$__db = (new Database())->connect();
$__s  = $__db->prepare("SELECT two_fa_enabled FROM users WHERE id = :id");
$__s->execute(['id' => $_SESSION['user_id']]);
$twoFaEnabled = (bool) $__s->fetchColumn();

// Load health settings from patient_profiles (weight + activity goal)
$__ps = $__db->prepare("SELECT weight_kg, activity_goal_mins FROM patient_profiles WHERE user_id = :id LIMIT 1");
$__ps->execute(['id' => $_SESSION['user_id']]);
$patientProfile   = $__ps->fetch(PDO::FETCH_ASSOC) ?: [];
$profileWeightKg  = $patientProfile['weight_kg']          ?? null;
$profileGoalMins  = $patientProfile['activity_goal_mins'] ?? 30;
?>

<link href="<?= BASE_URL ?>/assets/css/patient_profile.css?<?= time() ?>" rel="stylesheet">

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
                <i class="ti ti-heart-rate-monitor"></i> Patient
            </span>
        </div>
        <div class="pp-identity-since">
            <div class="pp-identity-since-label">Member Since</div>
            <div class="pp-identity-since-val"><?= $memberSince ?></div>
        </div>
    </div>

    <div class="pp-stats">
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="ti ti-droplet-half-2"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['blood_sugar_logs'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">BS Readings</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="ti ti-pill"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['medications'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Meds Tracked</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="ti ti-bowl-spoon"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['meal_logs'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Meal Entries</div>
            </div>
        </div>
        <div class="pp-stat-chip">
            <div class="pp-stat-chip-icon"><i class="ti ti-heart-handshake"></i></div>
            <div>
                <div class="pp-stat-chip-num"><?= $stats['caregivers'] ?? 0 ?></div>
                <div class="pp-stat-chip-label">Caregivers</div>
            </div>
        </div>
    </div>

    <div class="pp-settings">

        <nav class="pp-tabs">
            <button class="pp-tab active" onclick="showSection('info', this)">
                <i class="ti ti-user"></i> Personal Info
            </button>
            <button class="pp-tab" id="tab-health" onclick="showSection('health', this)">
                <i class="ti ti-heartbeat"></i> Health Settings
                <?php if (!$profileWeightKg): ?>
                <span class="pp-tab-badge pp-tab-badge--warn">Set up</span>
                <?php endif; ?>
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('password', this)">
                <i class="ti ti-shield-lock"></i> Password
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('caregivers', this)">
                <i class="ti ti-heart-handshake"></i> Caregivers
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('twofa', this)">
                <i class="ti ti-shield-check"></i> Two-Factor Auth
                <?php if ($twoFaEnabled): ?>
                <span style="background:#22c55e;color:#fff;font-size:0.55rem;font-weight:800;padding:2px 7px;border-radius:999px;margin-left:4px;">ON</span>
                <?php endif; ?>
            </button>
            <div class="pp-tab-divider"></div>
            <button class="pp-tab" onclick="showSection('account', this)" style="color:#c0392b;">
                <i class="ti ti-logout"></i> Sign Out
            </button>
        </nav>

        <div class="pp-panels">

            <?php if (!empty($flashSuccess)): ?>
                <div class="pp-flash success"><i class="ti ti-circle-check"></i> <?= htmlspecialchars($flashSuccess) ?></div>
            <?php elseif (!empty($flashError)): ?>
                <div class="pp-flash error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <!-- Personal Info -->
            <div class="pp-panel" id="section-info">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Personal Information</div>
                    <div class="pp-panel-sub">Update your name and email address</div>
                </div>
                <div class="pp-panel-body">
                    <form method="POST" action="/patient/updateProfile">
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
                            <button class="pp-btn pp-btn-primary" type="submit"><i class="ti ti-check"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Health Settings -->
            <div class="pp-panel" id="section-health" style="display:none;">
                <div class="pp-panel-head">
                    <div class="pp-panel-title">Health Settings</div>
                    <div class="pp-panel-sub">Your weight and goal are used to personalise calorie estimates and daily activity targets across the app</div>
                </div>
                <div class="pp-panel-body">
                    <form method="POST" action="/patient/updateProfile" id="healthForm">
                        <input type="hidden" name="action" value="info">
                        <!-- Hidden name/email so the existing info action still validates -->
                        <input type="hidden" name="name"  value="<?= htmlspecialchars($user['name']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">

                        <div class="pp-health-section-label">
                            <i class="ti ti-scale"></i> Body Weight
                        </div>

                        <div class="pp-form-2col">
                            <div class="pp-field">
                                <label class="pp-field-label">
                                    Weight (kg)
                                    <span class="pp-field-hint">Used for precise calorie calculation</span>
                                </label>
                                <div class="pp-weight-input-wrap">
                                    <input class="pp-input" type="number" name="weight_kg"
                                           id="weightInput"
                                           min="20" max="300" step="0.1"
                                           value="<?= htmlspecialchars($profileWeightKg ?? '') ?>"
                                           placeholder="e.g. 68.5">
                                    <span class="pp-unit-badge">kg</span>
                                </div>
                                <?php if (!$profileWeightKg): ?>
                                <div class="pp-field-notice">
                                    <i class="ti ti-info-circle"></i>
                                    Not set — calorie estimates are currently based on an average 70 kg person
                                </div>
                                <?php else: ?>
                                <div class="pp-field-notice pp-field-notice--ok">
                                    <i class="ti ti-circle-check"></i>
                                    Using your weight for precise calorie calculations
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="pp-field">
                                <label class="pp-field-label">
                                    Height (cm)
                                    <span class="pp-field-hint">Optional — for BMI display only</span>
                                </label>
                                <div class="pp-weight-input-wrap">
                                    <input class="pp-input" type="number" name="height_cm"
                                           id="heightInput"
                                           min="100" max="250" step="1"
                                           value="<?= htmlspecialchars($patientProfile['height_cm'] ?? '') ?>"
                                           placeholder="e.g. 165"
                                           oninput="updateBMI()">
                                    <span class="pp-unit-badge">cm</span>
                                </div>
                                <div class="pp-bmi-display" id="bmiDisplay" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="pp-health-section-label" style="margin-top:22px;">
                            <i class="ti ti-run"></i> Daily Activity Goal
                        </div>

                        <div class="pp-field">
                            <label class="pp-field-label">
                                Daily movement target (minutes)
                                <span class="pp-field-hint">ADA recommends 30 min/day for Type 2 diabetes</span>
                            </label>
                            <div class="pp-goal-preset-row">
                                <?php foreach ([15=>'Light (15m)', 20=>'Gentle (20m)', 30=>'ADA Rec. (30m)', 45=>'Active (45m)', 60=>'Athlete (60m)'] as $mins => $lbl): ?>
                                <button type="button"
                                        class="pp-goal-preset <?= $profileGoalMins === $mins ? 'active' : '' ?>"
                                        onclick="setGoalPreset(<?= $mins ?>, this)">
                                    <?= $lbl ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="pp-weight-input-wrap" style="margin-top:10px;max-width:200px;">
                                <input class="pp-input" type="number" name="activity_goal_mins"
                                       id="goalInput"
                                       min="10" max="180" step="5"
                                       value="<?= htmlspecialchars($profileGoalMins) ?>"
                                       oninput="syncPresets(this.value)">
                                <span class="pp-unit-badge">min/day</span>
                            </div>
                            <div class="pp-field-notice" style="margin-top:8px;">
                                <i class="ti ti-info-circle"></i>
                                Your activity ring on the Activity page will use this target
                            </div>
                        </div>

                        <div class="pp-btn-row">
                            <button class="pp-btn pp-btn-primary" type="submit">
                                <i class="ti ti-device-floppy"></i> Save Health Settings
                            </button>
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
                    <form method="POST" action="/patient/updateProfile">
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
                            <button class="pp-btn pp-btn-primary" type="submit"><i class="ti ti-shield-check"></i> Update Password</button>
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
                            <i class="ti ti-user-x"></i>
                            <p>No caregivers linked yet.</p>
                            <a href="<?= BASE_URL ?>/patient/caregiverRequests" class="pp-btn pp-btn-ghost">
                                <i class="ti ti-user-plus"></i> Manage Requests
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
                        <a href="<?= BASE_URL ?>/patient/disable2fa"
                           class="pp-btn pp-btn-danger"
                           onclick="return confirm('Disable 2FA? Your account will be less secure.')"
                           style="display:inline-flex;">
                            <i class="ti ti-shield-x"></i> Disable 2FA
                        </a>
                    <?php else: ?>
                        <div style="display:flex;align-items:center;gap:14px;background:rgba(249,116,71,0.06);border:1px solid rgba(249,116,71,0.14);border-radius:16px;padding:18px 20px;margin-bottom:20px;">
                            <span style="font-size:1.5rem;">🔓</span>
                            <div>
                                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:0.9rem;color:#1a0800;">2FA is Disabled</div>
                                <div style="font-size:0.75rem;color:#a0714f;margin-top:2px;">Enable 2FA to protect your account with Google Authenticator.</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/patient/setup2fa" class="pp-btn pp-btn-primary" style="display:inline-flex;">
                            <i class="ti ti-shield-check"></i> Enable 2FA
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
                    <a href="<?= BASE_URL ?>/auth/logout" class="pp-btn pp-btn-danger">
                        <i class="ti ti-logout"></i> Sign Out
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
// Health Settings JS
function setGoalPreset(mins, btn) {
    document.querySelectorAll('.pp-goal-preset').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('goalInput').value = mins;
}
function syncPresets(val) {
    const v = parseInt(val);
    document.querySelectorAll('.pp-goal-preset').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.textContent) === v);
    });
}
function updateBMI() {
    const wt = parseFloat(document.getElementById('weightInput')?.value);
    const ht = parseFloat(document.getElementById('heightInput')?.value);
    const el = document.getElementById('bmiDisplay');
    if (!el) return;
    if (wt > 0 && ht > 0) {
        const bmi = wt / Math.pow(ht / 100, 2);
        const cat = bmi < 18.5 ? 'Underweight' : bmi < 25 ? 'Normal weight' : bmi < 30 ? 'Overweight' : 'Obese';
        const col = bmi < 18.5 ? '#f59e0b' : bmi < 25 ? '#0f7a45' : bmi < 30 ? '#d97706' : '#dc2626';
        el.style.display = 'flex';
        el.innerHTML = `<i class="ti ti-calculator"></i> BMI: <strong style="color:${col};">${bmi.toFixed(1)}</strong> — ${cat}`;
    } else {
        el.style.display = 'none';
    }
}
// Auto-open Health Settings if weight is unset (query param nudge from activity page)
document.addEventListener('DOMContentLoaded', () => {
    if (location.hash === '#health-settings') {
        const btn = document.getElementById('tab-health');
        if (btn) { showSection('health', btn); btn.scrollIntoView({behavior:'smooth', block:'center'}); }
    }
    // Init BMI display if values already set
    updateBMI();
});

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