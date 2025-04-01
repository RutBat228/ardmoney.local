<?php
session_start();
include "inc/head.php";
AutorizeProtect();
access();
animate();
global $connect;
global $usr;

$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
    echo '<script>alert("Нет доступа"); document.location.replace("/");</script>';
    exit;
}

if (!isset($connect)) {
    die("Ошибка: переменная подключения к базе данных не определена.");
}

// Получение данных из таблицы vid_rabot
$result = $connect->query("SELECT * FROM vid_rabot ORDER BY type_kabel");
if (!$result) {
    die("Ошибка запроса к базе данных: " . $connect->error);
}
$vid_rabot = $result->fetch_all(MYSQLI_ASSOC);

// Получение уникальных значений для выпадающих списков
$razdel_result = $connect->query("SELECT DISTINCT razdel FROM vid_rabot");
$razdels = $razdel_result->fetch_all(MYSQLI_ASSOC);

$type_kabel_result = $connect->query("SELECT DISTINCT type_kabel FROM vid_rabot");
$type_kabels = $type_kabel_result->fetch_all(MYSQLI_ASSOC);

$icon_result = $connect->query("SELECT DISTINCT icon FROM vid_rabot");
$db_icons = $icon_result->fetch_all(MYSQLI_ASSOC);

// Расширенный список иконок, подходящих по смыслу (без bi bi-cable)
$additional_icons = [
    "bi bi-tools",           // Инструменты (монтаж, демонтаж)
    "bi bi-plug",            // Подключение
    "bi bi-wrench",          // Настройка
    "bi bi-box",             // Сейф/короб
    "bi bi-gear",            // Механизмы/работы
    "bi bi-display",         // ТВ/монитор
    "bi bi-camera-video",    // Видеокамеры
    "bi bi-lock",            // Замок
    "bi bi-signpost",        // Таблички
    "bi bi-hourglass",       // Почасовая работа
    "bi bi-lightning",       // Электричество/питание
    "bi bi-ethernet",        // Сеть/роутер
    "bi bi-trash",           // Демонтаж
    "bi bi-hammer"           // Сверление/монтаж
];
$icons = array_merge(array_column($db_icons, 'icon'), $additional_icons);
$icons = array_unique($icons);

$color_result = $connect->query("SELECT DISTINCT color FROM vid_rabot");
$colors = $color_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление видами работ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="css/adm_material.css">
    <style>
        .form-row { 
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap; 
        }
        .form-group { 
            flex: 1; 
            min-width: 200px; 
        }
        .priority-toggle { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .priority-toggle label { 
            margin: 0; 
            font-weight: bold; 
        }
        .priority-toggle input[type="checkbox"] { 
            display: none; 
        }
        .priority-toggle .switch { 
            position: relative; 
            display: inline-block; 
            width: 60px; 
            height: 34px; 
        }
        .priority-toggle .switch input { 
            opacity: 0; 
            width: 0; 
            height: 0; 
        }
        .priority-toggle .slider { 
            position: absolute; 
            cursor: pointer; 
            top: 0; 
            left: 0; 
            right: 0; 
            bottom: 0; 
            background-color: #ccc; 
            transition: .4s; 
            border-radius: 34px; 
        }
        .priority-toggle .slider:before { 
            position: absolute; 
            content: ""; 
            height: 26px; 
            width: 26px; 
            left: 4px; 
            bottom: 4px; 
            background-color: white; 
            transition: .4s; 
            border-radius: 50%; 
        }
        .priority-toggle input:checked + .slider { 
            background-color: #2196F3; 
        }
        .priority-toggle input:checked + .slider:before { 
            transform: translateX(26px); 
        }
        .color-grid, .icon-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            align-content: center;
            justify-content: space-between;
        }
        .color-btn, .icon-btn { 
            width: 40px; 
            height: 40px; 
            border: 2px solid transparent; 
            border-radius: 5px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .color-btn.active, .icon-btn.active { 
            border-color: #000; 
        }
        .icon-btn i { 
            font-size: 1.5rem; 
        }
        tr.priority-1 {
            background-color: #e6ffe6 !important; /* Зелёный для "Часто используемые" */
        }
        tr.priority-0 {
            background-color: #e6f2ff !important; /* Синий для "Редко используемые" */
        }
        .group-cell .razdel {
            font-weight: bold;
        }
        .group-cell .podrazdel {
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Управление видами работ</h1>
            <button class="btn btn-primary" onclick="openModal('add')">Добавить вид работ</button>
        </header>

        <div class="table-container">
            <table id="vidTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Группа</th>
                        <th>Цена</th>
                        <th>Приоритет</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vid_rabot as $vid) { ?>
                        <tr data-id="<?php echo $vid['id']; ?>" class="priority-<?php echo $vid['prioritet']; ?>">
                            <td><?php echo $vid['id']; ?></td>
                            <td><?php echo htmlspecialchars($vid['name']); ?></td>
                            <td class="group-cell">
                                <div class="razdel"><?php echo htmlspecialchars($vid['razdel']); ?></div>
                                <div class="podrazdel"><?php echo htmlspecialchars($vid['type_kabel']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($vid['price_tech']); ?></td>
                            <td data-priority="<?php echo $vid['prioritet']; ?>">
                                <?php echo $vid['prioritet'] == 1 ? 'Часто используемые' : 'Редко используемые'; ?>
                            </td>
                            <td>
                                <button class="action-btn" onclick='editVid(<?php echo htmlspecialchars(json_encode($vid)); ?>)'><i class="bi bi-pencil"></i></button>
                                <button class="action-btn" onclick="deleteVid(<?php echo $vid['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования -->
    <div class="modal" id="vidModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Добавить вид работ</h2>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form id="vidForm">
                <input type="hidden" name="id" id="modalId">
                <input type="hidden" name="action" id="modalAction" value="add_vid">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modalName">Название</label>
                        <input type="text" id="modalName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="modalRazdel">Раздел</label>
                        <select id="modalRazdel" name="razdel" required>
                            <?php foreach ($razdels as $razdel) { ?>
                                <option value="<?php echo $razdel['razdel']; ?>"><?php echo $razdel['razdel']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modalPrice">Цена</label>
                        <input type="number" id="modalPrice" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="modalTypeKabel">Подраздел</label>
                        <select id="modalTypeKabel" name="type_kabel" required>
                            <?php foreach ($type_kabels as $type_kabel) { ?>
                                <option value="<?php echo $type_kabel['type_kabel']; ?>"><?php echo $type_kabel['type_kabel']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Цвет</label>
                        <div class="color-grid" id="colorGrid">
                            <?php foreach ($colors as $color) { ?>
                                <button type="button" class="color-btn" style="background: <?php echo $color['color']; ?>;" data-color="<?php echo $color['color']; ?>" onclick="selectColor('<?php echo $color['color']; ?>')"></button>
                            <?php } ?>
                        </div>
                        <input type="hidden" id="modalColor" name="color" value="#55b32f">
                    </div>
                    <div class="form-group">
                        <label>Иконка</label>
                        <div class="icon-grid" id="iconGrid">
                            <?php foreach ($icons as $icon) { ?>
                                <button type="button" class="icon-btn" data-icon="<?php echo $icon; ?>" onclick="selectIcon('<?php echo $icon; ?>')">
                                    <i class="<?php echo $icon; ?>"></i>
                                </button>
                            <?php } ?>
                        </div>
                        <input type="hidden" id="modalIcon" name="icon" value="bi bi-1-circle">
                    </div>
                </div>
                <div class="form-group">
                    <label>Часто/Редко используемые</label>
                    <div class="priority-toggle">
                        <label for="modalPrioritet">Редко</label>
                        <label class="switch">
                            <input type="checkbox" id="modalPrioritet" name="prioritet" value="1">
                            <span class="slider"></span>
                        </label>
                        <label for="modalPrioritet">Часто</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    <script>
        $(document).ready(function() {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 5000
            };

            // Инициализация DataTables с кастомной сортировкой для колонки "Приоритет"
            const table = $('#vidTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.1.7/i18n/ru.json"
                },
                "stateSave": true,
                "responsive": true,
                "scrollX": true,
                "order": [[0, "asc"]], // Сортировка по ID по умолчанию
                "columnDefs": [
                    {
                        "targets": 4, // Колонка "Приоритет" (индекс 4)
                        "orderDataType": "dom-priority",
                        "type": "numeric"
                    }
                ]
            });

            // Кастомная сортировка по атрибуту data-priority
            $.fn.dataTable.ext.type.order['dom-priority-pre'] = function(d) {
                return parseInt($(d).data('priority')) || 0; // Извлекаем значение data-priority
            };

            const notification = sessionStorage.getItem('notification');
            if (notification) {
                const { type, message } = JSON.parse(notification);
                if (type === 'success') toastr.success(message);
                else if (type === 'error') toastr.error(message);
                else toastr.info(message);
                sessionStorage.removeItem('notification');
            }
        });

        const modal = document.getElementById('vidModal');
        const form = document.getElementById('vidForm');

        function openModal(type, data = {}) {
            document.getElementById('modalTitle').textContent = type === 'add' ? 'Добавить вид работ' : 'Редактировать вид работ';
            document.getElementById('modalAction').value = type === 'add' ? 'add_vid' : 'update_vid';
            
            if (type === 'edit' && data) {
                document.getElementById('modalId').value = data.id || '';
                document.getElementById('modalName').value = data.name || '';
                document.getElementById('modalRazdel').value = data.razdel || '';
                document.getElementById('modalPrice').value = data.price_tech || '';
                document.getElementById('modalTypeKabel').value = data.type_kabel || '';
                document.getElementById('modalColor').value = data.color || '#55b32f';
                document.getElementById('modalIcon').value = data.icon || 'bi bi-1-circle';
                document.getElementById('modalPrioritet').checked = data.prioritet == 1;
                document.querySelectorAll('.color-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.color === data.color);
                });
                document.querySelectorAll('.icon-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.icon === data.icon);
                });
            } else {
                form.reset();
                document.getElementById('modalColor').value = '#55b32f';
                document.getElementById('modalIcon').value = 'bi bi-1-circle';
                document.getElementById('modalPrioritet').checked = false;
                document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.icon-btn').forEach(btn => btn.classList.remove('active'));
            }

            modal.style.display = 'flex';
            gsap.fromTo(modal, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.3, ease: "power2.out" });
        }

        function closeModal() {
            gsap.to(modal, {
                opacity: 0,
                scale: 0.9,
                duration: 0.3,
                ease: "power2.in",
                onComplete: () => modal.style.display = 'none'
            });
        }

        function selectColor(color) {
            document.getElementById('modalColor').value = color;
            document.querySelectorAll('.color-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.color === color);
            });
        }

        function selectIcon(icon) {
            document.getElementById('modalIcon').value = icon;
            document.querySelectorAll('.icon-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.icon === icon);
            });
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            try {
                const response = await fetch('adm_vidrabot_obr.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    sessionStorage.setItem('notification', JSON.stringify({ type: 'success', message: result.message }));
                    closeModal();
                    location.reload();
                } else {
                    sessionStorage.setItem('notification', JSON.stringify({ type: 'error', message: result.error || 'Произошла ошибка' }));
                    closeModal();
                    location.reload();
                }
            } catch (error) {
                sessionStorage.setItem('notification', JSON.stringify({ type: 'error', message: 'Ошибка сервера: ' + error.message }));
                closeModal();
                location.reload();
            }
        });

        function editVid(vid) {
            openModal('edit', vid);
        }

        function deleteVid(id) {
            const dialog = bootbox.confirm({
                title: 'Подтверждение удаления',
                message: 'Вы точно хотите удалить этот вид работ? Действие нельзя отменить!',
                buttons: {
                    confirm: { label: 'Да, удалить', className: 'btn-danger' },
                    cancel: { label: 'Отмена', className: 'btn-secondary' }
                },
                closeButton: false,
                callback: async function(result) {
                    if (result) {
                        try {
                            const response = await fetch('adm_vidrabot_obr.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=delete_vid&id=${id}`
                            });
                            const result = await response.json();

                            if (result.success) {
                                sessionStorage.setItem('notification', JSON.stringify({ type: 'success', message: 'Вид работ удалён!' }));
                                location.reload();
                            } else {
                                sessionStorage.setItem('notification', JSON.stringify({ type: 'error', message: result.error || 'Ошибка удаления' }));
                                location.reload();
                            }
                        } catch (error) {
                            sessionStorage.setItem('notification', JSON.stringify({ type: 'error', message: 'Ошибка сервера: ' + error.message }));
                            location.reload();
                        }
                    }
                }
            });

            dialog.on('shown.bs.modal', function() {
                $('.bootbox.modal').removeAttr('aria-hidden');
            });

            $(document).on('click', '.bootbox.modal', function(e) {
                if (e.target === this) bootbox.hideAll();
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    </script>
    <?php include 'inc/foot.php'; ?>
</body>
</html>