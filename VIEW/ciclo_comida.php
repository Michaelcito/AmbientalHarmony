<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}

$rol = $_SESSION['user_rol'];

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$result = $conn->query("
    SELECT id, nombre_archivo, tipo_archivo
      FROM ciclo_comida
     ORDER BY fecha_subida DESC, id DESC
     LIMIT 1
");
$archivo = $result->fetch_assoc();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ciclo de Comida</title>
  <link rel="stylesheet" href="../ASSETS/CSS/ciclo_comida.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($archivo): ?>
  <div class="viewer-container">
    <iframe src="../PHP/CICLO_COMIDA/ver_archivo.php?id=<?= $archivo['id'] ?>&v=<?= time() ?>" frameborder="0"></iframe>
  </div>
<?php else: ?>
  <div class="no-archivo">
    <h2>No hay ciclo de comida cargado aún.</h2>
  </div>
<?php endif; ?>

<?php if ($rol !== 'operador'): ?>
  <div class="formulario">
    <form id="formSubir" method="POST" action="../PHP/CICLO_COMIDA/subir_archivo.php" enctype="multipart/form-data">
      <label>Reemplazar ciclo:</label>
      <input type="file" id="inputArchivo" name="archivo" accept=".jpg,.jpeg,.png,.pdf" required>
      <button type="button" onclick="confirmarSubida()">Subir</button>
    </form>
  </div>
<?php endif; ?>

<script>
function confirmarSubida() {
  const archivo = document.getElementById("inputArchivo").files[0];
  if (!archivo) {
    Swal.fire("Advertencia", "Selecciona un archivo antes de continuar.", "warning");
    return;
  }
  const hoy = new Date();
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0).getDate();

  if (hoy.getDate() < ultimoDia) {
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Aún no ha terminado el mes. Se añadirá un nuevo ciclo. ¿Continuar?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, subir",
      cancelButtonText: "Cancelar"
    }).then((res) => {
      if (res.isConfirmed) {
        document.getElementById("formSubir").submit();
      }
    });
  } else {
    document.getElementById("formSubir").submit();
  }
}
</script>
</body>
</html>