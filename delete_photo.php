<?php
session_start();
include("inc/function.php");

// Устанавливаем заголовок для JSON
header('Content-Type: application/json; charset=utf-8');

AutorizeProtect();
access();

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Отсутствует ID изображения']);
    exit;
}

$encodedStr = $_POST['id'];
$filename = "img/screen/$encodedStr.png";

// Проверяем существование файла
if (!file_exists($filename)) {
    echo json_encode(['success' => false, 'message' => 'Файл не найден']);
    exit;
}

// Проверяем права доступа
if (!is_writable(dirname($filename))) {
    echo json_encode(['success' => false, 'message' => 'Нет прав на удаление файла']);
    exit;
}

// Удаляем файл
if (unlink($filename)) {
    echo json_encode(['success' => true, 'message' => 'Изображение успешно удалено']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении файла']);
}
?> 