<?php
$pageTitle  = 'Messages';
$activeMenu = 'messages';
ob_start();

$myId      = $_SESSION['user_id'];
$totalMsgs = count($messages ?? []);
$unreadCG  = count(array_filter($messages ?? [], fn($m) =>
    $m['sender_type'] === 'caregiver' && !$m['read_at']
));

// Group messages by date
$byDate = [];
foreach ($messages ?? [] as $m) {
    $d = date('Y-m-d', strtotime($m['sent_at']));
    $byDate[$d][] = $m;
}

$lastId = !empty($messages) ? (int)end($messages)['id'] : 0;
$cgInit = $caregiver ? strtoupper(substr($caregiver['name'], 0, 1)) : 'C';
$cgFirst = $caregiver ? htmlspecialchars(explode(' ', ucwords(strtolower($caregiver['name'])))[0]) : 'Caregiver';
?>

<link href="<?= BASE_URL ?>/assets/css/messages.css?v=<?= time() ?>" rel="stylesheet">

<!-- ── Header ── -->
<div class="ptm-header">
    <div>
        <div class="ptm-eyebrow"><i class="ti ti-message-circle"></i> Direct Chat</div>
        <h1 class="ptm-title">Your <span>Messages</span></h1>
        <p class="ptm-sub">Chat directly with your caregiver</p>
    </div>
</div>

<?php if (!$caregiver): ?>
<!-- No caregiver -->
<div class="ptm-empty">
    <i class="ti ti-user-off"></i>
    <div class="ptm-empty-title">No caregiver linked yet</div>
    <div class="ptm-empty-sub">Once a caregiver sends a request and you
        <a href="<?= BASE_URL ?>/patient/caregiverRequests">accept it</a>,
        you can message each other here.</div>
</div>

<?php else: ?>

<div class="ptm-layout">

    <!-- ── Chat window ── -->
    <div class="ptm-chat">

        <!-- Header -->
        <div class="ptm-chat-header">
            <div class="ptm-cg-avatar"><?= $cgInit ?></div>
            <div style="flex:1;">
                <div class="ptm-cg-name">
                    <?= htmlspecialchars(ucwords(strtolower($caregiver['name']))) ?>
                </div>
                <div class="ptm-cg-role">
                    <span class="ptm-cg-dot"></span> Your Caregiver
                </div>
            </div>
        </div>

        <!-- Thread -->
        <div class="ptm-thread" id="thread">
            <?php if (empty($messages)): ?>
            <div class="ptm-empty-thread">
                <i class="ti ti-message-2-off"></i>
                <p>No messages yet.<br>Say hello to your caregiver!</p>
            </div>
            <?php else:
                // Group consecutive same-sender messages
                $groups  = [];
                $prevSdr = null;
                $prevDay = null;
                foreach ($messages as $msg) {
                    $day    = date('Y-m-d', strtotime($msg['sent_at']));
                    $mine   = ((int)$msg['sender_id'] === $myId);
                    $sender = $mine ? 'mine' : 'theirs';
                    if ($prevSdr !== $sender || $prevDay !== $day) {
                        $groups[] = ['sender'=>$sender,'day'=>$day,'msgs'=>[]];
                    }
                    $groups[count($groups)-1]['msgs'][] = $msg;
                    $prevSdr = $sender; $prevDay = $day;
                }
                $lastDay = null;
                foreach ($groups as $group):
                    if ($group['day'] !== $lastDay):
                        $lastDay = $group['day'];
                        $isToday = date('Y-m-d') === $group['day'];
                        $isYest  = date('Y-m-d',strtotime('-1 day')) === $group['day'];
                        $dlabel  = $isToday ? 'Today' : ($isYest ? 'Yesterday' : date('M j',strtotime($group['day'])));
            ?>
            <div class="ptm-day"><?= $dlabel ?></div>
            <?php  endif; ?>
            <div class="ptm-msg-group <?= $group['sender'] ?>">
                <?php
                    $count = count($group['msgs']);
                    foreach ($group['msgs'] as $gi => $msg):
                        $mine    = ($group['sender'] === 'mine');
                        $isRead  = !empty($msg['read_at']);
                        $isOnly  = ($count === 1);
                        $isFirst = ($gi === 0 && !$isOnly);
                        $isLast  = ($gi === $count-1 && !$isOnly);
                        $isMid   = (!$isFirst && !$isLast && !$isOnly);
                        $posCls  = $isOnly ? 'is-only' : ($isFirst ? 'is-first' : ($isLast ? 'is-last' : 'is-mid'));
                        $sideCls = $mine ? 'mine' : 'theirs';
                ?>
                <div class="ptm-row <?= $sideCls ?> <?= $posCls ?>" data-id="<?= (int)$msg['id'] ?>">
                    <?php if (!$mine): ?><div class="ptm-row-avatar"><?= $cgInit ?></div><?php endif; ?>
                    <div>
                        <div class="ptm-bubble"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
                        <div class="ptm-bubble-meta">
                            <?= date('h:i A',strtotime($msg['sent_at'])) ?>
                            <?php if ($mine): ?>
                            <i class="ti ti-check<?= $isRead?'-checks':'' ?> ptm-read-tick<?= $isRead?' read':'' ?>" style="font-size:11px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>

            <!-- Typing indicator -->
            <div class="ptm-typing" id="typingIndicator">
                <div class="ptm-row-avatar"><?= $cgInit ?></div>
                <div class="ptm-typing-bubble">
                    <div class="ptm-typing-dot"></div>
                    <div class="ptm-typing-dot"></div>
                    <div class="ptm-typing-dot"></div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="ptm-input-row">
            <div class="ptm-input-wrap">
                <textarea class="ptm-input" id="msgInput"
                    placeholder="Message <?= $cgFirst ?>…"
                    rows="1" maxlength="500"
                    onkeydown="handleKey(event)"
                    oninput="autoResize(this);onTyping()"></textarea>
            </div>
            <button class="ptm-send" id="sendBtn" onclick="sendMsg()">
                <i class="ti ti-send"></i>
            </button>
        </div>

    </div><!-- /.ptm-chat -->


    <!-- ── Sidebar ── -->
    <div class="ptm-sidebar">

        <div class="ptm-info-card">
            <div class="ptm-info-avatar"><?= $cgInit ?></div>
            <div class="ptm-info-name">
                <?= htmlspecialchars(ucwords(strtolower($caregiver['name']))) ?>
            </div>
            <div class="ptm-info-role">Your Caregiver</div>

            <div class="ptm-info-stats">
                <div class="ptm-info-stat">
                    <div class="ptm-info-stat-num"><?= $totalMsgs ?></div>
                    <div class="ptm-info-stat-lbl">Messages</div>
                </div>
                <div class="ptm-info-stat">
                    <div class="ptm-info-stat-num"
                         style="color:<?= $unreadCG > 0 ? '#ef4444' : '#22c55e' ?>;">
                        <?= $unreadCG ?>
                    </div>
                    <div class="ptm-info-stat-lbl">Unread</div>
                </div>
            </div>

            <div class="ptm-quick-label">Quick Replies</div>
            <div class="ptm-quick-list">
                <?php foreach ([
                    ['ti-heart',         "I'm feeling good today, thanks!"],
                    ['ti-check',         "I already took my medication."],
                    ['ti-droplet-half-2',"I just logged my blood sugar."],
                    ['ti-alert-triangle',"I'm not feeling well today."],
                    ['ti-help-circle',   "I have a question for you."],
                    ['ti-mood-smile',    "Thanks for checking in!"],
                ] as [$icon, $txt]): ?>
                <button class="ptm-quick-btn" onclick="setMsg(<?= htmlspecialchars(json_encode($txt)) ?>)">
                    <i class="ti <?= $icon ?>"></i><?= $txt ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /.ptm-sidebar -->

</div><!-- /.ptm-layout -->

<?php endif; ?>

<script>
const MY_ID   = <?= (int)$myId ?>;
const CG_INIT = '<?= $cgInit ?>';
let lastId    = <?= $lastId ?>;
let typingTimer = null;

const CG_INIT_JS = '<?= $cgInit ?>';
const POS_CLASSES = ['is-only','is-first','is-mid','is-last'];

function scrollBottom() {
    const t = document.getElementById('thread');
    if (t) t.scrollTop = t.scrollHeight;
}
function autoResize(ta) {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
}
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}
function setMsg(txt) {
    const ta = document.getElementById('msgInput');
    ta.value = txt; autoResize(ta); ta.focus();
}
function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

function regroup() {
    const thread = document.getElementById('thread');
    thread.querySelectorAll('.ptm-msg-group').forEach(g => {
        const rows = [...g.querySelectorAll('.ptm-row')];
        const n = rows.length;
        rows.forEach((row, i) => {
            row.classList.remove(...POS_CLASSES);
            row.classList.add(n === 1 ? 'is-only' : i === 0 ? 'is-first' : i === n-1 ? 'is-last' : 'is-mid');
        });
    });
}

function appendBubble(body, time, mine, isRead, msgId) {
    const thread  = document.getElementById('thread');
    thread.querySelector('.ptm-empty-thread')?.remove();
    const typer   = document.getElementById('typingIndicator');
    const sideCls = mine ? 'mine' : 'theirs';

    // Find or create group
    const allGroups = thread.querySelectorAll('.ptm-msg-group');
    let group;
    const lastGroup = allGroups.length ? allGroups[allGroups.length-1] : null;
    if (lastGroup && lastGroup.classList.contains(sideCls)) {
        group = lastGroup;
    } else {
        group = document.createElement('div');
        group.className = 'ptm-msg-group ' + sideCls;
        thread.insertBefore(group, typer);
    }

    const tick = mine
        ? `<i class="ti ti-check${isRead?'-checks':''} ptm-read-tick${isRead?' read':''}" style="font-size:11px;"></i>`
        : '';
    const avatar = !mine ? `<div class="ptm-row-avatar">${CG_INIT_JS}</div>` : '';

    const row = document.createElement('div');
    row.className = 'ptm-row ' + sideCls;
    if (msgId) row.dataset.id = String(msgId);
    row.innerHTML = `${avatar}<div>
        <div class="ptm-bubble">${escHtml(body)}</div>
        <div class="ptm-bubble-meta">${time} ${tick}</div>
    </div>`;
    group.appendChild(row);

    regroup();
    thread.scrollTop = thread.scrollHeight;
    if (msgId && +msgId > lastId) lastId = +msgId;
}

function sendMsg() {
    const ta  = document.getElementById('msgInput');
    const msg = ta.value.trim();
    if (!msg) { ta.focus(); return; }
    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2"></i>';
    appendBubble(
        msg,
        new Date().toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' }),
        true, false, null
    );
    ta.value = ''; autoResize(ta);
    fetch('/patient/sendPatientMessage', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
    })
    .then(r => r.json())
    .then(d => { if (d.ok && d.id) lastId = Math.max(lastId, +d.id); })
    .catch(() => {})
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ti ti-send"></i>'; });
}

function onTyping() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        fetch('/patient/setPatientTyping', { method: 'POST' }).catch(() => {});
    }, 400);
}

setInterval(() => {
    fetch('/patient/getPatientMessages?after=' + lastId)
    .then(r => r.json())
    .then(data => {
        const ti = document.getElementById('typingIndicator');
        if (ti) ti.classList.toggle('show', !!data.typing);
        if (data.messages?.length) {
            data.messages.forEach(m => {
                if (m.sender_type === 'caregiver') appendBubble(m.body, m.sent_at, false, false, +m.id);
                else if (+m.id > lastId) lastId = +m.id;
            });
        }
    })
    .catch(() => {});
}, 5000);

window.addEventListener('load', () => { regroup(); scrollBottom(); });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../shared/patient_layout.php';
?>