<?php
session_start();

include(__DIR__ . '/function.php');
echo '<!doctype html><html lang="ru">';
include(__DIR__ . '/style.php'); // тег head с подключением стилей
echo '<body style="background: #ffffff url(img/background.webp) repeat;">';
echo '<div class="container-sm">';
include(__DIR__ . '/navbar.php'); // Навигационный бар

if (isset($_COOKIE['user'])) {
    $loggedInUser = $_COOKIE['user'];
    echo "<script>
        if (window.AndroidInterface) {
            window.AndroidInterface.setUserLogin('$loggedInUser');
        }
    </script>";
}
?>


<style>
.select2-container .select2-selection--single {
    height: 35px; /* Устанавливаем высоту в 35 пикселей */
    border: 1px solid #bfbdbd;
    background: white;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 35px; /* Выравниваем текст по высоте */
    color: #999;
}

/* Убедимся, что иконки и текст правильно отображаются */
.select2-results__option[style*="font-size: 9pt"] {
    font-size: 9pt !important;
}

/* Устанавливаем количество видимых пунктов (9) */
.select2-container--default .select2-results__options {
    max-height: 270px !important; /* 9 * 30px (примерная высота строки, можно подогнать) */
}
.select2-container .select2-selection--single {
    box-sizing: border-box;
    cursor: pointer;
    display: block;
    height: 35px !important;
    user-select: none;
    -webkit-user-select: none;
}

.select2-results__option .select2-results__option--selectable .select2-results__option--highlighted[aria-selected] {
    background-color: #5897fb5c !important;
    color: white !important;
}




</style>
<main role="main" style = "padding-bottom: 60px;" >
    <div style="min-height: calc(100vh - 9rem);padding: 0 0;    background: #fff;
" class="jumbotron">
        <div class="col-md-12 col-sm-12  mx-auto">
            