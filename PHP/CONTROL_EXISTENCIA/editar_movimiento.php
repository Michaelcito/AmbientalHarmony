<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../VIEW/iniciar sesion.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conexion = new mysqli("localhost", "root", "", "harmony");
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $mov_id         = intval($_POST['movimiento_id']);
    $detalle        = $conexion->real_escape_string($_POST['detalle']);
    $fecha          = $_POST['fecha'];
    $valor_unitario = floatval($_POST['valor_unitario']);
    $entrada_cant   = intval($_POST['entradas_cantidad']); 
    $salida_cant    = intval($_POST['salidas_cantidad']);   

    $entrada_valor = $valor_unitario * $entrada_cant;
    $salida_valor  = $valor_unitario * $salida_cant;

    $sql_get_articulo = "SELECT articulo_id FROM control_existencias WHERE id = ?";
    $stmt_get_articulo = $conexion->prepare($sql_get_articulo);
    $stmt_get_articulo->bind_param("i", $mov_id);
    $stmt_get_articulo->execute();
    $result_get_articulo = $stmt_get_articulo->get_result();
    $movimiento_original = $result_get_articulo->fetch_assoc();
    $articulo_id = $movimiento_original['articulo_id'];
    $stmt_get_articulo->close();

    $sql_update_mov = "UPDATE control_existencias SET 
                       fecha = ?, detalle = ?, valor_unitario = ?, 
                       entradas_cantidad = ?, entradas_valor = ?, 
                       salidas_cantidad = ?, salidas_valor = ? 
                       WHERE id = ?";
    $stmt_update_mov = $conexion->prepare($sql_update_mov);
    $stmt_update_mov->bind_param("ssddiidi", 
        $fecha, $detalle, $valor_unitario, 
        $entrada_cant, $entrada_valor, 
        $salida_cant, $salida_valor, 
        $mov_id
    );

    if (!$stmt_update_mov->execute()) {
        echo "Error al actualizar el movimiento: " . $stmt_update_mov->error;
        $stmt_update_mov->close();
        $conexion->close();
        exit;
    }
    $stmt_update_mov->close();

    $sql_recalculate = "SELECT id, fecha, entradas_cantidad, entradas_valor, salidas_cantidad, salidas_valor, valor_unitario 
                        FROM control_existencias 
                        WHERE articulo_id = ? 
                        ORDER BY fecha ASC, id ASC";
    
    $stmt_recalculate = $conexion->prepare($sql_recalculate);
    $stmt_recalculate->bind_param("i", $articulo_id);
    $stmt_recalculate->execute();
    $result_recalculate = $stmt_recalculate->get_result();

    $saldo_cantidad_acumulado = 0;
    $saldo_valor_acumulado = 0;

    while ($row = $result_recalculate->fetch_assoc()) {
        $current_id = $row['id'];
        $current_entrada_cant = $row['entradas_cantidad'];
        $current_salida_cant = $row['salidas_cantidad'];
        $current_entrada_valor = $row['entradas_valor'];
        $current_salida_valor = $row['salidas_valor'];

        $saldo_cantidad_acumulado += $current_entrada_cant - $current_salida_cant;
        $saldo_valor_acumulado += $current_entrada_valor - $current_salida_valor;

        $sql_update_saldos = "UPDATE control_existencias SET 
                              saldos_cantidad = ?, saldos_valor = ? 
                              WHERE id = ?";
        $stmt_update_saldos = $conexion->prepare($sql_update_saldos);
        $stmt_update_saldos->bind_param("idi", 
            $saldo_cantidad_acumulado, 
            $saldo_valor_acumulado, 
            $current_id
        );
        $stmt_update_saldos->execute();
        $stmt_update_saldos->close();
    }
    $stmt_recalculate->close();

    header("Location: ../../VIEW/control_existencia.php?articulo_id=" . $articulo_id);
    exit;
} else {
    header("Location: ../../VIEW/control_existencia.php");
    exit;
}
?>