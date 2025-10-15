<?php
date_default_timezone_set('America/Bogota');
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error BD: " . $conn->connect_error);
}

// Obtener la lista de planillas guardadas
$res = $conn->query("
    SELECT h.id, h.periodo AS mes, h.fecha_guardado AS fecha_cierre
    FROM historia_planillas h
    ORDER BY h.id DESC
");
$planillas = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$detalle = [];
$config_h = [];
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Obtener la configuración general de la planilla
    $stmt = $conn->prepare("SELECT * FROM historia_planillas WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $config_h = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $stmt = $conn->prepare("
        SELECT fecha, tipoA, tipoB, tipoC, tipoD, contingencia AS cont,
               resp_ie AS respIE, resp_asoc AS respAsoc, resp_int AS respInt
        FROM detalle_planillas WHERE historial_id=?
        ORDER BY fecha ASC
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Historial de Planillas</title>
    <link rel="stylesheet" href="../ASSETS/CSS/historia_planillas.css">
    <style>
        .btn-reporte {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body class="p-4">
    <div class="container-wrapper">
        <div class="header-container">
            <h1 class="mb-4">Historial de planillas FRS</h1>
            <a href="historial_general.php" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al historial general
            </a>
        </div>
        
        <?php if(empty($planillas)): ?>
            <div class="alert alert-info">No hay planillas guardadas.</div>
        <?php else: ?>
            <ul class="list-group mb-4">
                <?php foreach($planillas as $p): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <a href="?id=<?= $p['id'] ?>">
                            Planilla de <?= date("F Y", strtotime($p['mes']."-01")) ?>
                        </a>
                        <span class="badge bg-secondary">
                            Guardada <?= date("d/m/Y H:i", strtotime($p['fecha_cierre'])) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if($config_h): ?>
            <h2>Detalles de <?= date("F Y", strtotime($config_h['periodo']."-01")) ?></h2>
            
            <div style="margin-bottom: 20px;">
                <a href="../PHP/REPORTES1/generar_reporte_pdf.php?id=<?= $id ?>" class="btn-reporte">Descargar PDF</a>
            </div>

            <div class="configuracion-historica-display mb-3 p-3 bg-light rounded">
                <?php 
                    $campos = [
                        'consecutivo'   => 'Consecutivo',
                        'periodo'       => 'Periodo',
                        'institucion'   => 'Institución Educativa',
                        'direccion'     => 'Dirección sede',
                        'localidad'     => 'Localidad / Barrio',
                        'tipo_entrega'  => 'Tipo de entrega',
                        'anio'          => 'Año',
                        'convenio'      => 'N° Convenio',
                        'operador'      => 'Operador / Asociado',
                        'rector'        => 'Rector (Nombre y Teléfono)'
                    ];
                    foreach($campos as $field => $label): 
                ?>
                    <div>
                        <strong><?= $label ?>:</strong>
                        <?= htmlspecialchars($config_h[$field] ?? 'N/A') ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3>Registros</h3>
            <?php if(empty($detalle)): ?>
                <div class="alert alert-warning">Sin registros para esta planilla.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>A</th><th>B</th><th>C</th><th>D</th>
                            <th>Cont.</th><th>Resp IE</th><th>Resp Asoc</th><th>Resp Int</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalle as $r): ?>
                            <tr>
                                <td data-label="Fecha"><?= htmlspecialchars($r['fecha']) ?></td>
                                <td data-label="A"><?= htmlspecialchars($r['tipoA']) ?></td>
                                <td data-label="B"><?= htmlspecialchars($r['tipoB']) ?></td>
                                <td data-label="C"><?= htmlspecialchars($r['tipoC']) ?></td>
                                <td data-label="D"><?= htmlspecialchars($r['tipoD']) ?></td>
                                <td data-label="Cont."><?= $r['cont'] ? 'Sí' : 'No' ?></td>
                                <td data-label="Resp IE"><?= htmlspecialchars($r['respIE']) ?></td>
                                <td data-label="Resp Asoc"><?= htmlspecialchars($r['respAsoc']) ?></td>
                                <td data-label="Resp Int"><?= htmlspecialchars($r['respInt']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>