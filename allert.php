<?php
// Включаем буферизацию вывода
ob_start();

include "inc/head.php";
AutorizeProtect();
access();
animate();
global $usr;

// Подключение к базе данных напрямую
const HOST = "localhost";
const USER = "root";
const BAZA = "ardmoney";
const PASS = "root";

global $connect;
$connect = new mysqli(HOST, USER, PASS, BAZA);
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}
$connect->query("SET NAMES 'utf8mb4'");

// Проверка авторизации
if (empty($_COOKIE['user'])) {
    echo 'Вы не авторизованы!';
    exit;
}
$userLogin = $_COOKIE['user'];

// Обработка действий
if (isset($_GET['action'])) {
    // Очищаем буфер вывода перед отправкой AJAX-ответа
    ob_end_clean();
    
    if ($_GET['action'] === 'read' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $connect->prepare("DELETE FROM notifications WHERE id = ? AND user_login = ?");
        $stmt->bind_param("ss", $id, $userLogin);
        $stmt->execute();
        $stmt->close();
        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK';
        exit;
    } elseif ($_GET['action'] === 'clear_all') {
        $stmt = $connect->prepare("DELETE FROM notifications WHERE user_login = ?");
        $stmt->bind_param("s", $userLogin);
        $stmt->execute();
        $stmt->close();
        header('Content-Type: text/plain; charset=utf-8');
        echo 'OK';
        exit;
    }
}

// Получение уведомлений для текущего пользователя
$notifications = [];
$stmt = $connect->prepare("SELECT id, title, body, received_at FROM notifications WHERE user_login = ? ORDER BY received_at DESC");
$stmt->bind_param("s", $userLogin);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Выводим буфер для обычного отображения страницы
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALLERT Уведомления</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Animate.css для анимаций -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts для современного шрифта -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            overflow-x: hidden;
        }
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        .container {
            max-width: 800px;
            margin-top: 40px;
            position: relative;
        }
        .notification-row {
            background: #ffffff;
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08), inset 0 1px 3px rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
            transition: opacity 0.5s ease, transform 0.5s ease; /* Анимация для удаления */
        }
        .notification-row:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), inset 0 2px 5px rgba(255, 255, 255, 0.9);
            background: #f9fafb;
        }
        .notification-row::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(29, 161, 242, 0.15), transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .notification-row:hover::before {
            opacity: 1;
        }
        .notification-placeholder {
            transition: height 0.5s ease; /* Плавное изменение высоты заполнителя */
            overflow: hidden;
        }
        .notification-title {
            font-size: 1.25em;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1f2937;
            background: linear-gradient(90deg, #1da1f2, #17bf63);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .notification-body {
            font-size: 1em;
            color: #4b5563;
            margin-bottom: 15px;
            line-height: 1.6;
            font-weight: 400;
        }
        .notification-time {
            font-size: 0.85em;
            color: #ffffff;
            background: rgba(75, 85, 99, 0.8);
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .btn-delete {
            background: transparent;
            border: none;
            color: #ef4444;
            font-size: 1.2em;
            padding: 5px;
            transition: color 0.3s ease, transform 0.3s ease, filter 0.3s ease;
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
        }
        .btn-delete:hover {
            color: #f87171;
            transform: scale(1.2) rotate(10deg);
            filter: drop-shadow(0 2px 5px rgba(239, 68, 68, 0.5));
        }
        .btn-clear-all {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(45deg, #1da1f2, #17bf63);
            border: none;
            padding: 12px 25px;
            font-size: 1em;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            z-index: 1000;
        }
        .btn-clear-all:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .no-notifications {
            text-align: center;
            color: #6b7280;
            padding: 40px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            font-size: 1.4em;
            font-weight: 600;
        }
        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }
            .notification-row {
                padding: 15px;
            }
            .notification-title {
                font-size: 1.1em;
            }
            .notification-body {
                font-size: 0.95em;
            }
            .btn-clear-all {
                padding: 10px 20px;
                font-size: 0.9em;
            }
            .btn-delete {
                font-size: 1em;
                top: 10px;
                right: 10px;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.notification-row').addClass('animate__animated animate__fadeInUp');

            $('.btn-delete').click(function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var $notification = $('#notification-' + id);
                var height = $notification.outerHeight(); // Запоминаем высоту удаляемого элемента

                // Создаём прозрачный заполнитель с той же высотой
                var $placeholder = $('<div class="notification-placeholder"></div>').css({
                    'height': height + 'px'
                });
                $notification.after($placeholder);

                // Анимация удаления уведомления влево
                $notification.css({
                    'transition': 'transform 0.5s ease, opacity 0.5s ease',
                    'transform': 'translateX(-100%)',
                    'opacity': '0'
                });

                // Уменьшаем высоту заполнителя до 0 после анимации
                setTimeout(function() {
                    $notification.remove(); // Удаляем уведомление из DOM
                    $placeholder.css('height', '0px'); // Плавно убираем заполнитель

                    // Удаляем заполнитель после завершения анимации высоты
                    setTimeout(function() {
                        $placeholder.remove();
                        checkEmptyList();
                    }, 500); // Соответствует времени анимации высоты
                }, 500); // Соответствует времени анимации ухода влево

                // Отправляем AJAX-запрос для удаления на сервере
                $.ajax({
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'GET',
                    data: { id: id, action: 'read' },
                    success: function(response) {
                        if (response !== 'OK') {
                            console.log('Ошибка сервера: ' + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX: ' + error);
                    }
                });
            });

            $('#clear-all').click(function(e) {
                e.preventDefault();
                $.ajax({
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'GET',
                    data: { action: 'clear_all' },
                    success: function(response) {
                        if (response === 'OK') {
                            $('.notification-row').addClass('animate__animated animate__fadeOutDown').one('animationend', function() {
                                $(this).remove();
                                checkEmptyList();
                            });
                        } else {
                            console.log('Ошибка сервера: ' + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX: ' + error);
                    }
                });
            });

            function checkEmptyList() {
                if ($('.notification-row').length === 0) {
                    $('#notifications-list').html('<div class="no-notifications animate__animated animate__fadeIn">Нет уведомлений</div>');
                    $('#clear-all').hide();
                } else if ($('.notification-row').length <= 2) {
                    $('#clear-all').hide();
                } else {
                    $('#clear-all').show();
                }
            }

            // Инициализация видимости кнопки при загрузке
            checkEmptyList();
        });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light" style="padding: 0;">
        <div class="container-fluid" style="background: #ffffff;"></div>
    </nav>

    <div class="container">
        <div id="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications animate__animated animate__fadeIn">Нет уведомлений</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-row" id="notification-<?php echo htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="notification-body"><?php echo nl2br(htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8')); ?></div>
                            <div class="notification-time"><?php echo htmlspecialchars($notification['received_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <button class="btn-delete" data-id="<?php echo htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (count($notifications) > 2): ?>
            <button id="clear-all" class="btn btn-clear-all animate__animated animate__bounceIn">Очистить все</button>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <?php include 'inc/foot.php'; ?>
</body>
</html>
