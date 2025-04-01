<?php
session_start();
include_once("inc/function.php");

AutorizeProtect();
access();
GLOBAL $usr;
// Инициализируем переменные
$view_complete = '';

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    $date_current = $_GET['date'];
} else {
    $date = date("Y-m-d");
    $date_current = substr($date, 0, -3);
}

// Formulate the SQL query
$sql = "SELECT * FROM `montaj` WHERE `region` = ? ";
$params = [$usr['region']];

if ($usr['rang'] != "Мастер участка") {
    if ($usr['admin_view'] == 1) {
        $sql .= " AND (`technik1` = ? OR `technik2` = ? OR `technik3` = ? OR `technik4` = ? OR `technik5` = ? OR `technik6` = ? OR `technik7` = ? OR `technik8` = ?)" . $view_complete . " AND visible = 1 ORDER BY `date` DESC, `id` DESC";
        $params = array_merge($params, array_fill(0, 8, $usr['fio']));
    } else {
        if (isset($_GET['current_user'])) {
            $sql .= " AND (`technik1` = ? OR `technik2` = ? OR `technik3` = ? OR `technik4` = ? OR `technik5` = ? OR `technik6` = ? OR `technik7` = ? OR `technik8` = ?)" . $view_complete . " AND visible = 1 ORDER BY `date` DESC, `id` DESC";
            $params = array_merge($params, array_fill(0, 8, $_GET['current_user']));
        } else {
            if ($usr['admin'] == "1") {
                if ($usr['name'] == "RutBat") {
                    $sql .= $view_complete . " ORDER BY `id` DESC";
                } else {
                    $sql .= " AND (`technik1` = ? OR `technik2` = ? OR `technik3` = ? OR `technik4` = ? OR `technik5` = ? OR `technik6` = ? OR `technik7` = ? OR `technik8` = ?)" . $view_complete . " AND visible = 1 ORDER BY `date` DESC, `id` DESC";
                    $params = array_merge($params, array_fill(0, 8, $usr['fio']));
                }
            }
        }
    }
} else {
    $sql .= $view_complete . " ORDER BY `id` DESC";
}

$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
mysqli_stmt_execute($stmt);
$res_data = mysqli_stmt_get_result($stmt);

echo '<ul class="list-group">';

while ($row = mysqli_fetch_array($res_data)) {
    $date = new DateTime;
    [$h, $m, $s] = explode(':', $date->format('H:i:s.u'));
    $cur_date = $h * 3600 + $m * 60 + $s;
    $test = time() - strtotime($row['date']);
    $color = ($cur_date < $test) ? "black" : "red";
    
    $data_tmp = substr($row['date'], 0, 7);
    
    if ($data_tmp === $date_current) {
        $str = $row['id'];
        $encodedStr = base64_encode($str);
        
        $bg = "#fff";
        $font = 'font-family: inherit;';
        
        if ($row['status'] == 1) {
            $bg = "success_color";
            $font = 'font-weight: normal;';
        } elseif ($row['status_baza'] == 1) {
            $bg = "baza_color";
            $font = 'font-weight: normal;';
        }

        echo '<div id="skrivat">';
        echo "<a id='search_view' class='search_view' style='$color' href='result.php?vid_id=$encodedStr' data-value='" . htmlspecialchars($row['adress']) . "'>";
        echo "<li class='hui list-group-item d-flex justify-content-between align-items-center $bg' style='padding: 7px 10px 5px 10px;'>";
        echo "<label style='color: $color; $font;'>";
        echo "<small class='form-text'>";
        if ($row['dogovor'] == 1) {
            echo '<img src="/img/dogovor.svg" width="24px">';
        }
        echo (new DateTime($row['date']))->format('Y-m-d H:i');
        echo '</small>';
        echo htmlspecialchars($row['adress']);
        echo '<br>';
        if ($row['status'] == 0) {
            echo "<small class='form-text'>";
            echo $row['technik1'] . $row['technik2'] . $row['technik3'] . $row['technik4'] . $row['technik5'];
            echo ":";
            echo htmlspecialchars($row['text']);
            echo '</small>';
        }
        echo '</label>';

        if ($usr['admin'] == 1) {
            echo "<a href='?delete=" . $encodedStr . "' style='color: white;text-decoration: none;'><span class='badge bg-danger rounded-pill'>X</span></a>";
        }else{
            echo $usr['admin'];
        }
        
        echo "</li></a>";
        echo "<hr class='hr_index'>";
        echo '</div>';
    }
}

echo '</ul>';

mysqli_stmt_close($stmt);
mysqli_close($connect);
?>
