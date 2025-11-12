<?php
include 'conexion.php';
require 'fpdf/fpdf.php'; // Asegúrate de incluir la biblioteca FPDF

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    // Consulta para obtener los datos del registro
    $sql = "SELECT * FROM solicitudes_nueva WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Crear el PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Título
        $pdf->Cell(0, 10, utf8_decode('Orden de Servicio'), 0, 1, 'C');
        $pdf->Ln(10);

        // Datos del registro
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, utf8_decode('Folio: ' . $row['folio']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Nombre Completo: ' . $row['nombre_completo']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Teléfono: ' . $row['telefono']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Email: ' . $row['email']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Calle: ' . $row['calle']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Número: ' . $row['numero']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Colonia: ' . $row['colonia']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Coordenadas: ' . $row['coordenadas']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Tipo de Servicio: ' . $row['tipo_servicio']), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Fecha Ejecución: ' . $row['fecha_ejecucion']), 0, 1);

        // Salida del PDF
        $pdf->Output('D', 'Orden_de_Servicio_' . $row['folio'] . '.pdf'); // Descargar el PDF
    } else {
        echo "No se encontró el registro.";
    }

    $stmt->close();
    $conn->close();
}
?>