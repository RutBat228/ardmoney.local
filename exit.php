<?php
session_start();
setcookie('user', '', 1);
setcookie('pass', '', 1);
session_destroy();
session_unset();

echo "<script>
if (window.AndroidInterface) {
    window.AndroidInterface.clearUserLogin();
}
</script>";
echo '<meta http-equiv="refresh" content="0;URL=/auth.php">';

exit();
