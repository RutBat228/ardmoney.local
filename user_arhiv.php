<?php
?>
<div class="alert alert-danger" role="alert">
    !!Информация прошлого года!!
</div>
<?
include "inc/head.php";
access();
AutorizeProtect();
global $connect;
global $usr;
?>

<head>
    <title>Монтажи прошлого года</title>
    <link rel="stylesheet" href="css/user.css">
</head>
<?
$months = [
    "01" => "Январь",
    "02" => "Февраль",
    "03" => "Март",
    "04" => "Апрель",
    "05" => "Май",
    "06" => "Июнь",
    "07" => "Июль",
    "08" => "Август",
    "09" => "Сентябрь",
    "10" => "Октябрь",
    "11" => "Ноябрь",
    "12" => "Декабрь",
];

$lastyear = date('Y', strtotime('last year'));
$date_blyat = $_GET['date'] ?? date('Y-12', strtotime('last year'));
$month = $months[substr($date_blyat, 5, 2)] ?? $months[date('m')];
$date_blyat = $date_blyat;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid navbar-container">
        <a class="navbar-brand" href="#"></a>
        <div class="navbar-collapse" id="navbarNavDarkDropdown">
            <ul class="navbar-nav navbar-nav-custom">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDarkDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $month ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="navbarDarkDropdownMenuLink" style="position: absolute;">
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-01">Январь</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-02">Февраль</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-03">Март</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-04">Апрель</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-05">Май</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-06">Июнь</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-07">Июль</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-08">Август</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-09">Сентябрь</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-10">Октябрь</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-11">Ноябрь</a></li>
                        <li><a class="dropdown-item" href="?date=<?= $lastyear ?>-12">Декабрь</a></li>
                    </ul>
                </li>
                <?php
                if (!empty(htmlentities($_COOKIE['user']))) {
                ?>
                    <ul style="float: right;">
                        <li>
                            <a href="user.php">
                                <img src="/img/home.png" alt="Домой" style="width: 40px;padding-bottom: 7px;">
                            </a>
                            <a href="search_montaj.php">
                                <img src="/img/search.png" alt="Поиск" style="width: 40px;padding-bottom: 7px;">
                </a>
                        </li>
                    </ul>
            
        <?php
                } ?>
            </ul>
        </div>
    </div>
</nav>
<div class="d-grid gap-2">
    <a class="btn btn-danger" href="ins.php">Инструкция</a>
</div>
<ul class="list-group">
    <li class="list-group-item" style="padding: 0;">
        <?
        if ($usr['name'] == "RutBat") {
            echo '<img class="mx-auto d-block w-100" src="img/user_RutBat.png">';
        } elseif ($usr['name'] == "Игорь") {
            echo '<img class="mx-auto d-block w-100" src="img/user_Игорь.png">';
        } elseif ($usr['name'] == "kovalev") {
            echo '<img class="mx-auto d-block w-100" src="img/user_Вова.png">';
        } elseif ($usr['name'] == "grisnevskijp@gmail.com") {
            echo '<img class="mx-auto d-block w-100" src="img/user_Паша.png">';
        } elseif ($usr['name'] == "Юра") {
            echo '<img class="mx-auto d-block w-100" src="img/user_Юра.png">';
        } else {
            echo '<img class="mx-auto d-block w-100" src="img/user_logo.webp?123">';
        }



        if ($usr['admin'] == "1" || $usr['name'] == "RutBat") {
        ?>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Техник</th>
                        <th scope="col">Количество монтажей</th>
                        <th scope="col">Сумма денег</th>
                    </tr>
                </thead>
                <tbody>
                    <?
                    $sql = "SELECT * FROM `user` WHERE `region` = '" . $usr['region'] . "' ORDER BY `id` desc";
                    $res_data = mysqli_query($connect, $sql);
                    while ($tech = mysqli_fetch_array($res_data)) {
                    ?>
                        <tr>
                            <td><?= $tech['fio'] ?></td>
                            <td><?
                                num_montaj("$tech[fio]", "$month", "$lastyear");
                                ?></td>



                            <td><?
                                summa_montaj("$tech[fio]", "$month", $lastyear);
                                ?> р.
                                <?php
                                // Получаем текущую дату
                                $currentDate = new DateTime();

                                // Получаем текущий месяц в числовом формате
                                $currentMonth = intval($currentDate->format('n')); // n - формат месяца без ведущего нуля

                                // Массив, связывающий текстовые названия месяцев с их числовыми представлениями
                                $monthNames = [
                                    'Январь' => 1,
                                    'Февраль' => 2,
                                    'Март' => 3,
                                    'Апрель' => 4,
                                    'Май' => 5,
                                    'Июнь' => 6,
                                    'Июль' => 7,
                                    'Август' => 8,
                                    'Сентябрь' => 9,
                                    'Октябрь' => 10,
                                    'Ноябрь' => 11,
                                    'Декабрь' => 12,
                                ];

                                // Получаем числовое представление выбранного месяца (предполагается, что $month это текстовое название месяца)
                                $selectedMonth = $monthNames[$month] ?? 0; // Если месяц не найден в массиве, по умолчанию 0

                                // Получаем последний день текущего месяца
                                $lastDayOfMonth = new DateTime('last day of this month');

                                // Вычисляем разницу между текущей датой и последним днем месяца в днях
                                $daysUntilEndOfMonth = $currentDate->diff($lastDayOfMonth)->days;

                                // Если $month не является текущим месяцем и осталось менее или равно 7 дням до конца месяца, либо $month отличается от текущего месяца, выполняем функцию prim_zp
                                if ($selectedMonth !== $currentMonth || $daysUntilEndOfMonth <= 7) {
                                    echo "<u><a style = 'color: #1ba11b;' href = 'zp.php?fio=$tech[fio]' >";
                                    prim_zp("$tech[fio]", "$month", $lastyear);
                                    echo '</a></u>';
                                }
                                ?>


                            </td>

















                        </tr>
                    <?
                    }
                    ?>
                </tbody>
            </table>
        <?
        } else {
        ?>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Техник</th>
                        <th scope="col">Количество монтажей</th>
                        <th scope="col">Сумма денег</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $usr['fio']; ?></td>
                        <td><?
                            num_montaj("$usr[fio]", "$month", "$lastyear");
                            ?></td>
                        <td><?
                            summa_montaj("$tech[fio]", "$month", $lastyear);
                            ?> р.
                            <?php
                            // Получаем текущую дату
                            $currentDate = new DateTime();

                            // Получаем текущий месяц в числовом формате
                            $currentMonth = intval($currentDate->format('n')); // n - формат месяца без ведущего нуля

                            // Массив, связывающий текстовые названия месяцев с их числовыми представлениями
                            $monthNames = [
                                'Январь' => 1,
                                'Февраль' => 2,
                                'Март' => 3,
                                'Апрель' => 4,
                                'Май' => 5,
                                'Июнь' => 6,
                                'Июль' => 7,
                                'Август' => 8,
                                'Сентябрь' => 9,
                                'Октябрь' => 10,
                                'Ноябрь' => 11,
                                'Декабрь' => 12,
                            ];

                            // Получаем числовое представление выбранного месяца (предполагается, что $month это текстовое название месяца)
                            $selectedMonth = $monthNames[$month] ?? 0; // Если месяц не найден в массиве, по умолчанию 0

                            // Получаем последний день текущего месяца
                            $lastDayOfMonth = new DateTime('last day of this month');

                            // Вычисляем разницу между текущей датой и последним днем месяца в днях
                            $daysUntilEndOfMonth = $currentDate->diff($lastDayOfMonth)->days;

                            // Если $month не является текущим месяцем и осталось менее или равно 7 дням до конца месяца, либо $month отличается от текущего месяца, выполняем функцию prim_zp
                            if ($selectedMonth !== $currentMonth || $daysUntilEndOfMonth <= 7) {
                                echo "<u><a style = 'color: #1ba11b;' href = 'zp.php?fio=$tech[fio]' >";
                                prim_zp("$usr[fio]", "$month", $lastyear);
                                echo '</a></u>';
                            }
                            ?>


                        </td>
                    </tr>
                </tbody>
            </table>
        <?
        }
        ?>
        <div class="alert alert-danger" role="alert">
            Суммы монтажей: <b><a href="user_arhiv.php">За прошлый год</a></b>
        </div>
        <div class="alert alert-danger" role="alert">
            Архив: <b><a href="arhiv.php">За прошлый год</a></b>
        </div>
        <div class="alert alert-success" role="alert">
            Регион: <b><?= $usr['region'] ?></b>
        </div>
        <div class="alert alert-info" role="alert">
            Ваш логин: <b><?= $usr['name'] ?></b>
        </div>
        <div class="alert alert-success" role="alert" style="padding: 0px 20px 0px;">
            Приложение для Android <a href="ardmoney.apk" class="alert-link"><img src="img/android.png" style="width: 32px;padding-bottom: 18px;">ArdMoney</a>.
        </div>
        <br>
        <b>
            <div class="d-grid gap-2">
                <a href="/exit.php" class="btn btn-outline-success btn-sm">Выход</a>
            </div>
        </b>
    </li>
</ul>
</div>
<?php include 'inc/foot.php';
?>