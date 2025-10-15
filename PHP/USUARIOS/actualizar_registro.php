<?php
session_start();
date_default_timezone_set('America/Costa_Rica');

$conn = new mysqli("localhost", "root", "", "harmony");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_registro = $_POST['id_registro_editar'] ?? null;
    $fecha = $_POST['fecha'] ?? null;
    $tipoA = $_POST['tipoA'] ?? 0;
    $tipoB = $_POST['tipoB'] ?? 0;
    $tipoC = $_POST['tipoC'] ?? 0;
    $tipoD = $_POST['tipoD'] ?? 0;
    $cont = isset($_POST['cont']) ? 1 : 0; // Checkbox value
    $respIE = $_POST['respIE'] ?? '';
    $respAsoc = $_POST['respAsoc'] ?? '';
    $respInt = $_POST['respInt'] ?? '';
    $configuracion_id = $_POST['configuracion_id'] ?? null;

    if (!$id_registro || !$fecha || !$configuracion_id) {
        $_SESSION['status'] = 'error';
        $_SESSION['title'] = 'Error al actualizar';
        $_SESSION['message'] = 'Faltan datos esenciales para actualizar el registro.';
        header("Location: ../../VIEW/planilla_FRS.php");
        exit();
    }

    $stmt = $conn->prepare("
        UPDATE registros_frs
        SET
            fecha = ?,
            tipoA = ?,
            tipoB = ?,
            tipoC = ?,
            tipoD = ?,
            contingencia = ?,
            resp_ie = ?,
            resp_asoc = ?,
            resp_int = ?
        WHERE id = ? AND configuracion_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("siiiiisssii", 
            $fecha, $tipoA, $tipoB, $tipoC, $tipoD, $cont, 
            $respIE, $respAsoc, $respInt, $id_registro, $configuracion_id
        );

        if ($stmt->execute()) {
            $_SESSION['status'] = 'success';
            $_SESSION['title'] = 'Registro Actualizado';
            $_SESSION['message'] = 'El registro diario se actualizó correctamente.';
        } else {
            $_SESSION['status'] = 'error';
            $_SESSION['title'] = 'Error de Base de Datos';
            $_SESSION['message'] = 'No se pudo actualizar el registro: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['title'] = 'Error de Preparación';
        $_SESSION['message'] = 'Fallo al preparar la consulta de actualización: ' . $conn->error;
    }
} else {
    $_SESSION['status'] = 'error';
    $_SESSION['title'] = 'Método Inválido';
    $_SESSION['message'] = 'Acceso directo no permitido.';
}

$conn->close();
header("Location: ../../VIEW/planilla_FRS.php");
exit();
?>