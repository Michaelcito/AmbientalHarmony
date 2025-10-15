<?php
include 'conexion.php'; 

header('Content-Type: application/json');

$response = ['hay_registros' => false];

try {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM registros_comite");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $response['hay_registros'] = true;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error al verificar registros: " . $e->getMessage());

    $response['error'] = 'Database query failed.';
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}

echo json_encode($response);
?>