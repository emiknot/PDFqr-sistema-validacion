<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once dirname(__DIR__, 2) . "/config/config.php";

// Verificar ID
if (!isset($_GET['id'])) {
    die("Falta el ID del documento.");
}
$id = $_GET['id'];

// --- Paso 1: consultar datos del documento ---
$sqlDoc = "SELECT id, tipo_documento FROM QRdocumentos WHERE id = ?";
$stmtDoc = $conn->prepare($sqlDoc);
$stmtDoc->bind_param("s", $id);
$stmtDoc->execute();
$result = $stmtDoc->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    die("Documento no encontrado.");
}

// --- Paso 2: construir ruta del PDF ---
$carpetaValidados = dirname(__DIR__, 2) . "/public/validados/";

//  mismo nombre estándar que usamos en ver_pdf y recuperar
$filename = ucfirst($doc["tipo_documento"]) . "_" . $doc["id"] . ".pdf";
$rutaPDF = $carpetaValidados . $filename;

// --- Paso 3: eliminar archivo físico ---
if (file_exists($rutaPDF)) {
    unlink($rutaPDF);
}

// --- Paso 4: actualizar estado en BD ---
$sqlUpdate = "UPDATE QRdocumentos SET estado_pdf = 'cancelado' WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("s", $id);
$stmtUpdate->execute();

// --- Redirigir de nuevo a documentos ---
header("Location: index.php?url=documentos");
exit;
