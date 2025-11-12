<?php
session_start();
include "config/config.php";

if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../../../lib/FPDF/font/');
}

require_once __DIR__ . '/../../../lib/FPDF/fpdf.php';
require_once __DIR__ . '/../../../lib/FPDI/src/autoload.php';
require_once __DIR__ . '/../../../lib/phpqrcode/qrlib.php'; 
require_once __DIR__ . '/../../../utils/helpers.php';

use setasign\Fpdi\Fpdi;

// --- Verificar que venga un ID ---
if (!isset($_GET["id"])) {
    die("ID de documento no proporcionado.");
}

$id = $_GET["id"];

// --- Consultar documento ---
$sql = "SELECT * FROM QRdocumentos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    die("Documento no encontrado.");
}

// --- Limpiar ceros decimales innecesarios ---
$doc["superficie_terreno"] = rtrim(rtrim($doc["superficie_terreno"], '0'), '.');
$doc["superficie_construccion"] = rtrim(rtrim($doc["superficie_construccion"], '0'), '.');
$doc["costo_certificacion"] = rtrim(rtrim($doc["costo_certificacion"], '0'), '.');
$doc["base_gravable"] = rtrim(rtrim($doc["base_gravable"], '0'), '.');

// --- Validar que no esté cancelado ---
if (isset($doc["estado_pdf"]) && $doc["estado_pdf"] === "cancelado") {
    die("Este documento ha sido cancelado y no es válido.");
}

// --- Nombre del archivo en validados ---
$filename = ucfirst($doc["tipo_documento"]) . "_" . $doc["id"] . ".pdf";
$savePath = __DIR__ . "/../../../public/validados/" . $filename;

//  Si ya existe → descargarlo directo
if (file_exists($savePath)) {
    // Forzar que no se use caché
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    readfile($savePath);
    exit;
}

// ======================================================
// Generar QR temporal que apunte otra vez a ver_pdf
// ======================================================
$urlQR = buildAbsoluteUrl("index.php", [
    "url" => "ver_pdf",
    "id"  => $doc["id"]
]);

ob_start();
QRcode::png($urlQR, null, QR_ECLEVEL_M, 6, 2);
$imageData = ob_get_contents();
ob_end_clean();

$tmpQR = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
file_put_contents($tmpQR, $imageData);

// ======================================================
// Plantilla
// ======================================================
$plantilla = __DIR__ . "/../../../plantillas/no_adeudo.pdf";
if ($doc["tipo_documento"] === "aportacion_mejoras") {
    $plantilla = __DIR__ . "/../../../plantillas/aportacion_mejoras.pdf";
}

// ======================================================
// Crear PDF con la plantilla
// ======================================================
$pdf = new Fpdi();
$pdf->AddPage();
$pdf->setSourceFile($plantilla);
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx, 0, 0, 210);

$pdf->SetFont("Arial", "", 12);
$pdf->SetTextColor(0, 0, 0);

// ======================================================
// Usar la fecha de captura de la BD (con verificación)
// ======================================================
$fechaGeneracion = null;
if (!empty($doc["fecha_captura"])) {
    try {
        $fechaGeneracion = new DateTime($doc["fecha_captura"]);
    } catch (Exception $e) {
        $fechaGeneracion = null;
    }
}

if ($fechaGeneracion) {
    $dia  = $fechaGeneracion->format("d");
    $anio = $fechaGeneracion->format("Y");
    $mesNum = $fechaGeneracion->format("m");
} else {
    // Valores por defecto si la fecha está vacía
    $dia = "--";
    $mesNum = "01";
    $anio = date("Y");
}

$meses = [
    "01" => "ENERO", "02" => "FEBRERO", "03" => "MARZO",
    "04" => "ABRIL", "05" => "MAYO", "06" => "JUNIO",
    "07" => "JULIO", "08" => "AGOSTO", "09" => "SEPTIEMBRE",
    "10" => "OCTUBRE", "11" => "NOVIEMBRE", "12" => "DICIEMBRE"
];
$mes = $meses[$mesNum] ?? "----";



// ======================================================
// BLOQUE → NO ADEUDO
// ======================================================
if ($doc["tipo_documento"] === "no_adeudo") {
    $pdf->SetFont("Arial", "", 12);
    $pdf->SetXY(40, 144);
    $pdf->Cell(120, 10, utf8_decode($doc["contribuyente"]), 0, 0);

    $pdf->SetFont("Arial", "B", 14); // Negritas
    $pdf->SetTextColor(255, 0, 0);   // Rojo
    $pdf->SetXY(165, 50.5); // <-- Ajusta coordenadas
    $pdf->Cell(40, 10, " " . $doc["folio_no_adeudo"], 0, 0);

    // Restablecer color y fuente para lo demás
    $pdf->SetFont("Arial", "", 12);
    $pdf->SetTextColor(0, 0, 0);
    // anio fiscal abajo
    $pdf->SetXY(150, 160.5);
    $pdf->Cell(30, 8, $doc["anio_fiscal"], 0, 0);
    // anio fiscal arriba
    $pdf->SetXY(40, 89);
    $pdf->Cell(30, 8, $doc["anio_fiscal"], 0, 0);

    $pdf->SetXY(65, 150);
    $pdf->Cell(60, 8, $doc["clave_catastral"], 0, 0);

    /// Dirección y Colonia en una misma línea con ajuste automático
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(18, 141.5); // coordenada inicial en tu plantilla

    $textoDireccionColonia = "" . utf8_decode($doc["direccion"]) . "" . utf8_decode($doc["colonia"]);

    $pdf->MultiCell(150, 6, $textoDireccionColonia, 0, 'L');

    $pdf->SetFont("Arial", "", 12);

    $pdf->SetXY(115, 135);
    $pdf->Cell(120, 8, utf8_decode($doc["tipo_predio"]), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(40, 165);
    $pdf->Cell(50, 8, $doc["linea_captura"], 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(95, 160.5);
    $pdf->Cell(40, 8, $doc["bimestre"], 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(18, 160.5);
    $pdf->Cell(40, 8, $doc["base_gravable"], 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(40, 170);
    $pdf->Cell(40, 8, utf8_decode($doc["superficie_terreno"] . " MTS"), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(150, 170);
    $pdf->Cell(40, 8, utf8_decode($doc["superficie_construccion"] . " MTS"), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, 180);
    $pdf->Cell(50, 8, utf8_decode("HAIX " .$doc["recibo_oficial"]), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(143, 180);
    $pdf->Cell(50, 8, $doc["costo_certificacion"], 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(40, 68.5);
    $pdf->Cell(60, 8, utf8_decode(SUBDIRECTOR), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(40, 74);
    $pdf->Cell(60, 8, utf8_decode($doc["cargo"]), 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $fechaSolo = date('d/m/Y', strtotime($doc["fecha_expedicion_pago"]));
    $pdf->SetXY(120, 165); 
    $pdf->Cell(60, 8, "" . $fechaSolo, 0, 0);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(67, 213);  // Día
    $pdf->Cell(15, 8, $dia, 0, 0);

    $pdf->SetFont("Arial", "", 8);
    $pdf->SetXY(117, 213);  // Mes
    $pdf->Cell(40, 8, $mes, 0, 0);

    $pdf->SetFont("Arial", "", 12);
    $pdf->SetXY(150, 213); // Año
    $pdf->Cell(20, 8, $anio, 0, 0);
}

// ======================================================
// BLOQUE → APORTACIÓN A MEJORAS
// ======================================================
if ($doc["tipo_documento"] === "aportacion_mejoras") {
    $pdf->SetFont("Arial", "", 12);
    $pdf->SetXY(45, 139.5);
    $pdf->Cell(120, 10, utf8_decode($doc["contribuyente"]), 0, 0);

     // FOLIO APORTACIÓN (en negro normal)
    $pdf->SetFont("Arial", "B", 14); // Negritas
    $pdf->SetTextColor(255, 0, 0);   // Rojo
    $pdf->SetXY(166, 50.5); // <-- Ajusta coordenadas
    $pdf->Cell(40, 10, " " . $doc["folio_aportacion"], 0, 0);

    // Restablecer fuente si lo necesitas
    $pdf->SetFont("Arial", "", 12);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(35, 89.5);
    $pdf->Cell(30, 8, $doc["anio_fiscal"], 0, 0);

    $pdf->SetXY(128, 135.5);
    $pdf->Cell(80, 8, $doc["clave_catastral"], 0, 0);

    /// Dirección y Colonia en una misma línea con ajuste automático
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(82, 131.5); // coordenada inicial en tu plantilla

    $textoDireccionColonia = "" . utf8_decode($doc["direccion"]) . "" . utf8_decode($doc["colonia"]);

    $pdf->MultiCell(150, 6, $textoDireccionColonia, 0, 'L');
    

    $pdf->SetFont("Arial", "", 12);
    $pdf->SetXY(25, 188.5);
    $pdf->Cell(50, 8, utf8_decode("HAIX " .$doc["recibo_oficial"]), 0, 0);

    $pdf->SetXY(25, 194);
    $pdf->Cell(40, 8, $doc["costo_certificacion"], 0, 0);

    $pdf->SetXY(35, 69.5);
    $pdf->Cell(60, 8, utf8_decode(SUBDIRECTOR), 0, 0);

    $pdf->SetXY(35, 74.5);
    $pdf->Cell(60, 8, utf8_decode($doc["cargo"]), 0, 0);

    $pdf->SetXY(68, 213);  // Día
    $pdf->Cell(15, 8, $dia, 0, 0);

    $pdf->SetFont("Arial", "", 8);
    $pdf->SetXY(119, 213.5);  // Mes
    $pdf->Cell(40, 8, $mes, 0, 0);

    $pdf->SetFont("Arial", "", 12);
    $pdf->SetXY(150, 213.5); // Año
    $pdf->Cell(20, 8, $anio, 0, 0);
}

// Insertar QR en el PDF
$pdf->Image($tmpQR, 170, 230, 28, 28);
unlink($tmpQR);

// Crear carpeta si no existe
if (!file_exists(dirname($savePath))) {
    mkdir(dirname($savePath), 0777, true);
}

// Guardar en carpeta validados
$pdf->Output("F", $savePath);

// Descargar directamente
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$pdf->Output("D", $filename);
exit;
