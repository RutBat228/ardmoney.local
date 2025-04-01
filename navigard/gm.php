<?php
include(__DIR__ . '/../inc/function.php');
include(__DIR__ . '/../inc/style.php');

?>
<!-- Font Awesome 6.2.1 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
<!-- Подключаем GSAP для анимаций -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
<!-- Внешний CSS файл вместо встроенных стилей -->
<link rel="stylesheet" href="css/gm.css">

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

class StatisticsManager {
    private $connect;
    private $user;
    
    public function __construct($connect, $user) {
        $this->connect = $connect;
        $this->user = $user;
    }
    
    public function render() {
        if ($this->user['admin'] !== '1') {
            echo '<div class="container-fluid p-0 bg-white min-vh-100 d-flex flex-column">
                    <div class="container py-4 flex-grow-1">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card shadow-sm border-0 rounded-4">
                                    <div class="card-body p-5 text-center">
                                        <div class="mb-4 text-danger">
                                            <i class="fa-solid fa-triangle-exclamation fa-4x"></i>
                                        </div>
                                        <h3 class="mb-3">Доступ запрещен</h3>
                                        <p class="text-muted mb-4">У вас нет прав для просмотра этой страницы</p>
                                        <a href="index.php" class="btn btn-primary">
                                            <i class="fa-solid fa-home me-2"></i>Вернуться на главную
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
                    
            include(__DIR__ . '/../inc/foot.php');
            exit;
        }
        
        echo '<div class="container-fluid p-0 bg-white min-vh-100 d-flex flex-column">
                <div class="page-header bg-primary text-white p-3 text-center position-relative mb-3">
                    <h1 class="h3 mt-1 mb-2 fw-bold">Статистика готовности домов</h1>
                </div>
                <div class="container py-3 flex-grow-1" style="padding-bottom: 6rem !important;">';
        
        $this->renderStatisticsSummary();
        $this->renderStatisticsByRegion();
        
        echo '</div>
              </div>';
    }
    
    private function renderStatisticsSummary() {
        // Получаем общие статистические данные
        $query = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN new = 1 THEN 1 ELSE 0 END) as new_count,
                        SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) as complete_count
                  FROM navigard_adress";
        $result = $this->connect->query($query);
        $stats = $result->fetch_assoc();
        
        // Вычисляем процент готовности
        $total = $stats['total'] > 0 ? $stats['total'] : 1; // Избегаем деления на ноль
        $percentComplete = round(($stats['complete_count'] / $total) * 100);
        $percentIncomplete = 100 - $percentComplete;
        
        echo '<div class="row stats-row">
                <div class="col-md-4 col-4 mb-4 mb-md-0">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-muted mb-3">Всего домов</h5>
                            <div class="display-4 fw-bold">' . $stats['total'] . '</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-4 mb-4 mb-md-0">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-muted mb-3">Новые дома</h5>
                            <div class="display-4 fw-bold text-primary">' . $stats['new_count'] . '</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-muted mb-3">Готовые дома</h5>
                            <div class="display-4 fw-bold text-success">' . $stats['complete_count'] . '</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0 text-primary">Общий прогресс</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: ' . $percentComplete . '%;" 
                                     aria-valuenow="' . $percentComplete . '" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    ' . $percentComplete . '% готово
                                </div>
                                <div class="progress-bar bg-danger" 
                                     role="progressbar" 
                                     style="width: ' . $percentIncomplete . '%;" 
                                     aria-valuenow="' . $percentIncomplete . '" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    ' . $percentIncomplete . '% не готово
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
    }
    
    private function renderStatisticsByRegion() {
        echo '<div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 text-primary">Статистика по регионам</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%"><span>Регион</span></th>
                                <th style="width: 15%" class="text-center"><span>Всего</span></th>
                                <th style="width: 15%" class="text-center"><span>Новые</span></th>
                                <th style="width: 15%" class="text-center"><span>Готовые</span></th>
                                <th style="width: 25%" class="text-center"><span>Готовность</span></th>
                            </tr>
                        </thead>
                        <tbody>';
                        
        // Получаем все регионы из таблицы регионов
        $query = "SELECT name as region FROM navigard_region ORDER BY name";
        $regionsResult = $this->connect->query($query);
        $regions = [];
        
        while ($row = $regionsResult->fetch_assoc()) {
            $regions[$row['region']] = [
                'region' => $row['region'],
                'total' => 0,
                'new_count' => 0,
                'complete_count' => 0
            ];
        }
        
        // Получаем статистику по регионам с домами
        $query = "SELECT region, 
                        COUNT(*) as total, 
                        SUM(CASE WHEN new = 1 THEN 1 ELSE 0 END) as new_count, 
                        SUM(CASE WHEN complete = 1 THEN 1 ELSE 0 END) as complete_count 
                  FROM navigard_adress 
                  GROUP BY region";
        $result = $this->connect->query($query);
        
        // Заполняем данные для регионов, в которых есть дома
        while ($row = $result->fetch_assoc()) {
            // Если регион есть в таблице домов, но отсутствует в регионах, добавим его
            if (!isset($regions[$row['region']])) {
                $regions[$row['region']] = [
                    'region' => $row['region'],
                    'total' => 0,
                    'new_count' => 0,
                    'complete_count' => 0
                ];
            }
            
            // Обновляем статистику
            $regions[$row['region']]['total'] = $row['total'];
            $regions[$row['region']]['new_count'] = $row['new_count'];
            $regions[$row['region']]['complete_count'] = $row['complete_count'];
        }
        
        // Сортируем регионы для отображения
        ksort($regions);
        
        // Выводим все регионы
        foreach ($regions as $row) {
            $total = $row['total'] > 0 ? $row['total'] : 1; // Избегаем деления на ноль
            $percentComplete = round(($row['complete_count'] / $total) * 100);
            
            // Если нет домов, процент готовности будет 0
            if ($row['total'] == 0) {
                $percentComplete = 0;
            }
            
            // Определяем класс для процента готовности в зависимости от значения
            $percentClass = '';
            if ($percentComplete >= 75) {
                $percentClass = 'text-success fw-bold';
            } elseif ($percentComplete >= 50) {
                $percentClass = 'text-primary fw-bold';
            } elseif ($percentComplete >= 25) {
                $percentClass = 'text-warning fw-bold';
            } else {
                $percentClass = 'text-danger fw-bold';
            }
            
            echo '<tr>
                    <td class="fw-bold">' . $row['region'] . '</td>
                    <td class="text-center">' . $row['total'] . '</td>
                    <td class="text-center">' . $row['new_count'] . '</td>
                    <td class="text-center">' . $row['complete_count'] . '</td>
                    <td class="' . $percentClass . ' text-center">' . $percentComplete . '%</td>
                  </tr>';
        }
        
        echo '</tbody>
              </table>
              </div>
              </div>';
    }
}

// Добавляем стиль для переопределения цвета primary
echo '<!-- Стили перенесены в файл css/gm.css -->';

$stats = new StatisticsManager($connect, $usr);
$stats->render();

include(__DIR__ . '/../inc/foot.php');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
// GSAP анимации для шапки и элементов страницы
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gsap !== 'undefined') {
        // Задержка перед началом анимации для гарантии полной загрузки
        setTimeout(function() {
            // Анимация шапки
            gsap.fromTo('.page-header', 
                { autoAlpha: 0, y: -20 }, 
                { autoAlpha: 1, y: 0, duration: 0.5, ease: 'power2.out' }
            );
            
            // Анимация карточек статистики 
            gsap.fromTo('.stats-row .card', 
                { autoAlpha: 0, y: 20, scale: 0.95 }, 
                { 
                    autoAlpha: 1, 
                    y: 0, 
                    scale: 1, 
                    duration: 0.5, 
                    delay: 0.3, 
                    ease: 'back.out(1.5)',
                    stagger: 0.1 
                }
            );
            
            // Анимация карточек с прогрессом и таблицами
            gsap.fromTo('.progress, .table-responsive', 
                { autoAlpha: 0, scale: 0.98 }, 
                { 
                    autoAlpha: 1, 
                    scale: 1, 
                    duration: 0.4, 
                    delay: 0.6, 
                    ease: 'power2.out' 
                }
            );
        }, 100);
    }
});
</script>