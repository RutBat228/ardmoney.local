<?php
include "inc/head.php";
AutorizeProtect();
access();
animate();
global $usr;

if ($usr['rang'] != "Мастер участка" && $usr['name'] != "RutBat") {
    echo 'Тебе тут не место!!!';
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
const HOST = "localhost";
const USER = "root";
const BAZA = "ardmoney";
const PASS = "root";

global $connect;
$connect = new mysqli(HOST, USER, PASS, BAZA);
$connect->query("SET NAMES 'utf8mb4' ");

define('SERVICE_ACCOUNT_FILE', __DIR__ . '/service-account.json');
define('FCM_PROJECT_ID', 'allert-b59d2');
define('FCM_URL', 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');
define('LOG_FILE', __DIR__ . '/fcm_log.txt');

function getAccessToken() {
    try {
        $client = new Google_Client();
        $client->setAuthConfig(SERVICE_ACCOUNT_FILE);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();
        if (empty($token['access_token'])) {
            throw new Exception('Не удалось получить access token');
        }
        return $token['access_token'];
    } catch (Exception $e) {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка получения токена: " . $e->getMessage() . "\n", FILE_APPEND);
        die("Ошибка получения токена: " . $e->getMessage());
    }
}

function getRegions() {
    global $connect;
    $regions = [];
    $result = $connect->query("SELECT DISTINCT region FROM user WHERE region IS NOT NULL ORDER BY region");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $regions[] = $row['region'];
        }
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Найдено регионов: " . count($regions) . "\n", FILE_APPEND);
    } else {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка запроса регионов: " . $connect->error . "\n", FILE_APPEND);
    }
    return $regions;
}

if (isset($_GET['region'])) {
    $region = $_GET['region'];
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Запрос пользователей для региона: $region\n", FILE_APPEND);

    $users = [];
    $stmt = $connect->prepare("SELECT name, fio FROM user WHERE region = ? ORDER BY name");
    if ($stmt) {
        $stmt->bind_param("s", $region);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $displayName = !empty($row['fio']) ? "{$row['fio']} ({$row['name']})" : $row['name'];
            $users[] = ['login' => $row['name'], 'display' => $displayName];
        }
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Найдено пользователей для региона $region: " . count($users) . "\n", FILE_APPEND);
    } else {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка подготовки запроса пользователей: " . $connect->error . "\n", FILE_APPEND);
    }
    
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($users);
    die();
}

function sendNotification($users, $title, $body, $messageId, $imageUrl) {
    global $connect;
    $accessToken = getAccessToken();
    $successCount = 0;
    $errorCount = 0;

    // Отладка входных данных
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Отправка уведомления для пользователей: " . implode(', ', $users) . "\n", FILE_APPEND);
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Данные перед сохранением: title='$title', body='$body', message_id='$messageId', image_url='$imageUrl'\n", FILE_APPEND);

    // Сохраняем уведомление в базу для каждого пользователя
    $stmt = $connect->prepare("INSERT INTO notifications (message_id, title, body, image_url, user_login) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка подготовки запроса для сохранения: " . $connect->error . "\n", FILE_APPEND);
        return ['success' => 0, 'error' => count($users)];
    }
    foreach ($users as $user) {
        // Убеждаемся, что все данные в UTF-8
        $title = mb_convert_encoding($title, 'UTF-8', 'UTF-8');
        $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
        $messageId = mb_convert_encoding($messageId, 'UTF-8', 'UTF-8');
        $imageUrl = mb_convert_encoding($imageUrl, 'UTF-8', 'UTF-8');
        $user = mb_convert_encoding($user, 'UTF-8', 'UTF-8');

        $stmt->bind_param("sssss", $messageId, $title, $body, $imageUrl, $user);
        if (!$stmt->execute()) {
            file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка выполнения запроса для $user: " . $stmt->error . "\n", FILE_APPEND);
        } else {
            file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Успешно сохранено для $user\n", FILE_APPEND);
        }
    }
    $stmt->close();

    $placeholders = implode(',', array_fill(0, count($users), '?'));
    $stmt = $connect->prepare("SELECT login, fcm_token FROM user_tokens WHERE login IN ($placeholders)");
    if (!$stmt) {
        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка подготовки запроса токенов: " . $connect->error . "\n", FILE_APPEND);
        return ['success' => 0, 'error' => count($users)];
    }

    $types = str_repeat('s', count($users));
    $stmt->bind_param($types, ...$users);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $token = $row['fcm_token'];
        $login = $row['login'];

        file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Отправка для $login с токеном: $token\n", FILE_APPEND);

        $messageData = [
            'message' => [
                'token' => $token,
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'message_id' => $messageId,
                    'image_url' => $imageUrl ?: '',
                ],
            ],
        ];
        $jsonMessage = json_encode($messageData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage);

        $resultCurl = curl_exec($ch);
        if ($resultCurl === false) {
            $error = curl_error($ch);
            file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка CURL для $login: $error\n", FILE_APPEND);
            $errorCount++;
        } else {
            $response = json_decode($resultCurl, true);
            if (isset($response['name'])) {
                file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Успешно отправлено для $login: " . $response['name'] . "\n", FILE_APPEND);
                $successCount++;
            } else {
                file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка для $login: " . ($response['error']['message'] ?? 'Неизвестная ошибка') . "\n", FILE_APPEND);
                $errorCount++;
            }
        }
        curl_close($ch);
    }

    return [
        'success' => $successCount,
        'error' => $errorCount
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['region'])) {
    $title = trim($_POST['title'] ?? 'No Title');
    $body = trim($_POST['body'] ?? 'No Body');
    $messageId = trim($_POST['message_id'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $selectedUsers = $_POST['users'] ?? [];

    if (empty($title) || empty($body) || empty($messageId)) {
        $message = "Пожалуйста, заполните все обязательные поля!";
    } elseif ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $message = "Некорректный URL картинки!";
    } elseif (empty($selectedUsers)) {
        $message = "Выберите хотя бы одного пользователя!";
    } else {
        try {
            $result = sendNotification($selectedUsers, $title, $body, $messageId, $imageUrl);
            $message = "Уведомление отправлено: успешно для {$result['success']} пользователей, с ошибками для {$result['error']}";
        } catch (Exception $e) {
            $message = "Ошибка: " . $e->getMessage();
            file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " Ошибка: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

$regions = getRegions();

$templates = [
    1 => [
        'title' => '🔧 Профилактические работы 🔧',
        'body' => "Уважаемые абоненты!\nСегодня на улице Киевская проводятся плановые профилактические работы. Возможны временные перебои в работе интернета и других сервисов.\n⏳ Ориентировочное время завершения работ: до 17:00.\nПриносим извинения за возможные неудобства. Спасибо за понимание!\n📞 Если у вас возникли вопросы, свяжитесь с нашей технической поддержкой.",
    ],
    2 => [
        'title' => '🎉 Акция! Скорость выше – цена та же! 🎉',
        'body' => "Друзья, отличная новость! Мы увеличили скорость на популярных тарифах без изменения стоимости!\n✅ Проверьте ваш тариф и наслаждайтесь более быстрым интернетом!\n🏆 Акция действует до [дата окончания акции].\nУспейте подключиться! Подробности – в личном кабинете или по телефону службы поддержки.",
    ],
    3 => [
        'title' => '⚠ Необходимо оплатить тариф ⚠',
        'body' => "Уважаемый абонент, срок действия вашего тарифа истекает!\nДля бесперебойного доступа к интернету, пожалуйста, пополните баланс. Сделать это можно:\n💳 В личном кабинете\n🏦 Через банковские терминалы\n📱 В мобильном приложении\n💡 Не забудьте, что при своевременной оплате сохраняются все ваши бонусы и скидки!",
    ],
    4 => [
        'title' => '🚀 Новинка! IPTV с 100+ каналами 📺',
        'body' => "Теперь у нас доступен новый сервис IPTV с более чем 100 телеканалами в отличном качестве!\n🎬 Фильмы, спорт, детские передачи – все в одном месте!\n🆓 Пробный период – 7 дней бесплатно!\nПодключайте IPTV прямо сейчас в личном кабинете!\n📞 Остались вопросы? Наши специалисты помогут вам в любое время!",
    ],
];

$popularImages = [
    'https://ardmoney.ru/img/cat.gif' => 'Котик',
    'https://ardmoney.ru/img/maintenance.png' => 'Ремонт',
    'https://ardmoney.ru/img/promo.png' => 'Акция',
    'https://ardmoney.ru/img/warning.png' => 'Предупреждение',
];
?>

<head>
    <title>ALLERT ПАНЕЛЬ</title>
    <style>
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        select { 
            width: 300px; 
            padding: 6px; 
            box-sizing: border-box; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); 
        }
        #region { height: 40px; }
        #users { height: 100px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .message { margin-top: 15px; padding: 10px; border-radius: 5px; }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
        .template-btn { margin: 5px; padding: 8px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .template-btn:hover { background-color: #0056b3; }
        .image-option { margin: 5px; display: inline-block; cursor: pointer; }
        .image-option img { width: 50px; height: 50px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#region').change(function() {
                var region = $(this).val();
                console.log('Выбран регион: ' + region);
                $.ajax({
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'GET',
                    data: { region: region },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Полученные данные: ', data);
                        var $users = $('#users');
                        $users.empty();
                        if (data.length === 0) {
                            $users.append($('<option>', { text: 'Нет пользователей для этого региона' }));
                        } else {
                            $.each(data, function(index, user) {
                                $users.append($('<option>', {
                                    value: user.login,
                                    text: user.display
                                }));
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX: ' + error);
                        console.log('Статус: ' + xhr.status);
                        console.log('Ответ сервера: ' + xhr.responseText);
                    }
                });
            });

            $('.template-btn').click(function() {
                var id = $(this).data('id');
                $('#title').val(templates[id].title);
                $('#body').val(templates[id].body);
                $('#message_id').val(id);
            });

            $('.image-option').click(function() {
                var url = $(this).data('url');
                $('#image_url').val(url);
            });
        });

        var templates = <?php echo json_encode($templates); ?>;
    </script>
</head>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="padding: 0;">
    <div class="container-fluid" style="background: #00000070;">
        <a class="navbar-brand" href="#"></a>
        <div class="navbar-collapse" id="navbarNavDarkDropdown">
            <ul class="navbar-nav rut_nav">
                <?php if (!empty(htmlentities($_COOKIE['user']))): ?>
                    <ul style="float: right;">
                        <li>
                            <a href="user.php">
                                <img src="/img/home.png" style="width: 40px; padding-bottom: 7px;">
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <h1>ALLERT ПАНЕЛЬ</h1>
    <form method="POST">
        <div class="form-group">
            <label for="region">Выберите регион:</label>
            <select id="region" name="region">
                <option value="">-- Выберите регион --</option>
                <?php foreach ($regions as $region): ?>
                    <option value="<?php echo htmlspecialchars($region); ?>">
                        <?php echo htmlspecialchars($region); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="users">Выберите пользователей:</label>
            <select id="users" name="users[]" multiple required>
                <option value="">-- Выберите регион выше --</option>
            </select>
        </div>
        <div class="form-group">
            <label>Шаблоны уведомлений:</label>
            <?php foreach ($templates as $id => $template): ?>
                <button type="button" class="template-btn" data-id="<?php echo $id; ?>">
                    <?php echo htmlspecialchars($template['title']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="form-group">
            <label for="title">Заголовок:</label>
            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="body">Текст:</label>
            <textarea id="body" name="body" rows="6" required><?php echo htmlspecialchars($body ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="message_id">ID сообщения (для ссылки):</label>
            <input type="text" id="message_id" name="message_id" required placeholder="например, 1" value="<?php echo htmlspecialchars($messageId ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="image_url">URL картинки (опционально):</label>
            <input type="url" id="image_url" name="image_url" placeholder="например, https://example.com/image.png" value="<?php echo htmlspecialchars($imageUrl ?? ''); ?>">
            <div>
                <?php foreach ($popularImages as $url => $name): ?>
                    <div class="image-option" data-url="<?php echo htmlspecialchars($url); ?>">
                        <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($name); ?>">
                        <span><?php echo htmlspecialchars($name); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit">Отправить</button>
    </form>

    <?php if (isset($message)): ?>
        <div class="message <?php echo strpos($message, 'успешно') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
</div>

<?php
include 'inc/foot.php';
?>