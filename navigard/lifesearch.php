<?php
include(__DIR__ . '/../inc/head.php');
AutorizeProtect();
global $connect;

// Подключаем Font Awesome для иконок
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/all.min.css">';

// Проверяем, что запрос существует
if (isset($_POST['search'])) {
    $search = trim($_POST['search']);
    
    // Убеждаемся, что строка поиска имеет минимальную длину
    if (strlen($search) >= 2) {
        // SQL-запрос для поиска домов по адресу
        $query = "SELECT id, adress, complete, new, region FROM navigard_adress 
                 WHERE adress LIKE '%$search%' 
                 ORDER BY CASE WHEN adress LIKE '$search%' THEN 0 ELSE 1 END, 
                 adress 
                 LIMIT 10";
        
        $result = mysqli_query($connect, $query);
        
        if (mysqli_num_rows($result) > 0) {
            echo '<div class="search-results-container p-2">';
            
            // Заголовок результатов поиска
            echo '<div class="search-results-header p-2 bg-light border-bottom d-flex justify-content-between">';
            echo '<div class="small text-muted"><i class="fas fa-info-circle me-1"></i>Найдено: ' . mysqli_num_rows($result) . '</div>';
            echo '<button type="button" class="btn-close btn-sm" onclick="$(\'#display\').hide()"></button>';
            echo '</div>';
            
            // Список результатов
            echo '<div class="search-results-list">';
            
            while ($row = mysqli_fetch_array($result)) {
                $id = $row['id'];
                $address = $row['adress'];
                $complete = $row['complete'] == 1;
                $is_new = $row['new'] == 1;
                $region = $row['region'];
                
                // Определяем стиль в зависимости от статуса
                $item_class = $complete ? 'text-success' : '';
                $new_badge = $is_new ? '<span class="badge bg-danger ms-2">NEW</span>' : '';
                
                echo '<div class="search-result-item p-2 d-flex align-items-center border-bottom" 
                           onclick="document.getElementById(\'adress_id\').value=\'' . $id . '\'; 
                                    fill(\'' . addslashes($address) . '\'); 
                                    document.getElementById(\'navigard_search\').submit();">';
                
                // Иконка и информация о доме
                echo '<div class="me-auto">';
                if ($complete) {
                    echo '<i class="fas fa-check-circle text-success me-2"></i>';
                } else {
                    echo '<i class="fas fa-building text-secondary me-2"></i>';
                }
                
                echo '<span class="' . $item_class . '">' . htmlspecialchars($address) . '</span>' . $new_badge;
                echo '<div class="small text-muted">' . htmlspecialchars($region) . '</div>';
                echo '</div>';
                
                // Стрелка для навигации
                echo '<div><i class="fas fa-arrow-right text-primary"></i></div>';
                
                echo '</div>';
            }
            
            echo '</div>'; // Конец списка
            
            // Подвал с подсказкой
            echo '<div class="search-results-footer p-2 bg-light text-center">';
            echo '<small class="text-muted">Нажмите на адрес для просмотра информации</small>';
            echo '</div>';
            
            echo '</div>'; // Конец контейнера
        } else {
            // Дом не найден
            echo '<div class="p-3 text-center">';
            echo '<div class="mb-2 text-muted"><i class="fas fa-search me-2"></i>Совпадений не найдено</div>';
            echo '<a href="add_house.php?adress=' . urlencode($search) . '" class="btn btn-sm btn-outline-primary">';
            echo '<i class="fas fa-plus me-1"></i>Добавить новый дом';
            echo '</a>';
            echo '</div>';
        }
    } else {
        // Слишком короткий запрос
        echo '<div class="p-3 text-center text-muted small">';
        echo '<i class="fas fa-info-circle me-2"></i>Введите не менее 2 символов для поиска';
        echo '</div>';
    }
}
?>

<style>
/* Стили для результатов поиска */
.search-results-container {
    border-radius: 0 0 0.5rem 0.5rem;
    background-color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.search-result-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.search-result-item:hover {
    background-color: #f8f9fa;
}

.search-result-item:last-child {
    border-bottom: none !important;
}

.search-results-footer {
    border-top: 1px solid #e9ecef;
}
</style> 