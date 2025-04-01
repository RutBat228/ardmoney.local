<?php
// submit_oil_change.php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение значения одометра на момент замены масла
    $odometer_at_change = isset($_POST['odometerAtChange']) ? intval($_POST['odometerAtChange']) : 0;

    if ($odometer_at_change <= 0) {
        echo "Некорректный пробег для замены масла.";
        exit();
    }

    // Вставка новой записи о замене масла
    $sql_insert = "INSERT INTO oil_changes (odometer_at_change, date_of_change) VALUES (?, CURDATE())";
    $stmt = $conn->prepare($sql_insert);
    if ($stmt) {
        $stmt->bind_param("i", $odometer_at_change);
        if ($stmt->execute()) {
            // Успешная вставка
            header("Location: index.php?oil_change=success");
            exit();
        } else {
            // Ошибка при вставке
            echo "Ошибка при сохранении замены масла: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Ошибка подготовки запроса
        echo "Ошибка подготовки запроса: " . $conn->error;
    }

    $conn->close();
}
?>
