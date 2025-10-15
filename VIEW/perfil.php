<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: iniciar_sesion.php");
    exit();
}

require_once __DIR__ . '/../PHP/USUARIOS/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nombre, email, telefono, rol, estado, fecha_creacion, foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Usuario no encontrado.";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$foto_relativa = $user['foto'] ?? '';
$archivo_foto = __DIR__ . "/../" . $foto_relativa;

if (empty($foto_relativa) || !file_exists($archivo_foto)) {
    $foto_url = "../ASSETS/IMG/default.png";
} else {
    $foto_url = "../" . $foto_relativa;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../ASSETS/CSS/perfil.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <div class="container">
        <header>
            <h1>Mi Perfil</h1>
            <p>Si tus datos estan incorrectos, contacta con un superadmin:).</p>
        </header>

        <div class="perfil-card">
            <div class="foto-container">
                <img class="foto-perfil" src="<?= htmlspecialchars($foto_url) ?>?v=<?= time() ?>" alt="Foto de perfil" />
            </div>

            <h3 class="nombre-usuario">¡Hola, <?= htmlspecialchars($user['nombre']) ?>!</h3>

            <div class="detalle-perfil">
                <div class="detalle-item">
                    <i class="fas fa-envelope"></i>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-phone-alt"></i>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($user['telefono']) ?></p>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-user-tag"></i>
                    <p><strong>Rol:</strong> <?= htmlspecialchars($user['rol']) ?></p>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-clipboard-check"></i>
                    <p><strong>Estado:</strong> <?= htmlspecialchars($user['estado']) ?></p>
                </div>
                <div class="detalle-item">
                    <i class="fas fa-calendar-alt"></i>
                    <p><strong>Te uniste el:</strong> <?= date("Y-m-d", strtotime($user['fecha_creacion'])) ?></p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>