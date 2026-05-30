<?php
$pageTitle  = 'Education Hub';
$activeMenu = 'education';
ob_start();

/* ── Category meta — icon, color, bg, count ─────────── */
$categories = [
    'nutrition'  => ['ti-salad',              '#0f7a45', '#d4f7e8', 3],
    'medication' => ['ti-pill',               '#c04a20', '#FDE8DC', 2],
    'exercise'   => ['ti-run',                '#d97706', '#fef3c7', 2],
    'monitoring' => ['ti-droplet-half-2',     '#6d28d9', '#ede9fe', 2],
    'lifestyle'  => ['ti-leaf',               '#0e7490', '#cffafe', 3],
];

/* ── Article registry ────────────────────────────────── */
$articleMeta = [
    'featured'       => ['cat' => 'monitoring', 'mins' => 5,  'level' => 'Beginner'],
    'carb-counting'  => ['cat' => 'nutrition',  'mins' => 4,  'level' => 'Beginner'],
    'glycemic-index' => ['cat' => 'nutrition',  'mins' => 6,  'level' => 'Intermediate'],
    'plate-method'   => ['cat' => 'nutrition',  'mins' => 5,  'level' => 'Beginner'],
    'insulin-types'  => ['cat' => 'medication', 'mins' => 7,  'level' => 'Intermediate'],
    'oral-meds'      => ['cat' => 'medication', 'mins' => 8,  'level' => 'Intermediate'],
    'exercise-sugar' => ['cat' => 'exercise',   'mins' => 5,  'level' => 'Beginner'],
    'walking-plan'   => ['cat' => 'exercise',   'mins' => 3,  'level' => 'Beginner'],
    'when-to-check'  => ['cat' => 'monitoring', 'mins' => 4,  'level' => 'Beginner'],
    'hba1c'          => ['cat' => 'monitoring', 'mins' => 5,  'level' => 'Intermediate'],
    'stress-sugar'   => ['cat' => 'lifestyle',  'mins' => 5,  'level' => 'Beginner'],
    'sleep-diabetes' => ['cat' => 'lifestyle',  'mins' => 4,  'level' => 'Beginner'],
    'alcohol'        => ['cat' => 'lifestyle',  'mins' => 4,  'level' => 'Intermediate'],
];

$totalArticles = count($articleMeta) - 1; /* exclude featured */
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="<?= BASE_URL ?>/assets/css/education.css?v=<?= time() ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="edu-page-header">
    <div class="edu-page-header-left">
        <div class="edu-page-eyebrow">
            <i class="ti ti-books"></i> Resources
        </div>
        <h1 class="edu-page-title">Education <span>Hub</span></h1>
        <p class="edu-page-sub">Guides, tips & articles to help you manage diabetes better.</p>
    </div>
    <div class="edu-page-header-right">
        <div class="edu-article-count-badge">
            <i class="ti ti-article"></i>
            <?= $totalArticles ?> articles
        </div>
        <div class="edu-search-wrap">
            <i class="ti ti-search edu-search-icon"></i>
            <input type="text" class="edu-search" id="eduSearch" placeholder="Search articles…" autocomplete="off">
            <button class="edu-search-clear" id="eduSearchClear" aria-label="Clear search">
                <i class="ti ti-x"></i>
            </button>
        </div>
    </div>
</div>


<!-- ══ CATEGORY TABS ════════════════════════════════════ -->
<div class="edu-tabs" id="eduTabs" role="tablist">
    <button class="edu-tab active" data-filter="all" role="tab" aria-selected="true">
        <i class="ti ti-layout-grid"></i>
        All Topics
        <span class="edu-tab-count"><?= $totalArticles ?></span>
    </button>
    <?php foreach ($categories as $key => [$icon, $color, $bg, $count]): ?>
    <button class="edu-tab" data-filter="<?= $key ?>" role="tab"
            style="--tab-color:<?= $color ?>;--tab-bg:<?= $bg ?>;">
        <i class="ti <?= $icon ?>"></i>
        <?= ucfirst($key) ?>
        <span class="edu-tab-count"><?= $count ?></span>
    </button>
    <?php endforeach; ?>
</div>


<!-- ══ FEATURED ARTICLE ═══════════════════════════════ -->
<div class="edu-featured" data-category="monitoring">
    <div class="edu-featured-badge">
        <i class="ti ti-star-filled"></i> Featured
    </div>
    <div class="edu-featured-content">
        <div class="edu-featured-tag">
            <i class="ti ti-droplet-half-2"></i> Blood Sugar Monitoring
        </div>
        <h2 class="edu-featured-title">Understanding Your Blood Glucose Numbers</h2>
        <p class="edu-featured-desc">Learn what your blood sugar readings mean, when to measure, and how to use the data to make better daily decisions about food, exercise, and medication.</p>
        <div class="edu-featured-meta">
            <span><i class="ti ti-clock"></i> 5 min read</span>
            <span class="edu-feat-dot"></span>
            <span><i class="ti ti-award"></i> Beginner Friendly</span>
            <span class="edu-feat-dot"></span>
            <span><i class="ti ti-eye"></i> Most Read</span>
        </div>
        <button class="edu-featured-btn" onclick="openArticle('featured')">
            Read Article <i class="ti ti-arrow-right"></i>
        </button>
    </div>
    <div class="edu-featured-illus" aria-hidden="true">
        <div class="edu-illus-ring">
            <div class="edu-illus-inner">
                <div class="edu-illus-val">180</div>
                <div class="edu-illus-unit">mg/dL</div>
                <div class="edu-illus-label">Target</div>
            </div>
        </div>
        <!-- Decorative ECG line -->
        <svg class="edu-illus-ecg" viewBox="0 0 200 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <polyline points="0,20 30,20 40,20 50,4 60,36 70,20 90,20 100,20 110,8 120,20 140,20 170,20 200,20"
                      fill="none" stroke="rgba(249,116,71,0.5)" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
</div>


<!-- ══ ARTICLE GRID ═══════════════════════════════════ -->
<div class="edu-grid-header">
    <div class="edu-section-label">
        <i class="ti ti-layout-cards"></i> All Articles
    </div>
    <div class="edu-grid-sort">
        <span id="eduResultCount"><?= $totalArticles ?> articles</span>
    </div>
</div>

<div class="edu-grid" id="eduGrid">

    <!-- NUTRITION ─── -->
    <div class="edu-card" data-category="nutrition" data-key="carb-counting"
         data-title="carb counting" style="--cat-color:#0f7a45;--cat-bg:#d4f7e8;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-salad"></i></div>
            <span class="edu-card-tag">Nutrition</span>
        </div>
        <h3 class="edu-card-title">Carb Counting 101</h3>
        <p class="edu-card-desc">Master the basics of counting carbohydrates to better manage post-meal blood sugar spikes.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 4 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('carb-counting')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:40%;"></div>
    </div>

    <div class="edu-card" data-category="nutrition" data-key="glycemic-index"
         data-title="glycemic index foods" style="--cat-color:#0f7a45;--cat-bg:#d4f7e8;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-chart-bar"></i></div>
            <span class="edu-card-tag">Nutrition</span>
        </div>
        <h3 class="edu-card-title">The Glycemic Index Guide</h3>
        <p class="edu-card-desc">Discover which foods raise blood sugar slowly vs. quickly, and how to build a low-GI meal plan.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 6 min</span>
                <span class="edu-level-badge edu-level--intermediate">Intermediate</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('glycemic-index')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:60%;"></div>
    </div>

    <div class="edu-card" data-category="nutrition" data-key="plate-method"
         data-title="meal planning diabetes plate" style="--cat-color:#0f7a45;--cat-bg:#d4f7e8;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-tools-kitchen-2"></i></div>
            <span class="edu-card-tag">Nutrition</span>
        </div>
        <h3 class="edu-card-title">Building a Diabetes-Friendly Plate</h3>
        <p class="edu-card-desc">Use the "Plate Method" to create balanced meals that keep your glucose levels steady throughout the day.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 5 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('plate-method')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:50%;"></div>
    </div>

    <!-- MEDICATION ─── -->
    <div class="edu-card" data-category="medication" data-key="insulin-types"
         data-title="insulin types medication" style="--cat-color:#c04a20;--cat-bg:#FDE8DC;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-vaccine"></i></div>
            <span class="edu-card-tag">Medication</span>
        </div>
        <h3 class="edu-card-title">Types of Insulin Explained</h3>
        <p class="edu-card-desc">Rapid-acting, long-acting, mixed — learn the differences between insulin types and when each is used.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 7 min</span>
                <span class="edu-level-badge edu-level--intermediate">Intermediate</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('insulin-types')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:70%;"></div>
    </div>

    <div class="edu-card" data-category="medication" data-key="oral-meds"
         data-title="oral medication metformin" style="--cat-color:#c04a20;--cat-bg:#FDE8DC;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-pill"></i></div>
            <span class="edu-card-tag">Medication</span>
        </div>
        <h3 class="edu-card-title">Common Oral Diabetes Medications</h3>
        <p class="edu-card-desc">An overview of Metformin, Sulfonylureas, DPP-4 inhibitors, and what to expect from each class.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 8 min</span>
                <span class="edu-level-badge edu-level--intermediate">Intermediate</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('oral-meds')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:80%;"></div>
    </div>

    <!-- EXERCISE ─── -->
    <div class="edu-card" data-category="exercise" data-key="exercise-sugar"
         data-title="exercise blood sugar benefits" style="--cat-color:#d97706;--cat-bg:#fef3c7;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-run"></i></div>
            <span class="edu-card-tag">Exercise</span>
        </div>
        <h3 class="edu-card-title">How Exercise Lowers Blood Sugar</h3>
        <p class="edu-card-desc">Understand the science behind physical activity and glucose uptake — and how to exercise safely with diabetes.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 5 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('exercise-sugar')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:50%;"></div>
    </div>

    <div class="edu-card" data-category="exercise" data-key="walking-plan"
         data-title="walking workout routine" style="--cat-color:#d97706;--cat-bg:#fef3c7;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-walk"></i></div>
            <span class="edu-card-tag">Exercise</span>
        </div>
        <h3 class="edu-card-title">The 30-Minute Walking Plan</h3>
        <p class="edu-card-desc">A beginner-friendly weekly walking schedule proven to improve insulin sensitivity and cardiovascular health.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 3 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('walking-plan')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:30%;"></div>
    </div>

    <!-- MONITORING ─── -->
    <div class="edu-card" data-category="monitoring" data-key="when-to-check"
         data-title="when to check blood sugar testing" style="--cat-color:#6d28d9;--cat-bg:#ede9fe;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-device-watch-stats"></i></div>
            <span class="edu-card-tag">Monitoring</span>
        </div>
        <h3 class="edu-card-title">When & How Often to Check</h3>
        <p class="edu-card-desc">Guidelines on the best times to test your blood glucose — fasting, pre-meal, post-meal, and bedtime.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 4 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('when-to-check')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:40%;"></div>
    </div>

    <div class="edu-card" data-category="monitoring" data-key="hba1c"
         data-title="a1c hba1c test what is" style="--cat-color:#6d28d9;--cat-bg:#ede9fe;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-microscope"></i></div>
            <span class="edu-card-tag">Monitoring</span>
        </div>
        <h3 class="edu-card-title">What is HbA1c?</h3>
        <p class="edu-card-desc">Your A1c test measures average blood sugar over 3 months. Here's what the numbers mean and your target range.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 5 min</span>
                <span class="edu-level-badge edu-level--intermediate">Intermediate</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('hba1c')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:50%;"></div>
    </div>

    <!-- LIFESTYLE ─── -->
    <div class="edu-card" data-category="lifestyle" data-key="stress-sugar"
         data-title="stress blood sugar management" style="--cat-color:#0e7490;--cat-bg:#cffafe;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-brain"></i></div>
            <span class="edu-card-tag">Lifestyle</span>
        </div>
        <h3 class="edu-card-title">Stress & Blood Sugar: The Link</h3>
        <p class="edu-card-desc">Chronic stress raises cortisol, which can spike blood glucose. Learn stress-reduction strategies that actually work.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 5 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('stress-sugar')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:50%;"></div>
    </div>

    <div class="edu-card" data-category="lifestyle" data-key="sleep-diabetes"
         data-title="sleep diabetes rest" style="--cat-color:#0e7490;--cat-bg:#cffafe;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-moon"></i></div>
            <span class="edu-card-tag">Lifestyle</span>
        </div>
        <h3 class="edu-card-title">Sleep & Diabetes Management</h3>
        <p class="edu-card-desc">Poor sleep affects insulin resistance more than you think. Tips to improve sleep quality for better glucose control.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 4 min</span>
                <span class="edu-level-badge edu-level--beginner">Beginner</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('sleep-diabetes')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:40%;"></div>
    </div>

    <div class="edu-card" data-category="lifestyle" data-key="alcohol"
         data-title="alcohol diabetes safety" style="--cat-color:#0e7490;--cat-bg:#cffafe;">
        <div class="edu-card-top">
            <div class="edu-card-icon"><i class="ti ti-alert-triangle"></i></div>
            <span class="edu-card-tag">Lifestyle</span>
        </div>
        <h3 class="edu-card-title">Alcohol & Diabetes: Safety Tips</h3>
        <p class="edu-card-desc">Can you drink alcohol with diabetes? Here's what you should know to stay safe and monitor your levels responsibly.</p>
        <div class="edu-card-footer">
            <div class="edu-card-footer-left">
                <span class="edu-read-time"><i class="ti ti-clock"></i> 4 min</span>
                <span class="edu-level-badge edu-level--intermediate">Intermediate</span>
            </div>
            <button class="edu-card-btn" onclick="openArticle('alcohol')">
                Read <i class="ti ti-arrow-right"></i>
            </button>
        </div>
        <div class="edu-card-progress" style="--pct:40%;"></div>
    </div>

</div><!-- /.edu-grid -->

<!-- No results state -->
<div class="edu-no-results" id="eduNoResults">
    <div class="edu-no-results-icon"><i class="ti ti-search-off"></i></div>
    <div class="edu-no-results-title">No articles found</div>
    <div class="edu-no-results-sub">Try a different keyword or clear your search.</div>
</div>


<!-- ══ DAILY DOSE — prescription-pad aesthetic ══════════ -->
<div class="edu-dose-header">
    <div class="edu-section-label">
        <i class="ti ti-clipboard-plus"></i> Daily Dose
    </div>
    <div class="edu-dose-rx">
        <i class="ti ti-prescription"></i> 4 tips
    </div>
</div>

<div class="edu-dose-pad">
    <div class="edu-dose-pad-header">
        <div class="edu-dose-pad-logo">
            <i class="ti ti-heart-rate-monitor"></i>
            DiabeTrack
        </div>
        <div class="edu-dose-pad-label">Daily Health Tips</div>
    </div>
    <div class="edu-dose-grid">
        <?php foreach ([
            ['ti-clock',          '#c04a20', '#FDE8DC', 'Eat meals at consistent times each day to help stabilize blood sugar rhythms.'],
            ['ti-droplet',        '#0e7490', '#cffafe', 'Drink water before meals — hydration affects glucose concentration in the bloodstream.'],
            ['ti-walk',           '#d97706', '#fef3c7', 'A 10-minute walk after eating can lower post-meal blood sugar by up to 22%.'],
            ['ti-sun',            '#0f7a45', '#d4f7e8', 'Never skip breakfast — it sets the metabolic tone for the entire day.'],
        ] as $i => [$icon, $color, $bg, $text]): ?>
        <div class="edu-dose-card">
            <div class="edu-dose-num" style="color:<?= $color ?>;">0<?= $i + 1 ?></div>
            <div class="edu-dose-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
                <i class="ti <?= $icon ?>"></i>
            </div>
            <p class="edu-dose-text"><?= $text ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="edu-dose-pad-footer">
        <i class="ti ti-info-circle"></i>
        These tips are general guidance. Always follow your doctor's personalised advice.
    </div>
</div>


<!-- ══ ARTICLE MODAL ══════════════════════════════════ -->
<div class="edu-modal-overlay" id="eduModal" aria-modal="true" role="dialog">
    <div class="edu-modal">

        <div class="edu-modal-head">
            <div>
                <div class="edu-modal-tag" id="modalTag"></div>
                <h2 class="edu-modal-title" id="modalTitle"></h2>
                <div class="edu-modal-meta" id="modalMeta"></div>
            </div>
            <button class="edu-modal-close" onclick="closeEduModal()" aria-label="Close article">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="edu-modal-divider"></div>
        <div class="edu-modal-body" id="modalBody"></div>

    </div>
</div>


<script>
/* ── Article content ──────────────────────────────────── */
const articles = {
    'featured': {
        tag: '<i class="ti ti-droplet-half-2"></i> Blood Sugar Monitoring',
        title: 'Understanding Your Blood Glucose Numbers',
        meta: '<i class="ti ti-clock"></i> 5 min read &nbsp;·&nbsp; <i class="ti ti-award"></i> Beginner Friendly',
        body: `
            <h4>What Do the Numbers Mean?</h4>
            <p>Blood glucose is measured in milligrams per deciliter (mg/dL). Here are the standard ranges:</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>70–99 mg/dL</span><span>Normal (Fasting)</span></div>
                <div class="mrt-row mrt-warn"><span>100–125 mg/dL</span><span>Prediabetes</span></div>
                <div class="mrt-row mrt-danger"><span>126+ mg/dL</span><span>Diabetes (Fasting)</span></div>
                <div class="mrt-row mrt-good"><span>&lt;140 mg/dL</span><span>Normal (2hr post-meal)</span></div>
            </div>
            <h4>When to Measure</h4>
            <p>For most diabetic patients, doctors recommend testing: <strong>fasting in the morning</strong>, <strong>before meals</strong>, <strong>2 hours after meals</strong>, and <strong>before bedtime</strong>. Your doctor may advise a specific schedule based on your type and treatment plan.</p>
            <h4>Using Trends, Not Just Single Readings</h4>
            <p>A single high reading isn't cause for alarm — look at your <em>patterns</em> over days and weeks. DiabeTrack's 7-day chart on your Blood Sugar page helps you visualize these trends.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> <strong>Pro Tip:</strong> Log your meals alongside your readings to identify which foods spike your glucose most.</div>
        `
    },
    'carb-counting': {
        tag: '<i class="ti ti-salad"></i> Nutrition',
        title: 'Carb Counting 101',
        meta: '<i class="ti ti-clock"></i> 4 min read',
        body: `
            <p>Carbohydrates raise blood sugar more than any other macronutrient. Counting carbs helps you predict and manage glucose spikes after meals.</p>
            <h4>How Many Carbs Per Meal?</h4>
            <p>Most adults with Type 2 diabetes aim for <strong>45–60g of carbs per meal</strong> and <strong>15–30g per snack</strong>. Your doctor or dietitian can give you personalised targets.</p>
            <h4>Reading Nutrition Labels</h4>
            <p>Look for <em>Total Carbohydrates</em> on the label — this includes sugar and fiber. Subtract dietary fiber (it doesn't raise blood sugar) to get the <strong>net carbs</strong>.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> <strong>Quick Rule:</strong> 15 grams of carbs = 1 "carb serving." Most people need 3–4 servings per meal.</div>
            <h4>High-Carb Foods to Watch</h4>
            <p>White rice, white bread, pasta, sugary drinks, sweets, and starchy vegetables like potatoes are the biggest sources of rapidly absorbed carbohydrates.</p>
        `
    },
    'glycemic-index': {
        tag: '<i class="ti ti-chart-bar"></i> Nutrition',
        title: 'The Glycemic Index Guide',
        meta: '<i class="ti ti-clock"></i> 6 min read',
        body: `
            <p>The Glycemic Index (GI) ranks foods from 0–100 based on how quickly they raise blood glucose. Lower GI = slower glucose rise = better control.</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>GI 1–55</span><span>Low — Oats, lentils, apples</span></div>
                <div class="mrt-row mrt-warn"><span>GI 56–69</span><span>Medium — Brown rice, sweet potato</span></div>
                <div class="mrt-row mrt-danger"><span>GI 70+</span><span>High — White bread, watermelon, soda</span></div>
            </div>
            <h4>GI vs. Glycemic Load</h4>
            <p>GI tells you the <em>speed</em>; Glycemic Load (GL) accounts for portion size too. A food can have a high GI but low GL if eaten in small amounts.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> <strong>Tip:</strong> Combine high-GI foods with protein, fat, or fiber to slow absorption and reduce the glucose spike.</div>
        `
    },
    'plate-method': {
        tag: '<i class="ti ti-tools-kitchen-2"></i> Nutrition',
        title: 'Building a Diabetes-Friendly Plate',
        meta: '<i class="ti ti-clock"></i> 5 min read',
        body: `
            <p>The Plate Method is a simple, visual way to portion your meals without counting every gram.</p>
            <h4>The Formula</h4>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>½ Plate</span><span>Non-starchy vegetables (broccoli, spinach, peppers)</span></div>
                <div class="mrt-row mrt-warn"><span>¼ Plate</span><span>Lean protein (chicken, fish, beans, tofu)</span></div>
                <div class="mrt-row mrt-danger"><span>¼ Plate</span><span>Complex carbs (brown rice, whole grain bread)</span></div>
            </div>
            <p>Add a small piece of fruit and a glass of water or unsweetened drink to complete the meal.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> <strong>Remember:</strong> The goal isn't to eliminate carbs — it's to balance them with protein and fiber.</div>
        `
    },
    'insulin-types': {
        tag: '<i class="ti ti-vaccine"></i> Medication',
        title: 'Types of Insulin Explained',
        meta: '<i class="ti ti-clock"></i> 7 min read',
        body: `
            <p>Insulin comes in several types, each designed to mimic the body's natural insulin release at different times.</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>Rapid-Acting</span><span>Works in 15 min, peaks 1–2 hrs (Humalog, NovoLog)</span></div>
                <div class="mrt-row mrt-good"><span>Short-Acting</span><span>Works in 30 min, peaks 2–4 hrs (Regular)</span></div>
                <div class="mrt-row mrt-warn"><span>Intermediate</span><span>Works in 2–4 hrs, peaks 4–12 hrs (NPH)</span></div>
                <div class="mrt-row mrt-danger"><span>Long-Acting</span><span>Works in 1–2 hrs, no peak, lasts 24 hrs (Lantus)</span></div>
            </div>
            <h4>Basal vs. Bolus</h4>
            <p><strong>Basal insulin</strong> (long-acting) provides a steady background level. <strong>Bolus insulin</strong> (rapid/short-acting) covers meals. Many Type 1 patients use both.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> Always rotate injection sites to prevent lipohypertrophy (fatty lumps that slow absorption).</div>
        `
    },
    'oral-meds': {
        tag: '<i class="ti ti-pill"></i> Medication',
        title: 'Common Oral Diabetes Medications',
        meta: '<i class="ti ti-clock"></i> 8 min read',
        body: `
            <h4>Metformin (First-Line)</h4>
            <p>The most prescribed diabetes drug. Reduces glucose production in the liver and improves insulin sensitivity. Take with food to reduce stomach upset.</p>
            <h4>Sulfonylureas</h4>
            <p>Stimulate the pancreas to produce more insulin (e.g., Glipizide, Glyburide). Risk of hypoglycemia if meals are skipped.</p>
            <h4>DPP-4 Inhibitors</h4>
            <p>Help the body produce more insulin when blood sugar is high (e.g., Sitagliptin/Januvia). Generally well-tolerated with low hypoglycemia risk.</p>
            <h4>SGLT2 Inhibitors</h4>
            <p>Cause the kidneys to remove excess glucose through urine (e.g., Jardiance, Farxiga). Also benefit heart and kidney health.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> Never stop or adjust diabetes medication without consulting your doctor first.</div>
        `
    },
    'exercise-sugar': {
        tag: '<i class="ti ti-run"></i> Exercise',
        title: 'How Exercise Lowers Blood Sugar',
        meta: '<i class="ti ti-clock"></i> 5 min read',
        body: `
            <p>During exercise, your muscles use glucose for energy — without requiring insulin. This directly lowers blood sugar and improves long-term insulin sensitivity.</p>
            <h4>Aerobic vs. Strength Training</h4>
            <p><strong>Aerobic exercise</strong> (walking, swimming, cycling) lowers blood sugar immediately. <strong>Resistance training</strong> (weights, resistance bands) builds muscle mass which helps absorb glucose over time.</p>
            <h4>Exercise Safely with Diabetes</h4>
            <p>Check blood sugar before and after exercise. If below 100 mg/dL before a workout, eat a small carb snack first. Carry fast-acting carbs in case of hypoglycemia.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> Even a 10-minute walk after eating can reduce post-meal glucose by 15–22%.</div>
        `
    },
    'walking-plan': {
        tag: '<i class="ti ti-walk"></i> Exercise',
        title: 'The 30-Minute Walking Plan',
        meta: '<i class="ti ti-clock"></i> 3 min read',
        body: `
            <h4>Week 1–2: Getting Started</h4>
            <p>Walk for 10 minutes after each main meal. Focus on steady pace, not speed.</p>
            <h4>Week 3–4: Building Up</h4>
            <p>Combine into one 20-minute morning walk plus a 10-minute post-dinner stroll. Aim for 5 days per week.</p>
            <h4>Week 5+: Maintenance</h4>
            <p>30 consecutive minutes daily at a brisk pace. This meets the WHO guideline of 150 min/week of moderate activity.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> <strong>Consistency beats intensity.</strong> Daily walks at moderate pace are more beneficial for blood sugar than occasional intense workouts.</div>
        `
    },
    'when-to-check': {
        tag: '<i class="ti ti-device-watch-stats"></i> Monitoring',
        title: 'When & How Often to Check',
        meta: '<i class="ti ti-clock"></i> 4 min read',
        body: `
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>Morning (Fasting)</span><span>Before eating or drinking anything</span></div>
                <div class="mrt-row mrt-good"><span>Pre-Meal</span><span>Before breakfast, lunch, and dinner</span></div>
                <div class="mrt-row mrt-warn"><span>Post-Meal</span><span>2 hours after starting a meal</span></div>
                <div class="mrt-row mrt-warn"><span>Bedtime</span><span>To ensure safe overnight levels</span></div>
                <div class="mrt-row mrt-danger"><span>Any Symptoms</span><span>Shaking, sweating, confusion, headache</span></div>
            </div>
            <h4>Type 1 vs. Type 2</h4>
            <p>Type 1 patients typically check 4–10 times daily. Type 2 on insulin: 2–4 times. Type 2 on oral meds: once daily or as directed by your doctor.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> Use DiabeTrack's Blood Sugar Logger to log each reading — patterns are more valuable than single numbers.</div>
        `
    },
    'hba1c': {
        tag: '<i class="ti ti-microscope"></i> Monitoring',
        title: 'What is HbA1c?',
        meta: '<i class="ti ti-clock"></i> 5 min read',
        body: `
            <p>HbA1c (glycated hemoglobin) shows your <em>average</em> blood sugar over the past 2–3 months. It's expressed as a percentage.</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>Below 5.7%</span><span>Normal</span></div>
                <div class="mrt-row mrt-warn"><span>5.7–6.4%</span><span>Prediabetes</span></div>
                <div class="mrt-row mrt-danger"><span>6.5% and above</span><span>Diabetes</span></div>
            </div>
            <h4>Your Target</h4>
            <p>Most adults with diabetes aim for <strong>below 7%</strong>. Get your HbA1c tested every 3 months if above target, or every 6 months once controlled.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> A 1% reduction in HbA1c reduces diabetes complications by up to 37%.</div>
        `
    },
    'stress-sugar': {
        tag: '<i class="ti ti-brain"></i> Lifestyle',
        title: 'Stress & Blood Sugar: The Link',
        meta: '<i class="ti ti-clock"></i> 5 min read',
        body: `
            <p>When stressed, your body releases cortisol and adrenaline — hormones that raise blood sugar. In diabetics, this effect is amplified.</p>
            <h4>Proven Stress-Reduction Techniques</h4>
            <p><strong>Deep breathing:</strong> 4 counts in, hold 4, out 4. Activates the parasympathetic nervous system in under 2 minutes.</p>
            <p><strong>Progressive muscle relaxation:</strong> Tense then release each muscle group from toes to face. 10–15 minutes before bed.</p>
            <p><strong>Mindfulness meditation:</strong> Even 5 minutes daily has been shown to lower cortisol levels over time.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> If you notice unexplained blood sugar spikes, consider whether a stressful event preceded them. Log it in your notes.</div>
        `
    },
    'sleep-diabetes': {
        tag: '<i class="ti ti-moon"></i> Lifestyle',
        title: 'Sleep & Diabetes Management',
        meta: '<i class="ti ti-clock"></i> 4 min read',
        body: `
            <p>Less than 6 hours of sleep increases insulin resistance and raises fasting blood glucose — even in healthy people. For diabetics, the effect is more severe.</p>
            <h4>Sleep Tips for Better Control</h4>
            <p>• Keep a consistent sleep schedule — same bedtime and wake time daily, including weekends.<br>
               • Avoid screens 1 hour before bed; blue light suppresses melatonin.<br>
               • Keep your bedroom cool (65–68°F / 18–20°C) for optimal sleep quality.<br>
               • Avoid heavy meals within 3 hours of bedtime.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> If you snore or feel unrefreshed after sleeping, ask your doctor about sleep apnea screening — it's very common in Type 2 diabetes.</div>
        `
    },
    'alcohol': {
        tag: '<i class="ti ti-alert-triangle"></i> Lifestyle',
        title: 'Alcohol & Diabetes: Safety Tips',
        meta: '<i class="ti ti-clock"></i> 4 min read',
        body: `
            <p>Alcohol can cause both high and low blood sugar depending on how much you drink and whether you eat. The liver processes alcohol instead of releasing glucose — which can cause hypoglycemia hours later.</p>
            <h4>Safety Guidelines</h4>
            <p>• Never drink on an empty stomach.<br>
               • Stick to 1 drink/day (women) or 2 drinks/day (men) maximum.<br>
               • Check blood sugar before, during, and up to 24 hours after drinking.<br>
               • Avoid sweet mixers and cocktails — they cause sharp glucose spikes.<br>
               • Wear a medical ID so others know you have diabetes.</p>
            <div class="modal-tip-box"><i class="ti ti-bulb"></i> Symptoms of hypoglycemia can be mistaken for intoxication. Check your blood sugar if you feel unwell after drinking.</div>
        `
    }
};

function openArticle(key) {
    const a = articles[key];
    if (!a) return;
    document.getElementById('modalTag').innerHTML   = a.tag;
    document.getElementById('modalTitle').textContent = a.title;
    document.getElementById('modalMeta').innerHTML  = a.meta;
    document.getElementById('modalBody').innerHTML  = a.body;
    document.getElementById('eduModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEduModal() {
    document.getElementById('eduModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('eduModal').addEventListener('click', function(e) {
    if (e.target === this) closeEduModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEduModal(); });

/* ── Category filter ─────────────────────────────────── */
function applyFilters(filter, query) {
    let visible = 0;
    document.querySelectorAll('.edu-card').forEach(card => {
        const matchCat   = filter === 'all' || card.dataset.category === filter;
        const searchStr  = (card.dataset.title || '') + ' ' + card.querySelector('.edu-card-title').textContent + ' ' + card.querySelector('.edu-card-desc').textContent;
        const matchQuery = !query || searchStr.toLowerCase().includes(query);
        const show = matchCat && matchQuery;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('eduResultCount').textContent = visible + ' article' + (visible !== 1 ? 's' : '');
    document.getElementById('eduNoResults').style.display = visible === 0 ? 'flex' : 'none';
}

let activeFilter = 'all';
document.querySelectorAll('.edu-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.edu-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter;
        applyFilters(activeFilter, document.getElementById('eduSearch').value.toLowerCase().trim());
    });
});

/* ── Search ──────────────────────────────────────────── */
const searchInput = document.getElementById('eduSearch');
const searchClear = document.getElementById('eduSearchClear');
searchInput.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    searchClear.style.display = q ? 'flex' : 'none';
    applyFilters(activeFilter, q);
});
searchClear.addEventListener('click', () => {
    searchInput.value = '';
    searchClear.style.display = 'none';
    applyFilters(activeFilter, '');
    searchInput.focus();
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>