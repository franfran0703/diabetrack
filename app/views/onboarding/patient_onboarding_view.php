<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — Set Up Your Profile</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .ob-wrap         { max-width: 520px; width: 100%; margin: 0 auto; padding: 2rem 1.5rem; }
        .ob-brand        { display:flex; align-items:center; gap:10px; margin-bottom:2rem; text-decoration:none; }
        .ob-brand-pill   { background:#fff3ee; border-radius:12px; padding:6px; display:flex; align-items:center; justify-content:center; }
        .ob-brand-name   { font-family:'Cabinet Grotesk',sans-serif; font-weight:800; font-size:1.2rem; color:#1a0800; }
        .ob-steps        { display:flex; align-items:center; gap:0; margin-bottom:2.5rem; }
        .ob-step         { display:flex; align-items:center; gap:8px; flex:1; }
        .ob-step-dot     { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem; font-weight:700; flex-shrink:0; transition:all .3s; }
        .ob-step-dot.done    { background:#f97316; color:#fff; }
        .ob-step-dot.active  { background:#1a0800; color:#fff; box-shadow:0 0 0 4px rgba(249,115,22,.2); }
        .ob-step-dot.pending { background:#f0ece8; color:#a0714f; }
        .ob-step-label   { font-size:0.75rem; font-weight:600; color:#a0714f; white-space:nowrap; }
        .ob-step-label.active { color:#1a0800; }
        .ob-step-line    { flex:1; height:2px; background:#f0ece8; margin:0 8px; border-radius:2px; }
        .ob-step-line.done { background:#f97316; }
        .ob-card         { background:#fff; border-radius:20px; padding:2rem; box-shadow:0 4px 24px rgba(0,0,0,.06); }
        .ob-heading      { font-family:'Cabinet Grotesk',sans-serif; font-weight:800; font-size:1.6rem; color:#1a0800; margin-bottom:.35rem; }
        .ob-heading span { color:#f97316; }
        .ob-sub          { font-size:.85rem; color:#a0714f; margin-bottom:1.75rem; }
        .ob-field        { margin-bottom:1.2rem; }
        .ob-label        { display:block; font-size:.78rem; font-weight:700; color:#1a0800; margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.04em; }
        .ob-label span   { color:#f97316; }
        .ob-input        { width:100%; padding:.7rem 1rem; border:1.5px solid #f0ece8; border-radius:10px; font-size:.9rem; color:#1a0800; background:#fafaf9; transition:border-color .2s; box-sizing:border-box; }
        .ob-input:focus  { outline:none; border-color:#f97316; background:#fff; }
        .ob-select       { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%23a0714f' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 1rem center; padding-right:2.5rem; }
        .ob-2col         { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .ob-btn-row      { display:flex; justify-content:space-between; align-items:center; margin-top:2rem; }
        .ob-btn-primary  { background:#f97316; color:#fff; border:none; border-radius:10px; padding:.75rem 1.8rem; font-size:.9rem; font-weight:700; cursor:pointer; transition:background .2s; }
        .ob-btn-primary:hover { background:#ea6c10; }
        .ob-btn-skip     { background:none; border:none; color:#a0714f; font-size:.82rem; cursor:pointer; text-decoration:underline; padding:0; }
        .ob-panel        { display:none; }
        .ob-panel.active { display:block; }
        .ob-success-icon { font-size:3.5rem; text-align:center; margin-bottom:1rem; }
        .ob-success-text { text-align:center; }
        .ob-tip          { background:#fff7f3; border:1px solid #ffe4d5; border-radius:12px; padding:.9rem 1rem; margin-bottom:1.5rem; font-size:.8rem; color:#a0714f; display:flex; gap:.6rem; align-items:flex-start; }
        .ob-tip i        { color:#f97316; font-size:1rem; flex-shrink:0; margin-top:1px; }
        @media(max-width:540px) { .ob-2col { grid-template-columns:1fr; } .ob-wrap { padding:1.2rem 1rem; } }
    </style>
</head>
<body>
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>
<div class="auth-orb auth-orb-3"></div>

<div class="ob-wrap">

    <a href="/" class="ob-brand">
        <div class="ob-brand-pill">
            <img src="<?= BASE_URL ?>/assets/img/diabetrack-icon.png" style="width:28px;height:28px;object-fit:contain;">
        </div>
        <span class="ob-brand-name">DiabeTrack</span>
    </a>

    <!-- Step indicators -->
    <div class="ob-steps" id="ob-steps">
        <div class="ob-step">
            <div class="ob-step-dot active" id="dot-1">1</div>
            <span class="ob-step-label active" id="lbl-1">Health Details</span>
        </div>
        <div class="ob-step-line" id="line-1"></div>
        <div class="ob-step">
            <div class="ob-step-dot pending" id="dot-2">2</div>
            <span class="ob-step-label" id="lbl-2">All Set</span>
        </div>
    </div>

    <div class="ob-card">

        <!-- Step 1: Health Details -->
        <div class="ob-panel active" id="panel-1">
            <div class="ob-heading">Your health <span>profile.</span></div>
            <div class="ob-sub">Help us personalise your experience. You can update these anytime.</div>

            <div class="ob-tip">
                <i class="bi bi-info-circle-fill"></i>
                <span>Your diabetes type helps us set the right blood sugar targets for you.</span>
            </div>

            <div class="ob-2col">
                <div class="ob-field">
                    <label class="ob-label">Date of Birth</label>
                    <input class="ob-input" type="date" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="ob-field">
                    <label class="ob-label">Diabetes Type <span>*</span></label>
                    <select class="ob-input ob-select" id="diabetes_type" name="diabetes_type" required>
                        <option value="">— Select type —</option>
                        <option value="Type 1">Type 1</option>
                        <option value="Type 2">Type 2</option>
                        <option value="Gestational">Gestational</option>
                        <option value="Pre-diabetes">Pre-diabetes</option>
                    </select>
                </div>
            </div>

            <div class="ob-field">
                <label class="ob-label">Emergency Contact Name</label>
                <input class="ob-input" type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="e.g. Maria Santos">
            </div>

            <div class="ob-field">
                <label class="ob-label">Emergency Contact Number</label>
                <input class="ob-input" type="text" id="emergency_contact_number" name="emergency_contact_number" placeholder="e.g. 09171234567">
            </div>

            <div class="ob-btn-row">
                <button class="ob-btn-skip" type="button" onclick="skipOnboarding()">Skip for now</button>
                <button class="ob-btn-primary" type="button" onclick="submitStep(1)">
                    Continue <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Done -->
        <div class="ob-panel" id="panel-2">
            <div class="ob-success-icon">🎉</div>
            <div class="ob-success-text">
                <div class="ob-heading" style="justify-content:center;">You're all <span>set!</span></div>
                <div class="ob-sub">Your profile is ready. Let's start tracking your health.</div>
            </div>
            <div class="ob-btn-row" style="justify-content:center; margin-top:2rem;">
                <button class="ob-btn-primary" type="button" onclick="submitStep(2)">
                    Go to Dashboard <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
function submitStep(step) {
    if (step === 1) {
        const dtype = document.getElementById('diabetes_type').value;
        if (!dtype) {
            document.getElementById('diabetes_type').style.borderColor = '#ef4444';
            document.getElementById('diabetes_type').focus();
            return;
        }
    }

    const data = new FormData();
    data.append('step', step);

    if (step === 1) {
        data.append('date_of_birth',            document.getElementById('date_of_birth').value);
        data.append('diabetes_type',            document.getElementById('diabetes_type').value);
        data.append('emergency_contact_name',   document.getElementById('emergency_contact_name').value);
        data.append('emergency_contact_number', document.getElementById('emergency_contact_number').value);
    }

    fetch('/onboarding/index', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.redirect) {
                window.location.href = res.redirect;
            } else if (res.next) {
                goToStep(res.next);
            }
        });
}

function goToStep(n) {
    document.querySelectorAll('.ob-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + n).classList.add('active');

    // Update dots
    for (let i = 1; i <= 2; i++) {
        const dot   = document.getElementById('dot-' + i);
        const lbl   = document.getElementById('lbl-' + i);
        const line  = document.getElementById('line-' + i);
        if (i < n) {
            dot.className  = 'ob-step-dot done';
            dot.innerHTML  = '<i class="bi bi-check"></i>';
            lbl.className  = 'ob-step-label';
            if (line) line.classList.add('done');
        } else if (i === n) {
            dot.className  = 'ob-step-dot active';
            dot.textContent = i;
            lbl.className  = 'ob-step-label active';
        } else {
            dot.className  = 'ob-step-dot pending';
            dot.textContent = i;
            lbl.className  = 'ob-step-label';
        }
    }
}

function skipOnboarding() {
    window.location.href = '/onboarding/skip';
}
</script>
</body>
</html>