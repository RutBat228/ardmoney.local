<?php
session_start();
include("inc/function.php");

// Проверка авторизации и прав доступа
AutorizeProtect();
global $usr;
global $connect;

if ($usr['admin'] != "1" && $usr['name'] != "RutBat") {
    die("Ошибка: недостаточно прав для доступа к странице администрирования.");
}

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Получение текущих праздничных дней из базы
$stmt = $connect->prepare("SELECT holiday_date FROM holidays WHERE YEAR(holiday_date) = ?");
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();
$holidays = [];
while ($row = $result->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}

// Проверка уведомлений из сессии
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление праздничными днями - <?=$selected_year?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>

        .calendar-table td { cursor: pointer; text-align: center; padding: 5px; }
        .calendar-table th { text-align: center; padding: 5px; }
        .calendar-table { font-size: 0.9rem; }
        .holiday {
            background-color: #dc3545 !important;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/user.php">ArdMoney</a>
            <div class="navbar-nav">
                <a class="nav-link" href="?year=<?= $selected_year - 1 ?>">Предыдущий год</a>
                <span class="nav-link active"><?=$selected_year?></span>
                <a class="nav-link" href="?year=<?= $selected_year + 1 ?>">Следующий год</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Праздничные дни <?=$selected_year?></h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="adm_prazdnik_obr.php?year=<?=$selected_year?>">
            <div class="row">
                <?php
                for ($month = 1; $month <= 12; $month++) {
                    $date = new DateTime("$selected_year-$month-01");
                    $month_name = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'][$month - 1];
                    ?>
                    <div class="col-md-4 mb-4">
                        <h5><?=$month_name?></h5>
                        <table class="table table-bordered calendar-table">
                            <thead>
                                <tr>
                                    <th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $first_day = (int)$date->format('N') - 1; // Понедельник = 0
                                $days_in_month = (int)$date->format('t');
                                $day = 1;
                                $week = array_fill(0, 7, '');
                                for ($i = 0; $i < $first_day; $i++) {
                                    $week[$i] = '';
                                }
                                while ($day <= $days_in_month) {
                                    $week[$first_day] = $day;
                                    $date_str = sprintf("%04d-%02d-%02d", $selected_year, $month, $day);
                                    if ($first_day == 6 || $day == $days_in_month) {
                                        echo '<tr>';
                                        foreach ($week as $d) {
                                            $class = $d && in_array(sprintf("%04d-%02d-%02d", $selected_year, $month, $d), $holidays) ? 'holiday' : '';
                                            $data_date = $d ? sprintf("%04d-%02d-%02d", $selected_year, $month, $d) : '';
                                            $checked = $class ? 'checked' : '';
                                            echo "<td class='$class' onclick='toggleHoliday(this)'>";
                                            if ($d) {
                                                echo "<input type='checkbox' name='holidays[]' value='$data_date' $checked style='display:none;'>";
                                                echo $d;
                                            }
                                            echo "</td>";
                                        }
                                        echo '</tr>';
                                        $week = array_fill(0, 7, '');
                                        $first_day = -1;
                                    }
                                    $first_day++;
                                    $day++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 mt-3">
                <i class="fas fa-save me-2"></i> Сохранить
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleHoliday(cell) {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            if (checkbox.checked) {
                cell.classList.add('holiday');
            } else {
                cell.classList.remove('holiday');
            }
        }
    }
    </script>
    <?php include 'inc/foot.php'; ?>
</body>
</html>