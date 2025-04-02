<?php
session_start();
include(__DIR__ . '/db.php');


date_default_timezone_set('Europe/Moscow');
global $connect;


// $used_router123 = $connect->query("SELECT * FROM `used_router` WHERE `technik` = '" . $usr['fio'] . "'");
// if ($used_router123->num_rows != 0) {
//     $used_router = $used_router123->fetch_array(MYSQLI_ASSOC);
// }
function del_mon($id)
{
    global $connect;
    $sql = "DELETE FROM array_montaj WHERE id = '$id'";
    if (mysqli_query($connect, $sql)) {
    } else {
        echo "Error deleting record: " . mysqli_error($connect);
    }
}


function edit_montaj_vidrabot($id_vid_rabot, $name, $new_name, $count)
{


    global $connect;
    $conn = $connect;

    $vid_montaj = $conn->query("SELECT * FROM `vid_rabot` WHERE `name` = '" . $name . "' LIMIT 1");

    if ($vid_montaj->num_rows != 0) {
        $vid_mon = $vid_montaj->fetch_assoc();
        if ($vid_mon['price_tech'] == 0) {
            $pric = 1;
        } else {
            $pric = $vid_mon['price_tech'];
        }
        $price = $pric * $count;
        $sql = "UPDATE array_montaj SET count = '$count', name = '$new_name', price = '$price' WHERE id = '$id_vid_rabot'";
        if ($conn->query($sql) === true) {
            // Здесь может быть дополнительный код, если нужно выполнить какие-то действия после успешного обновления
        } else {
            echo "Ошибка: " . $sql . "<br>" . $conn->error;
        }
    }else{
        $vid_montaj = $conn->query("SELECT * FROM `array_montaj` WHERE `name` = '" . $name . "' LIMIT 1");
        $vid_mon = $vid_montaj->fetch_assoc();
        if ($vid_mon['price_tech'] == 0) {
            $pric = 1;
        } else {
            $pric = $vid_mon['price_tech'];
        }
        $price = $pric * $count;
        $sql = "UPDATE array_montaj SET count = '$count', name = '$new_name', price = '$price' WHERE id = '$id_vid_rabot'";
        if ($conn->query($sql) === true) {
            // Здесь может быть дополнительный код, если нужно выполнить какие-то действия после успешного обновления
        } else {
            echo "Ошибка: " . $sql . "<br>" . $conn->error;
        }
    }
}
//, $status, $status_baza
function edit_montaj_summa($id_montaj)
{
    global $connect;
    $summa_query = $connect->prepare("SELECT SUM(price) AS count FROM array_montaj WHERE mon_id = ?");
    $summa_query->bind_param("i", $id_montaj);
    $summa_query->execute();
    $summa_result = $summa_query->get_result();
    $record = $summa_result->fetch_assoc();
    $summa = $record['count'];

    $montaj_query = $connect->prepare("SELECT * FROM montaj WHERE id = ?");
    $montaj_query->bind_param("i", $id_montaj);
    $montaj_query->execute();
    $montaj_result = $montaj_query->get_result();
    $mon = $montaj_result->fetch_assoc();

    $tech_codes = array("technik1", "technik2", "technik3", "technik4", "technik5", "technik6", "technik7", "technik8");
    $ebat_code = 0;
    foreach ($tech_codes as $tech_code) {
        if (!empty($mon[$tech_code])) {
            $ebat_code++;
        }
    }



    $kajdomu = round($summa / $ebat_code, 2);
    if ($summa == "") {
        $summa = 0;
    }
    $update_query = $connect->prepare("UPDATE montaj SET summa = ?,  kajdomu = ? WHERE id = ?");
    $update_query->bind_param("isi", $summa, $kajdomu, $id_montaj);
    if ($update_query->execute()) {
        // код для перенаправления на страницу red_index()
    } else {
        echo "Ошибка: " . $update_query->error;
    }
}

function summa_montaj($who, $mon, $years)
{
    global $connect, $usr;
    $months = array(
        'Январь' => 1, 'Февраль' => 2, 'Март' => 3, 'Апрель' => 4,
        'Май' => 5, 'Июнь' => 6, 'Июль' => 7, 'Август' => 8,
        'Сентябрь' => 9, 'Октябрь' => 10, 'Ноябрь' => 11, 'Декабрь' => 12
    );
    
    $zap_date = $months[$mon];
    
    // Используем один запрос с CASE для подсчета суммы по всем техникам
    $visible_condition = $usr['name'] == "RutBat" ? "" : "AND visible = 1";
    
    $sql = "SELECT SUM(kajdomu) as total_sum FROM (
        SELECT kajdomu FROM montaj 
        WHERE (
            technik1 = ? OR technik2 = ? OR technik3 = ? OR technik4 = ? OR
            technik5 = ? OR technik6 = ? OR technik7 = ? OR technik8 = ?
        )
        AND MONTH(date) = ? 
        AND YEAR(date) = ? 
        $visible_condition
    ) as subquery";
    
    $stmt = $connect->prepare($sql);
    $params = array_fill(0, 8, $who);
    $params[] = $zap_date;
    $params[] = $years;
    $stmt->bind_param(str_repeat('s', 8) . 'ii', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo $row['total_sum'] ?: 0;
}




function prim_zp($fio, $month_name, $year) {
    global $connect;
    
    $months = array(
        'Январь' => '01', 'Февраль' => '02', 'Март' => '03', 'Апрель' => '04',
        'Май' => '05', 'Июнь' => '06', 'Июль' => '07', 'Август' => '08',
        'Сентябрь' => '09', 'Октябрь' => '10', 'Ноябрь' => '11', 'Декабрь' => '12'
    );
    
    $month_num = $months[$month_name];
    $date_format = $year . '-' . $month_num;
    
    // Получаем все необходимые данные, включая регион и премию
    $sql = "SELECT 
        uf.dejurstva,
        u.id as user_id,
        uf.advance,
        uf.official_employment,
        u.region,
        COALESCE(c.monthly_bonus, 10.00) as monthly_bonus,
        COALESCE(
            (SELECT SUM(kajdomu) 
             FROM montaj 
             WHERE (technik1 = ? OR technik2 = ? OR technik3 = ? OR technik4 = ? OR
                    technik5 = ? OR technik6 = ? OR technik7 = ? OR technik8 = ?)
             AND MONTH(date) = ? 
             AND YEAR(date) = ?
            ), 0
        ) as montaj_sum
        FROM user u
        LEFT JOIN user_finance uf ON u.id = uf.user_id AND uf.month = ?
        LEFT JOIN config c ON u.region = c.region
        WHERE u.fio = ?";
        
    $stmt = $connect->prepare($sql);
    $month_num_int = intval($month_num);
    $year_int = intval($year);
    $params = array_fill(0, 8, $fio);  // 8 параметров для technik1-8
    $params[] = $month_num_int;         // MONTH(date)
    $params[] = $year_int;              // YEAR(date)
    $params[] = $date_format;           // uf.month
    $params[] = $fio;                   // u.fio
    
    $stmt->bind_param(str_repeat('s', 8) . 'iiss', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        echo "Пользователь не найден";
        return;
    }
    
    // Расчеты
    $dejurstva_sum = ($data['dejurstva'] ?? 0) * 1300;  // Если dejurstva NULL, используем 0
    $montaj_sum = $data['montaj_sum'];
    $advance = $data['advance'] ?: 0;
    $official_employment = $data['official_employment'];
    $monthly_bonus = $data['monthly_bonus'];  // Процент премии из config
    
    // Расчет бонуса на основе monthly_bonus из config (в процентах, делим на 100)
    $bonus = ($montaj_sum + $dejurstva_sum) * ($monthly_bonus / 100);
    
    // Расчет итоговых сумм
    if ($official_employment === 'Да' || $official_employment === '1') {
        $card_sum = 24000;
        $cash_sum = $montaj_sum + $dejurstva_sum + $bonus - $advance;
        $total_sum = $card_sum + $cash_sum + $advance;
    } else {
        $total_sum = $montaj_sum + 24000 + $dejurstva_sum + $bonus;
        $cash_sum = $total_sum - $advance;
    }
    
    // Вывод результатов с добавлением data-атрибутов для кликабельности
    echo "<div class='salary-block' data-user-id='{$data['user_id']}' data-month='{$month_num}' data-year='{$year}'>";
    echo "<div class='salary-total'>💰 " . number_format($total_sum, 0, '.', ' ') . " р.</div>";
    
    if ($advance > 0) {
        echo "<div class='salary-advance'>💳 -" . number_format($advance, 0, '.', ' ') . " р.</div>";
    }
    
    echo "<div class='salary-cash'>💸 " . number_format($cash_sum, 0, '.', ' ') . " р.</div>";
    echo "</div>";
}


function num_montaj($var1, $var2, $var3)
{
    global $usr, $connect;

    $months = array(
        'Январь' => 1, 'Февраль' => 2, 'Март' => 3, 'Апрель' => 4,
        'Май' => 5, 'Июнь' => 6, 'Июль' => 7, 'Август' => 8,
        'Сентябрь' => 9, 'Октябрь' => 10, 'Ноябрь' => 11, 'Декабрь' => 12
    );

    if (!isset($months[$var2])) {
        echo "Ошибка: неверное значение месяца: {$var2}";
        return;
    }

    $zap_date = $months[$var2];
    
    // Используем один запрос с OR условиями для всех техников
    $visible_condition = $usr['admin'] == "1" ? "" : "AND visible = 1";
    
    $sql = "SELECT COUNT(*) as total FROM montaj 
            WHERE (technik1 = ? OR technik2 = ? OR technik3 = ? OR technik4 = ? 
                  OR technik5 = ? OR technik6 = ? OR technik7 = ? OR technik8 = ?)
            AND MONTH(date) = ? AND YEAR(date) = ? $visible_condition";
            
    $stmt = $connect->prepare($sql);
    $params = array_fill(0, 8, $var1);
    $params[] = $zap_date;
    $params[] = $var3;
    $stmt->bind_param(str_repeat('s', 8) . 'ii', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo $row['total'];
}





function alrt($text, $why, $tim) //Уведомления
{
?>
    <script>
        setTimeout(function() {
            $('#hidenahoy').fadeOut();
        }, <?= $tim ?>000)
    </script>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <div id="hidenahoy" role="alert">
        <div class="alert alert-<?= $why ?>">
            <?= $text ?>
        </div>
    </div>
<?php
}
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
function h($string)
{
    return htmlentities($string, ENT_QUOTES, 'UTF-8');
}
function red_index($url) // редирект моментальный
{
    $url = htmlentities($url);
    echo '<meta http-equiv="refresh" content="0;URL=' . "$url" . '">';
}
function redir($url, $tim) // редирект с задержкой
{
    $url = htmlspecialchars($url);
    $tim = intval($tim);
    echo "<script>setTimeout(function(){ window.location.href = '$url'; }, $tim * 1000);</script>";
    exit;
}


function AutorizeProtect() {
    if (!checkAccess()) {
        header("Location: /auth.php");
        exit;
    }
}
function checkAccess() {
    global $connect;
    $name = $_COOKIE['user'] ?? "TestUser123";
    $pass = $_COOKIE['pass'] ?? "TestPass123";

    $stmt = $connect->prepare("SELECT * FROM `user` WHERE `name` = ? AND `pass` = ? AND `reger` = 1");
    $stmt->bind_param("ss", $name, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    $auth = ($result->num_rows > 0);
    $stmt->close();

    return $auth;
}


function access()
{
    global $usr;
    $current_date = date('y-m-d');
    $access = $usr['access_date'];
    $current_date = strtotime($current_date);
    $access = strtotime($access);
    //отключение подписки вообще
    $access = $current_date;
    if ($access < $current_date) {
    ?>
        <div class="card">
            <div class="card-header">
                Важное уведомление!
            </div>
            <div class="card-body">
                <h5 class="card-title">К большому сожалению у вас закончилась подписка. Это не бесплатное ПО. Дата подписки указана
                    в странице пользователя.</h5>
                <p class="card-text">Месячная подписка стоит <b>200р/мес.</b> Все деньги будут уходить в оплату хостинга и кофе.</p>
                <hr>
                <h5>Как оплатить?</h5>
                <br>
                <p class="card-text">Можно скинуть на любую из карт прямым переводом или по номеру телефона через СБП:</p>
                <img src="img/sbp.png" alt="" width="48px"> <b>+7(978)945-84-18</b><br>
                <img src="img/rnkb.png" alt="" width="48px"><b>РНКБ 2200 0202 2350 3329</b><br>
                <a href="https://www.tinkoff.ru/cf/AwmNLM8eFAA"><img src="img/tinkoff.png" alt="" width="48px"><b style="color: black;">Tinkoff(ссылка)</a> 2200 7004 9478 7426</b><br>
                <hr>
                <p class="card-text">После оплаты обязательно напишите любым удобным для вас способом администратору:</p>
                <p class="card-text">Пример текста:</p>
                <p class="fst-italic"><b>Оплатил подписку доступа в приложение ArdMoney, оплачивал через РНКБ в
                        <? echo date('y-m-d h:m'); ?>, моё Ф.И.О. <?= $usr['fio']; ?>
                    </b></p>
                <a href="https://wa.me/79789458418?text=Привет! Я оплатил подписку ArdMoney. Проверь пожалуйста. Меня зовут - <?= $usr['fio']; ?>"><img src="img/whatsapp.png" alt="" width="100px"></a><br><br>
                <a href="https://rutbat.t.me"><img src="img/telegram.png" alt="" width="100px"></a><br><br>
                <a href="httpd://rutbat.t.me"><img src="img/vk.png" alt="" width="100px"></a><br><br>
                <a href="tel:79789458418"><img src="img/sms.png" alt="" width="42px">+7(978)945-84-18</a><br>
                <br>
                После того как пройдет оплата администратор продлит доступ. Имейте терпение продление в ручном режиме.
                <br><br><br>
            </div>
        </div>
    <?
        include('foot.php');
        exit;
    }
}

function month_view($month)
{
    $months = array(
        '01' => 'Январь',
        '02' => 'Февраль',
        '03' => 'Март',
        '04' => 'Апрель',
        '05' => 'Май',
        '06' => 'Июнь',
        '07' => 'Июль',
        '08' => 'Август',
        '09' => 'Сентябрь',
        '10' => 'Октябрь',
        '11' => 'Ноябрь',
        '12' => 'Декабрь',
    );
    return $months[$month];
}


function date_view($date)
{
    // Массив месяцев
    $months = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

    // Если переменная $date пуста или равна null, возвращаем пустое значение
    if (empty($date)) {
        return '';
    }

    // Разделяем строку по дефису
    list($year, $number) = explode('-', $date);

    // Индекс месяца
    $index = (int)$number - 1;

    // Проверка на допустимость индекса
    if ($index >= 0 && $index < count($months)) {
        return $months[$index];
    }

    return ''; // Если месяц неверный, возвращаем пустую строку
}


function material_main($vid, $countid)
{
    global $connect;
    ?>
    <div class="row g-3">
        <div class="col-9" style="width: 74%;">
            <select class="selectpicker form-control dropup" style="background: white;" data-width="100%" data-container="body" title="Материалы" data-hide-disabled="true" data-width="auto" data-live-search="true" name='<?= $vid ?>' data-size="7">
                <?php
                $sql = "SELECT * FROM `material`  ORDER BY `razdel`";
                $results = mysqli_query($connect, $sql);
                $currentRazdel = '';

                while ($material_main = mysqli_fetch_array($results)) {
                    if ($material_main['razdel'] != $currentRazdel) {
                        // Начало новой группы (нового раздела)
                        if ($currentRazdel != '') {
                            echo '</optgroup>';
                        }
                        echo '<optgroup label="' . $material_main["razdel"] . '">';
                        $currentRazdel = $material_main['razdel'];
                    }
                ?>
                    <option 
                    style="color:<?= $material_main['color'] ?>;font-size: 10pt;" 
                    data-icon="<?= $material_main['icon'] ?>" 
                    value='<?= $material_main['name'] ?>'>
                        <?= $material_main["name"] ?></option>
                <?php
                }
                // Закрываем последнюю группу
                if ($currentRazdel != '') {
                    echo '</optgroup>';
                }
                ?>
            </select>
        </div>
        <div class="col-3 block">
            <input name="<?= $countid ?>" style="
                                    color: #999;
                                    border: 1px solid #bfbdbd;
                                    padding: 1px;
                                    margin: 5px 0px 1px;
                                    height:35px;
                                    background: white;
    " class="form-control form-control" type="text" placeholder="Количество" aria-label="Количество">

        </div>
    </div>
<?
}



























function vid_rabot_main($vid, $countid)
{
    global $connect;
?>
    <div class="row g-3">
        <div class="col-9" style="width: 74%;">
            <button type="button" class="form-control" style="background: white; text-align: left; color: #999;" id="button_<?=$vid?>" onclick="openModal('<?=$vid?>', 'Часто используемые')">
                Часто используемые
            </button>
            <input type="hidden" name="<?=$vid?>" id="selected_<?=$vid?>">
        </div>
        <div class="col-3 block">
            <input name="<?=$countid?>" style="
                color: #999;
                border: 1px solid #bfbdbd;
                padding: 1px;
                margin: 5px 0px 1px;
                height: 35px;
                background: white;
            " class="form-control form-control" type="text" placeholder="Количество" aria-label="Количество">
        </div>
    </div>

    <!-- Модальное окно -->
    <div id="modal_<?=$vid?>" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); max-height: 80vh; overflow-y: auto;">
            <h3 id="modal_title_<?=$vid?>"></h3>
            <input type="text" id="search_<?=$vid?>" onkeyup="liveSearch('<?=$vid?>')" placeholder="Поиск..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <div id="options_<?=$vid?>">
                <?php
                $sql = "SELECT * FROM `vid_rabot` WHERE `prioritet` = '1' ORDER BY `razdel`";
                $results = mysqli_query($connect, $sql);
                $currentRazdel = '';

                while ($vid_rabot = mysqli_fetch_array($results)) {
                    if ($vid_rabot['razdel'] != $currentRazdel) {
                        if ($currentRazdel != '') {
                            echo '</div>';
                        }
                        echo '<div class="option-group" style="margin-bottom: 10px;">';
                        echo '<h4 style="font-size: 14pt; margin: 10px 0 5px; text-align: left;">' . htmlspecialchars($vid_rabot["razdel"]) . '</h4>';
                        $currentRazdel = $vid_rabot['razdel'];
                    }
                ?>
                    <div class="option-item" style="padding: 5px; cursor: pointer; color: <?=$vid_rabot['color']?>; font-size: 10pt; text-align: left;" 
                         data-value="<?=htmlspecialchars($vid_rabot['name'])?>" 
                         onclick="selectOption('<?=$vid?>', '<?=$vid_rabot['name']?>')">
                        <?php if ($vid_rabot['icon']) { ?>
                            <span class="<?=$vid_rabot['icon']?>" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php } ?>
                        <?=htmlspecialchars($vid_rabot["name"])?>
                    </div>
                <?php
                }
                if ($currentRazdel != '') {
                    echo '</div>';
                }
                ?>
            </div>
            <button type="button" onclick="closeModal('<?=$vid?>')" style="width: 100%; padding: 10px; background: #ccc; border: none; border-radius: 4px; margin-top: 10px;">Закрыть</button>
            <button type="button" onclick="clearSelection('<?=$vid?>')" style="width: 100%; padding: 10px; background: #f8f9fa; border: none; border-radius: 4px; margin-top: 5px; color: #666;">Очистить</button>
        </div>
    </div>

    <script>
        function openModal(vid, title) {
            const modal = document.getElementById('modal_' + vid);
            modal.style.display = 'block';
            document.getElementById('modal_title_' + vid).innerText = title;
            document.getElementById('search_' + vid).focus();

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(vid);
                }
            });
        }

        function closeModal(vid) {
            document.getElementById('modal_' + vid).style.display = 'none';
        }

        function selectOption(vid, value) {
            document.getElementById('selected_' + vid).value = value;
            const button = document.getElementById('button_' + vid);
            button.innerText = value;
            button.style.color = '#28a745';
            closeModal(vid);
        }

        function clearSelection(vid) {
            document.getElementById('selected_' + vid).value = '';
            const button = document.getElementById('button_' + vid);
            button.innerText = 'Часто используемые';
            button.style.color = '#999';
            document.getElementsByName('<?=$countid?>')[0].value = '';
            closeModal(vid);
        }

        function liveSearch(vid) {
            const filter = document.getElementById('search_' + vid).value.toUpperCase();
            const optionsContainer = document.getElementById('options_' + vid);
            const groups = optionsContainer.getElementsByClassName('option-group');

            Array.from(groups).forEach(group => {
                const items = group.getElementsByClassName('option-item');
                let hasVisibleItem = false;

                Array.from(items).forEach(item => {
                    const text = item.getAttribute('data-value').toUpperCase();
                    const isVisible = text.includes(filter);
                    item.style.display = isVisible ? '' : 'none';
                    if (isVisible) hasVisibleItem = true;
                });

                group.style.display = hasVisibleItem ? '' : 'none';
            });
        }
    </script>
<?php
}

function vid_rabot_submain($vid, $countid)
{
    global $connect;
?>
    <div class="row g-3">
        <div class="col-9" style="width: 74%;">
            <button type="button" class="form-control" style="background: white; text-align: left; color: #999;" id="button_<?=$vid?>" onclick="openModal('<?=$vid?>', 'Редко используемые')">
                Редко используемые
            </button>
            <input type="hidden" name="<?=$vid?>" id="selected_<?=$vid?>">
        </div>
        <div class="col-3 block">
            <input name="<?=$countid?>" style="
                color: #999;
                border: 1px solid #bfbdbd;
                padding: 1px;
                margin: 5px 0px 1px;
                height: 35px;
                background: white;
            " class="form-control form-control" type="text" placeholder="Количество" aria-label="Количество">
        </div>
    </div>

    <!-- Модальное окно -->
    <div id="modal_<?=$vid?>" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); max-height: 80vh; overflow-y: auto;">
            <h3 id="modal_title_<?=$vid?>"></h3>
            <input type="text" id="search_<?=$vid?>" onkeyup="liveSearch('<?=$vid?>')" placeholder="Поиск..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <div id="options_<?=$vid?>">
                <?php
                $sql = "SELECT * FROM `vid_rabot` WHERE `prioritet` = '0' ORDER BY `razdel`, `type_kabel`";
                $results = mysqli_query($connect, $sql);
                $currentRazdel = '';

                while ($vid_rabot = mysqli_fetch_array($results)) {
                    if ($vid_rabot['razdel'] != $currentRazdel) {
                        if ($currentRazdel != '') {
                            echo '</div>';
                        }
                        echo '<div class="option-group" style="margin-bottom: 10px;">';
                        echo '<h4 style="font-size: 14pt; margin: 10px 0 5px; text-align: left;">' . htmlspecialchars($vid_rabot["razdel"]) . '</h4>';
                        $currentRazdel = $vid_rabot['razdel'];
                    }
                ?>
                    <div class="option-item" style="padding: 5px; cursor: pointer; color: <?=$vid_rabot['color']?>; font-size: 10pt; text-align: left;" 
                         data-value="<?=htmlspecialchars($vid_rabot['name'])?>" 
                         onclick="selectOption('<?=$vid?>', '<?=$vid_rabot['name']?>')">
                        <?php if ($vid_rabot['icon']) { ?>
                            <span class="<?=$vid_rabot['icon']?>" style="vertical-align: middle; margin-right: 5px;"></span>
                        <?php } ?>
                        <?=htmlspecialchars($vid_rabot["name"])?>
                    </div>
                <?php
                }
                if ($currentRazdel != '') {
                    echo '</div>';
                }
                ?>
            </div>
            <button type="button" onclick="closeModal('<?=$vid?>')" style="width: 100%; padding: 10px; background: #ccc; border: none; border-radius: 4px; margin-top: 10px;">Закрыть</button>
            <button type="button" onclick="clearSelection('<?=$vid?>')" style="width: 100%; padding: 10px; background: #f8f9fa; border: none; border-radius: 4px; margin-top: 5px; color: #666;">Очистить</button>
        </div>
    </div>

    <script>
        function openModal(vid, title) {
            const modal = document.getElementById('modal_' + vid);
            modal.style.display = 'block';
            document.getElementById('modal_title_' + vid).innerText = title;
            document.getElementById('search_' + vid).focus();

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(vid);
                }
            });
        }

        function closeModal(vid) {
            document.getElementById('modal_' + vid).style.display = 'none';
        }

        function selectOption(vid, value) {
            document.getElementById('selected_' + vid).value = value;
            const button = document.getElementById('button_' + vid);
            button.innerText = value;
            button.style.color = '#28a745';
            closeModal(vid);
        }

        function clearSelection(vid) {
            document.getElementById('selected_' + vid).value = '';
            const button = document.getElementById('button_' + vid);
            button.innerText = 'Редко используемые';
            button.style.color = '#999';
            document.getElementsByName('<?=$countid?>')[0].value = '';
            closeModal(vid);
        }

        function liveSearch(vid) {
            const filter = document.getElementById('search_' + vid).value.toUpperCase();
            const optionsContainer = document.getElementById('options_' + vid);
            const groups = optionsContainer.getElementsByClassName('option-group');

            Array.from(groups).forEach(group => {
                const items = group.getElementsByClassName('option-item');
                let hasVisibleItem = false;

                Array.from(items).forEach(item => {
                    const text = item.getAttribute('data-value').toUpperCase();
                    const isVisible = text.includes(filter);
                    item.style.display = isVisible ? '' : 'none';
                    if (isVisible) hasVisibleItem = true;
                });

                group.style.display = hasVisibleItem ? '' : 'none';
            });
        }
    </script>
<?php
}





































function nav_index($month)
{
    global $usr;
?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="padding: 0;">
        <div class="container-fluid" style="background: #00000070;">
            <a class="navbar-brand" href="#"></a>
            <div class="navbar-collapse" id="navbarNavDarkDropdown">
                <ul class="navbar-nav" style="flex-direction: row;
    padding-left: 0;
    margin-bottom: 0;
    list-style: none;
    flex-wrap: wrap;
    align-content: center;
    justify-content: space-around;
    align-items: center;">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDarkDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= $month ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDarkDropdownMenuLink" style="position: absolute;margin: -4px -5px 0px;">
                            <li><a class="dropdown-item" href="?date=2023-01">Январь</a></li>
                            <li><a class="dropdown-item" href="?date=2023-02">Февраль</a></li>
                            <li><a class="dropdown-item" href="?date=2023-03">Март</a></li>
                            <li><a class="dropdown-item" href="?date=2023-04">Апрель</a></li>
                            <li><a class="dropdown-item" href="?date=2023-05">Май</a></li>
                            <li><a class="dropdown-item" href="?date=2023-06">Июнь</a></li>
                            <li><a class="dropdown-item" href="?date=2023-07">Июль</a></li>
                            <li><a class="dropdown-item" href="?date=2023-08">Август</a></li>
                            <li><a class="dropdown-item" href="?date=2023-09">Сентябрь</a></li>
                            <li><a class="dropdown-item" href="?date=2023-10">Октябрь</a></li>
                            <li><a class="dropdown-item" href="?date=2023-11">Ноябрь</a></li>
                            <li><a class="dropdown-item" href="?date=2023-12">Декабрь</a></li>
                        </ul>
                    </li>
                    <?
                    if ($usr['admin'] == 1) {
                        $status = $usr['admin_view'] == "1" ? "checked" : "";
                        echo '
                                <div class="form-check form-switch">
                                    <input name="admin_viewer" class="form-check-input new_form-check-input" type="checkbox" id="admin_viewer" ' . "$status" . '>
                                    <label class="form-check-label" style = "color: #9ca09a;" for="admin_viewer">Мои</label>
                                </div>';
                    ?>
                        <script>
                            $(document).ready(function() {
                                $('#admin_viewer').change(function() {
                                    var checked = $(this).is(':checked');
                                    var userId = <?= $usr['id'] ?>;
                                    $.ajax({
                                        url: 'update_user.php',
                                        type: 'POST',
                                        data: {
                                            userId: userId,
                                            adminView: checked ? 1 : 0
                                        },
                                        success: function(response) {
                                            console.log(response);
                                            location.reload();
                                        },
                                        error: function(xhr, status, error) {
                                            console.log(xhr.responseText);
                                        }
                                    });
                                });
                            });
                        </script>
                    <?
                    }
                    if ($usr['name'] == 'test' or $usr['name'] == 'test2') {
                        echo '<a href = "demo.php" style = "color: chartreuse;">Инструкция демо аккаунта</a>';
                    }
                    ?>
                    <?php
                    if (!empty(htmlentities($_COOKIE['user']))) {
                    ?>
                        <ul style="float: right;">
                            <li>
                                <a href="user.php">
                                    <i style="font-size: x-large;color: lawngreen;" class="bi bi-house-gear"></i> </a>
                            </li>
                        </ul>
                    <?php
                    } ?>
                </ul>
            </div>
        </div>
    </nav>
<?

}


function demo()
{
    global $connect;
    global $usr;
    if ($usr['demo'] == 1) {
        echo "<div class='alert alert-danger' role='alert'>
Тестовая подписка активна до <b>$usr[access_date]</b> <br>
Подробнее <a href = '/novoreg.php'>ТУТ</a>
</a></b>
</div>";
        $sql = "UPDATE user SET
demo = '0'
WHERE name = '$usr[name]'";
        if ($connect->query($sql) === true) {
        }
    }
}
function modal_delete() {
    // Выводим стили для центрирования модального окна
    echo '<style>
        /* Центрирование модального окна по вертикали и горизонтали */
        #confirmDeleteModal .modal-dialog {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 1rem);
        }
        /* Дополнительное оформление содержимого модального окна */
        #confirmDeleteModal .modal-content {
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
    </style>';
    
    echo '<div class="modal fade" tabindex="-1" role="dialog" id="confirmDeleteModal">';
    echo '  <div class="modal-dialog" role="document" style = "            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 1rem);" >';
    echo '    <div class="modal-content">';
    echo '      <div class="modal-header">';
    echo '        <h5 class="modal-title">Удаление монтажа</h5>';
    echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    echo '      </div>';
    echo '      <div class="modal-body">';
    echo '        Вы действительно хотите удалить этот монтаж?';
    echo '      </div>';
    echo '      <div class="modal-footer">';
    echo '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>';
    echo '        <a href="' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET) . '&confirmDelete=true" class="btn btn-danger">Удалить</a>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
    
    // Скрипт для автоматического открытия модального окна
    echo '<script type="text/javascript">
        $(document).ready(function() {
            $("#confirmDeleteModal").modal("show");
        });
    </script>';
}

function gm()
{
    global $usr;

    if ($usr['hidden_mon'] == 0) {
        $check = "checked";
    } else {
        $check = "";
    }
?>
    <div class="m-2 form-check form-switch">
        <input class="form-check-input" type="checkbox" <?= $check ?> role="switch" id="flexSwitchCheckDefault">
        <label class="form-check-label" for="flexSwitchCheckDefault">Включить скрытые монтажи</label>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Обработчик изменения состояния чекбокса
            $("#flexSwitchCheckDefault").change(function() {
                if (this.checked) {
                    // Если чекбокс включен, отправляем запрос на сервер
                    $.ajax({
                        type: "POST",
                        url: "update_admin_hidden.php", // Путь к серверному скрипту
                        data: {
                            action: "enable",
                            username: "<?php echo $usr['name']; ?>"
                        },
                        success: function(response) {
                            // Обработка ответа от сервера, если необходимо
                            console.log(response);
                        }
                    });
                } else {
                    // Если чекбокс выключен, отправляем запрос на сервер для отключения
                    $.ajax({
                        type: "POST",
                        url: "update_admin_hidden.php", // Путь к серверному скрипту
                        data: {
                            action: "disable",
                            username: "<?php echo $usr['name']; ?>"
                        },
                        success: function(response) {
                            // Обработка ответа от сервера, если необходимо
                            console.log(response);
                        }
                    });
                }
            });
        });
    </script>



<?
}




function animate()
{
?>
    <script>
        // Проверка, что GSAP подключен
        if (typeof gsap !== 'undefined') {
            console.log('GSAP готов к использованию');
        } else {
            console.error('GSAP не подключен');
        }
    </script>
<?php
}



function delete_mon()
{
    global $connect;
    $encodedStr = $_GET["delete"];
    $id = base64_decode($encodedStr);

    // Формируем SQL-запрос на удаление записи с указанным id
    $sql = "DELETE FROM montaj WHERE id = " . $id;
    
    // Если удаление подтверждено, выполняем SQL-запрос
    if (isset($_GET['confirmDelete'])) {
        $result = mysqli_query($connect, $sql);
        if ($result) {
            red_index('index.php');
            exit;
        } else {
            echo "Ошибка при удалении записи: " . mysqli_error($connect);
        }
    }
    
    // Выводим стили для центрирования модального окна
    echo '<style>
        #confirmDeleteModal .modal-dialog {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 1rem);
        }
        #confirmDeleteModal .modal-content {
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
    </style>';
    
    // Формируем разметку модального окна
    echo '<div class="modal fade" tabindex="-1" role="dialog" id="confirmDeleteModal">';
    echo '  <div class="modal-dialog modal-dialog-centered" role="document">';
    echo '    <div class="modal-content">';
    echo '      <div class="modal-header">';
    echo '        <h5 class="modal-title">Удаление монтажа</h5>';
    echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    echo '      </div>';
    echo '      <div class="modal-body">';
    echo '        Вы действительно хотите удалить этот монтаж?';
    echo '      </div>';
    echo '      <div class="modal-footer">';
    echo '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>';
    echo '        <a href="' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET) . '&confirmDelete=true" class="btn btn-danger">Удалить</a>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
    
    // Скрипт для автоматического открытия модального окна
    echo '<script type="text/javascript">
        $(document).ready(function() {
            $("#confirmDeleteModal").modal("show");
        });
    </script>';
}

function li_month()
{
?>
    <? $dateGod = date("Y"); ?>
    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDarkDropdownMenuLink" style="position: absolute;margin: -4px -5px 0px;">
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-01">Январь</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-02">Февраль</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-03">Март</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-04">Апрель</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-05">Май</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-06">Июнь</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-07">Июль</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-08">Август</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-09">Сентябрь</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-10">Октябрь</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-11">Ноябрь</a></li>
        <li><a class="dropdown-item" href="?date=<?= $dateGod ?>-12">Декабрь</a></li>
        <?
        // Получаем текущий год
        $previousYear = $dateGod - 1;
        $currentMonth = date('n');
        if ($currentMonth == 1) {
        ?>
            <li><a class="dropdown-item" style="color:red;" href="?date=<?= $previousYear ?>-12">Декабрь <?= $previousYear ?></a></li>

        <?
        }
        ?>

    </ul>
<?
}
function admin_checkbox($id)
{
    global $usr;
    $status = $usr['admin_view'] == "1" ? "active" : "inactive";
    $textColor = $usr['admin_view'] == "1" ? "text-success glow" : "text-danger";
    $fontWeight = $usr['admin_view'] == "1" ? "fw-bold" : "";
?>
    <div class="admin-viewer d-inline-block p-2 rounded" id="admin_viewer" style="cursor: pointer;">
        <span class="<?= $textColor ?> <?= $fontWeight ?>">Мои</span>
    </div>

    <script>
        $(document).ready(function() {
            $('#admin_viewer').click(function() {
                var userId = <?= $id ?>;

                // Анимация при клике
                gsap.to('#admin_viewer', {
                    scale: 0.9,
                    duration: 0.1,
                    ease: "power1.in",
                    yoyo: true,
                    repeat: 1,
                    onComplete: function() {
                        $.ajax({
                            url: '../update_user.php',
                            type: 'POST',
                            data: {
                                userId: userId,
                                adminView: $('#admin_viewer span').hasClass('text-success') ? 0 : 1
                            },
                            success: function(response) {
                                if (typeof response === 'object') {
                                    if (response.success) {
                                        var newStatus = response.admin_view == 1 ? 'active' : 'inactive';
                                        var newTextClass = newStatus === 'active' ? 'text-success fw-bold glow' : 'text-danger';

                                        $('#admin_viewer span')
                                            .removeClass('text-success text-danger fw-bold glow')
                                            .addClass(newTextClass);

                                        // Анимация изменения состояния
                                        gsap.fromTo('#admin_viewer span', 
                                            { opacity: 0, scale: 0.8 },
                                            { opacity: 1, scale: 1, duration: 0.3, ease: "back.out(1.7)" }
                                        );

                                        loadArchiveData();
                                    } else {
                                        console.error('Ошибка обновления: ', response.error || 'Неизвестная ошибка.');
                                    }
                                } else {
                                    console.error('Ответ не является JSON:', response);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Ошибка запроса: ', xhr.responseText);
                            }
                        });
                    }
                });
            });

            // Стиль анимации для свечения
            const style = document.createElement('style');
            style.textContent = `
                .glow {
                    text-shadow: 0 0 5px green, 0 0 10px green, 0 0 15px green, 0 0 20px green;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
<?php
}

function demo_inst()
{
    global $usr;
    if ($usr['name'] == 'test' or $usr['name'] == 'test2') {
        echo '<a href = "demo.php" style = "color: chartreuse;">Инструкция демо аккаунта</a>';
    }
}
function LiveSearch($inputId, $searchViewsClass, $parentElementId)
{
    echo '<script>';
    echo 'function liveSearch() {';
    echo 'var filter = document.getElementById(\'' . $inputId . '\').value.toUpperCase();';
    echo 'var searchViews = document.getElementsByClassName(\'' . $searchViewsClass . '\');';
    echo 'Array.from(searchViews).forEach(view => {';
    echo 'var value = view.getAttribute(\'data-value\').toUpperCase();';
    echo 'var parentDiv = view.closest(\'' . $parentElementId . '\');';
    echo 'if (parentDiv) {';
    echo 'parentDiv.style.display = value.includes(filter) ? \'\' : \'none\';';
    echo '}';
    echo '});';
    echo '}';
    echo '</script>';
}
function date_rut($input, $format)
{
    // Пробуем создать объект DateTime с полным форматом (с временем)
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $input);
    
    if (!$date) {
        // Если не получилось, пробуем формат без времени
        $date = DateTime::createFromFormat('Y-m-d', $input);
        
        if (!$date) {
            // Если все еще не получилось, пробуем только год и месяц
            $date = DateTime::createFromFormat('Y-m', $input);
            
            if (!$date) {
                // И последний вариант — только год
                $date = DateTime::createFromFormat('Y', $input);
            }
        }
    }

    // Если ни один формат не подошел
    if (!$date) {
        return "Неверный формат даты";
    }

    return $date->format($format);
}





function moneyrain(){

    ?>
    <style>
    .money-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1000;
    }
    .money {
        position: absolute;
        width: 50px;
        animation: fall 5s linear, fadeOut 5s linear;
        visibility: hidden; /* Скрыть до начала анимации */
    }
    @keyframes fall {
        0% {
            transform: translateY(calc(-5rem)) rotate(0deg);
            visibility: visible; /* Появляется сразу перед падением */
        }
        100% {
            transform: translateY(110vh) rotate(var(--rotate-end));
        }
    }
    @keyframes fadeOut {
        0%, 10% {
            opacity: 0;
        }
        100% {
            opacity: 0;
        }
    }
</style>

<script>
    const moneyImages = ['money1.webp', 'money2.webp', 'money3.webp']; // Замените на свои webp-картинки

    function moneyrain(count = 100) {
        let container = document.querySelector('.money-container');

        // Создаем контейнер, если его еще нет
        if (!container) {
            container = document.createElement('div');
            container.className = 'money-container';
            document.body.appendChild(container);
        }

        for (let i = 0; i < count; i++) {
            const img = document.createElement('img');
            img.src = moneyImages[Math.floor(Math.random() * moneyImages.length)];
            img.className = 'money';

            // Случайное горизонтальное положение, задержка, направление вращения
            img.style.left = Math.random() * 100 + 'vw';
            img.style.animationDelay = Math.random() * 5 + 's'; // Случайная задержка
            img.style.animationDuration = '4s';
            img.style.setProperty('--rotate-end', Math.random() > 0.5 ? '360deg' : '-360deg');

            // Удаление элемента после завершения анимации
            img.addEventListener('animationend', () => {
                img.remove();
            });

            container.appendChild(img);
        }
    }

    window.addEventListener('load', () => moneyrain(50));

</script>

    <?
}


function prim_zp_year($var1, $var3)
{
    global $connect;
    $months = array(
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
        'Декабрь' => 12
    );
    $total_summa = 0;

    // Получение значения из базы данных
    $sql1 = "SELECT dejurstva FROM user WHERE fio = ?";
    $stmt1 = $connect->prepare($sql1);
    $stmt1->bind_param("s", $var1);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $record1 = $result1->fetch_array();
    $dejurstva = $record1['dejurstva'];

    foreach ($months as $month_name => $month_number) {
        $monthly_summa = 0;

        for ($i = 1; $i <= 8; $i++) {
            $technik = 'technik' . $i;
            $sql = "SELECT SUM(kajdomu) AS count FROM montaj WHERE $technik = ? AND MONTH(`date`) = ? AND YEAR(`date`) = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sii", $var1, $month_number, $var3);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_array();
            $monthly_summa += $record['count'];
        }

        $monthly_dejurstva = $dejurstva * 1300;
        $monthly_prim_zp = $monthly_summa + 24000 + $monthly_dejurstva + ($monthly_summa + 24000 + $monthly_dejurstva) * 0.1;

        $total_summa += $monthly_prim_zp;
    }

    echo number_format($total_summa, 0, '', ' ') . " рублей!!!";
}

function statistic($usr_name,$text, $year,$res){

    global $connect;
$query = "
    SELECT COUNT(*) AS total 
    FROM `montaj` 
    WHERE `text` LIKE '%$text%' 
      AND YEAR(`date`) = $year
      AND (`technik1` = '$usr_name' 
           OR `technik2` = '$usr_name' 
           OR `technik3` = '$usr_name' 
           OR `technik4` = '$usr_name' 
           OR `technik5` = '$usr_name' 
           OR `technik6` = '$usr_name' 
           OR `technik7` = '$usr_name' 
           OR `technik8` = '$usr_name')
";

// Выполнение запроса
$result = $connect->query($query);

// Проверка результата
if ($result) {
    $row = $result->fetch_assoc();
    echo "
    Было сделанно 
    <span style = 'font-size: medium;color: black;font-weight: 600;' >
    $row[total]
    </span>  
    $res";
    echo'<br>';
} else {
    echo "Ошибка выполнения запроса: " . $connect->error;
}
}



// Функция вывода выпадающего списка
function out_sel($val1, $val2, $val3) {
    global $connect;
    $val1 = htmlentities($val1);
    $val2 = htmlentities($val2);
    $color = $val3 == "Регион" ? "text-danger" : "text-muted";
    
    $results = $connect->query("SELECT * FROM " . TABLE_PREFIX . "adress WHERE adress LIKE '$val2'");
    while ($row = $results->fetch_object()) {
        echo "<small class='form-text $color'>$val3</small><select name='$val1' class='form-select mr-sm-2'>";
        $krish = $connect->query("SELECT * FROM " . TABLE_PREFIX . "$val1");
        while ($krisha = $krish->fetch_object()) {
            $sel_krisha = ($row->$val1 == $krisha->name) ? "selected" : "";
            echo "<option $sel_krisha value='$krisha->name'>$krisha->name</option>";
        }
        echo '</select>';
    }
}

// Функция вывода полей ввода
function out_in($val1, $val2, $val3) {
    global $connect;
    $val1 = htmlentities($val1);
    $val2 = htmlentities($val2);
    $val3 = htmlentities($val3);
    
    $results = $connect->query("SELECT * FROM " . TABLE_PREFIX . "adress WHERE adress LIKE '$val2'");
    while ($row = $results->fetch_object()) {
        if($val1 == 'phone') {
            ?>
            <small class="form-text text-muted"><?= $val3 ?></small>
            <input name="<?= $val1 ?>" type="text" class="form-control bfh-phone" data-format="+7(ddd)ddd-dd-dd" value="<?= $row->$val1 ?>"
            <?php
        } else {
            ?>
            <small class="form-text text-muted"><?= $val3 ?></small>
            <input name="<?= $val1 ?>" type="text" class="form-control" value="<?= $row->$val1 ?>"
            <?php
        }
        $placeholder = empty($row->$val1) ? $val3 : $row->$val1;
        ?>
        placeholder="<?= $placeholder ?>"
        <?= ($val1 == "adress") ? 'style="display: -webkit-inline-box;width: 91%;">' : '>' ?>
        <?php
    }
}

function show_upload_form($encodedStr, $mon) {
    echo '<div class="d-flex justify-content-center">
        <div id="spiner" class="spinner-border" role="status" style="display:none;"></div>
    </div>
    <div class="press" style="display: none">
        <form name="upload" action="download_img.php" method="POST" ENCTYPE="multipart/form-data">
            <div class="input-group mb-3" style="margin-bottom: 0rem!important;">
                <input type="hidden" name="id" value="' . $encodedStr . '">
                <input type="hidden" name="adress" value="' . $mon['adress'] . '">
                <input type="file" name="userfile" class="form-control" id="inputGroupFile02">
                <input type="submit" name="upload" class="input-group-text" value="Загрузить" onclick="(document.getElementById(\'spiner\').style.display=\'block\')">
            </div>
        </form>
    </div>
    <script>
        $("#ava").click(function() {
            $(".press").show();
        });
    </script>';
}

