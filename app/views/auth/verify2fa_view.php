<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — Two-Factor Verification</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .twofa-icon-ring {
            width: 68px; height: 68px;
            border-radius: 50%;
            background: rgba(249,116,71,0.10);
            border: 2px solid rgba(249,116,71,0.22);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 22px;
            animation: fadeUp 0.5s cubic-bezier(.22,1,.36,1) both 0.15s;
        }
        .twofa-verify-title {
            font-family: 'Cabinet Grotesk', sans-serif;
            font-weight: 900;
            font-size: clamp(1.6rem, 2.5vw, 2rem);
            color: var(--ink);
            letter-spacing: -1.5px;
            margin-bottom: 6px;
            text-align: center;
            line-height: 1.1;
            animation: fadeUp 0.5s cubic-bezier(.22,1,.36,1) both 0.2s;
        }
        .twofa-verify-sub {
            font-size: 0.84rem;
            color: var(--muted);
            margin-bottom: 32px;
            line-height: 1.6;
            text-align: center;
            font-style: italic;
            animation: fadeUp 0.5s cubic-bezier(.22,1,.36,1) both 0.25s;
        }
        .twofa-code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 0 0 20px;
            animation: fadeUp 0.5s cubic-bezier(.22,1,.36,1) both 0.3s;
        }
        .twofa-code-digit {
            width: 50px; height: 58px;
            border-radius: 14px;
            border: 1.5px solid rgba(249,116,71,0.18);
            background: rgba(255,255,255,0.9);
            font-family: 'Cabinet Grotesk', sans-serif;
            font-weight: 900;
            font-size: 1.4rem;
            text-align: center;
            color: var(--ink);
            outline: none;
            transition: all 0.18s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), inset 0 1px 2px rgba(255,255,255,0.8);
        }
        .twofa-code-digit:focus {
            border-color: var(--coral);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(249,116,71,0.11), 0 2px 10px rgba(249,116,71,0.09);
            transform: translateY(-2px);
        }
        .twofa-code-digit.filled {
            border-color: rgba(249,116,71,0.4);
            background: rgba(249,116,71,0.04);
        }
        .twofa-error {
            background: rgba(220,38,38,0.06);
            border: 1.5px solid rgba(220,38,38,0.15);
            border-radius: 14px;
            padding: 11px 16px;
            font-size: 0.82rem;
            color: #b91c1c;
            font-weight: 600;
            margin-bottom: 18px;
            text-align: center;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
        .twofa-back-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 0.78rem;
            color: var(--muted-lt);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.15s;
        }
        .twofa-back-link:hover { color: var(--coral); }

        /* Right panel 2FA hint cards */
        .twofa-hint-wrap {
            display: flex; flex-direction: column; gap: 14px;
            width: 100%; position: relative; z-index: 1;
        }
        .twofa-hint-card {
            border-radius: 18px; padding: 16px 18px;
            display: flex; align-items: center; gap: 14px;
            animation: cardSlideIn 0.55s cubic-bezier(.22,1,.36,1) both;
        }
        .twofa-hint-card:nth-child(1) { animation-delay: 0.30s; margin-right: 24px; background: linear-gradient(145deg, #fdeee4, #fddcc8); border: 1.5px solid rgba(249,116,71,0.20); box-shadow: inset 0 1px 0 rgba(255,255,255,0.65), 0 10px 32px rgba(0,0,0,0.28); }
        .twofa-hint-card:nth-child(2) { animation-delay: 0.42s; margin-left: 18px; background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.11); backdrop-filter: blur(18px); box-shadow: inset 0 1px 0 rgba(255,255,255,0.10), 0 10px 28px rgba(0,0,0,0.24); }
        .twofa-hint-card:nth-child(3) { animation-delay: 0.54s; margin-right: 18px; background: linear-gradient(145deg, #fff5ef, #fde8dc); border: 1.5px solid rgba(249,116,71,0.14); box-shadow: inset 0 1px 0 rgba(255,255,255,0.75), 0 10px 28px rgba(0,0,0,0.22); }
        .twofa-hint-icon {
            width: 44px; height: 44px; border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .twofa-hint-card:nth-child(1) .twofa-hint-icon,
        .twofa-hint-card:nth-child(3) .twofa-hint-icon { background: rgba(249,116,71,0.13); border: 1.5px solid rgba(249,116,71,0.18); }
        .twofa-hint-card:nth-child(2) .twofa-hint-icon { background: rgba(255,255,255,0.09); border: 1.5px solid rgba(255,255,255,0.13); }
        .twofa-hint-body { flex: 1; }
        .twofa-hint-title {
            font-family: 'Cabinet Grotesk', sans-serif;
            font-weight: 800; font-size: 0.85rem; margin-bottom: 3px;
        }
        .twofa-hint-card:nth-child(1) .twofa-hint-title,
        .twofa-hint-card:nth-child(3) .twofa-hint-title { color: var(--ink); }
        .twofa-hint-card:nth-child(2) .twofa-hint-title { color: #ffe8d6; }
        .twofa-hint-sub { font-size: 0.72rem; font-weight: 500; }
        .twofa-hint-card:nth-child(1) .twofa-hint-sub,
        .twofa-hint-card:nth-child(3) .twofa-hint-sub { color: var(--muted-lt); }
        .twofa-hint-card:nth-child(2) .twofa-hint-sub { color: rgba(255,200,160,0.48); }
    </style>
</head>
<body>

<a href="/" class="auth-home-float">
    <span class="home-arrow">←</span> Home
</a>

<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>
<div class="auth-orb auth-orb-3"></div>

<div class="auth-shell">

    <!-- LEFT — Form -->
    <div class="auth-left">
        <div style="position:relative;z-index:1;">
            <a href="/" class="auth-brand">
                <div class="auth-brand-pill">
                    <img src="<?= BASE_URL ?>/assets/img/diabetrack-icon.png" style="width:32px;height:32px;object-fit:contain;">
                </div>
                <span class="auth-brand-name">DiabeTrack</span>
            </a>

            <div class="twofa-icon-ring">🔐</div>

            <div class="twofa-verify-title">Two-Factor<br>Verification</div>
            <div class="twofa-verify-sub">Open your authenticator app and enter<br>the 6-digit code to continue.</div>

            <?php if (!empty($error)): ?>
            <div class="twofa-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/auth/verify2fa" id="verifyForm">
                <input type="hidden" name="code" id="fullCode">

                <div class="twofa-code-inputs">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" class="twofa-code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                    <?php endfor; ?>
                </div>

                <button type="submit" class="auth-submit">Verify & Sign In →</button>
            </form>

            <a href="<?= BASE_URL ?>/auth/login" class="twofa-back-link">← Back to Login</a>
        </div>

        <div class="auth-bottom-row">
            <span class="auth-bottom-text">Need help? <a href="#">Contact Support</a></span>
        </div>
    </div>

    <!-- RIGHT — Visual -->
    <div class="auth-right">
        <div class="auth-right-title">
            Keep your<br><span>account safe.</span>
        </div>
        <div class="auth-right-sub">
            Two-factor authentication adds an extra<br>layer of security to your health data.
        </div>

        <div class="twofa-hint-wrap">
            <div class="twofa-hint-card">
                <div class="twofa-hint-icon">📱</div>
                <div class="twofa-hint-body">
                    <div class="twofa-hint-title">Open Authenticator App</div>
                    <div class="twofa-hint-sub">Google Authenticator · Authy · any TOTP app</div>
                </div>
            </div>
            <div class="twofa-hint-card">
                <div class="twofa-hint-icon">🔑</div>
                <div class="twofa-hint-body">
                    <div class="twofa-hint-title">Find DiabeTrack Code</div>
                    <div class="twofa-hint-sub">6-digit code · refreshes every 30 seconds</div>
                </div>
            </div>
            <div class="twofa-hint-card">
                <div class="twofa-hint-icon">✅</div>
                <div class="twofa-hint-body">
                    <div class="twofa-hint-title">Enter & Verify</div>
                    <div class="twofa-hint-sub">Type the code on the left to sign in</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const digits = document.querySelectorAll('.twofa-code-digit');
const form   = document.getElementById('verifyForm');
const full   = document.getElementById('fullCode');

digits.forEach((d, i) => {
    d.addEventListener('input', () => {
        d.value = d.value.replace(/[^0-9]/g, '');
        d.classList.toggle('filled', d.value !== '');
        if (d.value && i < 5) digits[i + 1].focus();
    });
    d.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !d.value && i > 0) {
            digits[i - 1].focus();
            digits[i - 1].classList.remove('filled');
        }
    });
    d.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...text].slice(0, 6).forEach((ch, j) => {
            if (digits[j]) { digits[j].value = ch; digits[j].classList.add('filled'); }
        });
        digits[Math.min(text.length, 5)].focus();
    });
});

form.addEventListener('submit', e => {
    const code = [...digits].map(d => d.value).join('');
    if (code.length < 6) {
        e.preventDefault();
        digits[code.length < 6 ? code.length : 5]?.focus();
        return;
    }
    full.value = code;
});

digits[0].focus();
</script>
</body>
</html>