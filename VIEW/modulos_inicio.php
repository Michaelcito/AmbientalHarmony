<?php
session_start();
$rol = $_SESSION['user_rol'] ?? '';

$nombre = $_SESSION['user_name'];
$rol    = $_SESSION['user_rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AmbientalHarmony - Módulos</title>
  <link rel="stylesheet" href="../ASSETS/CSS/modulos_inicio.css"/>
</head>
<body>
  <div class="container">
    <h1 class="title">AmbientalHarmony</h1>
    <p class="subtitle">¿Qué deseas registrar el dia de hoy?:)</p>

    <div class="modules-grid">
      <?php if ($rol === 'superadmin' || $rol === 'admin'): ?>
        <a class="module" href="planilla_FRS.php" target="contentFrame">
          <h2>📋 FRS</h2>
          <p>Registro de entrega de Servicios Integrales de Desayunos y Almuerzos Escolares</p>
        </a>
        <a class="module" href="asistencia_diaria.php" target="contentFrame">
          <h2>📆 Asistencia</h2>
          <p>Registra tu asistencia diaria</p>
        </a>
        <a class="module" href="novedades_diarias.php" target="contentFrame">
          <h2>🍽️ Novedades diarias</h2>
          <p>Registro diario de novedades operativas.</p>
        </a>
        <a class="module" href="ciclo_comida.php" target="contentFrame">
          <h2>📊 Ciclo de Comida</h2>
          <p>Menú de comidas</p>
        </a>
        <a class="module" href="control_existencia.php" target="contentFrame">
          <h2>📦Control Existencia</h2>
          <p>Gestionar la existencia y cantidad de articulos</p>
        </a>
        <a class="module" href="historial_general.php" target="contentFrame">
          <h2>🕓Historia de planillas</h2>
          <p>Consulta planillas pasadas ya guardadas.</p>
        </a>

      <?php elseif ($rol === 'operador'): ?>
                <a class="module" href="asistencia_diaria.php" target="contentFrame">
          <h2>📆 Asistencia</h2>
          <p>Registra tu asistencia diaria</p>
        </a>
        <a class="module" href="novedades_diarias.php" target="contentFrame">
          <h2>🍽️ Novedades diarias</h2>
          <p>Registro diario de novedades operativas.</p>
        </a>
        <a class="module" href="ciclo_comida.php" target="contentFrame">
          <h2>📊 Ciclo de Comida</h2>
          <p>Menú de comidas</p>
        </a>

      <?php else: ?>
        <p>No tienes permisos para ver ningún módulo.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
