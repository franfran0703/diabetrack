<?php
$pageTitle  = 'Medication Monitor';
$activeMenu = 'medication';
ob_start();
?>

<link href="/diabetrack/public/assets/css/caregiver_layout.css?v=<?= time() ?>">
<link href="/diabetrack/public/assets/css/caregiver_medication.css?v=<?= time() ?>" rel="stylesheet">

<!-- HEADER -->
<div class="cgmed-header">
    <div>
        <div class="cgmed-eyebrow">Schedule Monitor</div>
        <h1 class="cgmed-title">Medication <span>Monitor</span></h1>
    </div>
    <?php if ($patient): ?>
    <div class="cgmed-patient-chip">
        <div class="cgmed-patient-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
        <div>
            <div class="cgmed-patient-name"><?= htmlspecialchars($patient['name']) ?></div>
            <div class="cgmed-patient-label">Linked Patient</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$patient): ?>
<div style="background:rgba(255,255,255,0.05);border:1.5px solid rgba(249,116,71,0.15);border-radius:24px;padding:60px;text-align:center;backdrop-filter:blur(8px);">
    <div style="font-size:3rem;margin-bottom:16px;">🔗</div>
    <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:800;font-size:1.1rem;color:#ffe8d6;margin-bottom:8px;">No Patient Linked</div>
    <div style="font-size:0.85rem;color:rgba(255,200,160,0.55);">
        <a href="/diabetrack/public/caregiver/patients" style="color:#fbab6e;font-weight:700;">Link a patient</a> to monitor medications.
    </div>
</div>

<?php else: ?>

<!-- SUMMARY BAR — 4 horizontal panels -->
<div class="cgmed-summary">
    <div class="cgmed-sum-panel glass">
        <div class="cgmed-sum-icon">💊</div>
        <div>
            <div class="cgmed-sum-val"><?= count($medications) ?></div>
            <div class="cgmed-sum-label">Total Meds</div>
        </div>
    </div>
    <div class="cgmed-sum-panel peach">
        <div class="cgmed-sum-icon">📅</div>
        <div>
            <div class="cgmed-sum-val"><?= $todayStats['total'] ?? 0 ?></div>
            <div class="cgmed-sum-label">Logged Today</div>
        </div>
    </div>
    <div class="cgmed-sum-panel glass-warm">
        <div class="cgmed-sum-icon">✅</div>
        <div>
            <div class="cgmed-sum-val"><?= $todayStats['taken'] ?? 0 ?></div>
            <div class="cgmed-sum-label">Taken Today</div>
        </div>
    </div>
    <div class="cgmed-sum-panel danger-glass">
        <div class="cgmed-sum-icon">❌</div>
        <div>
            <div class="cgmed-sum-val"><?= $todayStats['missed'] ?? 0 ?></div>
            <div class="cgmed-sum-label">Missed Today</div>
        </div>
    </div>
</div>

<!-- MAIN ROW: TIMELINE + ALERT PANELS -->
<div class="cgmed-main">

    <!-- TIMELINE -->
    <div class="cgmed-timeline-panel">
        <div class="cgmed-panel-label">
            Today's Schedule — <?= date('M d, Y') ?>
        </div>

        <?php if (empty($medications)): ?>
        <div class="cgmed-empty">
            <div class="cgmed-empty-icon">💊</div>
            <div class="cgmed-empty-text">No medications in schedule.</div>
        </div>
        <?php else: ?>
        <div class="cgmed-timeline">
            <?php foreach ($medications as $med):
                $logged  = $loggedToday[$med['id']] ?? false;

                // Find today's log for this med
                $logStatus = null;
                $logTime   = null;
                foreach ($todayLogs as $tl) {
                    if ($tl['medication_id'] ?? null == $med['id'] ||
                        $tl['name'] === $med['name']) {
                        $logStatus = $tl['status'];
                        $logTime   = $tl['logged_at'];
                        break;
                    }
                }

                $dotClass   = $logged ? strtolower($logStatus ?? 'taken') : 'pending';
                $dotEmoji   = $dotClass === 'taken' ? '✅' : ($dotClass === 'missed' ? '❌' : '⏳');
            ?>
            <div class="cgmed-tl-item">
                <div class="cgmed-tl-dot <?= $dotClass ?>"><?= $dotEmoji ?></div>
                <div class="cgmed-tl-content">
                    <div class="cgmed-tl-top">
                        <div class="cgmed-tl-name"><?= htmlspecialchars($med['name']) ?></div>
                        <span class="cgmed-tl-badge <?= $dotClass ?>">
                            <?= $logged ? $logStatus : 'Pending' ?>
                        </span>
                    </div>
                    <div class="cgmed-tl-meta">
                        <span>💊 <?= htmlspecialchars($med['dosage']) ?></span>
                        <span>·</span>
                        <span>🕐 <?= date('h:i A', strtotime($med['schedule_time'])) ?></span>
                        <span>·</span>
                        <span><?= $med['frequency'] ?></span>
                        <?php if ($logTime): ?>
                        <span>·</span>
                        <span style="color:rgba(255,200,160,0.35);">Logged <?= date('h:i A', strtotime($logTime)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT ALERT PANELS -->
    <div class="cgmed-alert-panel">

        <!-- Missed count hero — peach -->
        <div class="cgmed-missed-hero">
            <div style="font-size:0.6rem;font-weight:800;letter-spacing:2.5px;text-transform:uppercase;color:#c4714a;margin-bottom:12px;">Missed Today</div>
            <div class="cgmed-missed-count"><?= $todayStats['missed'] ?? 0 ?></div>
            <div class="cgmed-missed-label">missed doses</div>
            <div class="cgmed-missed-sub">
                <?php if (($todayStats['missed'] ?? 0) > 0): ?>
                    Patient needs attention
                <?php else: ?>
                    All clear for today!
                <?php endif; ?>
            </div>
        </div>

        <!-- Schedule count — warm glass -->
        <div class="cgmed-schedule-count">
            <div style="width:40px;height:40px;border-radius:12px;background:rgba(249,116,71,0.15);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">📋</div>
            <div>
                <div class="cgmed-schedule-num"><?= count($medications) ?></div>
                <div class="cgmed-schedule-text">medications scheduled</div>
            </div>
        </div>

        <!-- Taken count — green glass -->
        <div class="cgmed-taken-count">
            <div style="width:40px;height:40px;border-radius:12px;background:rgba(34,197,94,0.12);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">✅</div>
            <div>
                <div class="cgmed-taken-num"><?= $todayStats['taken'] ?? 0 ?></div>
                <div class="cgmed-taken-text">doses taken today</div>
            </div>
        </div>

        <!-- Pending meds -->
        <?php
        $pendingCount = count(array_filter(
            $medications,
            fn($m) => !($loggedToday[$m['id']] ?? false)
        ));
        ?>
        <div style="
            background: rgba(255,255,255,0.05);
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: 22px;
            padding: 20px 22px;
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        " onmouseover="this.style.transform='translateY(-2px)'"
           onmouseout="this.style.transform='none'">
            <div style="width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">⏳</div>
            <div>
                <div style="font-family:'Cabinet Grotesk',sans-serif;font-weight:900;font-size:2.4rem;color:#ffe8d6;line-height:1;letter-spacing:-2px;">
                    <?= $pendingCount ?>
                </div>
                <div style="font-size:0.75rem;color:rgba(255,200,160,0.45);font-weight:600;">still pending</div>
            </div>
        </div>

    </div>
</div>

<!-- HISTORY TABLE — full width peach -->
<div class="cgmed-table-panel">
    <div class="cgmed-panel-label">
        Dose History — <?= htmlspecialchars($patient['name']) ?>
        <span style="margin-left:auto;font-size:0.7rem;font-weight:700;color:#b8927e;letter-spacing:0;text-transform:none;">Last 50 entries</span>
    </div>

    <?php if (empty($allLogs)): ?>
    <div class="cgmed-empty">
        <div class="cgmed-empty-icon">📜</div>
        <div class="cgmed-empty-text">No dose history yet.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cgmed-table">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Scheduled</th>
                    <th>Status</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allLogs as $log): ?>
                <tr>
                    <td class="cgmed-table-name"><?= htmlspecialchars($log['name']) ?></td>
                    <td class="cgmed-table-muted"><?= htmlspecialchars($log['dosage']) ?></td>
                    <td class="cgmed-table-muted"><?= date('h:i A', strtotime($log['schedule_time'])) ?></td>
                    <td>
                        <span class="cgmed-tpill <?= strtolower($log['status']) ?>">
                            <?= $log['status']==='Taken' ? '✅' : '❌' ?>
                            <?= $log['status'] ?>
                        </span>
                    </td>
                    <td class="cgmed-table-muted" style="white-space:nowrap;">
                        <?= date('M d, Y h:i A', strtotime($log['logged_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/caregiver_layout.php';
?>