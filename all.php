<?php
include "inc/head.php";
AutorizeProtect();
global $usr;
global $connect;
?>

<head>
    <title>Список домов</title>
    <!-- Добавляем Bootstrap CSS и JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<style>
.hero-section {
    min-height: calc(100vh - 56px);
    padding: 2rem 0;
    background: linear-gradient(135deg, 
        #004d40 0%,
        #00695c 25%,
        #2e7d32 50%,
        #558b2f 75%,
        #33691e 100%
    );
}

.hero-section::before,
.hero-section::after {
    content: '$';
    position: absolute;
    font-weight: bold;
    color: #fff;
    opacity: 0.05;
}

.hero-section::before {
    font-size: 400px;
    top: -100px;
    right: -100px;
    transform: rotate(-15deg);
}

.hero-section::after {
    font-size: 300px;
    bottom: -100px;
    left: -50px;
    transform: rotate(15deg);
}

.list-group-item {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    width: 100%;
}

.list-group-item:hover {
    background: rgba(255, 255, 255, 0.8);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.list-group-item a {
    color: #333;
    text-decoration: none;
    display: block;
    padding: 0.5rem;
    width: 100%;
}

.text-danger {
    color: #dc3545 !important;
}

.btn-close {
    opacity: 0.5;
}

.btn-close:hover {
    opacity: 1;
}

.btn-group .btn {
    margin: 0 2px;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #d39e00;
}

.card.bg-transparent {
    width: 100%;
}

.container, 
.container-fluid, 
.container-lg, 
.container-md, 
.container-sm, 
.container-xl, 
.container-xxl {
    width: 100% !important;
    padding-right: 0 !important;
    padding-left: 0 !important;
    margin-right: 0 !important;
    margin-left: 0 !important;
}
.container{
    margin-top: -4rem;

}
.fas {
    font-weight: bold;
}

[style*="color: forestgreen"] {
    color: #2e7d32 !important;
    font-weight: 500;
}

.card {
    position: initial;
    display: block;
    flex-direction: initial;
    min-width: initial;
    word-wrap: initial;
    background-color: initial;
    background-clip: initial;
    border: none;
    border-radius: 0;
}

/* Дополнительные стили для модального окна */
.modal-content {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.modal-header {
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}

.modal-footer {
    border-top: none;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
}

.toast-header {
    border-radius: 8px 8px 0 0;
}
</style>

<?php
// Проверяем показывать все адреса или только для региона
if (!isset($_GET['all'])) {
    if ($usr['region'] == "Сварочный отдел") {
    } else {
        button('<a href="/navigard/all.php?all" class="btn bg-warning">Посмотреть все адреса</a>');
    }
} else {
    if ($usr['region'] == "Сварочный отдел") {
    } else {
        button("<a href='/navigard/all.php' class='btn bg-warning btn-block'>Посмотреть все адреса: {$usr['region']}</a>");
    }
}

if (isset($_GET['id']) && $_GET['id'] == "ok") {
    alrt("Успешно удаленно", "success", "2");
}

// Пагинация
$pageno = isset($_GET['pageno']) ? h($_GET['pageno']) : 1;
$size_page = 40;
$offset = ($pageno - 1) * $size_page;

$adrs = isset($_GET['adress']) ? h($_GET['adress']) : '';
$tech = isset($_GET['tech']) ? h($_GET['tech']) : '';

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

<div class="hero-section">
    <div class="container">
        <!-- Кнопки фильтров -->
        <div class="mb-4">

        </div>

        <!-- Список адресов -->
        <div class="card bg-transparent">
            <div class="card-body">
                <ul class="list-group">
                    <?php while ($row = mysqli_fetch_array($res_data)): 
                        $color = $row['new'] == 1 ? 'text-danger' : '';
                        $complete_color = $row['complete'] ? 'style="color: forestgreen;"' : '';
                        $text = $row['new'] == 1 ? 'NEW' : '';
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="/navigard/result.php?adress=<?= $row['adress'] ?>" 
                               class="<?= $color ?>"
                               <?= $complete_color ?>>
                                <?= $text ?> <?= $row['adress'] ?>
                            </a>
                            
                            <?php if ($usr['region'] == $row['region'] || $usr['admin'] == '1'): ?>
                                <button onclick="startdel(<?= $row['id'] ?>)" 
                                        class="btn-close" 
                                        aria-label="Close">
                                </button>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

        <!-- Пагинация -->
        <div class="d-flex justify-content-center mt-4">
            <div class="btn-group">
                <a href="/navigard/all.php?pageno=1<?= $split.$types.$all ?>" 
                   class="btn btn-warning">1</a>
                
                <a href="<?= ($pageno <= 1) ? '#' : "/navigard/all.php?pageno=".($pageno-1).$split.$types.$all ?>"
                   class="btn btn-warning <?= ($pageno <= 1) ? 'disabled' : '' ?>">
                    &larr;
                </a>
                
                <button class="btn btn-warning" disabled><?= $pageno ?></button>
                
                <a href="<?= ($pageno >= $total_pages) ? '#' : "/navigard/all.php?pageno=".($pageno+1).$split.$types.$all ?>"
                   class="btn btn-warning <?= ($pageno >= $total_pages) ? 'disabled' : '' ?>">
                    &rarr;
                </a>
                
                <a href="/navigard/all.php?pageno=<?= $total_pages.$split.$types.$all ?>"
                   class="btn btn-warning">
                    <?= $total_pages ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function startdel(id) {
    // Создаем модальное окно
    const modalHtml = `
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">Подтверждение удаления</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Вы действительно хотите удалить этот адрес?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete(${id})">Удалить</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Добавляем модальное окно в body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Инициализируем и показываем ��одальное окно
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();

    // Удаляем модальное окно после закрытия
    document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

function confirmDelete(id) {
    // Закрываем модальное окно
    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
    
    // Показываем уведомление об успешном удалении
    const toast = `
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Удаление</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Адрес успешно удален
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toast);
    
    // Изменен пу��ь с delete.php на del.php
    setTimeout(() => {
        window.location.href = '/navigard/del.php?id=' + id;
    }, 1000);
}
</script>

<?php include 'inc/foot.php'; ?>
