<?php
session_start();
include "inc/head.php";
AutorizeProtect();
access();
global $usr, $connect;

$fio = $_GET['fio'] ?? $usr['fio'];
$current_month = date('Y-m');
   
if ($usr['admin'] != "1" && $usr['name'] != "RutBat" && $fio != $usr['fio']) {
    die("Ошибка: недостаточно прав.");
}

$user_query = $connect->prepare("SELECT id FROM user WHERE fio = ?");
$user_query->bind_param("s", $fio);
$user_query->execute();
$target_user = $user_query->get_result()->fetch_assoc() ?? die("Ошибка: пользователь не найден.");

$finance_query = $connect->prepare("SELECT current_salary, official_employment FROM user_finance WHERE user_id = ? AND month = ?");
$finance_query->bind_param("is", $target_user['id'], $current_month);
$finance_query->execute();
$finance = $finance_query->get_result()->fetch_assoc();

if (!$finance) {
    $default_salary = 24000;
    $default_employment = 'Нет';
    $stmt = $connect->prepare("INSERT INTO user_finance (user_id, month, current_salary, official_employment, last_update) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $target_user['id'], $current_month, $default_salary, $default_employment);
    $stmt->execute();
    $finance = ['current_salary' => $default_salary, 'official_employment' => $default_employment];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_salary = $_POST['current_salary'] ?? 24000;
    $official_employment = $_POST['official_employment'] ?? 'Нет';
    
    $update_finance = $connect->prepare("UPDATE user_finance SET current_salary = ?, official_employment = ?, last_update = NOW() WHERE user_id = ? AND month = ?");
    $update_finance->bind_param("ssis", $current_salary, $official_employment, $target_user['id'], $current_month);
    $update_finance->execute();
    
    header("Location: zp.php" . ($fio != $usr['fio'] ? "?fio=$fio" : ""));
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>Настройки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #ddd;
            padding: 15px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: none;
            outline: none;
        }
        .btn-save {
            background: #28a745;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 1.2rem;
            color: white;
            width: 100%;
            transition: background 0.3s;
        }
        .btn-save:hover {
            background: #218838;
        }
        @media (max-width: 400px) {
            .form-container {
                padding: 10px;
            }
            .form-control, .form-select, .btn-save {
                padding: 12px;
                font-size: 1rem;
            }
            .form-group label {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form method="POST" action="">
            <div class="form-group">
                <label>Оклад (₽)</label>
                <input type="number" class="form-control" name="current_salary" 
                    value="<?= htmlspecialchars($finance['current_salary']) ?>" required>
            </div>
            <div class="form-group">
                <label>Офиц. трудоустройство</label>
                <select class="form-select" name="official_employment" required>
                    <option value="Да" <?= $finance['official_employment'] == 'Да' ? 'selected' : '' ?>>Да</option>
                    <option value="Нет" <?= $finance['official_employment'] == 'Нет' ? 'selected' : '' ?>>Нет</option>
                </select>
            </div>
            <button type="submit" class="btn-save">💾</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'inc/foot.php'; ?>

</body>
</html>