<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost","root","","harmony");
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error"=>"Conexión fallida"]);
  exit;
}
$sql="SELECT * FROM configuracion_frs LIMIT 1";
$res=$conn->query($sql);
echo json_encode($res&&$res->num_rows? $res->fetch_assoc():[]);
$conn->close();
