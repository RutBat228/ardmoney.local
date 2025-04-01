<?php
session_start();
include "inc/function.php";
AutorizeProtect();
access();
global $connect;
global $usr;

$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);

header('Content-Type: application/json');

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
        $response['success'] = false;
        $response['error'] = 'Нет доступа для выполнения этой операции';
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_vid') {
        $name = h($_POST['name']);
        $price = floatval($_POST['price']);
        $razdel = h($_POST['razdel']);
        $type_kabel = h($_POST['type_kabel']);
        $color = h($_POST['color']);
        $icon = h($_POST['icon']);
        $prioritet = isset($_POST['prioritet']) ? '1' : '0';
        $text = "Sample Value";

        if (empty($name) || empty($price) || empty($razdel) || empty($type_kabel)) {
            $response['success'] = false;
            $response['error'] = "Все обязательные поля должны быть заполнены";
        } else {
            $stmt = $connect->prepare("INSERT INTO vid_rabot (name, price_tech, price_full, razdel, prioritet, type_kabel, text, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sddssssss", $name, $price, $price, $razdel, $prioritet, $type_kabel, $text, $icon, $color);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Вид работ добавлен!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка добавления: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_vid') {
        $id = intval($_POST['id']);
        $name = h($_POST['name']);
        $price = floatval($_POST['price']);
        $razdel = h($_POST['razdel']);
        $type_kabel = h($_POST['type_kabel']);
        $color = h($_POST['color']);
        $icon = h($_POST['icon']);
        $prioritet = isset($_POST['prioritet']) ? '1' : '0';
        $text = "Sample Value";

        if (empty($id) || empty($name) || empty($price) || empty($razdel) || empty($type_kabel)) {
            $response['success'] = false;
            $response['error'] = "Все обязательные поля должны быть заполнены";
        } else {
            $stmt = $connect->prepare("UPDATE vid_rabot SET name = ?, price_tech = ?, price_full = ?, razdel = ?, prioritet = ?, type_kabel = ?, text = ?, icon = ?, color = ? WHERE id = ?");
            $stmt->bind_param("sddssssssi", $name, $price, $price, $razdel, $prioritet, $type_kabel, $text, $icon, $color, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Вид работ обновлен!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка обновления: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_vid') {
        $id = intval($_POST['id']);

        if (empty($id)) {
            $response['success'] = false;
            $response['error'] = "ID вида работ не указан";
        } else {
            $stmt = $connect->prepare("DELETE FROM vid_rabot WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Вид работ удален!";
            } else {
                $response['success'] = false;
                $response['error'] = "Ошибка удаления: " . $stmt->error;
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