<?php $csrfToken = $_SESSION['csrf_token']; ?>
<script>const CSRF = '<?= $csrfToken ?>';</script>
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

// ── Next due ───────────────────────────────────────────────
$nextDue = null; $nextDueUrgent = false; $nextDueMins = 0;
foreach ($medications as $med) {
    if ($loggedToday[$med['id']] ?? false) continue;
    $schedTs   = strtotime(date('Y-m-d') . ' ' . $med['schedule_time']);
    $minsUntil = ($schedTs - time()) / 60;
    if ($minsUntil >= 0 && ($nextDue === null || $schedTs < strtotime(date('Y-m-d') . ' ' . $nextDue['schedule_time']))) {
        $nextDue = $med; $nextDueUrgent = $minsUntil <= 30; $nextDueMins = (int)$minsUntil;
    }
}

// ── Sort medications by schedule time ────────────────────
usort($medications, function($a, $b) use ($loggedToday) {
    $aL = $loggedToday[$a['id']] ?? false;
    $bL = $loggedToday[$b['id']] ?? false;
    if ($aL !== $bL) return $aL ? 1 : -1;
    return strcmp($a['schedule_time'], $b['schedule_time']);
});

// Group siblings: same name+dosage+frequency = one card with multiple time slots.
// This fixes "Twice a day" appearing as two separate cards.
$medGroups = [];
foreach ($medications as $med) {
    $key = $med['name'] . '||' . $med['dosage'] . '||' . $med['frequency'];
    $medGroups[$key][] = $med;
}
foreach ($medGroups as &$slots) {
    usort($slots, fn($a, $b) => strcmp($a['schedule_time'], $b['schedule_time']));
}
unset($slots);

function getMedTimeBand(string $time): string {
    $hour = (int) date('H', strtotime('1970-01-01 ' . $time));
    if ($hour < 12) return 'morning';
    if ($hour < 17) return 'afternoon';
    if ($hour < 21) return 'evening';
    return 'night';
}
$timeBands = [
    'morning'   => ['label'=>'Morning',   'icon'=>'ti-sun',        'color'=>'#d97706','bg'=>'#fef3c7','range'=>'Before 12 PM','groups'=>[]],
    'afternoon' => ['label'=>'Afternoon', 'icon'=>'ti-sun-high',   'color'=>'#c04a20','bg'=>'#FDE8DC','range'=>'12-5 PM',     'groups'=>[]],
    'evening'   => ['label'=>'Evening',   'icon'=>'ti-sunset-2',   'color'=>'#6d28d9','bg'=>'#ede9fe','range'=>'5-9 PM',      'groups'=>[]],
    'night'     => ['label'=>'Night',     'icon'=>'ti-moon-stars', 'color'=>'#0e7490','bg'=>'#cffafe','range'=>'After 9 PM',  'groups'=>[]],
];
foreach ($medGroups as $groupKey => $slots) {
    $band = getMedTimeBand($slots[0]['schedule_time']);
    $timeBands[$band]['groups'][$groupKey] = $slots;
}

// ── History ──────────────────────────────────────────────
$logsByDate = [];
foreach ($allLogs as $l) {
    $d = date('Y-m-d', strtotime($l['logged_at']));
    $logsByDate[$d][] = $l;
}
krsort($logsByDate);
$totalLogs = count($allLogs);

// ── 7-day adherence heatmap ───────────────────────────────
$weekDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date    = date('Y-m-d', strtotime("-{$i} days"));
    $dayLogs = $logsByDate[$date] ?? [];
    $dayTk   = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Taken'));
    $dayMs   = count(array_filter($dayLogs, fn($l) => $l['status'] === 'Missed'));
    $total   = $dayTk + $dayMs;
    $pct     = $total > 0 ? round($dayTk / $total * 100) : null;
    $state   = $total === 0 ? 'none' : ($pct >= 80 ? 'good' : ($pct >= 50 ? 'warn' : 'bad'));
    $weekDays[] = [
        'date'    => $date,
        'label'   => date('D', strtotime($date)),
        'num'     => date('j', strtotime($date)),
        'taken'   => $dayTk,
        'missed'  => $dayMs,
        'total'   => $total,
        'pct'     => $pct,
        'state'   => $state,
        'isToday' => $date === date('Y-m-d'),
    ];
}

$medCssPath = dirname(dirname(dirname(__DIR__))) . '/public/assets/css/medication.css';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
<link href="/diabetrack/public/assets/css/medication.css?v=<?= file_exists($medCssPath) ? filemtime($medCssPath) : '1' ?>" rel="stylesheet">


<!-- ══ PAGE HEADER ══════════════════════════════════════ -->
<div class="med-page-header">
    <div class="med-page-header-left">
        <div class="med-page-eyebrow"><i class="ti ti-pill"></i> Medication Tracker</div>
        <h1 class="med-page-title">Daily <span>Schedule</span></h1>
        <p class="med-page-sub">Manage medications and track your dose adherence.</p>
    </div>
    <div class="med-page-header-right">
        <?php if ($nextDue): ?>
        <div class="med-nextdue-pill <?= $nextDueUrgent ? 'urgent' : '' ?>">
            <i class="ti <?= $nextDueUrgent ? 'ti-alarm' : 'ti-clock' ?>"></i>
            <?= $nextDueUrgent ? 'Due soon:' : 'Up next:' ?>
            <strong><?= htmlspecialchars($nextDue['name']) ?></strong>
            at <?= date('h:i A', strtotime($nextDue['schedule_time'])) ?>
            <?php if ($nextDueUrgent): ?> · <span class="med-nextdue-countdown"><?= $nextDueMins ?>min</span><?php endif; ?>
        </div>
        <?php elseif ($medTotal > 0 && $pendingToday === 0): ?>
        <div class="med-nextdue-pill done">
            <i class="ti ti-circle-check"></i>
            All <?= $medTotal ?> dose<?= $medTotal !== 1 ? 's' : '' ?> logged today
        </div>
        <?php endif; ?>
        <?php if ($totalLogs > 0): ?>
        <button class="med-history-btn" onclick="openHistoryDrawer()">
            <i class="ti ti-history"></i> Dose History
            <span class="med-history-count"><?= $totalLogs ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>


<!-- ══ TODAY'S OVERVIEW ════════════════════════════════ -->
<div class="med-overview">

    <!-- Adherence ring card -->
    <div class="med-overview-ring-card">
        <div class="med-overview-ring-wrap">
            <svg class="med-overview-svg" viewBox="0 0 80 80">
                <circle cx="40" cy="40" r="34" fill="none"
                    stroke="rgba(249,116,71,0.1)" stroke-width="7"/>
                <circle class="med-ring-fill" cx="40" cy="40" r="34" fill="none"
                    stroke="#F97447" stroke-width="7"
                    stroke-linecap="round"
                    stroke-dasharray="0 <?= round(2 * M_PI * 34, 2) ?>"
                    data-target="<?= $adherencePct !== null ? round(2 * M_PI * 34 * $adherencePct / 100, 2) : 0 ?>"
                    style="transform:rotate(-90deg);transform-origin:center;"/>
            </svg>
            <div class="med-overview-ring-center">
                <?php if ($adherencePct !== null): ?>
                <div class="med-overview-ring-val"><?= $adherencePct ?>%</div>
                <div class="med-overview-ring-lbl">adherence</div>
                <?php else: ?>
                <div class="med-overview-ring-val" style="font-size:1.2rem;">—</div>
                <div class="med-overview-ring-lbl">no data</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="med-overview-ring-info">
            <div class="med-overview-ring-title"><?= $medTotal ?> Medication<?= $medTotal !== 1 ? 's' : '' ?></div>
            <div class="med-overview-ring-sub">
                <?= $adherencePct !== null ? $adherencePct . '% overall adherence' : 'Start logging to track adherence' ?>
            </div>
        </div>
    </div>

    <!-- Today progress strip -->
    <div class="med-overview-today">
        <div class="med-overview-today-label">
            <i class="ti ti-calendar-check"></i> Today's Progress
        </div>

        <?php if ($medTotal > 0): ?>
        <!-- Segmented progress bar -->
        <div class="med-progress-bar-wrap">
            <div class="med-progress-bar">
                <?php if ($takenToday > 0): ?>
                <div class="med-progress-seg taken"
                     style="width:<?= round($takenToday  / $medTotal * 100) ?>%;"
                     title="<?= $takenToday ?> taken"></div>
                <?php endif; ?>
                <?php if ($missedToday > 0): ?>
                <div class="med-progress-seg missed"
                     style="width:<?= round($missedToday / $medTotal * 100) ?>%;"
                     title="<?= $missedToday ?> missed"></div>
                <?php endif; ?>
                <?php if ($pendingToday > 0): ?>
                <div class="med-progress-seg pending"
                     style="width:<?= round($pendingToday / $medTotal * 100) ?>%;"
                     title="<?= $pendingToday ?> pending"></div>
                <?php endif; ?>
            </div>
            <div class="med-progress-fraction"><?= $takenToday ?>/<?= $medTotal ?></div>
        </div>

        <!-- Dose count pills -->
        <div class="med-dose-count-row">
            <div class="med-dose-count taken">
                <div class="med-dose-count-icon"><i class="ti ti-circle-check"></i></div>
                <div>
                    <div class="med-dose-count-val"><?= $takenToday ?></div>
                    <div class="med-dose-count-lbl">Taken</div>
                </div>
            </div>
            <div class="med-dose-count missed">
                <div class="med-dose-count-icon"><i class="ti ti-circle-x"></i></div>
                <div>
                    <div class="med-dose-count-val"><?= $missedToday ?></div>
                    <div class="med-dose-count-lbl">Missed</div>
                </div>
            </div>
            <div class="med-dose-count pending">
                <div class="med-dose-count-icon"><i class="ti ti-clock"></i></div>
                <div>
                    <div class="med-dose-count-val"><?= $pendingToday ?></div>
                    <div class="med-dose-count-lbl">Pending</div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="med-dose-empty-hint">
            <i class="ti ti-pill"></i> No medications added yet
        </div>
        <?php endif; ?>
    </div>

    <!-- 7-day heatmap inline -->
    <div class="med-overview-heatmap">
        <div class="med-overview-heatmap-label">
            <i class="ti ti-calendar-week"></i> 7-Day Streak
        </div>
        <div class="med-streak-dots">
            <?php foreach ($weekDays as $day): ?>
            <div class="med-streak-dot state-<?= $day['state'] ?> <?= $day['isToday'] ? 'is-today' : '' ?>"
                 title="<?= $day['label'] . ' · ' . ($day['total'] > 0 ? $day['pct'] . '% adherence' : 'No data') ?>">
                <div class="med-streak-dot-inner"></div>
                <div class="med-streak-day-label"><?= $day['label'][0] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="med-streak-legend">
            <span class="med-streak-leg none"></span> None
            <span class="med-streak-leg good"></span> Good
            <span class="med-streak-leg warn"></span> Fair
            <span class="med-streak-leg bad"></span>  Poor
        </div>
    </div>

</div>


<!-- ══ SCHEDULE — grouped by time of day ══════════════ -->
<div class="med-schedule-card">
    <div class="med-schedule-header">
        <div class="med-section-label">
            <i class="ti ti-alarm"></i> Today's Schedule
            <span class="med-section-date">— <?= date('l, M d') ?></span>
        </div>
        <button class="med-add-btn" onclick="openModal('addModal')">
            <i class="ti ti-plus"></i> Add Medication
        </button>
    </div>

    <?php if (empty($medications)): ?>
    <div class="med-empty">
        <div class="med-empty-icon"><i class="ti ti-pill"></i></div>
        <p>No medications added yet.</p>
        <span>Tap the button to add your first medication.</span>
        <button class="med-empty-cta" onclick="openModal('addModal')">
            <i class="ti ti-plus"></i> Add Medication
        </button>
    </div>
    <?php else: ?>

    <?php foreach ($timeBands as $bandKey => $band):
        if (empty($band['groups'])) continue;
        $bandTaken = 0; $bandTotal = 0;
        foreach ($band['groups'] as $slots) {
            foreach ($slots as $m) {
                $bandTotal++;
                // Bug fix: ONLY match by exact ID — never by name — so sibling rows
                // don't contaminate each other when one of them is logged.
                if ($loggedToday[$m['id']] ?? false) $bandTaken++;
            }
        }
    ?>
    <!-- Time band section -->
    <div class="med-timeband" id="band-<?= $bandKey ?>">
        <div class="med-timeband-header">
            <div class="med-timeband-pill" style="background:<?= $band['bg'] ?>;color:<?= $band['color'] ?>;">
                <i class="ti <?= $band['icon'] ?>"></i>
                <?= $band['label'] ?>
                <span class="med-timeband-range"><?= $band['range'] ?></span>
            </div>
            <div class="med-timeband-progress">
                <span class="med-timeband-count"><?= $bandTaken ?>/<?= $bandTotal ?></span>
                <div class="med-timeband-bar">
                    <div class="med-timeband-bar-fill"
                         style="width:<?= $bandTotal > 0 ? round($bandTaken/$bandTotal*100) : 0 ?>%;
                                background:<?= $band['color'] ?>;">
                    </div>
                </div>
            </div>
        </div>

        <div class="med-schedule-list">
            <?php foreach ($band['groups'] as $groupSlots):
                // Representative med for display (name, dosage, frequency, edit/delete)
                $repMed    = $groupSlots[0];
                $isMulti   = count($groupSlots) > 1;
            ?>
            <?php if ($isMulti): ?>
            <!-- ── GROUPED CARD (e.g. Twice a day) ──────────────────────── -->
            <div class="med-schedule-item med-schedule-item--group state-pending"
                 id="med-item-<?= $repMed['id'] ?>">
                <div class="med-item-accent"></div>

                <!-- Time column shows slot count for grouped meds -->
                <div class="med-item-time">
                    <div class="med-item-time-val" style="font-size:0.7rem;line-height:1.2;">
                        <?= count($groupSlots) ?>x
                    </div>
                    <div class="med-item-time-period"><?= htmlspecialchars($repMed['frequency']) ?></div>
                </div>

                <div class="med-item-dot state-pending">
                    <i class="ti ti-pill"></i>
                </div>

                <div class="med-item-content">
                    <div class="med-item-name"><?= htmlspecialchars($repMed['name']) ?></div>
                    <div class="med-item-meta" style="margin-bottom:10px;">
                        <span><i class="ti ti-pill"></i> <?= htmlspecialchars($repMed['dosage']) ?></span>
                        <span><i class="ti ti-refresh"></i> <?= htmlspecialchars($repMed['frequency']) ?></span>
                    </div>
                    <!-- Individual slot rows -->
                    <div class="med-slots-list">
                        <?php foreach ($groupSlots as $slot):
                            // FIX: match ONLY by exact medication ID — never by name
                            $slotLogged = $loggedToday[$slot['id']] ?? false;
                            $slotStatus = null; $slotTime = null;
                            foreach ($todayLogs as $tl) {
                                if (($tl['medication_id'] ?? null) == $slot['id']) {
                                    $slotStatus = $tl['status']; $slotTime = $tl['logged_at']; break;
                                }
                            }
                            $slotTs    = strtotime(date('Y-m-d') . ' ' . $slot['schedule_time']);
                            $slotMins  = ($slotTs - time()) / 60;
                            if ($slotLogged)            $slotState = strtolower($slotStatus ?? 'taken');
                            elseif ($slotMins < -15)    $slotState = 'overdue';
                            elseif ($slotMins <= 30)    $slotState = 'due-soon';
                            else                        $slotState = 'pending';
                        ?>
                        <div class="med-slot-row">
                            <div class="med-slot-time">
                                <i class="ti ti-clock"></i>
                                <?= date('h:i A', strtotime($slot['schedule_time'])) ?>
                            </div>
                            <?php if ($slotLogged): ?>
                            <span class="med-logged-tag <?= strtolower($slotStatus ?? 'taken') ?> med-slot-tag">
                                <i class="ti <?= $slotStatus === 'Taken' ? 'ti-circle-check' : 'ti-circle-x' ?>"></i>
                                <?= $slotStatus ?? 'Taken' ?>
                                <?php if ($slotTime): ?>
                                <span class="med-slot-log-time">@ <?= date('h:i A', strtotime($slotTime)) ?></span>
                                <?php endif; ?>
                            </span>
                            <?php elseif ($slotState === 'overdue'): ?>
                            <span class="meta-overdue med-slot-overdue">
                                <i class="ti ti-alert-triangle"></i> Overdue <?= abs((int)$slotMins) ?>min
                            </span>
                            <?php elseif ($slotState === 'due-soon'): ?>
                            <span class="meta-urgent med-slot-overdue">
                                <i class="ti ti-alarm"></i> Due in <?= (int)$slotMins ?>min
                            </span>
                            <?php endif; ?>
                            <?php if (!$slotLogged): ?>
                            <div class="med-slot-actions">
                                <form method="POST" action="/diabetrack/public/patient/medication" class="med-log-form">
                                    <input type="hidden" name="action"  value="log">
                                    <input type="hidden" name="med_id"  value="<?= $slot['id'] ?>">
                                    <input type="hidden" name="status"  value="Taken">
                                    <button type="submit" class="med-action-btn btn-taken btn-sm">
                                        <i class="ti ti-check"></i> Taken
                                    </button>
                                </form>
                                <form method="POST" action="/diabetrack/public/patient/medication" class="med-log-form">
                                    <input type="hidden" name="action"  value="log">
                                    <input type="hidden" name="med_id"  value="<?= $slot['id'] ?>">
                                    <input type="hidden" name="status"  value="Missed">
                                    <button type="submit" class="med-action-btn btn-missed btn-sm">
                                        <i class="ti ti-x"></i> Missed
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="med-item-actions" style="align-self:flex-start;padding-top:4px;">
                    <button onclick='openEdit(<?= json_encode($repMed) ?>)' class="med-item-edit-btn" title="Edit">
                        <i class="ti ti-pencil"></i>
                    </button>
                    <button class="med-item-del-btn"
                            data-id="<?= $repMed['id'] ?>"
                            data-name="<?= htmlspecialchars($repMed['name'], ENT_QUOTES) ?>"
                            onclick="confirmDelete(this)" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>

            <?php else:
                // ── SINGLE SLOT CARD (unchanged original logic) ──────────────────
                $med       = $groupSlots[0];
                // FIX: match ONLY by exact medication ID — never by name
                $logged    = $loggedToday[$med['id']] ?? false;
                $logStatus = null; $logTime = null;
                foreach ($todayLogs as $tl) {
                    if (($tl['medication_id'] ?? null) == $med['id']) {
                        $logStatus = $tl['status']; $logTime = $tl['logged_at']; break;
                    }
                }
                $schedTs       = strtotime(date('Y-m-d') . ' ' . $med['schedule_time']);
                $itemMinsUntil = ($schedTs - time()) / 60;
                if ($logged)                    $state = strtolower($logStatus ?? 'taken');
                elseif ($itemMinsUntil < -15)   $state = 'overdue';
                elseif ($itemMinsUntil <= 30)   $state = 'due-soon';
                else                            $state = 'pending';
                $dotIcon = ['taken'=>'ti-circle-check','missed'=>'ti-circle-x','overdue'=>'ti-alert-triangle','due-soon'=>'ti-alarm','pending'=>'ti-clock'][$state] ?? 'ti-clock';
            ?>
            <div class="med-schedule-item state-<?= $state ?>" id="med-item-<?= $med['id'] ?>">
                <div class="med-item-accent"></div>

                <div class="med-item-time">
                    <div class="med-item-time-val"><?= date('h:i', strtotime($med['schedule_time'])) ?></div>
                    <div class="med-item-time-period"><?= date('A', strtotime($med['schedule_time'])) ?></div>
                </div>

                <div class="med-item-dot state-<?= $state ?>">
                    <i class="ti <?= $dotIcon ?>"></i>
                </div>

                <div class="med-item-content">
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
            <?php endif; // end single/multi branch ?>
            <?php endforeach; // end groups ?>
        </div>
    </div>
    <?php endforeach; // end timeBands ?>

    <?php endif; ?>
</div>


<!-- ══ HISTORY DRAWER ══════════════════════════════════ -->
<div class="bs-drawer-overlay" id="drawerOverlay" onclick="closeHistoryDrawer()"></div>
<div class="bs-drawer" id="historyDrawer" role="dialog" aria-label="Dose History" aria-modal="true">

    <div class="bs-drawer-header">
        <div class="bs-drawer-header-left">
            <div class="bs-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="bs-drawer-title">Dose History</div>
                <div class="bs-drawer-sub"><?= $totalLogs ?> total · <?= $totalTaken ?> taken · <?= $totalMissed ?> missed</div>
            </div>
        </div>
        <button class="bs-drawer-close" onclick="closeHistoryDrawer()"><i class="ti ti-x"></i></button>
    </div>

    <div class="bs-drawer-controls">
        <div class="bs-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search medication, dosage…" oninput="filterDrawer()">
        </div>
        <div class="bs-drawer-filters">
            <button class="bs-drawer-filter active" onclick="setDrawerFilter('all', this)">All</button>
            <button class="bs-drawer-filter" onclick="setDrawerFilter('Taken', this)">
                <span class="bs-df-dot" style="background:#22c55e;"></span> Taken
            </button>
            <button class="bs-drawer-filter" onclick="setDrawerFilter('Missed', this)">
                <span class="bs-df-dot" style="background:#ef4444;"></span> Missed
            </button>
        </div>
    </div>

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
            <div class="bs-drawer-day-header">
                <div class="bs-drawer-day-label">
                    <?= $dateLabel ?>
                    <?php if ($isDateToday): ?><span class="bs-today-chip">Today</span><?php endif; ?>
                </div>
                <div class="bs-drawer-day-stats">
                    <span><?= count($dayLogs) ?> dose<?= count($dayLogs) > 1 ? 's' : '' ?></span>
                    <?php if ($dayTaken  > 0): ?><span class="bs-drawer-day-flag" style="background:#d4f7e8;color:#0f7a45;"><?= $dayTaken ?> taken</span><?php endif; ?>
                    <?php if ($dayMissed > 0): ?><span class="bs-drawer-day-flag" style="background:#fde8e8;color:#dc2626;"><?= $dayMissed ?> missed</span><?php endif; ?>
                </div>
            </div>
            <div class="bs-timeline">
                <?php foreach ($dayLogs as $log):
                    $isTaken    = $log['status'] === 'Taken';
                    $statusCls  = $isTaken ? 'normal' : 'high';
                    $statusIcon = $isTaken ? 'ti-circle-check' : 'ti-circle-x';
                    $freqIcon   = match($log['frequency'] ?? 'Daily') {
                        'Twice a day','Three times a day' => 'ti-repeat',
                        'Weekly' => 'ti-calendar-week',
                        default  => 'ti-sun',
                    };
                ?>
                <div class="bs-timeline-item"
                     data-status="<?= $log['status'] ?>"
                     data-search="<?= strtolower(htmlspecialchars(($log['name'] ?? '') . ' ' . $log['status'] . ' ' . ($log['dosage'] ?? '') . ' ' . ($log['frequency'] ?? ''))) ?>">
                    <div class="bs-timeline-spine">
                        <div class="bs-timeline-dot <?= $statusCls ?>"><i class="ti <?= $statusIcon ?>"></i></div>
                        <div class="bs-timeline-line"></div>
                    </div>
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
                        <div class="bs-tl-meta">
                            <span class="bs-tl-type"><i class="ti ti-pill"></i> <?= htmlspecialchars($log['dosage'] ?? '') ?></span>
                            <?php if (!empty($log['schedule_time'])): ?>
                            <span class="bs-tl-type"><i class="ti ti-calendar-time"></i> Sched. <?= date('h:i A', strtotime($log['schedule_time'])) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($log['frequency'])): ?>
                            <span class="bs-tl-type"><i class="ti <?= $freqIcon ?>"></i> <?= htmlspecialchars($log['frequency']) ?></span>
                            <?php endif; ?>
                            <span class="bs-tl-status <?= $statusCls ?>"><?= $log['status'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="bs-drawer-footer">
        <div class="bs-drawer-no-results" id="drawerNoResults" style="display:none;">
            <i class="ti ti-search-off"></i> No results match your search.
        </div>
        <div class="bs-drawer-footer-stats">
            <span><i class="ti ti-circle-check" style="color:#22c55e;"></i> <?= $totalTaken ?> Taken</span>
            <span><i class="ti ti-circle-x"     style="color:#ef4444;"></i> <?= $totalMissed ?> Missed</span>
            <span><i class="ti ti-history"       style="color:#F97447;"></i> <?= $totalLogs ?> Total</span>
        </div>
    </div>
</div>


<!-- ══ ADD MODAL ══════════════════════════════════════ -->
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
                <div id="add-time-pickers" class="med-form-group"></div>
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

<!-- ══ EDIT MODAL ════════════════════════════════════ -->
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


<!-- ══ TOASTS ═════════════════════════════════════════ -->
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

<!-- ══ FAB ══════════════════════════════════════════ -->
<button class="patient-fab" onclick="openModal('addModal')" aria-label="Add medication">
    <span class="patient-fab-icon"><i class="ti ti-plus"></i></span>
    <span class="patient-fab-label">Add Medication</span>
</button>


<script>
// ── Frequency config ───────────────────────────────────
const FREQ_CONFIG = {
    'Daily':             { count:1, labels:['Morning dose'],                  defaults:['08:00'] },
    'Twice a day':       { count:2, labels:['Morning dose','Evening dose'],   defaults:['08:00','20:00'] },
    'Three times a day': { count:3, labels:['Morning','Afternoon','Evening'], defaults:['08:00','14:00','20:00'] },
    'Weekly':            { count:1, labels:['Weekly dose'],                   defaults:['08:00'] },
};

function updateTimePickers(prefix) {
    const freq   = document.getElementById(prefix + '-med-freq').value;
    const config = FREQ_CONFIG[freq] || FREQ_CONFIG['Daily'];
    const wrap   = document.getElementById(prefix + '-time-pickers');
    wrap.innerHTML = '';
    if (config.count === 1) {
        wrap.innerHTML = `
        <label class="med-form-label"><i class="ti ti-clock"></i> Schedule Time</label>
        <input type="time" name="schedule_time" class="med-form-input time-slot" value="${config.defaults[0]}" required>`;
    } else {
        const note = `<div class="med-freq-note"><i class="ti ti-info-circle"></i>${config.count} separate schedule entries will be created — one per dose time.</div>`;
        const grid = `<div class="med-time-grid">` +
            config.labels.map((label, i) => `
            <div class="med-time-slot-wrap">
                <label class="med-time-slot-label">${label}</label>
                <input type="time" name="schedule_time" class="med-form-input time-slot" value="${config.defaults[i]}" required data-slot="${i+1}">
            </div>`).join('') + `</div>`;
        wrap.innerHTML = note + grid;
    }
    updateAddPreview();
}

function updateAddPreview() {
    const name  = document.getElementById('add-med-name').value.trim();
    const dose  = document.getElementById('add-med-dosage').value.trim();
    const freq  = document.getElementById('add-med-freq').value;
    const times = [...document.querySelectorAll('#add-time-pickers .time-slot')]
                    .map(i => i.value ? new Date('1970-01-01T' + i.value).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}) : '?')
                    .filter(Boolean);
    const el = document.getElementById('add-preview-text');
    if (name && dose && times.length) {
        el.innerHTML = `<strong>${name}</strong> · ${dose} · ${freq}<br><span style="font-size:10px;color:#b8927e;">${times.join(' &nbsp;·&nbsp; ')}</span>`;
    } else {
        el.textContent = 'Fill in the fields above to preview your schedule.';
    }
}

async function handleAddSubmit(e) {
    e.preventDefault();
    const form   = document.getElementById('addForm');
    const name   = document.getElementById('add-med-name').value.trim();
    const dosage = document.getElementById('add-med-dosage').value.trim();
    const freq   = document.getElementById('add-med-freq').value;
    const times  = [...form.querySelectorAll('.time-slot')].map(i => i.value).filter(Boolean);
    if (!name || !dosage || times.length === 0) return;
    const btn = form.closest('.med-modal').querySelector('.med-save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2 ti-spin"></i> Saving…';
    try {
        for (const time of times) {
            const body = new URLSearchParams({ action:'add', name, dosage, schedule_time:time, frequency:freq });
            await fetch('/diabetrack/public/patient/medication', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
        }
        sessionStorage.setItem('med_saved_msg', times.length > 1 ? `${name} added — ${times.length} doses scheduled` : `${name} added to your schedule`);
        window.location.href = '/diabetrack/public/patient/medication';
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy"></i> Save Medication';
        alert('Something went wrong. Please try again.');
    }
    return false;
}

document.querySelectorAll('.med-log-form').forEach(form => {
    form.addEventListener('submit', () => {
        const status = form.querySelector('[name="status"]')?.value || '';
        const item   = form.closest('.med-schedule-item');
        const mname  = item?.querySelector('.med-item-name')?.textContent?.trim() || 'Medication';
        sessionStorage.setItem('med_saved_msg', status === 'Taken' ? `✓ ${mname} marked as Taken` : `${mname} marked as Missed`);
    });
});

document.getElementById('editForm').addEventListener('submit', () => {
    const name = document.getElementById('edit-med-name').value.trim() || 'Medication';
    sessionStorage.setItem('med_saved_msg', `${name} updated`);
});

document.addEventListener('DOMContentLoaded', () => {
    // Restore toast
    const msg = sessionStorage.getItem('med_saved_msg');
    if (msg) {
        sessionStorage.removeItem('med_saved_msg');
        document.getElementById('toastSavedMsg').textContent = msg;
        showToast('toastSaved');
    }
    updateTimePickers('add');

    // Animate adherence ring
    const fill = document.querySelector('.med-ring-fill');
    if (fill) {
        const target = parseFloat(fill.dataset.target) || 0;
        const circ   = 2 * Math.PI * 34;
        setTimeout(() => {
            fill.style.transition = 'stroke-dasharray 1.1s cubic-bezier(.4,0,.2,1)';
            fill.setAttribute('stroke-dasharray', `${target} ${circ}`);
        }, 200);
    }
});

function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
    if (id === 'addModal') {
        document.getElementById('addForm').reset();
        document.getElementById('add-med-freq').value = 'Daily';
        updateTimePickers('add');
    }
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }
function overlayClose(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }
function openEdit(med) {
    document.getElementById('edit-med-id').value     = med.id;
    document.getElementById('edit-med-name').value   = med.name;
    document.getElementById('edit-med-dosage').value = med.dosage;
    document.getElementById('edit-med-time').value   = med.schedule_time;
    document.getElementById('edit-med-freq').value   = med.frequency;
    document.getElementById('edit-preview-text').innerHTML = `<strong>${med.name}</strong> · ${med.dosage} · ${med.frequency}`;
    openModal('editModal');
}

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
function filterDrawer() { applyFilters(document.getElementById('drawerSearch').value.toLowerCase().trim(), drawerFilterActive); }
function setDrawerFilter(filter, btn) {
    drawerFilterActive = filter;
    document.querySelectorAll('.bs-drawer-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterDrawer();
}
function applyFilters(q, filter) {
    let visible = 0;
    document.querySelectorAll('.bs-timeline-item').forEach(item => {
        const ok = (filter === 'all' || item.dataset.status === filter) && (!q || item.dataset.search.includes(q));
        item.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.querySelectorAll('.bs-drawer-day-group').forEach(g => {
        g.style.display = [...g.querySelectorAll('.bs-timeline-item')].some(i => i.style.display !== 'none') ? '' : 'none';
    });
    document.getElementById('drawerNoResults').style.display = visible === 0 ? 'flex' : 'none';
}

function showToast(id, duration = 4500) {
    const t = document.getElementById(id);
    if (!t) return;
    t.classList.add('show');
    if (duration > 0) setTimeout(() => t.classList.remove('show'), duration);
}
function hideToast(id) { document.getElementById(id)?.classList.remove('show'); }

let deleteTimer = null, pendingDelete = null;
function confirmDelete(btn) {
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const item = btn.closest('.med-schedule-item');
    item.classList.add('med-item-deleting');
    document.getElementById('toastDeleteMsg').textContent = `"${name}" removed`;
    docume... (2 KB left)