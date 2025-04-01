<?php
ob_start(); // Включаем буферизацию вывода для предотвращения случайного вывода перед JSON

include "inc/db.php";
global $connect;
global $usr;

header('Content-Type: application/json');

// Определение ролей пользователя
$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Базовая проверка прав доступа для всех операций с районами
    if (isset($_POST['action']) && in_array($_POST['action'], ['add_region', 'edit_region', 'delete_region'])) {
        if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
            $response['success'] = false;
            $response['error'] = 'Нет доступа для выполнения данной операции';
            echo json_encode($response);
            exit;
        }
        
        // Дополнительная проверка для админов - могут редактировать только свой район
        if ($isAdmin && !$isOwner && !$isSuperAdmin && 
            in_array($_POST['action'], ['edit_region', 'delete_region'])) {
            $region_name = h($_POST['old_name'] ?? $_POST['name']);
            if ($region_name !== $usr['region']) {
                $response['success'] = false;
                $response['error'] = 'Доступ ограничен только вашим регионом';
                echo json_encode($response);
                exit;
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_region') {
        $name = h($_POST['name']);
        $bonus = floatval($_POST['bonus']);
        $stmt = $connect->prepare("SELECT COUNT(*) FROM user WHERE region = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            $response['success'] = false;
            $response['error'] = 'Район с таким названием уже существует';
        } else {
            $stmt = $connect->prepare("INSERT INTO config (region, monthly_bonus) VALUES (?, ?)");
            $stmt->bind_param("sd", $name, $bonus);
            if ($stmt->execute()) {
                $response['success'] = true;
                $timestamp = date('d.m.Y H:i:s');
                $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
                $logMessage = "$timestamp Пользователь $userName добавил район - $name <br>";
                $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
                $logStmt->bind_param("ss", $timestamp, $logMessage);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $response['success'] = false;
                $response['error'] = $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_region') {
        $old_name = h($_POST['old_name']);
        $name = h($_POST['name']);
        $bonus = isset($_POST['bonus']) ? floatval($_POST['bonus']) : null;
    
        $stmt = $connect->prepare("SELECT COUNT(*) FROM user WHERE region = ? AND region != ?");
        $stmt->bind_param("ss", $name, $old_name);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            $response['success'] = false;
            $response['error'] = 'Район с таким названием уже существует';
        } else {
            $stmt = $connect->prepare("UPDATE user SET region = ? WHERE region = ?");
            $stmt->bind_param("ss", $name, $old_name);
            $stmt->execute();
    
            if ($bonus !== null) {
                $stmt = $connect->prepare("UPDATE config SET region = ?, monthly_bonus = ? WHERE region = ?");
                $stmt->bind_param("sds", $name, $bonus, $old_name);
            } else {
                $stmt = $connect->prepare("UPDATE config SET region = ? WHERE region = ?");
                $stmt->bind_param("ss", $name, $old_name);
            }
    
            if ($stmt->execute()) {
                $response['success'] = true;
                $timestamp = date('d.m.Y H:i:s');
                $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
                $logMessage = "$timestamp Пользователь $userName отредактировал район - $old_name (новое название: $name) <br>";
                $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
                $logStmt->bind_param("ss", $timestamp, $logMessage);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $response['success'] = false;
                $response['error'] = $stmt->error;
            }
            $stmt->close();
        }
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_region') {
        $name = h($_POST['name']);
        $stmt = $connect->prepare("UPDATE user SET region = 'Без региона' WHERE region = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt = $connect->prepare("DELETE FROM config WHERE region = ?");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $response['success'] = true;
            $timestamp = date('d.m.Y H:i:s');
            $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
            $logMessage = "$timestamp Пользователь $userName удалил район - $name <br>";
            $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
            $logStmt->bind_param("ss", $timestamp, $logMessage);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $fio = h($_POST['fio']);
        $email = h($_POST['email']);
        $region = h($_POST['region_id']);
        $position = h($_POST['position']);
        $name = h($_POST['name']);
        $pass = hash('sha256', h($_POST['pass'])); // Используем SHA-256 как в auth_obr.php

        $stmt = $connect->prepare("SELECT id FROM user WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['success'] = false;
            $response['error'] = 'Логин уже занят';
            echo json_encode($response);
            exit;
        }
        $stmt->close();

        $stmt = $connect->prepare("INSERT INTO user (fio, email, region, rang, name, pass, reger, access_date) VALUES (?, ?, ?, ?, ?, ?, 1, '9999-12-31')");
        $stmt->bind_param("ssssss", $fio, $email, $region, $position, $name, $pass);
        if ($stmt->execute()) {
            $response['success'] = true;
            $timestamp = date('d.m.Y H:i:s');
            $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
            $logMessage = "$timestamp Пользователь $userName добавил пользователя - $fio / $email <br>";
            $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
            $logStmt->bind_param("ss", $timestamp, $logMessage);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $id = intval($_POST['id']);
        $email = h($_POST['email']);
        $region = h($_POST['region_id']);
        $position = h($_POST['position']);
        $pass = !empty($_POST['pass']) ? hash('sha256', h($_POST['pass'])) : null;
        $admin = isset($_POST['admin']) ? intval($_POST['admin']) : null;

        // Проверка прав доступа для изменения admin
        if ($admin !== null && !$isOwner && !$isSuperAdmin) {
            $response['success'] = false;
            $response['error'] = 'Нет доступа для изменения статуса администратора';
            echo json_encode($response);
            exit;
        }

        $sql = "UPDATE user SET email = ?, region = ?, rang = ?" . ($pass ? ", pass = ?" : "") . ($admin !== null ? ", admin = ?" : "") . " WHERE id = ?";
        $stmt = $connect->prepare($sql);
        if ($pass && $admin !== null) {
            $stmt->bind_param("ssssii", $email, $region, $position, $pass, $admin, $id);
        } elseif ($pass) {
            $stmt->bind_param("ssssi", $email, $region, $position, $pass, $id);
        } elseif ($admin !== null) {
            $stmt->bind_param("sssii", $email, $region, $position, $admin, $id);
        } else {
            $stmt->bind_param("sssi", $email, $region, $position, $id);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $timestamp = date('d.m.Y H:i:s');
            $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
            $logMessage = "$timestamp Пользователь $userName отредактировал пользователя - $email";
            if ($admin !== null) {
                $logMessage .= " (статус admin изменен на $admin)";
            }
            $logMessage .= " <br>";
            $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
            $logStmt->bind_param("ss", $timestamp, $logMessage);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $id = intval($_POST['id']);
        $stmt = $connect->prepare("SELECT fio, email FROM user WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $connect->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $timestamp = date('d.m.Y H:i:s');
            $userName = isset($usr['name']) ? $usr['name'] : 'Unknown';
            $logMessage = "$timestamp Пользователь $userName удалил пользователя - {$user['fio']} / {$user['email']} <br>";
            $logStmt = $connect->prepare("INSERT INTO log (kogda, log) VALUES (?, ?)");
            $logStmt->bind_param("ss", $timestamp, $logMessage);
            $logStmt->execute();
            $logStmt->close();
        } else {
            $response['success'] = false;
            $response['error'] = $stmt->error;
        }
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'check_login') {
        $name = h($_POST['name']);
        $stmt = $connect->prepare("SELECT id FROM user WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['exists'] = $result->num_rows > 0;
        $stmt->close();
    }

    ob_end_clean(); // Очищаем буфер перед выводом JSON
    echo json_encode($response);
    exit;
}

if (isset($_GET['name']) && isset($_GET['type']) && $_GET['type'] === 'region') {
    $name = h($_GET['name']);
    $stmt = $connect->prepare("SELECT region, monthly_bonus FROM config WHERE region = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    ob_end_clean(); // Очищаем буфер перед выводом JSON
    echo json_encode($result ?: ['error' => 'Район не найден', 'name' => $name, 'monthly_bonus' => 8.00]);
    $stmt->close();
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $connect->prepare("SELECT id, fio, email, region, rang, name, admin FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    ob_end_clean(); // Очищаем буфер перед выводом JSON
    echo json_encode($result ?: ['error' => 'Пользователь не найден']);
    $stmt->close();
    exit;
}

ob_end_clean(); // Очищаем буфер перед выводом JSON
echo json_encode(['error' => 'Неверный запрос']);
exit;
?>