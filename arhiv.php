<?php
include "inc/head.php";
AutorizeProtect();
access();
animate();

?>

<head>
    <title>Список домов архив</title>

</head>
<?php
// Инициализируем переменные
$view_complete = ''; // Инициализация переменной view_complete

// Получаем текущий год и месяц
$current_year = date('Y');
$current_month = date('m');

// Если месяц январь, берем декабрь предыдущего года
if ($current_month == '01') {
    $default_year = $current_year - 1;
    $default_month = '12';
} else {
    $default_year = $current_year;
    $default_month = str_pad($current_month - 1, 2, '0', STR_PAD_LEFT);
}

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    $date_current = $_GET['date'];
    list($selected_year, $selected_month) = explode('-', $date_current);
} else {
    $selected_year = $default_year;
    $selected_month = $default_month;
    $date_current = $selected_year . '-' . $selected_month;
}

$month = date_view($date_current);

// Проверяем параметр delete и наличие id
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = base64_decode($_GET['delete']); // Декодируем id
    if (is_numeric($id)) { // Проверяем что id это число
        delete_mon($id);
    }
}

if (isset($_GET['complete'])) {
    $view_complete = " AND `status` = '0'";
}

if (isset($_GET['id']) && $_GET['id'] == "ok") {
    alrt("Успешно удаленно", "success", "2");
}

?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="padding: 0;">
    <div class="container-fluid" style="background: #00000070;">
        <a class="navbar-brand" href="#"></a>
        <div class="navbar-collapse" id="navbarNavDarkDropdown">
            <ul class="navbar-nav rut_nav">
                <?php
                if (!empty(htmlentities($_COOKIE['user']))) {
                ?>
                    <ul style="float: right;">
                        <li>
                            <a href="user.php">
                                <img src="/img/home.png" style="width: 40px;padding-bottom: 7px;">
                            </a>
                        </li>
                    </ul>
                <?php
                } ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-3 mb-3">
    <div class="d-flex flex-nowrap gap-2">
        <div class="d-flex align-items-center">
            <label class="me-2" for="year">Год:</label>
            <select class="form-select form-select-sm" id="year" name="year" style="width: auto;" onchange="loadArchiveData()">
                <option value="2022">2022</option>
                <option value="2023">2023</option>
                <option value="2024">2024</option>
            </select>
        </div>

        <div class="d-flex align-items-center">
            <label class="mx-2" for="month">Месяц:</label>
            <select class="form-select form-select-sm" id="month" name="month" style="width: auto;" onchange="loadArchiveData()">
                <option value="01">Январь</option>
                <option value="02">Февраль</option>
                <option value="03">Март</option>
                <option value="04">Апрель</option>
                <option value="05">Май</option>
                <option value="06">Июнь</option>
                <option value="07">Июль</option>
                <option value="08">Август</option>
                <option value="09">Сентябрь</option>
                <option value="10">Октябрь</option>
                <option value="11">Ноябрь</option>
                <option value="12">Декабрь</option>
            </select>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/mark.js/7.0.0/jquery.mark.min.js"></script>
<div class="input-group mt-3">
    <span class="input-group-text">Поиск</span>
    <input id="spterm" type="text" aria-label="адрес" class="form-control" oninput="liveSearch()">
</div>

<div id="archiveContent">
    <!-- Здесь будет контент -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Получаем текущий год и месяц
    let currentDate = new Date();
    let currentYear = currentDate.getFullYear();
    let currentMonth = currentDate.getMonth() + 1;
    
    // Если январь, берем декабрь предыдущего года
    if (currentMonth === 1) {
        currentYear--;
        currentMonth = 12;
    } else {
        currentMonth--;
    }
    
    // Устанавливаем значения в селекты
    document.getElementById('year').value = currentYear.toString();
    document.getElementById('month').value = String(currentMonth).padStart(2, '0');
    
    // Загружаем данные
    loadArchiveData();
});

function loadArchiveData() {
    let year = document.getElementById('year').value;
    let month = document.getElementById('month').value;
    let archiveContent = document.getElementById('archiveContent');
    
    // Показываем индикатор загрузки
    archiveContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Загрузка...</span></div></div>';
    
    // Используем jQuery для AJAX запроса
    $.ajax({
        url: 'arhiv_data.php',
        method: 'GET',
        data: {
            date: year + '-' + month
        },
        success: function(response) {
            archiveContent.innerHTML = response;
        },
        error: function() {
            archiveContent.innerHTML = '<div class="alert alert-danger">Ошибка загрузки данных</div>';
        }
    });
}

function liveSearch() {
    let searchTerm = document.getElementById('spterm').value.toLowerCase();
    let items = document.querySelectorAll('#skrivat');
    
    items.forEach(item => {
        let searchValue = item.querySelector('.search_view').getAttribute('data-value').toLowerCase();
        if (searchValue.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php
include 'inc/foot.php';
?>
