<?php
include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    
    if ($type === 'mileage') {
        // Получаем последнюю запись
        $sql_last = "SELECT id FROM mileage ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql_last);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_id = $row['id'];
            
            // Удаляем последнюю запись
            $sql_delete = "DELETE FROM mileage WHERE id = ?";
            $stmt = $conn->prepare($sql_delete);
            
            if ($stmt) {
                $stmt->bind_param("i", $last_id);
                if ($stmt->execute()) {
                    header("Location: index.php?rollback=success");
                    exit();
                } else {
                    header("Location: index.php?rollback=error&message=delete_failed");
                    exit();
                }
                $stmt->close();
            }
        } else {
            header("Location: index.php?rollback=error&message=no_record");
            exit();
        }
    }
}

header("Location: index.php?rollback=error");
exit();
?> 