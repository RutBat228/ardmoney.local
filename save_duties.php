<?php
session_start();
include("inc/function.php");

AutorizeProtect();
global $usr;
global $connect;

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$month = $data['month'] ?? null;
$duties = $data['duties'] ?? [];
$advance = $data['advance'] ?? 0;

if (!$user_id || !$month) {
    die(json_encode(['success' => false, 'error' => 'Некорректные параметры']));
}

if ($usr['admin'] != "1" && $usr['name'] != "RutBat" && $usr['id'] != $user_id) {
    die(json_encode(['success' => false, 'error' => 'Недостаточно прав']));
}

// Получение праздничных дней за месяц
$stmt = $connect->prepare("SELECT holiday_date FROM holidays WHERE DATE_FORMAT(holiday_date, '%Y-%m') = ?");
$stmt->bind_param("s", $month);
$stmt->execute();
$result = $stmt->get_result();
$holidays = [];
while ($row = $result->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}

// Удаление старых дежурств
$stmt = $connect->prepare("DELETE FROM duty_days WHERE user_id = ? AND month = ?");
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();

// Добавление новых дежурств и подсчет с учетом двойного тарифа
$dejurstva = 0;
foreach ($duties as $duty_date) {
    $stmt = $connect->prepare("INSERT INTO duty_days (user_id, duty_date, month) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $duty_date, $month);
    $stmt->execute();

    $date = new DateTime($duty_date);
    $isWeekend = $date->format('N') >= 6; // Суббота (6) или воскресенье (7)
    $isHoliday = in_array($duty_date, $holidays);
    $dejurstva += ($isHoliday && $isWeekend) ? 2 : 1; // Двойной тариф для праздников на выходных
}

// Обновление количества дежурств и аванса в user_finance
$check_stmt = $connect->prepare("SELECT id FROM user_finance WHERE user_id = ? AND month = ?");
$check_stmt->bind_param("is", $user_id, $month);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $stmt = $connect->prepare("INSERT INTO user_finance (user_id, month, dejurstva, advance) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isid", $user_id, $month, $dejurstva, $advance);
    $stmt->execute();
} else {
    $stmt = $connect->prepare("UPDATE user_finance SET dejurstva = ?, advance = ? WHERE user_id = ? AND month = ?");
    $stmt->bind_param("idis", $dejurstva, $advance, $user_id, $month);
    $stmt->execute();
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>