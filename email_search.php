<?php
include "inc/db.php";
global $connect;
global $usr;

// Проверяем наличие поискового запроса
if (isset($_POST['search'])) {
    $str = $_POST['search'];
    
    // Проверяем наличие @ в строке поиска
    if (preg_match("/@/i", $str)) {
        $email = htmlspecialchars(htmlentities($_POST['search']));
        
        // Ищем пользователя по email
        $query = "SELECT * FROM navigard_user WHERE email LIKE '$email%' LIMIT 1";
        $result = mysqli_query($connect, $query);
        $num_rows = mysqli_num_rows($result);

        echo '<ul class="list-group">';
        
        // Обрабатываем результат поиска
        while ($row = mysqli_fetch_array($result)) {
            ?>
            <li class="list-group-item" onclick='fill("<?php echo $row['email']; ?>")'>
                <a><?php echo $row['email']; ?></a>
            </li>
            <?php
            
            // Проверяем возможность регистрации
            if(empty($row['reger'])){
                echo "Даную почту можно использовать";
            } else {
                echo "Данная почта уже зарегистрированна, <a class='text-success' href='/auth'>авторизуйтесь</a>";
            }
        }

        // Если email не найден
        if($num_rows == 0){
            echo "Даной почте нельзя регистрироватся - свяжитесь с <a class='text-danger' href='https://api.whatsapp.com/send?phone=79789458418'>администрацией</a> ";
        }
    }
}
?>
</ul>
