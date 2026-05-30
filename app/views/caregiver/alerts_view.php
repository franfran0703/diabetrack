<?php
$pageTitle  = 'Alerts & Messages';
$activeMenu = 'alerts';
ob_start();

$grouped = [];
foreach ($allAlerts as $alert) {
    $d = date('Y-m-d', strtotime($alert['created_at']));
    $grouped[$d][] = $alert;
}

function alertMeta(string $type): array {
    if (str_contains($type,'High') || (str_contains($type,'Sugar') && !str_contains($type,'Low')))
        return ['class'=>'high',   'icon'=>'ti-droplet-filled', 'badge'=>'High Sugar'];
    if (str_contains($type,'Low'))
        return ['class'=>'low',    'icon'=>'ti-alert-triangle',  'badge'=>'Low Sugar'];
    if (str_contains($type,'Missed') || str_contains($type,'Dose'))
        return ['class'=>'missed', 'icon'=>'ti-pill',             'badge'=>'Missed Dose'];
    return     ['class'=>'other',  'icon'=>'ti-bell',             'badge'=>$type];
}

$lowCount    = count(array_filter($allAlerts, fn($a) => str_contains($a['type'],'Low')));
$total       = $stats['total'];
$myId        = $_SESSION['user_id'];
$totalMsgs   = count($messages ?? []);
$unreadByMe  = count(array_filter($messages??[], fn($m) => $m['sender_type']==='patient' && !$m['read_at']));
$PREVIEW     = 6;

// Count per type for rail
$counts = ['all'=>$total,'high'=>$stats['high'],'low'=>$lowCount,'missed'=>$stats['missed'],'other'=>$stats['other'],'unread'=>$unreadCount];

$msgsByDate = [];
foreach ($messages ?? [] as $msg) {
    $d = date('Y-m-d', strtotime($msg['sent_at']));
    $msgsByDate[$d][] = $msg;
}
?>

<link href="<?= BASE_URL ?>/assets/css/caregiver_alerts.css?v=<?= time() ?>" rel="stylesheet">

<?php if (!$patient): ?>
<div class="cga-no-patient">
    <div class="cga-no-patient-icon"><i class="ti ti-link"></i></div>
    <div class="cga-no-patient-title">No Patient Linked</div>
    <div class="cga-no-patient-sub"><a href="<?= BASE_URL ?>/caregiver/patients">Link a patient</a> to view alerts.</div>
</div>
<?php else: ?>

<!-- ══ COMMAND BANNER ════════════════════════════════ -->
<div class="cga-banner">
    <div class="cga-banner-inner">

        <!-- Identity -->
        <div class="cga-banner-id">
            <div class="cga-banner-eyebrow"><i class="ti ti-bell"></i> Patient Monitor</div>
            <h1 class="cga-banner-title">Alerts <span>&amp; Chat</span></h1>
            <div class="cga-banner-date"><?= date('l, F j') ?></div>
        </div>

        <!-- Stat strip -->
        <div class="cga-banner-stats">
            <div class="cga-banner-stat bs-total">
                <div class="cga-bstat-icon total"><i class="ti ti-bell"></i></div>
                <div class="cga-bstat-num total"><?= $total ?></div>
                <div class="cga-bstat-lbl">Total Alerts</div>
                <?php if ($unreadCount > 0): ?>
                <div class="cga-unread-flash"><i class="ti ti-circle-dot"></i> <?= $unreadCount ?> unread</div>
                <?php else: ?>
                <div class="cga-bstat-sub">all reviewed</div>
                <?php endif; ?>
            </div>
            <div class="cga-banner-stat bs-high">
                <div class="cga-bstat-icon high"><i class="ti ti-droplet-filled"></i></div>
                <div class="cga-bstat-num high"><?= $stats['high'] ?></div>
                <div class="cga-bstat-lbl">High Sugar</div>
                <div class="cga-bstat-sub"><?= $stats['high']>0?'above 180':'all clear' ?></div>
            </div>
            <div class="cga-banner-stat bs-low">
                <div class="cga-bstat-icon low"><i class="ti ti-alert-triangle"></i></div>
                <div class="cga-bstat-num low"><?= $lowCount ?></div>
                <div class="cga-bstat-lbl">Low Sugar</div>
                <div class="cga-bstat-sub"><?= $lowCount>0?'below 70':'all clear' ?></div>
            </div>
            <div class="cga-banner-stat bs-missed">
                <div class="cga-bstat-icon missed"><i class="ti ti-pill"></i></div>
                <div class="cga-bstat-num missed"><?= $stats['missed'] ?></div>
                <div class="cga-bstat-lbl">Missed Doses</div>
                <div class="cga-bstat-sub"><?= $stats['missed']>0?'needs follow-up':'all taken' ?></div>
            </div>
            <div class="cga-banner-stat bs-msg">
                <div class="cga-bstat-icon msg"><i class="ti ti-message-circle"></i></div>
                <div class="cga-bstat-num msg"><?= $totalMsgs ?></div>
                <div class="cga-bstat-lbl">Messages</div>
                <div class="cga-bstat-sub"><?= $unreadByMe>0?$unreadByMe.' unread':'all read' ?></div>
            </div>
        </div>

        <!-- Patient -->
        <div class="cga-banner-patient">
            <div class="cga-banner-avatar"><?= strtoupper(substr($patient['name'],0,1)) ?></div>
            <div class="cga-banner-pname"><?= htmlspecialchars(ucwords(strtolower(explode(' ',$patient['name'])[0]))) ?></div>
            <div class="cga-banner-plabel">Linked Patient</div>
        </div>

    </div>
</div>

<!-- ══ TABS ══════════════════════════════════════════ -->
<div class="cga-tabs">
    <button class="cga-tab active" id="tab-alerts" onclick="switchTab('alerts')">
        <i class="ti ti-bell"></i> Alerts
        <?php if ($total > 0): ?><span class="cga-tab-badge"><?= $total ?></span><?php endif; ?>
    </button>
    <button class="cga-tab" id="tab-messages" onclick="switchTab('messages')">
        <i class="ti ti-message-circle"></i> Messages
        <?php if ($unreadByMe > 0): ?><span class="cga-tab-badge"><?= $unreadByMe ?></span><?php endif; ?>
    </button>
</div>

<!-- ══ ALERTS TAB ════════════════════════════════════ -->
<div class="cga-tab-panel active" id="panel-alerts">
<div class="cga-command">

    <!-- LEFT: Category rail -->
    <div class="cga-rail">
        <div class="cga-rail-label">Filter By</div>

        <?php
        $railItems = [
            ['key'=>'all',    'icon'=>'all',    'ti'=>'ti-bell',           'name'=>'All Alerts',   'count'=>$counts['all']],
            ['key'=>'high',   'icon'=>'high',   'ti'=>'ti-droplet-filled', 'name'=>'High Sugar',   'count'=>$counts['high']],
            ['key'=>'low',    'icon'=>'low',    'ti'=>'ti-alert-triangle', 'name'=>'Low Sugar',    'count'=>$counts['low']],
            ['key'=>'missed', 'icon'=>'missed', 'ti'=>'ti-pill',           'name'=>'Missed Dose',  'count'=>$counts['missed']],
            ['key'=>'other',  'icon'=>'other',  'ti'=>'ti-dots',           'name'=>'Other',        'count'=>$counts['other']],
        ];
        foreach ($railItems as $item): ?>
        <div class="cga-rail-item <?= $item['key']==='all'?'active':'' ?>"
             onclick="setRailFilter('<?= $item['key'] ?>',this)">
            <div class="cga-rail-icon <?= $item['icon'] ?>"><i class="ti <?= $item['ti'] ?>"></i></div>
            <div class="cga-rail-text">
                <div class="cga-rail-name"><?= $item['name'] ?></div>
                <div class="cga-rail-count"><?= $item['count'] ?> alert<?= $item['count']!==1?'s':'' ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="cga-rail-divider"></div>

        <div class="cga-rail-item <?= $unreadCount>0?'':'disabled' ?>"
             onclick="setRailFilter('unread',this)">
            <div class="cga-rail-icon unread"><i class="ti ti-circle-dot"></i></div>
            <div class="cga-rail-text">
                <div class="cga-rail-name">Unread</div>
                <div class="cga-rail-count"><?= $unreadCount ?> alert<?= $unreadCount!==1?'s':'' ?></div>
            </div>
            <?php if ($unreadCount > 0): ?>
            <span class="cga-rail-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- CENTER: Alert feed -->
    <div class="cga-feed">

        <!-- Search -->
        <div class="cga-feed-search">
            <i class="ti ti-search"></i>
            <input type="text" id="feedSearch" placeholder="Search alerts…" oninput="onFeedSearch(this)">
            <span class="cga-feed-search-count" id="feedCount"><?= $total ?> alert<?= $total!==1?'s':'' ?></span>
        </div>

        <?php if (empty($allAlerts)): ?>
        <div class="cga-feed-empty">
            <div class="cga-feed-empty-icon"><i class="ti ti-circle-check"></i></div>
            <div class="cga-feed-empty-title">All clear!</div>
            <div class="cga-feed-empty-sub">No alerts generated yet for this patient.</div>
        </div>
        <?php else:
            $globalIdx = 0;
            foreach ($grouped as $date => $dateAlerts):
                $isToday = date('Y-m-d') === $date;
                $isYest  = date('Y-m-d',strtotime('-1 day')) === $date;
                $dlabel  = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('F j, Y',strtotime($date)));
        ?>
        <div class="cga-date-sep" data-sep>
            <?= $dlabel ?>
            <?php if ($isToday): ?><span class="cga-today-chip">Today</span><?php endif; ?>
        </div>
        <?php foreach ($dateAlerts as $alert):
            $m         = alertMeta($alert['type']);
            $wasUnread = in_array($alert['id'], $unreadIds ?? []);
            $extraCls  = ($wasUnread ? ' unread' : '') . ($globalIdx >= $PREVIEW ? ' collapsed-alert' : '');
            $globalIdx++;
        ?>
        <div class="cga-alert-row <?= $m['class'].$extraCls ?>"
             data-class="<?= $m['class'] ?>"
             data-unread="<?= $wasUnread?'1':'0' ?>"
             data-search="<?= strtolower(htmlspecialchars($alert['type'].' '.$alert['message'])) ?>"
             style="animation-delay:<?= ($globalIdx-1)*.04 ?>s">

            <div class="cga-row-icon <?= $m['class'] ?>">
                <i class="ti <?= $m['icon'] ?>"></i>
            </div>

            <div class="cga-row-body">
                <div class="cga-row-head">
                    <span class="cga-row-type"><?= htmlspecialchars($alert['type']) ?></span>
                    <?php if ($wasUnread): ?>
                    <span class="cga-new-tag">New</span>
                    <span class="cga-unread-dot"></span>
                    <?php endif; ?>
                </div>
                <div class="cga-row-msg"><?= htmlspecialchars($alert['message']) ?></div>
            </div>

            <div class="cga-row-right">
                <div class="cga-row-time"><?= date('h:i A', strtotime($alert['created_at'])) ?></div>
                <button class="cga-row-reply"
                        onclick="switchTab('messages');prefillMsg(<?= htmlspecialchars(json_encode($alert['type'])) ?>)">
                    <i class="ti ti-message-circle"></i> Reply
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <?php if ($globalIdx > $PREVIEW): ?>
        <button class="cga-show-more" id="showMoreBtn" onclick="toggleShowMore(this)">
            <i class="ti ti-chevrons-down"></i>
            Show <?= $globalIdx - $PREVIEW ?> more alerts
        </button>
        <?php endif; ?>

        <div class="cga-no-match" id="noMatch">
            <i class="ti ti-search-off"></i> No alerts match your search.
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Action sidebar -->
    <div class="cga-actions">

        <?php if (!empty($allAlerts)): ?>
        <div class="cga-action-card">
            <div class="cga-action-eyebrow">Archive</div>
            <div class="cga-action-title">Full History</div>
            <button class="cga-history-btn" onclick="openDrawer()">
                <i class="ti ti-history"></i> View All
                <span class="cga-history-count"><?= $total ?></span>
            </button>
        </div>
        <?php endif; ?>

        <div class="cga-action-card">
            <div class="cga-action-eyebrow">Monitoring</div>
            <div class="cga-action-title">Alert Triggers</div>
            <div class="cga-trigger-list">
                <?php foreach ([
                    ['ti-droplet-filled', 'Blood sugar above 180 mg/dL'],
                    ['ti-alert-triangle',  'Blood sugar below 70 mg/dL'],
                    ['ti-pill',            'Medication not taken on time'],
                    ['ti-salad',           'Daily carb limit exceeded'],
                    ['ti-run',             'No activity logged all day'],
                    ['ti-calendar-event',  'Upcoming appointment'],
                ] as [$icon,$txt]): ?>
                <div class="cga-trigger-item">
                    <div class="cga-trigger-icon"><i class="ti <?= $icon ?>"></i></div>
                    <div class="cga-trigger-text"><?= $txt ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>
</div><!-- /#panel-alerts -->

<!-- ══ MESSAGES TAB ══════════════════════════════════ -->
<div class="cga-tab-panel" id="panel-messages">
<div class="cga-chat-wrap">

    <!-- Thread -->
    <div class="cga-chat-card">
        <div class="cga-chat-header">
            <div class="cga-chat-avatar"><?= strtoupper(substr($patient['name'],0,1)) ?></div>
            <div style="flex:1;">
                <div class="cga-chat-hname"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
                <div class="cga-chat-hstatus"><span class="cga-chat-dot"></span> Patient</div>
            </div>
            <button class="cga-chat-hbtn" title="View dashboard"
                    onclick="window.location='/caregiver/dashboard'">
                <i class="ti ti-layout-dashboard"></i>
            </button>
        </div>

        <div class="cga-thread" id="chatThread">
            <?php if (empty($messages)): ?>
            <div class="cga-chat-empty">
                <i class="ti ti-message-2-off"></i>
                <p>No messages yet.<br>Start the conversation below.</p>
            </div>
            <?php else:
                // Group consecutive same-sender messages together
                $groups = [];
                $prev   = null;
                foreach ($messages as $msg) {
                    $day    = date('Y-m-d', strtotime($msg['sent_at']));
                    $mine   = ((int)$msg['sender_id'] === (int)$myId);
                    $sender = $mine ? 'mine' : 'theirs';
                    // New group when sender changes or day changes
                    if ($prev === null || $prev['sender'] !== $sender || $prev['day'] !== $day) {
                        $groups[] = ['sender'=>$sender,'day'=>$day,'msgs'=>[]];
                    }
                    $groups[count($groups)-1]['msgs'][] = $msg;
                    $prev = ['sender'=>$sender,'day'=>$day];
                }

                $lastDay = null;
                $ptInit  = strtoupper(substr($patient['name'],0,1));
                foreach ($groups as $group):
                    // Day pill between groups when date changes
                    if ($group['day'] !== $lastDay):
                        $lastDay  = $group['day'];
                        $isToday  = date('Y-m-d') === $group['day'];
                        $isYest   = date('Y-m-d',strtotime('-1 day')) === $group['day'];
                        $dlabel   = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('M j', strtotime($group['day'])));
            ?>
            <div class="cga-chat-day"><?= $dlabel ?></div>
            <?php  endif; ?>
            <div class="cga-msg-group <?= $group['sender'] ?>">
                <?php
                    $count = count($group['msgs']);
                    foreach ($group['msgs'] as $gi => $msg):
                        $isFirst  = ($gi === 0);
                        $isLast   = ($gi === $count - 1);
                        $isOnly   = ($count === 1);
                        $isMid    = (!$isFirst && !$isLast);
                        $posCls   = $isOnly  ? 'is-only'
                                  : ($isFirst ? 'is-first'
                                  : ($isLast  ? 'is-last' : 'is-mid'));
                        $mine     = ($group['sender'] === 'mine');
                        $isRead   = !empty($msg['read_at']);
                        $sideCls  = $mine ? 'mine' : 'theirs';
                ?>
                <div class="cga-bubble-row <?= $sideCls ?> <?= $posCls ?>" data-id="<?= (int)$msg['id'] ?>">
                    <?php if (!$mine): ?>
                    <div class="cga-bavatar"><?= $ptInit ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="cga-bubble"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                        <div class="cga-bmeta">
                            <?= date('h:i A', strtotime($msg['sent_at'])) ?>
                            <?php if ($mine): ?>
                            <i class="ti ti-check<?= $isRead?'-checks':'' ?> cga-read-tick<?= $isRead?' read':'' ?>" style="font-size:11px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>

            <!-- Typing indicator -->
            <div class="cga-typing-indicator" id="typingIndicator">
                <div class="cga-bavatar"><?= strtoupper(substr($patient['name'],0,1)) ?></div>
                <div class="cga-typing-bubble">
                    <div class="cga-typing-dot"></div>
                    <div class="cga-typing-dot"></div>
                    <div class="cga-typing-dot"></div>
                </div>
            </div>
        </div>

        <div class="cga-chat-input-row">
            <div class="cga-chat-input-wrap">
                <textarea class="cga-chat-input" id="chatInput"
                    placeholder="Message <?= htmlspecialchars(explode(' ',$patient['name'])[0]) ?>…"
                    rows="1" maxlength="500"
                    onkeydown="handleChatKey(event)"
                    oninput="autoResize(this);onTyping()"></textarea>
            </div>
            <button class="cga-send-btn" id="chatSendBtn" onclick="sendChatMessage()">
                <i class="ti ti-send"></i>
            </button>
        </div>
    </div>

    <!-- Info panel -->
    <div class="cga-chat-info">
        <div class="cga-chat-info-card">
            <div class="cga-chat-info-avatar"><?= strtoupper(substr($patient['name'],0,1)) ?></div>
            <div class="cga-chat-info-name"><?= htmlspecialchars(ucwords(strtolower($patient['name']))) ?></div>
            <div class="cga-chat-info-sub">Your linked patient</div>
            <div class="cga-chat-stats">
                <div class="cga-chat-stat">
                    <div class="cga-chat-stat-num"><?= $totalMsgs ?></div>
                    <div class="cga-chat-stat-lbl">Messages</div>
                </div>
                <div class="cga-chat-stat">
                    <div class="cga-chat-stat-num" style="color:<?= $unreadByMe>0?'#f87171':'#4ade80' ?>"><?= $unreadByMe ?></div>
                    <div class="cga-chat-stat-lbl">Unread</div>
                </div>
            </div>
            <div class="cga-quick-label">Quick Messages</div>
            <div class="cga-quick-list">
                <?php foreach ([
                    ['ti-heart',          "How are you feeling today?"],
                    ['ti-pill',           "Don't forget your medication."],
                    ['ti-droplet-filled', "Please log your blood sugar."],
                    ['ti-run',            "Great job staying active!"],
                    ['ti-salad',          "Watch your carb intake today."],
                    ['ti-calendar-event', "You have an upcoming appointment."],
                ] as [$icon,$txt]): ?>
                <button class="cga-quick-item" onclick="setQuickMsg(<?= htmlspecialchars(json_encode($txt)) ?>)">
                    <i class="ti <?= $icon ?>"></i><?= $txt ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
</div><!-- /#panel-messages -->


<!-- ══ HISTORY DRAWER ════════════════════════════════ -->
<div class="cga-drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
<div class="cga-drawer" id="historyDrawer" role="dialog">
    <div class="cga-drawer-header">
        <div class="cga-drawer-header-left">
            <div class="cga-drawer-icon"><i class="ti ti-history"></i></div>
            <div>
                <div class="cga-drawer-title">Full Alert History</div>
                <div class="cga-drawer-sub"><?= $total ?> total &middot; <?= $unreadCount ?> were unread</div>
            </div>
        </div>
        <button class="cga-drawer-close" onclick="closeDrawer()"><i class="ti ti-x"></i></button>
    </div>
    <div class="cga-drawer-controls">
        <div class="cga-drawer-search">
            <i class="ti ti-search"></i>
            <input type="text" id="drawerSearch" placeholder="Search alerts…" oninput="filterDrawer()">
        </div>
        <div class="cga-drawer-filters">
            <button class="cga-drawer-filter active" onclick="setDrawerFilter('all',this)">All</button>
            <button class="cga-drawer-filter" onclick="setDrawerFilter('high',this)"><span style="width:7px;height:7px;border-radius:50%;background:#ef4444;display:inline-block;"></span> High</button>
            <button class="cga-drawer-filter" onclick="setDrawerFilter('low',this)"><span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;display:inline-block;"></span> Low</button>
            <button class="cga-drawer-filter" onclick="setDrawerFilter('missed',this)"><span style="width:7px;height:7px;border-radius:50%;background:#f97447;display:inline-block;"></span> Missed</button>
            <button class="cga-drawer-filter" onclick="setDrawerFilter('unread',this)">Unread</button>
        </div>
    </div>
    <div class="cga-drawer-body">
        <?php if (empty($allAlerts)): ?>
        <div class="cga-drawer-empty"><i class="ti ti-bell-off"></i><p>No alerts yet.</p></div>
        <?php else:
            foreach ($grouped as $date => $dateAlerts):
                $isToday = date('Y-m-d')===$date;
                $isYest  = date('Y-m-d',strtotime('-1 day'))===$date;
                $dlabel  = $isToday?'Today':($isYest?'Yesterday':date('l, M j',strtotime($date)));
        ?>
        <div class="cga-dtl-group" data-date="<?= $date ?>">
            <div class="cga-dtl-day">
                <?= $dlabel ?>
                <?php if ($isToday): ?><span class="cga-today-chip">Today</span><?php endif; ?>
                <span class="cga-dtl-day-sub"><?= count($dateAlerts) ?> alert<?= count($dateAlerts)>1?'s':'' ?></span>
            </div>
            <?php foreach ($dateAlerts as $alert):
                $m = alertMeta($alert['type']);
                $wasUnread = in_array($alert['id'], $unreadIds ?? []);
            ?>
            <div class="cga-dtl-item"
                 data-class="<?= $m['class'] ?>"
                 data-unread="<?= $wasUnread?'1':'0' ?>"
                 data-search="<?= strtolower($alert['type'].' '.$alert['message']) ?>">
                <div class="cga-dtl-spine">
                    <div class="cga-dtl-dot <?= $m['class'] ?>"><i class="ti <?= $m['icon'] ?>"></i></div>
                    <div class="cga-dtl-line"></div>
                </div>
                <div class="cga-dtl-card <?= $wasUnread?'unread':'' ?>">
                    <div class="cga-dtl-card-top">
                        <span class="cga-dtl-type <?= $m['class'] ?>"><?= htmlspecialchars($alert['type']) ?></span>
                        <span class="cga-dtl-time"><i class="ti ti-clock"></i><?= date('h:i A',strtotime($alert['created_at'])) ?></span>
                    </div>
                    <div class="cga-dtl-msg"><?= htmlspecialchars($alert['message']) ?></div>
                    <span class="cga-dtl-read <?= $wasUnread?'unread':'read' ?>">
                        <i class="ti ti-<?= $wasUnread?'clock':'check' ?>"></i>
                        <?= $wasUnread?'Was unread':'Read' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <div class="cga-drawer-no-results" id="drawerNoResults"><i class="ti ti-search-off"></i> Nothing matches.</div>
    </div>
    <div class="cga-drawer-footer">
        <div class="cga-drawer-footer-stats">
            <span><i class="ti ti-bell" style="color:#fbab6e;"></i> <?= $total ?> total</span>
            <span><i class="ti ti-circle-dot" style="color:#f97447;"></i> <?= $unreadCount ?> unread</span>
            <span><i class="ti ti-droplet-filled" style="color:#f87171;"></i> <?= $stats['high'] ?> high</span>
            <span><i class="ti ti-pill" style="color:#fbab6e;"></i> <?= $stats['missed'] ?> missed</span>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const CID = <?= (int)$_SESSION['user_id'] ?>;
const PID = <?= (int)($patient['id'] ?? 0) ?>;
let lastMsgId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;

// ── Tabs ─────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.cga-tab-panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.cga-tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('panel-'+tab).classList.add('active');
    document.getElementById('tab-'+tab).classList.add('active');
    if (tab==='messages') setTimeout(scrollBottom, 60);
}

// ── Rail filter ──────────────────────────────────────
let railFilter = 'all';
function setRailFilter(f, el) {
    railFilter = f;
    document.querySelectorAll('.cga-rail-item').forEach(i=>i.classList.remove('active'));
    el.classList.add('active');
    applyFeedFilters();
}

// ── Feed search ──────────────────────────────────────
function onFeedSearch() { applyFeedFilters(); }

function applyFeedFilters() {
    const q = (document.getElementById('feedSearch').value||'').toLowerCase().trim();
    let vis = 0;
    document.querySelectorAll('.cga-alert-row:not(.collapsed-alert)').forEach(row => {
        if (row.classList.contains('collapsed-alert') && !showingAll) return;
        const cls    = row.dataset.class;
        const unread = row.dataset.unread==='1';
        const search = row.dataset.search||'';
        const ok = (railFilter==='all'||railFilter===cls||(railFilter==='unread'&&unread))
                && (!q||search.includes(q));
        row.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    // Also apply to collapsed
    document.querySelectorAll('.cga-alert-row.collapsed-alert').forEach(row => {
        if (!showingAll) { row.style.display='none'; return; }
        const cls    = row.dataset.class;
        const unread = row.dataset.unread==='1';
        const search = row.dataset.search||'';
        const ok = (railFilter==='all'||railFilter===cls||(railFilter==='unread'&&unread))
                && (!q||search.includes(q));
        row.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    // hide date separators if all their alerts are hidden
    document.querySelectorAll('[data-sep]').forEach(sep => {
        let sibling = sep.nextElementSibling;
        let hasVisible = false;
        while (sibling && !sibling.hasAttribute('data-sep')) {
            if (sibling.classList.contains('cga-alert-row') && sibling.style.display!=='none') hasVisible=true;
            sibling = sibling.nextElementSibling;
        }
        sep.style.display = hasVisible ? '' : 'none';
    });
    const countEl = document.getElementById('feedCount');
    if (countEl) countEl.textContent = vis + ' alert' + (vis!==1?'s':'');
    const nm = document.getElementById('noMatch');
    if (nm) nm.style.display = (vis===0 && <?= $total ?> > 0) ? 'flex' : 'none';
}

// ── Show more ────────────────────────────────────────
let showingAll = false;
function toggleShowMore(btn) {
    showingAll = !showingAll;
    document.querySelectorAll('.collapsed-alert').forEach(el => {
        el.style.display = showingAll ? '' : 'none';
    });
    btn.innerHTML = showingAll
        ? '<i class="ti ti-chevrons-up"></i> Collapse'
        : '<i class="ti ti-chevrons-down"></i> Show <?= max(0,$globalIdx-$PREVIEW) ?> more alerts';
    applyFeedFilters();
}

// ── Drawer ───────────────────────────────────────────
let drawerFilter = 'all';
function openDrawer() {
    document.getElementById('historyDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('show');
    document.body.style.overflow='hidden';
}
function closeDrawer() {
    document.getElementById('historyDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('show');
    document.body.style.overflow='';
}
function filterDrawer() {
    applyDrawer((document.getElementById('drawerSearch').value||'').toLowerCase().trim(), drawerFilter);
}
function setDrawerFilter(f,btn) {
    drawerFilter=f;
    document.querySelectorAll('.cga-drawer-filter').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    applyDrawer((document.getElementById('drawerSearch').value||'').toLowerCase().trim(), f);
}
function applyDrawer(q,f) {
    let vis=0;
    document.querySelectorAll('.cga-dtl-item').forEach(el=>{
        const ok=(f==='all'||f===el.dataset.class||(f==='unread'&&el.dataset.unread==='1'))
                &&(!q||el.dataset.search.includes(q));
        el.style.display=ok?'':'none'; if(ok)vis++;
    });
    document.querySelectorAll('.cga-dtl-group').forEach(g=>{
        g.style.display=[...g.querySelectorAll('.cga-dtl-item')].some(i=>i.style.display!=='none')?'':'none';
    });
    document.getElementById('drawerNoResults').style.display=vis===0?'flex':'none';
}

// ── Chat ─────────────────────────────────────────────
const PT_INIT = '<?= strtoupper(substr($patient["name"]??'P',0,1)) ?>';

function scrollBottom() {
    const t = document.getElementById('chatThread');
    if (t) t.scrollTop = t.scrollHeight;
}
function autoResize(ta) {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
}
function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
}
function setQuickMsg(txt) {
    const ta = document.getElementById('chatInput');
    ta.value = txt; autoResize(ta); ta.focus();
}
function prefillMsg(alertType) {
    switchTab('messages');
    setTimeout(() => setQuickMsg('Re: ' + alertType + ' — '), 80);
}
function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

/* Regroup all bubble-rows in the thread so consecutive same-sender
   rows get correct is-first/is-mid/is-last/is-only + shape classes */
function regroup() {
    const thread = document.getElementById('chatThread');
    const groups  = thread.querySelectorAll('.cga-msg-group');
    const POS = ['is-only','is-first','is-mid','is-last'];

    groups.forEach(g => {
        const rows = [...g.querySelectorAll('.cga-bubble-row')];
        const n = rows.length;
        rows.forEach((row, i) => {
            // Strip old position classes
            row.classList.remove(...POS);
            if (n === 1) row.classList.add('is-only');
            else if (i === 0) row.classList.add('is-first');
            else if (i === n - 1) row.classList.add('is-last');
            else row.classList.add('is-mid');
        });
    });
}

/* Append a new bubble, either into an existing last group (same sender)
   or create a new group */
function appendBubble(body, time, mine, isRead, msgId) {
    const thread  = document.getElementById('chatThread');
    thread.querySelector('.cga-chat-empty')?.remove();

    const typer   = document.getElementById('typingIndicator');
    const sideCls = mine ? 'mine' : 'theirs';

    // Find the last msg-group before the typing indicator
    let lastGroup = null;
    const allGroups = thread.querySelectorAll('.cga-msg-group');
    if (allGroups.length) lastGroup = allGroups[allGroups.length - 1];

    // Reuse group if same sender, else create a new one
    let group;
    if (lastGroup && lastGroup.classList.contains(sideCls)) {
        group = lastGroup;
    } else {
        group = document.createElement('div');
        group.className = 'cga-msg-group ' + sideCls;
        thread.insertBefore(group, typer);
    }

    // Build the row
    const tick = mine
        ? `<i class="ti ti-check${isRead?'-checks':''} cga-read-tick${isRead?' read':''}" style="font-size:11px;"></i>`
        : '';
    const avatar = !mine ? `<div class="cga-bavatar">${PT_INIT}</div>` : '';

    const row = document.createElement('div');
    row.className = 'cga-bubble-row ' + sideCls;
    if (msgId) row.dataset.id = String(msgId);
    row.innerHTML = `${avatar}<div>
        <div class="cga-bubble">${escHtml(body)}</div>
        <div class="cga-bmeta">${time} ${tick}</div>
    </div>`;
    group.appendChild(row);

    regroup();
    thread.scrollTop = thread.scrollHeight;
    if (msgId && +msgId > lastMsgId) lastMsgId = +msgId;
}

function sendChatMessage() {
    const ta  = document.getElementById('chatInput');
    const msg = ta.value.trim();
    if (!msg) { ta.focus(); return; }
    const btn = document.getElementById('chatSendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2"></i>';
    appendBubble(
        msg,
        new Date().toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' }),
        true, false, null
    );
    ta.value = ''; autoResize(ta);
    fetch('/caregiver/sendMessage', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(d => { if (d.ok && d.id) lastMsgId = Math.max(lastMsgId, +d.id); })
    .catch(() => {})
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i>'; });
}

// Typing signal
let typingTimer = null;
function onTyping() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        fetch('/caregiver/setTyping', { method: 'POST' }).catch(() => {});
    }, 400);
}

// Poll every 5 seconds
setInterval(() => {
    if (!document.getElementById('panel-messages').classList.contains('active')) return;
    fetch('/caregiver/getMessages?after=' + lastMsgId)
    .then(r => r.json())
    .then(data => {
        const ti = document.getElementById('typingIndicator');
        if (ti) ti.classList.toggle('show', !!data.typing);

        if (data.messages?.length) {
            data.messages.forEach(m => {
                if (m.sender_type !== 'caregiver') {
                    appendBubble(m.body, m.sent_at, false, false, +m.id);
                }
            });
        }
        // Update read ticks on caregiver's own messages
        if (data.readUpto) {
            document.querySelectorAll('.cga-bubble-row.mine').forEach(row => {
                const id = +row.dataset.id;
                if (id && id <= data.readUpto) {
                    const tick = row.querySelector('.cga-read-tick');
                    if (tick && !tick.classList.contains('read')) {
                        tick.classList.add('read');
                        tick.classList.remove('ti-check');
                        tick.classList.add('ti-check-checks');
                    }
                }
            });
        }
    })
    .catch(() => {});
}, 5000);

window.addEventListener('load', () => { regroup(); scrollBottom(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
</script>

<?php
$content=ob_get_clean();
require_once __DIR__.'/../shared/caregiver_layout.php';
?>