<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    die("Usuario no identificado. Por favor inicia sesión.");
}
$usuario_id = (int)$_SESSION['user_id'];

$mysqli = new mysqli("localhost", "root", "", "harmony");
if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT id FROM configuracion_frs LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("No hay configuración disponible");
}
$configId = (int)$res->fetch_assoc()['id'];

$id_registro = $_POST['id_registro_editar'] ?? null;
$fecha = $mysqli->real_escape_string($_POST['fecha'] ?? '');
$tipoA = (int)($_POST['tipoA'] ?? 0);
$tipoB = (int)($_POST['tipoB'] ?? 0);
$tipoC = (int)($_POST['tipoC'] ?? 0);
$tipoD = (int)($_POST['tipoD'] ?? 0);
$contingencia = isset($_POST['cont']) ? 1 : 0;
$respIE = $mysqli->real_escape_string($_POST['respIE'] ?? '');
$respAsoc = $mysqli->real_escape_string($_POST['respAsoc'] ?? '');
$respInt = $mysqli->real_escape_string($_POST['respInt'] ?? '');

$sql = "";
$message = "";

if (!empty($id_registro)) {
    $id_registro = (int)$id_registro; 
    $sql = "
        UPDATE registros_frs SET
            fecha = '$fecha',
            tipoA = $tipoA,
            tipoB = $tipoB,
            tipoC = $tipoC,
            tipoD = $tipoD,
            contingencia = $contingencia,
            resp_ie = '$respIE',
            resp_asoc = '$respAsoc',
            resp_int = '$respInt'
        WHERE id = $id_registro AND configuracion_id = $configId
    ";
    $message = "Registro actualizado correctamente.";
} else {
    $sql = "
        INSERT INTO registros_frs
            (configuracion_id, fecha, tipoA, tipoB, tipoC, tipoD, contingencia, resp_ie, resp_asoc, resp_int, usuario_id)
        VALUES
            ($configId, '$fecha', $tipoA, $tipoB, $tipoC, $tipoD, $contingencia, '$respIE', '$respAsoc', '$respInt', $usuario_id)
    ";
    $message = "Nuevo registro guardado correctamente.";
}

if ($mysqli->query($sql)) {
    $_SESSION['status'] = 'success';
    $_SESSION['title'] = empty($id_registro) ? 'Registro Creado' : 'Actualización Exitosa';
    $_SESSION['message'] = $message;
} else {
    $_SESSION['status'] = 'error';
    $_SESSION['title'] = 'Error al procesar';
    $_SESSION['message'] = "Error de base de datos: " . $mysqli->error;
}

$mysqli->close();

header("Location: ../../VIEW/planilla_FRS.php");
exit;
?>