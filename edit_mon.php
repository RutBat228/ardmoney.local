<?php
include "inc/function.php";
AutorizeProtect();
access();
global $connect;
global $usr;

$material = isset($_GET['material']) ? $_GET['material'] : null;
$material_count = isset($_GET['material_count']) ? $_GET['material_count'] : null;
$material_delete = isset($_GET['material_delete']) ? $_GET['material_delete'] : null;
$name1 = isset($_GET['vid_rabot1']) ? $_GET['vid_rabot1'] : null;
$count1 = isset($_GET['count1']) ? $_GET['count1'] : null;
$name2 = isset($_GET['vid_rabot2']) ? $_GET['vid_rabot2'] : null;
$count2 = isset($_GET['count2']) ? $_GET['count2'] : null;
$name3 = isset($_GET['vid_rabot3']) ? $_GET['vid_rabot3'] : null;
$count3 = isset($_GET['count3']) ? $_GET['count3'] : null;
$name4 = isset($_GET['vid_rabot4']) ? $_GET['vid_rabot4'] : null;
$count4 = isset($_GET['count4']) ? $_GET['count4'] : null;
$mon_id = isset($_GET['mon_id']) ? $_GET['mon_id'] : null;
$summa = isset($_GET['summa']) ? $_GET['summa'] : null;
$kajdomu = isset($_GET['kajdomu']) ? $_GET['kajdomu'] : null;
$other = isset($_GET['other']) ? $_GET['other'] : null;

$technik1 = isset($_GET['technik']['0']) ? $_GET['technik']['0'] : null;
$technik2 = isset($_GET['technik']['1']) ? $_GET['technik']['1'] : null;
$technik3 = isset($_GET['technik']['2']) ? $_GET['technik']['2'] : null;
$technik4 = isset($_GET['technik']['3']) ? $_GET['technik']['3'] : null;
$technik5 = isset($_GET['technik']['4']) ? $_GET['technik']['4'] : null;
$technik6 = isset($_GET['technik']['5']) ? $_GET['technik']['5'] : null;
$technik7 = isset($_GET['technik']['6']) ? $_GET['technik']['6'] : null;
$technik8 = isset($_GET['technik']['7']) ? $_GET['technik']['7'] : null;

if (empty($count1)) {
    $count1 = 1;
}
if (empty($count2)) {
    $count2 = 1;
}
if (empty($count3)) {
    $count3 = 1;
}
if (empty($count4)) {
    $count4 = 1;
}

if (isset($_GET['delete'])) {
    $id_del = $_GET['delete'];
    del_mon($id_del);
    edit_montaj_summa($mon_id);
    $str = $mon_id;
    $encodedStr = base64_encode($str);
    red_index("result.php?vid_id=$encodedStr");
    exit;
}

if (!empty($material)) {
    if (empty($material_count)) {
        $material_count = 1;
    }
    $stmt = $connect->prepare("INSERT INTO used_material (name, count, id_montaj) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $material, $material_count, $mon_id);
    if ($stmt->execute() === false) {
        echo $connect->error;
    }
    $stmt->close();
}

if (!empty($material_delete)) {
    $stmt = $connect->prepare("DELETE FROM used_material WHERE id = ? AND id_montaj = ?");
    $stmt->bind_param("ii", $material_delete, $mon_id);
    $stmt->execute();
    $stmt->close();
    $str = $mon_id;
    $encodedStr = base64_encode($str);
    red_index("result.php?vid_id=$encodedStr");
    exit;
}

$stmt = $connect->prepare("SELECT * FROM `montaj` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $mon_id);
$stmt->execute();
$montaj = $stmt->get_result();
if ($montaj->num_rows != 0) {
    $mon = $montaj->fetch_array(MYSQLI_ASSOC);
}
$stmt->close();

$tech1 = $mon['technik1'];
$tech2 = $mon['technik2'];
$tech3 = $mon['technik3'];
$tech4 = $mon['technik4'];
$tech5 = $mon['technik5'];
$tech6 = $mon['technik6'];
$tech7 = $mon['technik7'];
$tech8 = $mon['technik8'];
$adress_mon = $mon['adress'];
$id_mon = $mon['id'];

$array_montaj = $connect->prepare("SELECT * FROM `array_montaj` WHERE `mon_id` = ? AND `name` = ?");
$array_montaj->bind_param("is", $mon_id, $name1);
$array_montaj->execute();
$result = $array_montaj->get_result();
if ($result->num_rows == 0) {
    if (!empty($name1) && !empty($mon_id)) {
        $vid_montaj1 = $connect->prepare("SELECT * FROM `vid_rabot` WHERE `name` = ? LIMIT 1");
        $vid_montaj1->bind_param("s", $name1);
        $vid_montaj1->execute();
        $result1 = $vid_montaj1->get_result();
        if ($result1->num_rows != 0) {
            $vid_mon1 = $result1->fetch_array(MYSQLI_ASSOC);
        }
        $vid_montaj1->close();

        $pric1 = $vid_mon1['price_tech'];
        $price1 = $pric1 * $count1;
        $text = !empty($other) ? $other : $vid_mon1['text'];

        $stmt = $connect->prepare("INSERT INTO array_montaj (name, mon_id, count, price, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $name1, $mon_id, $count1, $price1, $text);
        if ($stmt->execute() === false) {
            echo "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    }
}
$array_montaj->close();

$array_montaj = $connect->prepare("SELECT * FROM `array_montaj` WHERE `mon_id` = ? AND `name` = ?");
$array_montaj->bind_param("is", $mon_id, $name2);
$array_montaj->execute();
$result = $array_montaj->get_result();
if ($result->num_rows == 0) {
    if (!empty($name2) && !empty($mon_id)) {
        $vid_montaj2 = $connect->prepare("SELECT * FROM `vid_rabot` WHERE `name` = ? LIMIT 1");
        $vid_montaj2->bind_param("s", $name2);
        $vid_montaj2->execute();
        $result2 = $vid_montaj2->get_result();
        if ($result2->num_rows != 0) {
            $vid_mon2 = $result2->fetch_array(MYSQLI_ASSOC);
        }
        $vid_montaj2->close();

        $pric2 = $vid_mon2['price_tech'];
        $price2 = $pric2 * $count2;
        $text = !empty($other) ? $other : $vid_mon2['text'];

        $stmt = $connect->prepare("INSERT INTO array_montaj (name, mon_id, count, price, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $name2, $mon_id, $count2, $price2, $text);
        if ($stmt->execute() === false) {
            echo "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    }
}
$array_montaj->close();

$array_montaj = $connect->prepare("SELECT * FROM `array_montaj` WHERE `mon_id` = ? AND `name` = ?");
$array_montaj->bind_param("is", $mon_id, $name3);
$array_montaj->execute();
$result = $array_montaj->get_result();
if ($result->num_rows == 0) {
    if (!empty($name3) && !empty($mon_id)) {
        $vid_montaj3 = $connect->prepare("SELECT * FROM `vid_rabot` WHERE `name` = ? LIMIT 1");
        $vid_montaj3->bind_param("s", $name3);
        $vid_montaj3->execute();
        $result3 = $vid_montaj3->get_result();
        if ($result3->num_rows != 0) {
            $vid_mon3 = $result3->fetch_array(MYSQLI_ASSOC);
        }
        $vid_montaj3->close();

        $pric3 = $vid_mon3['price_tech'];
        $price3 = $pric3 * $count3;
        $text = !empty($other) ? $other : $vid_mon3['text'];

        $stmt = $connect->prepare("INSERT INTO array_montaj (name, mon_id, count, price, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $name3, $mon_id, $count3, $price3, $text);
        if ($stmt->execute() === false) {
            echo "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    }
}
$array_montaj->close();

$array_montaj = $connect->prepare("SELECT * FROM `array_montaj` WHERE `mon_id` = ? AND `name` = ?");
$array_montaj->bind_param("is", $mon_id, $name4);
$array_montaj->execute();
$result = $array_montaj->get_result();
if ($result->num_rows == 0) {
    if (!empty($name4) && !empty($mon_id)) {
        $vid_montaj4 = $connect->prepare("SELECT * FROM `vid_rabot` WHERE `name` = ? LIMIT 1");
        $vid_montaj4->bind_param("s", $name4);
        $vid_montaj4->execute();
        $result4 = $vid_montaj4->get_result();
        if ($result4->num_rows != 0) {
            $vid_mon4 = $result4->fetch_array(MYSQLI_ASSOC);
        }
        $vid_montaj4->close();

        $pric4 = $vid_mon4['price_tech'];
        $price4 = $pric4 * $count4;
        $text = !empty($other) ? $other : $vid_mon4['text'];

        $stmt = $connect->prepare("INSERT INTO array_montaj (name, mon_id, count, price, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siids", $name4, $mon_id, $count4, $price4, $text);
        if ($stmt->execute() === false) {
            echo "Ошибка: " . $stmt->error;
        }
        $stmt->close();
    }
}
$array_montaj->close();

$fields = [
    ['technik1', $technik1],
    ['technik2', $technik2],
    ['technik3', $technik3],
    ['technik4', $technik4],
    ['technik5', $technik5],
    ['technik6', $technik6],
    ['technik7', $technik7],
    ['technik8', $technik8]
];

foreach ($fields as $field) {
    $column = $field[0];
    $value = $field[1];
    $stmt = $connect->prepare("UPDATE montaj SET `$column` = ? WHERE `id` = ?");
    $empty = '';
    $param = !empty($value) ? $value : $empty;
    $stmt->bind_param("si", $param, $mon_id);
    if ($stmt->execute() === false) {
        echo "Ошибка: " . $stmt->error;
    }
    $stmt->close();
}

edit_montaj_summa($mon_id);
$str = $mon_id;
$encodedStr = base64_encode($str);

unset($material, $material_delete, $name1, $name2, $name3, $name4, $count1, $count2, $count3, $count4, $mon_id, $summa, $kajdomu, $other);
red_index("result.php?vid_id=$encodedStr");
?>