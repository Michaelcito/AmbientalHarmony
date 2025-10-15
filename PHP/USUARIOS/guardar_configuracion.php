<?php
session_start(); 

date_default_timezone_set('America/Costa_Rica');
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
    die("Error: Usuario no autenticado");
}

$usuario_id  = $_SESSION['user_id']; 
$consecutivo = $_POST['consecutivo'] ?? 'test_consecutivo';
$periodo     = $_POST['periodo'] ?? 'test_periodo';
$institucion = $_POST['institucion'] ?? 'test_institucion';
$direccion   = $_POST['direccion'] ?? 'test_direccion';
$localidad   = $_POST['localidad'] ?? 'test_localidad';
$tipo        = $_POST['tipo'] ?? 'test_tipo';
$anio        = $_POST['anio'] ?? '2025';
$convenio    = $_POST['convenio'] ?? 'test_convenio';
$operador    = $_POST['operador'] ?? 'test_operador';
$rector      = $_POST['rector'] ?? 'test_rector';

$sql = "DELETE FROM configuracion_frs";
if (!$conn->query($sql)) {
    die("Error al borrar configuración anterior: " . $conn->error);
}

$sql = "INSERT INTO configuracion_frs (
    consecutivo, periodo, institucion, direccion, localidad,
    tipo_entrega, anio, convenio, operador, rector, usuario_id
) VALUES (
    '$consecutivo', '$periodo', '$institucion', '$direccion', '$localidad',
    '$tipo', '$anio', '$convenio', '$operador', '$rector', '$usuario_id'
)";

if ($conn->query($sql) === TRUE) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Datos guardados!',
            text: 'La configuración se guardó correctamente',
            confirmButtonText: 'Aceptar'
        });
    </script>
    ";
} else {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar datos: " . $conn->error . "',
            confirmButtonText: 'Aceptar'
        });
    </script>
    ";
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
