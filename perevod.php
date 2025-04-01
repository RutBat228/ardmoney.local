<?php
session_start();
include "inc/head.php";
AutorizeProtect();
$adress = trim(h($_GET['adress'])); // Получаем и очищаем адрес из GET параметра
?>
<head>
	<title>Перевод на PON <?=$adress?></title>
	<script type="text/javascript" src="searcher.js"></script>
</head>
<?php
// Форма для добавления абонента
echo '<ul class="list-group"><form method="GET" action="perevod_add.php">';
echo '<input type="text" autocomplete="off" id="search" name="adress" class="form-control" required title="Введите от 4 символов" placeholder="Введите адрес абонента">';
echo '<ul class="list-group"><div id="display"></div></ul>';
echo '<button type="submit" class="btn btn-primary btn-lg btn-block">Добавить абонента</button></li></form></ul>';
include 'inc/foot.php';