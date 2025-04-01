<?php
// Отключаем вывод ошибок в ответе, чтобы не ломать JSON
ini_set('display_errors', 0);
error_reporting(0);

include "inc/function.php";
global $connect;

// Убеждаемся, что подключение к базе данных работает
if (!$connect) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Database connection failed']));
}

$mon_id = isset($_GET['mon_id']) ? h($_GET['mon_id']) : null;

if (!$mon_id) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'No mon_id provided']));
}

// Получаем данные о монтаже
$montaj = $connect->query("SELECT * FROM `montaj` WHERE `id` = '$mon_id' LIMIT 1");
if ($montaj->num_rows == 0) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Montaj not found']));
}
$mon = $montaj->fetch_array(MYSQLI_ASSOC);

// Получаем список работ
$montaj_items = [];
$sql = "SELECT * FROM `array_montaj` WHERE mon_id = '$mon_id'";
$results = mysqli_query($connect, $sql);
if ($results) {
    while ($vid_rabot = mysqli_fetch_array($results)) {
        $montaj_items[] = [
            'id' => $vid_rabot['id'],
            'name' => $vid_rabot['name'],
            'count' => $vid_rabot['count'],
            'price' => $vid_rabot['price'],
            'text' => $vid_rabot['text'],
            'status_baza' => $vid_rabot['status_baza']
        ];
    }
} else {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Failed to fetch montaj items: ' . $connect->error]));
}

// Получаем материалы
$materials = [];
$used_material = "SELECT * FROM used_material WHERE id_montaj = $mon_id";
$um = mysqli_query($connect, $used_material);
if ($um) {
    while ($material = mysqli_fetch_array($um)) {
        $materials[] = [
            'id' => $material['id'],
            'name' => $material['name'],
            'count' => $material['count']
        ];
    }
} else {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Failed to fetch materials: ' . $connect->error]));
}

// Получаем список техников
$ebat_code = 0;
$techniks = "";
for ($i = 1; $i <= 8; $i++) {
    $tech = "technik$i";
    if (!empty($mon[$tech])) {
        $ebat_code = $i;
        $techniks .= $mon[$tech] . ",";
    }
}
$techniks = rtrim($techniks, ",");

// Формируем ответ
$response = [
    'montaj_items' => $montaj_items,
    'materials' => $materials,
    'summa' => $mon['summa'],
    'kajdomu' => $mon['kajdomu'],
    'techniks' => $techniks,
    'ebat_code' => $ebat_code,
    'status' => $mon['status'],
    'status_baza' => $mon['status_baza'],
    'technik1' => $mon['technik1'],
    'technik2' => $mon['technik2'],
    'technik3' => $mon['technik3'],
    'technik4' => $mon['technik4'],
    'technik5' => $mon['technik5'],
    'technik6' => $mon['technik6'],
    'technik7' => $mon['technik7'],
    'technik8' => $mon['technik8']
];

// Устанавливаем заголовок и выводим только JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; // Убеждаемся, что ничего лишнего не выводится
?>