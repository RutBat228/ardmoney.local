<?php
include 'inc/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $token = trim($_POST['token'] ?? '');

    if (!empty($login) && !empty($token)) {
        $stmt = $connect->prepare("REPLACE INTO user_tokens (login, fcm_token) VALUES (?, ?)");
        $stmt->bind_param("ss", $login, $token);
        if ($stmt->execute()) {
            echo "Токен сохранён";
        } else {
            echo "Ошибка сохранения токена: " . $connect->error;
        }
        $stmt->close();
    } else {
        echo "Ошибка: логин или токен пусты";
    }
} else {
    echo "Ошибка: метод не POST";
}
?>