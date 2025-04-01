<?php
// Файл: ardmoney.ru/api/admin_update.php

include '../inc/db.php';
$logFile = __DIR__ . '/update_log.txt';

function logToFile($message, $file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = $_POST['version'] ?? '';
    $changelog = $_POST['changelog'] ?? '';

    if (!empty($version) && !empty($changelog)) {
        $stmt = $connect->prepare("INSERT INTO apps (version, changelog) VALUES (?, ?)");
        $stmt->bind_param("ss", $version, $changelog);
        if ($stmt->execute()) {
            logToFile("Добавлена новая версия: $version", $logFile);
            $message = "Версия $version успешно добавлена!";
        } else {
            $message = "Ошибка добавления версии: " . $connect->error;
        }
        $stmt->close();
    } else {
        $message = "Заполните все поля!";
    }
}

// Получаем последнюю версию
$query = "SELECT version, changelog FROM apps ORDER BY version DESC LIMIT 1";
$result = $connect->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentVersion = $row['version'];
    $currentChangelog = $row['changelog'];
} else {
    $currentVersion = "Нет данных";
    $currentChangelog = "Нет данных";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка ArdMoney</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #434f3a 0%, #5d6b52 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        #particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .admin-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 700px;
            text-align: center;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .admin-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            animation: rotateGlow 10s linear infinite;
            z-index: -1;
        }

        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h1 {
            color: #434f3a;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-control, .btn {
            border-radius: 10px;
        }

        .btn-submit {
            background: linear-gradient(45deg, #434f3a, #2f3a2a);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(45deg, #2f3a2a, #434f3a);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .current-version {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div id="particles"></div>
    <div class="admin-container animate__animated animate__fadeInUp">
        <h1 class="animate__animated animate__bounceIn">Админка ArdMoney</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-info animate__animated animate__fadeIn"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="current-version animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
            <p>Текущая версия: <strong><?php echo htmlspecialchars($currentVersion); ?></strong></p>
            <p>Changelog: <br><?php echo nl2br(htmlspecialchars($currentChangelog)); ?></p>
        </div>

        <form method="POST" class="animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
            <div class="mb-3">
                <label for="version" class="form-label">Новая версия</label>
                <input type="text" class="form-control" id="version" name="version" placeholder="Например, 2.2.3" required>
            </div>
            <div class="mb-3">
                <label for="changelog" class="form-label">Changelog</label>
                <textarea class="form-control" id="changelog" name="changelog" rows="5" placeholder="Опишите изменения" required></textarea>
            </div>
            <button type="submit" class="btn btn-submit">Добавить обновление</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS('particles', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: false },
                move: { enable: true, speed: 2, direction: 'none', random: true, straight: false, out_mode: 'out' }
            },
            interactivity: {
                detect_on: 'canvas',
                events: { onhover: { enable: true, mode: 'repulse' }, onclick: { enable: true, mode: 'push' }, resize: true },
                modes: { repulse: { distance: 100, duration: 0.4 }, push: { particles_nb: 4 } }
            },
            retina_detect: true
        });
    </script>
</body>
</html>
<?php $connect->close(); ?>