<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit();
}
include '../PHP/NOVEDADES/conexion.php';

$user_role = $_SESSION['user_rol'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Formulario Comité Escolar - Marzo 2025</title>
    <link rel="stylesheet" href="../ASSETS/CSS/informes_diarios.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>

    <div class="container">
        <h2>NOVEDADES DIARIAS - COMITÉ ESCOLAR</h2>
        <p class="descripcion">Registro de temas tratados, compromisos y resumen operativo en reuniones del comité escolar.</p>

        <form id="formulario" action="../PHP/NOVEDADES/insertar_registro.php" method="POST">
            <fieldset>
                <legend>Registro General</legend>

                <label for="concepto">Concepto:</label>
                <select id="concepto" name="tipo" required>
                    <option value="" disabled selected>-- Selecciona --</option>
                    <option value="tema">Temas Tratados</option>
                    <option value="compromiso">Compromisos Compensar</option>
                </select>

                <label for="registro">No. Registro:</label>
                <input type="number" id="registro" name="numero_registro" readonly required />

                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required readonly />

                <label for="texto">Descripción:</label>
                <textarea id="texto" name="descripcion" rows="4" required></textarea>

                <button type="submit">💾 Guardar Registro</button>
            </fieldset>
        </form>

        <h3>📄 Resumen Ejecutivo de la Operación</h3>
        <form id="form-resumen" action="../PHP/NOVEDADES/insertar_resumen.php" method="POST">
            <label for="res-fecha">Fecha:</label>
            <input type="date" id="res-fecha" name="fecha" required readonly />
            <label for="res-texto">Descripción:</label>
            <textarea id="res-texto" name="descripcion" rows="3" required></textarea>
            <button type="submit">➕ Agregar Resumen</button>
        </form>

        <h3>📝 Temas Tratados</h3>
        <table>
            <thead>
                <tr><th>No.</th><th>Fecha</th><th>Descripción</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conexion->prepare(
                    "SELECT numero_registro, fecha, descripcion
                     FROM registros_comite
                   WHERE tipo = 'tema'
                    ORDER BY fecha DESC, numero_registro ASC"
                );
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()):
                ?>
                    <tr>
                        <td data-label="No."><?= $row['numero_registro'] ?></td>
                        <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
                        <td data-label="Descripción"><?= nl2br(htmlspecialchars($row['descripcion'])) ?></td>
                    </tr>
                <?php endwhile;
                    $stmt->close();
                ?>
            </tbody>
        </table>

        <h3>📌 Compromisos Compensar</h3>
        <table>
            <thead>
                <tr><th>No.</th><th>Fecha</th><th>Descripción</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conexion->prepare(
                    "SELECT numero_registro, fecha, descripcion
                     FROM registros_comite
                     WHERE tipo = 'compromiso'
                    ORDER BY fecha DESC, numero_registro ASC"
                );
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()):
                ?>
                    <tr>
                        <td data-label="No."><?= $row['numero_registro'] ?></td>
                        <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
                        <td data-label="Descripción"><?= nl2br(htmlspecialchars($row['descripcion'])) ?></td>
                    </tr>
                <?php endwhile;
                    $stmt->close();
                ?>
            </tbody>
        </table>

        <h3>📄 Resumen Ejecutivo de la Operación</h3>
        <table>
            <thead>
                <tr><th>Fecha</th><th>Descripción</th></tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conexion->prepare(
                    "SELECT fecha, descripcion
                     FROM registros_comite
                     WHERE tipo = 'resumen'
                    ORDER BY fecha DESC"
                );
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()):
                ?>
                    <tr>
                        <td data-label="Fecha"><?= htmlspecialchars($row['fecha']) ?></td>
                        <td data-label="Descripción"><?= nl2br(htmlspecialchars($row['descripcion'])) ?></td>
                    </tr>
                <?php endwhile;
                    $stmt->close();
                    $conexion->close();
                ?>
            </tbody>
        </table>

        <?php
        if ($user_role === 'admin' || $user_role === 'superadmin') {
            echo '<form id="form-historial" action="../PHP/NOVEDADES/guardar_historial.php" method="POST">';
            echo '      <button type="submit" class="btn-historial">Guardar Planilla</button>';
            echo '</form>';
        }
        ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../ASSETS/JS/novedades_diarias.js"></script>
</body>
</html>