<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . "/../../../config/config.php";

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

// =======================
// Filtros iguales a DocumentosController
// =======================
$condiciones = [];
$params = [];
$tipos = "";

if (!empty($_GET["folio_no_adeudo"])) {
    $condiciones[] = "folio_no_adeudo LIKE ?";
    $params[] = "%".$_GET["folio_no_adeudo"]."%";
    $tipos .= "s";
}
if (!empty($_GET["folio_aportacion"])) {
    $condiciones[] = "folio_aportacion LIKE ?";
    $params[] = "%".$_GET["folio_aportacion"]."%";
    $tipos .= "s";
}
if (!empty($_GET["clave_catastral"])) {
    $condiciones[] = "clave_catastral LIKE ?";
    $params[] = "%".$_GET["clave_catastral"]."%";
    $tipos .= "s";
}
if (!empty($_GET["contribuyente"])) {
    $condiciones[] = "contribuyente LIKE ?";
    $params[] = "%".$_GET["contribuyente"]."%";
    $tipos .= "s";
}

// =======================
// Query con filtros
// =======================
$sqlDocs = "SELECT id, fecha_captura, fecha_expedicion_pago, contribuyente, folio_no_adeudo, folio_aportacion, clave_catastral, tipo_documento 
            FROM QRdocumentos 
            WHERE id_capturista = ?";
$tipos = "i".$tipos;
array_unshift($params, $id_capturista);

if (!empty($condiciones)) {
    $sqlDocs .= " AND " . implode(" AND ", $condiciones);
}

$sqlDocs .= " ORDER BY fecha_captura DESC";

$stmtDocs = $conn->prepare($sqlDocs);
$stmtDocs->bind_param($tipos, ...$params);
$stmtDocs->execute();
$docs = $stmtDocs->get_result();

// =======================
// Exportar CSV
// =======================
$filename = "documentos_exportados_" . date("Y-m-d_H-i-s") . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

// Encabezados
fputcsv($output, ['ID', 'Fecha', 'Fecha Expedición Pago', 'Contribuyente', 'Folio No adeudo', 'Folio Aportación', 'Clave catastral', 'Tipo documento']);

// Datos
while ($row = $docs->fetch_assoc()) {
    $fecha_captura = !empty($row['fecha_captura']) ? date("d/m/Y", strtotime($row['fecha_captura'])) : "";
    $fecha_expedicion_pago = !empty($row['fecha_expedicion_pago']) ? date("d/m/Y", strtotime($row['fecha_expedicion_pago'])) : "";

    fputcsv($output, [
        $row['id'],
        $fecha_captura,
        $fecha_expedicion_pago,
        $row['contribuyente'],
        $row['folio_no_adeudo'],
        $row['folio_aportacion'],
        $row['clave_catastral'],
        $row['tipo_documento']
    ]);
}



fclose($output);
exit;
