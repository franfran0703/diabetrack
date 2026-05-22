<?php
$pageTitle  = 'Medication Tracker';
$activeMenu = 'medication';
ob_start();

// ── Computed stats ────────────────────────────────────────
$medTotal     = count($medications);
$takenToday   = (int)($todayStats['taken']  ?? 0);
$missedToday  = (int)($todayStats['missed'] ?? 0);
$pendingToday = max(0, $medTotal - $takenToday - $missedToday);

$totalTaken = 0; $totalMissed = 0;
foreach ($allLogs as $l) {
    if ($l['status'] === 'Taken')  $totalTaken++;
    if ($l['status'] === 'Missed') $totalMissed++;
}
$adherenceTotal = $totalTaken + $totalMissed;
$adherencePct   = $adherenceTotal > 0 ? round(($totalTaken / $adherenceTotal) * 100) : null;

$ringR      = 26;
$ringCircum = round(2 * M_PI * $ringR, 2);
$ringDash   = $adherencePct !== null ? round($ringCircum * ($adherencePct / 100), 2) : 0;

$nextDue = null; $nextDueUrgent = false; $nextDueMins = 0;
foreach ($medications as $med) {
    if ($loggedToday[$med['id']] ?? false) continue;
    $schedTs   = strtotime(date('Y-m-d') . ' ' . $med['schedule_time']);
    $minsUntil = ($schedTs - time()) / 60;
    if ($minsUntil >= 0 && ($nextDue === null || $schedTs < strtotime(date('Y-m-d') . ' ' . $nextDue['schedule_time']))) {
        $nextDue = $med; $nextDueUrgent = $minsUntil <= 30; $nextDueMins = (int)$minsUntil;
    }
}

usort($medications, function($a, $b) use ($loggedToday) {
    $aL = $loggedToday[$a['id']] ?? false;
    $bL = $loggedToday[$b['id']] ?? false;
    if ($aL !== $bL) return $aL ? 1 : -1;
    return strcmp($a['schedule_time'], $b['schedule_time']);
});

$logsByDate = [];
foreach ($allLogs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);
$totalLogs = count($allLogs);

$medCssPath = dirname(dirname(dirname(__DIR__))) . '/public/assets/css/medication.css';
?>

<link href="/diabetrack/public/assets/css/medication.css?v=<?= file_exists($medCssPath) ? filemtime($medCssPath) : '1' ?>" rel="stylesheet">

<!-- ══ PAGE HEADER ═══════════════════════════════════════ -->
<div class="med-page-header">
    <div class="med-page-header-left">
        <div class="med-page-eyebrow"><i class="ti ti-pill"></i> Medication Tracker</div>
        <h1 class="med-page-title">Daily <span>Schedule</span></h1>
        <p class="med-page-sub">Manage medications and track your dose adherence.</p>
    </div>
    <div class="med-page-header-right">
        <?php if ($nextDue): ?>
        <div class="med-next-due <?= $nextDueUrgent ? 'urgent' : '' ?>">
            <i class="ti <?= $nextDueUrgent ? 'ti-alarm' : 'ti-clock' ?>"></i>
            <div>
                <div class="med-next-due-label"><?= $nextDueUrgent ? 'Due soon!' : 'Up next' ?></div>
                <div class="med-next-due-name"><?= htmlspecialchars($nextDue['name']) ?></div>
                <div class="med-next-due-time">
                    <?= date('h:i A', strtotime($nextDue['schedule_time'])) ?> · <?= htmlspecialchars($nextDue['dosage']) ?>
                    <?php if ($nextDueUrgent): ?> · <strong><?= $nextDueMins ?>min</strong><?php endif; ?>
                </div>
            </div>
        </div>
        <?php elseif ($medTotal > 0 && $pendingToday === 0): ?>
        <div class="med-next-due done">
            <i class="ti ti-circle-check"></i>
            <div>
                <div class="med-next-due-label">All done!</div>
                <div class="med-next-due-name">All doses logged today</div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($totalLogs > 0): ?>
        <button class="bs-history-btn" onclick="openHistoryDrawer()">
            <i class="ti ti-history"></i> Dose History
            <span class="bs-history-count"><?= $totalLogs ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ══ STAT CARDS ═════════════════════════════════════════ -->
<div class="med-stats-grid">
    <div class="med-stat-card card-primary card-tall">
        <div class="med-stat-card-top">
            <div class="med-stat-icon-wrap"><i class="ti ti-pill"></i></div>
            <?php if ($adherencePct !== null): ?>
            <div class="med-adherence-ring">
                <svg viewBox="0 0 64 64" width="64" height="64" style="transform:rotate(-90deg);">
                    <circle cx="32" cy="32" r="<?= $ringR ?>" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="6"/>
                    <circle cx="32" cy="32" r="<?= $ringR ?>" fill="none" stroke="#fff" stroke-width="6"
                        stroke-linecap="round" stroke-dasharray="<?= $ringDash ?> <?= $ringCircum ?>"/>
                </svg>
                <div class="med-adherence-ring-label"><?= $adherencePct ?>%</div>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <div class="med-stat-val"><?= $medTotal ?></div>
            <div class="med-stat-label">medications scheduled</div>
            <div class="med-stat-badge">
                <i class="ti ti-calendar-check"></i>
                <?= $adherencePct !== null ? $adherencePct . '% adherence rate' : 'No history yet' ?>
            </div>
        </div>
    </div>
    <div class="med-stats-right">
        <div class="med-stat-card card-flat">
            <div class="med-flat-top">
                <div class="med-flat-icon icon-green"><i class="ti ti-circle-check"></i></div>
                <span class="med-flat-title">Taken</span>
            </div>
            <div class="med-flat-val"><?= $takenToday ?><small>/<?= $medTotal ?></small></div>
            <div class="med-flat-label">doses today</div>
            <?php if ($medTotal > 0): ?>
            <div class="med-flat-bar-track">
                <div class="med-flat-bar-fill green" style="width:<?= round($takenToday / max(1,$medTotal) * 100) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="med-stat-card card-flat">
            <div class="med-flat-top">
                <div class="med-flat-icon icon-red"><i class="ti ti-circle-x"></i></div>
                <span class="med-flat-title">Missed</span>
            </div>
            <div class="med-flat-val"><?= $missedToday ?><small>/<?= $medTotal ?></small></div>
            <div class="med-flat-label">doses today</div>
            <?php if ($medTotal > 0): ?>
            <div class="med-flat-bar-track">
                <div class="med-flat-bar-fill red" style="width:<?= round($missedToday / max(1,$medTotal) * 100) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="med-stat-card card-flat">
            <div class="med-flat-top">
                <div class="med-flat-icon icon-amber"><i class="ti ti-clock"></i></div>
                <span class="med-flat-title">Pending</span>
            </div>
            <div class="med-flat-val"><?= $pendingToday ?><small>/<?= $medTotal ?></small></div>
            <div class="med-flat-label">doses today</div>
            <?php if ($medTotal > 0): ?>
            <div class="med-flat-bar-track">
                <div class="med-flat-bar-fill amber" style="width:<?= round($pendingToday / max(1,$medTotal) * 100) ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ SCHEDULE CARD ══════════════════════════════════════ -->
<div class="med-schedule-card">
    <div class="med-section-header">
        <div class="med-section-label">
            <i class="ti ti-calendar-week"></i>
            Today's Schedule
            <span class="med-section-date">— <?= date('l, M d') ?></span>
        </div>
        <div class="med-schedule-legend">
            <div class="med-legend-item"><div class="med-legend-dot green"></div> Taken</div>
            <div class="med-legend-item"><div class="med-legend-dot red"></div> Missed</div>
            <div class="med-legend-item"><div class="med-legend-dot amber"></div> Pending</div>
        </div>
    </div>

    <?php if (empty($medications)): ?>
    <div class="med-empty">
        <i class="ti ti-pill" style="font-size:2.8rem;color:rgba(249,116,71,0.3);"></i>
        <p>No medications added yet.</p>
        <span>Tap the button below to add your first medication.</span>
        <button class="med-empty-cta" onclick="openModal('addModal')">
            <i class="ti ti-plus"></i> Add Medication
        </button>
    </div>
    <?php else: ?>
    <div class="med-schedule-list">
        <?php foreach ($medications as $med):
            $logged    = $loggedToday[$med['id']] ?? false;
            $logStatus = null; $logTime = null;
            foreach ($todayLogs as $tl) {
                if (($tl['medication_id'] ?? null) == $med['id'] || ($tl['name'] ?? '') === $med['name']) {
                    $logStatus = $tl['status']; $logTime = $tl['logged_at']; break;
                }
            }
            $schedTs       = strtotime(date('Y-m-d') . ' ' . $med['schedule_time']);
            $itemMinsUntil = ($schedTs - time()) / 60;
            if ($logged)                    $state = strtolower($logStatus ?? 'taken');
            elseif ($itemMinsUntil < -15)   $state = 'overdue';   // >15min past due
            elseif ($itemMinsUntil <= 30)   $state = 'due-soon';  // within 30min window
            else                            $state = 'pending';
            $dotIcon = ['taken'=>'ti-circle-check','missed'=>'ti-circle-x','overdue'=>'ti-alert-triangle','due-soon'=>'ti-alarm','pending'=>'ti-clock'][$state] ?? 'ti-clock';
        ?>
        <div class="med-schedule-item state-<?= $state ?>" id="med-item-<?= $med['id'] ?>">
            <div class="med-item-time">
                <div class="med-item-time-val"><?= date('h:i', strtotime($med['schedule_time'])) ?></div>
                <div class="med-item-time-period"><?= date('A', strtotime($med['schedule_time'])) ?></div>
            </div>
            <div class="med-item-dot state-<?= $state ?>"><i class="ti <?= $dotIcon ?>"></i></div>
            <div>
                <div class="med-item-name"><?= htmlspecialchars($med['name']) ?></div>
                <div class="med-item-meta">
                    <span><i class="ti ti-pill"></i> <?= htmlspecialchars($med['dosage']) ?></span>
                    <span><i class="ti ti-refresh"></i> <?= htmlspecialchars($med['frequency']) ?></span>
                    <?php if ($logged && $logTime): ?>
                    <span class="meta-logged"><i class="ti ti-circle-check"></i> Logged <?= date('h:i A', strtotime($logTime)) ?></span>
                    <?php elseif ($state === 'overdue'): ?>
                    <span class="meta-overdue"><i class="ti ti-alert-triangle"></i> Overdue by <?= abs((int)$itemMinsUntil) ?>min</span>
                    <?php elseif ($state === 'due-soon'): ?>
                    <span class="meta-urgent"><i class="ti ti-alarm"></i> Due in <?= (int)$itemMinsUntil ?>min</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="med-item-actions">
                <?php if (!$logged): ?>
                <form method="POST" action="/diabetrack/public/patient/medication" class="med-log-form">
                    <input type="hidden" name="action" value="log">
                    <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                    <input type="hidden" name="status" value="Taken">
                    <button type="submit" class="med-action-btn btn-taken"><i class="ti ti-check"></i> Taken</button>
                </form>
                <form method="POST" action="/diabetrack/public/patient/medication" class="med-log-form">
                    <input type="hidden" name="action" value="log">
                    <input type="hidden" name="med_id" value="<?= $med['id'] ?>">
                    <input type="hidden" name="status" value="Missed">
                    <button type="submit" class="med-action-btn btn-missed"><i class="ti ti-x"></i> Missed</button>
                </form>
                <?php else: ?>
                <span class="med-logged-tag <?= strtolower($logStatus ?? 'taken') ?>">
                    <i class="ti <?= $logStatus === 'Taken' ? 'ti-circle-check' : 'ti-circle-x' ?>"></i>
                    <?= $logStatus ?? 'Taken' ?>
                </span>
                <?php endif; ?>
                <button onclick='openEdit(<?= json_encode($med) ?>)' class="med-item-edit-btn" title="Edit">
                    <i class="ti ti-pencil"></i>
                </button>
                <button class="med-item-del-btn"
                        data-id="<?= $med['id'] ?>"
                        data-name="<?= htmlspecialchars($med['name'], ENT_QUOTES) ?>"
                        onclick="confirmDelete(this)" title="Delete">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>


<!-- ══ HISTORY DRAWER — mirrors blood sugar drawer exactly ═ -->
<div class="bs-drawer-overlay" id="drawerOverlay" onclick="closeHistoryDrawer()"></div>
<div class="bs-drawer" id="historyDrawer" role="dialog" aria-label="Dose History" aria-modal="true">

    <!-- Header -->
    <div class="bs-drawer-header">
        <div class="bs-drawer-header-left">
            <div class="bs-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="bs-drawer-title">Dose History</div>
                <div class="bs-drawer-sub"><?= $totalLogs ?> total · <?= $totalTaken ?> taken · <?= $totalMissed ?> missed</div>
            </div>
        </div>
        <button class="bs-drawer-close" onclick="closeHistoryDrawer()" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <!-- Search + filter — identical structure to blood sugar -->
    <div class="bs-drawer-controls">
        <div class="bs-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search medication, dosage…" oninput="filterDrawer()">
        </div>
        <div class="bs-drawer-filters">
            <button class="bs-drawer-filter active" data-df="all" onclick="setDrawerFilter('all', this)">All</button>
            <button class="bs-drawer-filter" data-df="Taken" onclick="setDrawerFilter('Taken', this)">
                <span class="bs-df-dot" style="background:#22c55e;"></span> Taken
            </button>
            <button class="bs-drawer-filter" data-df="Missed" onclick="setDrawerFilter('Missed', this)">
                <span class="bs-df-dot" style="background:#ef4444;"></span> Missed
            </button>
        </div>
    </div>

    <!-- Timeline body -->
    <div class="bs-drawer-body" id="drawerBody">
        <?php if (empty($allLogs)): ?>
        <div class="bs-drawer-empty">
            <i class="ti ti-pill"></i>
            <p>No dose history yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($logsByDate as $date => $dayLogs):
            $isDateToday = $date === date('Y-m-d');
            $isYesterday = $date === date('Y-m-d', strtotime('-1 day'));
            $dateLabel   = $isDateToday ? 'Today' : ($isYesterday ? 'Yesterday' : date('l, M j', strtotime($date)));
            $dayTaken    = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Taken'));
            $dayMissed   = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Missed'));
        ?>
        <div class="bs-drawer-day-group" data-date="<?= $date ?>">

            <!-- Date header -->
            <div class="bs-drawer-day-header">
                <div class="bs-drawer-day-label">
                    <?= $dateLabel ?>
                    <?php if ($isDateToday): ?>
                    <span class="bs-today-chip">Today</span>
                    <?php endif; ?>
                </div>
                <div class="bs-drawer-day-stats">
                    <span><?= count($dayLogs) ?> dose<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <?php if ($dayTaken  > 0): ?><span class="bs-drawer-day-flag" style="background:#d4f7e8;color:#0f7a45;"><?= $dayTaken ?> taken</span><?php endif; ?>
                    <?php if ($dayMissed > 0): ?><span class="bs-drawer-day-flag" style="background:#fde8e8;color:#dc2626;"><?= $dayMissed ?> missed</span><?php endif; ?>
                </div>
            </div>

            <!-- Timeline items — same structure as blood sugar -->
            <div class="bs-timeline">
                <?php foreach ($dayLogs as $log):
                    $isTaken   = $log['status'] === 'Taken';
                    $statusCls = $isTaken ? 'normal' : 'high'; // green=taken, red=missed
                    $statusIcon = $isTaken ? 'ti-circle-check' : 'ti-circle-x';
                    $freqIcon   = match($log['frequency'] ?? 'Daily') {
                        'Twice a day'       => 'ti-repeat',
                        'Three times a day' => 'ti-repeat',
                        'Weekly'            => 'ti-calendar-week',
                        default             => 'ti-sun',
                    };
                ?>
                <div class="bs-timeline-item"
                     data-status="<?= $log['status'] ?>"
                     data-search="<?= strtolower(htmlspecialchars(($log['name'] ?? '') . ' ' . $log['status'] . ' ' . ($log['dosage'] ?? '') . ' ' . ($log['frequency'] ?? ''))) ?>">

                    <!-- Spine -->
                    <div class="bs-timeline-spine">
                        <div class="bs-timeline-dot <?= $statusCls ?>">
                            <i class="ti <?= $statusIcon ?>"></i>
                        </div>
                        <div class="bs-timeline-line"></div>
                    </div>

                    <!-- Card -->
                    <div class="bs-timeline-card">
                        <div class="bs-timeline-card-top">
                            <div class="bs-timeline-val">
                                <span class="bs-tl-num" style="font-size:1.1rem;letter-spacing:-0.3px;">
                                    <?= htmlspecialchars($log['name'] ?? '—') ?>
                                </span>
                            </div>
                            <div class="bs-timeline-card-right">
                                <span class="bs-tl-time">
                                    <i class="ti ti-clock"></i>
                                    <?= date('h:i A', strtotime($log['logged_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Meta row -->
                        <div class="bs-tl-meta">
                            <span class="bs-tl-type">
                                <i class="ti ti-pill"></i>
                                <?= htmlspecialchars($log['dosage'] ?? '') ?>
                            </span>
                            <?php if (!empty($log['schedule_time'])): ?>
                            <span class="bs-tl-type">
                                <i class="ti ti-calendar-time"></i>
                                Sched. <?= date('h:i A', strtotime($log['schedule_time'])) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($log['frequency'])): ?>
                            <span class="bs-tl-type">
                                <i class="ti <?= $freqIcon ?>"></i>
                                <?= htmlspecialchars($log['frequency']) ?>
                            </span>
                            <?php endif; ?>
                            <span class="bs-tl-status <?= $statusCls ?>">
                                <?= $log['status'] ?>
                            </span>
                        </div>
                    </div>

                </div><!-- /.bs-timeline-item -->
                <?php endforeach; ?>
            </div><!-- /.bs-timeline -->

        </div><!-- /.bs-drawer-day-group -->
        <?php endforeach; ?>
        <?php endif; ?>
    </div><!-- /.bs-drawer-body -->

    <!-- Footer -->
    <div class="bs-drawer-footer">
        <div class="bs-drawer-no-results" id="drawerNoResults" style="display:none;">
            <i class="ti ti-search-off"></i> No results match your search.
        </div>
        <div class="bs-drawer-footer-stats">
            <span><i class="ti ti-circle-check" style="color:#22c55e;"></i> <?= $totalTaken ?> Taken</span>
            <span><i class="ti ti-circle-x" style="color:#ef4444;"></i> <?= $totalMissed ?> Missed</span>
            <span><i class="ti ti-history" style="color:#F97447;"></i> <?= $totalLogs ?> Total</span>
        </div>
    </div>

</div><!-- /.bs-drawer -->


<!-- ══ ADD MODAL ══════════════════════════════════════════ -->
<div id="addModal" class="med-modal-overlay" onclick="overlayClose(event,'addModal')">
    <div class="med-modal">
        <div class="med-modal-header">
            <div class="med-modal-header-left">
                <div class="med-modal-icon"><i class="ti ti-pill"></i></div>
                <div>
                    <div class="med-modal-title">Add Medication</div>
                    <div class="med-modal-sub">Set up a new medication in your schedule.</div>
                </div>
            </div>
            <button class="med-modal-close" onclick="closeModal('addModal')"><i class="ti ti-x"></i></button>
        </div>
        <div class="med-modal-body">
            <form method="POST" action="/diabetrack/public/patient/medication" id="addForm"
                  onsubmit="return handleAddSubmit(event)">
                <input type="hidden" name="action" value="add">

                <div class="med-form-group">
                    <label class="med-form-label"><i class="ti ti-pill"></i> Medication Name</label>
                    <input type="text" name="name" id="add-med-name" class="med-form-input" placeholder="e.g. Metformin" required>
                </div>
                <div class="med-form-group">
                    <label class="med-form-label"><i class="ti ti-weight"></i> Dosage</label>
                    <input type="text" name="dosage" id="add-med-dosage" class="med-form-input" placeholder="e.g. 500mg" required>
                </div>
                <div class="med-form-group">
                    <label class="med-form-label"><i class="ti ti-refresh"></i> Frequency</label>
                    <select name="frequency" id="add-med-freq" class="med-form-select" required onchange="updateTimePickers('add')">
                        <option value="Daily">Daily — once per day</option>
                        <option value="Twice a day">Twice a day — 2 doses</option>
                        <option value="Three times a day">Three times a day — 3 doses</option>
                        <option value="Weekly">Weekly — once per week</option>
                    </select>
                </div>

                <!-- Dynamic time pickers — rendered by JS based on frequency -->
                <div id="add-time-pickers" class="med-form-group"></div>

                <!-- Live preview -->
                <div class="med-form-preview" id="add-preview">
                    <i class="ti ti-info-circle"></i>
                    <span id="add-preview-text">Select a frequency to set schedule times.</span>
                </div>
            </form>
        </div>
        <div class="med-modal-footer">
            <button class="med-cancel-btn" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" form="addForm" class="med-save-btn">
                <i class="ti ti-device-floppy"></i> Save Medication
            </button>
        </div>
    </div>
</div>

<!-- ══ EDIT MODAL ═════════════════════════════════════════ -->
<div id="editModal" class="med-modal-overlay" onclick="overlayClose(event,'editModal')">
    <div class="med-modal">
        <div class="med-modal-header">
            <div class="med-modal-header-left">
                <div class="med-modal-icon"><i class="ti ti-pencil"></i></div>
                <div>
                    <div class="med-modal-title">Edit Medication</div>
                    <div class="med-modal-sub">Update your medication details.</div>
                </div>
            </div>
            <button class="med-modal-close" onclick="closeModal('editModal')"><i class="ti ti-x"></i></button>
        </div>
        <div class="med-modal-body">
            <form method="POST" action="/diabetrack/public/patient/medication" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="med_id" id="edit-med-id">

                <div class="med-form-group">
                    <label class="med-form-label"><i class="ti ti-pill"></i> Medication Name</label>
                    <input type="text" name="name" id="edit-med-name" class="med-form-input" placeholder="e.g. Metformin" required>
                </div>
                <div class="med-form-group">
                    <label class="med-form-label"><i class="ti ti-weight"></i> Dosage</label>
                    <input type="text" name="dosage" id="edit-med-dosage" class="med-form-input" placeholder="e.g. 500mg" required>
                </div>
                <div class="med-form-row med-form-group">
                    <div>
                        <label class="med-form-label"><i class="ti ti-clock"></i> Schedule Time</label>
                        <input type="time" name="schedule_time" id="edit-med-time" class="med-form-input" required>
                    </div>
                    <div>
                        <label class="med-form-label"><i class="ti ti-refresh"></i> Frequency</label>
                        <select name="frequency" id="edit-med-freq" class="med-form-select" required>
                            <option value="Daily">Daily</option>
                            <option value="Twice a day">Twice a day</option>
                            <option value="Three times a day">Three times a day</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>

                <div class="med-form-preview">
                    <i class="ti ti-info-circle"></i>
                    <span id="edit-preview-text">Editing: update the fields above.</span>
                </div>
            </form>
        </div>
        <div class="med-modal-footer">
            <button class="med-cancel-btn" onclick="closeModal('editModal')">Cancel</button>
            <button type="submit" form="editForm" class="med-save-btn">
                <i class="ti ti-device-floppy"></i> Update Medication
            </button>
        </div>
    </div>
</div>


<!-- ══ TOASTS ══════════════════════════════════════════════ -->
<div class="med-toast med-toast-success" id="toastSaved">
    <i class="ti ti-circle-check"></i>
    <span id="toastSavedMsg">Saved successfully</span>
    <button class="med-toast-close" onclick="hideToast('toastSaved')"><i class="ti ti-x"></i></button>
</div>
<div class="med-toast" id="toastDelete">
    <i class="ti ti-trash"></i>
    <span id="toastDeleteMsg">Medication removed</span>
    <button class="med-toast-undo" id="deleteUndo">Undo</button>
    <button class="med-toast-close" id="deleteClose"><i class="ti ti-x"></i></button>
</div>


<!-- ══ FAB ════════════════════════════════════════════════ -->
<button class="patient-fab" onclick="openModal('addModal')" aria-label="Add medication">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Add Medication</span>
</button>


<script>
// ══════════════════════════════════════════════════════════
// FREQUENCY → TIMES CONFIG
// Clinically correct: each frequency maps to N schedule slots
// with sensible defaults doctors actually prescribe
// ══════════════════════════════════════════════════════════
const FREQ_CONFIG = {
    'Daily':              { count: 1, labels: ['Morning dose'],                   defaults: ['08:00'] },
    'Twice a day':        { count: 2, labels: ['Morning dose', 'Evening dose'],    defaults: ['08:00','20:00'] },
    'Three times a day':  { count: 3, labels: ['Morning','Afternoon','Evening'],   defaults: ['08:00','14:00','20:00'] },
    'Weekly':             { count: 1, labels: ['Weekly dose'],                     defaults: ['08:00'] },
};

// ── Build time pickers for the Add modal ─────────────────
function updateTimePickers(prefix) {
    const freq    = document.getElementById(prefix + '-med-freq').value;
    const config  = FREQ_CONFIG[freq] || FREQ_CONFIG['Daily'];
    const wrap    = document.getElementById(prefix + '-time-pickers');
    wrap.innerHTML = '';

    if (config.count === 1) {
        // Single time — simple row
        wrap.innerHTML = `
        <label class="med-form-label"><i class="ti ti-clock"></i> Schedule Time</label>
        <input type="time" name="schedule_time" class="med-form-input time-slot"
               value="${config.defaults[0]}" required>`;
    } else {
        // Multiple times — labelled grid
        const note = `<div class="med-freq-note">
            <i class="ti ti-info-circle"></i>
            ${config.count} separate schedule entries will be created — one per dose time.
        </div>`;
        const grid = `<div class="med-time-grid">` +
            config.labels.map((label, i) => `
            <div class="med-time-slot-wrap">
                <label class="med-time-slot-label">${label}</label>
                <input type="time" name="schedule_time" class="med-form-input time-slot"
                       value="${config.defaults[i]}" required
                       data-slot="${i + 1}">
            </div>`).join('') +
            `</div>`;
        wrap.innerHTML = note + grid;
    }
    updateAddPreview();
}

function updateAddPreview() {
    const name   = document.getElementById('add-med-name').value.trim();
    const dose   = document.getElementById('add-med-dosage').value.trim();
    const freq   = document.getElementById('add-med-freq').value;
    const times  = [...document.querySelectorAll('#add-time-pickers .time-slot')]
                      .map(i => i.value ? new Date('1970-01-01T' + i.value).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : '?')
                      .filter(Boolean);
    const el     = document.getElementById('add-preview-text');

    if (name && dose && times.length) {
        el.innerHTML = `<strong>${name}</strong> · ${dose} · ${freq}<br>
            <span style="font-size:10px;color:#b8927e;">${times.join(' &nbsp;·&nbsp; ')}</span>`;
    } else {
        el.textContent = 'Fill in the fields above to preview your schedule.';
    }
}

// ── Handle Add submit — one POST per time slot ────────────
// Controller only accepts one schedule_time per call, so we submit
// once per dose time using fetch(), then redirect on completion.
async function handleAddSubmit(e) {
    e.preventDefault();
    const form   = document.getElementById('addForm');
    const name   = document.getElementById('add-med-name').value.trim();
    const dosage = document.getElementById('add-med-dosage').value.trim();
    const freq   = document.getElementById('add-med-freq').value;
    const times  = [...form.querySelectorAll('.time-slot')].map(i => i.value).filter(Boolean);

    if (!name || !dosage || times.length === 0) return;

    // Disable button to prevent double-submit
    const btn = form.closest('.med-modal').querySelector('.med-save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2 ti-spin"></i> Saving…';

    try {
        // Submit one fetch request per time slot
        for (const time of times) {
            const body = new URLSearchParams({
                action:        'add',
                name:          name,
                dosage:        dosage,
                schedule_time: time,
                frequency:     freq,
            });
            await fetch('/diabetrack/public/patient/medication', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
        }
        // Plant toast flag and redirect
        sessionStorage.setItem('med_saved_msg',
            times.length > 1
                ? `${name} added — ${times.length} doses scheduled`
                : `${name} added to your schedule`
        );
        window.location.href = '/diabetrack/public/patient/medication';
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy"></i> Save Medication';
        alert('Something went wrong. Please try again.');
    }
    return false;
}

// ── Taken / Missed log forms — plant toast before submit ──
document.querySelectorAll('.med-log-form').forEach(form => {
    form.addEventListener('submit', () => {
        const btn    = form.querySelector('button[type="submit"]');
        const status = form.querySelector('[name="status"]')?.value || '';
        // Find the med name from the nearest schedule item
        const item   = form.closest('.med-schedule-item');
        const mname  = item?.querySelector('.med-item-name')?.textContent?.trim() || 'Medication';
        sessionStorage.setItem('med_saved_msg',
            status === 'Taken'
                ? `✓ ${mname} marked as Taken`
                : `${mname} marked as Missed`
        );
    });
});

// Edit form — plant toast on submit
document.getElementById('editForm').addEventListener('submit', () => {
    const name = document.getElementById('edit-med-name').value.trim() || 'Medication';
    sessionStorage.setItem('med_saved_msg', `${name} updated`);
});

// ── Read toast on page load ──────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const msg = sessionStorage.getItem('med_saved_msg');
    if (msg) {
        sessionStorage.removeItem('med_saved_msg');
        document.getElementById('toastSavedMsg').textContent = msg;
        showToast('toastSaved');
    }
    // Initialise time pickers for add modal
    updateTimePickers('add');
});

// ── Modal open/close ─────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
    // Reset add modal pickers when opening
    if (id === 'addModal') {
        document.getElementById('addForm').reset();
        document.getElementById('add-med-freq').value = 'Daily';
        updateTimePickers('add');
    }
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function overlayClose(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}
function openEdit(med) {
    document.getElementById('edit-med-id').value     = med.id;
    document.getElementById('edit-med-name').value   = med.name;
    document.getElementById('edit-med-dosage').value = med.dosage;
    document.getElementById('edit-med-time').value   = med.schedule_time;
    document.getElementById('edit-med-freq').value   = med.frequency;
    document.getElementById('edit-preview-text').innerHTML =
        `<strong>${med.name}</strong> · ${med.dosage} · ${med.frequency}`;
    openModal('editModal');
}

// ── History drawer ────────────────────────────────────────
let drawerFilterActive = 'all';
function openHistoryDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeHistoryDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function filterDrawer() {
    applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), drawerFilterActive);
}
function setDrawerFilter(filter, btn) {
    drawerFilterActive = filter;
    document.querySelectorAll('.bs-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterDrawer();
}
function applyFilters(q, filter) {
    let visible = 0;
    document.querySelectorAll('.bs-timeline-item').forEach(item => {
        const ok = (filter === 'all' || item.dataset.status === filter)
                && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.bs-drawer-day-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.bs-timeline-item')]
            .some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = visible === 0 ? 'flex' : 'none';
}

// ── Toasts ────────────────────────────────────────────────
function showToast(id, duration = 4500) {
    const t = document.getElementById(id);
    if (!t) return;
    t.classList.add('show');
    if (duration > 0) setTimeout(() => t.classList.remove('show'), duration);
}
function hideToast(id) { document.getElementById(id)?.classList.remove('show'); }

// ── Delete with undo ──────────────────────────────────────
let deleteTimer = null, pendingDelete = null;
function confirmDelete(btn) {
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const item = btn.closest('.med-schedule-item');
    item.classList.add('med-item-deleting');
    document.getElementById('toastDeleteMsg').textContent = `"${name}" removed`;
    document.getElementById('deleteUndo').style.display = '';
    showToast('toastDelete', 0);
    pendingDelete = { id, item };
    clearTimeout(deleteTimer);
    deleteTimer = setTimeout(() => {
        window.location.href = '/diabetrack/public/patient/medication?delete=' + id;
    }, 5000);
}
document.getElementById('deleteUndo').addEventListener('click', () => {
    clearTimeout(deleteTimer);
    if (pendingDelete) { pendingDelete.item.classList.remove('med-item-deleting'); pendingDelete = null; }
    hideToast('toastDelete');
});
document.getElementById('deleteClose').addEventListener('click', () => {
    if (pendingDelete) { window.location.href = '/diabetrack/public/patient/medication?delete=' + pendingDelete.id; }
    hideToast('toastDelete'); clearTimeout(deleteTimer);
});

// ── Keyboard ──────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeModal('addModal');
        closeModal('editModal');
        closeHistoryDrawer();
    }
});

// ── Live preview listeners for add modal ─────────────────
document.getElementById('add-med-name')?.addEventListener('input', updateAddPreview);
document.getElementById('add-med-dosage')?.addEventListener('input', updateAddPreview);
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>