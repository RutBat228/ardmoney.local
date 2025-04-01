<?php
include("inc/function.php");
global $connect;

$user_id = $_GET['user_id'];
$month = $_GET['month'];

$stmt = $connect->prepare("SELECT current_salary, official_employment, advance FROM user_finance WHERE user_id = ? AND month = ?");
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$result = $stmt->get_result();
$finance = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($finance ?: ['current_salary' => 24000, 'official_employment' => 'Нет', 'advance' => 0]);
?>