<?php
session_start();

date_default_timezone_set('America/Bogota');
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
  die("Error: usuario no autenticado.");
}
$usuario_id = $_SESSION['user_id'];

$conf = [
  'consecutivo'    => $_POST['consecutivo'] ?? '',
  'periodo'        => $_POST['periodo']     ?? '',
  'institucion'    => $_POST['institucion'] ?? '',
  'direccion'      => $_POST['direccion']   ?? '',
  'localidad'      => $_POST['localidad']   ?? '',
  'tipo_entrega'   => $_POST['tipo']        ?? '',
  'anio'           => $_POST['anio']        ?? '',
  'convenio'       => $_POST['convenio']    ?? '',
  'operador'       => $_POST['operador']    ?? '',
  'rector'         => $_POST['rector']      ?? '',
  'fecha_guardado' => date('Y-m-d H:i:s')
];

$stmt = $conn->prepare("
  INSERT INTO historia_planillas (
    consecutivo, periodo, institucion, direccion, localidad,
    tipo_entrega, anio, convenio, operador, rector,
    fecha_guardado, usuario_id
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sssssssssssi",
  $conf['consecutivo'], $conf['periodo'], $conf['institucion'],
  $conf['direccion'], $conf['localidad'], $conf['tipo_entrega'],
  $conf['anio'], $conf['convenio'], $conf['operador'],
  $conf['rector'], $conf['fecha_guardado'], $usuario_id
);
$stmt->execute();
$historial_id = $stmt->insert_id;
$stmt->close();

$registros = $_POST['reg'] ?? [];

if (!empty($registros)) {
  $stmt = $conn->prepare("
    INSERT INTO detalle_planillas (
      historial_id, fecha, tipoA, tipoB, tipoC, tipoD,
      contingencia, resp_ie, resp_asoc, resp_int, usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  foreach ($registros as $r) {
    $stmt->bind_param("isiiiissssi",
      $historial_id, $r['fecha'], $r['tipoA'], $r['tipoB'], $r['tipoC'], $r['tipoD'],
      $r['cont'], $r['respIE'], $r['respAsoc'], $r['respInt'], $usuario_id
    );
    $stmt->execute();
  }
  $stmt->close();
}

$conn->query("DELETE FROM registros_frs");

$conn->query("UPDATE configuracion_frs 
              SET consecutivo = CONCAT('FRS-', LPAD(SUBSTRING_INDEX(consecutivo, '-', -1)+1, 3, '0'))");

$conn->close();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
