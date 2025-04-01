<?php
include('inc/db.php');

header('Content-Type: application/json');

$monId = isset($_POST['monId']) ? $_POST['monId'] : null;
$dogovor = isset($_POST['ajaxname']) ? $_POST['ajaxname'] : null;

$response = ['success' => false, 'message' => ''];

if (!is_null($monId) && !is_null($dogovor)) {
    $dogovor = (int)$dogovor; // Приводим к числу (0 или 1)
    $monId = (int)$monId; // Защита от SQL-инъекций
    $stmt = $connect->prepare("UPDATE montaj SET `dogovor` = ? WHERE id = ?");
    $stmt->bind_param("ii", $dogovor, $monId);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Договор обновлён';
    } else {
        $response['message'] = 'Ошибка при обновлении: ' . $connect->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Недостаточно данных';
}

echo json_encode($response);
?>