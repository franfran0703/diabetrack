<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — Create Account</title>
    <link rel="stylesheet" href="/diabetrack/public/assets/css/auth.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- LEFT PANEL -->
<div class="auth-left">
    <a href="/diabetrack/public/" class="auth-left-brand">
        <div class="auth-left-pill">🩺</div>
        <span class="auth-left-brand-name">DiabeTrack</span>
    </a>

    <div class="auth-left-body">
        <div class="auth-left-eyebrow">Get Started</div>
        <h2 class="auth-left-title">Start your<br>health journey.</h2>
        <p class="auth-left-sub">
            Join DiabeTrack and take control of your diabetes management today — for free.
        </p>
        <div class="auth-features">
            <div class="auth-feature-item">
                <div class="auth-feature-icon">🥗</div>
                <div class="auth-feature-text">Log meals with Filipino food database</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">🚶</div>
                <div class="auth-feature-text">Track daily activity and steps</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">📊</div>
                <div class="auth-feature-text">View trends and health insights</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">🗺️</div>
                <div class="auth-feature-text">Find clinics and pharmacies nearby</div>
            </div>
        </div>
    </div>

    <div class="auth-left-footer">© 2025 DiabeTrack. All rights reserved.</div>
</div>

<!-- RIGHT PANEL -->
<div class="auth-right">
    <div class="auth-form-wrap">

        <div class="auth-form-title">Create Account</div>
        <div class="auth-form-sub">Fill in your details to get started.</div>

        <?php if (isset($error)): ?>
        <div class="auth-alert error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/diabetrack/public/auth/register">

            <div class="auth-field">
                <label class="auth-label">Full Name</label>
                <input
                    type="text"
                    name="name"
                    class="auth-input"
                    placeholder="e.g. Maria Santos"
                    required
                    autocomplete="name"
                >
            </div>

            <div class="auth-field">
                <label class="auth-label">Email Address</label>
                <input
                    type="email"
                    name="email"
                    class="auth-input"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="auth-field">
                <label class="auth-label">Password</label>
                <div class="auth-input-wrap">
                    <input
                        type="password"
                        name="password"
                        id="reg-password"
                        class="auth-input"
                        placeholder="Create a strong password"
                        required
                        style="padding-right:42px;"
                    >
                    <span class="auth-input-icon" onclick="togglePass('reg-password', this)">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label">I am a...</label>
                <div class="auth-role-grid">
                    <button type="button" class="auth-role-btn selected" id="btn-patient" onclick="selectRole('patient')">
                        <span>🧑‍⚕️</span>
                        Patient
                    </button>
                    <button type="button" class="auth-role-btn" id="btn-caregiver" onclick="selectRole('caregiver')">
                        <span>👨‍👩‍👧</span>
                        Caregiver
                    </button>
                </div>
                <input type="hidden" name="role" id="role-input" value="patient">
            </div>

            <button type="submit" class="auth-submit">Create Account →</button>
        </form>

        <div class="auth-divider">or</div>

        <div class="auth-bottom">
            Already have an account?
            <a href="/diabetrack/public/auth/login">Sign in here →</a>
        </div>
        <div class="auth-bottom" style="margin-top:10px;">
            <a href="/diabetrack/public/" style="color:#b8927e;font-weight:600;">← Back to home</a>
        </div>

    </div>
</div>

<script>
function selectRole(role) {
    document.getElementById('role-input').value = role;
    document.getElementById('btn-patient').classList.toggle('selected', role === 'patient');
    document.getElementById('btn-caregiver').classList.toggle('selected', role === 'caregiver');
}

function togglePass(id, icon) {
    const input = document.getElementById(id);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.innerHTML = isPass
        ? '<i class="bi bi-eye-slash"></i>'
        : '<i class="bi bi-eye"></i>';
}
</script>
</body>
</html>
