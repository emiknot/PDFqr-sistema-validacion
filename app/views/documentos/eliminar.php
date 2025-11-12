<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once dirname(__DIR__, 3) . "/config/config.php"; // subir 3 niveles a /config/

// Verificar usuario
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php?url=login");
    exit;
}

$usuario = $_SESSION["usuario"];
$sqlUser = "SELECT id FROM QRusuarios WHERE usuario = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$id_capturista = ($row = $resultUser->fetch_assoc()) ? $row["id"] : 0;

if (isset($_GET["id"])) {
    $id_doc = $_GET["id"];

    $sqlCheck = "SELECT id FROM QRdocumentos WHERE id = ? AND id_capturista = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("si", $id_doc, $id_capturista);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        $sqlDel = "DELETE FROM QRdocumentos WHERE id = ? AND id_capturista = ?";
        $stmtDel = $conn->prepare($sqlDel);
        $stmtDel->bind_param("si", $id_doc, $id_capturista);

        if ($stmtDel->execute()) {
            $_SESSION["msg"] = "Documento eliminado correctamente.";
        } else {
            $_SESSION["error"] = "Error al eliminar el documento: " . $conn->error;
        }
    } else {
        $_SESSION["error"] = "No se encontró el documento o no tienes permisos.";
    }
}

// Redirigir al listado
header("Location: index.php?url=documentos");
exit;
