<?php
include 'conexion.php';
session_start();

$MuserId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo            = $_POST['tipo'];
    $fecha           = $_POST['fecha'];
    $descripcion     = $_POST['descripcion'];
    $numeroRegistro  = (int)$_POST['numero_registro'];

    if ($MuserId) {
        $stmt = $conexion->prepare(
          "INSERT INTO registros_comite (usuario_id, tipo, fecha, descripcion, numero_registro) 
           VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("isssi", $MuserId, $tipo, $fecha, $descripcion, $numeroRegistro);
    } else {
        $stmt = $conexion->prepare(
          "INSERT INTO registros_comite (tipo, fecha, descripcion, numero_registro) 
           VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sssi", $tipo, $fecha, $descripcion, $numeroRegistro);
    }

    if ($stmt->execute()) {
        header("Location: ../../VIEW/novedades_diarias.php?success=1");
        exit;
    } else {
        echo "Error al guardar el registro: " . $stmt->error;
    }
    $stmt->close();
    $conexion->close();
}
