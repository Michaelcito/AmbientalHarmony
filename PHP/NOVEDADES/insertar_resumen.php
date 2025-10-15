<?php
include 'conexion.php';
session_start();

$MuserId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

$fecha = $_POST['fecha'];
$descripcion = $_POST['descripcion'];
$tipo = 'resumen';

if ($fecha && $descripcion) {
    if ($MuserId) {
        $stmt = $conexion->prepare("INSERT INTO registros_comite (usuario_id, tipo, fecha, descripcion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $MuserId, $tipo, $fecha, $descripcion);
    } else {
        $stmt = $conexion->prepare("INSERT INTO registros_comite (tipo, fecha, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $tipo, $fecha, $descripcion);
    }
    $stmt->execute();
    $stmt->close();
}

$conexion->close();

header("Location: ../../VIEW/novedades_diarias.php");
exit;
