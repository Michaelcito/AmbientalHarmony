<?php
// Asegúrate de que este archivo está en el mismo directorio que fpdf.php
require('fpdf.php');

date_default_timezone_set('America/Bogota');
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error BD: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("ID de planilla no especificado.");
}

$id = (int)$_GET['id'];

// Obtener la configuración general de la planilla
$stmt_config = $conn->prepare("SELECT * FROM historia_planillas WHERE id=?");
$stmt_config->bind_param('i', $id);
$stmt_config->execute();
$config_h = $stmt_config->get_result()->fetch_assoc();
$stmt_config->close();

if (!$config_h) {
    die("Planilla no encontrada.");
}

// Obtener los registros de detalle de la planilla
$stmt_detalle = $conn->prepare("
    SELECT fecha, tipoA, tipoB, tipoC, tipoD, contingencia,
           resp_ie AS respIE, resp_asoc AS respAsoc, resp_int AS respInt
    FROM detalle_planillas WHERE historial_id=?
    ORDER BY fecha ASC
");
$stmt_detalle->bind_param('i', $id);
$stmt_detalle->execute();
$detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();
$conn->close();

// Creación de la instancia de FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Reporte de Planilla FRS', 0, 1, 'C');
$pdf->Ln(5);

// ----------------------------------------------------
// Sección de Información General de la Planilla
// ----------------------------------------------------
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Informacion General', 0, 1);
$pdf->SetFont('Arial', '', 10);

$campos = [
    'consecutivo'   => 'Consecutivo',
    'periodo'       => 'Periodo',
    'institucion'   => 'Institucion Educativa',
    'direccion'     => 'Direccion Sede',
    'localidad'     => 'Localidad / Barrio',
    'tipo_entrega'  => 'Tipo de Entrega',
    'anio'          => 'Anio',
    'convenio'      => 'No. Convenio',
    'operador'      => 'Operador / Asociado',
    'rector'        => 'Rector (Nombre y Telefono)'
];

foreach ($campos as $field => $label) {
    $value = $config_h[$field] ?? 'N/A';
    if ($field == 'periodo') {
        $value = date("F Y", strtotime($value . "-01"));
    }
    $pdf->Cell(60, 6, utf8_decode($label . ':'), 0);
    $pdf->Cell(0, 6, utf8_decode($value), 0, 1);
}

$pdf->Ln(10); // Salto de línea después de la información general

// ----------------------------------------------------
// Sección de Registros (Detalle)
// ----------------------------------------------------
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Registros', 0, 1);
$pdf->SetFont('Arial', 'B', 9);

// Cabecera de la tabla
$pdf->Cell(20, 8, 'Fecha', 1);
$pdf->Cell(12, 8, 'A', 1, 0, 'C');
$pdf->Cell(12, 8, 'B', 1, 0, 'C');
$pdf->Cell(12, 8, 'C', 1, 0, 'C');
$pdf->Cell(12, 8, 'D', 1, 0, 'C');
$pdf->Cell(18, 8, 'Cont.', 1, 0, 'C');
$pdf->Cell(28, 8, 'Resp IE', 1, 0, 'C');
$pdf->Cell(30, 8, 'Resp Asoc', 1, 0, 'C');
$pdf->Cell(30, 8, 'Resp Int', 1, 1, 'C'); // Salto de línea al final de la celda

// Datos de la tabla
$pdf->SetFont('Arial', '', 8);
foreach ($detalle as $row) {
    $pdf->Cell(20, 8, $row['fecha'], 1);
    $pdf->Cell(12, 8, $row['tipoA'], 1, 0, 'C');
    $pdf->Cell(12, 8, $row['tipoB'], 1, 0, 'C');
    $pdf->Cell(12, 8, $row['tipoC'], 1, 0, 'C');
    $pdf->Cell(12, 8, $row['tipoD'], 1, 0, 'C');
    $pdf->Cell(18, 8, $row['contingencia'] ? 'Si' : 'No', 1, 0, 'C');
    $pdf->Cell(28, 8, substr($row['respIE'], 0, 10), 1, 0, 'C');
    $pdf->Cell(30, 8, substr($row['respAsoc'], 0, 10), 1, 0, 'C');
    $pdf->Cell(30, 8, substr($row['respInt'], 0, 10), 1, 1, 'C'); // Salto de línea
}

$pdf->Output('I', 'reporte_completo_id_' . $id . '.pdf');
exit;
?>