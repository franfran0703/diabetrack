<?php
$pageTitle  = 'Setup 2FA';
$activeMenu = 'profile';
ob_start();
?>

<style>
/* ═══════════════════════════════════════
   PATIENT 2FA SETUP — light warm theme
   matches patient_layout.css
═══════════════════════════════════════ */

.setup2fa-page {
    max-width: 860px;
    margin: 0 auto;
    padding: 8px 0 80px;
}

/* ── HEADER BANNER ── */
.setup2fa-banner {
    background: linear-gradient(135deg, #F97447 0%, #fb9261 50%, #fcc8ab 100%);
    border-radius: 28px;
    padding: 28px 36px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 36px rgba(249,116,71,0.28);
}
.setup2fa-banner::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    pointer-events: none;
}
.setup2fa-banner::after {
    content: '';
    position: absolute;
    right: 90px; bottom: -70px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
.setup2fa-banner-left { position: relative; z-index: 1; }
.setup2fa-banner-eyebrow {
    font-size: 0.63rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.7);
    margin-bottom: 8px;
    display: flex; align-items: center; gap: 8px;
}
.setup2fa-banner-eyebrow::before {
    content: '';
    display: inline-block;
    width: 20px; height: 2px;
    background: rgba(255,255,255,0.5);
    border-radius: 2px;
}
.setup2fa-banner-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900;
    font-size: 2rem;
    color: #fff;
    letter-spacing: -1.5px;
    line-height: 1.05;
    margin: 0 0 6px;
}
.setup2fa-banner-sub {
    font-family: 'Instrument Serif', serif;
    font-style: italic;
    font-size: 0.9rem;
    color: rgba(255,255,255,0.6);
    margin: 0;
}
.setup2fa-banner-illus {
    font-size: 3.5rem;
    position: relative; z-index: 1;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,0.15));
}

/* ── ERROR ── */
.setup2fa-error {
    display: flex; align-items: center; gap: 8px;
    background: rgba(220,38,38,0.06);
    border: 1.5px solid rgba(220,38,38,0.14);
    border-radius: 14px;
    padding: 12px 16px;
    font-size: 0.82rem; color: #b91c1c;
    font-weight: 600; margin-bottom: 14px;
}

/* ── MAIN GRID ── */
.setup2fa-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

/* ── CARD BASE (same as patient dashboard cards) ── */
.s2fa-card {
    background: #fff;
    border: 1.5px solid rgba(249,116,71,0.12);
    border-radius: 24px;
    padding: 26px 24px;
    box-shadow: 0 4px 14px rgba(168,56,24,0.07);
    position: relative;
    overflow: hidden;
}

/* ── STEPS CARD ── */
.s2fa-steps-card {}
.s2fa-section-label {
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #c4714a;
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 10px;
}
.s2fa-section-label::after {
    content: ''; flex: 1; height: 1.5px;
    background: rgba(249,116,71,0.12);
}
.step-row {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(249,116,71,0.07);
    margin-bottom: 18px;
}
.step-row:last-child { padding-bottom: 0; border-bottom: none; margin-bottom: 0; }
.step-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: #FDE8DC;
    border: 1.5px solid rgba(249,116,71,0.22);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.82rem;
    color: #c04a20;
    flex-shrink: 0; margin-top: 2px;
}
.step-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 800; font-size: 0.88rem;
    color: #1a0800; margin-bottom: 4px;
}
.step-desc {
    font-size: 0.76rem; color: #b8927e; line-height: 1.55;
}
.step-apps {
    display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;
}
.step-app-badge {
    font-size: 0.63rem; font-weight: 700;
    padding: 3px 10px; border-radius: 100px;
    background: #FDE8DC;
    border: 1px solid rgba(249,116,71,0.2);
    color: #c04a20;
}

/* ── QR CARD ── */
.s2fa-qr-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(145deg, #FFF8F5, #fdeee4);
}
.qr-eyebrow {
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #c4714a;
    margin-bottom: 14px;
}
.qr-frame {
    background: #fff;
    border-radius: 18px;
    padding: 14px;
    box-shadow: 0 8px 28px rgba(168,56,24,0.12),
                0 0 0 1.5px rgba(249,116,71,0.14);
    display: inline-block;
    margin-bottom: 14px;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: zoom-in;
}
.qr-frame:hover {
    transform: scale(1.04);
    box-shadow: 0 14px 40px rgba(168,56,24,0.18);
}
.qr-frame svg { display: block; width: 170px !important; height: 170px !important; }
.qr-manual-label {
    font-size: 0.66rem; color: #b8927e; margin-bottom: 7px;
}
.qr-secret {
    background: #fff;
    border: 1.5px solid rgba(249,116,71,0.18);
    border-radius: 12px;
    padding: 10px 14px;
    font-family: monospace;
    font-size: 0.78rem; color: #c04a20;
    letter-spacing: 2px; word-break: break-all;
    width: 100%; text-align: center;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.qr-secret:hover {
    background: #FDE8DC;
    border-color: rgba(249,116,71,0.35);
}
.qr-copy-hint { font-size: 0.6rem; color: #d4917a; margin-top: 5px; }

/* ── VERIFY STRIP ── */
.s2fa-verify-strip {
    background: #fff;
    border: 1.5px solid rgba(249,116,71,0.12);
    border-radius: 24px;
    padding: 26px 28px;
    box-shadow: 0 4px 14px rgba(168,56,24,0.07);
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 24px;
    align-items: center;
}
.verify-step-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.verify-step-num {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #FDE8DC;
    border: 2px solid rgba(249,116,71,0.22);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.88rem;
    color: #c04a20;
}
.verify-step-label {
    font-size: 0.55rem; font-weight: 800;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #d4917a;
}
.verify-center {}
.verify-title {
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 0.92rem;
    color: #1a0800; margin-bottom: 3px;
}
.verify-sub { font-size: 0.75rem; color: #b8927e; }
.verify-sub strong { color: #c04a20; font-weight: 700; }
.verify-right {
    display: flex; align-items: center; gap: 10px;
}
.code-inputs { display: flex; gap: 8px; }
.code-digit {
    width: 44px; height: 52px;
    border-radius: 12px;
    border: 1.5px solid rgba(249,116,71,0.2);
    background: #FFF8F5;
    font-family: 'Cabinet Grotesk', sans-serif;
    font-weight: 900; font-size: 1.3rem;
    text-align: center; color: #c04a20;
    outline: none;
    transition: all 0.15s;
}
.code-digit:focus {
    border-color: #F97447;
    background: #FDE8DC;
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
    background: linear-gradient(135deg, #e05c28, #F97447, #fb9261);
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
    font-size: 0.78rem; color: #b8927e;
    text-decoration: none; font-weight: 600;
    transition: color 0.15s;
    display: flex; align-items: center; gap: 5px;
}
.btn-cancel:hover { color: #F97447; }

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
        <div class="setup2fa-banner-left">
            <div class="setup2fa-banner-eyebrow">Account Security</div>
            <div class="setup2fa-banner-title">Enable Two-Factor<br>Authentication</div>
            <div class="setup2fa-banner-sub">Add an extra layer of protection to your health data.</div>
        </div>
        <div class="setup2fa-banner-illus">🔐</div>
    </div>

    <?php if ($error): ?>
    <div class="setup2fa-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/diabetrack/public/patient/setup2fa" id="setupForm">
        <input type="hidden" name="action" value="enable">
        <input type="hidden" name="secret" value="<?= htmlspecialchars($secret) ?>">
        <input type="hidden" name="code" id="fullCode">

        <!-- Steps + QR side by side -->
        <div class="setup2fa-grid">

            <!-- Steps -->
            <div class="s2fa-card s2fa-steps-card">
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
                        <div class="step-desc">In the app tap <strong style="color:#c04a20;">+</strong> or "Add Account", then point your camera at the QR code on the right.</div>
                    </div>
                </div>

                <div class="step-row">
                    <div class="step-num">3</div>
                    <div>
                        <div class="step-title">Enter the 6-Digit Code</div>
                        <div class="step-desc">Type the code shown in your app in the verify strip below. It refreshes every 30 seconds.</div>
                    </div>
                </div>
            </div>

            <!-- QR -->
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
            <div class="verify-center">
                <div class="verify-title">Enter the 6-digit code from your app</div>
                <div class="verify-sub">Type the <strong>current code</strong> shown in Google Authenticator or Authy to activate 2FA on your account.</div>
            </div>
            <div class="verify-right">
                <div class="code-inputs">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div class="setup2fa-actions">
            <a href="/diabetrack/public/patient/profile" class="btn-cancel">← Cancel</a>
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
        el.innerText = '✓ Copied to clipboard!';
        el.style.color = '#166534';
        el.style.background = '#d4f7e8';
        setTimeout(() => { el.innerText = orig; el.style.color = ''; el.style.background = ''; }, 1800);
    });
}
digits[0].focus();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>
