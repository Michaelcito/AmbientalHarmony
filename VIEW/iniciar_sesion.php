<?php
session_start();
$Error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "harmony");
    if ($conn->connect_error) {
        error_log("Error de conexión a la base de datos en iniciar sesion.php: " . $conn->connect_error);
        $Error = 'Ha ocurrido un error en el servidor. Por favor, inténtalo más tarde.';
    } else {
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $recordarme = isset($_POST['recordarme']);

        $email_regex = '/^[A-Za-z0-9][A-Za-z0-9._%+\-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i';

        if (!$email || !$password) {
            $Error = 'Por favor completa todos los campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $email)) {
            $Error = 'Correo inválido: no puede empezar con símbolos y debe tener formato válido.';
        } elseif (strlen($password) < 6) {
            $Error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $stmt = $conn->prepare("SELECT id, nombre, rol, foto, password, estado FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();

                if (password_verify($password, $usuario['password'])) {
                    if (strtolower($usuario['estado']) === 'activo') {
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
                            if ($insertStmt) {
                                $insertStmt->bind_param("issi", $usuario['id'], $selector, $hashedValidator, $expiryDate);
                                $insertStmt->execute();
                                $insertStmt->close();
                            } else {
                                error_log("Error al preparar la consulta de inserción de sesión persistente: " . $conn->error);
                            }

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

                        header("Location: interfaz de usuario.php");
                        exit;
                    } else {
                        $Error = 'Usuario inactivo. Contacta al administrador.';
                    }
                } else {
                    $Error = 'Contraseña incorrecta.';
                }
            } else {
                $Error = 'Usuario no existe.';
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../ASSETS/CSS/iniciar sesion.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../ASSETS/IMG/Logo.png">
</head>
<body>
    <header>
        <h1>AmbientalHarmony</h1>
        <nav>
            <a href="../PymesTech.html">Inicio</a>
            <a href="../PymesTech.html">Sobre Nosotros</a>
            <a href="../PymesTech.html">Contacto</a>
            <a href="../PymesTech.html">Servicios</a>
        </nav>
    </header>

    <div class="login-container">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Compensar_logo.svg/640px-Compensar_logo.svg.png" alt="Logo de Compensar" class="compensar-logo">

        <h3>¡Bienvenido de nuevo!</h3>

        <?php if (!empty($Error)): ?>
            <p id="login-error" class="error-message" style="color: red;">
                <?= htmlspecialchars($Error) ?>
            </p>
        <?php endif; ?>

        <form
            id="loginForm"
            method="POST"
            action=""
            novalidate
        >
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Correo electrónico"
                required
                pattern="^[A-Za-z0-9][A-Za-z0-9._%+\-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
                title="Introduzca un correo electrónico válido que no empiece por símbolos"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            />

            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                    pattern=".{6,}"
                    title="La contraseña debe tener al menos 6 caracteres"
                />
                <i class="fas fa-eye" id="togglePassword"></i>
            </div>
            <div class="remember-me-container">
                <input type="checkbox" id="recordarme" name="recordarme" <?= isset($_POST['recordarme']) ? 'checked' : '' ?>>
                <label for="recordarme">Recordarme</label>
            </div>

            <button type="submit" class="button">Continuar</button>
        </form>

        <p>¿Hay un error? <a href="reportes_login.html">¡Infórmenos!</a></p>
    </div>

    <script src="../ASSETS/JS/java iniciar sesion.js"></script>


</body>
</html>
