<?php
session_start();
include_once(dirname(dirname(dirname(__FILE__))) . "/inc/function.php");

?>
<!doctype html>
<html lang="ru">
<?php include(dirname(__FILE__) . "/style.php"); ?>
<body>
<div class="container-sm">
    <nav>
        <a href="/navigard/index.php">
            <img src="img/4.png" alt="NavigArd" class="logo">
        </a>
        <a href="/navigard/user.php" class="home-icon">
            <img src="img/profile.png" alt="Профиль" width="32">
        </a>
    </nav>
    
    <main role="main">
        <div class="jumbotron">
            <div class="col-md-8 col-sm-12 mx-auto">