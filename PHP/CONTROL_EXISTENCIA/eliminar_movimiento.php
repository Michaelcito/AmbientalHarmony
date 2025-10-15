<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "harmony");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movimiento_id'])) {
    $movimiento_id = intval($_POST['movimiento_id']);
    $usuario_id = $_SESSION['user_id'];
    $articulo_id_to_expand = null;

    $stmt_get_articulo_id = $conexion->prepare("SELECT ce.articulo_id FROM control_existencias ce
                                                 JOIN articulos a ON ce.articulo_id = a.id
                                                 WHERE ce.id = ? AND a.usuario_id = ?");
    if ($stmt_get_articulo_id) {
        $stmt_get_articulo_id->bind_param("ii", $movimiento_id, $usuario_id);
        $stmt_get_articulo_id->execute();
        $result_get_articulo_id = $stmt_get_articulo_id->get_result();
        $articulo_data = $result_get_articulo_id->fetch_assoc();
        if ($articulo_data) {
            $articulo_id_to_expand = $articulo_data['articulo_id'];
        }
        $stmt_get_articulo_id->close();
    }

    $sql_check_ownership = "SELECT ce.id FROM control_existencias ce
                            JOIN articulos a ON ce.articulo_id = a.id
                            WHERE ce.id = ? AND a.usuario_id = ?";
    $stmt_check_ownership = $conexion->prepare($sql_check_ownership);
    $stmt_check_ownership->bind_param("ii", $movimiento_id, $usuario_id);
    $stmt_check_ownership->execute();
    $resultado_ownership = $stmt_check_ownership->get_result();

    if ($resultado_ownership->num_rows === 1) {
        $delete = $conexion->prepare("DELETE FROM control_existencias WHERE id = ?");
        $delete->bind_param("i", $movimiento_id);
        $delete->execute();
        $delete->close();
    }

    $stmt_check_ownership->close();
    $conexion->close();

    if ($articulo_id_to_expand) {
        header("Location: ../../VIEW/control_existencia.php?articulo_id=" . $articulo_id_to_expand);
    } else {
        header("Location: ../../VIEW/control_existencia.php");
    }
    exit;
} else {

    header("Location: ../../VIEW/control_existencia.php");
    exit;
}
?>