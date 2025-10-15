<?php
session_start();

$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    error_log("Error de conexión a la base de datos en logout: " . $conn->connect_error);}

$remember_me_cookie = $_COOKIE['remember_me'] ?? '';
$selector = '';
if (!empty($remember_me_cookie)) {
    list($selector, $_) = explode(':', $remember_me_cookie);
}

session_unset();
session_destroy();
if (isset($_COOKIE['remember_me'])) {
    setcookie(
        'remember_me',
        '', 
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => true, 
            'samesite' => 'Lax'
        ]
    );
}

if (!empty($selector) && $conn->ping()) {
    $stmt = $conn->prepare("DELETE FROM sesiones_persistentes WHERE selector = ?");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $stmt->close();
}

if ($conn->ping()) {
    $conn->close();
}

header("Location: ../../VIEW/iniciar_sesion.php");
exit;
?>