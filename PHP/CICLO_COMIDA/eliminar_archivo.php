<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] === 'operador') {
    die("No autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id        = intval($_POST["id"]);
    $uploadDir = "../../ARCHIVOS/CICLO_COMIDA/";

    $conn = new mysqli("localhost", "root", "", "harmony");
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT nombre_archivo FROM ciclo_comida WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($fileName);
    $stmt->fetch();
    $stmt->close();


    $stmt = $conn->prepare("DELETE FROM ciclo_comida WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $fullPath = $uploadDir . $fileName;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

header("Location: ../../VIEW/ciclo_comida.php");
exit;
