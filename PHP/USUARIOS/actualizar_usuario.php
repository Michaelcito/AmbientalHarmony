<?php
session_start();
$back = '../../VIEW/gestionar usuarios.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'superadmin') {
    header("Location: ../../VIEW/acceso_denegado.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli("localhost", "root", "", "harmony");
    if ($conn->connect_error) {
        $_SESSION['flash_error'] = 'Error de conexión a la base de datos.';
        header("Location: $back");
        exit();
    }

    $errors = [];

    $id       = intval($_POST['id'] ?? 0);
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = trim($_POST['rol'] ?? 'operador');
    $estado   = trim($_POST['estado'] ?? 'Activo');
    $password = $_POST['password'] ?? '';

    if ($id <= 0) {
        $errors[] = 'ID de usuario inválido.';
    } else {
        $stmt_check_id = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $stmt_check_id->bind_param("i", $id);
        $stmt_check_id->execute();
        $stmt_check_id->bind_result($count);
        $stmt_check_id->fetch();
        $stmt_check_id->close();
        if ($count === 0) {
            $errors[] = 'El usuario a editar no existe.';
        }
    }

    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido.';
    } else {
        $stmt_check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $email, $id);
        $stmt_check_email->execute();
        if ($stmt_check_email->get_result()->num_rows > 0) {
            $errors[] = 'El correo electrónico ya está en uso por otro usuario.';
        }
        $stmt_check_email->close();
    }

    if (!empty($telefono)) {
        if (!ctype_digit($telefono)) {
            $errors[] = 'El teléfono solo puede contener números.';
        }
    }

    $hash = null;
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
        }
    }

    $allowed_roles = ['admin', 'operador'];
    if ($_SESSION['user_rol'] === 'superadmin') {
        $allowed_roles[] = 'superadmin';
    }
    if (!in_array($rol, $allowed_roles)) {
        $errors[] = 'El rol seleccionado no es válido.';
    }

    if (!in_array($estado, ['Activo', 'Inactivo'])) {
        $errors[] = 'El estado seleccionado no es válido.';
    }

    $fotoPath = '';
    $oldFoto = '';

    if ($id > 0 && empty($errors)) {
        $stmt_get_old_foto = $conn->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmt_get_old_foto->bind_param("i", $id);
        $stmt_get_old_foto->execute();
        $stmt_get_old_foto->bind_result($oldFoto);
        $stmt_get_old_foto->fetch();
        $stmt_get_old_foto->close();
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed_exts)) {
            $errors[] = 'Tipo de archivo no permitido para la foto. Se aceptan JPG, JPEG, PNG, GIF.';
        }

        if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'La foto es demasiado grande. El tamaño máximo es de 5MB.';
        }

        if (empty($errors)) {
            $dir = __DIR__ . "/FOTOS/";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $nombreArchivo = 'user_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $rutaCompleta  = $dir . $nombreArchivo;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaCompleta)) {
                $fotoPath = "PHP/USUARIOS/FOTOS/$nombreArchivo";

                if (!empty($oldFoto) && file_exists("../../$oldFoto")) {
                    unlink("../../$oldFoto");
                }
            } else {
                $errors[] = 'Error al subir la nueva foto.';
            }
        }
    } else {
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            $fotoPath = $oldFoto;
        } else if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error inesperado al subir el archivo: Código ' . $_FILES['foto']['error'];
        } else {
            if (empty($oldFoto) && (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE)) {
                $fotoPath = '';
            } else {
                $fotoPath = $oldFoto;
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode("<br>", $errors);
        header("Location: $back");
        exit();
    }

    $sql_parts = [];
    $params = [];
    $types = "";

    $sql_parts[] = "nombre = ?"; $params[] = $nombre; $types .= "s";
    $sql_parts[] = "email = ?"; $params[] = $email; $types .= "s";
    $sql_parts[] = "telefono = ?"; $params[] = $telefono; $types .= "s";
    $sql_parts[] = "rol = ?"; $params[] = $rol; $types .= "s";
    $sql_parts[] = "estado = ?"; $params[] = $estado; $types .= "s";

    if ($hash !== null) {
        $sql_parts[] = "password = ?"; $params[] = $hash; $types .= "s";
    }

    if ($fotoPath !== $oldFoto) {
        $sql_parts[] = "foto = ?"; $params[] = $fotoPath; $types .= "s";
    }

    $sql = "UPDATE usuarios SET " . implode(", ", $sql_parts) . " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Usuario actualizado exitosamente.";
    } else {
        $_SESSION['flash_error'] = 'Error al actualizar el usuario: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: $back");
    exit();
}
?>
