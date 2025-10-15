<?php
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("🚫 Error en la conexión a MySQL: " . $conn->connect_error);
}
echo "✅ Conexión a MySQL exitosa. BD: <strong>harmony</strong>";
$conn->close();
?>