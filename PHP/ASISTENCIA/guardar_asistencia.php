<?php
session_start();
date_default_timezone_set('America/Bogota');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "harmony");

if ($conn->connect_error) {
    header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Error de conexión a la base de datos."));
    exit;
}
$conn->set_charset("utf8mb4");

$MuserId = 0;

if (!empty($_SESSION['usuario_id'])) {
    $MuserId = intval($_SESSION['usuario_id']);
} elseif (!empty($_SESSION['user_id'])) {
    $MuserId = intval($_SESSION['user_id']);
}

$MuserExists = false;

if ($MuserId > 0) {
    $chkUser = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    if ($chkUser) {
        $chkUser->bind_param("i", $MuserId);
        $chkUser->execute();
        $resUser = $chkUser->get_result();

        if ($resUser && $resUser->num_rows > 0) {
            $MuserExists = true;
        }

        $chkUser->close();
    } else {
        error_log("Error al preparar verificación de usuario: " . $conn->error);
    }
}

$fecha          = $_POST['fecha'] ?? date('Y-m-d'); 
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$cedula          = trim($_POST['cedula'] ?? '');
$hora_entrada    = $_POST['hora_entrada'] ?? '';
$hora_salida     = $_POST['hora_salida'] ?? '';
$observacion     = trim($_POST['observacion'] ?? '');

if ($nombre_completo === '' || $cedula === '' || $hora_entrada === '' || $hora_salida === '') {
    header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Campos obligatorios faltantes"));
    exit;
}

$check_stmt = $conn->prepare("SELECT COUNT(*) FROM asistencia_diaria WHERE cedula = ? AND fecha = ?");
if ($check_stmt === false) {
    error_log("Error al preparar la consulta de verificación: " . $conn->error);
    header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Error interno al verificar asistencia."));
    exit;
}

$check_stmt->bind_param("ss", $cedula, $fecha);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$row   = $check_result->fetch_row();
$count = $row[0];
$check_stmt->close();

if ($count > 0) {
    header("Location: ../../VIEW/asistencia_diaria.php?status=duplicate_entry");
    exit;
}

if ($MuserExists) {
    $stmt = $conn->prepare(
        "INSERT INTO asistencia_diaria (usuario_id, fecha, nombre_completo, cedula, hora_entrada, hora_salida, observacion) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    if ($stmt === false) {
        error_log("Error al preparar la consulta de inserción (con usuario): " . $conn->error);
        header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Error interno al registrar asistencia."));
        exit;
    }
    $stmt->bind_param("issssss", $MuserId, $fecha, $nombre_completo, $cedula, $hora_entrada, $hora_salida, $observacion);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO asistencia_diaria (fecha, nombre_completo, cedula, hora_entrada, hora_salida, observacion) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmt === false) {
        error_log("Error al preparar la consulta de inserción (sin usuario): " . $conn->error);
        header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Error interno al registrar asistencia."));
        exit;
    }
    $stmt->bind_param("ssssss", $fecha, $nombre_completo, $cedula, $hora_entrada, $hora_salida, $observacion);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../../VIEW/asistencia_diaria.php?status=success");
    exit;
} else {
    error_log("Error al insertar asistencia: " . $stmt->error);
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: ../../VIEW/asistencia_diaria.php?status=error&message=" . urlencode("Error al registrar la asistencia: " . $err));
    exit;
}
?>
