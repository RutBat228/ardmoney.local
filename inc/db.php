<?php

// Константы для подключения к базе данных
const HOST = "localhost";      // Хост базы данных
const USER = "root";       // Имя пользователя
const BAZA = "ardmoney";       // Название базы данных 
const PASS = "root";       // Пароль


const TABLE_PREFIX = "navigard_"; // Префикс для таблиц навигарда

// Создаем подключение к базе данных
global $connect;
$connect = new mysqli(HOST, USER, PASS, BAZA);
$connect->query("SET NAMES 'utf8'"); // Устанавливаем кодировку UTF-8



if(!empty($_COOKIE['user'])){
    $user = htmlentities($_COOKIE['user']);

}else{
    $user = "";
}

$user = $connect->query("SELECT * FROM `user` WHERE `name` = '" . $user . "'");
if ($user->num_rows != 0)
    $usr = $user->fetch_array(MYSQLI_ASSOC);