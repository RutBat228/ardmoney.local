<?php
include "inc/head.php";
AutorizeProtect();
access();
animate();

// Инициализируем переменные
$view_complete = ''; // Инициализация переменной view_complete

// Определяем текущий год и месяц
$current_year = date('Y');
$current_month = date('m');

// Устанавливаем значения по умолчанию
$default_year = $current_year;
$default_month = $current_month;

// Проверяем наличие параметра 'older'
if (isset($_GET['older'])) {
    // Если 'older', показываем только прошлые годы
    $default_year = $current_year - 1; // Год по умолчанию - прошлый год
    $default_month = '12'; // Месяц по умолчанию - декабрь
}

// Проверяем наличие параметра 'date'
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    $date_current = $_GET['date'];
    list($selected_year, $selected_month) = explode('-', $date_current);
} else {
    $selected_year = $default_year;
    $selected_month = $default_month;
    $date_current = $selected_year . '-' . $selected_month;
}

// Форматируем месяц для отображения
$month = date_view($date_current);

?>
<head>
    <title>Монтажи - <?=$month?></title>
</head>

<?php
// Заменяем старую логику удаления
if (isset($_GET['delete'])) {
    delete_mon();
}

if (isset($_GET['complete'])) {
    $view_complete = " AND `status` = '0'";
}

if (isset($_GET['id']) && $_GET['id'] == "ok") {
    alrt("Успешно удаленно", "success", "2");
}

// Добавить после инициализации переменных
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    echo '<div class="alert text-center alert-success alert-dismissible fade show" role="alert" id="successAlert">
            Монтаж успешно подтвержден!
          </div>';
    
    echo '<script>
        const alert = document.getElementById("successAlert");
        const bsAlert = new bootstrap.Alert(alert);
        setTimeout(() => {
            bsAlert.close();
        }, 2000);
    </script>';
}

?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark nav-custom" style="padding: 0;">
  <div class="container-fluid" style="background: #00000059; padding: 0.75rem 0.25rem;">
    <?php if ($usr['admin'] == 1): ?>
      <div class="d-flex justify-content-center align-items-center gap-3 w-100">
        <!-- Селект года -->
        <select class="form-select form-select-sm" id="year" name="year" style="width: auto;" onchange="loadArchiveData()">
          <?php
          $start_year = 2022;
          $end_year = $current_year;
          for ($year = $start_year; $year <= $end_year; $year++) {
              $selected = (isset($_GET['older']) && $year == $current_year - 1) || (!isset($_GET['older']) && $year == $selected_year) ? 'selected' : '';
              echo "<option value=\"$year\" $selected>$year</option>";
          }
          ?>
        </select>
        <!-- Селект месяца -->
        <select class="form-select form-select-sm" id="month" name="month" style="width: auto;" onchange="loadArchiveData()">
          <?php
          $months = [
              '01' => 'Январь',
              '02' => 'Февраль',
              '03' => 'Март',
              '04' => 'Апрель',
              '05' => 'Май',
              '06' => 'Июнь',
              '07' => 'Июль',
              '08' => 'Август',
              '09' => 'Сентябрь',
              '10' => 'Октябрь',
              '11' => 'Ноябрь',
              '12' => 'Декабрь',
          ];
          foreach ($months as $key => $name) {
              $selected = $key == $selected_month ? 'selected' : '';
              echo "<option value=\"$key\" $selected>$name</option>";
          }
          ?>
        </select>
        <!-- Чекбокс администратора -->
        <?php admin_checkbox($usr['id']); ?>
      </div>
    <?php else: ?>
      <div class="d-flex justify-content-center align-items-center gap-3 w-100">
        <!-- Селекты для обычного пользователя -->
        <select class="form-select form-select-sm" id="year" name="year" style="width: auto;" onchange="loadArchiveData()">
          <?php
          $start_year = 2022;
          $end_year = $current_year;
          for ($year = $start_year; $year <= $end_year; $year++) {
              $selected = (isset($_GET['older']) && $year == $current_year - 1) || (!isset($_GET['older']) && $year == $selected_year) ? 'selected' : '';
              echo "<option value=\"$year\" $selected>$year</option>";
          }
          ?>
        </select>
        <select class="form-select form-select-sm" id="month" name="month" style="width: auto;" onchange="loadArchiveData()">
          <?php
          $months = [
              '01' => 'Январь',
              '02' => 'Февраль',
              '03' => 'Март',
              '04' => 'Апрель',
              '05' => 'Май',
              '06' => 'Июнь',
              '07' => 'Июль',
              '08' => 'Август',
              '09' => 'Сентябрь',
              '10' => 'Октябрь',
              '11' => 'Ноябрь',
              '12' => 'Декабрь',
          ];
          foreach ($months as $key => $name) {
              $selected = $key == $selected_month ? 'selected' : '';
              echo "<option value=\"$key\" $selected>$name</option>";
          }
          ?>
        </select>
      </div>
    <?php endif; ?>
  </div>
</nav>


<!-- <div style="width: 100%;" class="btn-group" role="group" aria-label="Basic outlined example">
    <a href="montaj.php" class="btn btn-success btn-lg green_button">Добавить монтаж</a>
</div> -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.min.js"></script>
<div class="input-group">
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
    let currentMonth = currentDate.getMonth() + 1; // +1 так как getMonth() возвращает 0-11

    // Проверяем наличие параметра "older" в URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('older')) {
        currentYear -= 1; // Если 'older', используем прошлый год
        currentMonth = 12; // И месяц декабрь
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
    
    // Показываем вращающуюся картинку по центру экрана
    archiveContent.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
            <img src="img/baza.png" style="width: 50px; animation: flipCoin 1s infinite linear;">
        </div>`;
    
    // Подготовка данных для передачи
    let requestData = {
        date: year + '-' + month
    };
    // Проверка наличия current_user в GET-параметрах
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('current_user')) {
        requestData.current_user = urlParams.get('current_user');
    }
    
    // AJAX-запрос
    $.ajax({
        url: 'obr_index.php',
        method: 'GET',
        data: requestData,
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
