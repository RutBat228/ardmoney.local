<?php
// Файл: ardmoney.ru/api/check_version.php

// Устанавливаем заголовок для ответа в формате JSON
header('Content-Type: application/json');

// Подключаем файл с настройками базы данных
include '../inc/db.php';

// Путь к файлу логов
$logFile = __DIR__ . '/update_log.txt';

// Функция для записи в лог
function logToFile($message, $file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
}

// Получаем текущую версию и changelog из базы данных
$query = "SELECT version, changelog FROM apps ORDER BY version DESC LIMIT 1";
$result = $connect->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentVersion = $row['version'];
    $changelog = $row['changelog'];
    $downloadUrl = "https://ardmoney.ru/ardmoney.apk"; // Можно добавить колонку в БД, если URL меняется
} else {
    $currentVersion = "2.2.2"; // Дефолтное значение
    $changelog = "Начальная версия";
    $downloadUrl = "https://ardmoney.ru/ardmoney.apk";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Читаем тело запроса (JSON)
    $input = file_get_contents('php://input');
    logToFile("Получено тело запроса: $input", $logFile);
    
    // Декодируем JSON
    $data = json_decode($input, true);
    $userVersion = isset($data['version']) ? $data['version'] : '';

    if (empty($userVersion)) {
        logToFile("Ошибка: версия не указана в теле запроса", $logFile);
        echo json_encode(['error' => 'Не указана версия приложения']);
        exit;
    }

    logToFile("Получен запрос с версией: $userVersion", $logFile);

    // Сравниваем версии с помощью version_compare
    $comparison = version_compare($userVersion, $currentVersion);
    if ($comparison < 0) { // Если пользовательская версия меньше серверной
        $response = [
            'updateNeeded' => true,
            'newVersion' => $currentVersion,
            'downloadUrl' => $downloadUrl,
            'changelog' => $changelog // Добавляем changelog в ответ
        ];
        logToFile("Обновление требуется (user: $userVersion < server: $currentVersion). Ответ: " . json_encode($response), $logFile);
        echo json_encode($response);
    } else {
        $response = ['updateNeeded' => false];
        logToFile("Обновление не требуется (user: $userVersion >= server: $currentVersion). Ответ: " . json_encode($response), $logFile);
        echo json_encode($response);
    }
} else {
    logToFile("Ошибка: неверный метод запроса ($_SERVER[REQUEST_METHOD])", $logFile);
    echo json_encode(['error' => 'Неверный метод запроса']);
}

$connect->close();
?>