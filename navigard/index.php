<?php
include(__DIR__ . '/../inc/function.php');
include(__DIR__ . '/../inc/style.php');

?>
<!-- Font Awesome 6.2.1 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">
<?php
AutorizeProtect();
global $usr, $connect;

// Получаем общее количество домов из базы
$query = "SELECT COUNT(*) as total FROM navigard_adress";
$result = mysqli_query($connect, $query);
$row = mysqli_fetch_assoc($result);
$total_houses = $row['total'];

// Получаем 4 случайных адреса для примеров
$random_examples_query = "SELECT id, adress FROM navigard_adress ORDER BY RAND() LIMIT 4";
$random_examples_result = mysqli_query($connect, $random_examples_query);
$random_examples = [];
while ($example = mysqli_fetch_assoc($random_examples_result)) {
    $random_examples[] = $example;
}
?>

<!-- Добавляем стиль для переопределения цвета primary -->
<style>
:root {
    --bs-primary: #434e38;
    --bs-primary-rgb: 67, 78, 56;
}
.btn-primary {
    background-color: #434e38;
    border-color: #434e38;
}
.btn-primary:hover, .btn-primary:focus, .btn-primary:active {
    background-color: #353e2d !important;
    border-color: #353e2d !important;
}
.text-primary {
    color: #434e38 !important;
}
.bg-primary {
    background-color: #434e38 !important;
}
.btn-outline-primary {
    color: #434e38;
    border-color: #434e38;
}
.btn-outline-primary:hover {
    background-color: #434e38;
    border-color: #434e38;
}
/* Стили для блока результатов поиска */
.search-results {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #eaeaea !important;
}

.search-results-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    font-weight: 500;
}

.search-results .hover-bg-light {
    transition: background-color 0.2s ease;
    position: relative;
    overflow: hidden;
}

.search-results .hover-bg-light:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.search-results .hover-bg-light:hover::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background-color: #434e38;
    animation: slideIn 0.2s ease;
}

@keyframes slideIn {
    from { height: 0; opacity: 0; }
    to { height: 100%; opacity: 1; }
}

.search-results a {
    color: #343a40;
    text-decoration: none;
}

.search-results a:hover {
    color: #434e38 !important;
    font-weight: 500;
}

.search-results .fa-map-marker-alt {
    color: #434e38;
    font-size: 1.2rem;
    transition: transform 0.2s ease;
}

.search-results .hover-bg-light:hover .fa-map-marker-alt {
    transform: scale(1.2);
    color: #434e38;
}

.gsap-display {
    transition: box-shadow 0.3s ease;
}

.gsap-display:hover {
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15) !important;
}

/* Анимация для поля поиска при фокусе */
.search-input:focus {
    border-color: #434e38 !important;
    box-shadow: 0 0 15px rgba(67, 78, 56, 0.3) !important;
}
</style>

<!-- Современный дизайн с белым фоном, оптимизированный для отображения на одном экране -->
<div class="container-fluid p-0 bg-white min-vh-100 d-flex flex-column">
    <div class="profile-header bg-primary text-white p-4 text-center position-relative mb-4">
        <h1 class="h3 mt-2 mb-3 fw-bold">Поиск информации о доме</h1>
        <div class="d-flex justify-content-center align-items-center">
            <div class="badge bg-white text-primary px-3 py-2 fs-6 shadow-sm">
                <i class="fa-solid fa-building me-1"></i> <?php echo $total_houses; ?> домов в базе
            </div>
        </div>
    </div>
    
    <div class="container py-4 flex-grow-1" style="padding-bottom: 6rem !important;">
        <!-- Главный блок с поиском - увеличен и акцентирован -->
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-9 mx-auto"> <!-- Центрирован блок поиска -->
                <!-- Блок поиска - главный элемент, увеличен и выделен -->
                <div class="card border-0 shadow-lg rounded-4 mb-4 gsap-search"> <!-- Увеличена тень и скругление -->
                    <div class="card-body p-4"> <!-- Увеличен padding -->
                        <form id="navigard_search" method="GET" action="result.php">
                            <div class="input-group input-group-lg">
                                <input type="text" 
                                       autocomplete="off" 
                                       id="search" 
                                       name="adress" 
                                       class="form-control search-input shadow-none py-3" 
                                       required
                                       title="Введите от 4 символов" 
                                       placeholder=""
                                       style="border: 1px solid #e0e0e0; border-radius: 0.5rem; font-size: 1rem;">
                                <input type="hidden" id="adress_id" name="adress_id">
                            </div>
                            
                            <!-- Результаты автозаполнения (перемещены внутрь формы) -->
                            <div id="display" class="card shadow-lg rounded-3 overflow-hidden gsap-display search-results mt-2" style="display:none; border: none; z-index:20;"></div>
                            
                            <!-- Подсказки под строкой поиска (добавлен id для управления видимостью) -->
                            <div class="mt-3" id="examples-block">
                                <small class="text-muted">Примеры:</small>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($random_examples as $index => $example): ?>
                                    <span class="badge bg-light text-primary py-2 px-3" style="cursor:pointer" 
                                          onclick="fillExample('<?= htmlspecialchars($example['adress']) ?>')">
                                        <?= htmlspecialchars($example['adress']) ?>
                                    </span>
                                    <?php if ($index < count($random_examples) - 1): ?>
                                    <span class="text-muted">•</span>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Карточка добавления нового дома - теперь вся кликабельная -->
                <div class="row justify-content-center mt-4"> <!-- Увеличен отступ сверху -->
                    <div class="col-lg-6 col-md-8"> <!-- Уменьшена ширина карточки -->
                        <a href="add_house.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm hover-card gsap-buttons"> <!-- Уменьшена тень -->
                                <div class="card-body p-3 d-flex align-items-center"> <!-- Горизонтальное расположение -->
                                    <div class="icon-circle bg-primary p-2 me-3 rounded-circle" style="width: 50px; height: 50px;"> <!-- Уменьшен размер иконки -->
                                        <i class="fa-solid fa-plus fa-lg text-white"></i> <!-- Белая иконка + -->
                                    </div>
                                    <div class="text-start">
                                        <h3 class="h6 mb-1 text-dark">Добавить новый дом</h3> <!-- Уменьшен размер заголовка -->
                                        <p class="text-muted mb-0 small">Добавление новой информации в базу данных</p>
                                    </div>
                                    <div class="ms-auto">
                                        <i class="fa-solid fa-arrow-right text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Ссылка на список всех домов -->
                    <div class="col-lg-6 col-md-8">
                        <a href="all.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm hover-card gsap-buttons"> <!-- Уменьшена тень -->
                                <div class="card-body p-3 d-flex align-items-center"> <!-- Горизонтальное расположение -->
                                    <div class="icon-circle bg-success p-2 me-3 rounded-circle" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-list fa-lg text-white"></i>
                                    </div>
                                    <div class="text-start">
                                        <h3 class="h6 mb-1 text-dark">Список всех домов</h3>
                                        <p class="text-muted mb-0 small">Просмотр и управление всеми домами в базе</p>
                                    </div>
                                    <div class="ms-auto">
                                        <i class="fa-solid fa-arrow-right text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Кнопка профиля пользователя - отдельный ряд -->
                <div class="row justify-content-center mt-3">
                    <div class="col-lg-6 col-md-8">
                        <a href="user.php" class="text-decoration-none">
                            <div class="card border-0 shadow-sm hover-card gsap-buttons">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="icon-circle bg-info p-2 me-3 rounded-circle" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-user fa-lg text-white"></i>
                                    </div>
                                    <div class="text-start">
                                        <h3 class="h6 mb-1 text-dark">Профиль пользователя</h3>
                                        <p class="text-muted mb-0 small">Управление личными данными и настройками</p>
                                    </div>
                                    <div class="ms-auto">
                                        <i class="fa-solid fa-arrow-right text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Стиль для карточки с эффектом при наведении */
.hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
}

.icon-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.hover-card:hover .icon-circle {
    background-color: rgba(67, 78, 56, 0.8) !important;
    transform: scale(1.1);
}

.profile-icon {
    transition: all 0.3s ease;
}

a:hover .profile-icon {
    transform: scale(1.1);
}

.rounded-4 {
    border-radius: 0.75rem;
}

/* Подсветка поля поиска при фокусе и вводе */
.search-input:focus {
    border-color: #434e38 !important;
    box-shadow: 0 0 10px rgba(67, 78, 56, 0.3) !important;
}

.search-input {
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='%23434e38' d='M18.031 16.617l4.283 4.282-1.415 1.415-4.282-4.283A8.96 8.96 0 0 1 11 20c-4.968 0-9-4.032-9-9s4.032-9 9-9 9 4.032 9 9a8.96 8.96 0 0 1-1.969 5.617zm-2.006-.742A6.977 6.977 0 0 0 18 11c0-3.868-3.133-7-7-7-3.868 0-7 3.132-7 7 0 3.867 3.132 7 7 7a6.977 6.977 0 0 0 4.875-1.975l.15-.15z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 15px center;
    background-size: 20px;
    padding-left: 45px !important;
    width: 100%;
    height: auto;
    min-height: 50px;
}

/* Анимация стрелки при наведении */
.hover-card .fa-arrow-right {
    transition: transform 0.3s ease;
}

.hover-card:hover .fa-arrow-right {
    transform: translateX(5px);
}
</style>

<script>
$(document).ready(function() {
    // GSAP анимации для элементов интерфейса
    if (typeof gsap !== 'undefined') {
        // Определяем последовательность анимаций
        const tl = gsap.timeline({delay: 0.05});
        
        // Анимация блоков страницы - удалена анимация профиля
        tl.from('.gsap-title', {
            opacity: 0,
            y: 20,
            duration: 0.3,
            ease: 'power2.out'
        })
        .from('.gsap-search', {
            opacity: 0,
            y: 20,
            duration: 0.4,
            ease: 'power3.out',
            scale: 0.95
        }, '+=0.03')
        .from('.gsap-buttons', {
            opacity: 0,
            y: 20,
            duration: 0.3,
            ease: 'power2.out',
            stagger: 0.1
        }, '+=0.03');
        
        // Пульсация эффекта для бейджа с количеством домов
        gsap.to('.badge.bg-primary', {
            scale: 1.1,
            duration: 0.3,
            ease: 'power1.inOut',
            repeat: 1,
            yoyo: true,
            delay: 0.8
        });
        
        // Добавляем анимированную подсказку на поле поиска
        setTimeout(function() {
            gsap.to('.search-input', {
                boxShadow: '0 0 0 2px rgba(67, 78, 56, 0.3)',
                duration: 0.3,
                repeat: 1,
                yoyo: true,
                ease: 'power2.inOut'
            });
        }, 1000);
    }
    
    // Запускаем анимацию текста в placeholder
    startPlaceholderAnimation();
    
    // Поиск по введенному тексту
    $("#search").keyup(function() {
        var name = $('#search').val();
        
        if (name === "") {
            hideResults();
            showExamples();
        } else {
            $.ajax({
                type: "POST",
                url: "search.php",
                data: { search: name },
                dataType: 'json',
                success: function(response) {
                    if (response && response.length > 0) {
                        var suggestions = response.map(function(item) {
                            return '<div class="p-3 border-bottom hover-bg-light d-flex align-items-center" onclick="fill(\'' + item.adress + '\', ' + item.id + ')">' +
                                   '<i class="fa-solid fa-map-marker-alt text-primary me-3"></i>' +
                                   '<a href="result.php?adress_id=' + item.id + '" class="text-decoration-none flex-grow-1 text-dark">' + item.adress + '</a></div>';
                        });
                        
                        // Скрываем блок примеров
                        hideExamples();
                        
                        // Формируем содержимое с заголовком для визуального разделения
                        var resultsContent = '<div class="search-results-header p-2 bg-light border-bottom">' +
                                            '<div class="d-flex align-items-center">' +
                                            '<i class="fa-solid fa-search text-primary me-2"></i>' +
                                            '<span class="text-muted small">Найденные адреса:</span>' +
                                            '<span class="ms-auto badge bg-primary rounded-pill">' + response.length + '</span>' +
                                            '</div></div>' + 
                                            suggestions.join('');
                        
                        // Используем GSAP для анимации появления результатов
                        $("#display").html(resultsContent).show();
                        if (typeof gsap !== 'undefined') {
                            gsap.from("#display > div", {
                                opacity: 0,
                                y: 10,
                                stagger: 0.05,
                                duration: 0.3,
                                ease: "power1.out"
                            });
                        }
                    } else {
                        hideResults();
                        showExamples();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    hideResults();
                    showExamples();
                }
            });
        }
    });
    
    // Скрыть результаты при клике вне их области
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#display, #search').length) {
            hideResults();
            showExamples();
        }
    });

    // Обработка отправки формы
    $("#navigard_search").submit(function(e) {
        e.preventDefault();
        var adressId = $("#adress_id").val();
        if (adressId) {
            window.location.href = "result.php?adress_id=" + adressId;
        } else {
            showNotification("Пожалуйста, выберите адрес из списка автодополнения.", "warning");
        }
    });
});

// Функция скрытия результатов
function hideResults() {
    if (typeof gsap !== 'undefined') {
        gsap.to('#display', {
            opacity: 0,
            y: 10,
            duration: 0.2,
            ease: 'power1.in',
            onComplete: function() {
                $('#display').hide().css({opacity: 1, y: 0});
                showExamples(); // Показываем примеры после скрытия результатов
            }
        });
    } else {
        $('#display').hide();
        showExamples();
    }
}

// Заполнение поля поиска из примеров
function fillExample(text) {
    const $search = $('#search');
    $search.val(''); // Очищаем поле перед печатаньем
    
    // Если анимация уже идет, прерываем ее
    if (window.typingTimer) {
        clearInterval(window.typingTimer);
    }
    
    // Создаем характеристики скорости печати для реалистичности
    const minDelay = 8;
    const maxDelay = 50;
    const avgDelay = 25;
    
    // Фокусируем поле ввода
    $search.focus();
    
    let currentIndex = 0;
    
    // Добавляем анимацию для поля поиска перед началом "печати"
    if (typeof gsap !== 'undefined') {
        gsap.to('.gsap-search', {
            scale: 1.02,
            duration: 0.1,
            ease: 'power1.out',
            yoyo: true,
            repeat: 1,
            onComplete: startTyping
        });
    } else {
        startTyping();
    }
    
    function startTyping() {
        // Устанавливаем интервал для ввода символов по одному
        window.typingTimer = setInterval(function() {
            // Если печатание завершено, останавливаем
            if (currentIndex >= text.length) {
                clearInterval(window.typingTimer);
                window.typingTimer = null;
                return;
            }
            
            // Добавляем следующий символ
            const currentText = $search.val() + text.charAt(currentIndex);
            $search.val(currentText);
            
            // Вызываем событие keyup для запуска поиска
            $search.trigger('keyup');
            
            // Переходим к следующему символу
            currentIndex++;
            
            // Варьируем задержку для каждого символа для естественности
            const randomDelay = minDelay + Math.random() * (maxDelay - minDelay);
            clearInterval(window.typingTimer);
            window.typingTimer = setTimeout(startTyping, randomDelay);
            
        }, avgDelay);
    }
}

// Заполнение поля поиска из результатов
function fill(adress, id) {
    $('#search').val(adress);
    $('#adress_id').val(id);
    hideResults();
}

// Функция для показа уведомлений
function showNotification(message, type) {
    const notification = $('<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">' +
                          '<div class="toast align-items-center text-white bg-' + type + ' border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">' +
                          '<div class="d-flex">' +
                          '<div class="toast-body"><i class="fa-solid fa-' + (type === 'warning' ? 'exclamation-triangle' : 'check-circle') + ' me-2"></i>' + message + '</div>' +
                          '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                          '</div></div></div>');
    $('body').append(notification);
    
    // Используем GSAP для анимации уведомления
    if (typeof gsap !== 'undefined') {
        gsap.from(notification, {
            opacity: 0,
            x: 50,
            duration: 0.2,
            ease: 'power2.out'
        });
    }
    
    const toast = new bootstrap.Toast(notification.find('.toast'), {
        delay: 3000
    });
    toast.show();
    
    setTimeout(function() {
        if (typeof gsap !== 'undefined') {
            gsap.to(notification, {
                opacity: 0,
                x: 50,
                duration: 0.2,
                ease: 'power2.in',
                onComplete: function() {
                    notification.remove();
                }
            });
        } else {
            notification.remove();
        }
    }, 3000);
}

// Функция анимации текста в placeholder
function startPlaceholderAnimation() {
    // Список подсказок для поочередного отображения
    const placeholderTexts = [
        "Введите адрес для поиска... ",
        "Например, Киевская 100 ",
        "Гагарина 22 ",
        "Выберите из примеров ниже ",
        "Или добавьте новый дом ",
        "Поиск по улице и номеру "
    ];
    
    const $search = $('#search');
    let currentTextIndex = 0;
    let isDeleting = false;
    let currentText = '';
    let charIndex = 0;
    
    // Остановка анимации при фокусе на поле
    $search.on('focus', function() {
        // Устанавливаем постоянный placeholder при фокусе
        $search.attr('placeholder', 'Введите адрес для поиска...');
        if (window.placeholderTimer) {
            clearTimeout(window.placeholderTimer);
            window.placeholderTimer = null;
        }
    });
    
    // Возобновление анимации при потере фокуса, если поле пустое
    $search.on('blur', function() {
        if ($search.val() === '' && !window.placeholderTimer) {
            typeNextChar();
        }
    });
    
    function typeNextChar() {
        const fullText = placeholderTexts[currentTextIndex];
        
        // Скорость печатания/удаления
        const typingSpeed = isDeleting ? 15 : 70;
        const pauseBeforeDelete = 1500; // Уменьшил паузу перед началом удаления текста
        const pauseBeforeNextText = 300; // Уменьшил паузу перед началом нового текста
        
        // Если текущее поле в фокусе или содержит текст, не меняем placeholder
        if (document.activeElement === $search[0] || $search.val() !== '') {
            window.placeholderTimer = setTimeout(typeNextChar, 500);
            return;
        }
        
        if (!isDeleting) {
            // Добавляем символы (печатаем)
            currentText = fullText.substring(0, charIndex + 1);
            charIndex++;
            
            // Если напечатали весь текст, начинаем удаление после паузы
            if (charIndex >= fullText.length) {
                isDeleting = true;
                window.placeholderTimer = setTimeout(typeNextChar, pauseBeforeDelete);
                return;
            }
        } else {
            // Удаляем символы (стираем)
            currentText = fullText.substring(0, charIndex - 1);
            charIndex--;
            
            // Если удалили весь текст, переходим к следующей фразе
            if (charIndex <= 0) {
                isDeleting = false;
                currentTextIndex = (currentTextIndex + 1) % placeholderTexts.length;
                window.placeholderTimer = setTimeout(typeNextChar, pauseBeforeNextText);
                return;
            }
        }
        
        // Обновляем placeholder
        $search.attr('placeholder', currentText);
        
        // Следующий символ с рандомной задержкой для эффекта естественной печати
        const randomSpeed = typingSpeed + Math.random() * 30;
        window.placeholderTimer = setTimeout(typeNextChar, randomSpeed);
    }
    
    // Запускаем анимацию
    typeNextChar();
    
    // Сразу устанавливаем первый placeholder, чтобы не было пустого поля
    $search.attr('placeholder', 'Введите адрес для поиска...');
}

// Функция скрытия блока примеров
function hideExamples() {
    if (typeof gsap !== 'undefined') {
        gsap.to('#examples-block', {
            opacity: 0,
            height: 0,
            duration: 0.2,
            ease: 'power1.in',
            onComplete: function() {
                $('#examples-block').hide();
            }
        });
    } else {
        $('#examples-block').hide();
    }
}

// Функция отображения блока примеров
function showExamples() {
    if ($('#search').val() !== '') return; // Не показываем примеры, если поле не пустое
    
    $('#examples-block').show();
    if (typeof gsap !== 'undefined') {
        gsap.fromTo('#examples-block', 
            {opacity: 0, height: 'auto'},
            {opacity: 1, height: 'auto', duration: 0.3, ease: 'power1.out'}
        );
    }
}
</script>

<?php
include(__DIR__ . '/../inc/foot.php');
?>
</body>
</html>