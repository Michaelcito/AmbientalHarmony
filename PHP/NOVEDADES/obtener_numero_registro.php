<?php


date_default_timezone_set('America/Bogota');
include 'conexion.php'; 

header('Content-Type: application/json');

$fecha = $_GET['fecha'] ?? '';
$tipo = $_GET['tipo'] ?? '';

$numero = 1; 

if (!empty($fecha) && !empty($tipo)) {
    $stmt = $conexion->prepare("SELECT MAX(numero_registro) AS max_num FROM registros_comite WHERE fecha = ? AND tipo = ?");
    $stmt->bind_param('ss', $fecha, $tipo);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && $result['max_num'] !== null) {
        $numero = $result['max_num'] + 1;
    }
    $stmt->close();
}

$conexion->close();

echo json_encode(['numero' => $numero]);
?>