<?php
session_start();
include("inc/head.php");
access();
AutorizeProtect();
global $connect;
global $usr;

echo '<div class="contadiner">';

// Получение данных из GET-запроса
$adress = isset($_GET['adress']) ? $_GET['adress'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';
if (isset($_GET['technik']['0'])) { $technik1 = $_GET['technik']['0']; } else { $technik1 = ''; }
if (isset($_GET['technik']['1'])) { $technik2 = $_GET['technik']['1']; } else { $technik2 = ''; }
if (isset($_GET['technik']['2'])) { $technik3 = $_GET['technik']['2']; } else { $technik3 = ''; }
if (isset($_GET['technik']['3'])) { $technik4 = $_GET['technik']['3']; } else { $technik4 = ''; }
if (isset($_GET['technik']['4'])) { $technik5 = $_GET['technik']['4']; } else { $technik5 = ''; }
if (isset($_GET['technik']['5'])) { $technik6 = $_GET['technik']['5']; } else { $technik6 = ''; }
if (isset($_GET['technik']['6'])) { $technik7 = $_GET['technik']['6']; } else { $technik7 = ''; }
if (isset($_GET['technik']['7'])) { $technik8 = $_GET['technik']['7']; } else { $technik8 = ''; }
$text = isset($_GET['text']) ? $_GET['text'] : '';
$date = date("Y-m-d H:i:s");
$dogovor = 0;

// Проверка обязательного поля
if (empty($adress)) {
    echo 'Введите адрес монтажа';
    exit;
}

$user = $usr['name'];

// Используем подготовленное выражение для вставки данных
$stmt = $connect->prepare("INSERT INTO montaj (adress, technik1, technik2, technik3, technik4, technik5, technik6, technik7, technik8, text, region, date, dogovor) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt === false) {
    echo "Ошибка подготовки запроса: " . $connect->error;
    exit;
}

$stmt->bind_param(
    "ssssssssssssi",
    $adress,
    $technik1,
    $technik2,
    $technik3,
    $technik4,
    $technik5,
    $technik6,
    $technik7,
    $technik8,
    $text,
    $region,
    $date,
    $dogovor
);

if ($stmt->execute() === true) {
    $last_id = $connect->insert_id;
    $id = base64_encode($last_id);
} else {
    echo "Ошибка выполнения запроса: " . $stmt->error;
    $stmt->close();
    exit;
}

$stmt->close();
?>
<meta http-equiv="refresh" content="0;URL='result.php?vid_id=<?= $id ?>'">
</div>
<?php
include('inc/foot.php');
?>