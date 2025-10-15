<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../VIEW/iniciar sesion.html");
    exit;
}
$conexion = new mysqli("localhost", "root", "", "harmony");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$articulo_id    = intval($_POST['articulo_id']);
$usuario_id     = intval($_SESSION['user_id']);
$fecha          = $_POST['fecha'];
$detalle        = $conexion->real_escape_string($_POST['detalle']); // Sanear detalle
$valor_unitario = floatval($_POST['valor_unitario']);
$entrada_cant   = intval($_POST['entradas_cantidad']); 
$salida_cant    = intval($_POST['salidas_cantidad']);  

$entrada_valor = $valor_unitario * $entrada_cant;
$salida_valor  = $valor_unitario * $salida_cant;

$saldos_cantidad_anterior = 0;
$saldos_valor_anterior = 0;

$sql_ultimo_saldo = "SELECT saldos_cantidad, saldos_valor FROM control_existencias WHERE articulo_id = ? ORDER BY fecha DESC, id DESC LIMIT 1";
$stmt_ultimo_saldo = $conexion->prepare($sql_ultimo_saldo);
$stmt_ultimo_saldo->bind_param("i", $articulo_id);
$stmt_ultimo_saldo->execute();
$result_ultimo_saldo = $stmt_ultimo_saldo->get_result();

if ($ultimo = $result_ultimo_saldo->fetch_assoc()) {
    $saldos_cantidad_anterior = $ultimo['saldos_cantidad'];
    $saldos_valor_anterior = $ultimo['saldos_valor'];
}
$stmt_ultimo_saldo->close();

$saldos_cantidad = $saldos_cantidad_anterior + $entrada_cant - $salida_cant;
$saldos_valor    = $saldos_valor_anterior + $entrada_valor - $salida_valor;

if ($saldos_cantidad < 0) {
    echo "Error: El saldo en cantidad no puede ser negativo.";
    $conexion->close();
    exit;
}

$sql_insert = "INSERT INTO control_existencias 
               (articulo_id, usuario_id, fecha, detalle, valor_unitario, entradas_cantidad, entradas_valor, salidas_cantidad, salidas_valor, saldos_cantidad, saldos_valor)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_insert = $conexion->prepare($sql_insert);
$stmt_insert->bind_param("iissdiddidd", 
    $articulo_id, $usuario_id, $fecha, $detalle, $valor_unitario,
    $entrada_cant, $entrada_valor, $salida_cant, $salida_valor,
    $saldos_cantidad, $saldos_valor
);

if ($stmt_insert->execute()) {
    header("Location: ../../VIEW/control_existencia.php?articulo_id=" . $articulo_id);
    exit;
} else {
    echo "Error al guardar el movimiento: " . $stmt_insert->error;
}

$stmt_insert->close();
$conexion->close();
?>