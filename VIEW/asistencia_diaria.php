<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}

date_default_timezone_set('America/Bogota');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Asistencia Diaria de Trabajadores</title>
  <link rel="stylesheet" href="../ASSETS/CSS/asistencia.css" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
</head>
<body>
  <div class="container">
    <h1>ASISTENCIA DIARIA — <span id="fecha-hoy"></span></h1>

    <form id="form-asistencia" action="../PHP/ASISTENCIA/guardar_asistencia.php" method="POST" novalidate>
      <input type="hidden" name="fecha" id="input-fecha" value="<?php echo date('Y-m-d'); ?>">

      <label for="nombre_completo">Nombre completo:</label>
      <input
        type="text"
        id="nombre_completo"
        name="nombre_completo"
        required
        minlength="3"
        maxlength="100"
        pattern="[A-Za-zÑñÁÉÍÓÚáéíóú\s]+"
        title="Solo letras y espacios. Min 3 caracteres."
        oninput="this.value=this.value.replace(/[^A-Za-zÑñÁÉÍÓÚáéíóú\s]/g,'')"
      >

      <label for="cedula">Cédula:</label>
      <input
        type="text"
        id="cedula"
        name="cedula"
        required
        inputmode="numeric"
        pattern="\d{6,12}"
        minlength="6"
        maxlength="12"
        title="Solo numeros. Entre 6 y 12 digitos."
        oninput="this.value=this.value.replace(/\D/g,'')"
      >

      <label for="hora_entrada">Hora de entrada:</label>
      <input type="time" id="hora_entrada" name="hora_entrada" required>

      <label for="hora_salida">Hora de salida:</label>
      <input type="time" id="hora_salida" name="hora_salida" required>

      <label for="observacion">Observacion:</label>
      <input
        type="text"
        id="observacion"
        name="observacion"
        placeholder="(opcional)"
        maxlength="255"
      >

      <button type="submit" class="btn-primary">Registrar asistencia</button>
    </form>

    <hr>

    <h2>Asistencia registrada hoy</h2>
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Cédula</th>
          <th>Entrada</th>
          <th>Salida</th>
          <th>Observación</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $conn = new mysqli("localhost", "root", "", "harmony");
        if ($conn->connect_error) {
            echo "<tr><td colspan='5'>Error al cargar la asistencia: " . $conn->connect_error . "</td></tr>";
        } else {
            $conn->set_charset("utf8mb4");

            $fecha = date('Y-m-d');
            $stmt = $conn->prepare(
              "SELECT nombre_completo, cedula, hora_entrada, hora_salida, observacion 
               FROM asistencia_diaria 
               WHERE fecha = ?"
            );
            $stmt->bind_param("s", $fecha);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($fila = $result->fetch_assoc()) {
                  echo "<tr>
                            <td>{$fila['nombre_completo']}</td>
                            <td>{$fila['cedula']}</td>
                            <td>{$fila['hora_entrada']}</td>
                            <td>{$fila['hora_salida']}</td>
                            <td>{$fila['observacion']}</td>
                        </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No hay asistencias registradas para hoy.</td></tr>";
            }

            $stmt->close();
            $conn->close();
        }
        ?>
      </tbody>
    </table>

    <?php
    $rol = $_SESSION['user_rol'] ?? '';
    if ($rol === 'admin' || $rol === 'superadmin'):
    ?>
    <form id="form-finalizar" action="../PHP/ASISTENCIA/finalizar_dia.php" method="POST">
      <button type="submit" class="btn btn-danger">
        Finalizar Día y Guardar
      </button>
    </form>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../ASSETS/JS/asistencia_diaria.js"></script>

  <?php
  if (isset($_GET['status'])) {
      $status = $_GET['status'];
      $title = '';
      $text = '';
      $icon = '';

      switch ($status) {
          case 'success':
              $title = '¡Éxito!';
              $text = 'Asistencia registrada correctamente.';
              $icon = 'success';
              break;
          case 'duplicate_entry':
              $title = '¡Atención!';
              $text = 'Ya existe un registro de asistencia para esta cédula en la fecha de hoy. No se puede registrar dos veces.';
              $icon = 'warning';
              break;
          case 'error':
              $title = '¡Error!';
              $text = $_GET['message'] ?? 'Hubo un problema al registrar la asistencia.';
              $icon = 'error';
              break;
      }

      echo "<script type='text/javascript'>
              document.addEventListener('DOMContentLoaded', function() {
                  Swal.fire({
                      title: '{$title}',
                      text: '{$text}',
                      icon: '{$icon}',
                      confirmButtonText: 'Ok'
                  }).then(() => {
                    window.history.replaceState({}, document.title, window.location.pathname);
                  });
              });
            </script>";
  }
  ?>
</body>
</html>