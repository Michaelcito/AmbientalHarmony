<?php
session_start();
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol']!=='superadmin') {
    header("Location: ../PHP/USUARIOS/acceso_denegado.php");
    exit();
}

if (!empty($_SESSION['flash_error'])) {
    echo '<div class="alert alert-danger" style="
            margin:10px 0;
            padding:10px;
            background:#f8d7da;
            color:#842029;
            border-radius:4px;
          ">'
        . htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES)
        . '</div>';
    unset($_SESSION['flash_error']);
}

$conn = new mysqli("localhost","root","","harmony");
if ($conn->connect_error) {
    die("Error de conexión: ".$conn->connect_error);
}

$editMode = false;
$usuarioEdit = [
    'id'=>'','nombre'=>'','email'=>'','telefono'=>'',
    'rol'=>'operador','estado'=>'Activo','foto'=>''
];

if (isset($_GET['editar_id']) && is_numeric($_GET['editar_id'])) {
    $id = intval($_GET['editar_id']);
    $stmt = $conn->prepare(
      "SELECT id,nombre,email,telefono,rol,estado,foto
        FROM usuarios WHERE id=?"
    );
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows===1) {
        $usuarioEdit = $res->fetch_assoc();
        $editMode = true;
    }
    $stmt->close();
}

$result = $conn->query(
  "SELECT id,nombre,email,telefono,rol,estado FROM usuarios"
);
$listaUsuarios = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <link rel="stylesheet" href="../ASSETS/CSS/gestionar_usuarios.css">
</head>
<body>
  <h1>Gestión de Usuarios</h1>

  <section>
    <h2 id="formTitle">
      <?= $editMode ? "Editar Usuario" : "Agregar Usuario" ?>
    </h2>
    <form id="userForm" method="POST" enctype="multipart/form-data"
          action="<?= $editMode
                            ? '../PHP/USUARIOS/actualizar_usuario.php'
                            : '../PHP/USUARIOS/crear_usuario.php'; ?>"
          novalidate>
      <?php if ($editMode): ?>
        <input type="hidden" name="id"
               value="<?= $usuarioEdit['id'] ?>">
      <?php endif; ?>

      <input type="text" name="nombre" placeholder="Nombre" required
             value="<?= htmlspecialchars($usuarioEdit['nombre'],ENT_QUOTES) ?>">

      <input type="email" name="email" placeholder="Correo" required
             value="<?= htmlspecialchars($usuarioEdit['email'],ENT_QUOTES) ?>">

      <input type="tel" name="telefono" placeholder="Teléfono"
             value="<?= htmlspecialchars($usuarioEdit['telefono'],ENT_QUOTES) ?>"
             pattern="[0-9]{7,15}" title="El teléfono debe contener solo números (7-15 dígitos).">
             <select name="rol" required>
        <?php if ($_SESSION['user_rol']==='superadmin'): ?>
          <option value="superadmin"
            <?= $usuarioEdit['rol']==='superadmin'?'selected':'' ?>>
            Super Admin
          </option>
        <?php endif; ?>
        <option value="admin"
          <?= $usuarioEdit['rol']==='admin'?'selected':'' ?>>
          Admin
        </option>
        <option value="operador"
          <?= $usuarioEdit['rol']==='operador'?'selected':'' ?>>
          Operador
        </option>
      </select>

      <select name="estado" required>
        <option value="Activo"
          <?= $usuarioEdit['estado']==='Activo'?'selected':'' ?>>
          Activo
        </option>
        <option value="Inactivo"
          <?= $usuarioEdit['estado']==='Inactivo'?'selected':'' ?>>
          Inactivo
        </option>
      </select>

      <input type="password" name="password"
             placeholder="<?= $editMode
                                ? 'Contraseña (dejar en blanco para no cambiar)'
                                : 'Contraseña' ?>"
             <?= $editMode?'':'required' ?>
             pattern=".{8,}"
             title="La contraseña debe tener al menos 8 caracteres.">

      <label for="userPhoto">Foto de perfil:</label>
      <input type="file" id="userPhoto" name="foto"
             accept="image/*"><br>
      <img id="previewPhoto"
                src="<?= $usuarioEdit['foto']
                            ? '../'.$usuarioEdit['foto']
                            : '' ?>"
                alt="Vista previa"
                style="max-width:150px;
                       display:<?= $usuarioEdit['foto']?'block':'none' ?>;
                       margin-top:10px;">

      <button type="submit" id="formButton">
        <?= $editMode ? 'Actualizar' : 'Crear' ?>
      </button>
    </form>
  </section>

  <section>
    <h2>Lista de Usuarios</h2>
    <input type="text" id="busquedaUsuario"
           placeholder="Buscar por email o teléfono..."
           style="margin-bottom:10px;width:100%;padding:8px;">

    <table>
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Email</th>
          <th>Teléfono</th><th>Rol</th><th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tablaUsuarios">
      <?php foreach($listaUsuarios as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['nombre'],ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($u['email'],ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($u['telefono'],ENT_QUOTES) ?></td>
          <td><?= $u['rol'] ?></td>
          <td><?= $u['estado'] ?></td>
          <td>
            <a class="btn-edit"
               href="gestionar usuarios.php?editar_id=<?= $u['id'] ?>">
              Editar
            </a>
            <form method="POST"
                  action="../PHP/USUARIOS/desactivar_usuario.php"
                  style="display:inline;"
                  onsubmit="return confirm(
                    '¿Deseas <?= $u['estado']==='Activo'?'desactivar':'reactivar' ?>
                      este usuario?'
                  );">
              <input type="hidden" name="id"
                     value="<?= $u['id'] ?>">
              <input type="hidden" name="estado_actual"
                     value="<?= $u['estado'] ?>">
              <button type="submit"
                      class="<?= $u['estado']==='Activo'
                                  ? 'btn-delete'
                                  : 'btn-reactivate' ?>">
                <?= $u['estado']==='Activo'
                    ? 'Desactivar'
                    : 'Reactivar' ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <script src="../ASSETS/JS/java gestionar usuarios.js"></script>
</body>
</html>