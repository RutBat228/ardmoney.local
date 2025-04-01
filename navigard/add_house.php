<?php
include(__DIR__ . '/../inc/function.php');
include(__DIR__ . '/../inc/style.php');
?>
<!-- Font Awesome 6.2.1 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
<!-- Подключаем GSAP для анимаций -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>

<style>
    /* Стили для шапки страницы */
    .page-header {
        background-color: #434e38 !important;
        position: relative;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        width: 100%;
        padding: 20px;
        text-align: center;
        color: white;
    }
    
    .page-header::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        right: 0;
        height: 20px;
        background: linear-gradient(135deg, transparent 33.33%, #434e38 33.33%, #434e38 66.66%, transparent 66.66%);
        background-size: 20px 40px;
        background-repeat: repeat-x;
        filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.1));
    }
</style>
<?php
AutorizeProtect();
global $usr, $connect;

class HouseAdder {
    private $connect;
    private $user;
    private $steps = [
        1 => ['name' => 'Адрес', 'key' => 'address'],
        2 => ['name' => 'Оборудование', 'key' => 'equipment'],
        3 => ['name' => 'Детали', 'key' => 'details'],
        'final' => ['name' => 'Завершение', 'key' => 'finish']
    ];
    
    public function __construct($connect, $user) {
        $this->connect = $connect;
        $this->user = $user;
        session_start();
    }
    
    private function clearSession() {
        $keys = ['adress', 'region', 'oboryda', 'pon', 'lesnica', 'podjezd', 
                'dopzamok', 'vihod', 'krisha', 'klych', 'pred', 'phone', 'text', 'step', 
                'link', 'pitanie'];
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }
    
    private function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function getCurrentStep() {
        return isset($_SESSION['step']) && array_key_exists($_SESSION['step'], $this->steps) 
            ? $_SESSION['step'] 
            : 1;
    }
    
    private function renderProgressBar($currentStep) {
        echo '<div class="progress-steps mb-4 gsap-progress">';
        echo '<div class="row no-gutters">';
        
        $totalSteps = count($this->steps) - 1; // Не считаем 'final' как отдельный шаг
        
        foreach ($this->steps as $stepNum => $stepData) {
            if ($stepNum === 'final') continue; // Пропускаем финальный шаг в прогресс-баре
            
            $class = $currentStep == $stepNum ? 'active' : 
                    ($currentStep > $stepNum || $currentStep === 'final' ? 'completed' : '');
            
            $icon = '';
            if ($class === 'completed') {
                $icon = '<i class="fa-solid fa-check"></i>';
            } else {
                switch ($stepNum) {
                    case 1: $icon = '<i class="fa-solid fa-map-marker-alt"></i>'; break;
                    case 2: $icon = '<i class="fa-solid fa-tools"></i>'; break;
                    case 3: $icon = '<i class="fa-solid fa-clipboard-list"></i>'; break;
                }
            }
            
            $colClass = 'col-' . (12 / $totalSteps);
            
            echo "<div class='$colClass'>
                    <div class='step-item $class'>
                        <div class='step-circle'>$icon</div>
                        <div class='step-label'>{$stepData['name']}</div>
                        " . ($stepNum < $totalSteps ? "<div class='step-line'></div>" : "") . "
                    </div>
                  </div>";
        }
        
        echo '</div></div>';
    }
    
    public function render() {
        $step = $this->getCurrentStep();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
            $this->processInput($_GET);
            $step = $this->getCurrentStep();
        }
        
        echo '<div class="container-fluid p-0 bg-white min-vh-100 d-flex flex-column">
                <div class="page-header bg-primary text-white p-3 text-center position-relative mb-3">
                    <h1 class="h3 mt-1 mb-2 fw-bold">Добавление дома</h1>
                </div>
                <div class="container py-3 flex-grow-1" style="padding-bottom: 6rem !important;">';
        
        echo '<div class="row justify-content-center">
                <div class="col-lg-8">
                    <p class="text-muted text-center mb-4 gsap-subtitle">Заполните информацию о доме поэтапно</p>';
        
        $this->renderProgressBar($step);
        
        echo '<div class="card shadow-sm border-0 rounded-4 gsap-card animate__animated animate__fadeIn">';
        
        switch ($step) {
            case 1:
                $this->renderAddressForm();
                break;
            case 2:
                $this->renderEquipmentForm();
                break;
            case 3:
                $this->renderDetailsForm();
                break;
            case 'final':
                $this->processFinalStep();
                break;
        }
        
        echo '</div></div></div></div></div>';
    }
    
    private function processInput($data) {
        foreach ($data as $key => $value) {
            if ($key === 'vihod') {
                $_SESSION[$key] = array_map([$this, 'sanitize'], (array)$value);
            } elseif ($key !== 'step') {
                $_SESSION[$key] = $this->sanitize($value);
            } else {
                $_SESSION[$key] = $value;
            }
        }
        
        // Проверяем только адрес и регион
        if (isset($_SESSION['step']) && $_SESSION['step'] >= 2 && 
            (!isset($_SESSION['adress']) || empty($_SESSION['adress']) || 
             !isset($_SESSION['region']) || empty($_SESSION['region']))) {
            $this->clearSession();
            $this->showNotification("Адрес и регион обязательны для заполнения!", "danger");
            header("Location: add_house.php");
            exit();
        }
    }
    
    private function showNotification($message, $type) {
        echo '<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
                <div class="toast align-items-center text-white bg-' . $type . ' border-0" role="alert" aria-live="assertive" aria-atomic="true">
                  <div class="d-flex">
                    <div class="toast-body">
                      <i class="fas ' . ($type == 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle') . ' me-2"></i>
                      ' . $message . '
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                  </div>
                </div>
              </div>
              <script>
                document.addEventListener("DOMContentLoaded", function() {
                  var toast = new bootstrap.Toast(document.querySelector(".toast"));
                  toast.show();
                });
              </script>';
    }
    
    private function renderAddressForm() {
        echo '<form method="GET" action="#" onsubmit="return validateStep1()">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Шаг 1: Укажите адрес</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label for="adress" class="form-label">
                        <i class="fas fa-home me-2"></i>
                        Адрес <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           name="adress" 
                           id="adress" 
                           class="form-control form-control-lg animate__animated animate__fadeIn" 
                           placeholder="Введите адрес дома" 
                           value="' . ($_SESSION['adress'] ?? '') . '" 
                           required>
                    <div class="form-text">Укажите полный адрес дома</div>
                </div>';
                
        if ($this->user['admin'] == '1') {
            echo '<div class="mb-4">
                    <label for="region" class="form-label">
                        <i class="fas fa-globe-europe me-2"></i>
                        Регион <span class="text-danger">*</span>
                    </label>
                    <select name="region" id="region" required class="form-select form-select-lg animate__animated animate__fadeIn">';
            $regions = $this->connect->query("SELECT * FROM navigard_region");
            while ($region = $regions->fetch_object()) {
                $selected = ($_SESSION['region'] ?? $this->user['region']) === $region->name ? 'selected' : '';
                echo "<option $selected value='$region->name'>$region->name</option>";
            }
            echo '</select>
                  <div class="form-text">Выберите регион, к которому относится дом</div>
                </div>';
        } else {
            echo "<input type='hidden' name='region' value='{$this->user['region']}'>";
        }
        
        echo '</div>
            <div class="card-footer d-flex justify-content-end bg-light">
                <input type="hidden" name="step" value="2">
                <button type="submit" class="btn btn-primary">
                    Далее <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div></form>';
    }
    
    private function renderEquipmentForm() {
        echo '<form method="GET" action="#" onsubmit="return validateStep2()">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Шаг 2: Оборудование</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label for="oboryda" class="form-label">
                        <i class="fas fa-map-signs me-2"></i>
                        Где размещено оборудование?
                    </label>
                    <select name="oboryda" id="oboryda" class="form-select form-select-lg animate__animated animate__fadeIn">';
        $equipment = $this->connect->query("SELECT * FROM navigard_oboryda");
        while ($item = $equipment->fetch_object()) {
            $selected = ($_SESSION['oboryda'] ?? '') === $item->name ? 'selected' : '';
            echo "<option $selected value='$item->name'>$item->name</option>";
        }
        echo '</select>
                    <div class="form-text">Укажите место размещения оборудования</div>
                </div>
                <div class="mb-4">
                    <label for="pon" class="form-label">
                        <i class="fas fa-network-wired me-2"></i>
                        Тип подключения
                    </label>
                    <select name="pon" id="pon" class="form-select form-select-lg animate__animated animate__fadeIn">';
        $pon = $this->connect->query("SELECT * FROM navigard_pon");
        while ($item = $pon->fetch_object()) {
            $selected = ($_SESSION['pon'] ?? '') === $item->name ? 'selected' : '';
            echo "<option $selected value='$item->name'>$item->name</option>";
        }
        echo '</select>
                    <div class="form-text">Выберите технологию подключения</div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between bg-light">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href=\'add_house.php?step=1\'">
                    <i class="fas fa-arrow-left me-2"></i> Назад
                </button>
                <input type="hidden" name="step" value="3">
                <button type="submit" class="btn btn-primary">
                    Далее <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div></form>';
    }
    
    private function renderDetailsForm() {
        $fields = [
            'Подвал' => ['dopzamok', 'klych', 'vihod', 'podjezd', 'pitanie', 'link'],
            'Фасад' => ['podjezd'],
            'Чердак' => ['krisha', 'lesnica', 'dopzamok', 'klych', 'vihod', 'podjezd', 'pitanie', 'link'],
            'Подъезд' => ['dopzamok', 'vihod', 'podjezd', 'pitanie', 'link']
        ];
        
        $oboryda = $_SESSION['oboryda'] ?? 'Подвал';
        $pon = $_SESSION['pon'] ?? '';
        
        echo '<form method="GET" action="#" onsubmit="return validateStep3()">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Шаг 3: Дополнительные детали</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">';
                
        foreach ($fields[$oboryda] as $field) {
            if ($field === 'vihod') {
                if ($oboryda === 'Подвал') {
                    $this->renderField($field, 'Подвал с оборудованием');
                } elseif ($oboryda === 'Фасад') {
                    continue;
                } elseif ($oboryda === 'Подъезд') {
                    $this->renderField($field, 'Подъезд с оборудованием');
                } else {
                    $this->renderField($field);
                }
            } elseif ($pon === 'Gpon' && in_array($field, ['dopzamok', 'pitanie', 'link'])) {
                continue;
            } else {
                $this->renderField($field);
            }
        }
        
        echo '<div class="col-md-6 mb-3">
                <label for="pred" class="form-label">
                    <i class="fas fa-user me-2"></i>
                    Ф.И.О. председателя
                </label>
                <input name="pred" 
                       id="pred" 
                       type="text" 
                       class="form-control animate__animated animate__fadeIn"
                       placeholder="Председатель Ф.И.О и Кв." 
                       value="' . ($_SESSION['pred'] ?? '') . '">
            </div>
            <div class="col-md-6 mb-3">
                <label for="phone" class="form-label">
                    <i class="fas fa-phone me-2"></i>
                    Номер телефона
                </label>
                <input type="tel" 
                       name="phone" 
                       id="phone" 
                       class="form-control animate__animated animate__fadeIn"
                       placeholder="+79781234567" 
                       pattern="\+7[0-9]{10}" 
                       value="' . ($_SESSION['phone'] ?? '') . '">
            </div>
            <div class="col-12 mb-3">
                <label for="text" class="form-label">
                    <i class="fas fa-comment-alt me-2"></i>
                    Заметки
                </label>
                <textarea name="text" 
                         id="text" 
                         class="form-control animate__animated animate__fadeIn"
                         rows="3"
                         placeholder="Введите заметки">' . ($_SESSION['text'] ?? '') . '</textarea>
            </div>
        </div>
        </div>
        <div class="card-footer d-flex justify-content-between bg-light">
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href=\'add_house.php?step=2\'">
                <i class="fas fa-arrow-left me-2"></i> Назад
            </button>
            <input type="hidden" name="step" value="final">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check me-2"></i> Завершить
            </button>
        </div></form>';
    }
    
    private function renderField($field, $customLabel = null) {
        $fieldMap = [
            'krisha' => ['Какая крыша?', 'navigard_krisha', 'fas fa-home'],
            'lesnica' => ['Наличие лестницы', 'navigard_lesnica', 'fas fa-ladder'],
            'dopzamok' => ['Наличие доп. замка', 'navigard_dopzamok', 'fas fa-lock'],
            'klych' => ['В какой квартире ключ', null, 'fas fa-key', 'text'],
            'vihod' => ['Выход на чердак', 'navigard_vihod', 'fas fa-door-open', 'select', true],
            'podjezd' => ['Количество подъездов', 'navigard_podjezd', 'fas fa-building'],
            'pitanie' => ['Источник питания', null, 'fas fa-plug', 'text'],
            'link' => ['Ссылка на документацию', null, 'fas fa-link', 'text']
        ];
        
        $config = $fieldMap[$field];
        $label = $customLabel ?? $config[0];
        $icon = $config[2] ?? 'fas fa-info-circle';
        
        echo "<div class='col-md-6 mb-3'>
                <label for='$field' class='form-label'>
                    <i class='$icon me-2'></i>
                    $label
                </label>";
        
        $fieldType = isset($config[3]) ? $config[3] : 'select';
        $isMultiple = isset($config[4]) && $config[4];
        
        if ($fieldType === 'text') {
            echo "<input name='$field' 
                       id='$field' 
                       type='text' 
                       class='form-control animate__animated animate__fadeIn'
                       placeholder='$label' 
                       value='" . ($_SESSION[$field] ?? '') . "'>";
        } else {
            echo "<select " . ($isMultiple ? 'multiple' : '') . " 
                        name='$field" . ($isMultiple ? '[]' : '') . "' 
                        id='$field'
                        class='form-select" . ($isMultiple ? ' form-select-multiple' : '') . " animate__animated animate__fadeIn'" .
                        ($isMultiple ? ' size="4"' : '') . ">";
            if (isset($config[1])) {
                $items = $this->connect->query("SELECT * FROM {$config[1]}");
                while ($item = $items->fetch_object()) {
                    $selected = in_array($item->name, (array)($_SESSION[$field] ?? [])) ? 'selected' : '';
                    echo "<option $selected value='$item->name'>$item->name</option>";
                }
            }
            echo "</select>";
        }
        echo "</div>";
    }
    
    private function processFinalStep() {
        if (!isset($_SESSION['adress']) || empty($_SESSION['adress']) || 
            !isset($_SESSION['region']) || empty($_SESSION['region'])) {
            $this->clearSession();
            $this->showNotification("Пожалуйста, заполните адрес и регион!", "danger");
            header("Location: add_house.php");
            exit();
        }

        $vihod = $_SESSION['vihod'] ?? [];
        $vihod1 = $vihod[0] ?? '';
        $vihod2 = $vihod[1] ?? '';
        $vihod3 = $vihod[2] ?? '';
        $vihod4 = $vihod[3] ?? '';
        $vihod5 = $vihod[4] ?? '';
        
        $data = [
            'adress' => $this->sanitize($_SESSION['adress']),
            'region' => $this->sanitize($_SESSION['region']),
            'oboryda' => $this->sanitize($_SESSION['oboryda'] ?? ''),
            'dopzamok' => $this->sanitize($_SESSION['dopzamok'] ?? ''),
            'kluch' => $this->sanitize($_SESSION['klych'] ?? ''),
            'pred' => $this->sanitize($_SESSION['pred'] ?? ''),
            'phone' => $this->sanitize($_SESSION['phone'] ?? ''),
            'krisha' => $this->sanitize($_SESSION['krisha'] ?? ''),
            'lesnica' => $this->sanitize($_SESSION['lesnica'] ?? ''),
            'pon' => $this->sanitize($_SESSION['pon'] ?? ''),
            'podjezd' => $this->sanitize($_SESSION['podjezd'] ?? ''),
            'text' => $this->sanitize($_SESSION['text'] ?? ''),
            'editor' => $this->user['name'],
            'new' => 0,
            'complete' => 0,
            'link' => $this->sanitize($_SESSION['link'] ?? ''),
            'pitanie' => $this->sanitize($_SESSION['pitanie'] ?? '')
        ];

        // Проверка на существующий адрес
        $check = $this->connect->prepare("SELECT * FROM navigard_adress WHERE adress = ?");
        $check->bind_param("s", $data['adress']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $this->showNotification("Этот адрес уже добавлен!", "danger");
            header("Location: /navigard/result.php?adress=" . urlencode($data['adress']));
            exit();
        }

        // Подготовка и выполнение запроса
        $stmt = $this->connect->prepare(
            "INSERT INTO navigard_adress (adress, vihod, vihod2, vihod3, vihod4, vihod5, 
            oboryda, dopzamok, kluch, pred, phone, krisha, lesnica, pon, podjezd, text, 
            editor, region, new, complete, link, pitanie) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssssssssssssssiiss",
            $data['adress'], $vihod1, $vihod2, $vihod3, $vihod4, $vihod5,
            $data['oboryda'], $data['dopzamok'], $data['kluch'], $data['pred'],
            $data['phone'], $data['krisha'], $data['lesnica'], $data['pon'],
            $data['podjezd'], $data['text'], $data['editor'], $data['region'],
            $data['new'], $data['complete'], $data['link'], $data['pitanie']
        );

        // Логирование
        $date = date("d.m.Y H:i:s");
        $logText = "Пользователь {$this->user['name']} добавил дом {$data['adress']}";
        $logStmt = $this->connect->prepare("INSERT INTO navigard_log (kogda, log) VALUES (?, ?)");
        $logStmt->bind_param("ss", $date, $logText);
        $logStmt->execute();

        if ($stmt->execute()) {
            $this->clearSession();
            echo '<div class="card-body p-5 text-center">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Дом успешно добавлен!</h2>
                    <p class="mb-4 text-muted">Все данные сохранены в базе данных</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="add_house.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>
                            Добавить еще один дом
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>
                            Вернуться на главную
                        </a>
                    </div>
                  </div>';
        } else {
            $this->showNotification("Ошибка при добавлении дома: " . $this->connect->error, "danger");
        }
    }
}

// Добавляем стиль для переопределения цвета primary
echo '<style>
:root {
    --bs-primary: #434e38;
    --bs-primary-rgb: 67, 78, 56;
}
.btn-primary {
    background-color: #434e38;
    border-color: #434e38;
}
.btn-primary:hover, .btn-primary:focus, .btn-primary:active {
    background-color: #353e2d !important;
    border-color: #353e2d !important;
}
.text-primary {
    color: #434e38 !important;
}
.bg-primary {
    background-color: #434e38 !important;
}
.btn-outline-primary {
    color: #434e38;
    border-color: #434e38;
}
.btn-outline-primary:hover {
    background-color: #434e38;
    border-color: #434e38;
}

/* Улучшенный стиль для прогресс-бара */
.progress-steps {
    margin-bottom: 2rem;
}

.step-item {
    position: relative;
    text-align: center;
    padding: 0 10px;
}

.step-circle {
    width: 50px;
    height: 50px;
    margin: 0 auto 10px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.step-line {
    position: absolute;
    top: 25px;
    right: calc(-50% + 25px);
    width: calc(100% - 50px);
    height: 3px;
    background-color: #f0f0f0;
    z-index: 0;
    transition: all 0.3s ease;
}

.step-item.active .step-circle {
    background-color: #434e38;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 10px rgba(67, 78, 56, 0.3);
}

.step-item.active .step-label {
    color: #434e38;
    font-weight: 600;
}

.step-item.completed .step-circle {
    background-color: #6c9955;
    color: white;
}

.step-item.completed .step-line {
    background-color: #6c9955;
}

/* Улучшенные стили для полей и элементов формы */
.form-control:focus, .form-select:focus {
    border-color: rgba(67, 78, 56, 0.5);
    box-shadow: 0 0 0 0.25rem rgba(67, 78, 56, 0.25);
}

.rounded-4 {
    border-radius: 0.75rem !important;
}

/* Стили для карточек и кнопок */
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(67, 78, 56, 0.1);
    color: #434e38;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
}

.btn:hover .btn-icon {
    background: rgba(67, 78, 56, 0.2);
    transform: scale(1.1);
}
</style>';

// Скрипт для GSAP анимаций
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // GSAP анимации для элементов интерфейса
    if (typeof gsap !== "undefined") {
        // Определяем последовательность анимаций
        const tl = gsap.timeline({delay: 0.2});
        
        // Анимация элементов страницы
        tl.from(".page-header", {
            opacity: 0,
            y: -20,
            duration: 0.5,
            ease: "power2.out"
        })
        .from(".gsap-subtitle", {
            opacity: 0,
            y: 10,
            duration: 0.4,
            ease: "power2.out"
        }, "+=0.1")
        .from(".gsap-progress .step-item", {
            opacity: 0,
            y: 20,
            stagger: 0.1,
            duration: 0.5,
            ease: "power3.out"
        }, "+=0.1")
        .from(".gsap-card", {
            opacity: 0,
            y: 20,
            duration: 0.6,
            ease: "power3.out"
        }, "+=0.1");
        
        // Анимируем активный шаг
        const activeStep = document.querySelector(".step-item.active");
        if (activeStep) {
            gsap.from(activeStep, {
                scale: 1.2,
                duration: 0.5,
                ease: "elastic.out(1, 0.5)",
                delay: 1.5
            });
            
            gsap.from(activeStep.querySelector(".step-circle"), {
                rotation: 360,
                duration: 0.8,
                ease: "power2.out",
                delay: 1.6
            });
        }
        
        // Анимация для полей формы при загрузке
        const formElements = document.querySelectorAll(".card-body .form-group, .card-body .form-control, .card-body .form-select, .card-body .btn");
        if (formElements.length > 0) {
            gsap.from(formElements, {
                opacity: 0,
                y: 15,
                stagger: 0.05,
                duration: 0.4,
                ease: "power2.out",
                delay: 0.5
            });
        }
    }
});
</script>';

$houseAdder = new HouseAdder($connect, $usr);
$houseAdder->render();

include(__DIR__ . '/../inc/foot.php');
?>

<style>
.progress-steps {
    padding: 20px 0;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-size: 18px;
    position: relative;
    z-index: 2;
    transition: all 0.3s;
}

.step-label {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
}

.step-line {
    position: absolute;
    top: 25px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.step-item.active .step-circle {
    background-color: var(--bs-primary);
    color: white;
}

.step-item.active .step-label {
    color: var(--bs-primary);
    font-weight: 600;
}

.step-item.completed .step-circle {
    background-color: var(--bs-success);
    color: white;
}

.step-item.completed .step-line {
    background-color: var(--bs-success);
}

.form-select-multiple {
    height: auto !important;
}
</style>

<script>
    function validateStep1() {
        const adress = document.getElementById('adress').value.trim();
        const region = document.getElementById('region');
        if (!adress) {
            alert('Пожалуйста, введите адрес!');
            return false;
        }
        if (region && !region.value.trim()) {
            alert('Пожалуйста, выберите регион!');
            return false;
        }
        return true;
    }
    
    function validateStep2() {
        return true; // Все поля необязательные
    }
    
    function validateStep3() {
        const phone = document.getElementById('phone').value.trim();
        if (phone && !/^\+7[0-9]{10}$/.test(phone)) {
            alert('Номер телефона должен быть в формате +79781234567!');
            return false;
        }
        return true;
    }
</script>

<script>
// GSAP анимации для шапки
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gsap !== 'undefined') {
        // Задержка перед началом анимации для гарантии полной загрузки
        setTimeout(function() {
            // Анимация шапки
            gsap.fromTo('.page-header', 
                { autoAlpha: 0, y: -20 }, 
                { autoAlpha: 1, y: 0, duration: 0.5, ease: 'power2.out' }
            );
            
            // Анимация остальных элементов
            gsap.fromTo('.progress-steps', 
                { autoAlpha: 0, y: 20 }, 
                { autoAlpha: 1, y: 0, duration: 0.5, delay: 0.3, ease: 'power2.out' }
            );
            
            gsap.fromTo('.card', 
                { autoAlpha: 0, scale: 0.95 }, 
                { autoAlpha: 1, scale: 1, duration: 0.5, delay: 0.5, ease: 'back.out(1.5)' }
            );
        }, 100);
    }
});
</script>