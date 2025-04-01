<?php
session_start();
include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php");

AutorizeProtect();
access();

global $connect;
global $usr;
$connect->set_charset('utf8mb4');

// Определение ролей
$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<head>
    <style>
        .tabbar {
            max-height: 3rem;
        }
        .reply-to {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer; /* Указывает, что элемент кликабелен */
        }
        .reply-to:hover {
            text-decoration: underline; /* Подчеркивание при наведении */
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
    <link rel="stylesheet" href="css/adm_chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>

<body>
<div class="container-sm">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <a class="navbar-brand" href="/">
                        <img id="animated-example" class="mt-2 pidaras animated fadeOut" src="img/logo.webp?12w" alt="ArdMoney" height="90px">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main role="main">
        <div class="chat-wrapper">
            <div class="update-banner" id="update-banner" style="display: none;">Обновите страницу для новых сообщений <i class="fas fa-sync-alt"></i></div>
            <div class="month-nav">
                <div class="month-year">Чат для обсуждения багов</div>
            </div>

            <div id="pinned-message" class="pinned-message" style="display: none;">
                <span>Закреплённое сообщение:</span>
                <div id="pinned-content"></div>
                <button id="unpin-message" class="unpin-message" style="display: none;"><i class="fa-solid fa-times"></i></button>
            </div>

            <div id="chat-messages" class="chat-container"></div>

            <div class="chat-form-container">
                <form id="chat-form">
                    <div id="reply-indicator" class="reply-indicator" style="display: none;">
                        <span>Ответ на: <span id="reply-username"></span></span>
                        <button type="button" id="cancel-reply" class="cancel-reply"><i class="fa-solid fa-times"></i></button>
                    </div>
                    <div id="edit-indicator" class="edit-indicator" style="display: none;">
                        <span>Редактирование: <span id="edit-message-text"></span></span>
                        <button type="button" id="cancel-edit" class="cancel-edit"><i class="fa-solid fa-times"></i></button>
                    </div>
                    <div class="input-group">
                        <textarea class="form-control" id="message-input" rows="1" maxlength="500" placeholder="Сообщите о баге..." required oninput="this.value.length > 500 ? this.value = this.value.slice(0, 500) : null"></textarea>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                        <input type="hidden" id="reply-to-id" name="reply_to_id" value="">
                        <input type="hidden" id="edit-message-id" name="edit_message_id" value="">
                        <input type="hidden" id="csrf-token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    </div>
                </form>
            </div>
            <footer></footer>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const replyIndicator = document.getElementById('reply-indicator');
    const replyUsername = document.getElementById('reply-username');
    const cancelReply = document.getElementById('cancel-reply');
    const replyToIdInput = document.getElementById('reply-to-id');
    const editIndicator = document.getElementById('edit-indicator');
    const editMessageText = document.getElementById('edit-message-text');
    const cancelEdit = document.getElementById('cancel-edit');
    const editMessageIdInput = document.getElementById('edit-message-id');
    const pinnedMessage = document.getElementById('pinned-message');
    const pinnedContent = document.getElementById('pinned-content');
    const unpinMessage = document.getElementById('unpin-message');
    const updateBanner = document.getElementById('update-banner');
    const csrfToken = document.getElementById('csrf-token').value;
    let replyingTo = null;
    let editingMessage = null;
    const currentUser = "<?php echo $usr['name']; ?>";
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const markedAsViewed = [];

    // Настройка toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    const reactionEmojis = {
        'like': '1f44d',    // 👍
        'dislike': '1f44e', // 👎
        'haha': '1f602',    // 😂
        'angry': '1f620',   // 😠
        'clown': '1f921'    // 🤡
    };

    setTimeout(() => {
        updateBanner.style.display = 'block';
    }, 10000);

    updateBanner.addEventListener('click', () => {
        location.reload();
    });

    async function loadMessages() {
        try {
            const response = await fetch(`adm_chat_obr.php?action=get_messages`);
            if (!response.ok) throw new Error('Ошибка сети: ' + response.statusText);
            const data = await response.json();
            if (data.status === 'error') throw new Error(data.message);
            const messages = data.messages;
            const pinned = data.pinned;

            if (pinned) {
                pinnedMessage.style.display = 'flex';
                pinnedContent.innerHTML = `${pinned.username}: ${pinned.message}`;
                pinnedContent.dataset.id = pinned.id;
                unpinMessage.style.display = isAdmin ? 'block' : 'none';
            } else {
                pinnedMessage.style.display = 'none';
            }

            chatMessages.innerHTML = '';

            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `chat-message ${msg.is_admin == 1 ? 'admin' : 'user'}`;
                messageDiv.dataset.id = msg.id;
                messageDiv.dataset.username = msg.username;

                let replyHtml = '';
                if (msg.reply_to) {
                    const replyText = msg.reply_to.message.length > 50 
                        ? msg.reply_to.message.substring(0, 47) + '...' 
                        : msg.reply_to.message;
                    replyHtml = `<div class="reply-to" data-reply-id="${msg.reply_to.id}"><i class="fas fa-reply"></i> ${msg.reply_to.username}: ${replyText}</div>`;
                }

                let reactionsHtml = '';
                if (msg.reactions && Object.keys(msg.reactions).length > 0) {
                    reactionsHtml = '<div class="reactions">';
                    Object.keys(msg.reactions).forEach(reaction => {
                        const count = msg.reactions[reaction].length;
                        const emojiCode = reactionEmojis[reaction] || '2753';
                        reactionsHtml += `<span class="reaction" data-reaction="${reaction}"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/${emojiCode}.png" alt="${reaction}"> <span>${count}</span></span>`;
                    });
                    reactionsHtml += '</div>';
                }

                const isOwnMessage = msg.username === currentUser;
                const actionsHtml = `
                    <div class="menu-container">
                        <div class="reactions-picker">
                            <button data-reaction="like"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/1f44d.png" alt="like"></button>
                            <button data-reaction="dislike"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/1f44e.png" alt="dislike"></button>
                            <button data-reaction="haha"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/1f602.png" alt="haha"></button>
                            <button data-reaction="angry"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/1f620.png" alt="angry"></button>
                            <button data-reaction="clown"><img src="https://cdn.jsdelivr.net/npm/emoji-datasource-google@14.0.0/img/google/64/1f921.png" alt="clown"></button>
                        </div>
                        <div class="actions">
                            <button class="reply-message"><i class="fas fa-reply"></i></button>
                            ${isOwnMessage ? '<button class="edit-message"><i class="fas fa-edit"></i></button>' : ''}
                            ${isOwnMessage || isAdmin ? '<button class="delete-message"><i class="fas fa-trash"></i></button>' : ''}
                            ${isAdmin ? `<button class="pin-message"><i class="fas fa-thumbtack"></i></button>` : ''}
                        </div>
                    </div>
                `;

                messageDiv.innerHTML = `
                    ${replyHtml}
                    <div class="username">${msg.username}</div>
                    <div class="message-text">${msg.message}${msg.edited_at ? ' <span class="edited-label">(ред.)</span>' : ''}</div>
                    <div class="timestamp">${msg.created_at}</div>
                    <div class="views">${msg.views} <i class="fas fa-eye"></i></div>
                    ${reactionsHtml}
                    ${actionsHtml}
                `;

                chatMessages.appendChild(messageDiv);
                if (!msg.viewed && !markedAsViewed.includes(msg.id)) {
                    markMessageViewed(msg.id);
                    markedAsViewed.push(msg.id);
                }
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        } catch (error) {
            console.error('Ошибка загрузки сообщений:', error);
            toastr.error('Ошибка загрузки сообщений');
        }
    }

    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        const replyToId = replyToIdInput.value;
        const editMessageId = editMessageIdInput.value;

        if (!message) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        if (editMessageId) {
            formData.append('action', 'edit_message');
            formData.append('message_id', editMessageId);
            formData.append('message', message);
        } else {
            formData.append('action', 'send_message');
            formData.append('message', message);
            if (replyToId) formData.append('reply_to_id', replyToId);
        }

        try {
            const response = await fetch('adm_chat_obr.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.status === 'success') {
                messageInput.value = '';
                replyIndicator.style.display = 'none';
                editIndicator.style.display = 'none';
                replyToIdInput.value = '';
                editMessageIdInput.value = '';
                replyingTo = null;
                editingMessage = null;
                loadMessages();
                toastr.success('Сообщение успешно отправлено');
            } else {
                toastr.error('Ошибка: ' + data.message);
            }
        } catch (error) {
            console.error('Ошибка отправки:', error);
            toastr.error('Не удалось отправить сообщение');
        }
    });

    chatMessages.addEventListener('click', async function(e) {
        const replyDiv = e.target.closest('.reply-to');
        if (replyDiv) {
            const replyId = replyDiv.dataset.replyId;
            const targetMessage = chatMessages.querySelector(`.chat-message[data-id="${replyId}"]`);
            if (targetMessage) {
                targetMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const messageDiv = e.target.closest('.chat-message');
        if (!messageDiv) return;

        const target = e.target.closest('button');
        const messageId = messageDiv.dataset.id;
        const username = messageDiv.dataset.username;

        const menuContainer = messageDiv.querySelector('.menu-container');
        if (!target) {
            const isVisible = menuContainer.style.display === 'block';
            document.querySelectorAll('.menu-container').forEach(el => el.style.display = 'none');
            if (!isVisible) menuContainer.style.display = 'block';
            return;
        }

        if (target.classList.contains('reply-message')) {
            replyingTo = { id: messageId, username: username };
            editingMessage = null;
            replyIndicator.style.display = 'flex';
            editIndicator.style.display = 'none';
            replyUsername.textContent = username;
            replyToIdInput.value = messageId;
            editMessageIdInput.value = '';
            messageInput.value = '';
            messageInput.focus();
            menuContainer.style.display = 'none';
        } else if (target.classList.contains('edit-message')) {
            const messageText = messageDiv.querySelector('.message-text').textContent.replace(' (ред.)', '');
            editingMessage = { id: messageId, text: messageText };
            replyingTo = null;
            editIndicator.style.display = 'flex';
            replyIndicator.style.display = 'none';
            editMessageText.textContent = messageText;
            editMessageIdInput.value = messageId;
            replyToIdInput.value = '';
            messageInput.value = messageText;
            messageInput.focus();
            menuContainer.style.display = 'none';
        } else if (target.classList.contains('delete-message')) {
            if (confirm('Удалить сообщение?')) {
                const formData = new FormData();
                formData.append('action', 'delete_message');
                formData.append('message_id', messageId);
                formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('adm_chat_obr.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.status === 'success') {
                        loadMessages();
                        toastr.success('Сообщение удалено');
                    } else {
                        toastr.error('Ошибка: ' + data.message);
                    }
                } catch (error) {
                    toastr.error('Не удалось удалить сообщение');
                }
            }
        } else if (target.classList.contains('pin-message')) {
            const formData = new FormData();
            formData.append('action', 'toggle_pin');
            formData.append('message_id', messageId);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('adm_chat_obr.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    loadMessages();
                    toastr.success('Статус закрепления изменен');
                } else {
                    toastr.error('Ошибка: ' + data.message);
                }
            } catch (error) {
                toastr.error('Не удалось закрепить сообщение');
            }
        } else if (target.dataset.reaction) {
            const reaction = target.dataset.reaction;
            const formData = new FormData();
            formData.append('action', 'add_reaction');
            formData.append('message_id', messageId);
            formData.append('reaction', reaction);
            formData.append('csrf_token', csrfToken);
            formData.append('replace', 'true');

            try {
                const response = await fetch('adm_chat_obr.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    loadMessages();
                    toastr.success('Реакция добавлена');
                } else {
                    toastr.error('Ошибка: ' + data.message);
                }
            } catch (error) {
                toastr.error('Не удалось добавить реакцию');
            }
            menuContainer.style.display = 'none';
        }
    });

    cancelReply.addEventListener('click', function() {
        replyIndicator.style.display = 'none';
        replyToIdInput.value = '';
        replyingTo = null;
        messageInput.value = '';
    });

    cancelEdit.addEventListener('click', function() {
        editIndicator.style.display = 'none';
        editMessageIdInput.value = '';
        editingMessage = null;
        messageInput.value = '';
    });

    pinnedContent.addEventListener('click', function() {
        const pinnedMsg = chatMessages.querySelector(`.chat-message[data-id="${pinnedContent.dataset.id}"]`);
        if (pinnedMsg) pinnedMsg.scrollIntoView();
    });

    unpinMessage.addEventListener('click', async function() {
        const formData = new FormData();
        formData.append('action', 'toggle_pin');
        formData.append('message_id', pinnedContent.dataset.id);
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('adm_chat_obr.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                loadMessages();
                toastr.success('Сообщение откреплено');
            } else {
                toastr.error('Ошибка: ' + data.message);
            }
        } catch (error) {
            toastr.error('Не удалось открепить сообщение');
        }
    });

    async function markMessageViewed(messageId) {
        const formData = new FormData();
        formData.append('action', 'mark_viewed');
        formData.append('message_id', messageId);
        formData.append('csrf_token', csrfToken);

        try {
            await fetch('adm_chat_obr.php', { method: 'POST', body: formData });
        } catch (error) {
            console.error('Ошибка отметки просмотра:', error);
        }
    }

    messageInput.addEventListener('focus', function() {
        setTimeout(() => {
            const formContainer = document.querySelector('.chat-form-container');
            formContainer.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 300);
    });

    loadMessages();
});
</script>
<?php include 'inc/foot.php'; ?>
</body>
</html>