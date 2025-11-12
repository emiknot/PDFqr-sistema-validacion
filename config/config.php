<?php
// Configuración de la BD
$host = "localhost";
$user = "root"; // cambia si tu MySQL tiene otro usuario
$pass = "";     // cambia si tu MySQL tiene contraseña
$db   = "val_doc"; // nombre de tu BD en phpMyAdmin

// Conexión
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Nombre del subdirector (puedes cambiarlo en el futuro)
define("SUBDIRECTOR", "JOSÉ MANUEL LÓPEZ CORTES");

?>
