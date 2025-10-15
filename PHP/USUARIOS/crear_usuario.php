<?php
session_start();
$back = '../../VIEW/gestionar usuarios.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'superadmin') {
    header("Location: acceso_denegado.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    $_SESSION['flash_error'] = 'Error de conexión a la base de datos.';
    header("Location: $back");
    exit();
}

$errors = [];

$nombre   = trim($_POST['nombre']   ?? '');
$email    = trim($_POST['email']    ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$rol      = trim($_POST['rol']      ?? 'operador');
$estado   = trim($_POST['estado']   ?? 'Activo');
$password = $_POST['password']      ?? '';


if (empty($nombre)) {
    $errors[] = 'El nombre es obligatorio.';
}

if (empty($email)) {
    $errors[] = 'El correo electrónico es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El formato del correo electrónico no es válido.';
} else {
    $stmt_check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->num_rows > 0) {
        $errors[] = 'El correo electrónico ya está en uso.';
    }
    $stmt_check_email->close();
}

if (!empty($telefono)) {
    if (!ctype_digit($telefono)) {
        $errors[] = 'El teléfono solo puede contener números.';
    }
}

if (empty($password)) {
    $errors[] = 'La contraseña es obligatoria.';
} elseif (strlen($password) < 8) {
    $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
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
            $fotoPath = "PHP/USUARIOS/FOTOS/$nombreArchivo"; // Ruta relativa para la DB
        } else {
            $errors[] = 'Error al subir la foto.';
        }
    }
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode("<br>", $errors);
    header("Location: $back");
    exit();
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt_insert = $conn->prepare("
    INSERT INTO usuarios
    (nombre, email, telefono, rol, estado, password, foto)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt_insert->bind_param("sssssss", $nombre, $email, $telefono, $rol, $estado, $hash, $fotoPath);

if ($stmt_insert->execute()) {
    $_SESSION['flash_message'] = "Usuario creado exitosamente.";
} else {
    $_SESSION['flash_error'] = 'Error al crear el usuario: ' . $stmt_insert->error;
}

$stmt_insert->close();
$conn->close();

header("Location: $back");
exit();
?>