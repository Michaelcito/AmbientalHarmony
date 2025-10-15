<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] === 'operador') {
    die("No autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
    die("Petición inválida.");
}

$tmpPath      = $_FILES['archivo']['tmp_name'];
$originalName = basename($_FILES['archivo']['name']);
$mimeType     = $_FILES['archivo']['type'];
$uniqueName   = time() . "_" . uniqid() . "_" . $originalName;
$uploadDir    = __DIR__ . "/../../ARCHIVOS/CICLO_COMIDA/";

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$dest = $uploadDir . $uniqueName;
if (!move_uploaded_file($tmpPath, $dest)) {
    die("Error al mover el archivo.");
}

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$stmt = $conn->prepare(
    "INSERT INTO ciclo_comida (nombre_archivo, tipo_archivo, fecha_subida, usuario_id)
     VALUES (?, ?, NOW(), ?)"
);
$stmt->bind_param("ssi", $uniqueName, $mimeType, $_SESSION['user_id']);
$stmt->execute();

$nuevoId = $stmt->insert_id;

$stmt->close();
$conn->close();

header("Location: ../../VIEW/ciclo_comida.php?id=" . $nuevoId);
exit;
