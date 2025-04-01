<?php
include_once(dirname(__FILE__) . "/../inc/function.php");
global $connect;
global $usr;

if (isset($_POST['my_file_upload'])) {
    // Проверяем тип загружаемого файла
    if ($_FILES['0']['type'] != 'image/gif' || $_FILES['0']['type'] != 'image/jpeg' || $_FILES['0']['type'] != 'image/png') {
        $done_files1[] = $_FILES['0']['type'];
        $data1 = array('files' => $done_files1);
        die(json_encode($data1));
    }

    $uploaddir = './img';
    
    // Создаем директорию для загрузки если её нет
    if (!is_dir($uploaddir)) {
        mkdir($uploaddir, 0777);
    }

    $files = $_FILES;
    $done_files = array();

    // Перемещаем файлы из временной директории
    foreach ($files as $file) {
        $file['name'] = "$usr[name].png";
        $file_name = navigard_cyrillic_translit($file['name']);

        if (move_uploaded_file($file['tmp_name'], "$uploaddir/$file_name")) {
            $done_files[] = realpath("$uploaddir/$file_name");
        }
    }
    
    $data = $done_files ? array('files' => $done_files) : array('error' => 'Ошибка загрузки файлов.');
    die(json_encode($data));
}

// Функция для транслитерации кириллицы в латиницу
function navigard_cyrillic_translit($title) {
    $iso9_table = array(
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 
        'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
        'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH',
        'Ш' => 'SH', 'Щ' => 'SHH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
        'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    );

    $name = strtr($title, $iso9_table);
    $name = preg_replace('~[^A-Za-z0-9\'_\-\.]~', '-', $name);
    $name = preg_replace('~\-+~', '-', $name);
    $name = preg_replace('~^-+|-+$~', '', $name);

    return $name;
}
