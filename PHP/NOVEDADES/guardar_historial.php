<?php
include 'conexion.php';
session_start();
date_default_timezone_set('America/Bogota');
$ahora = date('Y-m-d H:i:s');

$usuarioIdPlanilla = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

$result = $conexion->query("SELECT id, usuario_id, tipo, fecha, descripcion, numero_registro FROM registros_comite");

$stmt = $conexion->prepare("
    INSERT INTO historial_novedades (
        usuario_id_registro, usuario_id_planilla,
        tipo, fecha, descripcion, numero_registro, fecha_guardado
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

while ($fila = $result->fetch_assoc()) {
    $usuarioIdRegistro = $fila['usuario_id'] ?? null;
    $tipo              = $fila['tipo'];
    $fecha             = $fila['fecha'];
    $descripcion       = $fila['descripcion'];
    $numeroRegistro    = $fila['numero_registro'];
    
    $stmt->bind_param(
        "iisssis",
        $usuarioIdRegistro,
        $usuarioIdPlanilla,
        $tipo,
        $fecha,
        $descripcion,
        $numeroRegistro,
        $ahora
    );
    $stmt->execute();
}

$conexion->query("DELETE FROM registros_comite");

$stmt->close();
$conexion->close();

header("Location: ../../VIEW/novedades_diarias.php");
exit;
