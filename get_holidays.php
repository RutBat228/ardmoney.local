<?php
session_start();
include("inc/function.php");

AutorizeProtect();
global $connect;

$month = $_GET['month'] ?? null;

if (!$month) {
    die(json_encode(['error' => 'Месяц не указан']));
}

$stmt = $connect->prepare("SELECT holiday_date FROM holidays WHERE DATE_FORMAT(holiday_date, '%Y-%m') = ?");
$stmt->bind_param("s", $month);
$stmt->execute();
$result = $stmt->get_result();

$holidays = [];
while ($row = $result->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}

header('Content-Type: application/json');
echo json_encode(['holidays' => $holidays]);
?>