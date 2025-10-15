<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'superadmin') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error al conectar a la BD: ' . $conn->connect_error]);
    exit;
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID faltante o inválido']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, nombre, email, telefono, rol, estado, foto
    FROM usuarios
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();

    $usuario['foto'] = $usuario['foto'] ?? '';

    echo json_encode([
      'status'  => 'success',
      'usuario' => $usuario
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
}

$stmt->close();
$conn->close();