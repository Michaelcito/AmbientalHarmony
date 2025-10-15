<?php
// Asegúrate de que esta ruta a FPDF sea correcta
require('../../PHP/REPORTES2/fpdf.php');

date_default_timezone_set("America/Bogota");

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "harmony");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

// Obtener parámetros de la URL
$articulo_id = $_GET['articulo_id'] ?? null;
$search_month = $_GET['search_month'] ?? null;
$search_date_exact = $_GET['search_date_exact'] ?? null;

if (!$articulo_id) {
    die("ID de artículo no especificado.");
}

// Obtener información del artículo para los encabezados
$sql_articulo = "SELECT nombre, unidad, localizacion, referencia, proveedores, minimo, maximo
                 FROM articulos WHERE id = ?";
$stmt_articulo = $conexion->prepare($sql_articulo);
$stmt_articulo->bind_param("i", $articulo_id);
$stmt_articulo->execute();
$articulo_info = $stmt_articulo->get_result()->fetch_assoc();
$stmt_articulo->close();

$nombre_articulo = $articulo_info['nombre'] ?? 'Desconocido';

// Construir la consulta de movimientos
$sqlMov = "
    SELECT id, fecha, detalle, valor_unitario, entradas_cantidad, entradas_valor,
           salidas_cantidad, salidas_valor, saldos_cantidad, saldos_valor
    FROM control_existencias
    WHERE articulo_id = ?
";
$bind_types = "i";
$bind_params = [$articulo_id];

if ($search_month) {
    $sqlMov .= " AND DATE_FORMAT(fecha, '%Y-%m') = ?";
    $bind_types .= "s";
    $bind_params[] = $search_month;
} elseif ($search_date_exact) {
    $sqlMov .= " AND fecha = ?";
    $bind_types .= "s";
    $bind_params[] = $search_date_exact;
}
$sqlMov .= " ORDER BY fecha ASC, id ASC";

$stmtMov = $conexion->prepare($sqlMov);
$stmtMov->bind_param($bind_types, ...$bind_params);
$stmtMov->execute();
$movs = $stmtMov->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMov->close();

// Recálculo de saldos para el período seleccionado
$saldos_acumulados = ['cantidad' => 0, 'valor' => 0];

if ($search_month || $search_date_exact) {
    $sql_saldo_inicial = "SELECT saldos_cantidad, saldos_valor FROM control_existencias WHERE articulo_id = ? AND fecha < ?";
    $fecha_corte = '';
    if ($search_month) {
        $fecha_corte = $search_month . '-01';
    } elseif ($search_date_exact) {
        $fecha_corte = $search_date_exact;
    }

    if ($fecha_corte) {
        $sql_saldo_inicial .= " ORDER BY fecha DESC, id DESC LIMIT 1";
        $stmt_saldo_inicial = $conexion->prepare($sql_saldo_inicial);
        $stmt_saldo_inicial->bind_param("is", $articulo_id, $fecha_corte);
        $stmt_saldo_inicial->execute();
        $res_saldo_inicial = $stmt_saldo_inicial->get_result()->fetch_assoc();
        $stmt_saldo_inicial->close();

        if ($res_saldo_inicial) {
            $saldos_acumulados['cantidad'] = (float)$res_saldo_inicial['saldos_cantidad'];
            $saldos_acumulados['valor'] = (float)$res_saldo_inicial['saldos_valor'];
        }
    }
}


// Recalcular saldos para cada fila
$movs_recalculated = [];
foreach ($movs as $mov) {
    if ($search_month || $search_date_exact) {
        $saldos_acumulados['cantidad'] += $mov['entradas_cantidad'] - $mov['salidas_cantidad'];
        $saldos_acumulados['valor'] += $mov['entradas_valor'] - $mov['salidas_valor'];
        $mov['saldos_cantidad'] = $saldos_acumulados['cantidad'];
        $mov['saldos_valor'] = $saldos_acumulados['valor'];
    }
    $movs_recalculated[] = $mov;
}


// Creación del PDF
$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Reporte de Control de Existencias'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('Artículo: ' . $nombre_articulo), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 10);
if ($search_month) {
    $periodo = "Período: " . date('F Y', strtotime($search_month));
} elseif ($search_date_exact) {
    $periodo = "Fecha: " . date('d/m/Y', strtotime($search_date_exact));
} else {
    $periodo = "Todos los registros";
}
$pdf->Cell(0, 5, utf8_decode($periodo), 0, 1, 'C');
$pdf->Ln(5);

// Información del artículo
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode("Unidad: " . ($articulo_info['unidad'] ?? 'N/A') . " | Localización: " . ($articulo_info['localizacion'] ?? 'N/A')), 0, 1);
$pdf->Cell(0, 5, utf8_decode("Referencia: " . ($articulo_info['referencia'] ?? 'N/A') . " | Proveedores: " . ($articulo_info['proveedores'] ?? 'N/A')), 0, 1);
$pdf->Cell(0, 5, utf8_decode("Mínimo: " . ($articulo_info['minimo'] ?? 'N/A') . " | Máximo: " . ($articulo_info['maximo'] ?? 'N/A')), 0, 1);
$pdf->Ln(5);

// Encabezados de la tabla
$pdf->SetFont('Arial', 'B', 7);
// Nuevos anchos de columna para evitar superposición
$ancho = [20, 40, 20, 18, 22, 18, 22, 18, 22]; 
$headers = ['Fecha', 'Detalle', 'Valor Unitario', 'Entradas (Cant.)', 'Entradas (Valor)', 'Salidas (Cant.)', 'Salidas (Valor)', 'Saldos (Cant.)', 'Saldos (Valor)'];

// Dibujar los encabezados con un ancho mayor
foreach($headers as $i => $header) {
    $pdf->Cell($ancho[$i], 7, utf8_decode($header), 1, 0, 'C');
}
$pdf->Ln();

// Contenido de la tabla
$pdf->SetFont('Arial', '', 7);

foreach ($movs_recalculated as $mov) {
    // Calculamos la altura necesaria para la fila
    $detalle = utf8_decode($mov['detalle']);
    $altura_detalle = $pdf->GetStringWidth($detalle) > ($ancho[1] - 1) ? 4 * (ceil($pdf->GetStringWidth($detalle) / ($ancho[1] - 1))) : 6;
    $altura_fila = max(6, $altura_detalle);
    
    // Si la nueva fila no cabe, creamos una nueva página
    if ($pdf->GetY() + $altura_fila > $pdf->GetPageHeight() - 15) {
        $pdf->AddPage();
        // Volver a dibujar los encabezados
        $pdf->SetFont('Arial', 'B', 7);
        foreach($headers as $i => $header) {
            $pdf->Cell($ancho[$i], 7, utf8_decode($header), 1, 0, 'C');
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7);
    }
    
    // Guardamos la posición inicial para el texto envuelto
    $x_pos_detalle = $pdf->GetX() + $ancho[0];
    $y_pos_inicial = $pdf->GetY();

    // Dibujamos el texto del detalle usando MultiCell para que envuelva
    $pdf->SetXY($x_pos_detalle, $y_pos_inicial);
    $pdf->MultiCell($ancho[1], 4, $detalle, 0, 'L');

    // Volvemos a la posición inicial y dibujamos las celdas con la altura calculada
    $pdf->SetXY($pdf->GetX(), $y_pos_inicial);
    
    $pdf->Cell($ancho[0], $altura_fila, $mov['fecha'], 1, 0, 'C');
    $pdf->Cell($ancho[1], $altura_fila, '', 1, 0, 'C'); // Celda de detalle vacía (la llenamos con MultiCell)
    $pdf->Cell($ancho[2], $altura_fila, number_format($mov['valor_unitario'], 2), 1, 0, 'C');
    $pdf->Cell($ancho[3], $altura_fila, $mov['entradas_cantidad'], 1, 0, 'C');
    $pdf->Cell($ancho[4], $altura_fila, number_format($mov['entradas_valor'], 2), 1, 0, 'C');
    $pdf->Cell($ancho[5], $altura_fila, $mov['salidas_cantidad'], 1, 0, 'C');
    $pdf->Cell($ancho[6], $altura_fila, number_format($mov['salidas_valor'], 2), 1, 0, 'C');
    $pdf->Cell($ancho[7], $altura_fila, number_format($mov['saldos_cantidad'], 0), 1, 0, 'C');
    $pdf->Cell($ancho[8], $altura_fila, number_format($mov['saldos_valor'], 2), 1, 1, 'C');
}

$pdf->Output('I', 'reporte_existencias_' . str_replace(" ", "_", $nombre_articulo) . '.pdf');
exit;
?>