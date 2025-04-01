<?php
session_start();
include("inc/function.php");

// Проверка авторизации
AutorizeProtect();
global $usr;
global $connect;

$user_id = $_GET['user_id'] ?? null;
$month = $_GET['month'] ?? null;

if (!$user_id || !$month) {
    die(json_encode(['error' => 'Некорректные параметры']));
}

// Проверка прав доступа
if ($usr['admin'] != "1" && $usr['name'] != "RutBat" && $usr['id'] != $user_id) {
    die(json_encode(['error' => 'Недостаточно прав']));
}

// Получение существующих дежурств
$stmt = $connect->prepare("SELECT duty_date FROM duty_days WHERE user_id = ? AND month = ?");
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$result = $stmt->get_result();

$duties = [];
while ($row = $result->fetch_assoc()) {
    $duties[] = $row['duty_date'];
}

header('Content-Type: application/json');
echo json_encode(['duties' => $duties]);
?>