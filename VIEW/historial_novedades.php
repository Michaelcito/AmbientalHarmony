<?php
date_default_timezone_set('America/Bogota');
include '../PHP/NOVEDADES/conexion.php';

$meses = [];
$resMeses = $conexion->query("
    SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') AS mes
    FROM historial_novedades
    ORDER BY mes DESC
");
if ($resMeses) {
    while ($m = $resMeses->fetch_assoc()) {
        $meses[] = $m['mes'];
    }
}

$detalle = [];
$resumenes = [];
$temas_tratados = [];
$compromisos = [];
$mes_seleccionado = '';

if (isset($_GET['mes'])) {
    $mesSel = $_GET['mes'];
    $mes_seleccionado = $mesSel;
    $stmt = $conexion->prepare("
        SELECT tipo, fecha, descripcion, fecha_guardado, numero_registro
        FROM historial_novedades
        WHERE DATE_FORMAT(fecha, '%Y-%m') = ?
        ORDER BY fecha ASC, numero_registro ASC
    ");
    $stmt->bind_param('s', $mesSel);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($detalle as $fila) {
        switch (mb_strtolower($fila['tipo'])) {
            case 'resumen':
                $resumenes[] = $fila;
                break;
            case 'tema':
                $temas_tratados[] = $fila;
                break;
            case 'compromiso':
                $compromisos[] = $fila;
                break;
            default:
                break;
        }
    }
}
$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Historial Mensual de las Novedades diarias</title>
    <link rel="stylesheet" href="../ASSETS/CSS/historial_novedades.css"/>
    <style>
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
        <div class="header-container">
            <h1>Historial Mensual de las Novedades diarias</h1>
            <a href="historial_general.php" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al historial general
            </a>
        </div>
        
        <?php if (empty($meses)): ?>
            <p class="no-result">No hay registros en el historial.</p>
        <?php else: ?>
            <h2>Meses con Registros</h2>
            <ul class="lista-meses">
                <?php foreach ($meses as $mes):
                    setlocale(LC_TIME, 'es_ES.UTF-8');
                    $timestamp = strtotime($mes . '-01');
                    $nombreMes = strftime('%B %Y', $timestamp);
                ?>
                    <li>
                        <a href="?mes=<?= $mes ?>">
                            <?= ucfirst($nombreMes) ?>
                        </a>
                        <span class="badge">
                             <?php
                                // Se recomienda usar la misma conexión abierta o refactorizar para no abrir y cerrar múltiples conexiones
                                $countConn = new mysqli("localhost", "root", "", "harmony");
                                if ($countConn->connect_error) {
                                    echo "Error de conexión al contar: " . $countConn->connect_error;
                                } else {
                                    $countQuery = $countConn->prepare("SELECT COUNT(*) AS total FROM historial_novedades WHERE DATE_FORMAT(fecha, '%Y-%m') = ?");
                                    $countQuery->bind_param("s", $mes);
                                    $countQuery->execute();
                                    $count = $countQuery->get_result()->fetch_assoc()['total'];
                                    $countQuery->close();
                                    $countConn->close();
                                    echo $count . ' registros';
                                }
                            ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($detalle)): ?>
            <?php 
                $nombre_mes_reporte = ucfirst(strftime('%B %Y', strtotime($mes_seleccionado . '-01')));
            ?>
            <h2 class="sub-heading-detail">
                Detalle de <?= $nombre_mes_reporte ?>
            </h2>
            
            <div style="margin-bottom: 20px;">
                <a href="../PHP/REPORTES2/generar_novedades_pdf.php?mes=<?= $mes_seleccionado ?>" class="btn-reporte">Descargar PDF</a>
            </div>

            <?php if (!empty($resumenes)): ?>
                <h3>Resúmenes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Fecha Guardado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumenes as $fila): ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td data-label="Descripción"><?= nl2br(htmlspecialchars($fila['descripcion'])) ?></td>
                                <td data-label="Fecha Guardado"><?= htmlspecialchars($fila['fecha_guardado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($temas_tratados)): ?>
                <h3>Temas Tratados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th> <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Fecha Guardado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($temas_tratados as $fila): ?>
                            <tr>
                                <td data-label="No."><?= htmlspecialchars($fila['numero_registro']) ?></td> <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td data-label="Descripción"><?= nl2br(htmlspecialchars($fila['descripcion'])) ?></td>
                                <td data-label="Fecha Guardado"><?= htmlspecialchars($fila['fecha_guardado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($compromisos)): ?>
                <h3>Compromisos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th> <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Fecha Guardado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compromisos as $fila): ?>
                            <tr>
                                <td data-label="No."><?= htmlspecialchars($fila['numero_registro']) ?></td> <td data-label="Fecha"><?= htmlspecialchars($fila['fecha']) ?></td>
                                <td data-label="Descripción"><?= nl2br(htmlspecialchars($fila['descripcion'])) ?></td>
                                <td data-label="Fecha Guardado"><?= htmlspecialchars($fila['fecha_guardado']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (empty($resumenes) && empty($temas_tratados) && empty($compromisos)): ?>
                <p class="no-result">No se encontraron detalles para este mes.</p>
            <?php endif; ?>

            <p><a href="historial_novedades.php">← Volver a meses</a></p>
        <?php endif; ?>
    </div>
</body>
</html>