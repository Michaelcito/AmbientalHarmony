<?php
$servername  = "localhost";
$dbUsername  = "root";
$dbPassword  = "";
$dbname      = "harmony";
$conn = new mysqli(
    $servername,
    $dbUsername,
    $dbPassword,
    $dbname
);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>