<?php
session_start();
$back = '../../VIEW/gestionar usuarios.php';
if (!isset($_SESSION['user_rol'])||$_SESSION['user_rol']!=='superadmin') {
    header("Location:$back"); exit();
}

$conn = new mysqli("localhost","root","","harmony");
if ($conn->connect_error) {
    $_SESSION['flash_error']='Error DB';
    header("Location:$back"); exit();
}

$id = isset($_POST['id'])&&is_numeric($_POST['id'])
                 ? intval($_POST['id']) : 0;
$estado_actual = trim($_POST['estado_actual'] ?? 'Activo');
if (!$id) {
    header("Location:$back"); exit();
}

$nuevo = ($estado_actual==='Activo')?'Inactivo':'Activo';
$stmt  = $conn->prepare(
  "UPDATE usuarios SET estado = ? WHERE id = ?"
);
$stmt->bind_param("si",$nuevo,$id);
if (!$stmt->execute()) {
    $_SESSION['flash_error']='Error al cambiar estado';
}
$stmt->close();
$conn->close();

header("Location:$back");
exit();
