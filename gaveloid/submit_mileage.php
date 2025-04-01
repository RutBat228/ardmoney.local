<?php
// submit_mileage.php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение и проверка данных из формы
    $actual_mileage = isset($_POST['actualMileage']) ? intval($_POST['actualMileage']) : 0;
    $displayed_mileage = isset($_POST['displayedMileage']) ? intval($_POST['displayedMileage']) : 0;

    // Расчёт намотанного пробега и пройденного расстояния
    $wrap_distance = $displayed_mileage - $actual_mileage;
    $distance_driven = $wrap_distance > 0 ? $actual_mileage : 0;  // Пройденное расстояние

    // Подготовленный запрос для вставки данных
    $sql = "INSERT INTO mileage (actual_mileage, displayed_mileage, mileage_added_at, distance_driven, wrap_distance)
            VALUES (?, ?, CURDATE(), ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiii", $actual_mileage, $displayed_mileage, $distance_driven, $wrap_distance);
        if ($stmt->execute()) {
            // Успешная вставка
            header("Location: index.php?mileage_added=success");
            exit();
        } else {
            // Ошибка при вставке
            echo "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Ошибка подготовки запроса
        echo "Ошибка подготовки запроса: " . $conn->error;
    }

    $conn->close();
}
?>
