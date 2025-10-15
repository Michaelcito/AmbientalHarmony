<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: iniciar sesion.html");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $movimiento_id = intval($_POST['movimiento_id']);
  $detalle = trim($_POST['detalle']);
  $valor_unitario = floatval($_POST['valor_unitario']);
  $entrada_cant = intval($_POST['entrada_cant']);
  $salida_cant = intval($_POST['salida_cant']);

  $conexion = new mysqli("localhost", "root", "", "harmony");
  if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
  }

  $usuario_id = $_SESSION['user_id'];
  date_default_timezone_set("America/Bogota");
  $hoy = date("Y-m-d");

  $sql = "SELECT ce.id FROM control_existencias ce
          JOIN articulos a ON ce.articulo_id = a.id
          WHERE ce.id = ? AND a.usuario_id = ? AND ce.fecha = ?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("iis", $movimiento_id, $usuario_id, $hoy);
  $stmt->execute();
  $resultado = $stmt->get_result();

  if ($resultado->num_rows === 1) {
    $update = $conexion->prepare("UPDATE control_existencias SET detalle = ?, valor_unitario = ?, entradas_cantidad = ?, salidas_cantidad = ? WHERE id = ?");
    $update->bind_param("sdiii", $detalle, $valor_unitario, $entrada_cant, $salida_cant, $movimiento_id);
    $update->execute();
  }

  $stmt->close();
  $conexion->close();
}

header("Location: ../VIEW/control_existencia.php");
exit;
