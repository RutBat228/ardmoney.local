<?php
include_once(dirname(__FILE__) . "/../inc/function.php");
include_once(dirname(__FILE__) . "/../inc/style.php");
AutorizeProtect();
global $usr;
global $connect;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список домов</title>
    <!-- Подключаем Bootstrap и Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Подключаем GSAP для анимаций -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>

<style>
    body {
        background-color: #f8f9fa;
    }
    
    .houses-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .houses-header {
        background-color: #424e37;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .houses-title {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }
    
    .filter-bar {
        background-color: white;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .houses-list {
        background-color: white;
        border-radius: 5px;
        padding: 5px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .house-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        transition: all 0.2s ease;
    }
    
    .house-item:hover {
        background-color: #f8f9fa;
    }
    
    .house-item:last-child {
        border-bottom: none;
    }
    
    .house-link {
    color: #333;
    text-decoration: none;
        flex-grow: 1;
    }
    
    .house-link:hover {
        color: #424e37;
    }
    
    .new-badge {
        background-color: #dc3545;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
        margin-right: 8px;
    }
    
    .complete-text {
        color: #28a745 !important;
        font-weight: 500;
    }
    
    .delete-btn {
        background: none;
        border: none;
        color: #dc3545;
        opacity: 0.7;
        transition: opacity 0.2s ease;
        cursor: pointer;
        padding: 6px;
    }
    
    .delete-btn:hover {
        opacity: 1;
    }
    
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        margin-bottom: 3rem;
    width: 100%;
}

    .pagination {
        width: 100%;
        display: flex;
        justify-content: space-between;
    }
    
    .pagination .page-link {
        color: #424e37;
        border-color: #e9ecef;
        transition: all 0.2s ease;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #424e37;
        border-color: #424e37;
        color: white;
    }
    
    .pagination .page-link:hover {
        background-color: rgba(66, 78, 55, 0.1);
        border-color: #424e37;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #adb5bd;
        border-color: #e9ecef;
    }
    
    .btn-view-all {
    background-color: #ffc107;
        color: #333;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        font-weight: 500;
        transition: background-color 0.2s ease;
    }
    
    .btn-view-all:hover {
    background-color: #e0a800;
        color: #333;
    }
    
    /* Стили для уведомлений */
    .custom-toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .custom-toast {
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        color: white;
        min-width: 300px;
    }
    
    .custom-toast-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .custom-toast-body {
        font-size: 14px;
    }
    
    .custom-toast-danger {
        background-color: #dc3545;
    }
    
    .custom-toast-success {
        background-color: #28a745;
    }
    
    .custom-toast-primary {
        background-color: #0d6efd;
    }
    
    /* Стили для шапки профиля (аналогично user.php и index.php) */
    .profile-header {
        background-color: #434e38 !important;
        position: relative;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        width: 100%;
        padding: 20px;
        text-align: center;
        color: white;
    }
    
    .profile-header::after {
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
    
    .profile-header .badge {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .profile-header .badge:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .btn-view-all-header {
        background-color: white;
        color: #434e38;
    border: none;
        padding: 8px 16px;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-view-all-header:hover {
        background-color: rgba(255, 255, 255, 0.9);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    @media (max-width: 768px) {
        .houses-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .houses-title {
            margin-bottom: 10px;
        }
        
        .custom-toast {
            min-width: auto;
            width: 90%;
        }
}
</style>
</head>
<body>

<div class="container-fluid p-0 bg-white min-vh-100 d-flex flex-column">
    <!-- Шапка с заголовком и кнопкой -->
    <div class="profile-header bg-primary text-white p-3 text-center position-relative mb-3">
        <h1 class="h3 mt-1 mb-2 fw-bold">Список домов</h1>
<?php
// Проверяем показывать все адреса или только для региона
if (!isset($_GET['all'])) {
            if ($usr['region'] != "Сварочный отдел") {
                echo '<div class="d-flex justify-content-center mt-2">
                        <a href="/navigard/all.php?all" class="btn-view-all-header">
                            <i class="fas fa-globe me-2"></i>Все адреса
                        </a>
                      </div>';
    }
} else {
            if ($usr['region'] != "Сварочный отдел") {
                echo '<div class="d-flex justify-content-center mt-2">
                        <a href="/navigard/all.php" class="btn-view-all-header">
                            <i class="fas fa-filter me-2"></i>Только ' . $usr['region'] . '
                        </a>
                      </div>';
            }
        }
        ?>
    </div>

    <div class="container py-3 flex-grow-1" style="padding-bottom: 6rem !important;">
        <!-- Блок фильтров -->
        <div class="filter-bar">
            <div class="row g-2">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="adress" placeholder="Поиск по адресу..." class="form-control me-2" 
                               value="<?= isset($_GET['adress']) ? htmlspecialchars($_GET['adress']) : '' ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end">
                        <?php 
                        $tech = isset($_GET['tech']) ? $_GET['tech'] : '';
                        $all = isset($_GET['all']) ? '&all' : '';
                        ?>
                        <div class="btn-group w-100 justify-content-end">
                            <a href="/navigard/all.php?tech=complete<?= $all ?>" class="btn btn-sm btn-outline-success <?= $tech == 'complete' ? 'active' : '' ?>">
                                Готовые
                            </a>
                            <a href="/navigard/all.php?tech=pon<?= $all ?>" class="btn btn-sm btn-outline-primary <?= $tech == 'pon' ? 'active' : '' ?>">
                                GPON
                            </a>
                            <a href="/navigard/all.php?tech=ethernet<?= $all ?>" class="btn btn-sm btn-outline-info <?= $tech == 'ethernet' ? 'active' : '' ?>">
                                Ethernet
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Сообщение об успешном удалении
if (isset($_GET['id']) && $_GET['id'] == "ok") {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Дом успешно удалён
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
}

// Пагинация
        $pageno = isset($_GET['pageno']) ? (int)htmlspecialchars($_GET['pageno']) : 1;
$size_page = 40;
$offset = ($pageno - 1) * $size_page;

        $adrs = isset($_GET['adress']) ? htmlspecialchars($_GET['adress']) : '';
        $tech = isset($_GET['tech']) ? htmlspecialchars($_GET['tech']) : '';

// Формируем SQL запрос в зависимости от параметров
if (!empty($_GET['adress'])) {
    $sql = "SELECT * FROM `navigard_adress` WHERE adress LIKE '%$adrs%' ORDER BY `adress` LIMIT $offset, $size_page";
    $pages_sql = "SELECT COUNT(*) FROM `navigard_adress` WHERE adress LIKE '%$adrs%'";
    $split = "&adress=$adrs";
} else {
    $sql = "SELECT * FROM navigard_adress WHERE region LIKE '$usr[region]' ORDER BY `adress` LIMIT $offset, $size_page";
    $pages_sql = "SELECT COUNT(*) FROM `navigard_adress` WHERE region LIKE '$usr[region]'";
    $split = "&adress=$adrs";
    $types = isset($_GET['tech']) ? "&tech=$tech" : "";

    if (isset($_GET['all'])) {
        $sql = "SELECT * FROM navigard_adress ORDER BY `adress` LIMIT $offset, $size_page";
        $pages_sql = "SELECT COUNT(*) FROM `navigard_adress`";
        $split = "&adress=$adrs";
        $types = isset($_GET['tech']) ? "&tech=$tech" : "";
    }
}

// Фильтры по типу подключения
if ($tech == 'complete') {
    $sql = "SELECT * FROM `navigard_adress` WHERE complete LIKE '1' ORDER BY `adress` LIMIT $offset, $size_page";
    $pages_sql = "SELECT COUNT(*) FROM `navigard_adress` WHERE complete LIKE '1'";
    $split = "&adress=$adrs";
    $types = "&tech=$tech";
}

if ($tech == 'pon') {
    $sql = "SELECT * FROM `navigard_adress` WHERE pon LIKE 'Gpon' ORDER BY `adress` LIMIT $offset, $size_page";
    $pages_sql = "SELECT COUNT(*) FROM `navigard_adress` WHERE pon LIKE 'Gpon'";
    $split = "&adress=$adrs";
    $types = "&tech=$tech";
}

if ($tech == 'ethernet') {
    $sql = "SELECT * FROM `navigard_adress` WHERE pon LIKE 'Ethernet' ORDER BY `adress` LIMIT $offset, $size_page";
    $pages_sql = "SELECT COUNT(*) FROM `navigard_adress` WHERE pon LIKE 'Ethernet'";
    $split = "&adress=$adrs";
    $types = "&tech=$tech";
}

$result = mysqli_query($connect, $pages_sql);
$total_rows = mysqli_fetch_array($result)[0];
$total_pages = ceil($total_rows / $size_page);
$res_data = mysqli_query($connect, $sql);

$all = isset($_GET['all']) ? "&all" : "";
?>

        <!-- Список домов -->
        <div class="houses-list">
            <?php 
            if (mysqli_num_rows($res_data) > 0) {
                while ($row = mysqli_fetch_array($res_data)) {
                    $is_new = $row['new'] == 1;
                    $is_complete = $row['complete'] == 1;
                    
                    echo '<div class="house-item">';
                    echo '<a href="/navigard/result.php?adress_id=' . $row['id'] . '" class="house-link ' . ($is_complete ? 'complete-text' : '') . '">';
                    
                    if ($is_new) {
                        echo '<span class="new-badge">NEW</span>';
                    }
                    
                    echo htmlspecialchars($row['adress']);
                    echo '</a>';
                    
                    if ($usr['region'] == $row['region'] || $usr['admin'] == '1') {
                        echo '<button onclick="deleteHouse(' . $row['id'] . ', \'' . htmlspecialchars($row['adress']) . '\')" class="delete-btn" title="Удалить">';
                        echo '<i class="fas fa-trash"></i>';
                        echo '</button>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="text-center py-4 text-muted">Нет домов для отображения</div>';
            }
            ?>
        </div>

        <!-- Пагинация -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <nav aria-label="Навигация по страницам">
                <ul class="pagination">
                    <li class="page-item <?= ($pageno <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($pageno <= 1) ? '#' : "/navigard/all.php?pageno=1".$split.$types.$all ?>" aria-label="Первая">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <li class="page-item <?= ($pageno <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($pageno <= 1) ? '#' : "/navigard/all.php?pageno=".($pageno-1).$split.$types.$all ?>" aria-label="Предыдущая">
                            <span aria-hidden="true">&lsaquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $pageno - 2);
                    $end_page = min($total_pages, $pageno + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?= ($pageno == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="/navigard/all.php?pageno=<?= $i.$split.$types.$all ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($pageno >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($pageno >= $total_pages) ? '#' : "/navigard/all.php?pageno=".($pageno+1).$split.$types.$all ?>" aria-label="Следующая">
                            <span aria-hidden="true">&rsaquo;</span>
                        </a>
                    </li>
                    
                    <li class="page-item <?= ($pageno >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($pageno >= $total_pages) ? '#' : "/navigard/all.php?pageno=".$total_pages.$split.$types.$all ?>" aria-label="Последняя">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// GSAP анимации для шапки
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gsap !== 'undefined') {
        // Задержка перед началом анимации для гарантии полной загрузки
        setTimeout(function() {
            // Анимация шапки
            gsap.fromTo('.profile-header', 
                { autoAlpha: 0, y: -20 }, 
                { autoAlpha: 1, y: 0, duration: 0.5, ease: 'power2.out' }
            );
            
            // Анимация кнопки
            gsap.fromTo('.btn-view-all-header', 
                { autoAlpha: 0, scale: 0.8 }, 
                { autoAlpha: 1, scale: 1, duration: 0.4, delay: 0.3, ease: 'back.out(1.5)' }
            );
        }, 100);
    }
});

function deleteHouse(id, adress) {
    const confirmDelete = confirm(`Вы уверены, что хотите удалить дом по адресу: ${adress}?`);
    if (confirmDelete) {
        // Показываем индикатор загрузки
        showNotification(true, 'Удаление дома...', true);
        
        // AJAX запрос к обработчику
        fetch('obr_result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=delete&id=${id}&adress=${encodeURIComponent(adress)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(true, data.message || 'Дом успешно удален');
                // Перезагружаем страницу после короткой задержки
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(false, data.error || 'Ошибка при удалении');
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            showNotification(false, 'Произошла ошибка при удалении');
        });
    }
}

// Функция отображения уведомлений
function showNotification(isSuccess, message, isLoading = false) {
    // Удаляем все существующие уведомления
    const existingToasts = document.querySelectorAll('.custom-toast-container');
    existingToasts.forEach(toast => toast.remove());
    
    // Определяем тип уведомления
    let toastClass = isSuccess ? 'custom-toast-success' : 'custom-toast-danger';
    let icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    if (isLoading) {
        toastClass = 'custom-toast-primary';
        icon = 'fa-spinner fa-spin';
    }
    
    // Создаем контейнер для уведомления
    const toastContainer = document.createElement('div');
    toastContainer.className = 'custom-toast-container';
    
    const toast = document.createElement('div');
    toast.className = `custom-toast ${toastClass}`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    const toastBody = document.createElement('div');
    toastBody.className = 'custom-toast-body';
    
    // Добавляем содержимое
    toastBody.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${icon} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    toast.appendChild(toastBody);
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    // Удаляем уведомление через 3 секунды, если это не загрузка
    if (!isLoading) {
    setTimeout(() => {
            toastContainer.remove();
        }, 3000);
    }
}
</script>

</body>
</html>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/inc/foot.php"); ?>
