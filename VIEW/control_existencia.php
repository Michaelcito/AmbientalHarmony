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

$conexion = new mysqli("localhost", "root", "", "harmony");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

date_default_timezone_set("America/Bogota");
$hoy = date('Y-m-d');

$sql_articulos = "
    SELECT id, nombre, unidad, localizacion, referencia, proveedores, minimo, maximo
    FROM articulos
    WHERE activo = 1
";
$stmt_articulos = $conexion->prepare($sql_articulos);
$stmt_articulos->execute();
$articulos = $stmt_articulos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_articulos->close();

$sql_inactivos = "
    SELECT id, nombre
    FROM articulos
    WHERE activo = 0
";
$stmt_inactivos = $conexion->prepare($sql_inactivos);
$stmt_inactivos->execute();
$inactivos = $stmt_inactivos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_inactivos->close();

$movimientos_por_articulo = [];
$saldo_actual_por_articulo = [];

foreach ($articulos as $articulo) {
    $aid = $articulo['id'];

    $sqlMov = "SELECT id, fecha, detalle, valor_unitario, entradas_cantidad, entradas_valor,
                      salidas_cantidad, salidas_valor, saldos_cantidad, saldos_valor
                FROM control_existencias
                WHERE articulo_id = ?";
    $bind_types = "i"; $bind_params = [$aid];

    // Se mantiene la lógica de búsqueda aquí, usando $_GET['articulo_id'] para filtrar
    if (isset($_GET['articulo_id']) && $_GET['articulo_id'] == $aid) {
        if (!empty($_GET['search_month'])) {
            $sqlMov .= " AND DATE_FORMAT(fecha, '%Y-%m') = ?";
            $bind_types .= "s";
            $bind_params[] = $_GET['search_month'];
        } elseif (!empty($_GET['search_date_exact'])) {
            $sqlMov .= " AND fecha = ?";
            $bind_types .= "s";
            $bind_params[] = $_GET['search_date_exact'];
        }
    }
    $sqlMov .= " ORDER BY fecha DESC, id DESC";
    if (empty($_GET['search_month']) && empty($_GET['search_date_exact'])) {
        $sqlMov .= " LIMIT 10"; // Solo limitar si no hay búsqueda activa
    }
    $stmtMov = $conexion->prepare($sqlMov);
    $stmtMov->bind_param($bind_types, ...$bind_params);
    $stmtMov->execute();
    $movs = $stmtMov->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtMov->close();

    // Recálculo de saldos para búsquedas por mes/fecha
    if (!empty($_GET['search_month']) || !empty($_GET['search_date_exact'])) {
        $saldos_acumulados = ['cantidad' => 0, 'valor' => 0];
        $movs_recalculated = [];

        $sql_saldo_inicial = "SELECT saldos_cantidad, saldos_valor FROM control_existencias WHERE articulo_id = ? AND fecha < ?";
        $fecha_corte = '';
        if (!empty($_GET['search_month'])) {
            $fecha_corte = $_GET['search_month'] . '-01'; // Primer día del mes de búsqueda
        } elseif (!empty($_GET['search_date_exact'])) {
            $fecha_corte = $_GET['search_date_exact']; // La fecha exacta
        }

        if ($fecha_corte) { // Solo si hay una fecha de corte definida
            $sql_saldo_inicial .= " ORDER BY fecha DESC, id DESC LIMIT 1";
            $stmt_saldo_inicial = $conexion->prepare($sql_saldo_inicial);
            $stmt_saldo_inicial->bind_param("is", $aid, $fecha_corte);
            $stmt_saldo_inicial->execute();
            $res_saldo_inicial = $stmt_saldo_inicial->get_result()->fetch_assoc();
            $stmt_saldo_inicial->close();

            if ($res_saldo_inicial) {
                $saldos_acumulados['cantidad'] = (float)$res_saldo_inicial['saldos_cantidad'];
                $saldos_acumulados['valor'] = (float)$res_saldo_inicial['saldos_valor'];
            }
        }

        // Ordenar los movimientos del período de búsqueda cronológicamente para recalcular saldos
        usort($movs, function($a, $b) {
            if ($a['fecha'] === $b['fecha']) {
                return $a['id'] <=> $b['id'];
            }
            return $a['fecha'] <=> $b['fecha'];
        });

        foreach ($movs as $m) {
            $saldos_acumulados['cantidad'] += $m['entradas_cantidad'] - $m['salidas_cantidad'];
            $saldos_acumulados['valor']    += $m['entradas_valor'] - $m['salidas_valor'];

            $m['saldos_cantidad_display'] = $saldos_acumulados['cantidad'];
            $m['saldos_valor_display']  = $saldos_acumulados['valor'];
            $movs_recalculated[] = $m;
        }
        // Devolver los movimientos al orden original (más reciente primero) para la tabla
        $movs = array_reverse($movs_recalculated);

    } else {
        // Si no hay búsqueda, simplemente usa los saldos guardados
        foreach ($movs as &$m) {
            $m['saldos_cantidad_display'] = $m['saldos_cantidad'];
            $m['saldos_valor_display']   = $m['saldos_valor'];
        }
        unset($m); // Romper la referencia al último elemento
    }
    $movimientos_por_articulo[$aid] = $movs;

    $stmtSaldo = $conexion->prepare(
        "SELECT saldos_cantidad, saldos_valor
         FROM control_existencias
         WHERE articulo_id = ?
         ORDER BY fecha DESC, id DESC
         LIMIT 1"
    );
    $stmtSaldo->bind_param("i", $aid);
    $stmtSaldo->execute();
    $resSaldo = $stmtSaldo->get_result()->fetch_assoc();
    $stmtSaldo->close();

    $saldo_actual_por_articulo[$aid] = [
        'cantidad' => (float)($resSaldo['saldos_cantidad'] ?? 0),
        'valor'    => (float)($resSaldo['saldos_valor']      ?? 0),
    ];
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Existencias</title>
    <link rel="stylesheet" href="../ASSETS/CSS/control_existencia.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .report-buttons {
            margin: 10px 0;
            text-align: right;
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

    <h1>Artículos Activos</h1>
    <div id="contenedor-planillas">
    <?php foreach ($articulos as $articulo): ?>
        <?php $id = $articulo['id']; ?>
        <div class="planilla"
             data-articulo-saldo-actual="<?= $saldo_actual_por_articulo[$id]['cantidad'] ?>"
             data-articulo-saldo-valor="<?= $saldo_actual_por_articulo[$id]['valor'] ?>">
            <div class="planilla-header" data-target="#content-<?= $id ?>">
                <span>Planilla de Control de Existencias - <?= htmlspecialchars($articulo['nombre']) ?></span>
                <span class="toggle-icon">&#9658;</span>
            </div>
            <div id="content-<?= $id ?>" class="planilla-content">
                <section class="configuracion">
                    <div><label>Artículo:</label><input type="text" value="<?= htmlspecialchars($articulo['nombre']) ?>" disabled></div>
                    <div><label>Unidad:</label><input type="text" value="<?= htmlspecialchars($articulo['unidad']) ?>" disabled></div>
                    <div><label>Localización:</label><input type="text" value="<?= htmlspecialchars($articulo['localizacion']) ?>" disabled></div>
                    <div><label>Referencia:</label><input type="text" value="<?= htmlspecialchars($articulo['referencia']) ?>" disabled></div>
                    <div><label>Proveedores:</label><input type="text" value="<?= htmlspecialchars($articulo['proveedores']) ?>" disabled></div>
                    <div><label>Mínimo:</label><input type="number" value="<?= $articulo['minimo'] ?>" disabled></div>
                    <div><label>Máximo:</label><input type="number" value="<?= $articulo['maximo'] ?>" disabled></div>
                </section>

                <button class="btn-primary agregar-dia">Agregar Día</button>

                <form class="deshabilitar-form" data-articulo-id="<?= $id ?>" style="display:inline;">
                    <input type="hidden" name="articulo_id" value="<?= $id ?>">
                    <button type="button" class="btn-danger deshabilitar-btn">Deshabilitar artículo</button>
                </form>

                <section class="daily-form" style="display:none;">
                    <h3 class="form-title">Registrar Nuevo Día</h3>
                    <form action="../PHP/CONTROL_EXISTENCIA/guardar_movimiento.php" method="POST" class="form-movimiento">
                        <input type="hidden" name="articulo_id" value="<?= $id ?>">
                        <input type="hidden" name="usuario_id" value="<?= $_SESSION['user_id'] ?>">
                        <input type="hidden" name="movimiento_id" value="">

                        <div class="field-group">
                            <div>
  <label>Fecha</label>
  <input type="date" name="fecha" value="<?= $hoy ?>" required readonly aria-readonly="true" tabindex="-1">
</div>

                            <div style="grid-column: span 2;"><label>Detalle</label><input type="text" name="detalle" required></div>
                            <div><label>Valor Unitario</label><input type="number" name="valor_unitario" step="0.01" **min="0"** required></div>
                            <div><label>Entradas (Cantidad)</label><input type="number" name="entradas_cantidad" **min="0"** value="0" required></div>
                            <div><label>Salidas (Cantidad)</label><input type="number" name="salidas_cantidad" **min="0"** value="0" required></div>
                        </div>
                        <div class="actions">
                            <button class="btn-save" type="submit">Guardar</button>
                            <button class="btn-cancel" type="button">Cancelar</button>
                        </div>
                    </form>
                </section>

                <div class="search-bar-container">
                    <form method="GET" class="search-form" data-articulo-id="<?= $id ?>">
                        <input type="hidden" name="articulo_id" value="<?= $id ?>">

                        <label for="search_month_<?= $id ?>">Buscar por mes:</label>
                        <input type="month" id="search_month_<?= $id ?>" name="search_month"
                               value="<?= (isset($_GET['search_month']) && $_GET['articulo_id']==$id) ? htmlspecialchars($_GET['search_month']) : '' ?>"
                               class="search-input">

                        <label for="search_date_exact_<?= $id ?>">Buscar por fecha exacta:</label>
                        <input type="date" id="search_date_exact_<?= $id ?>" name="search_date_exact"
                               value="<?= (isset($_GET['search_date_exact']) && $_GET['articulo_id']==$id) ? htmlspecialchars($_GET['search_date_exact']) : '' ?>"
                               class="search-input">

                        <button type="submit" class="submit-search-btn">Buscar</button>
                        <button type="button" class="clear-search-btn" data-articulo-id="<?= $id ?>">Limpiar Búsqueda</button>
                    </form>
                </div>
                
                <div class="report-buttons">
                    <a href="../PHP/REPORTES4/generar_existencias_pdf.php?articulo_id=<?= $id ?>&search_month=<?= (isset($_GET['search_month']) && $_GET['articulo_id']==$id) ? htmlspecialchars($_GET['search_month']) : '' ?>&search_date_exact=<?= (isset($_GET['search_date_exact']) && $_GET['articulo_id']==$id) ? htmlspecialchars($_GET['search_date_exact']) : '' ?>"
                       class="btn-reporte">Descargar PDF</a>
                </div>

                <table class="tabla-registros">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Detalle</th><th>Valor Unitario</th><th>Entradas</th>
                            <th>Entradas (Valor)</th><th>Salidas</th><th>Salidas (Valor)</th>
                            <th>Saldos (Cant.)</th><th>Saldos (Valor)</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movimientos_por_articulo[$id] as $mov): ?>
                        <tr>
                            <td><?= $mov['fecha'] ?></td>
                            <td><?= htmlspecialchars($mov['detalle']) ?></td>
                            <td><?= number_format($mov['valor_unitario'],2) ?></td>
                            <td><?= $mov['entradas_cantidad'] ?></td>
                            <td><?= number_format($mov['entradas_valor'],2) ?></td>
                            <td><?= number_format($mov['salidas_cantidad'],2) ?></td>
                            <td><?= number_format($mov['salidas_valor'],2) ?></td>
                            <td><?= number_format($mov['saldos_cantidad_display'],0) ?></td>
                            <td><?= number_format($mov['saldos_valor_display'],2) ?></td>
                            <td>
                                <?php if ($mov['fecha'] === $hoy): ?>
                                    <button class="btn-edit"
                                                data-id="<?= $mov['id'] ?>"
                                                data-fecha="<?= $mov['fecha'] ?>"
                                                data-detalle="<?= htmlspecialchars($mov['detalle']) ?>"
                                                data-unitario="<?= $mov['valor_unitario'] ?>"
                                                data-entrada="<?= $mov['entradas_cantidad'] ?>"
                                                data-salida="<?= $mov['salidas_cantidad'] ?>">
                                        Editar
                                    </button>
                                    <form class="eliminar-movimiento-form" style="display:inline;">
                                        <input type="hidden" name="movimiento_id" value="<?= $mov['id'] ?>">
                                        <button type="button" class="eliminar-movimiento-btn">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <h1>Artículos Deshabilitados</h1>
    <ul>
    <?php if (empty($inactivos)): ?>
        <li>No hay artículos deshabilitados.</li>
    <?php else: ?>
        <?php foreach ($inactivos as $art): ?>
            <li>
                <?= htmlspecialchars($art['nombre']) ?>
                <form class="reactivar-form" data-articulo-id="<?= $art['id'] ?>" style="display:inline;">
                    <input type="hidden" name="articulo_id" value="<?= $art['id'] ?>">
                    <button type="button" class="btn-primary reactivar-btn">Reactivar</button>
                </form>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ul>

    <div style="margin: 20px; text-align: center;">
        <a href="crear_articulo.php" class="btn-new-articulo">Agregar nuevo artículo</a>
    </div>

    <script src="../ASSETS/JS/control_existencia.js"></script>
</body>
</html>