<style>
	nav {
            position: relative;
            width: 100%;
        }

        nav img.logo {
            width: 100%;
            max-height: 100%;
            object-fit: contain; /* Сохраняет пропорции изображения */
        }

        /* Стили для иконки Home в правом нижнем углу логотипа */
        nav .home-icon {
            position: absolute;
            bottom: 10px; /* Отступ снизу */
            right: 10px; /* Отступ справа */
            font-size: 24px; /* Размер иконки */
            color: #ffffff; /* Цвет иконки (белый для контраста) */
            text-decoration: none; /* Убираем подчёркивание */
            z-index: 1001; /* Чтобы иконка была выше логотипа */
        }

        nav .home-icon:hover {
            color: #e0e0e0; /* Лёгкое изменение цвета при наведении */
        }
</style>
<nav>
        <a href="/navigard/index.php">
            <img src="/navigard/img/4.png" alt="NavigArd" class="logo">
        </a>
        <!-- Иконка Home в правом нижнем углу логотипа -->
        <a href="/navigard/user.php" class="home-icon">
            <i class="fas fa-home"></i> <!-- Иконка дома из Font Awesome -->
        </a>
    </nav>