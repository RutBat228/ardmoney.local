<?php
include(__DIR__ . '/../inc/head.php');
?>
<!-- Font Awesome 6.2.1 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
<!-- Внешний CSS файл вместо встроенных стилей -->
<link rel="stylesheet" href="css/user.css">
<!-- Подключаем GSAP для анимаций -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<?php
AutorizeProtect();
global $connect, $usr;

// Очистка логов для администратора
if (isset($_POST['submit_clear_logs']) && $usr['admin'] == 1) {
    $sql = "TRUNCATE navigard_log";
    if ($connect->query($sql) === false) {
        echo "Ошибка: " . $sql . "<br>" . $connect->error;
    }
    redir("user", "0");
}

const USER_IMAGES = [
    'RutBat' => 'user_RutBat.webp',
    'Игорь' => 'user_Игорь.webp',
    'kovalev' => 'user_Вова.webp',
    'grisnevskijp@gmail.com' => 'user_Паша.webp',
    'Юра' => 'user_Юра.webp'
];

function getUserImage(string $username): string {
    return isset(USER_IMAGES[$username])
        ? "img/user/" . USER_IMAGES[$username]
        : "img/user/user_logo.webp?123";
}

// Получаем количество домов в регионе пользователя
function getRegionStats($connect, $region) {
    $query = "SELECT 
              COUNT(*) as total, 
              SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) as complete_count
              FROM navigard_adress
              WHERE region = '$region'";
    $result = mysqli_query($connect, $query);
    $row = mysqli_fetch_assoc($result);
    return $row;
}

// Получаем статистику по пользователю
$regionStats = getRegionStats($connect, $usr['region']);
?>

<div class="container-fluid p-0 bg-light min-vh-100 d-flex flex-column mobile-container">
    <div class="profile-header bg-primary text-white p-3 text-center position-relative">
        <div class="avatar-container">
            <div class="avatar-wrapper" id="avatar-wrapper">
                <img src="../<?= getUserImage($usr['name']); ?>" alt="Аватар пользователя" class="avatar-img">
                <div class="avatar-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
        </div>
        <h1 class="h4 mt-3 mb-1"><?= $usr['name'] ?></h1>
        <p class="mb-0 small opacity-75"><?= $usr['rang'] ?></p>
        
        <!-- Форма загрузки аватара (скрытая) -->
        <div class="upload-form-container" id="upload-form" style="display:none;">
            <form name="upload" action="download_img.php" method="POST" ENCTYPE="multipart/form-data" class="mt-3">
                <div class="upload-input-wrapper">
                    <input type="file" class="form-control" name="userfile" id="input-file">
                    <button type="button" class="btn btn-sm btn-light" id="cancel-upload">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button type="submit" name="upload" class="btn btn-light btn-sm w-100 mt-2" id="upload-button">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="fas fa-upload me-2"></i> Загрузить
                        <div id="spiner" class="spinner-border spinner-border-sm ms-2" role="status" style="display:none;"></div>
                    </div>
                </button>
            </form>
        </div>
    </div>
    
    <div class="container pt-3 flex-grow-1" style="padding-bottom: 6rem !important;">
        <?php if ($usr['admin'] == 1): ?>
        <!-- Навигация для администратора -->
        <div class="admin-tabs mb-3">
            <div class="tab-nav">
                <button class="tab-btn active" data-tab="profile">
                    <i class="fas fa-user"></i> Профиль
                </button>
                <button class="tab-btn" data-tab="logs">
                    <i class="fas fa-list"></i> Логи
                </button>
                <button class="tab-btn" data-tab="stats">
                    <i class="fas fa-chart-bar"></i> Статистика
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Секция профиля - видима изначально -->
        <div class="tab-content" id="profile-tab" style="display:block;">
            <div class="info-card">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Логин</div>
                        <div class="info-value"><?= $usr['name'] ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Должность</div>
                        <div class="info-value"><?= $usr['rang'] ?></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <div class="info-label">Регион</div>
                        <div class="info-value"><?= $usr['region'] ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Дополнительная информация по региону вместо даты и времени -->
            <div class="stats-cards">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stats-data">
                        <div class="stats-value">
                            <?= $regionStats['total'] ?? 0 ?>
                        </div>
                        <div class="stats-label">Всего домов</div>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-data">
                        <div class="stats-value">
                            <?= $regionStats['complete_count'] ?? 0 ?>
                        </div>
                        <div class="stats-label">Готово</div>
                    </div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stats-data">
                        <?php 
                        $total = ($regionStats['total'] > 0) ? $regionStats['total'] : 1;
                        $percent = round(($regionStats['complete_count'] / $total) * 100);
                        ?>
                        <div class="stats-value">
                            <?= $percent ?>%
                        </div>
                        <div class="stats-label">Готовность</div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($usr['admin'] == 1): ?>
        <!-- Секция логов - скрыта изначально -->
        <div class="tab-content" id="logs-tab" style="display:none;">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Логи системы</h5>
                    <form action="" method="POST" class="d-inline">
                        <button type="submit" name="submit_clear_logs" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> Очистить
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="logs-container">
                        <?php
                        $sql = "SELECT * FROM navigard_log ORDER BY id DESC LIMIT 100";
                        $res_data = mysqli_query($connect, $sql);
                        
                        if (mysqli_num_rows($res_data) > 0) {
                            while ($row = mysqli_fetch_array($res_data)) {
                                $text = trim($row['log']);
                                $time = isset($row['date']) ? date('d.m H:i', strtotime($row['date'])) : '';
                                
                                // Определяем тип лога по содержимому
                                $logType = 'info';
                                if (stripos($text, 'error') !== false || stripos($text, 'ошибка') !== false) {
                                    $logType = 'danger';
                                } elseif (stripos($text, 'warning') !== false || stripos($text, 'внимание') !== false) {
                                    $logType = 'warning';
                                } elseif (stripos($text, 'success') !== false || stripos($text, 'успешно') !== false) {
                                    $logType = 'success';
                                }
                                
                                // Попытка извлечь имя пользователя из лога
                                $userName = '';
                                $logText = $text;
                                
                                // Проверка наличия имени пользователя в формате "Имя:"
                                if (preg_match('/^([^:]+):(.+)$/', $text, $matches)) {
                                    $userName = trim($matches[1]);
                                    $logText = trim($matches[2]);
                                }
                                
                                echo "<div class='log-entry log-{$logType}'>";
                                if ($time) {
                                    echo "<div class='log-time'><i class='fas fa-calendar-alt me-1'></i>{$time}</div>";
                                }
                                if ($userName) {
                                    echo "<div class='log-user'><i class='fas fa-user me-1'></i>{$userName}</div>";
                                }
                                echo "<div class='log-text'>{$logText}</div>";
                                echo "</div>";
                            }
                        } else {
                            echo "<div class='text-center text-muted py-4'>Нет записей в логах</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Секция статистики - скрыта изначально -->
        <div class="tab-content" id="stats-tab" style="display:none;">
            <div class="stats-actions mb-3">
                <a href="gm.php" class="btn btn-primary w-100">
                    <i class="fas fa-chart-bar me-2"></i> Перейти к полной статистике
                </a>
            </div>
            
            <!-- Краткая статистика -->
            <?php
            // Получаем базовую статистику
            $query = "SELECT COUNT(*) as total, 
                      SUM(CASE WHEN new = 1 THEN 1 ELSE 0 END) as new_count,
                      SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) as complete_count
                      FROM navigard_adress";
            $result = $connect->query($query);
            $stats = $result->fetch_assoc();
            
            $total = $stats['total'] > 0 ? $stats['total'] : 1;
            $percentComplete = round(($stats['complete_count'] / $total) * 100);
            ?>
            
            <div class="stats-quick-view">
                <div class="stats-quick-item">
                    <div class="stats-quick-label">Всего домов</div>
                    <div class="stats-quick-value"><?= $stats['total'] ?></div>
                </div>
                <div class="stats-quick-item highlight">
                    <div class="stats-quick-label">Готовность</div>
                    <div class="stats-quick-value"><?= $percentComplete ?>%</div>
                    <div class="quick-progress">
                        <div class="quick-progress-bar" style="width: <?= $percentComplete ?>%"></div>
                    </div>
                </div>
                <div class="stats-quick-item">
                    <div class="stats-quick-label">Готовых</div>
                    <div class="stats-quick-value"><?= $stats['complete_count'] ?></div>
                </div>
            </div>
            
            <!-- Таблица с топ-3 регионами -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Топ регионы</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Регион</th>
                                    <th class="text-center">Готовых</th>
                                    <th class="text-center">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Получаем топ-3 региона по готовности
                                $query = "SELECT region, COUNT(*) as total, 
                                         SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) as complete_count
                                         FROM navigard_adress
                                         GROUP BY region
                                         ORDER BY (SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) / COUNT(*)) DESC, 
                                         COUNT(*) DESC
                                         LIMIT 3";
                                $result = $connect->query($query);
                                
                                while ($row = $result->fetch_assoc()) {
                                    $total = $row['total'] > 0 ? $row['total'] : 1;
                                    $percentComplete = round(($row['complete_count'] / $total) * 100);
                                    
                                    // Определяем класс для значения
                                    $class = 'text-danger';
                                    if ($percentComplete >= 75) $class = 'text-success';
                                    elseif ($percentComplete >= 50) $class = 'text-primary';
                                    elseif ($percentComplete >= 25) $class = 'text-warning';
                                    
                                    echo "<tr>
                                            <td>{$row['region']}</td>
                                            <td class='text-center'>{$row['complete_count']}</td>
                                            <td class='text-center {$class}'>{$percentComplete}%</td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик клика на аватар
    document.getElementById('avatar-wrapper').addEventListener('click', function() {
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm.style.display === 'none') {
            uploadForm.style.display = 'block';
            gsap.fromTo(uploadForm, {opacity: 0, y: -20}, {opacity: 1, y: 0, duration: 0.3});
        } else {
            gsap.to(uploadForm, {opacity: 0, y: -20, duration: 0.3, onComplete: function() {
                uploadForm.style.display = 'none';
            }});
        }
    });
    
    // Кнопка отмены загрузки
    document.getElementById('cancel-upload').addEventListener('click', function() {
        gsap.to(document.getElementById('upload-form'), {opacity: 0, y: -20, duration: 0.3, onComplete: function() {
            document.getElementById('upload-form').style.display = 'none';
            document.getElementById('input-file').value = '';
        }});
    });
    
    // Спиннер при загрузке
    document.getElementById('upload-button').addEventListener('click', function() {
        document.getElementById('spiner').style.display = 'inline-block';
    });
    
    // Обработка вкладок для администратора
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Активация кнопки
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Показ нужного контента
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            const targetTab = document.getElementById(tabName + '-tab');
            targetTab.style.display = 'block';
            
            // Анимация появления
            gsap.fromTo(targetTab, 
                {opacity: 0, y: 10}, 
                {opacity: 1, y: 0, duration: 0.3}
            );
        });
    });
    
    // Инициализация GSAP анимаций
    gsap.from('.info-card', {opacity: 0, y: 30, duration: 0.6, ease: 'power2.out'});
    gsap.from('.stats-card', {opacity: 0, y: 20, duration: 0.5, stagger: 0.1, delay: 0.3, ease: 'back.out(1.2)'});
});
</script>

<?php include(__DIR__ . '/../inc/foot.php'); ?>