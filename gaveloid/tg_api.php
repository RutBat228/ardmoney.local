<?php
// tg_api.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
$servername = "localhost";
$username = "gaveloid";
$password = "64ihufoz";
$dbname = "gaveloid";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - DB Connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    exit;
}
$conn->set_charset("utf8mb4");

// Логирование
$input = file_get_contents("php://input");
file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - Input: " . $input . "\n", FILE_APPEND);

$update = json_decode($input, true);
$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
$message = $update['message']['text'] ?? '';
$message_id = $update['message']['message_id'] ?? $update['callback_query']['message']['message_id'] ?? null;
$callback_data = $update['callback_query']['data'] ?? '';

define('BOT_TOKEN', '7602474226:AAGhXrVMg2QxiPI3ey5wJflceR5_BTvJGPY');

// Функция отправки или редактирования сообщения
function sendOrEditMessage($chat_id, $text, $reply_markup = null, $message_id_to_edit = null) {
    $method = $message_id_to_edit ? "editMessageText" : "sendMessage";
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/$method";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    if ($message_id_to_edit) {
        $data['message_id'] = $message_id_to_edit;
    }
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - Sent/Edited: $text" . ($error ? " | Error: $error" : "") . "\n", FILE_APPEND);
    return json_decode($result, true);
}

// Функция удаления сообщения
function deleteMessage($chat_id, $message_id) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/deleteMessage";
    $data = ['chat_id' => $chat_id, 'message_id' => $message_id];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// Главное меню (2 кнопки в ряд)
$main_menu = [
    'inline_keyboard' => [
        [
            ['text' => '🚗 Добавить пробег', 'callback_data' => 'add_mileage'],
            ['text' => '📊 Статистика', 'callback_data' => 'stats'],
        ],
        [
            ['text' => '📜 История', 'callback_data' => 'history_1'],
            ['text' => '🛢 Замена масла', 'callback_data' => 'oil_change'],
        ],
        [
            ['text' => '🗑 Удалить запись', 'callback_data' => 'delete_entry'],
            ['text' => 'ℹ О боте', 'callback_data' => 'about'],
        ],
    ]
];

// Кнопка возврата в главное меню
$back_to_main = [
    'inline_keyboard' => [
        [['text' => '🏠 На главную', 'callback_data' => 'main_menu']],
    ]
];

// Проверка chat_id
if (!$chat_id) {
    file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - No chat_id found\n", FILE_APPEND);
    exit;
}

// Получение текущего пробега
$sql_last = "SELECT displayed_mileage FROM mileage ORDER BY id DESC LIMIT 1";
$result_last = $conn->query($sql_last);
$last_data = $result_last ? $result_last->fetch_assoc() : null;
$current_mileage = $last_data ? $last_data['displayed_mileage'] : 0;

// Обработка команд и callback
if ($message == '/start') {
    // Если это первый запуск, просто отправляем сообщение
    sendOrEditMessage($chat_id, "✨ <b>Gaveloid Bot</b> ✨\nТвой помощник для учёта пробега и масла!\n\nВыбери действие:", $main_menu);
} elseif ($callback_data) {
    file_put_contents('telegram_log.txt', date('Y-m-d H:i:s') . " - Callback Data: '$callback_data'\n", FILE_APPEND);

    switch ($callback_data) {
        case 'add_mileage':
            sendOrEditMessage($chat_id, "📏 <b>Добавить пробег</b>\nВведи в формате: \"до\" \"после\"\nПример: <code>230000 230150</code>\n\n📌 Подсказка: Первое число — это когда ты на офис приехал, второе — когда идёшь к долбоёбу Косте.", $back_to_main, $message_id);
            break;

        case 'stats':
            $total_wrap = $conn->query("SELECT SUM(wrap_distance) AS total_wrap FROM mileage")->fetch_assoc()['total_wrap'] ?? 0;
            $real_mileage = $current_mileage - $total_wrap;

            $week_data = $conn->query("SELECT SUM(wrap_distance) AS week FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc();
            $month_data = $conn->query("SELECT SUM(wrap_distance) AS month FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc();
            $year_data = $conn->query("SELECT SUM(wrap_distance) AS year FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)")->fetch_assoc();

            $text = "📊 <b>Статистика</b> 📊\n";
            $text .= "🚗 Реальный пробег: <b>" . number_format($real_mileage, 0, ',', ' ') . " км</b>\n";
            $text .= "🔧 Намотка всего: <b>" . number_format($total_wrap, 0, ',', ' ') . " км</b>\n";
            $text .= "📅 За 7 дней: <b>" . number_format($week_data['week'] ?? 0, 0, ',', ' ') . " км</b>\n";
            $text .= "📅 За 30 дней: <b>" . number_format($month_data['month'] ?? 0, 0, ',', ' ') . " км</b>\n";
            $text .= "📅 За год: <b>" . number_format($year_data['year'] ?? 0, 0, ',', ' ') . " км</b>";
            sendOrEditMessage($chat_id, $text, $main_menu, $message_id);
            break;

        case (preg_match('/^history_(\d+)$/', $callback_data, $matches) ? $callback_data : ''):
            $page = (int)($matches[1] ?? 1);
            $limit = 5;
            $offset = ($page - 1) * $limit;
            $sql_history = "SELECT id, actual_mileage, displayed_mileage, wrap_distance, DATE_FORMAT(mileage_added_at, '%d.%m.%Y') as formatted_date FROM mileage ORDER BY mileage_added_at DESC LIMIT $limit OFFSET $offset";
            $result_history = $conn->query($sql_history);
            $total_records = $conn->query("SELECT COUNT(*) as total FROM mileage")->fetch_assoc()['total'];
            $total_pages = ceil($total_records / $limit);

            $text = "📜 <b>История</b> (стр. $page/$total_pages)\n";
            if ($result_history && $result_history->num_rows > 0) {
                while ($row = $result_history->fetch_assoc()) {
                    $text .= sprintf("📅 %s | ID: <b>%d</b>\n🚗 %s км → %s км (+%s км)\n",
                        $row['formatted_date'], $row['id'],
                        number_format($row['actual_mileage'], 0, ',', ' '),
                        number_format($row['displayed_mileage'], 0, ',', ' '),
                        number_format($row['wrap_distance'], 0, ',', ' '));
                }
            } else {
                $text .= "⚠ История пуста.";
            }

            $nav_buttons = [];
            if ($page > 1) $nav_buttons[] = ['text' => '⬅ Назад', 'callback_data' => "history_" . ($page - 1)];
            if ($page < $total_pages) $nav_buttons[] = ['text' => 'Вперёд ➡', 'callback_data' => "history_" . ($page + 1)];
            $history_menu = [
                'inline_keyboard' => [
                    $nav_buttons,
                    [['text' => '🏠 На главную', 'callback_data' => 'main_menu']],
                ]
            ];
            sendOrEditMessage($chat_id, $text, $history_menu, $message_id);
            break;

        case 'oil_change':
            $oil_data = $conn->query("SELECT odometer_at_change, DATE_FORMAT(date_of_change, '%d.%m.%Y') as formatted_date FROM oil_changes ORDER BY odometer_at_change DESC LIMIT 1")->fetch_assoc();
            $last_oil_change = $oil_data ? $oil_data['odometer_at_change'] : 0;
            $last_oil_date = $oil_data ? $oil_data['formatted_date'] : 'Никогда';
            $distance_since_oil = $current_mileage - $last_oil_change;
            $distance_until_oil = 7000 - $distance_since_oil;

            $progress = min(10, max(0, floor(($distance_since_oil / 7000) * 10)));
            $progress_percent = floor(($distance_since_oil / 7000) * 100);

            $text = "🛢 <b>Замена масла</b> 🛢\n";
            $text .= "⏳ Последняя: <b>" . number_format($last_oil_change, 0, ',', ' ') . " км</b> ($last_oil_date)\n";
            $text .= "🚗 Текущий пробег: <b>" . number_format($current_mileage, 0, ',', ' ') . " км</b>\n";
            $text .= "⏰ До замены: <b>" . ($distance_until_oil <= 0 ? "Срочно!" : number_format($distance_until_oil, 0, ',', ' ') . " км") . "</b>\n";
            $text .= "📈 Прогресс: " . str_repeat("█", $progress) . str_repeat("░", 10 - $progress) . " ($progress_percent%)";
            sendOrEditMessage($chat_id, $text, [
                'inline_keyboard' => [
                    [['text' => '🔧 Зафиксировать замену', 'callback_data' => 'submit_oil']],
                    [['text' => '🏠 На главную', 'callback_data' => 'main_menu']],
                ]
            ], $message_id);
            break;

        case 'submit_oil':
            sendOrEditMessage($chat_id, "🔧 <b>Фиксация замены масла</b>\nВведи текущий пробег (только число):\nПример: <code>230150</code>", $back_to_main, $message_id);
            break;

        case 'delete_entry':
            $sql_last_three = "SELECT id, actual_mileage, displayed_mileage, DATE_FORMAT(mileage_added_at, '%d.%m.%Y') as formatted_date FROM mileage ORDER BY id DESC LIMIT 3";
            $result_last_three = $conn->query($sql_last_three);

            $text = "🗑 <b>Удалить запись</b>\nВот последние 3 записи:\n";
            if ($result_last_three && $result_last_three->num_rows > 0) {
                while ($row = $result_last_three->fetch_assoc()) {
                    $text .= sprintf("ID: <b>%d</b> | %s | %s км → %s км\n",
                        $row['id'], $row['formatted_date'],
                        number_format($row['actual_mileage'], 0, ',', ' '),
                        number_format($row['displayed_mileage'], 0, ',', ' '));
                }
                $text .= "\nВведи ID записи для удаления:\nПример: <code>5</code>";
            } else {
                $text .= "⚠ Нет записей для удаления.";
            }
            sendOrEditMessage($chat_id, $text, $back_to_main, $message_id);
            break;

        case 'about':
            $text = "ℹ <b>О боте</b>\n";
            $text .= "Я — Gaveloid Bot, создан для учёта пробега и замены масла.\n";
            $text .= "📅 Дата: " . date('d.m.Y') . "\n";
            $text .= "👨‍💻 Разработчик: xAI\n";
            $text .= "💡 Используй кнопки для управления!";
            sendOrEditMessage($chat_id, $text, $main_menu, $message_id);
            break;

        case 'main_menu':
            sendOrEditMessage($chat_id, "✨ <b>Gaveloid Bot</b> ✨\nВыбери действие:", $main_menu, $message_id);
            break;

        default:
            sendOrEditMessage($chat_id, "❓ Неизвестная команда: '$callback_data'", $main_menu, $message_id);
            break;
    }
} elseif (preg_match('/^(\d+)\s+(\d+)$/', $message, $matches)) {
    deleteMessage($chat_id, $message_id);
    $actual_mileage = (int)$matches[1];
    $displayed_mileage = (int)$matches[2];
    $wrap_distance = $displayed_mileage - $actual_mileage;
    $distance_driven = $actual_mileage;

    if ($wrap_distance < 0) {
        sendOrEditMessage($chat_id, "❌ Ошибка: 'После' должно быть больше 'До'.\nПопробуй снова.", $main_menu, $message_id);
    } else {
        $sql = "INSERT INTO mileage (actual_mileage, displayed_mileage, mileage_added_at, distance_driven, wrap_distance) VALUES (?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiii", $actual_mileage, $displayed_mileage, $distance_driven, $wrap_distance);
            if ($stmt->execute()) {
                $text = "✅ <b>Пробег добавлен</b>\n";
                $text .= "🚗 До: <b>" . number_format($actual_mileage, 0, ',', ' ') . " км</b>\n";
                $text .= "📏 После: <b>" . number_format($displayed_mileage, 0, ',', ' ') . " км</b>";
                sendOrEditMessage($chat_id, $text, $main_menu, $message_id);
            } else {
                sendOrEditMessage($chat_id, "❌ Ошибка базы данных: " . $stmt->error, $main_menu, $message_id);
            }
            $stmt->close();
        } else {
            sendOrEditMessage($chat_id, "❌ Ошибка подготовки запроса.", $main_menu, $message_id);
        }
    }
} elseif (preg_match('/^\d+$/', $message, $matches)) {
    deleteMessage($chat_id, $message_id);
    $value = (int)$matches[0];

    // Фиксация замены масла
    if (isset($update['message']['reply_to_message']['text']) && strpos($update['message']['reply_to_message']['text'], "Фиксация замены масла") !== false) {
        $sql = "INSERT INTO oil_changes (odometer_at_change, date_of_change) VALUES (?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $value);
            if ($stmt->execute()) {
                sendOrEditMessage($chat_id, "✅ <b>Масло зафиксировано</b>\n🛢 Пробег: <b>" . number_format($value, 0, ',', ' ') . " км</b>", $main_menu, $message_id);
            } else {
                sendOrEditMessage($chat_id, "❌ Ошибка: " . $stmt->error, $main_menu, $message_id);
            }
            $stmt->close();
        }
    // Удаление записи
    } elseif (isset($update['message']['reply_to_message']['text']) && strpos($update['message']['reply_to_message']['text'], "Удалить запись") !== false) {
        $sql = "DELETE FROM mileage WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $value);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                sendOrEditMessage($chat_id, "🗑 <b>Запись ID $value удалена</b>", $main_menu, $message_id);
            } else {
                sendOrEditMessage($chat_id, "❌ Запись с ID $value не найдена или ошибка: " . $stmt->error, $main_menu, $message_id);
            }
            $stmt->close();
        }
    } else {
        sendOrEditMessage($chat_id, "❓ Введи число в ответ на запрос бота.", $main_menu, $message_id);
    }
} else {
    sendOrEditMessage($chat_id, "❓ Не понимаю. Используй кнопки или введи данные в нужном формате.", $main_menu, $message_id);
}

$conn->close();
?>