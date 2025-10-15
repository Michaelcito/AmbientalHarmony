<?php
session_start();

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    header("Location: ../PHP/error_servidor.php");
    exit;
}

require_once '../PHP/USUARIOS/sesion_larga.php';
verificarSesionPersistente($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit;
}

$nombre = $_SESSION['user_name'];
$rol    = $_SESSION['user_rol'];
$fotoperfil = !empty($_SESSION['user_foto'])
    ? '../PHP/USUARIOS/FOTOS/' . $_SESSION['user_foto']
    : '../ASSETS/IMG/default.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AmbientalHarmony</title>
  <link rel="icon" type="image/x-icon" href="../ASSETS/IMG/Logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../ASSETS/CSS/interfaz principal.css">
</head>
<body>
  <div id="loading-overlay" class="loading-overlay">
    <div class="loading-icons">
      <i class="fas fa-utensils cutlery-icon fork"></i>
      <i class="fas fa-utensils cutlery-icon knife"></i>
    </div>
  </div>

  <div class="container">
    <div class="sidebar">
      <div class="logo">
        <h2 style="cursor:pointer" onclick="loadHomePage()">AmbientalHarmony</h2>
        <p>Bienvenido, <?= htmlspecialchars($nombre) ?> (<?= htmlspecialchars($rol) ?>)</p>
      </div>

      <div class="profile">
        <img src="<?= htmlspecialchars($fotoperfil) ?>" alt="Foto de perfil" class="profile-pic">
        <div class="profile-buttons">
          <button onclick="openProfile()">Perfil</button>
          <?php if ($rol === 'superadmin'): ?>
            <button onclick="openUserManagement()">Gestionar Usuarios</button>
          <?php endif; ?>
        </div>
      </div>

      <nav class="menu">
        <ul>
          <li class="dropdown">
            <div class="dropdown-details">
              <button class="summary-btn">
                <i class="fas fa-file-alt"></i>
                <span class="dropdown-title">Planillas</span>
                <i class="fas fa-chevron-down chevron"></i>
              </button>
              <ul class="submenu">
                <?php if ($rol !== 'operador'): ?>
                  <li><a href="planilla_FRS.php" target="contentFrame">FRS</a></li>
                  <li><a href="control_existencia.php" target="contentFrame">Control Existencia</a></li>
                <?php endif; ?>
                <li><a href="asistencia_diaria.php" target="contentFrame">Asistencia</a></li>
                <li><a href="ciclo_comida.php" target="contentFrame">Ciclo de Comida</a></li>
              </ul>
            </div>
          </li>

          <?php if ($rol !== 'operador'): ?>
            <li class="dropdown-single">
              <a href="historial_general.php" target="contentFrame">
                <i class="fas fa-history"></i>
                <span class="dropdown-title">Historia de Planillas</span>
              </a>
            </li>
          <?php endif; ?>

          <li class="dropdown-single separador-superior">
            <a href="novedades_diarias.php" target="contentFrame">
              <i class="fas fa-bell"></i>
              <span class="dropdown-title">Novedades diarias</span>
            </a>
          </li>
        </ul>
      </nav>

      <div class="login-footer">
        <a href="ayuda_soporte.php" class="config-btn" title="Configuración" onclick="openConfig(event)">
          <i class="fas fa-cog"></i>
          <span class="logout-text">Ayuda y Soporte</span>
        </a>

        <a href="#" class="logout-btn" title="Cerrar sesión" onclick="confirmarCierreSesion(event)">
          <i class="fas fa-power-off"></i>
          <span class="logout-text">Cerrar sesión</span>
        </a>
      </div>
    </div>

    <main class="iframe-container">
      <iframe name="contentFrame" src="modulos_inicio.php"></iframe>
    </main>
  </div>

  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <h3>¿Deseas cerrar sesión?</h3>
      <form method="POST" action="../PHP/USUARIOS/logout.php">
        <div class="modal-buttons">
          <button type="submit">Aceptar</button>
          <button type="button" onclick="cerrarModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../ASSETS/JS/interfaz_usuario.js"></script>
</body>
</html>
<?php
if ($conn) {
    $conn->close();
}
?>
