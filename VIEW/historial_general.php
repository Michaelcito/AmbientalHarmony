<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}

if (isset($_SESSION['user_rol'])) {
    $user_rol = $_SESSION['user_rol'];
    if ($user_rol === 'operador') {
        header("Location: ../PHP/USUARIOS/acceso_denegado.php");
        exit();
    }
} else {
    header("Location: ../PHP/USUARIOS/acceso_denegado.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia de Planillas</title>
    <link rel="stylesheet" href="../ASSETS/CSS/historial_general.css">
    
</head>
<body>
    <h1>Historia de Planillas</h1>
    <div class="botones-container">
        <a href="historia_planillas.php" class="btn-historial">📄 Historial Planilla FRS</a>
        <a href="historial_ciclos.php" class="btn-historial">🍽️ Historial Ciclo de Comida</a>
        <a href="historial_asistencia.php" class="btn-historial">🕘 Historial Asistencia</a>
        <a href="historial_novedades.php" class="btn-historial">🔔 Historial Novedades</a>
    </div>
</body>
</html>
