<?php
include "inc/head.php";
AutorizeProtect();
access();
animate();
global $usr;
global $connect; // Добавляем подключение к БД

// Проверка прав доступа
$isOwner = ($usr['name'] === "RutBat");
$isSuperAdmin = ($usr['name'] === "tretjak");
$isAdmin = ($usr['rang'] === "Мастер участка" || $usr['admin'] == 1);
$isTechnician = in_array($usr['rang'], ["Техник 1 разряда", "Техник 2 разряда", "Техник 3 разряда"]) && !$usr['admin'];

if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
    echo '<div class="container py-5"><h1 class="text-danger text-center">Ошибка: Нет доступа</h1></div>';
    include 'inc/foot.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>СКС ПАНЕЛЬ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #fff;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
        }
        .row {
            margin-left: -5px;
            margin-right: -5px;
        }
        .col-4 {
            padding-left: 5px;
            padding-right: 5px;
        }
        .card {
            border: 1px solid #007bff;
            border-radius: 5px;
            background: #fff;
            margin-bottom: 10px;
            padding: 10px;
            height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .card-icon {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        .card-title {
            font-size: 0.9rem;
            margin: 0;
            color: #000;
        }
        .card-subtitle {
            font-size: 0.8rem;
            margin-top: 2px;
            color: #666;
        }
        a.stretched-link {
            position: relative;
            z-index: 1;
            color: #000;
            text-decoration: none;
        }
        a.card-subtitle-link {
            color: #666;
            text-decoration: none;
        }
        h1 {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h1>Административное управление</h1>

    <?php if ($isOwner || $isSuperAdmin || $isAdmin) { ?>
        <div class="row">
            <div class="col-4">
                <div class="card">
                    <i class="fas fa-boxes card-icon text-danger"></i>
                    <h5 class="card-title">
                        <a href="adm_material.php" class="stretched-link">Управление материалами</a>
                    </h5>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <i class="fas fa-tools card-icon text-info"></i>
                    <h5 class="card-title">
                        <a href="adm_vidrabot.php" class="stretched-link">Управление видами работ</a>
                    </h5>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <i class="fas fa-user card-icon text-success"></i>
                    <h5 class="card-title">
                        <?php
                        // Получаем ID региона текущего пользователя
                        $region_id = null;
                        if (!empty($usr['region'])) {
                            $stmt = $connect->prepare("SELECT id FROM config WHERE region = ?");
                            $stmt->bind_param("s", $usr['region']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $region_id = $row['id'];
                            }
                            $stmt->close();
                        }
                        ?>
                        <a href="region_info.php?<?php echo $region_id ? 'id=' . $region_id : 'name=' . urlencode($usr['region']); ?>" class="stretched-link">Управление</a>
                    </h5>
                    <p class="card-subtitle">
                        <a href="region_info.php?<?php echo $region_id ? 'id=' . $region_id : 'name=' . urlencode($usr['region']); ?>" class="card-subtitle-link"><?php echo htmlspecialchars($usr['region'] ?? 'Без региона'); ?></a>
                    </p>
                </div>
            </div>

            <?php if ($isOwner || $isSuperAdmin) { ?>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-map-marked-alt card-icon text-primary"></i>
                        <h5 class="card-title">
                            <a href="adm_region.php" class="stretched-link">Управление районами</a>
                        </h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-user-plus card-icon text-purple"></i>
                        <h5 class="card-title">
                            <a href="adm_add_user.php" class="stretched-link">Добавить пользователя</a>
                        </h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-star card-icon text-successe"></i>
                        <h5 class="card-title">
                            <a href="adm_prazdnik.php" class="stretched-link">Управление праздничными днями</a>
                        </h5>
                    </div>
                </div>
            <?php } ?>

            <?php if ($isOwner) { ?>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-server card-icon text-warning"></i>
                        <h5 class="card-title">
                            <a href="https://ardmoney.ru:8888" class="stretched-link">FastPanel</a>
                        </h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-shield-alt card-icon text-secondary"></i>
                        <h5 class="card-title">
                            <a href="https://ardmoney.ru:1488/rutbat" class="stretched-link">ArdVPN</a>
                        </h5>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-download card-icon text-dark"></i>
                        <h5 class="card-title">
                            <a href="/backup/backup_last.zip" class="stretched-link">Бекап сайта</a>
                        </h5>
                        <p class="card-subtitle">
                            <a href="/backup/backup_last.sql" class="card-subtitle-link">Бекап базы</a>
                        </p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <i class="fas fa-mobile-alt card-icon text-primary"></i>
                        <h5 class="card-title">
                            <a href="/api/admin_update.php" class="stretched-link">Обновления приложения</a>
                        </h5>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
    gsap.from(".card", {
        duration: 1,
        y: 50,
        opacity: 0,
        stagger: 0.2
    });
</script>
</body>
</html>

<?php include 'inc/foot.php'; ?>