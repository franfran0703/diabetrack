<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — Sign In</title>
    <link rel="stylesheet" href="/diabetrack/public/assets/css/auth.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Floating Home Button -->
<a href="/diabetrack/public/" class="auth-home-float">
    <span class="home-arrow">←</span> Home
</a>

<!-- Ambient Orbs -->
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>
<div class="auth-orb auth-orb-3"></div>

<div class="auth-shell">

    <!-- LEFT — Form -->
    <div class="auth-left">
        <div>
         <a href="/diabetrack/public/" class="auth-brand">
    <div class="auth-brand-pill">
        <img src="/diabetrack/public/assets/img/diabetrack-icon.png" style="width:32px;height:32px;object-fit:contain;">
    </div>
    <span class="auth-brand-name">DiabeTrack</span>
</a>

            <div class="auth-heading">Welcome <span>back.</span></div>
            <div class="auth-subheading">Sign in to continue your health journey.</div>

            <?php if (isset($error)): ?>
            <div class="auth-alert error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/diabetrack/public/auth/login">
                <div class="auth-field">
                    <label class="auth-label">Email Address</label>
                    <input type="email" name="email" class="auth-input"
                           placeholder="you@example.com" required autocomplete="email">
                </div>
                <div class="auth-field">
                    <label class="auth-label">Password</label>
                    <div class="auth-input-wrap">
                        <input type="password" name="password" id="password"
                               class="auth-input" placeholder="Enter your password"
                               required style="padding-right:44px;">
                        <span class="auth-input-icon" onclick="togglePass('password', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="auth-submit">Sign In →</button>
            </form>

            <div class="auth-divider">or continue with</div>

            <div class="auth-social-grid">

                <!-- Google -->
                <a href="#" class="auth-social-btn btn-google">
                    <svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>

                <!-- Facebook -->
                <a href="#" class="auth-social-btn btn-facebook">
                    <svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M24 12.073C24 5.404 18.627 0 12 0S0 5.404 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.428c0-3.007 1.792-4.669 4.533-4.669 1.313 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" fill="#1877F2"/>
                    </svg>
                    Facebook
                </a>

                <!-- Apple -->
                <a href="#" class="auth-social-btn btn-apple">
                    <svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701" fill="#000000"/>
                    </svg>
                    Apple
                </a>

            </div>
        </div>

        <div class="auth-bottom-row">
            <span class="auth-bottom-text">
                No account? <a href="/diabetrack/public/auth/register">Register →</a>
            </span>
        </div>
    </div>

    <!-- RIGHT — Visual -->
    <div class="auth-right">
        <div class="auth-right-title">
            All-in-one<br><span>Diabetes Care.</span>
        </div>
        <div class="auth-right-sub">
            Monitor, track, and manage — all in one place.
        </div>

        <div class="auth-cards-wrap">

            <div class="auth-float-card card-peach">
                <div class="auth-card-icon">🩸</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Blood Sugar</div>
                    <div class="auth-card-sub">Before Meal · Just now</div>
                </div>
                <div class="auth-card-val">120 mg/dL</div>
                <div class="auth-card-dot normal"></div>
            </div>

            <div class="auth-float-card card-cream">
                <div class="auth-card-icon">💊</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Metformin 500mg</div>
                    <div class="auth-card-sub">Daily · 8:00 AM</div>
                </div>
                <div class="auth-card-val">✅ Taken</div>
            </div>

            <div class="auth-float-card card-glass">
                <div class="auth-card-icon">🥗</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Lunch — Sinangag</div>
                    <div class="auth-card-sub">Carbs · 12:30 PM</div>
                </div>
                <div class="auth-card-val">45g</div>
            </div>

            <div class="auth-float-card card-peach">
                <div class="auth-card-icon">🏃</div>
                <div class="auth-card-info">
                    <div class="auth-card-title">Morning Walk</div>
                    <div class="auth-card-sub">Light · 6:00 AM</div>
                </div>
                <div class="auth-card-val">30 min</div>
                <div class="auth-card-dot normal"></div>
            </div>

        </div>
    </div>

</div>

<script>
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