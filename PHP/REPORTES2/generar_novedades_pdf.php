<?php
require('fpdf.php');

date_default_timezone_set('America/Bogota');
include '../NOVEDADES/conexion.php';

if (!isset($_GET['mes'])) {
    die("Mes no especificado.");
}

$mesSel = $_GET['mes'];
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
$conexion->close();

$resumenes = [];
$temas_tratados = [];
$compromisos = [];
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

// ----------------------------------------------------
// Creación del PDF
// ----------------------------------------------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte de Novedades'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
setlocale(LC_TIME, 'es_ES.UTF-8');
$nombreMes = strftime('%B %Y', strtotime($mesSel . '-01'));
$pdf->Cell(0, 10, utf8_decode('Periodo: ' . ucfirst($nombreMes)), 0, 1, 'C');
$pdf->Ln(5);

// Función para imprimir una sección de tabla con manejo de celdas multicelda
function imprimirSeccion($pdf, $titulo, $datos, $columnas, $incluir_num_registro = true) {
    if (empty($datos)) {
        return;
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode($titulo), 0, 1);
    
    // Cabecera de la tabla
    $pdf->SetFont('Arial', 'B', 9);
    foreach($columnas as $col) {
        $pdf->Cell($col['ancho'], 8, utf8_decode($col['titulo']), 1, 0, 'C');
    }
    $pdf->Ln();

    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 8);
    foreach($datos as $row) {
        // Almacenar la posición Y actual antes de la celda multicelda
        $y_inicial = $pdf->GetY();
        
        // Determinar el ancho de la celda de descripción
        $ancho_desc_celda = $incluir_num_registro ? $columnas[2]['ancho'] : $columnas[1]['ancho'];
        
        // Obtener la altura de la celda de descripción
        $pdf->SetX($pdf->GetX() + ($incluir_num_registro ? $columnas[0]['ancho'] + $columnas[1]['ancho'] : $columnas[0]['ancho']));
        $pdf->MultiCell($ancho_desc_celda, 4, utf8_decode($row['descripcion']), 0, 'L');
        $y_final = $pdf->GetY();
        $altura_celda = $y_final - $y_inicial;

        // Volver a la posición inicial Y para dibujar las celdas de la fila
        $pdf->SetY($y_inicial);
        
        // Dibujar las celdas de la fila, con la altura calculada
        if ($incluir_num_registro) {
            $pdf->Cell($columnas[0]['ancho'], $altura_celda, utf8_decode($row['numero_registro']), 1, 0, 'C');
            $pdf->Cell($columnas[1]['ancho'], $altura_celda, $row['fecha'], 1, 0, 'C');
            $pdf->Cell($columnas[2]['ancho'], $altura_celda, '', 1); // Borde de la celda de descripción
            $pdf->Cell($columnas[3]['ancho'], $altura_celda, $row['fecha_guardado'], 1, 1, 'C');
        } else {
            $pdf->Cell($columnas[0]['ancho'], $altura_celda, $row['fecha'], 1, 0, 'C');
            $pdf->Cell($columnas[1]['ancho'], $altura_celda, '', 1); // Borde de la celda de descripción
            $pdf->Cell($columnas[2]['ancho'], $altura_celda, $row['fecha_guardado'], 1, 1, 'C');
        }
    }
    $pdf->Ln(5);
}

// Definición de las columnas para cada tipo de reporte
$columnas_comunes = [
    ['titulo' => 'No.', 'ancho' => 15],
    ['titulo' => 'Fecha', 'ancho' => 20],
    ['titulo' => 'Descripcion', 'ancho' => 100],
    ['titulo' => 'Fecha Guardado', 'ancho' => 35]
];

$columnas_resumenes = [
    ['titulo' => 'Fecha', 'ancho' => 25],
    ['titulo' => 'Descripcion', 'ancho' => 130],
    ['titulo' => 'Fecha Guardado', 'ancho' => 35]
];

// Imprimir secciones con el nuevo código
imprimirSeccion($pdf, 'Resúmenes', $resumenes, $columnas_resumenes, false);
imprimirSeccion($pdf, 'Temas Tratados', $temas_tratados, $columnas_comunes);
imprimirSeccion($pdf, 'Compromisos', $compromisos, $columnas_comunes);

$pdf->Output('I', 'reporte_novedades_' . $mesSel . '.pdf');
exit;
?>