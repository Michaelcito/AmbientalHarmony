<?php
session_start();
date_default_timezone_set('America/Bogota');

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_registro_eliminar'])) {
    $id_a_eliminar = $_POST['id_registro_eliminar'];
    if (!filter_var($id_a_eliminar, FILTER_VALIDATE_INT) || $id_a_eliminar <= 0) {
        $_SESSION['status']  = "error";
        $_SESSION['title']   = "Error de Validación";
        $_SESSION['message'] = "ID inválido: '" . htmlspecialchars($id_a_eliminar) . "'";
        header("Location: ../../VIEW/planilla_FRS.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT configuracion_id FROM registros_frs WHERE id = ?");
    $stmt->bind_param("i", $id_a_eliminar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $configuracion_id = $row['configuracion_id'];
    } else {
        $_SESSION['status']  = "warning";
        $_SESSION['title']   = "Registro No Encontrado";
        $_SESSION['message'] = "No existe el registro con ID $id_a_eliminar.";
        $stmt->close();
        header("Location: ../../VIEW/planilla_FRS.php");
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM registros_frs WHERE id = ?");
    $stmt->bind_param("i", $id_a_eliminar);
    if ($stmt->execute()) {
        $_SESSION['status']  = "success";
        $_SESSION['title']   = "¡Registro Eliminado!";
        $_SESSION['message'] = "ID $id_a_eliminar eliminado.";

        $stmt2 = $conn->prepare("SELECT COUNT(*) AS cnt FROM registros_frs WHERE configuracion_id = ?");
        $stmt2->bind_param("i", $configuracion_id);
        $stmt2->execute();
        $cnt = $stmt2->get_result()->fetch_assoc()['cnt'];
        $stmt2->close();

    } else {
        $_SESSION['status']  = "error";
        $_SESSION['title']   = "Error al Eliminar";
        $_SESSION['message'] = "No se pudo eliminar: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['status']  = "error";
    $_SESSION['title']   = "Error de Solicitud";
    $_SESSION['message'] = "Solicitud inválida.";
}

$conn->close();
header("Location: ../../VIEW/planilla_FRS.php");
exit;
?>