<?php
include(__DIR__ . '/../inc/head.php');

// Проверяем, является ли запрос AJAX-запросом
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
         (isset($_SERVER['HTTP_ACCEPT']) && 
          strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Если запрос AJAX, устанавливаем заголовок Content-Type: application/json
if ($isAjax) {
    header('Content-Type: application/json');
}
?>
<!-- Font Awesome 6.2.1 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
<!-- Внешний CSS файл вместо встроенных стилей -->
<link rel="stylesheet" href="css/result.css">
<?php
AutorizeProtect();
global $connect, $usr;

class HouseResult {
    private $connect;
    private $user;
    private $isAjax;
    
    public function __construct($connect, $user, $isAjax = false) {
        $this->connect = $connect;
        $this->user = $user;
        $this->isAjax = $isAjax;
    }
    
    private function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function redirect($url) {
        header("Location: $url");
        exit();
    }
    
    // Обработка AJAX запросов
    public function handleAjaxRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            // Проверяем, есть ли уже какой-то вывод перед отправкой заголовков
            if (ob_get_length()) {
                ob_clean(); // Очищаем буфер вывода
            }
            
            // Устанавливаем заголовок для JSON-ответа
            header('Content-Type: application/json');
            
            $action = $_POST['action'];
            
            switch ($action) {
                case 'delete':
                    $this->deleteHouse();
                    break;
                // Можно добавить другие действия при необходимости
            }
            exit; // Предотвращаем дальнейшее выполнение кода
        }
    }
    
    // Удаление дома
    private function deleteHouse() {
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            echo json_encode(['success' => false, 'error' => 'Некорректный ID']);
            return;
        }
        
        $id = intval($_POST['id']);
        $adress = isset($_POST['adress']) ? $_POST['adress'] : '';
        
        // Проверка прав доступа
        $checkSql = "SELECT region FROM navigard_adress WHERE id = $id LIMIT 1";
        $checkResult = $this->connect->query($checkSql);
        
        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Дом не найден']);
            return;
        }
        
        $row = $checkResult->fetch_object();
        
        if ($this->user['admin'] != '1' && $this->user['region'] != $row->region) {
            echo json_encode(['success' => false, 'error' => 'Недостаточно прав для удаления']);
            return;
        }
        
        // Выполнение удаления
        $deleteSql = "DELETE FROM navigard_adress WHERE id = $id LIMIT 1";
        if ($this->connect->query($deleteSql)) {
            // Логирование операции удаления
            $this->logAction("Удален дом с ID: $id, адрес: $adress");
            echo json_encode(['success' => true, 'message' => 'Дом успешно удален']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при удалении: ' . $this->connect->error]);
        }
    }
    
    // Логирование действий
    private function logAction($message) {
        $username = $this->user['name'];
        $logText = "$username: $message";
        $sql = "INSERT INTO navigard_log (log) VALUES ('" . $this->connect->real_escape_string($logText) . "')";
        $this->connect->query($sql);
    }
    
    public function render() {
        // Если это AJAX запрос, обрабатываем его
        if ($this->isAjax) {
            $this->handleAjaxRequest();
            return;
        }
        
        // Сначала проверяем, есть ли AJAX запрос
        $this->handleAjaxRequest();
        
        // Получаем информацию о доме
        $row = null;
        
        // Проверяем параметр adress_id
        $adress_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']) : 0;
        if ($adress_id > 0) {
            $stmt = $this->connect->prepare("SELECT * FROM navigard_adress WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $adress_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->num_rows > 0 ? $result->fetch_object() : null;
            $stmt->close();
        }
        
        // Если adress_id не задан или не найден, проверяем параметр adress
        if (!$row && isset($_GET['adress'])) {
            $adress = $_GET['adress'];
            $stmt = $this->connect->prepare("SELECT * FROM navigard_adress WHERE adress = ? LIMIT 1");
            $stmt->bind_param("s", $adress);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->num_rows > 0 ? $result->fetch_object() : null;
            $stmt->close();
        }
        
        // Если дом не найден
        if (!$row) {
            $this->renderNotFound();
            return;
        }
        
        echo '<div class="container-fluid p-0 bg-light min-vh-100 d-flex flex-column mobile-container">';
        
        // Улучшенная шапка с адресом дома
        echo '<div class="result-header">
                <h1>Информация о доме</h1>
                <div class="result-address">' . $this->sanitize($row->adress) . '</div>
              </div>';
              
        // Добавляю класс pb-5 к контейнеру с содержимым
        echo '<div class="container mt-3" style="padding-bottom: 6rem !important;">';
        $this->renderHouseDetails($row);
        echo '</div></div>';
    }
    
    private function renderHouseDetails($row) {
        $isEditable = $this->user['admin'] || ($row->region == $this->user['region'] && $this->user['viewer']);
        
        // Карточка с основной информацией
        echo '<div class="card info-card mb-4">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><span id="cardStatusText">Просмотр информации</span></h5>';
                    
        if ($isEditable) {
            echo '<div class="custom-switch-container">
                    <input class="custom-switch-input" type="checkbox" id="editMode" onchange="toggleEditMode()">
                    <label class="custom-switch-label" for="editMode"></label>
                  </div>';
        }
        
        echo '</div>';
        
        // Блоки для просмотра и редактирования
        echo '<div class="editable-fields d-none">';
        $this->renderEditableFields($row);
        echo '</div>';
        
        echo '<div class="view-fields">';
        $this->renderViewFields($row);
        echo '</div>';
        
        // Статус завершения и кнопки действий
        echo '<div class="card-footer bg-light p-0">
                <div class="status-switch">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="check" id="completeSwitch" value="1" ' . ($row->complete == 1 ? "checked" : "") . '>
                        <label class="form-check-label" for="completeSwitch">Статус завершения</label>
                    </div>
                </div>';
                
        if ($isEditable || $this->user['region'] == $row->region || $this->user['admin'] == '1') {
            echo '<div class="action-buttons">';
            
            if ($isEditable) {
                echo '<button type="button" class="btn btn-success" onclick="saveChanges(' . $row->id . ')">
                        <i class="fa-solid fa-save"></i> Сохранить
                      </button>';
            }
            
            if ($this->user['region'] == $row->region || $this->user['admin'] == '1') {
                echo '<button type="button" class="btn btn-outline-danger" onclick="deleteHouse(' . $row->id . ', \'' . $this->sanitize($row->adress) . '\')">
                        <i class="fa-solid fa-trash"></i> Удалить
                      </button>';
            }
            
            echo '</div>';
        }
                
        echo '</div>';
        echo '</div>';
    }
    
    private function renderEditableFields($row) {
        echo '<form id="editForm" class="p-3">';
        echo '<div class="row">';
        
        // Основные данные
        echo '<div class="col-12">
                <h6 class="mb-3 pb-2 border-bottom">Местоположение</h6>
              </div>';
        
        echo '<div class="col-md-6 mb-3">';
        $this->outInput("adress", $row->adress, "Адрес", "fas fa-map-marker-alt");
        echo '</div>';
        
        echo '<div class="col-md-6 mb-3">';
        $this->outSelect("oboryda", "Где оборудование?", "navigard_oboryda", $row->oboryda, "fas fa-tools");
        echo '</div>';
        
        echo '<div class="col-md-6 mb-3">';
        $this->outSelect("pon", "Технология подключения", "navigard_pon", $row->pon, "fas fa-network-wired");
        echo '</div>';
        
        echo '<div class="col-md-6 mb-3">';
        $this->outSelect("podjezd", "Количество подъездов", "navigard_podjezd", $row->podjezd, "fas fa-building");
        echo '</div>';
        
        // Специфичные данные в зависимости от типа размещения
        switch ($row->oboryda) {
            case "Чердак":
                echo '<div class="col-12 mt-2">
                        <h6 class="mb-3 pb-2 border-bottom">Информация о чердаке</h6>
                      </div>';
                      
                echo '<div class="col-md-6 mb-3">';
                $this->outVihodSelect($row, "Подъезд с выходом", "fas fa-door-open");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("krisha", "Тип крыши", "navigard_krisha", $row->krisha, "fas fa-home");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outInput("kluch", $row->kluch, "Расположение ключа от чердака", "fas fa-key");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("lesnica", "Наличие лестницы", "navigard_lesnica", $row->lesnica, "fas fa-ladder");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("dopzamok", "Дополнительный замок", "navigard_dopzamok", $row->dopzamok, "fas fa-lock");
                echo '</div>';
                break;
                
            case "Подвал":
                echo '<div class="col-12 mt-2">
                        <h6 class="mb-3 pb-2 border-bottom">Информация о подвале</h6>
                      </div>';
                      
                echo '<div class="col-md-6 mb-3">';
                $this->outVihodSelect($row, "Подъезд с подвалом", "fas fa-door-open");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outInput("kluch", $row->kluch, "Расположение ключа от подвала", "fas fa-key");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("dopzamok", "Дополнительный замок", "navigard_dopzamok", $row->dopzamok, "fas fa-lock");
                echo '</div>';
                break;
                
            case "Подъезд":
                echo '<div class="col-12 mt-2">
                        <h6 class="mb-3 pb-2 border-bottom">Информация о подъезде</h6>
                      </div>';
                      
                echo '<div class="col-md-6 mb-3">';
                $this->outVihodSelect($row, "Подъезд с оборудованием", "fas fa-door-open");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("dopzamok", "Дополнительный замок", "navigard_dopzamok", $row->dopzamok, "fas fa-lock");
                echo '</div>';
                break;
                
            case "Фасад":
                echo '<div class="col-12 mt-2">
                        <h6 class="mb-3 pb-2 border-bottom">Информация о фасаде</h6>
                      </div>';
                      
                echo '<div class="col-md-6 mb-3">';
                $this->outInput("pitanie", $row->pitanie, "Источник питания", "fas fa-plug");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outInput("link", $row->link, "Источник линка", "fas fa-link");
                echo '</div>';
                break;
                
            case "Не указанно":
                echo '<div class="col-12 mt-2">
                        <h6 class="mb-3 pb-2 border-bottom">Дополнительная информация</h6>
                      </div>';
                      
                echo '<div class="col-md-6 mb-3">';
                $this->outVihodSelect($row, "Подъезд с выходом", "fas fa-door-open");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outSelect("dopzamok", "Дополнительный замок", "navigard_dopzamok", $row->dopzamok, "fas fa-lock");
                echo '</div>';
                
                echo '<div class="col-md-6 mb-3">';
                $this->outInput("kluch", $row->kluch, "Расположение ключа", "fas fa-key");
                echo '</div>';
                break;
        }
        
        // Контактная информация
        echo '<div class="col-12 mt-2">
                <h6 class="mb-3 pb-2 border-bottom">Контактная информация</h6>
              </div>';
        
        if ($this->user['admin'] == '1') {
            echo '<div class="col-md-6 mb-3">';
            $this->outSelect("region", "Регион", "navigard_region", $row->region, "fas fa-globe");
            echo '</div>';
        } else {
            echo "<input type='hidden' name='region' id='region' value='{$this->user['region']}'>";
        }
        
        echo '<div class="col-md-6 mb-3">';
        $this->outInput("pred", $row->pred, "Председатель (кв. и Ф.И.О)", "fas fa-user");
        echo '</div>';
        
        echo '<div class="col-md-6 mb-3">';
        $this->outInput("phone", $row->phone, "Телефон председателя", "fas fa-phone");
        echo '</div>';
        
        // Заметки
        echo '<div class="col-12 mt-3">
                <h6 class="mb-3 pb-2 border-bottom">Заметки</h6>
              </div>';
              
        echo '<div class="col-12">
                <div class="note-editor p-3 mb-3 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <label for="text" class="form-label d-block mb-3"><i class="fas fa-comment-alt me-2"></i>Добавить новую заметку:</label>
                    <div class="form-floating">
                        <textarea class="form-control" name="text" id="text" style="height: 100px; resize: vertical;" placeholder="Введите новую заметку"></textarea>
                        <label for="text">Ваша заметка будет сохранена с текущей датой и вашим именем</label>
                    </div>
                    <small class="text-muted mt-2 d-block">Заметка будет добавлена к существующим при сохранении изменений.</small>
                </div>
              </div>';
        
        echo '</div>';
        echo '</form>';
    }
    
    private function renderViewFields($row) {
        echo '<div class="p-0">';
        
        // Блок с местоположением и оборудованием
        echo '<div class="info-section">
                <h6 class="mb-3">Местоположение и оборудование</h6>
                <div class="row g-3">';
        
        $oboryda = $row->oboryda;
        $vihod_display = array_filter([$row->vihod, $row->vihod2, $row->vihod3, $row->vihod4, $row->vihod5]);
        $vihod_text = implode(', ', $vihod_display);
        
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-map-marker-alt"></i> Адрес</div>
                <div class="info-value">' . $this->sanitize($row->adress) . '</div>
              </div>';
              
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-tools"></i> Размещение оборудования</div>
                <div class="info-value">' . $this->sanitize($oboryda) . '</div>
              </div>';
        
        if (in_array($oboryda, ["Чердак", "Подвал", "Подъезд", "Не указанно"])) {
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-building"></i> Количество подъездов</div>
                    <div class="info-value">' . $this->sanitize($row->podjezd) . '</div>
                  </div>';
            
            if ($vihod_text) {
                $vihod_label = $oboryda == "Подвал" ? "Подъезд с подвалом" : 
                             ($oboryda == "Подъезд" ? "Подъезд с оборудованием" : "Подъезд с выходом");
                echo '<div class="col-md-6">
                        <div class="info-label"><i class="fas fa-door-open"></i> ' . $vihod_label . '</div>
                        <div class="info-value">' . $vihod_text . '</div>
                      </div>';
            }
        }
        
        echo '</div></div>';
        
        // Информация о типе размещения
        if ($oboryda == "Чердак" || $oboryda == "Не указанно") {
            echo '<div class="info-section">
                    <h6 class="mb-3">Информация о чердаке</h6>
                    <div class="row g-3">';
                    
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-home"></i> Тип крыши</div>
                    <div class="info-value">' . $this->sanitize($row->krisha) . '</div>
                  </div>';
                  
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-key"></i> Расположение ключа</div>
                    <div class="info-value">' . $this->sanitize($row->kluch) . '</div>
                  </div>';
                  
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-ladder"></i> Наличие лестницы</div>
                    <div class="info-value">' . $this->sanitize($row->lesnica) . '</div>
                  </div>';
                  
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-lock"></i> Дополнительный замок</div>
                    <div class="info-value">' . $this->sanitize($row->dopzamok) . '</div>
                  </div>';
                  
            echo '</div></div>';
            
        } elseif ($oboryda == "Подвал") {
            echo '<div class="info-section">
                    <h6 class="mb-3">Информация о подвале</h6>
                    <div class="row g-3">';
                    
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-key"></i> Расположение ключа</div>
                    <div class="info-value">' . $this->sanitize($row->kluch) . '</div>
                  </div>';
                  
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-lock"></i> Дополнительный замок</div>
                    <div class="info-value">' . $this->sanitize($row->dopzamok) . '</div>
                  </div>';
                  
            echo '</div></div>';
            
        } elseif ($oboryda == "Подъезд") {
            echo '<div class="info-section">
                    <h6 class="mb-3">Информация о подъезде</h6>
                    <div class="row g-3">';
                    
            echo '<div class="col-12">
                    <div class="info-label"><i class="fas fa-lock"></i> Дополнительный замок</div>
                    <div class="info-value">' . $this->sanitize($row->dopzamok) . '</div>
                  </div>';
                  
            echo '</div></div>';
        } elseif ($oboryda == "Фасад") {
            echo '<div class="info-section">
                    <h6 class="mb-3">Информация о фасаде</h6>
                    <div class="row g-3">';
                    
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-plug"></i> Источник питания</div>
                    <div class="info-value">' . $this->sanitize($row->pitanie) . '</div>
                  </div>';
                  
            echo '<div class="col-md-6">
                    <div class="info-label"><i class="fas fa-link"></i> Источник линка</div>
                    <div class="info-value">' . $this->sanitize($row->link) . '</div>
                  </div>';
                  
            echo '</div></div>';
        }
        
        // Техническая и контактная информация
        echo '<div class="info-section">
                <h6 class="mb-3">Технические данные</h6>
                <div class="row g-3">';
                
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-globe"></i> Регион</div>
                <div class="info-value">' . $this->sanitize($row->region) . '</div>
              </div>';
              
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-network-wired"></i> Тип подключения</div>
                <div class="info-value">' . $this->sanitize($row->pon) . '</div>
              </div>';
              
        echo '</div></div>';
        
        echo '<div class="info-section">
                <h6 class="mb-3">Контактная информация</h6>
                <div class="row g-3">';
                
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-user"></i> Председатель</div>
                <div class="info-value">' . $this->sanitize($row->pred) . '</div>
              </div>';
              
        echo '<div class="col-md-6">
                <div class="info-label"><i class="fas fa-phone"></i> Телефон</div>
                <div class="info-value">
                    <a href="tel:' . $this->sanitize($row->phone) . '" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-phone-alt me-1"></i> ' . $this->sanitize($row->phone) . '
                    </a>
                </div>
              </div>';
              
        echo '</div></div>';
        
        // Заметки - улучшенное отображение
        if (!empty($row->text)) {
            echo '<div class="info-section">
                    <h6 class="mb-3">Заметки</h6>
                    <div class="notes-wrapper">';
            
            $notes = explode("\n", $this->sanitize($row->text));
            foreach ($notes as $note) {
                if (trim($note)) {
                    if (preg_match('/\[DATE\](.*?)\[\/DATE\]\[AUTHOR\](.*?)\[\/AUTHOR\]\[TEXT\](.*?)\[\/TEXT\]/', $note, $matches)) {
                        $date = $matches[1];
                        $fio = $matches[2];
                        // Не экранируем HTML в тексте заметки
                        $noteText = $matches[3];
                        
                        echo '<div class="note-item">
                                <div class="note-item-header">
                                    <span class="note-item-date"><i class="far fa-calendar-alt me-2"></i>' . $date . '</span>
                                    <span class="note-item-author"><i class="far fa-user me-2"></i>' . $fio . '</span>
                                </div>
                                <div class="note-item-content">' . $noteText . '</div>
                              </div>';
                    } else {
                        echo '<div class="note-item">
                                <div class="note-item-content">' . $note . '</div>
                              </div>';
                    }
                }
            }
            
            echo '</div></div>';
        }
        
        // История изменений для админа - HTML обрабатывается корректно
        if ($this->user['admin'] == 1 && !empty($row->history)) {
            echo '<div class="info-section">
                    <h6 class="mb-3">История изменений</h6>
                    <div class="history-container small">' . 
                        $row->history . // Не экранируем HTML в истории изменений
                    '</div>
                  </div>';
        }
        
        echo '</div>';
    }
    
    private function outInput($name, $value, $label, $icon = null) {
        $iconHtml = $icon ? '<i class="' . $icon . ' me-2"></i>' : '';
        echo '<div class="form-floating">
                <input type="text" class="form-control" name="' . $name . '" id="' . $name . '" value="' . $this->sanitize($value ?? '') . '" placeholder="' . $label . '">
                <label for="' . $name . '">' . $iconHtml . $label . '</label>
              </div>';
    }
    
    private function outSelect($name, $label, $table, $currentValue, $icon = null) {
        $iconHtml = $icon ? '<i class="' . $icon . ' me-2"></i>' : '';
        echo '<div class="form-floating">
                <select class="form-select" name="' . $name . '" id="' . $name . '">';
        $items = $this->connect->query("SELECT * FROM $table");
        while ($item = $items->fetch_object()) {
            $selected = $currentValue === $item->name ? 'selected' : '';
            echo "<option $selected value='$item->name'>$item->name</option>";
        }
        echo '</select>
                <label for="' . $name . '">' . $iconHtml . $label . '</label>
              </div>';
    }
    
    private function outVihodSelect($row, $label, $icon = null) {
        $iconHtml = $icon ? '<i class="' . $icon . ' me-2"></i>' : '';
        echo '<div class="form-group">
                <label for="vihod" class="form-label">' . $iconHtml . $label . '</label>
                <select class="form-select" multiple name="vihod[]" id="vihod" style="height: 100px">';
        $vih = $this->connect->query("SELECT * FROM navigard_vihod");
        while ($vihod = $vih->fetch_object()) {
            $selected = in_array($vihod->name, [$row->vihod, $row->vihod2, $row->vihod3, $row->vihod4, $row->vihod5]) ? 'selected' : '';
            echo "<option $selected value='$vihod->name'>$vihod->name</option>";
        }
        echo '</select></div>';
    }
    
    private function renderNotFound() {
        echo '<div class="container-fluid p-0 bg-light min-vh-100 d-flex flex-column mobile-container">';
        
        echo '<div class="result-header">
                <h1>Результаты поиска</h1>
              </div>';
              
        echo '<div class="container py-4">
                <div class="card info-card text-center p-4">
                    <div class="mb-4 text-warning">
                        <i class="fa-solid fa-triangle-exclamation fa-4x"></i>
                    </div>
                    <h3 class="mb-3">Дом не найден</h3>
                    <p class="text-muted mb-4">Информация о доме с указанным идентификатором не найдена в базе данных.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fa-solid fa-home me-2"></i>Вернуться на главную
                    </a>
                </div>
              </div>';
        echo '</div>';
    }
}

$houseResult = new HouseResult($connect, $usr, $isAjax);
$houseResult->render();
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Показываем все элементы сразу без анимаций
    $(".result-header").css({opacity: 1, transform: 'none'});
    $(".info-card").css({opacity: 1, transform: 'none'});
    $(".info-section").show();
    
    // Инициализация всплывающих подсказок (Bootstrap tooltips)
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// Переключение между режимами редактирования и просмотра
function toggleEditMode() {
    var isEditMode = $('#editMode').is(':checked');
    
    // Обновление заголовка карточки
    $('#cardStatusText').text(isEditMode ? 'Редактирование информации' : 'Просмотр информации');
    
    // Переключение без анимации
    $('.editable-fields').toggleClass('d-none', !isEditMode);
    $('.view-fields').toggleClass('d-none', isEditMode);
}

// Функция для сохранения изменений
function saveChanges(id) {
    // Собираем данные формы
    var formData = {
        action: 'edit',
        id: id,
        adress: $('#adress').val(),
        check: $('#completeSwitch').is(':checked') ? 1 : 0,
        text: $('#text').val()
    };
    
    // Добавляем селекты и текстовые поля
    $('select').not('[name="vihod[]"]').each(function() {
        formData[$(this).attr('name')] = $(this).val();
    });
    
    $('input[type=text]').each(function() {
        formData[$(this).attr('name')] = $(this).val();
    });
    
    // Выбранные значения из множественного выбора
    var vihod = $('#vihod').val();
    if (vihod) {
        formData.vihod = vihod;
    }
    
    // Показываем индикатор загрузки
    showLoading(true);
    
    // Отправляем данные на сервер
    $.ajax({
        type: 'POST',
        url: 'obr_result.php',
        data: formData,
        dataType: 'json',
        success: function(response) {
            showLoading(false);
            
            if (response.success) {
                showNotification(true, 'Изменения успешно сохранены!');
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showNotification(false, response.error || 'Неизвестная ошибка при сохранении');
            }
        },
        error: function(xhr, status, error) {
            showLoading(false);
            console.error('Ошибка AJAX:', status, error);
            console.error('Ответ сервера:', xhr.responseText);
            showNotification(false, 'Произошла ошибка при сохранении данных: ' + error);
        }
    });
}

// Функция для удаления дома
function deleteHouse(id, adress) {
    if (!confirm('Вы уверены, что хотите удалить дом по адресу: ' + adress + '?')) {
        return;
    }
    
    showLoading(true);
    
    $.ajax({
        type: 'POST',
        url: 'obr_result.php',
        data: {
            action: 'delete',
            id: id,
            adress: adress
        },
        dataType: 'json',
        success: function(response) {
            showLoading(false);
            
            if (response.success) {
                showNotification(true, response.message || 'Дом успешно удален');
                setTimeout(function() { 
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                showNotification(false, response.error || 'Ошибка при удалении');
            }
        },
        error: function(xhr, status, error) {
            showLoading(false);
            console.error('Ошибка AJAX:', status, error);
            console.error('Ответ сервера:', xhr.responseText);
            showNotification(false, 'Произошла ошибка при удалении: ' + error);
        }
    });
}

// Отображение индикатора загрузки
function showLoading(isShow) {
    if (isShow) {
        $('body').append('<div id="loadingIndicator" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background-color: rgba(0,0,0,0.3); z-index: 9999;"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Загрузка...</span></div></div>');
    } else {
        $('#loadingIndicator').remove();
    }
}

// Отображение уведомлений
function showNotification(isSuccess, message) {
    // Удаляем все существующие уведомления
    $('.notification-container').remove();
    
    const type = isSuccess ? 'success' : 'danger';
    const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
    const backgroundColor = isSuccess ? '#28a745' : '#dc3545';
    
    // Создаем контейнер для уведомления
    const notification = $(`
        <div class="notification-container position-fixed top-50 start-50 translate-middle" style="z-index: 9999; max-width: 90%;">
            <div class="alert alert-${type} shadow-lg border-0 d-flex align-items-center p-4" style="min-width: 300px; background-color: ${backgroundColor}; color: white;">
                <div class="me-3">
                    <i class="fas ${icon} fa-2x"></i>
                </div>
                <div class="notification-message">
                    <strong class="d-block mb-1">${isSuccess ? 'Успешно!' : 'Внимание!'}</strong>
                    <span>${message}</span>
                </div>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    // Анимация появления
    notification.css('opacity', '0');
    notification.css('transform', 'translate(-50%, -60%)');
    
    setTimeout(() => {
        notification.css('transition', 'all 0.3s ease');
        notification.css('opacity', '1');
        notification.css('transform', 'translate(-50%, -50%)');
    }, 10);
    
    // Исчезновение через некоторое время
    setTimeout(() => {
        notification.css('opacity', '0');
        notification.css('transform', 'translate(-50%, -40%)');
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
</script>

<?php include(__DIR__ . '/../inc/foot.php'); ?>