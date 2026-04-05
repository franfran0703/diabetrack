<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiabeTrack — All-In-One Diabetes Management</title>
    <link rel="stylesheet" href="/diabetrack/public/assets/css/landing.css?v=<?= time() ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────── -->
<nav class="nav" id="navbar">
    <a href="/diabetrack/public/" class="nav-brand">
        <div class="nav-brand-pill">🩺</div>
        <span class="nav-brand-name">DiabeTrack</span>
    </a>
    <div class="nav-links">
        <a href="#features" class="nav-link">Features</a>
        <a href="#how"      class="nav-link">How It Works</a>
        <a href="#users"    class="nav-link">Who It's For</a>
        <a href="/diabetrack/public/auth/login"    class="nav-link">Login</a>
        <a href="/diabetrack/public/auth/register" class="nav-cta">Get Started →</a>
    </div>
</nav>

<!-- ── HERO ──────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            Built for Filipino Diabetic Patients
        </div>
        <h1 class="hero-title">
            Manage Your<br>
            <span class="gradient-text">Diabetes</span><br>
            Smarter.
        </h1>
        <p class="hero-sub">
            DiabeTrack is your all-in-one companion for blood sugar, medication, meals, and activity — with real-time caregiver support.
        </p>
        <div class="hero-btns">
            <a href="/diabetrack/public/auth/register" class="btn-primary-hero">
                🚀 Get Started Free
            </a>
            <a href="#features" class="btn-secondary-hero">
                See Features →
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat-item">
                <div class="hero-stat-val">8+</div>
                <div class="hero-stat-label">Core Modules</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
                <div class="hero-stat-val">2</div>
                <div class="hero-stat-label">User Roles</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
                <div class="hero-stat-val">24/7</div>
                <div class="hero-stat-label">Health Tracking</div>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat-item">
                <div class="hero-stat-val">🇵🇭</div>
                <div class="hero-stat-label">Locally Relevant</div>
            </div>
        </div>
    </div>
</section>

<!-- ── FEATURES ───────────────────────────────────────── -->
<section class="section features-section" id="features">
    <div class="section-inner">
        <div class="reveal">
            <div class="section-eyebrow">Features</div>
            <h2 class="section-title">Everything you need,<br>all in one place.</h2>
            <p class="section-sub">From blood sugar logs to doctor reports — DiabeTrack covers every aspect of diabetes management.</p>
        </div>

        <div class="features-grid reveal">
            <div class="feature-card featured">
                <div class="feature-icon">🩸</div>
                <div class="feature-title">Blood Sugar Logger</div>
                <div class="feature-desc">Log readings before and after meals. Auto-flags Low, Normal, and High with instant caregiver alerts.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💊</div>
                <div class="feature-title">Medication Tracker</div>
                <div class="feature-desc">Set up daily schedules, log taken or missed doses, and receive push reminders.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🥗</div>
                <div class="feature-title">Meal & Carb Tracker</div>
                <div class="feature-desc">Log meals with carb estimates. Includes Filipino food database and carb limit warnings.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚶</div>
                <div class="feature-title">Activity Monitor</div>
                <div class="feature-desc">Track daily activities, duration, and intensity. See how movement affects your sugar levels.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👨‍👩‍👧</div>
                <div class="feature-title">Caregiver Portal</div>
                <div class="feature-desc">Real-time monitoring for family members. Get alerts for critical readings and missed doses.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🩺</div>
                <div class="feature-title">Doctor Reports</div>
                <div class="feature-desc">Auto-generated PDF health summaries covering 7-day or 30-day periods. Share directly to your doctor.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <div class="feature-title">Education Hub</div>
                <div class="feature-desc">Daily health tips, video guides, and myth vs fact articles about diabetes management.</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🗺️</div>
                <div class="feature-title">Nearby Services</div>
                <div class="feature-desc">Find hospitals, clinics, and pharmacies near you with filters and contact details.</div>
            </div>
        </div>
    </div>
</section>

<!-- ── HOW IT WORKS ────────────────────────────────────── -->
<section class="section how-section" id="how">
    <div class="section-inner">
        <div class="reveal">
            <div class="section-eyebrow">How It Works</div>
            <h2 class="section-title">Simple. Smart. Effective.</h2>
            <p class="section-sub">Getting started takes less than a minute.</p>
        </div>

        <div class="how-grid reveal">
            <div class="how-card">
                <span class="how-number">01</span>
                <span class="how-icon">📝</span>
                <div class="how-title">Create Your Account</div>
                <div class="how-desc">Register as a Patient or Caregiver. Set up your profile and link your caregiver for real-time monitoring.</div>
            </div>
            <div class="how-card">
                <span class="how-number">02</span>
                <span class="how-icon">📊</span>
                <div class="how-title">Log Your Health Data</div>
                <div class="how-desc">Track blood sugar, meals, medication, and activity daily. The system automatically flags abnormal readings.</div>
            </div>
            <div class="how-card">
                <span class="how-number">03</span>
                <span class="how-icon">📈</span>
                <div class="how-title">Monitor & Improve</div>
                <div class="how-desc">View trends, receive alerts, generate doctor reports, and make smarter health decisions every day.</div>
            </div>
        </div>
    </div>
</section>

<!-- ── WHO IT'S FOR ────────────────────────────────────── -->
<section class="section users-section" id="users">
    <div class="section-inner">
        <div class="reveal">
            <div class="section-eyebrow">Who It's For</div>
            <h2 class="section-title">Built for patients<br>and their families.</h2>
            <p class="section-sub">Two roles, one shared goal — better diabetes management.</p>
        </div>

        <div class="users-grid reveal">
            <!-- Patient Card -->
            <div class="user-role-card patient-card">
                <span class="user-role-icon">🧑‍⚕️</span>
                <div class="user-role-title">Patient</div>
                <div class="user-role-desc">
                    Take full control of your diabetes management with easy daily logging and smart health insights.
                </div>
                <ul class="user-role-features">
                    <li><span class="check-pill">✓</span> Log blood sugar readings</li>
                    <li><span class="check-pill">✓</span> Track medications & meals</li>
                    <li><span class="check-pill">✓</span> Monitor daily activity</li>
                    <li><span class="check-pill">✓</span> Generate doctor reports</li>
                    <li><span class="check-pill">✓</span> Access education hub</li>
                    <li><span class="check-pill">✓</span> Find nearby clinics</li>
                </ul>
            </div>

            <!-- Caregiver Card -->
            <div class="user-role-card caregiver-card">
                <span class="user-role-icon">👨‍👩‍👧</span>
                <div class="user-role-title">Caregiver</div>
                <div class="user-role-desc">
                    Stay informed and involved in your loved one's health with real-time monitoring and instant alerts.
                </div>
                <ul class="user-role-features">
                    <li><span class="check-pill">✓</span> Monitor patient readings</li>
                    <li><span class="check-pill">✓</span> Receive critical alerts</li>
                    <li><span class="check-pill">✓</span> View medication status</li>
                    <li><span class="check-pill">✓</span> Track patient activity</li>
                    <li><span class="check-pill">✓</span> Access health reports</li>
                    <li><span class="check-pill">✓</span> Manage multiple patients</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ── CTA ─────────────────────────────────────────────── -->
<section class="cta-section">
    <div class="section-inner">
        <div class="section-eyebrow" style="justify-content:center;">Start Today</div>
        <h2 class="section-title" style="color:#fff;margin-bottom:16px;">
            Your health journey<br>starts here.
        </h2>
        <p class="section-sub" style="margin:0 auto 40px;">
            Join DiabeTrack and take the first step towards smarter, safer diabetes management — for you and your family.
        </p>
        <a href="/diabetrack/public/auth/register" class="btn-cta-white">
            🚀 Create Free Account →
        </a>
    </div>
</section>

<!-- ── FOOTER ──────────────────────────────────────────── -->
<footer class="footer">
    <a href="/diabetrack/public/" class="footer-brand">
        <div class="nav-brand-pill">🩺</div>
        <span class="footer-brand-name">DiabeTrack</span>
    </a>
    <div class="footer-copy">© 2025 DiabeTrack. All-In-One Diabetes Management.</div>
    <div class="footer-links">
        <a href="#features">Features</a>
        <a href="#how">How It Works</a>
        <a href="/diabetrack/public/auth/login">Login</a>
        <a href="/diabetrack/public/auth/register">Register</a>
    </div>
</footer>

<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Scroll reveal
const reveals = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('visible'), i * 80);
        }
    });
}, { threshold: 0.1 });
reveals.forEach(r => observer.observe(r));
</script>
</body>
</html>