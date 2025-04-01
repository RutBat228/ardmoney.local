<?php
include(__DIR__ . '/../inc/db.php');
global $connect;

header('Content-Type: application/json; charset=utf-8');

function sanitizeInput($input) {
    return htmlspecialchars(trim(strip_tags(stripcslashes($input))), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['search']) || isset($_POST['referal'])) {
    $searchTerm = sanitizeInput($_POST['search'] ?? $_POST['referal']);
    $limit = isset($_POST['search']) ? 5 : 10; // 5 для живого поиска, 10 для обычного
    
    // Используем подготовленный запрос для безопасности
    $query = "SELECT id, adress FROM navigard_adress WHERE adress LIKE ? LIMIT ?";
    $stmt = $connect->prepare($query);
    $searchPattern = "%{$searchTerm}%";
    $stmt->bind_param("si", $searchPattern, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'adress' => $row['adress']
        ];
    }
    
    // Форматируем вывод в зависимости от типа запроса
    if (isset($_POST['referal'])) {
        $output = [];
        foreach ($results as $row) {
            $output[] = "<li>{$row['adress']}</li>";
        }
        echo json_encode(['html' => implode("\n", $output)]);
    } else {
        echo json_encode($results);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Не указан поисковый запрос']);
}
