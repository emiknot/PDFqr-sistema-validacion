<?php
session_start();
include "config/config.php";

if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../../../lib/fpdf/font/');
}

require_once __DIR__ . '/../../../lib/fpdf/fpdf.php';
require_once __DIR__ . '/../../../lib/fpdi/src/autoload.php';
require_once __DIR__ . '/../../../lib/phpqrcode/qrlib.php';
require_once __DIR__ . '/../../../utils/helpers.php';

use setasign\Fpdi\Fpdi;

// --- Validar parámetros ---
if (!isset($_GET["id1"]) || !isset($_GET["id2"])) {
    die("Faltan parámetros id1 e id2.");
}

$ids = [$_GET["id1"], $_GET["id2"]];

// ======================================================
// Función para generar cada documento en el PDF combinado
// ======================================================
function renderDocumento($pdf, $doc, $conn) {

    // --- Limpiar ceros decimales innecesarios ---

    $doc["superficie_terreno"] = rtrim(rtrim($doc["superficie_terreno"], '0'), '.');
    $doc["superficie_construccion"] = rtrim(rtrim($doc["superficie_construccion"], '0'), '.');
    $doc["costo_certificacion"] = rtrim(rtrim($doc["costo_certificacion"], '0'), '.');
    $doc["base_gravable"] = rtrim(rtrim($doc["base_gravable"], '0'), '.');

    // Generar QR
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

    // Plantilla
    $plantilla = __DIR__ . "/../../../plantillas/no_adeudo.pdf";
    if ($doc["tipo_documento"] === "aportacion_mejoras") {
        $plantilla = __DIR__ . "/../../../plantillas/aportacion_mejoras.pdf";
    }

    // Crear página
    $pdf->AddPage();
    $pdf->setSourceFile($plantilla);
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx, 0, 0, 210);

    $pdf->SetFont("Arial", "", 12);
    $pdf->SetTextColor(0, 0, 0);

    // Fecha actual (generación)
    $fechaGeneracion = new DateTime($doc["fecha_captura"]);
    $dia  = $fechaGeneracion->format("d");
    $anio = $fechaGeneracion->format("Y");

    $meses = [
        "01" => "ENERO", "02" => "FEBRERO", "03" => "MARZO",
        "04" => "ABRIL", "05" => "MAYO", "06" => "JUNIO",
        "07" => "JULIO", "08" => "AGOSTO", "09" => "SEPTIEMBRE",
        "10" => "OCTUBRE", "11" => "NOVIEMBRE", "12" => "DICIEMBRE"
    ];
    $mes = $meses[$fechaGeneracion->format("m")];

    // =======================
    // BLOQUE: NO ADEUDO
    // =======================
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

    // =======================
    // BLOQUE: APORTACIÓN MEJORAS
    // =======================
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

    // QR
    $pdf->Image($tmpQR, 170, 225, 28, 28);
    unlink($tmpQR);
}

// ======================================================
// Generar PDF final con ambos documentos
// ======================================================
$pdf = new Fpdi();

foreach ($ids as $id) {
    $sql = "SELECT * FROM QRdocumentos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();

    if ($doc && (!isset($doc["estado_pdf"]) || $doc["estado_pdf"] !== "cancelado")) {
        renderDocumento($pdf, $doc, $conn);
    }
}

// ======================================================
// Salida
// ======================================================
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=Ambos_Documentos.pdf");
$pdf->Output("I", "Ambos_Documentos.pdf");
exit;
