<?php
    // Начало сессии
    session_start();
    
    // Удаление куки пользователя
    setcookie('navigard_user', '', 1);
    setcookie('navigard_pass', '', 1);
    
    // Уничтожение сессии
    session_destroy();
    session_unset();
    
    // Перенаправление на страницу авторизации
    echo '<meta http-equiv="refresh" content="0;URL=/auth.php">';
    exit();