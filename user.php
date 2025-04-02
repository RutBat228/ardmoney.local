<?php
session_start();
include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php");

// Отладочный лог для PHP
error_log("PHP: Начало обработки user.php");

// Проверка функций авторизации
AutorizeProtect();
error_log("PHP: После AutorizeProtect()");
access();
error_log("PHP: После access()");

global $connect;
global $usr;

const DAYS_BEFORE_ZP_SHOW = 7;

$current_year  = date('Y');
$current_month = date('m');

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    error_log("PHP: Проверка параметра date: " . $_GET['date']);
    $date_parts = explode('-', $_GET['date']);
    $display_year = (int)$date_parts[0];
    $month_num = $date_parts[1];
    $month = date_view($_GET['date']);
    $date_blyat = $_GET['date'];
    error_log("PHP: Параметр date корректен: " . $date_blyat . ", display_year: " . $display_year);
} else {
    if (isset($_GET['date'])) {
        error_log("PHP: Параметр date некорректен, перенаправление на текущий месяц");
        if (!isset($_GET['redirected'])) {
            header("Location: user.php?date=" . date("Y-m") . "&redirected=1");
            exit();
        }
    }
    $display_year = isset($_GET['older']) ? $current_year - 1 : $current_year;
    $month = month_view(date('m'));
    $date_blyat = $display_year . '-' . date('m');
    error_log("PHP: Параметр date не указан или некорректен, используется: display_year=" . $display_year . ", date_blyat=" . $date_blyat);
}

function getUserImage(string $username): string {
    $customImage = "img/{$username}.png";
    return file_exists($customImage) ? $customImage : "img/user/user_logo.webp?123";
}

function getDejurstvaCount($user_id, $month) {
    global $connect;
    $stmt = $connect->prepare("SELECT dejurstva FROM user_finance WHERE user_id = ? AND month = ?");
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['dejurstva'] : 0;
}

$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

// Проверка непрочитанных ответов от админа
$unreadAdminReplies = 0;
if (!$isAdmin && !$isOwner && !$isSuperAdmin) {
    $stmt = $connect->prepare("
        SELECT COUNT(*) as unread_count
        FROM chat_messages cm1
        INNER JOIN chat_messages cm2 ON cm1.id = cm2.reply_to_id
        LEFT JOIN message_views mv ON cm2.id = mv.message_id AND mv.user_id = ?
        WHERE cm1.username = ? 
        AND cm2.is_admin = 1 
        AND mv.message_id IS NULL
    ");
    $stmt->bind_param('ss', $usr['name'], $usr['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $unreadAdminReplies = $row['unread_count'];
    error_log("PHP: Непрочитанных ответов от админа для {$usr['name']}: $unreadAdminReplies");
}

// Логика для настроек
$link_date = $_GET['date'] ?? $date_blyat;
$finance_query = $connect->prepare("SELECT current_salary, official_employment FROM user_finance WHERE user_id = ? AND month = ?");
$finance_query->bind_param("is", $usr['id'], $link_date);
$finance_query->execute();
$finance = $finance_query->get_result()->fetch_assoc();

if (!$finance) {
    $default_salary = 24000;
    $default_employment = 'Нет';
    $stmt = $connect->prepare("INSERT INTO user_finance (user_id, month, current_salary, official_employment, last_update) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $usr['id'], $link_date, $default_salary, $default_employment);
    $stmt->execute();
    $finance = ['current_salary' => $default_salary, 'official_employment' => $default_employment];
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
</head>

<body style="background: #ffffff url(img/background.webp) repeat;">
<div class="container-sm">
    <nav class="navbar navbar-expand-lg navbar-dark" style="border-radius: 0!important; padding-bottom: 0; background: #ffffff url(img/background.webp) repeat;">
        <div class="container" style="display: initial;">
            <div class="row">
                <div class="col-12">
                    <a class="navbar-brand" href="/index.php">
                        <img id="animated-example" class="mt-2 pidaras animated fadeOut" src="img/logo.webp?12w" alt="ArdMoney" height="90px">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main role="main" style="padding-bottom: 60px">
        <div style="min-height: calc(100vh - 9rem); padding: 0 0; background: #fff;" class="jumbotron">
            <div class="col-md-12 col-sm-12 mx-auto">
                <title>Страница пользователя - <?=$month?> <?=$display_year?></title>

                <?php if ($unreadAdminReplies > 0): ?>
                    <div class="alert alert-warning text-center" role="alert" style="margin-bottom: 0; padding: 5px;">
                        <i class="fa-solid fa-envelope me-2"></i>
                        У вас <?=$unreadAdminReplies?> непрочитанных сообщений в чате от админа! 
                        <a href="adm_chat.php" class="alert-link">Перейти в чат</a>
                    </div>
                <?php endif; ?>

                <div class="month-nav">
                    <?php
                    $prevMonth = date('Y-m', strtotime("$date_blyat -1 month"));
                    $nextMonth = date('Y-m', strtotime("$date_blyat +1 month"));
                    ?>
                    <button onclick="window.location.href='?date=<?=$prevMonth?>'"><i class="fa-solid fa-arrow-left"></i></button>
                    <div class="calendar-wrapper">
                        <div class="month-year" onclick="toggleCalendar(event)">
                            <?=$month?> <?=$display_year?>
                        </div>
                        <div class="calendar-container" id="calendarContainer" style="display: none;">
                            <div class="month-grid">
                                <?php
                                $months = [
                                    '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
                                    '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
                                    '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
                                    '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
                                ];
                                foreach ($months as $mnum => $mname): ?>
                                    <button class="month-btn" data-month="<?=$mnum?>"><?=$mname?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($nextMonth <= date('Y-m')): ?>
                        <button onclick="window.location.href='?date=<?=$nextMonth?>'"><i class="fa-solid fa-arrow-right"></i></button>
                    <?php else: ?>
                        <button disabled><i class="fa-solid fa-arrow-right"></i></button>
                    <?php endif; ?>
                </div>

                <div class="list-group">
                    <div class="list-group-item p-0 border-0">
                        <img class="mx-auto d-block w-100" src="<?= getUserImage($usr['name']); ?>" alt="Фото пользователя">
                        <?php if ($display_year < $current_year): ?>
                            <div class="alert alert-warning text-center" role="alert">
                                <strong>Внимание!</strong> Вы просматриваете данные за <?= $display_year ?> год
                            </div>
                        <?php endif; 
                        $currentDate  = new DateTime();
                        $monthNames   = [
                            'Январь' => 1, 'Февраль' => 2, 'Март' => 3, 'Апрель' => 4,
                            'Май' => 5, 'Июнь' => 6, 'Июль' => 7, 'Август' => 8,
                            'Сентябрь' => 9, 'Октябрь' => 10, 'Ноябрь' => 11, 'Декабрь' => 12
                        ];
                        $selectedMonth = $monthNames[$month] ?? 0;
                        $selectedYear  = intval($display_year);

                        $isCurrentMonth = ($selectedYear == $current_year && $selectedMonth == intval($current_month));
                        $showSalaryColumn = false;
                        if ($isCurrentMonth) {
                            $lastDayOfMonth = new DateTime("$current_year-$current_month-01");
                            $lastDayOfMonth->modify('last day of this month');
                            $daysUntilEnd = $currentDate->diff($lastDayOfMonth)->days;
                            $showSalaryColumn = ($daysUntilEnd <= DAYS_BEFORE_ZP_SHOW);
                        } else {
                            $showSalaryColumn = true;
                        }
                        ?>

                        <?php if ($usr['admin'] == "1" || $usr['name'] == "RutBat") : ?>
                        <?php else: ?>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table user-table text-center" style="font-size: 0.85rem; white-space: nowrap;">
                                <thead>
                                    <tr>
                                        <th>Техник</th>
                                        <th>Работы</th>
                                        <th>Монтажи</th>
                                        <?php if ($showSalaryColumn) : ?>
                                            <th>Зарплата</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $connect->prepare("SELECT * FROM `user` WHERE `region` = ? ORDER BY `id` DESC");
                                    $stmt->bind_param('s', $usr['region']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    while ($tech = $result->fetch_assoc()):
                                        if ($tech['rang'] === 'Мастер участка') continue;
                                    ?>
                                    <tr>
                                        <td class="adaptive-text">
                                            <a style="color: black;" href="index.php?current_user=<?= $tech['fio'] ?>"><?= $tech['fio'] ?></a>
                                        </td>
                                        <td class="adaptive-text"><?php num_montaj("$tech[fio]", "$month", $display_year); ?></td>
                                        <td class="adaptive-text"><?php summa_montaj("$tech[fio]", "$month", $display_year); ?> р.</td>
                                        <?php if ($showSalaryColumn) : ?>
                                            <td class="adaptive-text"><?php prim_zp("$tech[fio]", "$month", $display_year); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Кнопки в одном контейнере -->
                        <div class="action-buttons">
                            <?php 
                            $panelTitle = '';
                            if ($isOwner) {
                                $panelTitle = 'Панель владельца';
                            } elseif ($isSuperAdmin) {
                                $panelTitle = 'Панель суперадмина';
                            } elseif ($isAdmin) {
                                $panelTitle = 'Панель мастера';
                            }
                            if ($isOwner || $isSuperAdmin || $isAdmin): ?>
                                <a href="admin_sks.php" class="control-panel">
                                    <i class="fa-solid fa-cog me-2"></i> <?=$panelTitle?>
                                </a>
                            <?php endif; ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#settingsModal" data-user-id="<?= $usr['id'] ?>" data-month="<?= $link_date ?>" class="settings-button">
                                <i class="fa-solid fa-gear me-2"></i> Настройки
                            </a>
                            <a href="adm_chat.php" class="chat-button">
                                <i class="fa-solid fa-comments me-2"></i> Чат для обсуждения багов
                            </a>
                            <a href="ardmoney.apk" class="app-button">
                                <img src="img/android.png" style="width: 24px; margin-right: 8px;"> Приложение ArdMoney
                            </a>
                        </div>

                        <!-- Блок с логином и регионом -->
                        <div class="alert alert-info text-center" role="alert" style="padding: 0.5rem; font-size: 0.9rem;">
                            Логин: <b><?= $usr['name'] ?></b> | Регион: <span class="text-muted"><?= $usr['region'] ?></span>
                        </div>

                        <b>
                            <div class="d-grid gap-2">
                                <a href="/exit.php" class="btn btn-outline-success btn-sm">
                                    <i class="fa-solid fa-sign-out-alt me-2"></i> Выход
                                </a>
                            </div>
                        </b>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно для дежурств -->
    <div class="modal fade" id="dutyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Выбор дежурств для <span id="dutyUserFio"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="dutyCalendar"></div>
                    <div class="mt-3">
                        <label for="advanceInput" class="form-label">Аванс (руб):</label>
                        <input type="number" class="form-control" id="advanceInput" min="0" step="100" placeholder="Введите сумму аванса">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-primary" id="saveDuties">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для настроек -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Настройки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="salaryInput" class="form-label">Оклад (руб):</label>
                        <input type="number" class="form-control" id="salaryInput" min="0" step="100" value="<?= htmlspecialchars($finance['current_salary']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="employmentSelect" class="form-label">Офиц. трудоустройство:</label>
                        <select class="form-select" id="employmentSelect">
                            <option value="Да" <?= $finance['official_employment'] == 'Да' ? 'selected' : '' ?>>Да</option>
                            <option value="Нет" <?= $finance['official_employment'] == 'Нет' ? 'selected' : '' ?>>Нет</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-primary" id="saveSettings">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .month-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0;
        background: #30352d;
        color: white;
        padding: 5px 10px;
        border-radius: 0;
    }
    .month-nav button {
        background: #495057;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 15px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    .month-nav button:hover {
        background: #6c757d;
        transform: scale(1.05);
    }
    .month-nav button:disabled {
        background: #6c757d;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .month-year {
        font-size: 1rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 5px;
        transition: background 0.3s ease;
    }
    .month-year:hover {
        background: #495057;
    }
    .calendar-wrapper {
        position: relative;
    }
    .calendar-container {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 10px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        z-index: 1000;
        width: 250px;
    }
    .month-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    .month-btn {
        padding: 5px;
        background: #e9ecef;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    .month-btn:hover {
        background: #d1d5db;
    }
    .month-btn:disabled {
        background: #6c757d;
        opacity: 0.5;
        cursor: not-allowed;
    }
    .action-buttons {
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        margin: 10px 0;
    }
    .action-buttons a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .action-buttons a:last-child {
        border-bottom: none;
    }
    .action-buttons a:hover {
        filter: brightness(1.1);
    }
    .control-panel {
        background: linear-gradient(45deg, #ff416c, #ff4b2b);
    }
    .settings-button {
        background: linear-gradient(45deg, #007bff, #0056b3);
    }
    .chat-button {
        background: linear-gradient(45deg, #28a745, #218838);
    }
    .app-button {
        background: linear-gradient(45deg, #17a2b8, #138496);
    }
    .holiday-selected {
        background: linear-gradient(45deg, #28a745, #ffc107) !important;
        color: white;
    }
    .salary-block {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .salary-block:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .salary-block.no-edit {
        cursor: default;
    }
    .salary-block.no-edit:hover {
        transform: none;
        box-shadow: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Страница загружена");

    const calendarContainer = document.getElementById('calendarContainer');
    const monthButtons = document.querySelectorAll('.month-btn');

    const fixedYear = <?php echo $display_year; ?>;
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;

    window.toggleCalendar = function(event) {
        if (event) event.stopPropagation();
        console.log("Тоггл календаря");
        calendarContainer.style.display = (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') ? 'block' : 'none';
    };

    document.addEventListener('click', function(event) {
        const calendarWrapper = document.querySelector('.calendar-wrapper');
        if (!calendarWrapper.contains(event.target)) {
            console.log("Клик вне календаря");
            calendarContainer.style.display = 'none';
        }
    });

    monthButtons.forEach(button => {
        const monthNum = parseInt(button.getAttribute('data-month'));
        if (fixedYear > currentYear || (fixedYear === currentYear && monthNum > currentMonth)) {
            button.disabled = true;
        }
    });

    monthButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
            console.log("Выбор месяца");
            const selectedMonth = this.getAttribute('data-month');
            if (selectedMonth) {
                console.log(`Перенаправление на ?date=${fixedYear}-${selectedMonth}`);
                window.location.href = `?date=${fixedYear}-${selectedMonth}`;
            }
            calendarContainer.style.display = 'none';
        });
    });

    // Логика календаря дежурств
    let currentUserId;
    let dutyMonth;
    let selectedDates = new Set();
    let holidays = new Set();
    let currentAdvance = 0;

    document.querySelectorAll('[data-bs-target="#dutyModal"]').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            currentUserId = this.getAttribute('data-user-id');
            dutyMonth = this.getAttribute('data-month');
            const userFio = this.closest('tr').querySelector('td:first-child a').textContent;
            document.getElementById('dutyUserFio').textContent = userFio;
            loadDutiesAndHolidays(currentUserId, dutyMonth);
        });
    });

    function loadDutiesAndHolidays(userId, month) {
        Promise.all([
            fetch(`get_duties.php?user_id=${userId}&month=${month}`).then(res => res.json()).catch(err => ({ duties: [] })),
            fetch(`get_holidays.php?month=${month}`).then(res => res.json()).catch(err => ({ holidays: [] })),
            fetch(`get_finance.php?user_id=${userId}&month=${month}`).then(res => res.json()).catch(err => ({ advance: 0 }))
        ])
        .then(([dutiesData, holidaysData, financeData]) => {
            selectedDates = new Set(dutiesData.duties || []);
            holidays = new Set(holidaysData.holidays || []);
            currentAdvance = financeData.advance || 0;
            document.getElementById('advanceInput').value = currentAdvance;
            generateCalendar(month);
        })
        .catch(error => {
            console.error('Ошибка загрузки данных:', error);
            selectedDates = new Set();
            holidays = new Set();
            currentAdvance = 0;
            document.getElementById('advanceInput').value = currentAdvance;
            generateCalendar(month);
        });
    }

    function generateCalendar(month) {
        let year, monthNum;
        if (month.includes('-')) {
            [year, monthNum] = month.split('-');
        } else {
            // Если месяц передан в формате "MM", используем текущий год
            year = new Date().getFullYear();
            monthNum = month;
        }
        
        const date = new Date(year, parseInt(monthNum) - 1, 1);
        const calendar = document.getElementById('dutyCalendar');
        calendar.innerHTML = '';

        const monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
        const header = document.createElement('h5');
        header.textContent = `${monthNames[parseInt(monthNum) - 1]} ${year}`;
        calendar.appendChild(header);

        const table = document.createElement('table');
        table.className = 'table table-bordered';
        const thead = document.createElement('thead');
        const tr = document.createElement('tr');
        ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'].forEach(day => {
            const th = document.createElement('th');
            th.textContent = day;
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        let trWeek = document.createElement('tr');
        const firstDay = (date.getDay() + 6) % 7; // Понедельник = 0
        for (let i = 0; i < firstDay; i++) {
            trWeek.appendChild(document.createElement('td'));
        }

        const lastDay = new Date(year, monthNum, 0).getDate();
        for (let day = 1; day <= lastDay; day++) {
            const td = document.createElement('td');
            td.textContent = day;
            const dateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isWeekend = (day + firstDay - 1) % 7 >= 5;
            const isHoliday = holidays.has(dateStr);

            if (selectedDates.has(dateStr)) {
                td.classList.add(isHoliday ? 'holiday-selected' : 'bg-success', 'text-white');
            } else if (isHoliday) {
                td.classList.add('bg-warning', 'text-dark');
            }

            td.addEventListener('click', function() {
                if (selectedDates.has(dateStr)) {
                    selectedDates.delete(dateStr);
                    td.classList.remove('bg-success', 'holiday-selected', 'text-white');
                    if (isHoliday) td.classList.add('bg-warning', 'text-dark');
                } else {
                    selectedDates.add(dateStr);
                    td.classList.remove('bg-warning', 'text-dark');
                    td.classList.add(isHoliday ? 'holiday-selected' : 'bg-success', 'text-white');
                }
            });
            trWeek.appendChild(td);
            if ((day + firstDay - 1) % 7 === 0) {
                tbody.appendChild(trWeek);
                trWeek = document.createElement('tr');
            }
        }
        if (trWeek.children.length > 0) {
            tbody.appendChild(trWeek);
        }
        table.appendChild(tbody);
        calendar.appendChild(table);
    }

    document.getElementById('saveDuties').addEventListener('click', function() {
        const duties = Array.from(selectedDates);
        const advance = parseFloat(document.getElementById('advanceInput').value) || 0;
        fetch('save_duties.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: currentUserId, month: dutyMonth, duties: duties, advance: advance })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Дежурства и аванс сохранены');
                bootstrap.Modal.getInstance(document.getElementById('dutyModal')).hide();
                location.reload();
            } else {
                alert('Ошибка при сохранении: ' + data.error);
            }
        });
    });

    // Логика настроек
    let settingsUserId;
    let settingsMonth;

    document.querySelectorAll('[data-bs-target="#settingsModal"]').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            settingsUserId = this.getAttribute('data-user-id');
            settingsMonth = this.getAttribute('data-month');
            fetch(`get_finance.php?user_id=${settingsUserId}&month=${settingsMonth}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('salaryInput').value = data.current_salary || 24000;
                    document.getElementById('employmentSelect').value = data.official_employment || 'Нет';
                })
                .catch(error => {
                    console.error('Ошибка загрузки данных настроек:', error);
                    document.getElementById('salaryInput').value = 24000;
                    document.getElementById('employmentSelect').value = 'Нет';
                });
        });
    });

    document.getElementById('saveSettings').addEventListener('click', function() {
        const salary = parseFloat(document.getElementById('salaryInput').value) || 24000;
        const employment = document.getElementById('employmentSelect').value;
        fetch('save_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: settingsUserId, month: settingsMonth, current_salary: salary, official_employment: employment })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Настройки сохранены');
                bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
                location.reload();
            } else {
                alert('Ошибка при сохранении: ' + data.error);
            }
        });
    });

    // Обработчик клика по блоку зарплаты
    document.querySelectorAll('.salary-block').forEach(block => {
        const userId = block.getAttribute('data-user-id');
        const isAdmin = <?php echo ($isAdmin || $isOwner || $isSuperAdmin) ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo $usr['id']; ?>;
        
        // Если пользователь не админ и это не его блок зарплаты
        if (!isAdmin && userId != currentUserId) {
            block.classList.add('no-edit');
            return;
        }
        
        block.addEventListener('click', function() {
            const month = this.getAttribute('data-month');
            const year = this.getAttribute('data-year');
            const userFio = this.closest('tr').querySelector('td:first-child a').textContent;
            
            // Формируем дату в формате YYYY-MM
            const formattedMonth = `${year}-${month}`;
            
            // Открываем модальное окно дежурств
            const dutyModal = new bootstrap.Modal(document.getElementById('dutyModal'));
            document.getElementById('dutyUserFio').textContent = userFio;
            dutyModal.show();
            
            // Загружаем данные дежурств
            loadDutiesAndHolidays(userId, formattedMonth);
        });
    });
});
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>
<?php ob_end_flush(); ?>