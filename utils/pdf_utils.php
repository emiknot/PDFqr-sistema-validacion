<?php
session_start();
include "config/config.php";

//  Definir la ruta de fuentes para FPDF si no está definida
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/libreria/FPDF/font/');
}

//  Incluir librerías
require_once __DIR__ . "/libreria/FPDF/fpdf.php";
require_once __DIR__ . "/libreria/FPDI/src/autoload.php";

use setasign\Fpdi\Fpdi;

// Verificar login
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    die("Falta el ID del documento.");
}

$id = $_GET["id"];

// Obtener datos de la BD
$sql = "SELECT * FROM QRdocumentos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    die("Documento no encontrado.");
}

// Crear PDF usando la plantilla
$pdf = new Fpdi();
$pdf->AddPage();
$pdf->setSourceFile(__DIR__ . "/plantillas/no_adeudo.pdf"); // tu plantilla
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx, 0, 0, 210); // tamaño A4

// Configurar fuente
$pdf->SetFont("Arial", "", 12);
$pdf->SetTextColor(0, 0, 0);

// === Colocar datos en coordenadas (ejemplo) ===
$pdf->SetXY(50, 80);
$pdf->Cell(100, 10, utf8_decode($doc["contribuyente"]), 0, 0);

$pdf->SetXY(50, 95);
$pdf->Cell(100, 10, $doc["clave_catastral"], 0, 0);

$pdf->SetXY(50, 110);
$pdf->Cell(100, 10, $doc["folio_no_adeudo"], 0, 0);

$pdf->SetXY(50, 125);
$pdf->Cell(100, 10, $doc["fecha_captura"], 0, 0);

// Salida del PDF
$pdf->Output("I", "NoAdeudo_" . $doc["id"] . ".pdf");
