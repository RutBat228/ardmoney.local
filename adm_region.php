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
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
    echo '<div class="container mt-4"><h1 class="text-danger">Ошибка: Нет доступа</h1></div>';
    include 'inc/foot.php';
    exit;
}

// Получаем уникальные регионы с ID из таблицы config
$regions = $connect->query("SELECT c.id, c.region, c.monthly_bonus, COUNT(u.id) as user_count 
                            FROM config c 
                            LEFT JOIN user u ON u.region = c.region 
                            GROUP BY c.id, c.region, c.monthly_bonus 
                            ORDER BY c.region")->fetch_all(MYSQLI_ASSOC);

// Добавляем запись "Без региона" отдельно, если есть пользователи без региона
$noRegion = $connect->query("SELECT COUNT(id) as user_count 
                             FROM user 
                             WHERE region = 'Без региона' OR region IS NULL")->fetch_assoc();
if ($noRegion['user_count'] > 0) {
    $regions[] = ['id' => null, 'region' => 'Без региона', 'monthly_bonus' => 8.00, 'user_count' => $noRegion['user_count']];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление районами</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .region-card { 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            background: #fff; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            transition: all 0.3s ease; 
            cursor: pointer; 
            position: relative; 
            text-align: center; 
            opacity: 0; 
        }
        .region-card.visible { opacity: 1; }
        .region-card:hover { 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
            transform: translateY(-5px); 
        }
        .region-card.no-region { 
            background: #f1b88685; 
        }
        .region-name { font-size: 1.5rem; font-weight: bold; margin-bottom: 10px; }
        .user-count { font-size: 1rem; color: #666; }
        .bonus { position: absolute; top: 10px; right: 10px; font-size: 1rem; color: #28a745; font-weight: bold; }
        .action-buttons { display: flex; margin-top: 20px; }
        .action-buttons button { flex: 1; border-radius: 0; padding: 10px; font-size: 1rem; transition: all 0.3s ease; }
        .action-buttons .edit-btn { border-bottom-left-radius: 8px; background: #28a745; color: white; }
        .action-buttons .delete-btn { border-bottom-right-radius: 8px; background: #dc3545; color: white; }
        .action-buttons button:hover { filter: brightness(90%); }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Управление районами</h1>
    <div class="row">
        <?php foreach ($regions as $region) { 
            $regionName = htmlspecialchars($region['region']);
            $isNoRegion = ($region['region'] === 'Без региона') ? 'no-region' : '';
            $regionId = $region['id'] ?? null;
        ?>
            <div class="col-md-4">
                <div class="region-card <?php echo $isNoRegion; ?>" 
                     data-id="<?php echo $regionId; ?>" 
                     data-name="<?php echo htmlspecialchars($region['region']); ?>">
                    <div class="bonus"><?php echo number_format($region['monthly_bonus'] ?? 8.00, 2); ?>%</div>
                    <div class="region-name"><?php echo $regionName; ?></div>
                    <div class="user-count">Пользователей: <?php echo $region['user_count']; ?></div>
                    <?php if (($isOwner || $isSuperAdmin || ($isAdmin && $region['region'] === $usr['region'])) && $region['region'] !== 'Без региона') { ?>
                        <div class="action-buttons">
                            <button class="btn edit-btn edit-region-btn" data-name="<?php echo htmlspecialchars($region['region']); ?>">
                                <i class="bi bi-pencil"></i> Редактировать
                            </button>
                            <button class="btn delete-btn delete-region-btn" data-name="<?php echo htmlspecialchars($region['region']); ?>">
                                <i class="bi bi-trash"></i> Удалить
                            </button>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="modal fade" id="editRegionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать район</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRegionForm">
                    <input type="hidden" name="old_name">
                    <div class="mb-3">
                        <label class="form-label">Название</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ежемесячная премия (%)</label>
                        <input type="number" class="form-control" name="bonus" step="0.01" min="0" max="200" value="10.00" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить</button>
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
    console.log("Page loaded, initializing scripts");

    // Настройки toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    gsap.fromTo(".region-card", 
        { opacity: 0, y: 50 },
        { opacity: 1, y: 0, duration: 0.8, stagger: 0.2, ease: "power2.out", onComplete: function() { $(".region-card").addClass("visible"); } }
    );

    $('.region-card').click(function(e) {
        if (!$(e.target).closest('.edit-region-btn, .delete-region-btn').length) {
            const id = $(this).data('id');
            const name = $(this).data('name');
            // Если ID есть, используем его, иначе используем name (для "Без региона")
            if (id) {
                window.location.href = `region_info.php?id=${id}`;
            } else {
                window.location.href = `region_info.php?name=${encodeURIComponent(name)}`;
            }
        }
    });

    $('.edit-region-btn').click(function(e) {
        e.stopPropagation();
        const name = $(this).data('name');
        
        toastr.info('Получаем данные района');
        
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'GET',
            data: { name: name, type: 'region' },
            dataType: 'json',
            success: function(data) {
                if (data && !data.error) {
                    $('#editRegionForm [name="old_name"]').val(name);
                    $('#editRegionForm [name="name"]').val(data.region);
                    $('#editRegionForm [name="bonus"]').val(data.monthly_bonus);
                    $('#editRegionModal').modal('show');
                } else {
                    toastr.error(data.error || 'Район не найден', 'Ошибка');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Ошибка загрузки данных: ' + error, 'Ошибка');
            }
        });
    });

    $('#editRegionForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=edit_region';
        
        $('#editRegionModal').modal('hide');
        
        toastr.info('Сохраняем изменения', 'Обработка');
        
        $.ajax({
            url: 'adm_region_obr.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Район обновлен', 'Успех');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.error || 'Не удалось обновить район', 'Ошибка');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Ошибка сервера: ' + error, 'Ошибка');
            }
        });
    });

    $('.delete-region-btn').click(function(e) {
        e.stopPropagation();
        const name = $(this).data('name');
        
        bootbox.confirm({
            title: 'Вы уверены?',
            message: 'Пользователи будут перемещены в "Без региона"',
            buttons: {
                confirm: { label: 'Да, удалить', className: 'btn-danger' },
                cancel: { label: 'Отмена', className: 'btn-secondary' }
            },
            callback: function(result) {
                if (result) {
                    toastr.info('Удаляем район', 'Обработка');
                    
                    $.ajax({
                        url: 'adm_region_obr.php',
                        type: 'POST',
                        data: { action: 'delete_region', name: name },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Район удален', 'Успех');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                toastr.error(response.error || 'Не удалось удалить район', 'Ошибка');
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Ошибка сервера: ' + error, 'Ошибка');
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