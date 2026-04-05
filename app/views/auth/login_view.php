<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — Login</title>
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
        <div class="auth-left-eyebrow">Welcome Back</div>
        <h2 class="auth-left-title">Your health,<br>your journey.</h2>
        <p class="auth-left-sub">
            Sign in to continue tracking your blood sugar, medications, meals, and more.
        </p>
        <div class="auth-features">
            <div class="auth-feature-item">
                <div class="auth-feature-icon">🩸</div>
                <div class="auth-feature-text">Track blood sugar in real time</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">💊</div>
                <div class="auth-feature-text">Never miss a medication dose</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">👨‍👩‍👧</div>
                <div class="auth-feature-text">Keep your caregiver informed</div>
            </div>
            <div class="auth-feature-item">
                <div class="auth-feature-icon">🩺</div>
                <div class="auth-feature-text">Generate instant doctor reports</div>
            </div>
        </div>
    </div>

    <div class="auth-left-footer">© 2025 DiabeTrack. All rights reserved.</div>
</div>

<!-- RIGHT PANEL -->
<div class="auth-right">
    <div class="auth-form-wrap">

        <div class="auth-form-title">Sign In</div>
        <div class="auth-form-sub">Enter your credentials to access your dashboard.</div>

        <?php if (isset($error)): ?>
        <div class="auth-alert error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/diabetrack/public/auth/login">

            <div class="auth-field">
                <label class="auth-label">Email Address</label>
                <div class="auth-input-wrap">
                    <input
                        type="email"
                        name="email"
                        class="auth-input"
                        placeholder="you@example.com"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label">Password</label>
                <div class="auth-input-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="auth-input"
                        placeholder="Enter your password"
                        required
                        style="padding-right: 42px;"
                    >
                    <span class="auth-input-icon" onclick="togglePass('password', this)">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="auth-submit">Sign In →</button>
        </form>

        <div class="auth-divider">or</div>

        <div class="auth-bottom">
            Don't have an account?
            <a href="/diabetrack/public/auth/register">Create one here →</a>
        </div>
        <div class="auth-bottom" style="margin-top:10px;">
            <a href="/diabetrack/public/" style="color:#b8927e;font-weight:600;">← Back to home</a>
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