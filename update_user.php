<?php
include('inc/db.php');

// Получение данных из POST-запроса
$userId = isset($_POST['userId']) ? intval($_POST['userId']) : null;
$adminView = isset($_POST['adminView']) ? intval($_POST['adminView']) : null;

$response = [];

if (!is_null($userId) && !is_null($adminView)) {
    $stmt = $connect->prepare("UPDATE user SET admin_view = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $adminView, $userId);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['admin_view'] = $adminView;
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }
        $stmt->close();
    } else {
        $response['success'] = false;
        $response['error'] = $connect->error;
    }
} else {
    $response['success'] = false;
    $response['error'] = "Некорректные данные запроса.";
}

// Установите правильный заголовок и верните JSON
header('Content-Type: application/json');
echo json_encode($response);
