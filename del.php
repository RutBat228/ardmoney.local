<?php
include "inc/head.php";
AutorizeProtect();
global $connect;
global $usr;

// Получаем ID и адрес из GET параметров
$id = e(h($_GET['id']));
$adress = e(h($_GET['adress']));

// Формируем дату и данные для лога
$date = date("d.m.Y H:i:s");
$user = $usr['name'];
$log = "Пользователь $user удалил дом с id $id";

// Записываем в лог
$zap = "INSERT INTO navigard_log (kogda, log) VALUES ('$date', '$log')";
if ($connect->query($zap) === false) {
    echo $connect->error;
}

// Удаляем запись из базы
$sql = "DELETE FROM navigard_adress WHERE id = '$id'";
if (mysqli_query($connect, $sql)) {
    red_index("all?id=ok");
    exit;
} else {
    echo "Error deleting record: " . mysqli_error($connect);
}

mysqli_close($connect);
include 'inc/foot.php';
