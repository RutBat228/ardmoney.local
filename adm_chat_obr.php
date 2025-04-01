<?php
session_start();
include("inc/function.php");

AutorizeProtect();
access();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

global $connect;
global $usr;

$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Неверный CSRF-токен']);
        exit();
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action === 'get_messages') {
    if ($isOwner || $isSuperAdmin) {
        $query = "SELECT m1.*, m2.username AS reply_to_username, m2.message AS reply_to_message
                  FROM chat_messages m1
                  LEFT JOIN chat_messages m2 ON m1.reply_to_id = m2.id";
    } else {
        $query = "SELECT m1.*, m2.username AS reply_to_username, m2.message AS reply_to_message
                  FROM chat_messages m1
                  LEFT JOIN chat_messages m2 ON m1.reply_to_id = m2.id
                  WHERE (m1.username = ? OR (m1.reply_to_id IN (SELECT id FROM chat_messages WHERE username = ?) AND m1.is_admin = 1))";
    }
    $query .= " ORDER BY m1.created_at ASC";

    $stmt = $connect->prepare($query);
    if (!$isOwner && !$isSuperAdmin) {
        $stmt->bind_param('ss', $usr['name'], $usr['name']);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];

    $pinnedStmt = $connect->prepare("SELECT * FROM chat_messages WHERE is_pinned = 1 LIMIT 1");
    $pinnedStmt->execute();
    $pinnedResult = $pinnedStmt->get_result();
    $pinned = $pinnedResult->fetch_assoc();
    if ($pinned) {
        $pinned = [
            'id' => $pinned['id'],
            'username' => htmlspecialchars($pinned['username']),
            'message' => htmlspecialchars($pinned['message'])
        ];
    }

    $viewsStmt = $connect->prepare("SELECT message_id, COUNT(*) as view_count FROM message_views GROUP BY message_id");
    $viewsStmt->execute();
    $viewsResult = $viewsStmt->get_result();
    $views = [];
    while ($row = $viewsResult->fetch_assoc()) {
        $views[$row['message_id']] = $row['view_count'];
    }

    $reactionsStmt = $connect->prepare("SELECT message_id, reaction, user_id FROM message_reactions");
    $reactionsStmt->execute();
    $reactionsResult = $reactionsStmt->get_result();
    $reactions = [];
    while ($row = $reactionsResult->fetch_assoc()) {
        if (!isset($reactions[$row['message_id']])) $reactions[$row['message_id']] = [];
        if (!isset($reactions[$row['message_id']][$row['reaction']])) $reactions[$row['message_id']][$row['reaction']] = [];
        $reactions[$row['message_id']][$row['reaction']][] = $row['user_id'];
    }

    $viewedStmt = $connect->prepare("SELECT message_id FROM message_views WHERE user_id = ?");
    $viewedStmt->bind_param('s', $usr['name']);
    $viewedStmt->execute();
    $viewedResult = $viewedStmt->get_result();
    $viewedMessages = [];
    while ($row = $viewedResult->fetch_assoc()) {
        $viewedMessages[$row['message_id']] = true;
    }

    while ($row = $result->fetch_assoc()) {
        $message = [
            'id' => $row['id'],
            'username' => htmlspecialchars($row['username']),
            'message' => htmlspecialchars($row['message']),
            'created_at' => date('d.m.Y H:i', strtotime($row['created_at'])),
            'is_admin' => $row['is_admin'],
            'is_pinned' => $row['is_pinned'],
            'edited_at' => $row['edited_at'] ? date('d.m.Y H:i', strtotime($row['edited_at'])) : null,
            'views' => isset($views[$row['id']]) ? $views[$row['id']] : 0,
            'viewed' => isset($viewedMessages[$row['id']]),
            'reactions' => isset($reactions[$row['id']]) ? $reactions[$row['id']] : []
        ];

        if ($row['reply_to_id']) {
            $message['reply_to'] = [
                'id' => $row['reply_to_id'],
                'username' => htmlspecialchars($row['reply_to_username']),
                'message' => htmlspecialchars($row['reply_to_message'])
            ];
        }

        $messages[] = $message;
    }

    echo json_encode(['status' => 'success', 'messages' => $messages, 'pinned' => $pinned]);
    exit();
}

if ($action === 'send_message' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $replyToId = isset($_POST['reply_to_id']) && !empty($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

    if (strlen($message) > 500) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение слишком длинное (максимум 500 символов)']);
        exit();
    }

    if ($message === '') {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не может быть пустым']);
        exit();
    }

    $stmt = $connect->prepare("INSERT INTO chat_messages (username, message, is_admin, reply_to_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssii', $usr['name'], $message, $isAdmin, $replyToId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось отправить сообщение']);
    }
    exit();
}

if ($action === 'delete_message' && isset($_POST['message_id'])) {
    $messageId = (int)$_POST['message_id'];

    // Проверяем, является ли пользователь автором сообщения или админом
    $stmt = $connect->prepare("SELECT username FROM chat_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не найдено']);
        exit();
    }

    if ($row['username'] !== $usr['name'] && !$isAdmin) {
        echo json_encode(['status' => 'error', 'message' => 'Вы можете удалять только свои сообщения или быть админом']);
        exit();
    }

    $stmt = $connect->prepare("DELETE FROM chat_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось удалить сообщение']);
    }
    exit();
}

if ($action === 'edit_message' && isset($_POST['message_id']) && isset($_POST['message'])) {
    $messageId = (int)$_POST['message_id'];
    $message = trim($_POST['message']);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    if (strlen($message) > 500) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение слишком длинное (максимум 500 символов)']);
        exit();
    }

    if ($message === '') {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не может быть пустым']);
        exit();
    }

    $stmt = $connect->prepare("SELECT username, created_at FROM chat_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не найдено']);
        exit();
    }

    if ($row['username'] !== $usr['name']) {
        echo json_encode(['status' => 'error', 'message' => 'Вы можете редактировать только свои сообщения']);
        exit();
    }

    $createdAt = strtotime($row['created_at']);
    $now = time();
    if ($now - $createdAt > 5 * 60) {
        echo json_encode(['status' => 'error', 'message' => 'Время для редактирования истекло']);
        exit();
    }

    $stmt = $connect->prepare("UPDATE chat_messages SET message = ?, edited_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $message, $messageId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось отредактировать сообщение']);
    }
    exit();
}

if ($action === 'toggle_pin' && isset($_POST['message_id'])) {
    if (!$isAdmin) {
        echo json_encode(['status' => 'error', 'message' => 'Только админы могут закреплять сообщения']);
        exit();
    }

    $messageId = (int)$_POST['message_id'];

    $stmt = $connect->prepare("SELECT is_pinned FROM chat_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не найдено']);
        exit();
    }

    $newPinnedState = $row['is_pinned'] ? 0 : 1;

    if ($newPinnedState) {
        $unpinStmt = $connect->prepare("UPDATE chat_messages SET is_pinned = 0 WHERE is_pinned = 1");
        $unpinStmt->execute();
    }

    $stmt = $connect->prepare("UPDATE chat_messages SET is_pinned = ? WHERE id = ?");
    $stmt->bind_param('ii', $newPinnedState, $messageId);
    $stmt->execute();

    if ($stmt->affected_rows > 0 || $newPinnedState === 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось закрепить/открепить сообщение']);
    }
    exit();
}

if ($action === 'add_reaction' && isset($_POST['message_id']) && isset($_POST['reaction'])) {
    $messageId = (int)$_POST['message_id'];
    $reaction = trim($_POST['reaction']);
    $userId = $usr['name'];

    if (empty($messageId) || empty($reaction)) {
        echo json_encode(['status' => 'error', 'message' => 'Неверные параметры']);
        exit();
    }

    $validReactions = ['like', 'dislike', 'haha', 'angry', 'clown'];
    if (!in_array($reaction, $validReactions)) {
        echo json_encode(['status' => 'error', 'message' => 'Недопустимая реакция']);
        exit();
    }

    $stmt = $connect->prepare("SELECT id FROM chat_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не найдено']);
        exit();
    }

    $stmt = $connect->prepare("DELETE FROM message_reactions WHERE message_id = ? AND user_id = ?");
    $stmt->bind_param('is', $messageId, $userId);
    $stmt->execute();

    $stmt = $connect->prepare("INSERT INTO message_reactions (message_id, user_id, reaction) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $messageId, $userId, $reaction);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось добавить реакцию']);
    }
    exit();
}

if ($action === 'mark_viewed' && isset($_POST['message_id'])) {
    $messageId = (int)$_POST['message_id'];

    $stmt = $connect->prepare("SELECT id FROM message_views WHERE message_id = ? AND user_id = ?");
    $stmt->bind_param('is', $messageId, $usr['name']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt = $connect->prepare("INSERT INTO message_views (message_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('is', $messageId, $usr['name']);
        $stmt->execute();
    }

    echo json_encode(['status' => 'success']);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Неверное действие']);
exit();
?>