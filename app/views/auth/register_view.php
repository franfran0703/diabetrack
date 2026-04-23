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
<a href="/diabetrack/public/" class="auth-home-float">
    <span class="home-arrow">←</span> Home
</a>
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>
<div class="auth-orb auth-orb-3"></div>

<div class="auth-shell">

    <!-- LEFT — Form -->
    <div class="auth-left">
        <div>
            <a href="/diabetrack/public/" class="auth-brand">
                <div class="auth-brand-pill">🩺</div>
                <span class="auth-brand-name">DiabeTrack</span>
            </a>

            <div class="auth-heading">Start your <span>journey.</span></div>
            <div class="auth-subheading">Create your free DiabeTrack account today.</div>

            <?php if (isset($error)): ?>
            <div class="auth-alert error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/diabetrack/public/auth/register">
                <div class="auth-field">
                    <label class="auth-label">Full Name</label>
                    <input type="text" name="name" class="auth-input"
                           placeholder="e.g. Maria Santos" required autocomplete="name">
                </div>
                <div class="auth-field">
                    <label class="auth-label">Email Address</label>
                    <input type="email" name="email" class="auth-input"
                           placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="auth-field">
                    <label class="auth-label">Password</label>
                    <div class="auth-input-wrap">
                        <input type="password" name="password" id="reg-password"
                               class="auth-input" placeholder="Create a strong password"
                               required style="padding-right:44px;">
                        <span class="auth-input-icon" onclick="togglePass('reg-password', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">I am a...</label>
                    <div class="auth-role-grid">
                        <button type="button" class="auth-role-btn selected"
                                id="btn-patient" onclick="selectRole('patient')">
                            <span>🧑‍⚕️</span> Patient
                        </button>
                        <button type="button" class="auth-role-btn"
                                id="btn-caregiver" onclick="selectRole('caregiver')">
                            <span>👨‍👩‍👧</span> Caregiver
                        </button>
                    </div>
                    <input type="hidden" name="role" id="role-input" value="patient">
                </div>
                <button type="submit" class="auth-submit">Create Account →</button>
            </form>

            <div class="auth-divider">or</div>
        </div>
        

        <div class="auth-bottom-row">
            <span class="auth-bottom-text">
                Have an account? <a href="/diabetrack/public/auth/login">Sign in →</a>
            </span>
        </div>
    </div>

    <!-- RIGHT — Visual -->
    <div class="auth-right">
        <div class="auth-right-title">
            Your health,<br><span>our priority.</span>
        </div>
        <div class="auth-right-sub">
            Join DiabeTrack and take control today.
        </div>

        <div class="auth-cards-wrap">

            <div class="auth-float-card card-peach">
                <div class="auth-card-icon">📅</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Next Appointment</div>
                    <div class="auth-card-sub">Dr. Santos · Tomorrow</div>
                </div>
                <div class="auth-card-val">9:00 AM</div>
                <div class="auth-card-dot normal"></div>
            </div>

            <div class="auth-float-card card-glass">
                <div class="auth-card-icon">🔔</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">High Sugar Alert</div>
                    <div class="auth-card-sub">Caregiver notified</div>
                </div>
                <div class="auth-card-val">257 mg/dL</div>
                <div class="auth-card-dot danger"></div>
            </div>

            <div class="auth-float-card card-cream">
                <div class="auth-card-icon">📊</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Weekly Activity</div>
                    <div class="auth-card-sub">This week · 5 sessions</div>
                </div>
                <div class="auth-card-val">142 min</div>
            </div>

            <div class="auth-float-card card-peach">
                <div class="auth-card-icon">🥗</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Daily Carbs</div>
                    <div class="auth-card-sub">Today · 3 meals</div>
                </div>
                <div class="auth-card-val">98g</div>
                <div class="auth-card-dot warn"></div>
            </div>

        </div>
    </div>

</div>

<script>
function selectRole(role) {
    document.getElementById('role-input').value = role;
    document.getElementById('btn-patient').classList.toggle('selected',   role === 'patient');
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