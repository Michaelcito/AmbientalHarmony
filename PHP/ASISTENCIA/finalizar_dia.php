<?php
$conn = new mysqli("localhost", "root", "", "harmony");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$copiar = "
    INSERT INTO historial_asistencia (usuario_id, nombre_completo, cedula, fecha, hora_entrada, hora_salida, observacion)
    SELECT usuario_id, nombre_completo, cedula, fecha, hora_entrada, hora_salida, observacion
    FROM asistencia_diaria
";

if ($conn->query($copiar) === TRUE) {
    $vaciar = "DELETE FROM asistencia_diaria";
    $conn->query($vaciar);
    echo "<script>alert('Asistencia del día finalizada y archivada correctamente.'); window.location.href='../../VIEW/asistencia_diaria.php';</script>";
} else {
    echo "Error al finalizar el día: " . $conn->error;
}

$conn->close();
