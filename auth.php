<?php
session_start();
include("inc/function.php"); // Тут висят все функции сайта.
echo '<!doctype html><html lang="ru">';
include("inc/style.php"); // тег head в котором указываются все стили сайта
echo '<body style = "background: #ffffff url(img/background.webp) repeat;">';
echo '<div class="container-sm">';
?>
<main role="main">
    <div class="jumbotron">
        <div style = "display: grid;place-items: center;" >
			<?

if (isset($_GET['err'])) {
	//ОШИБКА АВТОРИЗАЦИИ
	$error = h(e($_GET['err']));
	//alrt("Ошибка $error", "danger", "2");
?>
	<script type="text/javascript">
		alert('Ошибка <?= $error ?>')
		document.location.replace("auth.php");
	</script>
<?php
	exit();
}

?>
    <style>
        body {
            background: linear-gradient(133deg, #122f18ed, #323331c2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .auth-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 90px rgba(0, 0, 0, 0.5);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .auth-container img {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .auth-container h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .auth-container .btn-primary {
            background-color: #FFA726;
            border: none;
            padding: 0.6rem 1.2rem;
        }
        .auth-container .btn-secondary {
            background-color: #EF5350;
            border: none;
            color:white;
            padding: 0.6rem 1.2rem;
        }
        .auth-container a {
            display: block;
            margin-top: 1rem;
            color: #616161;
            text-decoration: none;
        }
        .auth-container a:hover {
            color: #000;
        }
    </style>
<?php
session_start(); // Для хранения данных о запросах пользователя
if (isset($_GET['reg'])) { ?>

	<head>
		<title>Регистрация</title>
		<style>
			/* Стили остаются без изменений + стили для таймера */
			.reg-card {
				background: #fff;
				border-radius: 15px;
				padding: 2rem;
				max-width: 450px;
				margin: 0 auto;
				box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
				animation: slideUp 0.5s ease-out;
			}
			@keyframes slideUp {
				from { transform: translateY(50px); opacity: 0; }
				to { transform: translateY(0); opacity: 1; }
			}
			.reg-card h2 {
				text-align: center;
				color: #333;
				margin-bottom: 1.5rem;
				font-size: 1.8rem;
			}
			.reg-card .form-control {
				border-radius: 8px;
				border: 1px solid #ddd;
				padding: 0.8rem;
				margin-bottom: 1rem;
				transition: border-color 0.3s, box-shadow 0.3s;
			}
			.reg-card .form-control:focus {
				border-color: #FFA726;
				box-shadow: 0 0 5px rgba(255, 167, 38, 0.5);
				outline: none;
			}
			.reg-card .btn-submit {
				background: #FFA726;
				color: #fff;
				border: none;
				padding: 0.8rem;
				border-radius: 8px;
				width: 100%;
				font-size: 1.1rem;
				transition: background 0.3s, transform 0.2s;
			}
			.reg-card .btn-submit:hover {
				background: #FB8C00;
				transform: translateY(-2px);
			}
			.reg-card img {
				display: block;
				margin: 0 auto 1.5rem;
				width: 100px;
				animation: bounce 2s infinite;
			}
			@keyframes bounce {
				0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
				40% { transform: translateY(-10px); }
				60% { transform: translateY(-5px); }
			}
			.info-text {
				text-align: center;
				color: #666;
				font-size: 0.9rem;
				margin-bottom: 1.5rem;
			}
			.info-text a {
				color: #FFA726;
				text-decoration: none;
			}
			.info-text a:hover {
				text-decoration: underline;
			}
			.success-message {
				color: #28a745;
				text-align: center;
				margin-top: 1rem;
				font-weight: bold;
				display: none;
			}
			.captcha-container {
				margin-bottom: 1rem;
				text-align: center;
			}
			.captcha-container label {
				display: block;
				margin-bottom: 0.5rem;
				color: #333;
			}
			.timer-container {
				text-align: center;
				color: #dc3545;
				margin-top: 1rem;
				font-weight: bold;
				display: none;
			}
		</style>
	</head>
	<body style="background: linear-gradient(135deg, #f5f7fa, #c3cfe2); height: 100vh; display: flex; align-items: center;">
		<div class="reg-card">
			<img src="img/mail.png" alt="mail">
			<h2>Регистрация</h2>
			<div class="info-text">
				Введите данные для создания учетной записи.<br>
				Они будут отправлены администратору <a href="https://rutbat.t.me">rutbat</a>.
			</div>
			<form id="regForm" method="POST" action="">
				<input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
				<input type="email" name="email" class="form-control" placeholder="Рабочая почта" required>
				<input type="text" name="fio" class="form-control" placeholder="Ф.И.О." required>
				<input type="text" name="region" class="form-control" placeholder="Регион" required>
				<input type="tel" name="phone" class="form-control" placeholder="Номер телефона (например, +79991234567)" required>
				<input type="password" name="password" class="form-control" placeholder="Пароль" required>
				<div class="captcha-container">
					<label id="captchaQuestion"></label>
					<input type="number" name="captcha" class="form-control" placeholder="Ответ" required>
				</div>
				<button type="submit" class="btn-submit">Отправить заявку</button>
			</form>
			<div id="successMessage" class="success-message">Заявка принята! Ожидайте подтверждения.</div>
			<div id="timerMessage" class="timer-container">Подождите <span id="timerSeconds"></span> сек.</div>
		</div>

		<!-- JavaScript с исправленным таймером и номером телефона -->
		<script>
			// Генерация капчи
			function generateCaptcha() {
				const num1 = Math.floor(Math.random() * 10);
				const num2 = Math.floor(Math.random() * 10);
				const answer = num1 + num2;
				document.getElementById('captchaQuestion').textContent = `${num1} + ${num2} = ?`;
				return answer;
			}
			let captchaAnswer = generateCaptcha();

			// Rate limiting: 1 запрос в минуту
			const rateLimit = {
				key: 'regFormRateLimit',
				maxAttempts: 1,
				timeWindow: 60 * 1000, // 1 минута
				check: function() {
					const now = Date.now();
					let data = JSON.parse(localStorage.getItem(this.key)) || { attempts: 0, start: now };
					if (now - data.start > this.timeWindow) {
						data = { attempts: 0, start: now };
					}
					if (data.attempts >= this.maxAttempts) {
						return false;
					}
					return true; // Увеличиваем attempts только при успешной отправке
				},
				increment: function() {
					const now = Date.now();
					let data = JSON.parse(localStorage.getItem(this.key)) || { attempts: 0, start: now };
					if (now - data.start > this.timeWindow) {
						data = { attempts: 0, start: now };
					}
					data.attempts++;
					localStorage.setItem(this.key, JSON.stringify(data));
				},
				getRemainingTime: function() {
					const data = JSON.parse(localStorage.getItem(this.key));
					if (!data || !data.start) return 0;
					const now = Date.now();
					const elapsed = now - data.start;
					return Math.max(0, Math.ceil((this.timeWindow - elapsed) / 1000));
				}
			};

			// Функция обновления таймера
			function updateTimer() {
				const remainingTime = rateLimit.getRemainingTime();
				const timerMessage = document.getElementById('timerMessage');
				const timerSeconds = document.getElementById('timerSeconds');
				if (remainingTime > 0) {
					timerMessage.style.display = 'block';
					timerSeconds.textContent = remainingTime;
				} else {
					timerMessage.style.display = 'none';
				}
			}

			// Запуск таймера
			setInterval(updateTimer, 1000);

			document.getElementById('regForm').addEventListener('submit', function(e) {
				e.preventDefault();
				const form = this;

				// Проверка rate limiting
				if (!rateLimit.check()) {
					const remainingTime = rateLimit.getRemainingTime();
					alert('Часто отправляете запрос! Подождите ' + remainingTime + ' сек. перед следующей попыткой.');
					updateTimer();
					return;
				}

				// Получение данных формы
				const email = form.email.value.trim();
				const fio = form.fio.value.trim();
				const region = form.region.value.trim();
				const phone = form.phone.value.trim();
				const password = form.password.value.trim();
				const captcha = parseInt(form.captcha.value);
				const csrfToken = form.csrf_token.value;

				// Валидация ввода
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if (!emailRegex.test(email)) {
					alert('Введите корректный email!');
					return;
				}
				if (fio.length < 2 || fio.length > 100) {
					alert('Ф.И.О. должно быть от 2 до 100 символов!');
					return;
				}
				if (region.length < 2 || region.length > 50) {
					alert('Регион должен быть от 2 до 50 символов!');
					return;
				}
				const phoneRegex = /^\+?[0-9]{10,15}$/;
				if (!phoneRegex.test(phone)) {
					alert('Введите корректный номер телефона (например, +79991234567)!');
					return;
				}
				if (password.length < 6 || password.length > 50) {
					alert('Пароль должен быть от 6 до 50 символов!');
					return;
				}

				// Проверка капчи
				if (captcha !== captchaAnswer) {
					alert('Неверный ответ на капчу!');
					captchaAnswer = generateCaptcha();
					form.captcha.value = '';
					return;
				}

				// Экранирование для MarkdownV2
				const escapeMarkdown = (text) => {
					return text.replace(/([_*[\]()~`>#+\-=|{}.!])/g, '\\$1');
				};

				const message = `*Новая заявка на регистрацию\\!* 🎉\n` +
					`*Почта:* \`${escapeMarkdown(email)}\` 📧\n` +
					`*Ф\\.И\\.О\\.:* _${escapeMarkdown(fio)}_ 👤\n` +
					`*Регион:* ${escapeMarkdown(region)} 🌍\n` +
					`*Телефон:* \`${escapeMarkdown(phone)}\` 📱\n` +
					`*Пароль:* ||${escapeMarkdown(password)}|| 🔒`;

				const botToken = '7013371542:AAEax5BDgEmw4QB_fWDR1bWBQI1Fhc2M_GY';
				const chatId = '1002640575'; // Ваш личный ID (rutbat)
				const url = `https://api.telegram.org/bot${botToken}/sendMessage`;

				fetch(url, {
					method: 'POST',
					headers: { 
						'Content-Type': 'application/json',
						'X-CSRF-Token': csrfToken
					},
					body: JSON.stringify({
						chat_id: chatId,
						text: message,
						parse_mode: 'MarkdownV2'
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.ok) {
						rateLimit.increment(); // Увеличиваем счётчик только при успешной отправке
						const successMessage = document.getElementById('successMessage');
						successMessage.style.display = 'block';
						setTimeout(() => {
							successMessage.style.display = 'none';
						}, 5000);
						form.reset();
						captchaAnswer = generateCaptcha();
						updateTimer(); // Обновляем таймер после успешной отправки
					} else {
						alert('Ошибка при отправке: ' + data.description);
					}
				})
				.catch(error => {
					console.error('Ошибка:', error);
					alert('Произошла ошибка. Проверьте подключение.');
				});
			});
		</script>
	</body>
<?php
	include 'inc/foot.php';
	exit();
}
?>

<head>
	<title>Авторизация</title>
</head>
	<form method="POST" action="auth_obr.php">

	<div class="auth-container">
        <img src="img/logo.webp" alt="Логотип">
        <p>Авторизуйтесь или создайте новый профиль.</p>
        
            <div class="mb-3">
                <input name="login" type="text" class="form-control" placeholder="Введите логин" required>
            </div>
            <div class="mb-3">
                <input name="pass" type="password" class="form-control" placeholder="Пароль" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Войти</button>
        
        <a href="auth.php?reg" class="btn btn-secondary w-100 mt-3">Новый пользователь?</a>
        <a href="https://rutbat.t.me">Забыли пароль?</a>
    </div>





	</form>

