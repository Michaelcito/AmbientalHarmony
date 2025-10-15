<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['articulo_id'])) {
    $articulo_id = intval($_POST['articulo_id']);

    $conexion = new mysqli("localhost", "root", "", "harmony");
    if ($conexion->connect_error) {
        die("Error en la conexión: " . $conexion->connect_error);
    }

    $stmt = $conexion->prepare("UPDATE articulos SET activo = 1 WHERE id = ?");
    $stmt->bind_param("i", $articulo_id);
    $stmt->execute();
    $stmt->close();
    $conexion->close();

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "Solicitud inválida.";
}
?>
