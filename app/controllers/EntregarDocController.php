<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once dirname(__DIR__, 2) . "/config/config.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: index.php?url=login");
    exit;
}

// Solo aceptar POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?url=documentos");
    exit;
}

if (empty($_POST["id"])) {
    $_SESSION["msg_error"] = "ID inválido (no recibido).";
    header("Location: index.php?url=documentos");
    exit;
}

$id = trim($_POST["id"]);

// Ejecutar actualización
$sql = "UPDATE QRdocumentos SET estado_entrega = 'entregado' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    $_SESSION["msg_success"] = "Documento marcado como ENTREGADO correctamente.";
} else {
    $_SESSION["msg_error"] = "Error al actualizar el estado de entrega: " . $conn->error;
}

header("Location: index.php?url=documentos");
exit;
