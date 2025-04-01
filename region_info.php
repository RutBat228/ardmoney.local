<?php
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

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Используем ID вместо имени
$region_id = intval($_GET['id'] ?? 0);
if (!$region_id) {
    echo '<script>alert("Район не указан"); document.location.replace("adm_region.php");</script>';
    exit;
}

// Получаем данные региона по ID
$region_data = $connect->query("SELECT region, monthly_bonus FROM config WHERE id = $region_id")->fetch_assoc();
if (!$region_data) {
    echo '<script>alert("Район не найден"); document.location.replace("adm_region.php");</script>';
    exit;
}
$region_name = $region_data['region'];
$monthly_bonus = $region_data['monthly_bonus'] ?? 10.00;

// Ограничение для админов: только свой регион
if ($isAdmin && !$isOwner && !$isSuperAdmin && $region_name !== $usr['region']) {
    echo '<script>alert("Доступ ограничен только вашим регионом"); document.location.replace("adm_region.php");</script>';
    exit;
}

// Пользователи региона
$users = $connect->query("SELECT * FROM user WHERE region = '$region_name' ORDER BY fio")->fetch_all(MYSQLI_ASSOC);
$all_regions = $connect->query("SELECT region FROM config ORDER BY region")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о районе: <?php echo htmlspecialchars($region_name); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .editable {
            position: relative;
            display: inline-block;
            padding-right: 20px;
            border-bottom: 1px dashed #007bff;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .editable:hover { background: #f8f9fa; }
        .editable::after {
            content: '\270E';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
            color: #007bff;
        }
        .user-table th, .user-table td { padding: 8px; vertical-align: middle; }
        .user-table tr { transition: all 0.3s ease; }
        .user-table tr:hover { background: #f1f1f1; }
        .is-invalid-login { border-color: #dc3545; }
        .login-feedback { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
        .table-responsive { overflow-x: auto; }
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.25em;
            background-color: #ccc;
            border: none;
            transition: background-color 0.3s ease;
        }
        .form-switch .form-check-input:checked {
            background-color: #28a745;
        }
        .form-switch .form-check-input:focus {
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }
        .form-check-label {
            margin-left: 10px;
            font-weight: 500;
            color: #333;
        }
        @media (max-width: 576px) {
            .user-table thead { display: none; }
            .user-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                background: #fff;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .user-table td {
                display: flex;
                justify-content: space-between;
                padding: 6px 10px;
                border-bottom: 1px solid #eee;
                font-size: 0.9rem;
            }
            .user-table td:last-child { border-bottom: none; }
            .user-table td:before {
                content: attr(data-label);
                font-weight: bold;
                color: #555;
                margin-right: 10px;
            }
            .user-table td[data-label="Действия"] { justify-content: center; }
            .btn-link i { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Район: <span class="editable" data-field="name" data-name="<?php echo htmlspecialchars($region_name); ?>" data-id="<?php echo $region_id; ?>"><?php echo htmlspecialchars($region_name); ?></span></h1>
    <div class="mb-3">
        <strong>Премия:</strong> <span class="editable" data-field="monthly_bonus" data-name="<?php echo htmlspecialchars($region_name); ?>" data-id="<?php echo $region_id; ?>"><?php echo number_format($monthly_bonus, 2); ?>%</span>
    </div>

    <h2>Пользователи района</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-plus-circle"></i> Добавить пользователя
    </button>
    <div class="table-responsive">
        <table class="table table-bordered user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Email</th>
                    <th>Логин</th>
                    <th>Должность</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) { ?>
                    <tr>
                        <td data-label="ID"><?php echo $user['id']; ?></td>
                        <td data-label="ФИО"><?php echo $user['fio']; ?></td>
                        <td data-label="Email"><?php echo $user['email']; ?></td>
                        <td data-label="Логин"><?php echo $user['name']; ?></td>
                        <td data-label="Должность"><?php echo $user['rang']; ?></td>
                        <td data-label="Действия">
                            <button class="btn btn-link edit-user-btn" data-id="<?php echo $user['id']; ?>">
                                <i class="bi bi-pencil" style="color: green;"></i>
                            </button>
                            <button class="btn btn-link delete-user-btn" data-id="<?php echo $user['id']; ?>">
                                <i class="bi bi-trash" style="color: red;"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <a href="adm_region.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Назад</a>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить пользователя в <?php echo htmlspecialchars($region_name); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <input type="hidden" name="region_id" value="<?php echo htmlspecialchars($region_name); ?>">
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="fio" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <input type="text" class="form-control" name="name" id="loginInput" required>
                        <div class="login-feedback" id="loginFeedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Должность</label>
                        <select class="form-control" name="position" required>
                            <option value="Техник 1 разряда">Техник 1 разряда</option>
                            <option value="Техник 2 разряда">Техник 2 разряда</option>
                            <option value="Техник 3 разряда">Техник 3 разряда</option>
                            <option value="Мастер участка">Мастер участка</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="pass" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitAddUser"><i class="bi bi-save"></i> Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать пользователя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="fio" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Район</label>
                        <select class="form-control" name="region_id" required>
                            <option value="">Выберите район</option>
                            <option value="Без региона">Без региона</option>
                            <?php foreach ($all_regions as $r) { ?>
                                <option value="<?php echo htmlspecialchars($r['region']); ?>"><?php echo htmlspecialchars($r['region']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Должность</label>
                        <select class="form-control" name="position" required>
                            <option value="Техник 1 разряда">Техник 1 разряда</option>
                            <option value="Техник 2 разряда">Техник 2 разряда</option>
                            <option value="Техник 3 разряда">Техник 3 разряда</option>
                            <option value="Мастер участка">Мастер участка</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <input type="text" class="form-control" name="name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Новый пароль (опционально)</label>
                        <input type="password" class="form-control" name="pass">
                    </div>
                    <?php if ($isOwner || $isSuperAdmin) { ?>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="adminToggle" name="admin">
                            <label class="form-check-label" for="adminToggle">Администратор</label>
                        </div>
                    <?php } ?>
                    <button type="submit" class="btn btn-primary" id="submitEditUser"><i class="bi bi-save"></i> Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<script>
$(document).ready(function() {
    console.log("Region info page loaded, initializing scripts");

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    gsap.from(".container", { opacity: 0, y: 50, duration: 0.8, ease: "power2.out" });
    console.log("GSAP animation applied to container");

    $('.editable').click(function() {
        const field = $(this).data('field');
        const name = $(this).data('name');
        const id = $(this).data('id');
        let currentValue = $(this).text().replace('%', '');
        
        bootbox.prompt({
            title: 'Изменить ' + (field === 'name' ? 'название' : 'премию'),
            inputType: field === 'monthly_bonus' ? 'number' : 'text',
            value: currentValue,
            buttons: {
                confirm: { label: 'Сохранить', className: 'btn-success' },
                cancel: { label: 'Отмена', className: 'btn-secondary' }
            },
            callback: function(result) {
                if (result !== null) {
                    saveRegionField(id, name, field, result);
                }
            }
        });
        
        if (field === 'monthly_bonus') {
            setTimeout(() => {
                $('.bootbox-input-number').attr({
                    'min': 0,
                    'max': 200,
                    'step': 0.01
                });
            }, 100);
        }
    });

    function saveRegionField(id, oldName, field, value) {
        toastr.info('Сохраняем изменения');
        let data = { action: 'edit_region', old_name: oldName };
        if (field === 'name') {
            data.name = value;
        } else if (field === 'monthly_bonus') {
            data.name = oldName;
            data.bonus = value;
        }
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                console.log("Edit region response:", response);
                if (response.success) {
                    toastr.success('Данные обновлены');
                    const $editable = $(`.editable[data-field="${field}"][data-id="${id}"]`);
                    if (field === 'name') {
                        $editable.text(value).data('name', value);
                        $('title').text(`Информация о районе: ${value}`);
                        $('#addUserModal .modal-title').text(`Добавить пользователя в ${value}`);
                        window.history.pushState({}, document.title, `region_info.php?id=${id}`);
                    } else if (field === 'monthly_bonus') {
                        $editable.text(Number(value).toFixed(2) + '%');
                    }
                } else {
                    toastr.error('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                }
            },
            error: function(xhr, status, error) {
                console.error("Edit region AJAX error:", status, error, xhr.responseText);
                toastr.error('Ошибка сервера: ' + error + '. Ответ: ' + xhr.responseText);
            }
        });
    }

    function checkLogin(input, feedback) {
        input.on('input', function() {
            const name = $(this).val();
            if (name.length > 0) {
                $.ajax({
                    url: 'adm_region_obr.php',
                    type: 'POST',
                    data: { action: 'check_login', name: name },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            input.addClass('is-invalid-login');
                            feedback.text('Этот логин уже занят');
                            $('#submitAddUser').prop('disabled', true);
                        } else {
                            input.removeClass('is-invalid-login');
                            feedback.text('');
                            $('#submitAddUser').prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Check login AJAX error:", status, error, xhr.responseText);
                    }
                });
            } else {
                input.removeClass('is-invalid-login');
                feedback.text('');
                $('#submitAddUser').prop('disabled', false);
            }
        });
    }

    checkLogin($('#loginInput'), $('#loginFeedback'));

    $('#addUserForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=add_user';
        console.log("Submitting add user form with data:", formData);
        toastr.info('Добавляем пользователя');
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log("Add user response:", response);
                if (response.success) {
                    toastr.success('Пользователь добавлен');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                }
            },
            error: function(xhr, status, error) {
                console.error("Add user AJAX error:", status, error, xhr.responseText);
                toastr.error('Ошибка сервера: ' + error);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        });
    });

    $('.edit-user-btn').click(function() {
        const id = $(this).data('id');
        console.log("Editing user with ID:", id);
        toastr.info('Получаем данные пользователя');
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                console.log("Received user data:", data);
                if (data && !data.error) {
                    $('#editUserForm [name="id"]').val(data.id);
                    $('#editUserForm [name="fio"]').val(data.fio);
                    $('#editUserForm [name="email"]').val(data.email);
                    $('#editUserForm [name="region_id"]').val(data.region || '');
                    $('#editUserForm [name="position"]').val(data.rang);
                    $('#editUserForm [name="name"]').val(data.name);
                    // Устанавливаем состояние переключателя admin
                    $('#editUserForm [name="admin"]').prop('checked', data.admin == 1);
                    $('#editUserModal').modal('show');
                    console.log("Edit user modal opened with data:", data);
                } else {
                    console.error("User not found or error:", data.error);
                    toastr.error(data.error || 'Пользователь не найден');
                }
            },
            error: function(xhr, status, error) {
                console.error("Edit user GET error:", status, error, xhr.responseText);
                toastr.error('Ошибка загрузки данных: ' + error);
            }
        });
    });

    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        // Сериализуем данные формы и добавляем admin явно
        const adminValue = $('#editUserForm [name="admin"]').is(':checked') ? 1 : 0;
        const formData = $(this).serialize() + '&action=edit_user&admin=' + adminValue;
        console.log("Submitting edit user form with data:", formData);
        $('#editUserModal').modal('hide');
        toastr.info('Сохраняем изменения');
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log("Edit user response:", response);
                if (response.success) {
                    toastr.success('Пользователь обновлен');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                }
            },
            error: function(xhr, status, error) {
                console.error("Edit user POST error:", status, error, xhr.responseText);
                toastr.error('Ошибка сервера: ' + error);
            }
        });
    });

    $('.delete-user-btn').click(function() {
        const id = $(this).data('id');
        console.log("Deleting user with ID:", id);
        bootbox.confirm({
            title: 'Вы уверены?',
            message: 'Пользователь будет удалён без возможности восстановления',
            buttons: {
                confirm: { label: 'Да, удалить', className: 'btn-danger' },
                cancel: { label: 'Отмена', className: 'btn-secondary' }
            },
            callback: function(result) {
                if (result) {
                    toastr.info('Удаляем пользователя');
                    $.ajax({
                        url: 'adm_region_obr.php',
                        type: 'POST',
                        data: { action: 'delete_user', id: id },
                        dataType: 'json',
                        success: function(response) {
                            console.log("Delete user response:", response);
                            if (response.success) {
                                toastr.success('Пользователь удалён');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                toastr.error('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Delete user AJAX error:", status, error, xhr.responseText);
                            toastr.error('Ошибка сервера: ' + error);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    });
                }
            }
        });
    });
});
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>