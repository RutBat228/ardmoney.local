<?php
session_start();
include("inc/function.php");

// Устанавливаем часовой пояс
date_default_timezone_set('Europe/Moscow');

// Устанавливаем заголовок для JSON
header('Content-Type: application/json; charset=utf-8');

AutorizeProtect();
access();

if (!isset($_POST['id']) || !isset($_POST['date'])) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые параметры']);
    exit;
}

$id = intval($_POST['id']);
$date = $_POST['date'];

// Проверяем формат даты
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Неверный формат даты']);
    exit;
}

// Убираем проверку на даты в прошлом
// if (strtotime($date) < strtotime(date('Y-m-d'))) {
//     echo json_encode(['success' => false, 'message' => 'Нельзя установить дату в прошлом']);
//     exit;
// }

global $connect;

// Проверяем существование монтажа и права доступа
$stmt = $connect->prepare("SELECT * FROM `montaj` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Монтаж не найден']);
    exit;
}

$montaj = $result->fetch_assoc();

// Проверяем права доступа
if ($montaj['region'] !== $usr['region']) {
    echo json_encode(['success' => false, 'message' => 'Нет прав для изменения этого монтажа']);
    exit;
}

// Обновляем дату
$stmt = $connect->prepare("UPDATE `montaj` SET `date` = ? WHERE `id` = ?");
$stmt->bind_param("si", $date, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'date' => $date]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении даты']);
}

$stmt->close();
$connect->close(); 