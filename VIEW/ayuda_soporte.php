<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ayuda y Soporte - AmbientalHarmony</title>
  <link rel="stylesheet" href="../ASSETS/CSS/ayuda_soporte.css">
</head>
<body>

  <div class="config-container">
    <h2>Manual de Usuario</h2>
    <iframe src="../Manual de usuario AmbientalHarmony.pdf" height="500"></iframe>

    <h2>Formulario de PQRS</h2>
    <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSc0d5bQHAEgT0TXPLwg5fuv0oj8TUZZL_Xo1GSYfR3ycatV_A/viewform?embedded=true" 
            height="700" frameborder="0" marginheight="0" marginwidth="0">
      Cargando…
    </iframe>
  </div>

</body>
</html>
