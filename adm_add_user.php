<?php
include "inc/head.php";
AutorizeProtect();
access();
animate();
global $connect;
global $usr;

// Проверка прав доступа
$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");

if (!$isOwner && !$isSuperAdmin) {
    echo '<script>alert("Нет доступа"); document.location.replace("/");</script>';
    exit;
}

$all_regions = $connect->query("SELECT region FROM config ORDER BY region")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление пользователя</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .is-invalid-login { border-color: #dc3545; }
        .login-feedback { color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Добавление пользователя</h1>
    
    <div class="modal-body">
        <form id="addUserForm">
            <div class="mb-3">
                <label class="form-label">ФИО</label>
                <input type="text" class="form-control" name="fio">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
            </div>
            <div class="mb-3">
                <label class="form-label">Логин <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="loginInput" required>
                <div class="login-feedback" id="loginFeedback"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Пароль <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="pass" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Район <span class="text-danger">*</span></label>
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
                <select class="form-control" name="position">
                    <option value="Техник 1 разряда">Техник 1 разряда</option>
                    <option value="Техник 2 разряда">Техник 2 разряда</option>
                    <option value="Техник 3 разряда">Техник 3 разряда</option>
                    <option value="Мастер участка">Мастер участка</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" id="submitAddUser"><i class="bi bi-save"></i> Сохранить</button>
            <a href="admin_sks.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Назад</a>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
<script>
$(document).ready(function() {
    // Настройки toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
    
    gsap.from(".container", { opacity: 0, y: 50, duration: 0.8, ease: "power2.out" });

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
                        console.error("Check login AJAX error:", status, error);
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
        
        // Показываем индикатор загрузки с помощью toastr
        toastr.info('Добавляем пользователя', 'Загрузка');
        
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Пользователь добавлен', 'Успех');
                    setTimeout(() => {
                        window.location.href = 'admin_sks.php';
                    }, 1000);
                } else {
                    toastr.error(response.error || 'Не удалось добавить пользователя', 'Ошибка');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Ошибка сервера: ' + error, 'Ошибка');
            }
        });
    });
});
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>