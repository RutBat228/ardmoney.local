<?php
session_start();
include("inc/function.php");

// Проверка авторизации и прав доступа
AutorizeProtect();
global $usr;
global $connect;

if ($usr['admin'] != "1" && $usr['name'] != "RutBat") {
    die("Ошибка: недостаточно прав для доступа к странице администрирования.");
}

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$holidays = $_POST['holidays'] ?? [];

// Очистка существующих праздничных дней за год
$stmt = $connect->prepare("DELETE FROM holidays WHERE YEAR(holiday_date) = ?");
$stmt->bind_param("i", $selected_year);
$stmt->execute();

// Добавление новых праздничных дней
if (!empty($holidays)) {
    $stmt = $connect->prepare("INSERT IGNORE INTO holidays (holiday_date) VALUES (?)");
    foreach ($holidays as $date) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { // Проверка формата даты
            $stmt->bind_param("s", $date);
            $stmt->execute();
        }
    }
}

if ($stmt->error) {
    $_SESSION['error_message'] = "Ошибка при сохранении праздничных дней: " . $stmt->error;
} else {
    $_SESSION['success_message'] = "Праздничные дни успешно сохранены!";
}

// Перенаправление обратно на страницу администрирования
header("Location: adm_prazdnik.php?year=$selected_year");
exit();
?>