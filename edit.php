<?php
include "inc/function.php";
AutorizeProtect();
global $connect;
global $usr;

// Получаем параметры из GET запроса
$id = h($_GET['id']); 
$adress = h($_GET['adress']);

// Получаем текущие данные дома
$results = $connect->query("SELECT * FROM navigard_adress WHERE adress LIKE '$adress' LIMIT 1");
$this_house = $results->num_rows == 1 ? $results->fetch_array(MYSQLI_ASSOC) : '';

// Проверяем наличие новых данных, если нет - используем текущие
$complete = empty(h($_GET['check'])) ? 0 : h($_GET['check']);
$dopzamok = empty(h($_GET['dopzamok'])) ? $this_house['dopzamok'] : h($_GET['dopzamok']);

// Получаем данные о выходах
$vihod1 = h($_GET['vihod']['0']);
$vihod2 = h($_GET['vihod']['1']); 
$vihod3 = h($_GET['vihod']['2']);
$vihod4 = h($_GET['vihod']['3']);
$vihod5 = h($_GET['vihod']['4']);

// Получаем остальные параметры дома
$kluch = empty(h($_GET['kluch'])) ? $this_house['kluch'] : h($_GET['kluch']);
$krisha = empty(h($_GET['krisha'])) ? $this_house['krisha'] : h($_GET['krisha']);
$link = empty(h($_GET['link'])) ? $this_house['link'] : h($_GET['link']);
$pitanie = empty(h($_GET['pitanie'])) ? $this_house['pitanie'] : h($_GET['pitanie']);
$podjezd = empty(h($_GET['podjezd'])) ? $this_house['podjezd'] : h($_GET['podjezd']);
$pon = empty(h($_GET['pon'])) ? $this_house['pon'] : h($_GET['pon']);
$oboryda = empty(h($_GET['oboryda'])) ? $this_house['oboryda'] : h($_GET['oboryda']);
$lesnica = empty(h($_GET['lesnica'])) ? $this_house['lesnica'] : h($_GET['lesnica']);
$pred = empty(h($_GET['pred'])) ? $this_house['pred'] : h($_GET['pred']);
$phone = empty(h($_GET['phone'])) ? $this_house['phone'] : h($_GET['phone']);
$region = empty(h($_GET['region'])) ? $this_house['region'] : h($_GET['region']);

$history = $this_house['history'];

// Формируем лог изменений
$log1 = $adress != $this_house['adress'] ? "Смена адреса дома <br>" : "";
$log2 = $complete != $this_house['complete'] ? "Смена статуса завершенности дома <br>" : "";
$log3 = $dopzamok != $this_house['dopzamok'] ? "Смена статуса допзамков <br>" : "";
$log5 = $kluch != $this_house['kluch'] ? "Смена статуса ключей <br>" : "";
$log6 = $krisha != $this_house['krisha'] ? "Смена статуса крыши <br>" : "";
$log7 = $podjezd != $this_house['podjezd'] ? "Смена статуса подъездов <br>" : "";
$log8 = $pon != $this_house['pon'] ? "Смена статуса типа сети <br>" : "";
$log9 = $oboryda != $this_house['oboryda'] ? "Смена размещения оборудования <br>" : "";
$log10 = $lesnica != $this_house['lesnica'] ? "Смена наличия лестницы <br>" : "";
$log11 = $pred != $this_house['pred'] ? "Смена информации о председателе <br>" : "";
$log12 = $phone != $this_house['phone'] ? "Смена номера телефона председателя <br>" : "";
$log14 = !empty($text) ? "Добавленно новое примечание <br>" : "";

$new_status_home = "<br> $log1 $log2 $log3 $log5 $log6 $log7 $log8 $log9 $log10 $log11 $log12 $log14";

// Формируем текст примечания
$text = h($_GET['text']);
$date = date("d.m.Y H:i:s");
$fio = $usr['fio'];
$text_new = "[$date] $fio $text";
$text = $text_new ."<br>". $this_house['text'];

// Проверяем обязательные поля
$new = 0;
if (empty($adress)) {
    echo 'Введите адрес дома';
    exit();
}

// Записываем в лог
$text2 = 'отредактировал дом -';
$log = "$date $fio $text2 $adress $new_status_home";
$zap = "INSERT INTO navigard_log (kogda, log) VALUES ('$date','$log')";
$log = "$log <br> $history";

if ($connect->query($zap) === false) {
    echo "Ошибка: " . $zap . "<br>" . $connect->error;
}

// Формируем SQL запрос в зависимости от наличия примечания
$sql = "UPDATE navigard_adress SET
adress = '$adress',
vihod = '$vihod1',
vihod2 = '$vihod2',
vihod3 = '$vihod3',
vihod4 = '$vihod4',
vihod5 = '$vihod5',
oboryda = '$oboryda',
dopzamok = '$dopzamok',
kluch = '$kluch',
pred = '$pred',
phone = '$phone',
krisha = '$krisha',
lesnica = '$lesnica',
link = '$link',
pitanie = '$pitanie',
pon = '$pon',
podjezd = '$podjezd',
editor = '$fio',
region = '$region',
new = '$new',
complete = '$complete',
history = '$log'" .
(!empty($_GET['text']) ? ", text = '$text'" : "") .
" WHERE id = '$id'";

// Выполняем запрос и обрабатываем результат
if ($connect->query($sql) === true) {
    red_index("/navigard/result.php?adress=$adress&success");
    exit;
} else {
    echo "Ошибка: " . $sql . "<br>" . $connect->error;
}

include 'inc/foot.php';
