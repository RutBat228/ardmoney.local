<?php
session_start();
include("inc/function.php"); // Тут висят все функции сайта.
echo '<!doctype html><html lang="ru">';
include("inc/style.php"); // тег head в котором указываются все стили сайта
echo '<body style = "background: #ffffff url(img/background.webp) repeat;">';
echo '<div class="container-sm">';
?>
<main role="main">
    <div class="jumbotron" style = "padding: 9% 0;" >
        <div style="display: grid;place-items: center;">
            <?
AutorizeProtect();
access();
global $connect;
global $usr;
?>
    <head>
        <title>Добавить работу</title>
        <link rel="stylesheet" href="css/itogi.css">
    </head>
<form method="GET" action="add_mon.php" style="
    font-family: system-ui;
">
    <div class="auth-container" >
    <a href = "/" ><img src="img/logo.webp" alt="Логотип"></a>


<div>
  За 2024 год ты наколядовал около <br>
  <? 
echo '<div class="year-amount">';
prim_zp_year("$usr[fio]", 2024);
echo '</div>';
$usr_stat = $usr['fio'];
?>
<ol class="list-group list-group-numbered text-start">
  <li class="list-group-item"><?php statistic($usr_stat,'Подкл', 2024,'Подключений');?></li>
  <li class="list-group-item"><?php statistic($usr_stat,'перевод', 2024,'переводов');?></li>
  <li class="list-group-item"><?php statistic($usr_stat,'кабел', 2024,'замен кабеля');?></li>
  <li class="list-group-item"><?php statistic($usr_stat,'глоб', 2024,'крупных глобалок');?> </li>
</ul>
<?

?>

</div>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
        <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Varela+Round&amp;display=swap'>
        <link rel="stylesheet" href="css/checbox.css">
        <br>

    </div>
    <br>
    </div>
</form>
<?php include 'inc/foot.php';
