<?php
session_start();
$url = $_SERVER['REQUEST_URI'];
$url = explode('?', $url);
$url = $url[0];

include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php");
echo '<body style="background: #ffffff url(img/background.webp) repeat;height: auto;">';
echo '<div class="container-sm">';
?>
<link rel="stylesheet" href="css/result.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>

<?php
AutorizeProtect();
access();
global $connect;
global $usr;
global $used_router;
$encodedStr = $_GET["vid_id"];
$id = base64_decode($encodedStr);

$stmt = $connect->prepare("SELECT * FROM `montaj` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$montaj = $stmt->get_result();
if ($montaj->num_rows != 0) {
    $mon = $montaj->fetch_array(MYSQLI_ASSOC);
}
$stmt->close();
?>

<!-- Стили для спиннера и уведомления -->
<style>
    .spinner {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #28a745;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 2000;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .notification {
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background-color: #28a745;
        color: white;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 2000;
    }

    .edit-form {
        background: #fff;
        margin-top: 5px;
    }

    .edit-form input[type="text"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .edit-form .btn-group {
        display: flex;
        gap: 10px;
    }

    .edit-form button {
        width: 100%;
        padding: 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .edit-form .btn-save {
        background: #28a745;
        color: white;
    }

    .edit-form button:hover {
        opacity: 0.9;
    }

    .upload-form {
        background: #fff;
        margin-top: 5px;
    }

    .upload-form .input-group {
        display: flex;
        gap: 10px;
    }

    .upload-form input[type="file"] {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .upload-form button {
        padding: 8px 15px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-form button:hover {
        opacity: 0.9;
    }

    .edit-icon {
        cursor: pointer;
        color: #6c757d;
        transition: color 0.3s ease;
    }

    .edit-icon:hover {
        color: #007bff;
    }

    .date-block {
        background: #ffffffab;
        display: block;
        border-radius: 1rem 0rem 0rem 1rem;
        width: fit-content;
        padding: 0.25rem 0.5rem;
        text-align: left;
        float: right;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .date-block:hover {
        background: #ffffff;
    }

    .spinner-border {
        display: none;
        width: 1rem;
        height: 1rem;
        border-width: 0.15em;
        margin-right: 0.5rem;
    }
</style>

<div data-barba="wrapper">
    <div data-barba="container" data-barba-namespace="result">
        <main role="main">
            <div class="jumbotron" style="padding: 0">
                <div style="display: grid;place-items: center;">
                    <div class="auth-container">
                        <a href="/">
                            <?php include("inc/navbar_result.php"); ?>
                        </a>
                        <div class="col-md-12 col-sm-12 mx-auto">
                            <div class="section over-hide">
                                <div class="row justify-content-center">
                                    <div class="col-12 text-center align-self-center">
                                        <div class="d-grid gap-2">
                                            <b>
                                                <button style="width:100%; background:#0088cc; color: white;" onclick="shareScreenshot()" class="btn btn-sm btn-success">
                                                    <i class="fa-brands fa-telegram"></i> Отправить скриншот
                                                </button>
                                            </b>
                                        </div>

                                        <!-- Подключение Font Awesome -->
                                        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
                                        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
                                        <script>
                                            function shareScreenshot() {
                                                if (typeof AndroidInterface !== 'undefined') {
                                                    AndroidInterface.shareScreenshot();
                                                } else {
                                                    alert('Эта функция доступна только в приложении ArdMoney.');
                                                }
                                            }
                                        </script>
                                        <?php result_ava($encodedStr, $mon); ?>
                                        <head>
                                            <title><?= htmlspecialchars($mon['adress'] ?? '', ENT_QUOTES, 'UTF-8') ?></title>
                                        </head>
                                        <link rel="stylesheet" href="css/fix.css">
                                        <div class="section text-center py-md-0">
                                            <div class="content-container" style="background: #2d885750; padding: 0.25rem; font-family: auto; width: 100%;">
                                                <div class="address-block" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 0.5rem; width: 100%;">
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <rut id="mon_adress" style="font-size: large; color: #000;"><?= htmlspecialchars($mon['adress'] ?? '', ENT_QUOTES, 'UTF-8') ?></rut>
                                                        <a id="image" class="edit-icon"><i class="fas fa-edit"></i></a>
                                                    </div>
                                                </div>
                                                <div class="vin-image" style="text-align: center; margin-bottom: 0.5rem;">
                                                    <img src="img/vin.png" style="width: 50%;">
                                                </div>
                                                <div class="description-block" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;">
                                                    <div style="display: flex; align-items: center; gap: 10px;">
                                                        <rut id="mon_adress_text" style="font-size: large; color: #000;"><?= htmlspecialchars($mon['text'] ?? '', ENT_QUOTES, 'UTF-8') ?></rut>
                                                        <a id="image_text" class="edit-icon"><i class="fas fa-edit"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <form id="update_form" class="edit-form" style="display:none;">
                                                <div class="form-group">
                                                    <label for="new_adress">Новый адрес:</label>
                                                    <input type="text" id="new_adress" name="new_adress" value="<?= htmlspecialchars($mon['adress'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div class="btn-group">
                                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Сохранить</button>
                                                </div>
                                            </form>
                                            <form id="update_form_text" class="edit-form" style="display:none;">
                                                <div class="form-group">
                                                    <label for="new_adress_text">Новое описание:</label>
                                                    <input type="text" id="new_adress_text" name="new_adress_text" value="<?= htmlspecialchars($mon['text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div class="btn-group">
                                                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Сохранить</button>
                                                </div>
                                            </form>
                                            <script>
                                                var image = document.getElementById("image");
                                                var block = document.getElementById("update_form");
                                                image.addEventListener("click", function() {
                                                    if (block.style.display === "none") {
                                                        block.style.display = "block";
                                                    } else {
                                                        block.style.display = "none";
                                                    }
                                                });
                                                $(function() {
                                                    $('#update_form').submit(function(event) {
                                                        event.preventDefault();
                                                        var new_adress = $('#new_adress').val();
                                                        $.ajax({
                                                            url: 'adress_update.php',
                                                            type: 'POST',
                                                            data: {
                                                                id: <?=$mon['id']; ?>,
                                                                adress: new_adress
                                                            },
                                                            success: function(data) {
                                                                $('#update_form').hide();
                                                                $('#mon_adress').text(new_adress);
                                                            }
                                                        });
                                                    });
                                                });
                                                var image_text = document.getElementById("image_text");
                                                var block_text = document.getElementById("update_form_text");
                                                image_text.addEventListener("click", function() {
                                                    if (block_text.style.display === "none") {
                                                        block_text.style.display = "block";
                                                    } else {
                                                        block_text.style.display = "none";
                                                    }
                                                });
                                                $(function() {
                                                    $('#update_form_text').submit(function(event) {
                                                        event.preventDefault();
                                                        var new_text = $('#new_adress_text').val();
                                                        $.ajax({
                                                            url: 'text_update.php',
                                                            type: 'POST',
                                                            data: {
                                                                id: <?=$mon['id']; ?>,
                                                                text: new_text
                                                            },
                                                            success: function(data) {
                                                                $('#update_form_text').hide();
                                                                $('#mon_adress_text').text(new_text);
                                                            }
                                                        });
                                                    });
                                                });
                                            </script>
                                            <?php
                                            $tech1 = $mon['technik1'] ?? '';
                                            $tech2 = $mon['technik2'] ?? '';
                                            $tech3 = $mon['technik3'] ?? '';
                                            $tech4 = $mon['technik4'] ?? '';
                                            $tech5 = $mon['technik5'] ?? '';
                                            $tech6 = $mon['technik6'] ?? '';
                                            $tech7 = $mon['technik7'] ?? '';
                                            $tech8 = $mon['technik8'] ?? '';

                                            $ebat_code = 0;
                                            for ($i = 1; $i <= 8; $i++) {
                                                $tech = "tech$i";
                                                if (!empty(${$tech})) {
                                                    $ebat_code = $i;
                                                }
                                            }
                                            ?>
                                            <ol class="list-group list-group-numbered" style="font-size: small;" id="montaj-list">
                                                <?php
                                                $stmt = $connect->prepare("SELECT * FROM `array_montaj` WHERE mon_id = ?");
                                                $stmt->bind_param("i", $id);
                                                $stmt->execute();
                                                $results = $stmt->get_result();

                                                while ($vid_rabot = $results->fetch_array(MYSQLI_ASSOC)) {
                                                    $bg_acent = ($vid_rabot['price'] ?? 0) == 0 ? "background: #c8e4f58c;" : "";

                                                    if ($vid_rabot['name'] == "Подключение по GPON" || $vid_rabot['name'] == "Сложное подключение Gpon") {
                                                        $product_id = $mon['id'];
                                                ?>
                                                        <script>
                                                            document.addEventListener("DOMContentLoaded", function() {
                                                                function setCookie(name, value, days) {
                                                                    var expires = "";
                                                                    if (days) {
                                                                        var date = new Date();
                                                                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                                                        expires = "; expires=" + date.toUTCString();
                                                                    }
                                                                    document.cookie = name + "=" + (value || "") + expires + "; path=/";
                                                                }

                                                                function getCookie(name) {
                                                                    var nameEQ = name + "=";
                                                                    var ca = document.cookie.split(";");
                                                                    for (var i = 0; i < ca.length; i++) {
                                                                        var c = ca[i];
                                                                        while (c.charAt(0) == " ") c = c.substring(1, c.length);
                                                                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                                                                    }
                                                                    return null;
                                                                }

                                                                if (!getCookie("alert_closed_product_<?= $product_id ?>")) {
                                                                    document.getElementById("close-alert-<?= $product_id ?>").addEventListener("click", function() {
                                                                        setCookie("alert_closed_product_<?= $product_id ?>", "true", 7);
                                                                    });
                                                                }
                                                            });
                                                        </script>
                                                        <style>
                                                            .alert { padding: 0.2rem 0; }
                                                            .alert-dismissible .btn-close { padding: 0.5rem 1rem; }
                                                        </style>
                                                        <?php
                                                        if (!isset($_COOKIE['alert_closed_product_' . $product_id])) {
                                                            echo '<div id="hidenahoy-' . $product_id . '" class="alert alert-danger alert-dismissible fade show" role="alert">
                                                                    Не проеби указать <strong>ONU</strong> если ставили, ну и <strong>роутер</strong>!</strong>
                                                                    <button type="button" class="btn-close" id="close-alert-' . $product_id . '" data-bs-dismiss="alert" aria-label="Close"></button>
                                                                  </div>';
                                                        }
                                                    }
                                                    ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-start" style="text-align: left;<?=$bg_acent?>">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-normal">
                                                                <a style="color:#000;" href="edit_array_montaj.php?id=<?=isset($vid_rabot['id']) ? $vid_rabot['id'] : '' ?>&mon_id=<?=isset($id) ? $id : '' ?>&name=<?=isset($vid_rabot['name']) ? urlencode($vid_rabot['name']) : '' ?>&status_baza=<?=isset($vid_rabot['status_baza']) ? $vid_rabot['status_baza'] : '' ?>">
                                                                    <?= htmlspecialchars($vid_rabot['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                                    <?php
                                                                    if (isset($vid_rabot['name']) && $vid_rabot['name'] == "Переработка вечер с 18 до 22") {
                                                                        if (isset($vid_rabot['count'], $ebat_code) && $ebat_code != 0) {
                                                                            $vid_rabot['count'] = $vid_rabot['count'] / $ebat_code;
                                                                            if ($vid_rabot['count'] == 1) {
                                                                                echo "( " . htmlspecialchars($vid_rabot['count'], ENT_QUOTES, 'UTF-8') . " час / " . htmlspecialchars($ebat_code, ENT_QUOTES, 'UTF-8') . " чел.)";
                                                                            } else {
                                                                                echo "( " . htmlspecialchars($vid_rabot['count'], ENT_QUOTES, 'UTF-8') . " часа / " . htmlspecialchars($ebat_code, ENT_QUOTES, 'UTF-8') . " чел.)";
                                                                            }
                                                                        } else {
                                                                            echo "Некорректные данные для расчета.";
                                                                        }
                                                                    } else {
                                                                        if (isset($vid_rabot['count'])) {
                                                                            if ($vid_rabot['count'] != 1) {
                                                                                if (isset($vid_rabot['price']) && $vid_rabot['price'] == 0) {
                                                                                    echo "( " . htmlspecialchars($vid_rabot['count'], ENT_QUOTES, 'UTF-8') . " метров)";
                                                                                } else {
                                                                                    echo "( " . htmlspecialchars($vid_rabot['count'], ENT_QUOTES, 'UTF-8') . " единиц)";
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                </a>
                                                                <?php
                                                                if ($vid_rabot['name'] == "Другие виды работ") {
                                                                ?>
                                                                    <span class="text-muted fw-light" style="font-size: small;"><?= htmlspecialchars($vid_rabot['text'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                                                <?php
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <?php
                                                        if (($vid_rabot['price'] ?? 0) != 0) {
                                                        ?>
                                                            <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($vid_rabot['price'], ENT_QUOTES, 'UTF-8') ?>р.</span>
                                                        <?php
                                                        }
                                                        ?>
                                                        <a href="edit_mon.php?delete=<?= urlencode($vid_rabot['id']) ?>&mon_id=<?= urlencode($id) ?>&technik1=<?= urlencode($tech1) ?>&technik2=<?= urlencode($tech2) ?>&technik3=<?= urlencode($tech3) ?>&technik4=<?= urlencode($tech4) ?>&technik5=<?= urlencode($tech5) ?>&technik6=<?= urlencode($tech6) ?>&technik7=<?= urlencode($tech7) ?>&technik8=<?= urlencode($tech8) ?>" class="delete-item">
                                                            <span class="badge bg-danger rounded-pill">X</span>
                                                        </a>
                                                    </li>
                                                <?php
                                                }
                                                $stmt->close();
                                                ?>
                                            </ol>
                                            <!-- Переносим materials-and-summary из списка наружу -->
                                            <span style="background: #ffffff;" id="materials-and-summary">
                                                <?php
                                                $stmt = $connect->prepare("SELECT * FROM used_material WHERE id_montaj = ?");
                                                $stmt->bind_param("i", $mon['id']);
                                                $stmt->execute();
                                                $um = $stmt->get_result();
                                                $num_results = $um->num_rows;
                                                if ($num_results > 0) {
                                                    echo ' Материалы: <br>';
                                                    while ($materials = $um->fetch_array(MYSQLI_ASSOC)) {
                                                        $chego = ($materials['count'] ?? 0) > 4 ? "м." : "шт.";
                                                        echo "<a style='color: black; text-decoration: underline;' href='edit_mon.php?material_delete=" . urlencode($materials['id']) . "&mon_id=" . urlencode($mon['id']) . "&status=" . urlencode($mon['status']) . "&status_baza=" . urlencode($mon['status_baza']) . "&technik1=" . urlencode($tech1) . "&technik2=" . urlencode($tech2) . "&technik3=" . urlencode($tech3) . "&technik4=" . urlencode($tech4) . "&technik5=" . urlencode($tech5) . "&technik6=" . urlencode($tech6) . "&technik7=" . urlencode($tech7) . "&technik8=" . urlencode($tech8) . "' >" . htmlspecialchars($materials['name'] ?? '', ENT_QUOTES, 'UTF-8') . "  <b style='color:red;' >" . htmlspecialchars($materials['count'] ?? '', ENT_QUOTES, 'UTF-8') . " $chego </b><br></a>";
                                                    }
                                                }
                                                $stmt->close();

                                                echo "Сумма:<span style='color: green;font-weight: bold;' id='summa'>" . htmlspecialchars($mon['summa'] ?? '', ENT_QUOTES, 'UTF-8') . "₽ </span>";
                                                echo "Каждому:<span style='color: green;font-weight: bold;' id='kajdomu'>" . htmlspecialchars($mon['kajdomu'] ?? '', ENT_QUOTES, 'UTF-8') . "₽</span>";
                                                echo "<br>";
                                                $ebat_code = 0;
                                                $who = "";
                                                for ($i = 1; $i <= 8; $i++) {
                                                    $tech = "tech$i";
                                                    if (!empty(${$tech})) {
                                                        $ebat_code = $i;
                                                        $who .= htmlspecialchars($mon["technik$i"] ?? '', ENT_QUOTES, 'UTF-8') . ",";
                                                    }
                                                }
                                                $who = rtrim($who, ",");
                                                echo "Делали: <span id='techniks'>$who</span> ";
                                                echo '<a id="image_tech"><i class="bi bi-arrow-left-right"></i></a>';
                                                ?>
                                                <br><br>
                                                <script>
                                                    $(document).ready(function() {
                                                        $("#image_tech").click(function() {
                                                            $("#dropdown").toggle();
                                                        });
                                                    });
                                                </script>
                                            </span>
                                            <form method="GET" action="edit_mon.php" style="background: white;" id="montaj-form" autocomplete="off">
                                                <div id="dropdown" style="display: none;">
                                                    <?php
                                                    $stmt = $connect->prepare("SELECT * FROM `user` WHERE `region` = ? ORDER BY `brigada`");
                                                    $stmt->bind_param("s", $usr['region']);
                                                    $stmt->execute();
                                                    $res_data = $stmt->get_result();
                                                    while ($tech = $res_data->fetch_array(MYSQLI_ASSOC)) {
                                                        $check = in_array($tech['fio'], [$tech1, $tech2, $tech3, $tech4, $tech5, $tech6, $tech7, $tech8]) ? "checked" : "";
                                                    ?>
                                                        <div class="form-check">
                                                            <div id="checklist" class="form-check">
                                                                <input <?=$check ?> type="checkbox" value="<?= htmlspecialchars($tech['fio'] ?? '', ENT_QUOTES, 'UTF-8') ?>" name="technik[]" id="flexCheckDefault<?=$tech['id'] ?>">
                                                                <label for="flexCheckDefault<?=$tech['id'] ?>"> <?= htmlspecialchars($tech['fio'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    }
                                                    $stmt->close();
                                                    ?>
                                                </div>
                                                <?php
                                                $status = ($mon['status'] ?? 0) == "1" ? "checked" : "";
                                                $dogovor = ($mon['dogovor'] ?? 0) == "1" ? "checked" : "";
                                                $if_baza = ($mon['status_baza'] ?? 0) == "1" ? "white" : "white"; // Всегда white
                                                $status_baza = ($mon['status_baza'] ?? 0) == "1" ? "checked" : "";
                                                $stat = ($mon['status'] ?? 0) == "1" ? "checked" : "";
                                                $stat_baza = ($mon['status_baza'] ?? 0) == "1" ? "checked" : "";
                                                $dogovor = ($mon['dogovor'] ?? 0) == "1" ? "checked" : "";
                                                ?>
                                                <div class="container" style="margin-top: 1rem;">
                                                    <div class="row">
                                                        <div class="col">
                                                            <label class="form-check-label" for="dogovor">Нет договора</label>
                                                        </div>
                                                        <div class="col">
                                                            <label class="form-check-label" for="stat">Подтвердили</label>
                                                        </div>
                                                        <div class="col">
                                                            <label class="form-check-label" for="stat_baza" id="stat_baza_label" style="display: <?=$stat == 'checked' ? 'none' : 'block'?>">В базе</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="container">
                                                    <div class="row">
                                                        <div class="col">
                                                            <div style="display: block; background: <?= $if_baza ?>; text-align: left; padding: 5px 25% 0px;">
                                                                <div class="form-check form-switch" style="display: inline-block;">
                                                                    <input name="dogovor" class="dogovor form-check-input" value="" type="checkbox" id="dogovor" data-ajax-handler data-ajaxname="dogovor" data-mon-id="<?= $id ?>" data-server-script="update_dogovor.php" <?= $dogovor ?>>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div style="display: block; background: <?= $if_baza ?>; text-align: left; padding: 5px 25% 0px;">
                                                                <div class="form-check form-switch" style="display: inline-block;">
                                                                    <input name="status" class="status form-check-input" type="checkbox" id="stat" data-ajax-handler data-mon-id="<?= $id ?>" data-ajaxname="stat" data-mon-dat="<?= htmlspecialchars($mon['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-server-script="update_status.php" <?= $stat ?>>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col" id="stat-baza-container" style="display: <?=$stat == 'checked' ? 'none' : 'block'?>">
                                                            <div style="display: block; background: <?= $if_baza ?>; text-align: left; padding: 5px 25% 0px;">
                                                                <div class="form-check form-switch" style="display: inline-block;">
                                                                    <input name="status_baza" class="status_baza form-check-input" type="checkbox" id="stat_baza" data-ajax-handler data-mon-id="<?= $id ?>" data-ajaxname="stat_baza" data-server-script="update_status_baza.php" <?= $stat_baza ?>>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php material_main("material", "material_count"); ?>

                                                <hr>
                                                <div style="display: flex;align-items: flex-start;flex-direction: row;justify-content: center;">
    <small class="form-text">Добавить вид работ и количество</small>
</div>

                                                <style>
                                                    .g-3, .gy-3 { background: #fff; }
                                                    .dropdown-item.active,
                                                    .dropdown-item:active {
                                                        background-color: #40fd0d26;
                                                    }
                                                </style>

                                                <input name="mon_id" type="hidden" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                                                <input name="summa" type="hidden" value="<?= htmlspecialchars($row_price_test ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <input name="kajdomu" type="hidden" value="<?= htmlspecialchars($kajdomu ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                                <?php
                                                vid_rabot_main("vid_rabot1", "count1");
                                                vid_rabot_main("vid_rabot2", "count2");
                                                vid_rabot_main("vid_rabot3", "count3");
                                                vid_rabot_submain("vid_rabot4", "count4");
                                                ?>

                                                <div data-role="footer">
                                                    <div class="d-grid gap-2">
                                                        <button type="submit" class="btn btn-success btn-lg" id="submit-btn">Отправить данные</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Элементы спиннера и уведомления -->
<div class="spinner" id="loading-spinner"></div>
<div class="notification" id="success-notification">Данные успешно обновлены!</div>

<div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="dateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dateModalLabel">Изменение даты монтажа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="montageCalendar"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveDate">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.bundle.min.js"></script>
<script src="js/bootstrap-select.js"></script>
<script>
    // Функция для генерации календаря
    function generateCalendar(year, month, selectedDate) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startingDay = firstDay.getDay() || 7; // Преобразуем воскресенье (0) в 7
        const totalDays = lastDay.getDate();
        
        const monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 
                           'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
        
        let calendar = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(${year}, ${month - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h5 class="mb-0">${monthNames[month]} ${year}</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="changeMonth(${year}, ${month + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>`;
        
        calendar += '<table class="table table-bordered"><thead><tr>';
        
        // Заголовки дней недели
        const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        weekDays.forEach(day => calendar += `<th>${day}</th>`);
        calendar += '</tr></thead><tbody>';
        
        // Пустые ячейки до первого дня месяца
        for (let i = 1; i < startingDay; i++) {
            calendar += '<td></td>';
        }
        
        // Дни месяца
        for (let day = 1; day <= totalDays; day++) {
            const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isSelected = currentDate === selectedDate ? 'bg-warning text-dark' : '';
            calendar += `<td class="${isSelected}" data-date="${currentDate}">${day}</td>`;
            
            if ((day + startingDay - 1) % 7 === 0) {
                calendar += '</tr><tr>';
            }
        }
        
        calendar += '</tbody></table>';
        return calendar;
    }

    // Функция для смены месяца
    function changeMonth(year, month) {
        if (month < 0) {
            year--;
            month = 11;
        } else if (month > 11) {
            year++;
            month = 0;
        }
        const selectedDate = $('#montageCalendar td.bg-warning').data('date') || '';
        const calendar = generateCalendar(year, month, selectedDate);
        $('#montageCalendar').html(calendar);
    }

    $(document).ready(function() {
        // Инициализация Bootstrap Select
        $('.selectpicker').selectpicker();
        $('.selectpicker').on('shown.bsAIDselect', function() {
            var searchInput = $(this).next('.dropdown-menu').find('.bs-searchbox input');
            searchInput.blur();
        });

        // Обработка отправки формы через AJAX
        $('#montaj-form').submit(function(event) {
            event.preventDefault();

            const spinner = $('#loading-spinner');
            spinner.show();

            const formData = $(this).serialize();

            $.ajax({
                url: 'edit_mon.php',
                type: 'GET',
                data: formData,
                success: function(response) {
                    spinner.hide();
                    updatePageData(<?=$id?>);
                    showSuccessNotification();
                    clearFormFields(); // Очистка полей после успешной отправки
                },
                error: function(xhr, status, error) {
                    spinner.hide();
                    console.error('Ошибка AJAX при отправке формы:', error, xhr.responseText);
                    alert('Ошибка при отправке данных: ' + error);
                }
            });
        });

        // Обработка удаления через AJAX
        $('.delete-item').on('click', function(event) {
            event.preventDefault();

            const spinner = $('#loading-spinner');
            spinner.show();

            const url = $(this).attr('href');
            const monId = <?=$id?>;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    spinner.hide();
                    updatePageData(monId);
                    showSuccessNotification('Элемент успешно удалён!');
                },
                error: function(xhr, status, error) {
                    spinner.hide();
                    console.error('Ошибка AJAX при удалении:', error, xhr.responseText);
                    alert('Ошибка при удалении: ' + error);
                }
            });
        });

        // Обработка чекбоксов через AJAX
        $('[data-ajax-handler]').on('change', function() {
            const $checkbox = $(this);
            const monId = $checkbox.data('mon-id');
            const ajaxName = $checkbox.data('ajaxname');
            const serverScript = $checkbox.data('server-script');
            const monDat = $checkbox.data('mon-dat');
            const value = $checkbox.is(':checked') ? 1 : 0;
            const spinner = $('#loading-spinner');

            // Форматирование даты для редиректа
            let formattedDat = '';
            if (monDat) {
                const parts = monDat.split('-');
                if (parts.length >= 2) {
                    formattedDat = parts[0] + '-' + parts[1];
                }
            }

            spinner.show();

            $.ajax({
                url: serverScript,
                type: 'POST',
                data: {
                    monId: monId,
                    ajaxname: value
                },
                dataType: 'json',
                success: function(response) {
                    spinner.hide();
                    if (response.success) {
                        if (ajaxName === 'stat' && value === 1) {
                            window.location.href = '/index.php?date=' + formattedDat + '&status=success';
                        } else {
                            showSuccessNotification(response.message);
                            updatePageData(monId);
                        }
                    } else {
                        console.error('Ошибка от сервера:', response.message);
                        alert('Ошибка: ' + response.message);
                        $checkbox.prop('checked', !$checkbox.is(':checked'));
                    }
                },
                error: function(xhr, status, error) {
                    spinner.hide();
                    console.error('Ошибка AJAX:', error, xhr.responseText);
                    alert('Ошибка при обновлении: ' + error);
                    $checkbox.prop('checked', !$checkbox.is(':checked'));
                }
            });
        });

        // Функция для обновления данных на странице
        function updatePageData(monId) {
            $.ajax({
                url: 'fetch_montaj_data.php',
                type: 'GET',
                data: { mon_id: monId },
                dataType: 'json',
                success: function(data) {
                    console.log('Полученные данные:', data);

                    if (data.error) {
                        console.error('Ошибка в данных:', data.error);
                        alert('Ошибка: ' + data.error);
                        return;
                    }

                    const montajList = $('#montaj-list');
                    montajList.empty();
                    data.montaj_items.forEach(item => {
                        const bgAcent = item.price == 0 ? "background: #c8e4f58c;" : "";
                        let countText = '';
                        if (item.name === "Переработка вечер с 18 до 22") {
                            const ebatCode = data.ebat_code || 0;
                            if (item.count && ebatCode) {
                                const hours = item.count / ebatCode;
                                countText = `(${hours} ${hours == 1 ? 'час' : 'часа'} / ${ebatCode} чел.)`;
                            } else {
                                countText = 'Некорректные данные для расчета.';
                            }
                        } else if (item.count && item.count != 1) {
                            countText = item.price == 0 ? `(${item.count} метров)` : `(${item.count} единиц)`;
                        }
                        const priceBadge = item.price != 0 ? `<span class="badge bg-primary rounded-pill">${item.price}р.</span>` : '';
                        const textSpan = item.name === "Другие виды работ" ? `<span class="text-muted fw-light" style="font-size: small;">${item.text || ''}</span>` : '';
                        montajList.append(`
                            <li class="list-group-item d-flex justify-content-between align-items-start" style="text-align: left;${bgAcent}">
                                <div class="ms-2 me-auto">
                                    <div class="fw-normal">
                                        <a style="color:#000;" href="edit_array_montaj.php?id=${item.id}&mon_id=${monId}&name=${encodeURIComponent(item.name)}&status_baza=${item.status_baza}">
                                            ${item.name} ${countText}
                                        </a>
                                        ${textSpan}
                                    </div>
                                </div>
                                ${priceBadge}
                                <a href="edit_mon.php?delete=${item.id}&mon_id=${monId}&technik1=${encodeURIComponent(data.technik1 || '')}&technik2=${encodeURIComponent(data.technik2 || '')}&technik3=${encodeURIComponent(data.technik3 || '')}&technik4=${encodeURIComponent(data.technik4 || '')}&technik5=${encodeURIComponent(data.technik5 || '')}&technik6=${encodeURIComponent(data.technik6 || '')}&technik7=${encodeURIComponent(data.technik7 || '')}&technik8=${encodeURIComponent(data.technik8 || '')}" class="delete-item">
                                    <span class="badge bg-danger rounded-pill">X</span>
                                </a>
                            </li>
                        `);
                    });

                    const materialsAndSummary = $('#materials-and-summary');
                    if (!materialsAndSummary.length) {
                        console.error('Элемент #materials-and-summary не найден в DOM');
                        return;
                    }
                    console.log('Обновляем #materials-and-summary с данными:', data);
                    materialsAndSummary.empty();
                    if (data.materials && data.materials.length > 0) {
                        materialsAndSummary.append(' Материалы: <br>');
                        data.materials.forEach(material => {
                            const unit = material.count > 4 ? 'м.' : 'шт.';
                            materialsAndSummary.append(`
                                <a style="color: black; text-decoration: underline;" href="edit_mon.php?material_delete=${material.id}&mon_id=${monId}&status=${data.status}&status_baza=${data.status_baza}&technik1=${encodeURIComponent(data.technik1 || '')}&technik2=${encodeURIComponent(data.technik2 || '')}&technik3=${encodeURIComponent(data.technik3 || '')}&technik4=${encodeURIComponent(data.technik4 || '')}&technik5=${encodeURIComponent(data.technik5 || '')}&technik6=${encodeURIComponent(data.technik6 || '')}&technik7=${encodeURIComponent(data.technik7 || '')}&technik8=${encodeURIComponent(data.technik8 || '')}">
                                    ${material.name} <b style="color:red;">${material.count} ${unit}</b><br>
                                </a>
                            `);
                        });
                    }
                    materialsAndSummary.append(`
                        Сумма:<span style="color: green;font-weight: bold;" id="summa">${data.summa || '0'}₽ </span>
                        Каждому:<span style="color: green;font-weight: bold;" id="kajdomu">${data.kajdomu || '0'}₽</span><br>
                        Делали: <span id="techniks">${data.techniks || ''}</span> 
                        <a id="image_tech"><i class="bi bi-arrow-left-right"></i></a><br><br>
                    `);

                    const statBazaContainer = $('#stat-baza-container');
                    const statBazaLabel = $('#stat_baza_label');
                    if (data.status == 1) {
                        statBazaContainer.hide();
                        statBazaLabel.hide();
                    } else {
                        statBazaContainer.show();
                        statBazaLabel.show();
                        $('#stat_baza').prop('checked', data.status_baza == 1);
                    }

                    $("#image_tech").off('click').on('click', function() {
                        $("#dropdown").toggle();
                    });

                    gsap.from(".list-group-item", {
                        opacity: 0,
                        x: -30,
                        duration: 0.6,
                        stagger: 0.1,
                        ease: "power2.out"
                    });
                    console.log('Обновление #materials-and-summary завершено');

                    bindDeleteHandlers();
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка при обновлении данных:', error, xhr.responseText);
                    alert('Ошибка при обновлении данных: ' + error + '\nОтвет сервера: ' + xhr.responseText);
                }
            });
        }

        // Функция для показа уведомления
        function showSuccessNotification(message = 'Данные успешно обновлены!') {
            const notification = $('#success-notification');
            notification.text(message);
            gsap.fromTo(notification, 
                { opacity: 0, y: -20 }, 
                { 
                    display: 'block', 
                    opacity: 1, 
                    y: 0, 
                    duration: 0.5, 
                    ease: "power2.out",
                    onComplete: function() {
                        gsap.to(notification, {
                            opacity: 0,
                            y: -20,
                            duration: 0.5,
                            ease: "power2.in",
                            delay: 2,
                            onComplete: function() {
                                notification.hide();
                            }
                        });
                    }
                }
            );
        }

        // Функция для привязки обработчиков удаления
        function bindDeleteHandlers() {
            $('.delete-item').off('click').on('click', function(event) {
                event.preventDefault();

                const spinner = $('#loading-spinner');
                spinner.show();

                const url = $(this).attr('href');
                const monId = <?=$id?>;

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        spinner.hide();
                        updatePageData(monId);
                        showSuccessNotification('Элемент успешно удалён!');
                    },
                    error: function(xhr, status, error) {
                        spinner.hide();
                        console.error('Ошибка AJAX при удалении:', error, xhr.responseText);
                        alert('Ошибка при удалении: ' + error);
                    }
                });
            });
        }

        // Функция для очистки полей формы, включая поля поиска в модальных окнах
        function clearFormFields() {
            const fields = [
                { vid: 'vid_rabot1', count: 'count1', button: 'button_vid_rabot1', search: 'search_vid_rabot1' },
                { vid: 'vid_rabot2', count: 'count2', button: 'button_vid_rabot2', search: 'search_vid_rabot2' },
                { vid: 'vid_rabot3', count: 'count3', button: 'button_vid_rabot3', search: 'search_vid_rabot3' },
                { vid: 'vid_rabot4', count: 'count4', button: 'button_vid_rabot4', search: 'search_vid_rabot4' }
            ];

            fields.forEach(field => {
                const vidInput = document.getElementById('selected_' + field.vid);
                const countInput = document.getElementsByName(field.count)[0];
                const button = document.getElementById(field.button);
                const searchInput = document.getElementById(field.search);

                if (vidInput && countInput && button) {
                    vidInput.value = '';
                    countInput.value = '';
                    button.innerText = field.vid === 'vid_rabot4' ? 'Редко используемые' : 'Часто используемые';
                    button.style.color = '#999';

                    // Очистка поля поиска
                    if (searchInput) {
                        searchInput.value = '';
                        liveSearch(field.vid); // Сброс фильтрации после очистки
                    }

                    gsap.to(button, { opacity: 0, duration: 0.2, onComplete: function() {
                        gsap.to(button, { opacity: 1, duration: 0.2 });
                    }});
                    gsap.to(countInput, { opacity: 0, duration: 0.2, onComplete: function() {
                        gsap.to(countInput, { opacity: 1, duration: 0.2 });
                    }});
                }
            });

            const materialSelect = $('select[name="material"]');
            const materialCount = $('input[name="material_count"]');
            if (materialSelect.length && materialCount.length) {
                materialSelect.val('');
                materialCount.val('');
                materialSelect.selectpicker('refresh');
            }
        }

        bindDeleteHandlers();

        // Обработчик для блока с датой
        $(document).on('click', '.date-block', function(e) {
            e.stopPropagation();
            const currentDate = $(this).data('date');
            const date = new Date(currentDate);
            const calendar = generateCalendar(date.getFullYear(), date.getMonth(), currentDate);
            $('#montageCalendar').html(calendar);
            $('#dateModal').modal('show');
        });

        // Обработчик клика по ячейке календаря
        $(document).on('click', '#montageCalendar td[data-date]', function() {
            $('#montageCalendar td').removeClass('bg-warning text-dark');
            $(this).addClass('bg-warning text-dark');
        });

        // Обработчик сохранения даты
        $('#saveDate').click(function() {
            const selectedDate = $('#montageCalendar td.bg-warning').data('date');
            if (!selectedDate) {
                showNotification('Пожалуйста, выберите дату', 'error');
                return;
            }

            $.ajax({
                url: 'update_date.php',
                type: 'POST',
                data: {
                    id: <?= $id ?>,
                    date: selectedDate
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Дата успешно обновлена', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification(response.message || 'Ошибка при обновлении даты', 'error');
                    }
                },
                error: function() {
                    showNotification('Ошибка при обновлении даты', 'error');
                }
            });
        });

        // Функция для показа уведомлений
        function showNotification(message, type = 'success') {
            const notification = $('<div>')
                .addClass('notification')
                .css('background-color', type === 'success' ? '#28a745' : '#dc3545')
                .text(message);
            
            $('body').append(notification);
            notification.fadeIn();
            
            setTimeout(() => {
                notification.fadeOut(() => {
                    notification.remove();
                });
            }, 3000);
        }

        // Обработчик клика по кнопке удаления фото
        $(document).on('click', '#delete-photo', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('Вы уверены, что хотите удалить это изображение?')) {
                $.ajax({
                    url: 'delete_photo.php',
                    type: 'POST',
                    data: {
                        id: '<?= $encodedStr ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Изображение успешно удалено', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification(response.message || 'Ошибка при удалении', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Ошибка при удалении файла', 'error');
                    }
                });
            }
        });

        // Обработчик клика по блоку загрузки изображения
        $(document).on('click', '.upload-trigger', function(e) {
            if (!$(e.target).closest('.date-block').length) {
                $('.upload-form').slideToggle();
            }
        });

        // Обработчик клика по изображению
        $(document).on('click', '#ava img', function(e) {
            e.preventDefault();
            $('.upload-form').slideToggle();
        });

        // Обработчик загрузки изображения
        $('#upload-btn').click(function() {
            var fileInput = $('#inputGroupFile02');
            var file = fileInput[0].files[0];
            
            if (!file) {
                showNotification('Пожалуйста, выберите файл', 'error');
                return;
            }

            var formData = new FormData();
            formData.append('userfile', file);
            formData.append('id', '<?= $encodedStr ?>');

            $.ajax({
                url: 'download_img.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('.progress').show();
                    $('.progress-bar').css('width', '0%');
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Изображение успешно загружено', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification(response.message || 'Ошибка при загрузке', 'error');
                    }
                },
                error: function() {
                    showNotification('Ошибка при загрузке файла', 'error');
                },
                complete: function() {
                    $('.progress').hide();
                    fileInput.val('');
                }
            });
        });
    });

    // Анимации GSAP при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        gsap.from("#mon_adress, #mon_adress_text", {
            opacity: 0,
            y: 20,
            duration: 0.8,
            stagger: 0.2,
            ease: "power2.out"
        });

        gsap.from(".list-group-item", {
            opacity: 0,
            x: -30,
            duration: 0.6,
            stagger: 0.1,
            ease: "power2.out",
            delay: 0.2
        });

        gsap.from(".badge", {
            opacity: 0,
            scale: 0.5,
            duration: 0.4,
            stagger: 0.05,
            ease: "elastic.out(1, 0.3)",
            delay: 0.4
        });

        gsap.from([".form-check", ".btn-success"], {
            opacity: 0,
            scale: 0.9,
            duration: 0.6,
            ease: "back.out(1.7)",
            delay: 0.8
        });
    });

    // Повторная привязка событий после перехода Barba.js
    barba.hooks.enter(() => {
        gsap.from("#mon_adress, #mon_adress_text", {
            opacity: 0,
            y: 20,
            duration: 0.8,
            stagger: 0.2,
            ease: "power2.out"
        });

        gsap.from(".list-group-item", {
            opacity: 0,
            x: -30,
            duration: 0.6,
            stagger: 0.1,
            ease: "power2.out",
            delay: 0.2
        });

        gsap.from(".badge", {
            opacity: 0,
            scale: 0.5,
            duration: 0.4,
            stagger: 0.05,
            ease: "elastic.out(1, 0.3)",
            delay: 0.4
        });

        gsap.from([".form-check", ".btn-success"], {
            opacity: 0,
            scale: 0.9,
            duration: 0.6,
            ease: "back.out(1.7)",
            delay: 0.8
        });

        var image = document.getElementById("image");
        var block = document.getElementById("update_form");
        image.addEventListener("click", function() {
            if (block.style.display === "none") {
                block.style.display = "block";
            } else {
                block.style.display = "none";
            }
        });

        var image_text = document.getElementById("image_text");
        var block_text = document.getElementById("update_form_text");
        image_text.addEventListener("click", function() {
            if (block_text.style.display === "none") {
                block_text.style.display = "block";
            } else {
                block_text.style.display = "none";
            }
        });

        $("#image_tech").click(function() {
            $("#dropdown").toggle();
        });
    });
</script>

<br>
<?php
function result_ava($encodedStr, $mon)
{
    $filename = "img/screen/$encodedStr.png";
    $tim = file_exists($filename) ? filemtime($filename) : time();
    $ava = file_exists($filename) ? "img/screen/$encodedStr.png?r=$tim" : "";
    echo '<div id="ava">';
    echo '<span class="upload-trigger" style="background: #e9ab4f85; display:block; cursor: pointer;"><i class="fas fa-camera"></i> Прикрепить изображение';
    ?>
    <span class="date-block" data-date="<?= date('Y-m-d', strtotime($mon['date'])) ?>">
        <i class="far fa-calendar-alt"></i> <?= date('Y-m-d', strtotime($mon['date'])) ?>
    </span>
    <?
    if (!empty($ava)) {
        echo '<div class="d-grid gap-2">';
        echo '<button type="button" id="delete-photo" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Удалить фото</button>';
        echo '</div>';

        echo '<a id="download-photo" download href="' . $ava . '">';
        echo '<img style="width: 100%; height: 300px; border-radius: 8px;" data-toggle="tooltip" data-placement="top" title="Для смены нажмите на изображение" class="img-fluid mx-auto d-block" loading="lazy" src="' . $ava . '" alt="">';
        echo '</a>';
    }

    echo '</span>';
    echo '</div>';
    ?>
    <div class="upload-form" style="display: none">
        <div class="input-group">
            <input type="file" name="userfile" class="form-control" id="inputGroupFile02" accept="image/*">
            <button type="button" id="upload-btn" class="btn btn-primary">
                <i class="fas fa-upload"></i> Загрузить
            </button>
        </div>
        <div class="progress mt-2" style="display: none">
            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>
    </div>
    <?
}

include 'inc/foot.php';
?>