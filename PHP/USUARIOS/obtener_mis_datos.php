<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$userId = $_SESSION['user_id'];

$host = "localhost";
$dbname = "tu_basededatos";
$username = "tu_usuario";
$password = "tu_contraseña";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT nombre, email, telefono, rol, estado FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit();
    }

    header('Content-Type: application/json');
    echo json_encode($user);

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}