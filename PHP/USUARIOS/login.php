<?php
session_start();

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    header("Location: error_servidor.php");
    exit;
}

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$recordarme = isset($_POST['recordarme']); 

if (!$email || !$password) {
    header("Location: ../ASSETS/VIEW/iniciar_sesion.php?error=campos_vacios");
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre, rol, foto, password, estado FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../ASSETS/VIEW/iniciar_sesion.php?error=usuario_no_existe");
    exit;
}

$usuario = $result->fetch_assoc();

if (!password_verify($password, $usuario['password'])) {
    header("Location: ../ASSETS/VIEW/iniciar_sesion.php?error=contraseña_incorrecta");
    exit;
}

if (strtolower($usuario['estado']) !== 'activo') {
    header("Location: ../ASSETS/VIEW/iniciar_sesion.php?error=usuario_inactivo");
    exit;
}

$_SESSION['user_id']    = $usuario['id'];
$_SESSION['user_name']  = $usuario['nombre'];
$_SESSION['user_rol']   = $usuario['rol'];
$foto_bd                = $usuario['foto'] ?? '';
$_SESSION['user_foto']  = !empty($foto_bd) ? basename($foto_bd) : '';

if ($recordarme) {
    $selector = bin2hex(random_bytes(8));
    $validator = bin2hex(random_bytes(32)); 
    $hashedValidator = hash('sha256', $validator); 

    $expiryDate = time() + (86400 * 30);

    $insertStmt = $conn->prepare("INSERT INTO sesiones_persistentes (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, FROM_UNIXTIME(?))");
    $insertStmt->bind_param("isss", $usuario['id'], $selector, $hashedValidator, $expiryDate);
    $insertStmt->execute();

    setcookie(
        'remember_me',
        $selector . ':' . $validator,
        [
            'expires' => $expiryDate,
            'path' => '/',
            'httponly' => true,
            'secure' => true,
            'samesite' => 'Lax'
        ]
    );
}

$conn->close();
header("Location: ../../VIEW/interfaz de usuario.php");
exit;
?>