<?php
/**

 * 
 *
 * @param mysqli
 * @return bool 
 */
function verificarSesionPersistente($conn) {
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (isset($_COOKIE['remember_me'])) {
        list($selector, $validator) = explode(':', $_COOKIE['remember_me'] ?? '');

        if (empty($selector) || empty($validator)) {
            setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'secure' => true, 'samesite' => 'Lax']);
            return false;
        }

        $stmt = $conn->prepare("SELECT sp.id, sp.user_id, sp.hashed_validator, sp.expires_at,
                                       u.id as user_id_db, u.nombre, u.rol, u.foto
                                FROM sesiones_persistentes sp
                                JOIN usuarios u ON sp.user_id = u.id
                                WHERE sp.selector = ? AND sp.expires_at > NOW()");
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            if (hash_equals($row['hashed_validator'], hash('sha256', $validator))) {
                $_SESSION['user_id']    = $row['user_id_db'];
                $_SESSION['user_name']  = $row['nombre'];
                $_SESSION['user_rol']   = $row['rol'];
                $_SESSION['user_foto']  = !empty($row['foto']) ? basename($row['foto']) : '';


                $newSelector = bin2hex(random_bytes(8));
                $newValidator = bin2hex(random_bytes(32));
                $newHashedValidator = hash('sha256', $newValidator);
                $newExpiryDate = time() + (86400 * 30);

                $updateStmt = $conn->prepare("UPDATE sesiones_persistentes SET selector = ?, hashed_validator = ?, expires_at = FROM_UNIXTIME(?) WHERE id = ?");
                $updateStmt->bind_param("ssii", $newSelector, $newHashedValidator, $newExpiryDate, $row['id']);
                $updateStmt->execute();

                setcookie(
                    'remember_me',
                    $newSelector . ':' . $newValidator,
                    [
                        'expires' => $newExpiryDate,
                        'path' => '/',
                        'httponly' => true,
                        'secure' => true,
                        'samesite' => 'Lax'
                    ]
                );

                return true;
            }
        }

        setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'secure' => true, 'samesite' => 'Lax']);
    }
    return false;
}
?>