<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание пробега</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .highlight {
            color: green;
            font-weight: bold;
        }
    </style>
    <script>
    function showConfirmModal(formId) {
        var form = document.getElementById(formId);
        if (form) {
            form.onsubmit = function(e) {
                e.preventDefault();
                var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
                document.getElementById('confirmForm').value = formId;
                modal.show();
            };
        }
    }

    function confirmAction() {
        var formId = document.getElementById('confirmForm').value;
        document.getElementById(formId).submit();
    }
    </script>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Отслеживание пробега</h1>

        <!-- Форма для ввода данных -->
        <form action="submit_mileage.php" method="POST" class="mb-5" id="mileageForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="actualMileage" class="form-label">Не мотал (км):</label>
                    <input type="number" class="form-control" id="actualMileage" name="actualMileage" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="displayedMileage" class="form-label">Намотал (км):</label>
                    <input type="number" class="form-control" id="displayedMileage" name="displayedMileage" required>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Отправить</button>
            </div>
        </form>

        <script>showConfirmModal('mileageForm');</script>

        <!-- Обработка уведомлений -->
        <?php
        include("db.php");

        // Функция для показа уведомления
        function showAlert($message, $type = 'success') {
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                    ' . $message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }

        // Обработка уведомлений
        if (isset($_GET['oil_change']) && $_GET['oil_change'] == 'success') {
            showAlert('<strong>Успешно!</strong> Замена масла успешно зафиксирована.');
        }

        if (isset($_GET['rollback'])) {
            if ($_GET['rollback'] == 'success') {
                showAlert('<strong>Готово!</strong> Последняя запись успешно удалена.');
            } else if ($_GET['rollback'] == 'error') {
                $error_message = isset($_GET['message']) ? $_GET['message'] : 'unknown';
                
                switch($error_message) {
                    case 'time_expired':
                        showAlert('<strong>Ошибка!</strong> Невозможно удалить запись: прошло более 5 минут с момента добавления.', 'danger');
                        break;
                    case 'no_record':
                        showAlert('<strong>Внимание!</strong> Нет записей для удаления.', 'warning');
                        break;
                    case 'delete_failed':
                        showAlert('<strong>Ошибка!</strong> Не удалось удалить запись из базы данных.', 'danger');
                        break;
                    default:
                        showAlert('<strong>Ошибка!</strong> Неизвестная ошибка при удалении записи.', 'danger');
                }
            }
        }

        // Добавим уведомление при успешном добавлении записи пробега
        if (isset($_GET['mileage_added']) && $_GET['mileage_added'] == 'success') {
            showAlert('<strong>Успешно!</strong> Новая запись пробега добавлена.');
        }
        ?>

        <!-- Вывод данных -->
        <?php
        // Получение предыдущих данных для расчётов
        $sql_last = "SELECT * FROM mileage ORDER BY id DESC LIMIT 1";
        $result_last = $conn->query($sql_last);
        $last_data = $result_last->fetch_assoc();

        if ($last_data) {
            // Получение первого значения пробега
            $sql_first = "SELECT MIN(actual_mileage) AS first_mileage FROM mileage";
            $result_first = $conn->query($sql_first);
            $first_data = $result_first->fetch_assoc();
            $first_mileage = $first_data['first_mileage'];

            // Получение суммы всех намоток и реального пробега
            $sql_wraps = "SELECT SUM(wrap_distance) AS total_wrap FROM mileage";
            $result_wraps = $conn->query($sql_wraps);
            $wraps_data = $result_wraps->fetch_assoc();
            $total_wrap = $wraps_data['total_wrap'];

            // Расчёт реального пробега
            $real_mileage = $last_data['displayed_mileage'] - $total_wrap;

            // Проверка, нужно ли обновлять данные в summary таблице
            $sql_check_summary = "SELECT * FROM mileage_summary ORDER BY `id` DESC LIMIT 1";
            $result_check = $conn->query($sql_check_summary);
            $summary_data = $result_check->fetch_assoc();

            if ($summary_data) {
                $prev_real_mileage = $summary_data['total_mileage'];
                $prev_total_wrap = $summary_data['total_wrap'];
                
                if ($real_mileage != $prev_real_mileage || $total_wrap != $prev_total_wrap) {
                    // Обновление данных в summary таблице, если данные изменились
                    $sql_summary = "INSERT INTO mileage_summary (total_mileage, total_wrap) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE total_mileage = VALUES(total_mileage), total_wrap = VALUES(total_wrap)";
                    $stmt = $conn->prepare($sql_summary);
                    $stmt->bind_param("dd", $real_mileage, $total_wrap);
                    $stmt->execute();
                }
            } else {
                // Если нет данных, вставляем их впервые
                $sql_summary = "INSERT INTO mileage_summary (total_mileage, total_wrap) VALUES (?, ?)";
                $stmt = $conn->prepare($sql_summary);
                $stmt->bind_param("dd", $real_mileage, $total_wrap);
                $stmt->execute();
            }

            // Пробег за неделю, месяц, год с учётом логики чистого пробега и намотанного пробега
            $sql_week = "SELECT SUM(distance_driven - wrap_distance) AS clean_driven_week, SUM(wrap_distance) AS total_wrap_week 
                         FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            $result_week = $conn->query($sql_week);
            $week_data = $result_week->fetch_assoc();

            $sql_month = "SELECT SUM(distance_driven - wrap_distance) AS clean_driven_month, SUM(wrap_distance) AS total_wrap_month 
                          FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            $result_month = $conn->query($sql_month);
            $month_data = $result_month->fetch_assoc();

            $sql_year = "SELECT SUM(distance_driven - wrap_distance) AS clean_driven_year, SUM(wrap_distance) AS total_wrap_year 
                         FROM mileage WHERE mileage_added_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            $result_year = $conn->query($sql_year);
            $year_data = $result_year->fetch_assoc();

            // Получение информации о последней замене масла
            $sql_oil = "SELECT * FROM oil_changes ORDER BY odometer_at_change DESC LIMIT 1";
            $result_oil = $conn->query($sql_oil);
            $oil_data = $result_oil->fetch_assoc();

            if ($oil_data && $oil_data['odometer_at_change'] > 0) {
                $last_oil_change_odometer = $oil_data['odometer_at_change'];
                $last_oil_change_date = $oil_data['date_of_change'];
            } else {
                // Если масло никогда не менялось, используем первый пробег
                $last_oil_change_odometer = $first_mileage + $total_wrap;
                $last_oil_change_date = 'Никогда';
            }

            // Расчет текущего одометра
            $current_odometer = $real_mileage + $total_wrap;

            // Расчет оставшегося расстояния до следующей замены масла
            $distance_since_last_oil_change = $current_odometer - $last_oil_change_odometer;
            $distance_until_oil_change = 7000 - $distance_since_last_oil_change;

            // Проверка, чтобы оставшееся расстояние не было отрицательным, если масло было заменено в будущем
            if ($last_oil_change_odometer > $current_odometer) {
                $distance_until_oil_change = 7000;
            }

        } else {
            $week_data = $month_data = $year_data = ['clean_driven_week' => 0, 'clean_driven_month' => 0, 'clean_driven_year' => 0, 'total_wrap_week' => 0, 'total_wrap_month' => 0, 'total_wrap_year' => 0];
            $real_mileage = 0;
            $total_wrap = 0;
            $last_oil_change_odometer = 0;
            $last_oil_change_date = 'Никогда';
            $distance_until_oil_change = 7000;
        }
        ?>

        <?php if ($last_data && isset($_GET['mileage_added']) && $_GET['mileage_added'] == 'success'): ?>
            <div class="text-center mb-4">
                <form action="rollback.php" method="POST" id="rollbackForm" style="display: inline-block;">
                    <input type="hidden" name="type" value="mileage">
                    <button type="button" class="btn btn-danger" onclick="new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show()">
                        Отменить последнюю запись пробега
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Карточка "За неделю" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">За неделю</h5>
                        <p class="card-text">Намотано: <?= number_format($week_data['total_wrap_week'], 0, ',', ' ') ?> км</p>
                    </div>
                </div>
            </div>
            <!-- Карточка "За месяц" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">За месяц</h5>
                        <p class="card-text">Намотано: <?= number_format($month_data['total_wrap_month'], 0, ',', ' ') ?> км</p>
                    </div>
                </div>
            </div>
            <!-- Карточка "За год" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">За год</h5>
                        <p class="card-text">Намотано: <?= number_format($year_data['total_wrap_year'], 0, ',', ' ') ?> км</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Карточка "Реальный пробег" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Реальный пробег</h5>
                        <p class="card-text highlight"><?= number_format($real_mileage, 0, ',', ' ') ?> км</p>
                    </div>
                </div>
            </div>
            <!-- Карточка "Намотанный пробег" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Намотанный пробег</h5>
                        <p class="card-text"><?= number_format($total_wrap, 0, ',', ' ') ?> км</p>
                    </div>
                </div>
            </div>
            <!-- Карточка "Замена масла" -->
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Замена масла</h5>
                        <p class="card-text">Последняя замена: <?= number_format($last_oil_change_odometer, 0, ',', ' ') ?> км (<?= $last_oil_change_date ?>)</p>
                        <p class="card-text">До следующей замены: 
                            <?php if ($distance_until_oil_change <= 0): ?>
                                <span class="text-danger">Необходимо заменить масло!</span>
                            <?php else: ?>
                                <?= number_format($distance_until_oil_change, 0, ',', ' ') ?> км
                            <?php endif; ?>
                        </p>
                        <!-- Полоса прогресса -->
                        <div class="progress mt-3 mb-3" style="height: 20px; 
                            background: #333333;
                            position: relative; 
                            overflow: visible;
                            border-radius: 0;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= max(0, min(100, (7000 - $distance_until_oil_change) / 70)) ?>%; 
                                        background: #333333;
                                        border-radius: 0;" 
                                 aria-valuenow="<?= max(0, min(100, (7000 - $distance_until_oil_change) / 70)) ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                            <!-- Дорожная разметка -->
                            <div style="position: absolute;
                                        top: 45%;
                                        left: 0;
                                        right: 0;
                                        height: 2px;
                                        background: repeating-linear-gradient(
                                            90deg,
                                            #ffffff,
                                            #ffffff 20px,
                                            transparent 20px,
                                            transparent 40px
                                        );">
                            </div>
                            <!-- Иконка машины -->
                            <div style="position: absolute; 
                                        left: <?= max(0, min(100, (7000 - $distance_until_oil_change) / 70)) ?>%; 
                                        top: -16px; 
                                        transform: translateX(-50%) scaleX(-1); 
                                        font-size: 32px;
                                        line-height: 1;
                                        z-index: 2;
                                        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                🚗
                            </div>
                            <!-- Иконка СТО -->
                            <div style="position: absolute; 
                                        right: -16px; 
                                        top: -16px; 
                                        font-size: 32px;
                                        line-height: 1;
                                        z-index: 2;
                                        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">
                                🛠
                            </div>
                            <!-- Метки расстояния -->
                            <div style="position: absolute; 
                                        left: 0; 
                                        bottom: -25px; 
                                        font-size: 12px;
                                        font-weight: bold;">
                                0 км
                            </div>
                            <div style="position: absolute; 
                                        right: 0; 
                                        bottom: -25px; 
                                        font-size: 12px;
                                        font-weight: bold;">
                                7000 км
                            </div>
                        </div>
                        <!-- Форма для сброса счетчика замены масла -->
                        <form action="submit_oil_change.php" method="POST" class="mt-3" id="oilChangeForm">
                            <div class="mb-3">
                                <label for="odometerAtChange" class="form-label">Пробег на момент замены (км):</label>
                                <input type="number" class="form-control" id="odometerAtChange" name="odometerAtChange" required>
                            </div>
                            <button type="submit" class="btn btn-warning">Заменил масло</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($distance_until_oil_change <= 0): ?>
            <div class="alert alert-warning mt-5" role="alert">
                Внимание! Вам нужно заменить масло!
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-5 text-center" role="alert">
                До замены масла осталось <?= number_format($distance_until_oil_change, 0, ',', ' ') ?> км.
            </div>
        <?php endif; ?>

        <!-- История добавлений -->
        <div class="card mt-5">
            <div class="card-header">
                <h5 class="mb-0">История добавлений</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Не мотал (км)</th>
                                <th>Намотано (км)</th>
                                <th>Намотал (км)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Параметры пагинации
                            $limit = 10; // Количество записей на странице
                            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                            $offset = ($page - 1) * $limit;

                            // Получаем общее количество записей
                            $sql_count = "SELECT COUNT(*) as total FROM mileage";
                            $result_count = $conn->query($sql_count);
                            $total_records = $result_count->fetch_assoc()['total'];
                            $total_pages = ceil($total_records / $limit);

                            // Получаем записи для текущей страницы
                            $sql_history = "SELECT 
                                actual_mileage,
                                displayed_mileage,
                                wrap_distance,
                                DATE_FORMAT(mileage_added_at, '%d.%m.%Y') as formatted_date
                                FROM mileage 
                                ORDER BY mileage_added_at DESC 
                                LIMIT $limit OFFSET $offset";
                            $result_history = $conn->query($sql_history);

                            if ($result_history && $result_history->num_rows > 0) {
                                while($row = $result_history->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['formatted_date'] . "</td>";
                                    echo "<td>" . number_format($row['actual_mileage'], 0, ',', ' ') . "</td>";
                                    echo "<td>" . number_format($row['wrap_distance'], 0, ',', ' ') . "</td>";
                                    echo "<td>" . number_format($row['displayed_mileage'], 0, ',', ' ') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Нет записей</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <!-- Пагинация -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <?php $conn->close(); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>showConfirmModal('oilChangeForm');</script>

    <!-- Модальное окно подтверждения добавления -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Подтверждение действия</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Вы уверены, что хотите добавить эту запись?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmAction()">Подтвердить</button>
                </div>
                <input type="hidden" id="confirmForm" value="">
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Вы действительно хотите удалить последнюю запись? Это действие нельзя будет отменить.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('rollbackForm').submit()">Удалить</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
