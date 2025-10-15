<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'superadmin') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$sql = "SELECT id, nombre, email, telefono, rol, estado, foto FROM usuarios";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener usuarios']);
    exit;
}

$usuarios = [];
while ($u = $result->fetch_assoc()) {
    $u['foto'] = $u['foto'] ?? '';
    $usuarios[] = $u;
}

echo json_encode($usuarios);