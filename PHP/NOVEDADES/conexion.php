<?php
$conexion = new mysqli("localhost", "root", '', "harmony");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>