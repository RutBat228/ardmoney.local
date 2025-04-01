<?php
include(__DIR__ . '/../inc/function.php');
AutorizeProtect();
global $connect, $usr;

// Проверяем, есть ли уже какой-то вывод перед отправкой заголовков
if (ob_get_length()) {
    ob_clean(); // Очищаем буфер вывода
}

// Всегда устанавливаем заголовок типа содержимого как JSON для всех запросов
header('Content-Type: application/json');

// Класс для обработки операций с домами
class HouseProcessor {
    private $connect;
    private $user;
    
    public function __construct($connect, $user) {
        $this->connect = $connect;
        $this->user = $user;
    }
    
    // Проверка является ли запрос AJAX-запросом
    private function isAjaxRequest() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    // Метод для обработки запросов
    public function processRequest() {
        // Проверяем метод запроса
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(false, 'Метод запроса должен быть POST');
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'delete':
                return $this->deleteHouse();
            case 'edit':
                return $this->editHouse();
            default:
                return $this->jsonResponse(false, 'Неизвестное действие');
        }
    }
    
    // Вспомогательный метод для формирования JSON-ответа
    private function jsonResponse($success, $message = '', $data = null) {
        $response = ['success' => $success];
        
        if (!$success && $message) {
            $response['error'] = $message;
        } elseif ($success && $message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit; // Важно: прекращаем выполнение скрипта после отправки JSON
    }
    
    // Обработка запроса на удаление дома
    private function deleteHouse() {
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            return $this->jsonResponse(false, 'Некорректный ID');
        }
        
        $id = intval($_POST['id']);
        $adress = $_POST['adress'] ?? '';
        
        // Проверка прав доступа
        $checkSql = "SELECT region FROM navigard_adress WHERE id = $id LIMIT 1";
        $checkResult = $this->connect->query($checkSql);
        
        if ($checkResult->num_rows === 0) {
            return $this->jsonResponse(false, 'Дом не найден');
        }
        
        $row = $checkResult->fetch_object();
        
        if ($this->user['admin'] != '1' && $this->user['region'] != $row->region) {
            return $this->jsonResponse(false, 'Недостаточно прав для удаления');
        }
        
        // Выполнение удаления
        $deleteSql = "DELETE FROM navigard_adress WHERE id = $id LIMIT 1";
        if ($this->connect->query($deleteSql)) {
            // Логирование операции удаления
            $this->logAction("Удален дом с ID: $id, адрес: $adress");
            return $this->jsonResponse(true, 'Дом успешно удален');
        } else {
            return $this->jsonResponse(false, 'Ошибка при удалении: ' . $this->connect->error);
        }
    }
    
    // Обработка запроса на редактирование дома
    private function editHouse() {
        // Получаем основные параметры из POST
        $id = isset($_POST['id']) ? $this->sanitize($_POST['id']) : '';
        if (!$id) {
            return $this->jsonResponse(false, 'ID не указан');
        }
        
        $adress = $this->sanitize($_POST['adress'] ?? '');
        $check = $this->sanitize($_POST['check'] ?? 0);
        $text = $_POST['text'] ?? ''; // Не применяем sanitize к text, чтобы сохранить HTML
        
        // Получаем текущие данные дома
        $stmt = $this->connect->prepare("SELECT * FROM navigard_adress WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $this_house = $result->num_rows == 1 ? $result->fetch_assoc() : [];
        $stmt->close();
        
        if (!$this_house) {
            return $this->jsonResponse(false, 'Дом не найден');
        }
        
        // Подготовка данных
        $data = [];
        $data['complete'] = empty($check) ? 0 : $check;
        $fields = ['dopzamok', 'kluch', 'krisha', 'link', 'pitanie', 'podjezd', 'pon', 'oboryda', 'lesnica', 'pred', 'phone', 'region'];
        foreach ($fields as $field) {
            $data[$field] = $this->sanitize($_POST[$field] ?? $this_house[$field] ?? '');
        }
        
        // Обработка массива vihod
        $vihods = isset($_POST['vihod']) && is_array($_POST['vihod']) ? array_map([$this, 'sanitize'], $_POST['vihod']) : [];
        $vihods = array_pad($vihods, 5, '');
        $data['vihod'] = $vihods[0] ?? '';
        $data['vihod2'] = $vihods[1] ?? '';
        $data['vihod3'] = $vihods[2] ?? '';
        $data['vihod4'] = $vihods[3] ?? '';
        $data['vihod5'] = $vihods[4] ?? '';
        
        // Логи изменений
        $log_items = [];
        if ($adress !== ($this_house['adress'] ?? '')) $log_items[] = "Смена адреса дома";
        if ($data['complete'] != ($this_house['complete'] ?? '')) $log_items[] = "Смена статуса завершенности дома";
        foreach ($fields as $field) {
            if ($field === 'phone') {
                $log_message = "Смена номера телефона председателя";
            } elseif ($field === 'pred') {
                $log_message = "Смена информации о председателе";
            } else {
                $log_message = "Смена статуса " . ($field === 'oboryda' ? 'размещения оборудования' : $field);
            }
            if ($data[$field] != ($this_house[$field] ?? '')) $log_items[] = $log_message;
        }
        if ($text) $log_items[] = "Добавлено новое примечание";
        $new_status_home = $log_items ? "<br>" . implode("<br>", $log_items) : '';
        
        // Формирование текста и лога
        $date = date("d.m.Y");
        $fio = $this->user['fio'];
        $new_text = $text ? "[DATE]" . $date . "[/DATE][AUTHOR]" . $fio . "[/AUTHOR][TEXT]" . $text . "[/TEXT]\n" . ($this_house['text'] ?? '') : ($this_house['text'] ?? '');
        
        // Ограничение длины текста
        $max_text_length = 65535;
        if (strlen($new_text) > $max_text_length) {
            $new_text = substr($new_text, 0, $max_text_length);
        }
        
        $log = "$date $fio отредактировал дом - $adress $new_status_home<br>" . ($this_house['history'] ?? '');
        
        // Валидация
        if (empty($adress)) {
            return $this->jsonResponse(false, 'Введите адрес дома');
        }
        
        // Запись в лог
        $this->logAction($log);
        
        // Обновление данных
        $stmt = $this->connect->prepare(
            "UPDATE navigard_adress SET adress=?, vihod=?, vihod2=?, vihod3=?, vihod4=?, vihod5=?, " .
            "dopzamok=?, kluch=?, krisha=?, link=?, pitanie=?, podjezd=?, pon=?, oboryda=?, lesnica=?, " .
            "pred=?, phone=?, region=?, complete=?, text=?, editor=?, new=?, history=? WHERE id=?"
        );
        $new = 0;
        $stmt->bind_param(
            "ssssssssssssssssssisssii",
            $adress,
            $data['vihod'], $data['vihod2'], $data['vihod3'], $data['vihod4'], $data['vihod5'],
            $data['dopzamok'], $data['kluch'], $data['krisha'], $data['link'], $data['pitanie'],
            $data['podjezd'], $data['pon'], $data['oboryda'], $data['lesnica'], $data['pred'],
            $data['phone'], $data['region'], $data['complete'], $new_text, $fio, $new, $log, $id
        );
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'data' => array_merge(
                    $data,
                    [
                        'adress' => $adress,
                        'text' => $new_text,
                        'vihod' => $data['vihod'],
                        'vihod2' => $data['vihod2'],
                        'vihod3' => $data['vihod3'],
                        'vihod4' => $data['vihod4'],
                        'vihod5' => $data['vihod5'],
                        'complete' => $data['complete']
                    ]
                )
            ];
            return $this->jsonResponse(true, 'Дом успешно обновлен', $response);
        } else {
            return $this->jsonResponse(false, 'Ошибка обновления: ' . $this->connect->error);
        }
    }
    
    // Метод для санитизации данных
    private function sanitize($value) {
        return htmlspecialchars(trim($value !== null ? $value : ''), ENT_QUOTES, 'UTF-8');
    }
    
    // Логирование действий
    private function logAction($message) {
        $date = date("d.m.Y H:i:s");
        $stmt = $this->connect->prepare("INSERT INTO navigard_log (kogda, log) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// Инициализация и обработка запроса
$processor = new HouseProcessor($connect, $usr);
$result = $processor->processRequest();
?> 