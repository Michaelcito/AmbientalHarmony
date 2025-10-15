<?php
session_start();

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

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$result = $conn->query("
    SELECT id, nombre_archivo, tipo_archivo, fecha_subida
    FROM ciclo_comida
    ORDER BY fecha_subida DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ciclos de Comida</title>
    <link rel="stylesheet" href="../ASSETS/CSS/historial_ciclos.css">
</head>
<body>

    <div class="container-wrapper"> 
        <div class="header-container">
            <h1>Historial de Ciclos de Comida</h1>
            <a href="historial_general.php" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al historial general
            </a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre del Archivo</th>
                        <th>Tipo</th>
                        <th>Fecha de Subida</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Nombre del Archivo"><?= htmlspecialchars($row['nombre_archivo']) ?></td>
                            <td data-label="Tipo"><?= strtoupper(pathinfo($row['nombre_archivo'], PATHINFO_EXTENSION)) ?></td>
                            <td data-label="Fecha de Subida"><?= date('d/m/Y', strtotime($row['fecha_subida'])) ?></td>
                            <td data-label="Acción">
                                <a class="btn" href="../PHP/CICLO_COMIDA/ver_archivo.php?id=<?= $row['id'] ?>" target="_blank">Ver</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-result">No se han subido ciclos de comida aún.</p>
        <?php endif; ?>
    </div> 
    <?php $conn->close(); ?>
</body>
</html>