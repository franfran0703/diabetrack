<?php
$pageTitle  = 'Education Hub';
$activeMenu = 'education';
ob_start();
?>

<link href="/diabetrack/public/assets/css/education.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="edu-header">
    <div class="edu-header-left">
        <div class="edu-eyebrow">📚 Resources</div>
        <h1 class="edu-title">Education <span>Hub</span></h1>
        <p class="edu-sub">Guides, tips & articles to help you manage diabetes better.</p>
    </div>
    <div class="edu-header-right">
        <div class="edu-search-wrap">
            <span class="edu-search-icon">🔍</span>
            <input type="text" class="edu-search" id="eduSearch" placeholder="Search articles...">
        </div>
    </div>
</div>

<!-- CATEGORY FILTER TABS -->
<div class="edu-tabs" id="eduTabs">
    <button class="edu-tab active" data-filter="all">All Topics</button>
    <button class="edu-tab" data-filter="nutrition">🥗 Nutrition</button>
    <button class="edu-tab" data-filter="medication">💊 Medication</button>
    <button class="edu-tab" data-filter="exercise">🏃 Exercise</button>
    <button class="edu-tab" data-filter="monitoring">🩸 Monitoring</button>
    <button class="edu-tab" data-filter="lifestyle">🌿 Lifestyle</button>
</div>

<!-- FEATURED ARTICLE -->
<div class="edu-featured" data-category="monitoring">
    <div class="edu-featured-badge">⭐ Featured</div>
    <div class="edu-featured-content">
        <div class="edu-featured-tag">🩸 Blood Sugar Monitoring</div>
        <h2 class="edu-featured-title">Understanding Your Blood Glucose Numbers</h2>
        <p class="edu-featured-desc">Learn what your blood sugar readings mean, when to measure, and how to use the data to make better daily decisions about food, exercise, and medication.</p>
        <div class="edu-featured-meta">
            <span>⏱ 5 min read</span>
            <span>·</span>
            <span>🏷 Beginner Friendly</span>
        </div>
        <button class="edu-featured-btn" onclick="openArticle('featured')">Read Article →</button>
    </div>
    <div class="edu-featured-illus">
        <div class="edu-illus-ring">
            <div class="edu-illus-inner">
                <div class="edu-illus-val">180</div>
                <div class="edu-illus-unit">mg/dL</div>
                <div class="edu-illus-label">Target</div>
            </div>
        </div>
    </div>
</div>

<!-- ARTICLE GRID -->
<div class="edu-section-label">All Articles</div>

<div class="edu-grid" id="eduGrid">

    <!-- Nutrition -->
    <div class="edu-card" data-category="nutrition" data-title="carb counting">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(46,196,182,0.12);color:#2ec4b6;">🥗</div>
            <div class="edu-card-tag">Nutrition</div>
        </div>
        <h3 class="edu-card-title">Carb Counting 101</h3>
        <p class="edu-card-desc">Master the basics of counting carbohydrates to better manage post-meal blood sugar spikes.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 4 min</span>
            <button class="edu-card-btn" onclick="openArticle('carb-counting')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="nutrition" data-title="glycemic index foods">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(46,196,182,0.12);color:#2ec4b6;">📊</div>
            <div class="edu-card-tag">Nutrition</div>
        </div>
        <h3 class="edu-card-title">The Glycemic Index Guide</h3>
        <p class="edu-card-desc">Discover which foods raise blood sugar slowly vs. quickly, and how to build a low-GI meal plan.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 6 min</span>
            <button class="edu-card-btn" onclick="openArticle('glycemic-index')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="nutrition" data-title="meal planning diabetes">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(46,196,182,0.12);color:#2ec4b6;">🍽</div>
            <div class="edu-card-tag">Nutrition</div>
        </div>
        <h3 class="edu-card-title">Building a Diabetes-Friendly Plate</h3>
        <p class="edu-card-desc">Use the "Plate Method" to create balanced meals that keep your glucose levels steady throughout the day.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 5 min</span>
            <button class="edu-card-btn" onclick="openArticle('plate-method')">Read →</button>
        </div>
    </div>

    <!-- Medication -->
    <div class="edu-card" data-category="medication" data-title="insulin types medication">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(249,116,71,0.12);color:#F97447;">💊</div>
            <div class="edu-card-tag">Medication</div>
        </div>
        <h3 class="edu-card-title">Types of Insulin Explained</h3>
        <p class="edu-card-desc">Rapid-acting, long-acting, mixed — learn the differences between insulin types and when each is used.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 7 min</span>
            <button class="edu-card-btn" onclick="openArticle('insulin-types')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="medication" data-title="oral medication metformin">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(249,116,71,0.12);color:#F97447;">🧬</div>
            <div class="edu-card-tag">Medication</div>
        </div>
        <h3 class="edu-card-title">Common Oral Diabetes Medications</h3>
        <p class="edu-card-desc">An overview of Metformin, Sulfonylureas, DPP-4 inhibitors, and what to expect from each class.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 8 min</span>
            <button class="edu-card-btn" onclick="openArticle('oral-meds')">Read →</button>
        </div>
    </div>

    <!-- Exercise -->
    <div class="edu-card" data-category="exercise" data-title="exercise blood sugar benefits">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(255,193,7,0.12);color:#e6a817;">🏃</div>
            <div class="edu-card-tag">Exercise</div>
        </div>
        <h3 class="edu-card-title">How Exercise Lowers Blood Sugar</h3>
        <p class="edu-card-desc">Understand the science behind physical activity and glucose uptake — and how to exercise safely with diabetes.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 5 min</span>
            <button class="edu-card-btn" onclick="openArticle('exercise-sugar')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="exercise" data-title="walking workout routine">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(255,193,7,0.12);color:#e6a817;">🚶</div>
            <div class="edu-card-tag">Exercise</div>
        </div>
        <h3 class="edu-card-title">The 30-Minute Walking Plan</h3>
        <p class="edu-card-desc">A beginner-friendly weekly walking schedule proven to improve insulin sensitivity and cardiovascular health.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 3 min</span>
            <button class="edu-card-btn" onclick="openArticle('walking-plan')">Read →</button>
        </div>
    </div>

    <!-- Monitoring -->
    <div class="edu-card" data-category="monitoring" data-title="when to check blood sugar testing">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(108,99,255,0.10);color:#6c63ff;">🩸</div>
            <div class="edu-card-tag">Monitoring</div>
        </div>
        <h3 class="edu-card-title">When & How Often to Check</h3>
        <p class="edu-card-desc">Guidelines on the best times to test your blood glucose — fasting, pre-meal, post-meal, and bedtime.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 4 min</span>
            <button class="edu-card-btn" onclick="openArticle('when-to-check')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="monitoring" data-title="a1c hba1c test what is">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(108,99,255,0.10);color:#6c63ff;">🔬</div>
            <div class="edu-card-tag">Monitoring</div>
        </div>
        <h3 class="edu-card-title">What is HbA1c?</h3>
        <p class="edu-card-desc">Your A1c test measures average blood sugar over 3 months. Here's what the numbers mean and your target range.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 5 min</span>
            <button class="edu-card-btn" onclick="openArticle('hba1c')">Read →</button>
        </div>
    </div>

    <!-- Lifestyle -->
    <div class="edu-card" data-category="lifestyle" data-title="stress blood sugar management">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(46,196,182,0.10);color:#1a9e96;">🧘</div>
            <div class="edu-card-tag">Lifestyle</div>
        </div>
        <h3 class="edu-card-title">Stress & Blood Sugar: The Link</h3>
        <p class="edu-card-desc">Chronic stress raises cortisol, which can spike blood glucose. Learn stress-reduction strategies that actually work.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 5 min</span>
            <button class="edu-card-btn" onclick="openArticle('stress-sugar')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="lifestyle" data-title="sleep diabetes rest">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(46,196,182,0.10);color:#1a9e96;">😴</div>
            <div class="edu-card-tag">Lifestyle</div>
        </div>
        <h3 class="edu-card-title">Sleep & Diabetes Management</h3>
        <p class="edu-card-desc">Poor sleep affects insulin resistance more than you think. Tips to improve sleep quality for better glucose control.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 4 min</span>
            <button class="edu-card-btn" onclick="openArticle('sleep-diabetes')">Read →</button>
        </div>
    </div>

    <div class="edu-card" data-category="lifestyle" data-title="alcohol diabetes safety">
        <div class="edu-card-top">
            <div class="edu-card-icon" style="background:rgba(249,116,71,0.10);color:#F97447;">🍷</div>
            <div class="edu-card-tag">Lifestyle</div>
        </div>
        <h3 class="edu-card-title">Alcohol & Diabetes: Safety Tips</h3>
        <p class="edu-card-desc">Can you drink alcohol with diabetes? Here's what you should know to stay safe and monitor your levels responsibly.</p>
        <div class="edu-card-footer">
            <span class="edu-read-time">⏱ 4 min</span>
            <button class="edu-card-btn" onclick="openArticle('alcohol')">Read →</button>
        </div>
    </div>

</div>

<!-- QUICK TIPS STRIP -->
<div class="edu-section-label" style="margin-top:32px;">Daily Tips</div>
<div class="edu-tips-strip">
    <div class="edu-tip">
        <div class="edu-tip-num">01</div>
        <div class="edu-tip-text">Eat meals at consistent times each day to help stabilize blood sugar rhythms.</div>
    </div>
    <div class="edu-tip">
        <div class="edu-tip-num">02</div>
        <div class="edu-tip-text">Drink water before meals — hydration affects glucose concentration in the bloodstream.</div>
    </div>
    <div class="edu-tip">
        <div class="edu-tip-num">03</div>
        <div class="edu-tip-text">A 10-minute walk after eating can lower post-meal blood sugar by up to 22%.</div>
    </div>
    <div class="edu-tip">
        <div class="edu-tip-num">04</div>
        <div class="edu-tip-text">Never skip breakfast — it sets the metabolic tone for the entire day.</div>
    </div>
</div>

<!-- ARTICLE MODAL -->
<div class="edu-modal-overlay" id="eduModal">
    <div class="edu-modal">
        <button class="edu-modal-close" onclick="closeEduModal()">✕</button>
        <div class="edu-modal-tag" id="modalTag"></div>
        <h2 class="edu-modal-title" id="modalTitle"></h2>
        <div class="edu-modal-meta" id="modalMeta"></div>
        <div class="edu-modal-body" id="modalBody"></div>
    </div>
</div>

<script>
// ── ARTICLE CONTENT ──────────────────────────────────────────────────
const articles = {
    'featured': {
        tag: '🩸 Blood Sugar Monitoring',
        title: 'Understanding Your Blood Glucose Numbers',
        meta: '⏱ 5 min read · 🏷 Beginner Friendly',
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
            <p>For most diabetic patients, doctors recommend testing: <strong>fasting in the morning</strong>, <strong>before meals</strong>, <strong>2 hours after meals</strong>, and <strong>before bedtime</strong>. Your doctor may advise a specific schedule based on your type of diabetes and treatment plan.</p>
            <h4>Using Trends, Not Just Single Readings</h4>
            <p>A single high reading isn't cause for alarm — look at your <em>patterns</em> over days and weeks. DiabeTrack's 7-day chart on your Blood Sugar page helps you visualize these trends. Share this data with your doctor at each visit.</p>
            <div class="modal-tip-box">💡 <strong>Pro Tip:</strong> Log your meals alongside your readings to identify which foods spike your glucose most.</div>
        `
    },
    'carb-counting': {
        tag: '🥗 Nutrition',
        title: 'Carb Counting 101',
        meta: '⏱ 4 min read',
        body: `
            <p>Carbohydrates raise blood sugar more than any other macronutrient. Counting carbs helps you predict and manage glucose spikes after meals.</p>
            <h4>How Many Carbs Per Meal?</h4>
            <p>Most adults with Type 2 diabetes aim for <strong>45–60g of carbs per meal</strong> and <strong>15–30g per snack</strong>. Your doctor or dietitian can give you personalized targets.</p>
            <h4>Reading Nutrition Labels</h4>
            <p>Look for <em>Total Carbohydrates</em> on the label — this includes sugar and fiber. Subtract dietary fiber (it doesn't raise blood sugar) to get the <strong>net carbs</strong>.</p>
            <div class="modal-tip-box">💡 <strong>Quick Rule:</strong> 15 grams of carbs = 1 "carb serving." Most people need 3–4 servings per meal.</div>
            <h4>High-Carb Foods to Watch</h4>
            <p>White rice, white bread, pasta, sugary drinks, sweets, and starchy vegetables like potatoes are the biggest sources of rapidly absorbed carbohydrates.</p>
        `
    },
    'glycemic-index': {
        tag: '🥗 Nutrition',
        title: 'The Glycemic Index Guide',
        meta: '⏱ 6 min read',
        body: `
            <p>The Glycemic Index (GI) ranks foods from 0–100 based on how quickly they raise blood glucose. Lower GI = slower glucose rise = better control.</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>GI 1–55</span><span>Low — Oats, lentils, apples</span></div>
                <div class="mrt-row mrt-warn"><span>GI 56–69</span><span>Medium — Brown rice, sweet potato</span></div>
                <div class="mrt-row mrt-danger"><span>GI 70+</span><span>High — White bread, watermelon, soda</span></div>
            </div>
            <h4>GI vs. Glycemic Load</h4>
            <p>GI tells you the <em>speed</em>; Glycemic Load (GL) accounts for portion size too. A food can have a high GI but low GL if eaten in small amounts (e.g., watermelon).</p>
            <div class="modal-tip-box">💡 <strong>Tip:</strong> Combine high-GI foods with protein, fat, or fiber to slow absorption and reduce the glucose spike.</div>
        `
    },
    'plate-method': {
        tag: '🥗 Nutrition',
        title: 'Building a Diabetes-Friendly Plate',
        meta: '⏱ 5 min read',
        body: `
            <p>The Plate Method is a simple, visual way to portion your meals without counting every gram.</p>
            <h4>The Formula</h4>
            <p>Use a 9-inch plate and divide it into sections:</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>½ Plate</span><span>Non-starchy vegetables (broccoli, spinach, peppers)</span></div>
                <div class="mrt-row mrt-warn"><span>¼ Plate</span><span>Lean protein (chicken, fish, beans, tofu)</span></div>
                <div class="mrt-row mrt-danger"><span>¼ Plate</span><span>Complex carbs (brown rice, whole grain bread)</span></div>
            </div>
            <p>Add a small piece of fruit and a glass of water or unsweetened drink to complete the meal.</p>
            <div class="modal-tip-box">💡 <strong>Remember:</strong> The goal isn't to eliminate carbs — it's to balance them with protein and fiber.</div>
        `
    },
    'insulin-types': {
        tag: '💊 Medication',
        title: 'Types of Insulin Explained',
        meta: '⏱ 7 min read',
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
            <div class="modal-tip-box">💡 Always rotate injection sites to prevent lipohypertrophy (fatty lumps that slow absorption).</div>
        `
    },
    'oral-meds': {
        tag: '💊 Medication',
        title: 'Common Oral Diabetes Medications',
        meta: '⏱ 8 min read',
        body: `
            <h4>Metformin (First-Line)</h4>
            <p>The most prescribed diabetes drug. Reduces glucose production in the liver and improves insulin sensitivity. Take with food to reduce stomach upset.</p>
            <h4>Sulfonylureas</h4>
            <p>Stimulate the pancreas to produce more insulin (e.g., Glipizide, Glyburide). Risk of hypoglycemia if meals are skipped.</p>
            <h4>DPP-4 Inhibitors</h4>
            <p>Help the body produce more insulin when blood sugar is high (e.g., Sitagliptin/Januvia). Generally well-tolerated with low hypoglycemia risk.</p>
            <h4>SGLT2 Inhibitors</h4>
            <p>Cause the kidneys to remove excess glucose through urine (e.g., Jardiance, Farxiga). Also benefit heart and kidney health.</p>
            <div class="modal-tip-box">💡 Never stop or adjust diabetes medication without consulting your doctor first.</div>
        `
    },
    'exercise-sugar': {
        tag: '🏃 Exercise',
        title: 'How Exercise Lowers Blood Sugar',
        meta: '⏱ 5 min read',
        body: `
            <p>During exercise, your muscles use glucose for energy — without requiring insulin. This directly lowers blood sugar and improves long-term insulin sensitivity.</p>
            <h4>Aerobic vs. Strength Training</h4>
            <p><strong>Aerobic exercise</strong> (walking, swimming, cycling) lowers blood sugar immediately. <strong>Resistance training</strong> (weights, resistance bands) builds muscle mass which helps absorb glucose over time.</p>
            <h4>Exercise Safely with Diabetes</h4>
            <p>Check blood sugar before and after exercise. If below 100 mg/dL before a workout, eat a small carb snack first. Carry fast-acting carbs (glucose tablets) in case of hypoglycemia.</p>
            <div class="modal-tip-box">💡 Even a 10-minute walk after eating can reduce post-meal glucose by 15–22%.</div>
        `
    },
    'walking-plan': {
        tag: '🏃 Exercise',
        title: 'The 30-Minute Walking Plan',
        meta: '⏱ 3 min read',
        body: `
            <h4>Week 1–2: Getting Started</h4>
            <p>Walk for 10 minutes after each main meal (breakfast, lunch, dinner). Focus on steady pace, not speed.</p>
            <h4>Week 3–4: Building Up</h4>
            <p>Combine into one 20-minute morning walk plus a 10-minute post-dinner stroll. Aim for 5 days per week.</p>
            <h4>Week 5+: Maintenance</h4>
            <p>30 consecutive minutes daily at a brisk pace. This meets the WHO guideline of 150 min/week of moderate activity.</p>
            <div class="modal-tip-box">💡 <strong>Consistency beats intensity.</strong> Daily walks at moderate pace are more beneficial for blood sugar than occasional intense workouts.</div>
        `
    },
    'when-to-check': {
        tag: '🩸 Monitoring',
        title: 'When & How Often to Check',
        meta: '⏱ 4 min read',
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
            <div class="modal-tip-box">💡 Use DiabeTrack's Blood Sugar Logger to log each reading — patterns are more valuable than single numbers.</div>
        `
    },
    'hba1c': {
        tag: '🩸 Monitoring',
        title: 'What is HbA1c?',
        meta: '⏱ 5 min read',
        body: `
            <p>HbA1c (glycated hemoglobin) shows your <em>average</em> blood sugar over the past 2–3 months. It's expressed as a percentage.</p>
            <div class="modal-range-table">
                <div class="mrt-row mrt-good"><span>Below 5.7%</span><span>Normal</span></div>
                <div class="mrt-row mrt-warn"><span>5.7–6.4%</span><span>Prediabetes</span></div>
                <div class="mrt-row mrt-danger"><span>6.5% and above</span><span>Diabetes</span></div>
            </div>
            <h4>Your Target</h4>
            <p>Most adults with diabetes aim for <strong>below 7%</strong>. Your doctor may set a different goal based on age, other conditions, and risk of hypoglycemia.</p>
            <p>Get your HbA1c tested every 3 months if it's above target, or every 6 months once controlled.</p>
            <div class="modal-tip-box">💡 A 1% reduction in HbA1c reduces diabetes complications by up to 37%.</div>
        `
    },
    'stress-sugar': {
        tag: '🌿 Lifestyle',
        title: 'Stress & Blood Sugar: The Link',
        meta: '⏱ 5 min read',
        body: `
            <p>When you're stressed, your body releases cortisol and adrenaline — hormones that raise blood sugar to give you energy for "fight or flight." In diabetics, this effect is amplified.</p>
            <h4>Proven Stress-Reduction Techniques</h4>
            <p><strong>Deep breathing:</strong> 4 counts in, hold 4, out 4. Activates the parasympathetic nervous system in under 2 minutes.</p>
            <p><strong>Progressive muscle relaxation:</strong> Tense then release each muscle group from toes to face. 10–15 minutes before bed.</p>
            <p><strong>Mindfulness meditation:</strong> Even 5 minutes daily has been shown to lower cortisol levels over time.</p>
            <div class="modal-tip-box">💡 If you notice unexplained blood sugar spikes, consider whether a stressful event preceded them. Log stress in your notes field.</div>
        `
    },
    'sleep-diabetes': {
        tag: '🌿 Lifestyle',
        title: 'Sleep & Diabetes Management',
        meta: '⏱ 4 min read',
        body: `
            <p>Less than 6 hours of sleep increases insulin resistance and raises fasting blood glucose — even in healthy people. For diabetics, the effect is more severe.</p>
            <h4>Sleep Tips for Better Control</h4>
            <p>• Keep a consistent sleep schedule — same bedtime and wake time daily, including weekends.</p>
            <p>• Avoid screens 1 hour before bed; blue light suppresses melatonin.</p>
            <p>• Keep your bedroom cool (65–68°F / 18–20°C) for optimal sleep quality.</p>
            <p>• Avoid heavy meals within 3 hours of bedtime — but don't go to bed too hungry either.</p>
            <div class="modal-tip-box">💡 If you snore or feel unrefreshed after sleeping, ask your doctor about a sleep apnea screening — it's very common in Type 2 diabetes and worsens glucose control.</div>
        `
    },
    'alcohol': {
        tag: '🌿 Lifestyle',
        title: 'Alcohol & Diabetes: Safety Tips',
        meta: '⏱ 4 min read',
        body: `
            <p>Alcohol can cause both high and low blood sugar depending on how much you drink and whether you eat. The liver processes alcohol instead of releasing glucose — which can cause hypoglycemia hours later.</p>
            <h4>Safety Guidelines</h4>
            <p>• Never drink on an empty stomach.</p>
            <p>• Stick to 1 drink/day (women) or 2 drinks/day (men) maximum.</p>
            <p>• Check blood sugar before, during, and up to 24 hours after drinking.</p>
            <p>• Avoid sweet mixers and cocktails — they cause sharp glucose spikes.</p>
            <p>• Wear a medical ID so others know you have diabetes if you feel unwell.</p>
            <div class="modal-tip-box">💡 Symptoms of hypoglycemia (shaking, confusion, sweating) can be mistaken for intoxication. Check your blood sugar if you feel unwell after drinking.</div>
        `
    }
};

function openArticle(key) {
    const a = articles[key];
    if (!a) return;
    document.getElementById('modalTag').textContent   = a.tag;
    document.getElementById('modalTitle').textContent = a.title;
    document.getElementById('modalMeta').textContent  = a.meta;
    document.getElementById('modalBody').innerHTML    = a.body;
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

// ── CATEGORY FILTER ──────────────────────────────────────────────────
document.querySelectorAll('.edu-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.edu-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.edu-card').forEach(card => {
            const match = filter === 'all' || card.dataset.category === filter;
            card.style.display = match ? '' : 'none';
        });
    });
});

// ── SEARCH ───────────────────────────────────────────────────────────
document.getElementById('eduSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.edu-card').forEach(card => {
        const title = card.querySelector('.edu-card-title').textContent.toLowerCase();
        const desc  = card.querySelector('.edu-card-desc').textContent.toLowerCase();
        const tags  = (card.dataset.title || '').toLowerCase();
        card.style.display = (!q || title.includes(q) || desc.includes(q) || tags.includes(q)) ? '' : 'none';
    });
    // Reset tab active state on search
    if (q) {
        document.querySelectorAll('.edu-tab').forEach(b => b.classList.remove('active'));
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>