<?php
session_start();
include("inc/function.php");

AutorizeProtect();
access();

global $connect;
global $usr;

$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

header('Content-Type: application/json');

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    if (!$isOwner && !$isSuperAdmin) {
        $response['success'] = false;
        $response['error'] = 'Нет доступа для выполнения этой операции';
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_material') {
        $name = h($_POST['name']);
        $color = h($_POST['color']);
        $razdel = h($_POST['razdel']);
        $icon = h($_POST['icon']);

        if (empty($name) || empty($color) || empty($razdel)) {
            $response['success'] = false;
            $response['error'] = "Все обязательные поля должны быть заполнены";
        } else {
            $stmt = $connect->prepare("INSERT INTO material (name, color, razdel, icon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $color, $razdel, $icon);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Материал добавлен!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка добавления материала: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_material') {
        $id = intval($_POST['id']);
        $name = h($_POST['name']);
        $color = h($_POST['color']);
        $razdel = h($_POST['razdel']);
        $icon = h($_POST['icon']);

        if (empty($id) || empty($name) || empty($color) || empty($razdel)) {
            $response['success'] = false;
            $response['error'] = "Все обязательные поля должны быть заполнены";
        } else {
            $stmt = $connect->prepare("UPDATE material SET name = ?, color = ?, razdel = ?, icon = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $color, $razdel, $icon, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Материал обновлен!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка обновления материала: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_material') {
        $id = intval($_POST['id']);

        if (empty($id)) {
            $response['success'] = false;
            $response['error'] = "ID материала не указан";
        } else {
            $stmt = $connect->prepare("DELETE FROM material WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Материал удален!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка удаления материала: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    echo json_encode($response);
    exit;
}

echo json_encode(['error' => 'Неверный запрос']);
exit;
?>