<?php
// Asegúrate de que esta ruta a FPDF sea correcta
require('../../PHP/REPORTES2/fpdf.php');

date_default_timezone_set('America/Bogota');
// Ruta a tu archivo de conexión. Asegúrate de que sea correcta
include '../../PHP/NOVEDADES/conexion.php';

if (!isset($_GET['dia'])) {
    die("Día no especificado.");
}

$dia = $_GET['dia'];
$sqlRegistros = $conexion->prepare(
    "SELECT nombre_completo, cedula, hora_entrada, hora_salida, observacion 
    FROM historial_asistencia 
    WHERE fecha = ? 
    ORDER BY nombre_completo"
);
$sqlRegistros->bind_param("s", $dia);
$sqlRegistros->execute();
$resultRegistros = $sqlRegistros->get_result();
$registros = $resultRegistros->fetch_all(MYSQLI_ASSOC);

$sqlRegistros->close();
$conexion->close();

// ----------------------------------------------------
// Creación del PDF
// ----------------------------------------------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte de Asistencia'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('Fecha: ' . date('d/m/Y', strtotime($dia))), 0, 1, 'C');
$pdf->Ln(5);

// Encabezados de la tabla
$pdf->SetFont('Arial', 'B', 9);
$ancho_nombre = 50;
$ancho_cedula = 25;
$ancho_entrada = 25;
$ancho_salida = 25;
$ancho_observacion = 65;

$pdf->Cell($ancho_nombre, 8, 'Nombre', 1, 0, 'C');
$pdf->Cell($ancho_cedula, 8, utf8_decode('Cédula'), 1, 0, 'C');
$pdf->Cell($ancho_entrada, 8, 'H. Entrada', 1, 0, 'C');
$pdf->Cell($ancho_salida, 8, 'H. Salida', 1, 0, 'C');
$pdf->Cell($ancho_observacion, 8, utf8_decode('Observación'), 1, 1, 'C');

// Contenido de la tabla
$pdf->SetFont('Arial', '', 8);
foreach ($registros as $row) {
    $y_inicial = $pdf->GetY();
    
    // Calcular la altura de la celda de observación
    $x_observacion = $pdf->GetX() + $ancho_nombre + $ancho_cedula + $ancho_entrada + $ancho_salida;
    $pdf->SetXY($x_observacion, $y_inicial);
    $pdf->MultiCell($ancho_observacion, 4, utf8_decode($row['observacion']), 0, 'L');
    $altura_observacion = $pdf->GetY() - $y_inicial;

    // Volver a la posición inicial para dibujar las celdas de la fila
    $pdf->SetY($y_inicial);
    $pdf->SetX($pdf->GetX());

    $pdf->Cell($ancho_nombre, $altura_observacion, utf8_decode($row['nombre_completo']), 1, 0, 'L');
    $pdf->Cell($ancho_cedula, $altura_observacion, utf8_decode($row['cedula']), 1, 0, 'C');
    $pdf->Cell($ancho_entrada, $altura_observacion, utf8_decode($row['hora_entrada']), 1, 0, 'C');
    $pdf->Cell($ancho_salida, $altura_observacion, utf8_decode($row['hora_salida']), 1, 0, 'C');
    $pdf->Cell($ancho_observacion, $altura_observacion, '', 1, 1); // Borde de la celda de observación
}

$pdf->Output('I', 'reporte_asistencia_' . $dia . '.pdf');
exit;
?>