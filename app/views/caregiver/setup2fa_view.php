<?php
$pageTitle  = 'Setup 2FA';
$activeMenu = 'profile';
ob_start();
?>

<style>
/* ═══════════════════════════════════════
   CAREGIVER 2FA SETUP — dark warm theme
   matches caregiver_layout.css (#1C0F0A)
═══════════════════════════════════════ */

.setup2fa-page {
    max-width: 860px;
    margin: 0 auto;
    padding: 8px 0 80px;
}

/* ── HEADER BANNER (matches cgd-banner style) ── */
.setup2fa-banner {
    background: linear-gradient(135deg, #2e1008 0%, #3d1800 50%, #1c0f0a 100%);
    border: 1.5px solid rgba(249,116,71,0.2);
    border-radius: 28px;
    padding: 36px 44px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.04);
}
.setup2fa-banner-orb1 {
    position: absolute;
    width: 400px; height: 400px;
    top: -160px; right: -60px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(249,116,71,0.13) 0%, transparent 65%);
    pointer-events: none;
}
.setup2fa-banner-orb2 {
    position: absolute;
    width: 200px; height: 200px;
    bottom: -80px; left: 30%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(251,171,110,0.08) 0%, transparent 65%);
    pointer-events: none;
}
.setup2fa-banner-content { position: relative; z-index: 2; }
.setup2fa-banner-eyebrow {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.63rem; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    color: rgba(255,180,130,0.45);
    margin-bottom: 14px;
}
.cgd-pulse-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #f97447;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.35; transform:scale(0.65); }
}
.setup2fa-banner-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 2.2rem;
    color: #ffe8d6; letter-spacing: -2px;
    line-height: 1.05; margin: 0 0 8px;
}
.setup2fa-banner-title .grad {
    background: linear-gradient(120deg, #f97447 0%, #fbab6e 50%, #fdd5be 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.setup2fa-banner-sub {
    font-family: 'Instrument Serif', serif;
    font-style: italic;
    font-size: 0.9rem;
    color: rgba(255,200,160,0.38);
}
.setup2fa-banner-illus {
    font-size: 4rem; position: relative; z-index: 2;
    filter: drop-shadow(0 4px 16px rgba(249,116,71,0.35));
}

/* ── ERROR ── */
.setup2fa-error {
    display: flex; align-items: center; gap: 8px;
    background: rgba(220,38,38,0.08);
    border: 1.5px solid rgba(220,38,38,0.18);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 0.82rem; color: #f87171;
    font-weight: 600; margin-bottom: 14px;
}

/* ── MAIN GRID ── */
.setup2fa-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

/* ── CARD BASE (matches caregiver glass cards) ── */
.s2fa-card {
    background: rgba(255,255,255,0.04);
    border: 1.5px solid rgba(255,255,255,0.07);
    border-radius: 24px;
    padding: 26px 24px;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
}

/* ── STEPS CARD ── */
.s2fa-section-label {
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(255,180,130,0.4);
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.s2fa-section-label::after {
    content: ''; flex: 1; height: 1px;
    background: rgba(255,255,255,0.06);
}
.step-row {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 18px;
}
.step-row:last-child { padding-bottom: 0; border-bottom: none; margin-bottom: 0; }
.step-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(249,116,71,0.12);
    border: 1.5px solid rgba(249,116,71,0.28);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.82rem;
    color: #fbab6e;
    flex-shrink: 0; margin-top: 2px;
}
.step-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 800; font-size: 0.88rem;
    color: #ffe8d6; margin-bottom: 4px;
}
.step-desc {
    font-size: 0.76rem; color: rgba(255,200,160,0.4); line-height: 1.55;
}
.step-apps {
    display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;
}
.step-app-badge {
    font-size: 0.63rem; font-weight: 700;
    padding: 3px 10px; border-radius: 100px;
    background: rgba(249,116,71,0.1);
    border: 1px solid rgba(249,116,71,0.2);
    color: #fbab6e;
}

/* ── QR CARD ── */
.s2fa-qr-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: rgba(249,116,71,0.04);
    border-color: rgba(249,116,71,0.14);
}
.qr-eyebrow {
    font-size: 0.58rem; font-weight: 800;
    letter-spacing: 3px; text-transform: uppercase;
    color: rgba(255,180,130,0.4);
    margin-bottom: 14px;
}
.qr-frame {
    background: #fff;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4),
                0 0 0 1.5px rgba(249,116,71,0.15);
    display: inline-block;
    margin-bottom: 14px;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: zoom-in;
}
.qr-frame:hover {
    transform: scale(1.04);
    box-shadow: 0 14px 48px rgba(0,0,0,0.5), 0 0 0 2px rgba(249,116,71,0.3);
}
.qr-frame svg { display: block; width: 170px !important; height: 170px !important; }
.qr-manual-label {
    font-size: 0.66rem; color: rgba(255,200,160,0.3); margin-bottom: 7px;
}
.qr-secret {
    background: rgba(255,255,255,0.04);
    border: 1.5px solid rgba(249,116,71,0.15);
    border-radius: 12px;
    padding: 10px 14px;
    font-family: monospace;
    font-size: 0.78rem; color: #fbab6e;
    letter-spacing: 2px; word-break: break-all;
    width: 100%; text-align: center;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.qr-secret:hover {
    background: rgba(249,116,71,0.09);
    border-color: rgba(249,116,71,0.3);
}
.qr-copy-hint { font-size: 0.6rem; color: rgba(255,180,130,0.25); margin-top: 5px; }

/* ── VERIFY STRIP ── */
.s2fa-verify-strip {
    background: rgba(255,255,255,0.04);
    border: 1.5px solid rgba(249,116,71,0.15);
    border-radius: 24px;
    padding: 26px 28px;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 24px;
    align-items: center;
}
.verify-step-badge {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.verify-step-num {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: rgba(249,116,71,0.12);
    border: 2px solid rgba(249,116,71,0.28);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.88rem; color: #fbab6e;
}
.verify-step-label {
    font-size: 0.55rem; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: rgba(255,180,130,0.35);
}
.verify-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.92rem;
    color: #ffe8d6; margin-bottom: 4px;
}
.verify-sub { font-size: 0.75rem; color: rgba(255,200,160,0.38); line-height: 1.5; }
.verify-sub strong { color: #fbab6e; font-weight: 700; }

.code-inputs { display: flex; gap: 8px; }
.code-digit {
    width: 44px; height: 52px;
    border-radius: 12px;
    border: 1.5px solid rgba(249,116,71,0.2);
    background: rgba(255,255,255,0.04);
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 1.3rem;
    text-align: center; color: #fbab6e;
    outline: none;
    transition: all 0.15s;
}
.code-digit:focus {
    border-color: #f97447;
    background: rgba(249,116,71,0.09);
    box-shadow: 0 0 0 3px rgba(249,116,71,0.12);
    transform: translateY(-2px);
}

/* ── ACTIONS ── */
.setup2fa-actions {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 16px;
}
.btn-activate {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 30px;
    background: linear-gradient(135deg, #e05c28, #F97447, #fbab6e);
    color: #fff; border: none; border-radius: 100px;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-size: 0.88rem; font-weight: 800;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(249,116,71,0.4);
    transition: all 0.2s cubic-bezier(.34,1.56,.64,1);
}
.btn-activate:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(249,116,71,0.55);
}
.btn-cancel {
    font-size: 0.78rem; color: rgba(255,200,160,0.3);
    text-decoration: none; font-weight: 600;
    transition: color 0.15s;
    display: flex; align-items: center; gap: 5px;
}
.btn-cancel:hover { color: #fbab6e; }

@media (max-width: 640px) {
    .setup2fa-grid { grid-template-columns: 1fr; }
    .s2fa-verify-strip { grid-template-columns: 1fr; }
    .setup2fa-actions { flex-direction: column-reverse; gap: 12px; align-items: stretch; }
    .btn-activate { justify-content: center; }
    .btn-cancel { justify-content: center; }
}
</style>

<div class="setup2fa-page">

    <!-- Banner -->
    <div class="setup2fa-banner">
        <div class="setup2fa-banner-orb1"></div>
        <div class="setup2fa-banner-orb2"></div>
        <div class="setup2fa-banner-content">
            <div class="setup2fa-banner-eyebrow">
                <span class="cgd-pulse-dot"></span> Account Security
            </div>
            <div class="setup2fa-banner-title">
                Secure Your<br><span class="grad">Caregiver Access</span>
            </div>
            <div class="setup2fa-banner-sub">Protect the patient data you manage with 2-step verification.</div>
        </div>
        <div class="setup2fa-banner-illus">🔐</div>
    </div>

    <?php if ($error): ?>
    <div class="setup2fa-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/caregiver/setup2fa" id="setupForm">
        <input type="hidden" name="action" value="enable">
        <input type="hidden" name="secret" value="<?= htmlspecialchars($secret) ?>">
        <input type="hidden" name="code" id="fullCode">

        <!-- Steps + QR -->
        <div class="setup2fa-grid">

            <div class="s2fa-card">
                <div class="s2fa-section-label">Setup Guide</div>

                <div class="step-row">
                    <div class="step-num">1</div>
                    <div>
                        <div class="step-title">Get an Authenticator App</div>
                        <div class="step-desc">Download any TOTP app on your phone.</div>
                        <div class="step-apps">
                            <span class="step-app-badge">Google Authenticator</span>
                            <span class="step-app-badge">Authy</span>
                            <span class="step-app-badge">Microsoft Auth</span>
                        </div>
                    </div>
                </div>

                <div class="step-row">
                    <div class="step-num">2</div>
                    <div>
                        <div class="step-title">Scan the QR Code</div>
                        <div class="step-desc">In the app tap <strong style="color:#fbab6e;">+</strong> or "Add Account", then scan the QR code on the right.</div>
                    </div>
                </div>

                <div class="step-row">
                    <div class="step-num">3</div>
                    <div>
                        <div class="step-title">Enter the 6-Digit Code</div>
                        <div class="step-desc">Type the code shown in your app in the verify strip below. Refreshes every 30 seconds.</div>
                    </div>
                </div>
            </div>

            <div class="s2fa-card s2fa-qr-card">
                <div class="qr-eyebrow">Scan to Link Your App</div>
                <div class="qr-frame">
                    <?= $qrSvg ?>
                </div>
                <div class="qr-manual-label">Can't scan? Copy the key below</div>
                <div class="qr-secret" onclick="copySecret(this)" title="Click to copy">
                    <?= chunk_split(htmlspecialchars($secret), 4, ' ') ?>
                </div>
                <div class="qr-copy-hint">click to copy</div>
            </div>

        </div>

        <!-- Verify strip -->
        <div class="s2fa-verify-strip">
            <div class="verify-step-badge">
                <div class="verify-step-num">3</div>
                <div class="verify-step-label">Verify</div>
            </div>
            <div>
                <div class="verify-title">Enter the 6-digit code from your app</div>
                <div class="verify-sub">Type the <strong>current code</strong> shown in your authenticator to confirm and activate 2FA.</div>
            </div>
            <div class="code-inputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <?php endfor; ?>
            </div>
        </div>

        <div class="setup2fa-actions">
            <a href="<?= BASE_URL ?>/caregiver/profile" class="btn-cancel">← Cancel</a>
            <button type="submit" class="btn-activate">🔐 Activate 2FA</button>
        </div>

    </form>
</div>

<script>
const digits = document.querySelectorAll('.code-digit');
const form   = document.getElementById('setupForm');
const full   = document.getElementById('fullCode');

digits.forEach((d, i) => {
    d.addEventListener('input', () => {
        d.value = d.value.replace(/[^0-9]/g, '');
        if (d.value && i < 5) digits[i + 1].focus();
    });
    d.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !d.value && i > 0) digits[i - 1].focus();
    });
    d.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...text].slice(0, 6).forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
        digits[Math.min(text.length, 5)].focus();
    });
});
form.addEventListener('submit', () => { full.value = [...digits].map(d => d.value).join(''); });

function copySecret(el) {
    navigator.clipboard.writeText(el.innerText.replace(/\s/g, '')).then(() => {
        const orig = el.innerText;
        el.innerText = '✓ Copied!';
        el.style.color = '#4ade80';
        setTimeout(() => { el.innerText = orig; el.style.color = ''; }, 1800);
    });
}
digits[0].focus();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>