<?php
session_start();
include("inc/function.php");

// Устанавливаем заголовок для JSON
header('Content-Type: application/json; charset=utf-8');

AutorizeProtect();
access();

if (!isset($_FILES['userfile']) || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые параметры']);
    exit;
}

$id = $_POST['id'];
$file = $_FILES['userfile'];

// Проверяем тип файла
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Неподдерживаемый тип файла']);
    exit;
}

// Проверяем размер файла (максимум 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Файл слишком большой']);
    exit;
}

$filename = "img/screen/$id.png";

// Если файл существует, удаляем его
if (file_exists($filename)) {
    unlink($filename);
}

// Загружаем новый файл
if (move_uploaded_file($file['tmp_name'], $filename)) {
    echo json_encode(['success' => true, 'message' => 'Изображение успешно загружено']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке файла']);
}
?>
