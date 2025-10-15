<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../iniciar_sesion.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID inválido.");

$stmt = $conn->prepare("SELECT nombre_archivo, tipo_archivo FROM ciclo_comida WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $mime);
if (!$stmt->fetch()) {
    die("Archivo no encontrado en BD.");
}
$stmt->close();
$conn->close();

$path = realpath(__DIR__ . "/../../ARCHIVOS/CICLO_COMIDA/{$name}");
if (!$path || !file_exists($path)) {
    die("Archivo físico no existe.");
}

header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if (isset($_GET['dl'])) {
    header("Content-Disposition: attachment; filename=\"" . basename($name) . "\"");
} else {
    header("Content-Disposition: inline; filename=\"" . basename($name) . "\"");
}
header("Content-Type: " . $mime);
readfile($path);
exit;
