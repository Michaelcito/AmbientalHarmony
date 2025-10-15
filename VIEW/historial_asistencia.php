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

date_default_timezone_set('America/Bogota');
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mes = $_GET['mes'] ?? null;  
$dia = $_GET['dia'] ?? null;  

function formatMes($mes) {
    setlocale(LC_TIME, 'es_ES.UTF-8');
    $timestamp = strtotime($mes . '-01');
    return strftime('%B %Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Historial de Asistencia</title>
    <link rel="stylesheet" href="../ASSETS/CSS/historial_asistencia.css" />
    <style>
        .report-buttons {
            margin-bottom: 20px;
        }
        .btn-reporte {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Historial de Asistencia</h1>
        <p>
            <a href="historial_general.php" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al historial general
            </a>
        </p>

        <?php if (!$mes): ?>
            <h2>Meses con registros</h2>
            <ul>
                <?php
                $sqlMeses = "SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') AS mes FROM historial_asistencia ORDER BY mes DESC";
                $resultMeses = $conn->query($sqlMeses);

                while ($row = $resultMeses->fetch_assoc()) {
                    $mesFormat = formatMes($row['mes']);
                    echo "<li><a href='?mes={$row['mes']}'>$mesFormat</a></li>";
                }
                ?>
            </ul>

        <?php elseif ($mes && !$dia): ?>
            <h2>Días del mes: <?php echo formatMes($mes); ?></h2>
            <ul>
                <?php
                $sqlDias = $conn->prepare(
                    "SELECT DISTINCT fecha 
                     FROM historial_asistencia 
                     WHERE DATE_FORMAT(fecha, '%Y-%m') = ? 
                     ORDER BY fecha DESC"
                );
                $sqlDias->bind_param("s", $mes);
                $sqlDias->execute();
                $resultDias = $sqlDias->get_result();

                while ($row = $resultDias->fetch_assoc()) {
                    $fechaFormateada = date('d/m/Y', strtotime($row['fecha']));
                    echo "<li><a href='?mes={$mes}&dia={$row['fecha']}'>$fechaFormateada</a></li>";
                }
                ?>
            </ul>

            <p><a href="historial_asistencia.php">← Volver a meses</a></p>

        <?php else: ?>
            <h2>Asistencia del día: <?php echo date('d/m/Y', strtotime($dia)); ?></h2>
            
            <div class="report-buttons">
                <a href="../PHP/REPORTES3/generar_asistencia_pdf.php?dia=<?= $dia ?>" class="btn-reporte">Descargar PDF</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sqlRegistros = $conn->prepare(
                        "SELECT nombre_completo, cedula, hora_entrada, hora_salida, observacion 
                         FROM historial_asistencia 
                         WHERE fecha = ? 
                         ORDER BY nombre_completo"
                    );
                    $sqlRegistros->bind_param("s", $dia);
                    $sqlRegistros->execute();
                    $resultRegistros = $sqlRegistros->get_result();

                    while ($row = $resultRegistros->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['nombre_completo']}</td>
                                <td>{$row['cedula']}</td>
                                <td>{$row['hora_entrada']}</td>
                                <td>{$row['hora_salida']}</td>
                                <td>{$row['observacion']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>

            <p><a href="?mes=<?php echo $mes; ?>">← Volver a días</a></p>
            <p><a href="historial_asistencia.php">← Volver a meses</a></p>
        <?php endif; ?>

    </div>
</body>
</html>

<?php
$conn->close();
?>