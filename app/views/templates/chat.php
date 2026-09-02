<?php require __DIR__ . '/' . $headerFile; ?>

<h2><?= sprintf(t('heading_chat_with'), htmlspecialchars($otherPartyName)) ?></h2>

<div id="chatMessages" class="card" style="height:400px; overflow-y:auto; display:flex; flex-direction:column; gap:0.5rem; padding:1rem;"></div>

<form id="chatForm" style="display:flex; gap:0.5rem; margin-top:1rem;">
    <input type="text" id="chatInput" placeholder="<?= t('chat_placeholder') ?>" required style="flex:1; padding:0.7rem 0.8rem; border:1px solid var(--border); border-radius:6px; font-size:1rem;">
    <button type="submit" class="btn" style="width:auto; padding:0.7rem 1.5rem;"><?= t('btn_send') ?></button>
</form>

<style>
.chat-bubble {
    max-width:75%;
    padding:0.6rem 0.9rem;
    border-radius:12px;
    font-size:0.9rem;
    line-height:1.4;
}
.chat-bubble.mine {
    align-self:flex-end;
    background:var(--teal-dark);
    color:var(--white);
}
.chat-bubble.theirs {
    align-self:flex-start;
    background:var(--sand);
    border:1px solid var(--border);
    color:var(--ink);
}
.chat-bubble .chat-meta {
    display:block;
    font-size:0.68rem;
    opacity:0.7;
    margin-top:0.25rem;
}
</style>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const bookingId = <?= json_encode($bookingId) ?>;
const myName = <?= json_encode($myName) ?>;
const myRole = <?= json_encode($myRole) ?>;

const messagesRef = db.ref('chats/' + bookingId + '/messages');
const messagesEl = document.getElementById('chatMessages');
const form = document.getElementById('chatForm');
const input = document.getElementById('chatInput');

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

messagesRef.orderByChild('timestamp').on('child_added', function(snapshot) {
    const msg = snapshot.val();
    const isMine = msg.senderRole === myRole && msg.senderName === myName;

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + (isMine ? 'mine' : 'theirs');

    const time = new Date(msg.timestamp).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

    bubble.innerHTML = escapeHtml(msg.text) + '<span class="chat-meta">' + escapeHtml(msg.senderName) + ' &middot; ' + time + '</span>';

    messagesEl.appendChild(bubble);
    messagesEl.scrollTop = messagesEl.scrollHeight;
});

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;

    messagesRef.push({
        text: text,
        senderName: myName,
        senderRole: myRole,
        timestamp: Date.now()
    });

    input.value = '';
});
</script>

<?php require __DIR__ . '/' . $footerFile; ?>
