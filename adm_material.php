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

if (!$isOwner && !$isSuperAdmin) {
    echo '<script>alert("Нет доступа"); document.location.replace("/");</script>';
    exit;
}

if (!isset($connect)) {
    die("Ошибка: переменная подключения к базе данных не определена.");
}

$result = $connect->query("SELECT * FROM material");
if (!$result) {
    die("Ошибка запроса к базе данных: " . $connect->error);
}
$materials = $result->fetch_all(MYSQLI_ASSOC);

$icons = ["bi bi-asterisk", "bi bi-router", "bi bi-inbox-fill", "bi bi-usb-fill", "bi bi-usb-drive-fill", "bi bi-stop-circle-fill", "bi bi-film", "bi bi-border-width", "bi bi-motherboard-fill", "bi bi-plugin", "bi bi-plug-fill"];
$colors = ["red", "mediumseagreen", "darkorange", "blue", "brown", "deeppink", "black", "purple"];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление материалами</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="css/adm_material.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Управление материалами</h1>
            <button class="btn btn-primary" onclick="openModal('add')">Добавить материал</button>
        </header>

        <div class="table-container">
            <table id="materialsTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Раздел</th>
                        <th>Атрибуты</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $material) { ?>
                        <tr data-id="<?php echo $material['id']; ?>">
                            <td><?php echo $material['id']; ?></td>
                            <td><?php echo $material['name']; ?></td>
                            <td><?php echo $material['razdel']; ?></td>
                            <td class="attributes">
                                <span class="attribute-icon" style="background: <?php echo $material['color']; ?>;">
                                    <i class="<?php echo $material['icon']; ?>"></i>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn" onclick='editMaterial(<?php echo htmlspecialchars(json_encode($material)); ?>)'><i class="bi bi-pencil"></i></button>
                                <button class="action-btn" onclick="deleteMaterial(<?php echo $material['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования -->
    <div class="modal" id="materialModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Добавить материал</h2>
                <button class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form id="materialForm">
                <input type="hidden" name="id" id="modalId">
                <input type="hidden" name="action" id="modalAction" value="add_material">
                <div class="form-group">
                    <label for="modalName">Название</label>
                    <input type="text" id="modalName" name="name" required>
                </div>
                <div class="form-group">
                    <label>Цвет</label>
                    <div class="color-grid" id="colorGrid">
                        <?php foreach ($colors as $color) { ?>
                            <button type="button" class="color-btn" style="background: <?php echo $color; ?>;" data-color="<?php echo $color; ?>" onclick="selectColor('<?php echo $color; ?>')"></button>
                        <?php } ?>
                    </div>
                    <input type="hidden" id="modalColor" name="color" value="red">
                </div>
                <div class="form-group">
                    <label for="modalRazdel">Раздел</label>
                    <select id="modalRazdel" name="razdel" required>
                        <option value="Аккумуляторы">Аккумуляторы</option>
                        <option value="Другое">Другое</option>
                        <option value="Инверторы">Инверторы</option>
                        <option value="Кабель">Кабель</option>
                        <option value="Медики">Медики</option>
                        <option value="Онушки">Онушки</option>
                        <option value="Приставки">Приставки</option>
                        <option value="Роутеры">Роутеры</option>
                        <option value="Трансы">Трансы</option>
                        <option value="Управляхи">Управляхи</option>
                    </select>
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
                    <input type="hidden" id="modalIcon" name="icon" value="bi bi-asterisk">
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

            const table = $('#materialsTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.2.1/i18n/ru.json"
                },
                "stateSave": true,
                "responsive": true,
                "scrollX": true
            });

            const notification = sessionStorage.getItem('notification');
            if (notification) {
                const { type, message } = JSON.parse(notification);
                if (type === 'success') {
                    toastr.success(message);
                } else if (type === 'error') {
                    toastr.error(message);
                } else {
                    toastr.info(message);
                }
                sessionStorage.removeItem('notification');
            }
        });

        const modal = document.getElementById('materialModal');
        const form = document.getElementById('materialForm');

        function openModal(type, data = {}) {
            document.getElementById('modalTitle').textContent = type === 'add' ? 'Добавить материал' : 'Редактировать материал';
            document.getElementById('modalAction').value = type === 'add' ? 'add_material' : 'update_material';
            
            if (type === 'edit' && data) {
                document.getElementById('modalId').value = data.id || '';
                document.getElementById('modalName').value = data.name || '';
                document.getElementById('modalColor').value = data.color || 'red';
                document.getElementById('modalRazdel').value = data.razdel || '';
                document.getElementById('modalIcon').value = data.icon || 'bi bi-asterisk';
                document.querySelectorAll('.color-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.color === data.color);
                });
                document.querySelectorAll('.icon-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.icon === data.icon);
                });
            } else {
                form.reset();
                document.getElementById('modalColor').value = 'red';
                document.getElementById('modalIcon').value = 'bi bi-asterisk';
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
                const response = await fetch('adm_material_obr.php', {
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

        function editMaterial(material) {
            openModal('edit', material);
        }

        function deleteMaterial(id) {
            const dialog = bootbox.confirm({
                title: 'Подтверждение удаления',
                message: 'Вы точно хотите удалить этот материал? Действие нельзя отменить!',
                buttons: {
                    confirm: {
                        label: 'Да, удалить',
                        className: 'btn-danger'
                    },
                    cancel: {
                        label: 'Отмена',
                        className: 'btn-secondary'
                    }
                },
                closeButton: false,
                callback: async function(result) {
                    if (result) {
                        try {
                            const response = await fetch('adm_material_obr.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=delete_material&id=${id}`
                            });
                            const result = await response.json();

                            if (result.success) {
                                sessionStorage.setItem('notification', JSON.stringify({ type: 'success', message: 'Материал успешно удалён!' }));
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

            // Убираем aria-hidden и обеспечиваем центрирование
            dialog.on('shown.bs.modal', function() {
                $('.bootbox.modal').removeAttr('aria-hidden'); // Убираем aria-hidden
            });

            // Закрытие при клике вне модального окна
            $(document).on('click', '.bootbox.modal', function(e) {
                if (e.target === this) {
                    bootbox.hideAll();
                }
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    </script>
    <?php include 'inc/foot.php'; ?>
</body>
</html>