<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Константы для подключения к базе данных
const HOST = "localhost";
const USER = "ardmoney";
const BAZA = "ardmoney";
const PASS = "64ihufoz";
const TABLE_PREFIX = "navigard_";

// Токен вашего бота
define('BOT_TOKEN', '7740028419:AAGuY8rxAt_kmQbP6w2qBPI2wqXbdo3bNds');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Логирование входящих запросов
$input = file_get_contents('php://input');
file_put_contents(__DIR__ . '/telegram_log.txt', date('[Y-m-d H:i:s] ') . "Input: " . $input . "\n", FILE_APPEND);

// Создаем подключение к базе данных
global $connect;
$connect = new mysqli(HOST, USER, PASS, BAZA);
if ($connect->connect_error) {
    file_put_contents(__DIR__ . '/telegram_log.txt', date('[Y-m-d H:i:s] ') . "DB Connection failed: " . $connect->connect_error . "\n", FILE_APPEND);
    http_response_code(200);
    exit;
}
$connect->query("SET NAMES 'utf8'");

// Функция для отправки ответа в Telegram
function sendResponse($method, $data) {
    $url = API_URL . $method;
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data),
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    file_put_contents(__DIR__ . '/telegram_log.txt', date('[Y-m-d H:i:s] ') . "Sent response to $method: " . json_encode($data) . "\n", FILE_APPEND);
    return $result;
}

// Функция для экранирования текста для Telegram (Markdown)
function sanitize($input) {
    $input = trim($input ?? '');
    return str_replace(['*', '_', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'], 
                       ['\*', '\_', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'], 
                       $input);
}

// Обработка входящего запроса от Telegram
$update = json_decode($input, true);
if (!$update) {
    file_put_contents(__DIR__ . '/telegram_log.txt', date('[Y-m-d H:i:s] ') . "Failed to decode JSON: " . json_last_error_msg() . "\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// Обработка сообщений
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = trim($update['message']['text']);

    if ($text === '/start') {
        sendResponse('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "Привет! Я бот для поиска домов.\nВведите адрес (например, Гагарина), чтобы найти дома.",
            'parse_mode' => 'Markdown'
        ]);
    } elseif (strlen($text) >= 2) {
        // Поиск домов
        $query = "SELECT id, adress, complete, new, region FROM " . TABLE_PREFIX . "adress 
                  WHERE adress LIKE '%$text%' 
                  ORDER BY CASE WHEN adress LIKE '$text%' THEN 0 ELSE 1 END, adress 
                  LIMIT 10";
        $result = $connect->query($query);
        if (!$result) {
            file_put_contents(__DIR__ . '/telegram_log.txt', date('[Y-m-d H:i:s] ') . "Query failed: " . $connect->error . "\n", FILE_APPEND);
            sendResponse('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Ошибка при поиске. Попробуйте позже.",
            ]);
            http_response_code(200);
            exit;
        }

        if ($result->num_rows > 0) {
            $keyboard = ['inline_keyboard' => []];
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $address = sanitize($row['adress']);
                $complete = $row['complete'] == 1 ? "✅" : "🏠";
                $is_new = $row['new'] == 1 ? " [NEW]" : "";
                $button_text = "$complete $address$is_new";

                $keyboard['inline_keyboard'][] = [
                    ['text' => $button_text, 'callback_data' => "house_$id"]
                ];
            }

            sendResponse('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Найдено домов: {$result->num_rows}\nВыберите дом для подробной информации:",
                'reply_markup' => $keyboard
            ]);
        } else {
            sendResponse('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Совпадений не найдено для \"$text\".\nПопробуйте другой адрес или добавьте новый дом через сайт."
            ]);
        }
    } else {
        sendResponse('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "Введите минимум 2 символа для поиска."
        ]);
    }
}

// Обработка нажатия кнопок (callback query)
if (isset($update['callback_query'])) {
    $callback_id = $update['callback_query']['id'];
    $chat_id = $update['callback_query']['message']['chat']['id'];
    $data = $update['callback_query']['data'];

    if (strpos($data, 'house_') === 0) {
        $house_id = (int)str_replace('house_', '', $data);

        // Получаем подробную информацию о доме
        $query = "SELECT * FROM " . TABLE_PREFIX . "adress WHERE id = $house_id LIMIT 1";
        $result = $connect->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $address = sanitize($row['adress']);
            $complete = $row['complete'] == 1 ? "✅ Завершён" : "🏠 В процессе";
            $is_new = $row['new'] == 1 ? " [NEW]" : "";
            $oboryda = sanitize($row['oboryda'] ?? 'Не указано');
            $vihod_display = array_filter([$row['vihod'] ?? '', $row['vihod2'] ?? '', $row['vihod3'] ?? '', $row['vihod4'] ?? '', $row['vihod5'] ?? '']);
            $vihod_text = implode(', ', array_map('sanitize', $vihod_display));
            $podjezd = sanitize($row['podjezd'] ?? 'Не указано');
            $krisha = sanitize($row['krisha'] ?? 'Не указано');
            $kluch = sanitize($row['kluch'] ?? 'Не указано');
            $lesnica = sanitize($row['lesnica'] ?? 'Не указано');
            $dopzamok = sanitize($row['dopzamok'] ?? 'Не указано');
            $pitanie = sanitize($row['pitanie'] ?? 'Не указано');
            $link = sanitize($row['link'] ?? 'Не указано');
            $pon = sanitize($row['pon'] ?? 'Не указано');
            $region = sanitize($row['region'] ?? 'Не указано');
            $pred = sanitize($row['pred'] ?? 'Не указано');
            $phone = sanitize($row['phone'] ?? 'Не указано');
            $text = $row['text'] ?? '';

            // Формируем ответ в стиле вашего примера
            $response_text = "*$address*$is_new\nСтатус: $complete\n\n" .
                            "*Местоположение и оборудование*\n" .
                            "📍 *Адрес:* $address\n" .
                            "🛠 *Размещение оборудования:* $oboryda\n";

            if (in_array($oboryda, ["Чердак", "Подвал", "Подъезд", "Не указанно"])) {
                $response_text .= "🏢 *Количество подъездов:* $podjezd\n";
                if ($vihod_text) {
                    $vihod_label = $oboryda == "Подвал" ? "Подъезд с подвалом" : 
                                 ($oboryda == "Подъезд" ? "Подъезд с оборудованием" : "Подъезд с выходом");
                    $response_text .= "🚪 *$vihod_label:* $vihod_text\n";
                }
            }

            if ($oboryda == "Чердак" || $oboryda == "Не указанно") {
                $response_text .= "\n*Информация о чердаке*\n" .
                                 "🏠 *Тип крыши:* $krisha\n" .
                                 "🔑 *Расположение ключа:* $kluch\n" .
                                 "🪜 *Наличие лестницы:* $lesnica\n" .
                                 "🔒 *Дополнительный замок:* $dopzamok\n";
            } elseif ($oboryda == "Подвал") {
                $response_text .= "\n*Информация о подвале*\n" .
                                 "🔑 *Расположение ключа:* $kluch\n" .
                                 "🔒 *Дополнительный замок:* $dopzamok\n";
            } elseif ($oboryda == "Подъезд") {
                $response_text .= "\n*Информация о подъезде*\n" .
                                 "🔒 *Дополнительный замок:* $dopzamok\n";
            } elseif ($oboryda == "Фасад") {
                $response_text .= "\n*Информация о фасаде*\n" .
                                 "🔌 *Источник питания:* $pitanie\n" .
                                 "🔗 *Источник линка:* $link\n";
            }

            $response_text .= "\n*Технические данные*\n" .
                             "🌍 *Регион:* $region\n" .
                             "🌐 *Тип подключения:* $pon\n" .
                             "\n*Контактная информация*\n" .
                             "👤 *Председатель:* $pred\n" .
                             "📞 *Телефон:* [$phone](tel:$phone)\n";

            if (!empty($text)) {
                $response_text .= "\n*Заметки*\n";
                $notes = explode("\n", $text);
                foreach ($notes as $note) {
                    $note = trim($note);
                    if (empty($note)) continue;

                    if (preg_match('/\[DATE\](.*?)\[\/DATE\]\[AUTHOR\](.*?)\[\/AUTHOR\]\[TEXT\](.*?)\[\/TEXT\]/', $note, $matches)) {
                        $date = sanitize($matches[1]);
                        $author = sanitize($matches[2]);
                        $note_text = sanitize($matches[3]);
                        $response_text .= "📅 *$date* | 👤 *$author*\n$note_text\n";
                    } elseif (preg_match('/\[(\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2})\] (.*?)(?:<br>|$)/', $note, $matches)) {
                        $date = sanitize($matches[1]);
                        $author_note = sanitize($matches[2]);
                        if (preg_match('/^(.*?) (.*)$/', $author_note, $author_matches)) {
                            $author = $author_matches[1];
                            $note_text = $author_matches[2];
                            $response_text .= "📅 *$date* | 👤 *$author*\n$note_text\n";
                        } else {
                            $response_text .= "$author_note\n";
                        }
                    } else {
                        $response_text .= "$note\n";
                    }
                }
            }

            $response_text .= "\n🔗 [Открыть на сайте](https://ardmoney.ru/navigard/result.php?adress_id=$house_id)";

            sendResponse('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $response_text,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true
            ]);
        } else {
            sendResponse('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "Дом с ID $house_id не найден."
            ]);
        }

        // Подтверждаем обработку callback
        sendResponse('answerCallbackQuery', [
            'callback_query_id' => $callback_id
        ]);
    }
}

$connect->close();
http_response_code(200);
?>