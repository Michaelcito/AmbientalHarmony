<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../VIEW/iniciar sesion.html");
  exit;
}

date_default_timezone_set('America/Bogota');

$fecha_creacion = date('Y-m-d');

$conexion = new mysqli("localhost", "root", "", "harmony");
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

if (
  isset($_POST['nombre'], $_POST['unidad'], $_POST['localizacion'], $_POST['referencia'], $_POST['proveedores'],
        $_POST['minimo'], $_POST['maximo'], $_POST['usuario_id'])
) {
  $nombre       = $_POST['nombre'];
  $unidad       = $_POST['unidad'];
  $localizacion = $_POST['localizacion'];
  $referencia   = $_POST['referencia'];
  $proveedores  = $_POST['proveedores'];
  $minimo       = floatval($_POST['minimo']);
  $maximo       = floatval($_POST['maximo']);
  $usuario_id   = intval($_POST['usuario_id']);

  $stmt = $conexion->prepare("INSERT INTO articulos 
    (nombre, unidad, localizacion, referencia, proveedores, minimo, maximo, usuario_id, fecha_creacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssddis", 
    $nombre, $unidad, $localizacion, $referencia, $proveedores,
    $minimo, $maximo, $usuario_id, $fecha_creacion
  );

  if ($stmt->execute()) {
    $nuevoId = $stmt->insert_id;
    header("Location: ../../VIEW/control_existencia.php?articulo_id=" . $nuevoId);
    exit;
  } else {
    echo "Error al insertar: " . $stmt->error;
  }

  $stmt->close();
} else {
  echo "Faltan datos del formulario.";
}

$conexion->close();
?>