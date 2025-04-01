<?php
include("inc/function.php");
global $connect;

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];
$month = $data['month'];
$current_salary = $data['current_salary'];
$official_employment = $data['official_employment'];

$check_query = $connect->prepare("SELECT id FROM user_finance WHERE user_id = ? AND month = ?");
$check_query->bind_param("is", $user_id, $month);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows === 0) {
    $stmt = $connect->prepare("INSERT INTO user_finance (user_id, month, current_salary, official_employment, last_update) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $month, $current_salary, $official_employment);
} else {
    $stmt = $connect->prepare("UPDATE user_finance SET current_salary = ?, official_employment = ?, last_update = NOW() WHERE user_id = ? AND month = ?");
    $stmt->bind_param("ssis", $current_salary, $official_employment, $user_id, $month);
}

$success = $stmt->execute();
header('Content-Type: application/json');
echo json_encode(['success' => $success, 'error' => $success ? '' : $connect->error]);
?>