<?php
include('inc/db.php');

header('Content-Type: application/json');

$monId = isset($_POST['monId']) ? $_POST['monId'] : null;
$stat = isset($_POST['ajaxname']) ? $_POST['ajaxname'] : null;

$response = ['success' => false, 'message' => ''];

if (!is_null($monId) && !is_null($stat)) {
    $stat = (int)$stat; // Приводим к числу (0 или 1)
    $monId = (int)$monId;
    $stmt = $connect->prepare("UPDATE montaj SET `status` = ? WHERE id = ?");
    $stmt->bind_param("ii", $stat, $monId);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Статус обновлён';
    } else {
        $response['message'] = 'Ошибка при обновлении: ' . $connect->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Недостаточно данных';
}

echo json_encode($response);
?>