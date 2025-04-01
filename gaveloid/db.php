<?php

$servername = "localhost";
$username = "gaveloid";
$password = "64ihufoz";
$dbname = "gaveloid";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}