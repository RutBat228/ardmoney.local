<?php
// Файл: ardmoney.ru/api/update_info.php

// Подключаем файл с настройками базы данных
include '../inc/db.php';

// Путь к файлу логов
$logFile = __DIR__ . '/update_log.txt';

// Функция для записи в лог
function logToFile($message, $file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
}

// Получаем текущую версию из базы данных
$query = "SELECT version, changelog FROM apps ORDER BY version DESC LIMIT 1";
$result = $connect->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentVersion = $row['version'];
    $changelogText = $row['changelog'];
    $downloadUrl = "https://ardmoney.ru/ardmoney.apk"; // Можно добавить в БД
} else {
    $currentVersion = "2.3.6";
    $changelogText = "Начальная версия";
    $downloadUrl = "https://ardmoney.ru/ardmoney.apk";
}

$userVersion = isset($_GET['version']) ? $_GET['version'] : "Не указана";
logToFile("Посещение update_info.php с версией: $userVersion", $logFile);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обновление ArdMoney</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .update-container {
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

        .update-container::before {
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
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .version-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .version-info:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .update-arrow {
            font-size: 40px;
            color: #434f3a;
            margin: 0;
            animation: bounceArrow 1.5s infinite;
        }

        @keyframes bounceArrow {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .update-text {
            color: #434f3a;
            font-style: italic;
            margin-bottom: 20px;
        }

        .btn-download {
            background: linear-gradient(45deg, #434f3a, #2f3a2a);
            border: none;
            padding: 12px 30px;
            font-size: 18px;
            border-radius: 50px;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
        }

        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #2f3a2a, #434f3a);
        }

        .btn-download::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-download:hover::after {
            width: 300px;
            height: 300px;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .success-message {
            padding: 20px;
            background: #e6ffe6;
            border-radius: 15px;
            border: 2px solid #434f3a;
            color: #434f3a;
            font-size: 18px;
            text-align: center;
            animation: fadeInSuccess 0.5s ease-in;
        }

        @keyframes fadeInSuccess {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .changelog {
            background: #f1f3f5;
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
            text-align: left;
            transition: all 0.3s ease;
            border-left: 5px solid #434f3a;
        }

        .changelog:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .changelog h2 {
            color: #434f3a;
            font-size: 24px;
            margin-bottom: 20px;
            position: relative;
            padding-left: 30px;
        }

        .changelog h2::before {
            content: '\f055';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #434f3a;
            position: absolute;
            left: 0;
            top: 2px;
        }

        .changelog ul {
            list-style: none;
            padding-left: 0;
        }

        .changelog li {
            position: relative;
            padding-left: 35px;
            margin-bottom: 15px;
            color: #333;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .changelog li:hover {
            color: #434f3a;
            transform: translateX(5px);
        }

        .changelog li::before {
            content: '\f058';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #434f3a;
            position: absolute;
            left: 0;
            top: 2px;
            font-size: 18px;
        }

        .changelog li strong {
            color: #2f3a2a;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="particles"></div>
    <div id="update-container" class="update-container animate__animated animate__fadeInUp">
        <h1 class="animate__animated animate__bounceIn">Обновление ArdMoney</h1>
        <div class="version-info animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
            <p>Ваша текущая версия: <strong><?php echo htmlspecialchars($userVersion); ?></strong></p>
        </div>
        <div class="update-arrow animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="version-info animate__animated animate__fadeIn" style="animation-delay: 0.8s;">
            <p>Новая версия: <strong><?php echo htmlspecialchars($currentVersion); ?></strong></p>
        </div>
        <p class="animate__animated animate__fadeIn" style="animation-delay: 1s;">Обновите приложение, чтобы получить новые функции и улучшения!</p>
        <a href="<?php echo htmlspecialchars($downloadUrl); ?>" id="download-link" class="btn btn-download pulse animate__animated animate__pulse" style="animation-delay: 1.2s; text-decoration: none;">Скачать обновление</a>

        <div class="changelog animate__animated animate__fadeIn" style="animation-delay: 1.4s;">
            <h2>Список изменений</h2>
            <?php
            $changelogLines = explode("\n", $changelogText);
            echo '<ul>';
            foreach ($changelogLines as $index => $line) {
                if (trim($line) !== '') {
                    $delay = 1.6 + ($index * 0.2);
                    echo "<li class=\"animate__animated animate__fadeInLeft\" style=\"animation-delay: {$delay}s;\">" . htmlspecialchars($line) . "</li>";
                }
            }
            echo '</ul>';
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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

    // Обработка клика по ссылке "Скачать"
    document.getElementById('download-link').addEventListener('click', function(event) {
        // Показываем сообщение об успехе перед переходом в браузер
        const container = document.getElementById('update-container');
        container.innerHTML = `
            <div class="success-message">
                <h2>Успех!</h2>
                <p>Если телефон поддерживает то скачивание в приложении начнётся в течении пары секунд.</p>
                <i class="fas fa-check-circle" style="font-size: 50px; color: #434f3a; margin-top: 20px;"></i>
            </div>
        `;
        
        // Показываем дополнительную ссылку через 3 секунды
        setTimeout(() => {
            const successMessage = container.querySelector('.success-message');
            if (successMessage) {
                successMessage.innerHTML += `
                    <div style="margin-top: 20px;">
                        <p>Если загрузка не началась, используйте прямую ссылку:</p>
                        <a href="https://ardmoney.ru/ardmoney.apk" style="color: #434f3a; text-decoration: underline;">Скачать напрямую</a>
                    </div>
                `;
            }
        }, 3000); // Задержка 3 секунды для показа ссылки

        // Задержка перед переходом, чтобы пользователь увидел сообщение
        setTimeout(() => {
            // Разрешаем стандартное поведение ссылки (открытие в браузере)
            window.location.href = this.getAttribute('href');
        }, 1000); // Задержка 1 секунда для начала скачивания
        
        event.preventDefault(); // Предотвращаем немедленный переход
    });
</script>
</body>
</html>
<?php $connect->close(); ?>