<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once dirname(__DIR__, 3) . "/config/config.php";
include_once dirname(__DIR__, 3) . "/utils/helpers.php";

// ==========================
//  Verificar usuario
// ==========================
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php?url=login");
    exit;
}

$usuario = $_SESSION["usuario"];

// ==========================
//  Obtener id_capturista
// ==========================
$sqlUser = "SELECT id FROM QRusuarios WHERE usuario = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$id_capturista = ($row = $resultUser->fetch_assoc()) ? $row["id"] : 0;

// ==========================
//  Validar ID de documento
// ==========================
if (!isset($_GET["id"])) {
    die("ID de documento no proporcionado.");
}
$id_doc = $_GET["id"];

// ==========================
//  Obtener datos del documento
// ==========================


$tiene_permiso_global = tienePermisoGlobal($usuario);

if ($tiene_permiso_global) {
    // El admin o usuarios con permiso global pueden editar cualquier documento
    $sql = "SELECT * FROM QRdocumentos WHERE id = ?"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_doc);
} else {
    // Solo puede editar documentos propios
    $sql = "SELECT * FROM QRdocumentos WHERE id = ? AND id_capturista = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $id_doc, $id_capturista);
}

$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    die("Documento no encontrado o no tienes permisos para editarlo.");
}


// ==========================
//  Procesar actualización
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["actualizar_doc"])) {

    // --- Fechas ---
    $fecha_captura = !empty($_POST["fecha_captura"])
        ? date("Y-m-d", strtotime($_POST["fecha_captura"]))
        : $doc["fecha_captura"];

    $fecha_expedicion_pago = !empty($_POST["fecha_expedicion_pago"])
        ? $_POST["fecha_expedicion_pago"]
        : $doc["fecha_expedicion_pago"];

    // --- Campos generales ---
    $anio_fiscal = $_POST["anio_fiscal"] ?? $doc["anio_fiscal"];
    $tipo_documento = isset($_POST["tipo_documento"]) && in_array($_POST["tipo_documento"], ["no_adeudo", "aportacion_mejoras"])
        ? $_POST["tipo_documento"]
        : $doc["tipo_documento"];

    $folio_no_adeudo = $_POST["folio_no_adeudo"] ?? $doc["folio_no_adeudo"];
    $folio_aportacion = $_POST["folio_aportacion"] ?? $doc["folio_aportacion"];
    $linea_captura = $_POST["linea_captura"] ?? $doc["linea_captura"];
    $tipo_predio = $_POST["tipo_predio"] ?? $doc["tipo_predio"];

    // --- Colonia y dirección ---
    $colonia = $_POST["colonia"] ?? $doc["colonia"];
    $colonia_otro = $_POST["colonia_otro"] ?? null;
    if ($colonia === "Otro" && !empty($colonia_otro)) {
        $colonia = $colonia_otro;
    }

    $direccion = $_POST["direccion"] ?? $doc["direccion"];

    // --- Datos del predio ---
    $clave_catastral = $_POST["clave_catastral"] ?? $doc["clave_catastral"];
    $base_gravable = $_POST["base_gravable"] ?? $doc["base_gravable"];
    $bimestre = $_POST["bimestre"] ?? $doc["bimestre"];
    $superficie_terreno = trim($_POST["superficie_terreno"] ?? $doc["superficie_terreno"]);
    $superficie_construccion = trim($_POST["superficie_construccion"] ?? $doc["superficie_construccion"]);

    // --- Datos del contribuyente ---
    $contribuyente = $_POST["contribuyente"] ?? $doc["contribuyente"];
    $subdirector = $_POST["subdirector"] ?? $doc["subdirector"];
    $cargo = $_POST["cargo"] ?? $doc["cargo"];

    // --- Recibos y costos ---
    $recibo_oficial = $_POST["recibo_oficial"] ?? $doc["recibo_oficial"];
    $recibo_mejoras = $_POST["recibo_mejoras"] ?? $doc["recibo_mejoras"];
    $costo_certificacion = trim($_POST["costo_certificacion"] ?? $doc["costo_certificacion"]);

// ==========================
// Actualizar en BD
// ==========================
if ($tiene_permiso_global) {
    // --- Admin o usuario con permisos globales ---
    $sqlUpdate = "UPDATE QRdocumentos 
                  SET fecha_captura=?, fecha_expedicion_pago=?, anio_fiscal=?, tipo_documento=?, 
                      folio_no_adeudo=?, folio_aportacion=?, linea_captura=?, 
                      tipo_predio=?, colonia=?, direccion=?, clave_catastral=?, 
                      base_gravable=?, bimestre=?, 
                      superficie_terreno=?, superficie_construccion=?, 
                      contribuyente=?, subdirector=?, cargo=?, 
                      recibo_oficial=?, recibo_mejoras=?, costo_certificacion=? 
                  WHERE id=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param(
        "ssissssssssdiddsssssds",
        $fecha_captura, $fecha_expedicion_pago, $anio_fiscal, $tipo_documento,
        $folio_no_adeudo, $folio_aportacion, $linea_captura,
        $tipo_predio, $colonia, $direccion, $clave_catastral,
        $base_gravable, $bimestre, $superficie_terreno, $superficie_construccion,
        $contribuyente, $subdirector, $cargo,
        $recibo_oficial, $recibo_mejoras, $costo_certificacion,
        $id_doc
    );
} else {
    // --- Usuarios normales: solo pueden actualizar sus propios documentos ---
    $sqlUpdate = "UPDATE QRdocumentos 
                  SET fecha_captura=?, fecha_expedicion_pago=?, anio_fiscal=?, tipo_documento=?, 
                      folio_no_adeudo=?, folio_aportacion=?, linea_captura=?, 
                      tipo_predio=?, colonia=?, direccion=?, clave_catastral=?, 
                      base_gravable=?, bimestre=?, 
                      superficie_terreno=?, superficie_construccion=?, 
                      contribuyente=?, subdirector=?, cargo=?, 
                      recibo_oficial=?, recibo_mejoras=?, costo_certificacion=? 
                  WHERE id=? AND id_capturista=?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param(
        "ssissssssssdiddsssssdsi",
        $fecha_captura, $fecha_expedicion_pago, $anio_fiscal, $tipo_documento,
        $folio_no_adeudo, $folio_aportacion, $linea_captura,
        $tipo_predio, $colonia, $direccion, $clave_catastral,
        $base_gravable, $bimestre, $superficie_terreno, $superficie_construccion,
        $contribuyente, $subdirector, $cargo,
        $recibo_oficial, $recibo_mejoras, $costo_certificacion,
        $id_doc, $id_capturista
    );
}

    if ($stmtUpdate->execute()) {
        // Eliminar PDF viejo
        $filename = ucfirst($tipo_documento) . "_" . $id_doc . ".pdf";
        $savePath = dirname(__DIR__, 3) . "/public/validados/" . $filename;
        if (file_exists($savePath)) {
            unlink($savePath);
        }

        $_SESSION["msg"] = "Documento actualizado correctamente.";
        header("Location: index.php?url=documentos");
        exit;
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<?php include dirname(__DIR__) . "/layout/header.php"; ?>

<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="card p-4 mb-4">
      <h4 class="text-center">Editar Documento</h4>

      <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php } ?>

      <form method="post" class="row g-3">
        <!-- Datos del documento -->
        <h5 class="mt-3">Datos del documento</h5>
        <div class="col-md-4">
          <label class="form-label">Fecha de CAPTURA</label>
          <input type="date" name="fecha_captura"
                 value="<?php echo htmlspecialchars($doc['fecha_captura']); ?>"
                 class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Año fiscal</label>
          <input type="number" name="anio_fiscal" value="<?php echo $doc['anio_fiscal']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Tipo de documento</label>
          <select name="tipo_documento" class="form-select">
            <option value="no_adeudo" <?php if($doc['tipo_documento']=='no_adeudo') echo 'selected'; ?>>NO ADEUDO PREDIAL</option>
            <option value="aportacion_mejoras" <?php if($doc['tipo_documento']=='aportacion_mejoras') echo 'selected'; ?>>APORTACIÓN A MEJORAS</option>
          </select>
        </div>

        <!-- Identificación y folios -->
        <h5 class="mt-3">Identificación y folios</h5>
        <div class="col-md-4">
          <label class="form-label">Folio No adeudo predial</label>
          <input type="text" name="folio_no_adeudo" value="<?php echo $doc['folio_no_adeudo']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Folio Aportación</label>
          <input type="text" name="folio_aportacion" value="<?php echo $doc['folio_aportacion']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Línea de captura</label>
          <input type="text" name="linea_captura" value="<?php echo $doc['linea_captura']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label for="fecha_expedicion_pago" class="form-label">Fecha de EXPEDICIÓN del Pago</label>
          <input type="date" class="form-control" name="fecha_expedicion_pago" id="fecha_expedicion_pago"
                 value="<?php echo htmlspecialchars($doc['fecha_expedicion_pago']); ?>">
        </div>

        <!-- Datos del predio -->
        <h5 class="mt-3">Datos del predio</h5>
        <div class="col-md-4">
          <label class="form-label">Tipo de predio</label>
          <select name="tipo_predio" class="form-select">
            <option value="CONSTRUIDO" <?php if($doc['tipo_predio']=='CONSTRUIDO') echo 'selected'; ?>>CONSTRUIDO</option>
            <option value="SIN CONSTRUIR" <?php if($doc['tipo_predio']=='SIN CONSTRUIR') echo 'selected'; ?>>SIN CONSTRUIR</option>
            <option value="BALDÍO" <?php if($doc['tipo_predio']=='BALDÍO') echo 'selected'; ?>>BALDÍO</option>
            <option value="OTRO" <?php if($doc['tipo_predio']=='OTRO') echo 'selected'; ?>>OTRO</option>
          </select>
        </div>

                <!-- Colonia -->
        <div class="col-md-4">
          <label class="form-label">Colonia</label>
          <select name="colonia" id="colonia" class="form-select" onchange="toggleOtraColonia()">
            <option value="">Seleccione una colonia</option>
            <?php
            $colonias = [
              '18 DE AGOSTO','1RO DE MAYO','20 DE NOVIEMBRE','6 DE JUNIO','ALFREDO DEL MAZO',
              'AMPLIACIÓN 6 DE JUNIO','AMPLIACIÓN ACOZAC','AMPLIACIÓN DR. JORGE JIMÉNEZ CANTÚ','AMPLIACIÓN ESCALERILLAS',
              'AMPLIACIÓN LOMA BONITA','AMPLIACIÓN LUIS CÓRDOVA REYES','AMPLIACIÓN MORELOS',
              'AMPLIACIÓN PLUTARCO ELÍAS CALLES','AMPLIACIÓN SAN FRANCISCO','AMPLIACIÓN SANTO TOMÁS',
              'AMPLIACIÓN TEJALPA','AQUILES CÓRDOVA MORÁN','ÁVILA CAMACHO','AYOTLA CENTRO','AZIZINTLA','BENITO QUEZADA',
              'CAPILLAS I','CAPILLAS II','CAPILLAS III Y IV','CERRO DE MOCTEZUMA','CITLALMINA','CIUDAD CUATRO VIENTOS',
              'CLARA CÓRDOVA MORÁN','COATEPEC','CONTADERO','CUMBRES DE LA MONTAÑA','DERRAMADERO','DR. JORGE JIMÉNEZ CANTÚ',
              'EL CACERIO','EL CAPULÍN','EL CARACOL','EL CARMEN','EL CHILILICO','EL GATO','EL MAGUEYAL','EL MAGISTERIO',
              'EL MIRADOR','EL MIRTO','EL MOLINO','EL OCOTE','EL PANTEÓN','EL PATRONATO','EL PILAR','EL PINO','EL TABLÓN',
              'EL TEJOLOTE','ELSA CÓRDOVA MORÁN','EMILIANO ZAPATA','ESCALERILLAS','ESPARTACO','ESTADO DE MÉXICO','F. ÁLVAREZ',
              'FRACC. IZCALLI IXTAPALUCA','FRACC. JOSE DE LA MORA','FRACC. RANCHO EL CARMEN',
              'FRACCIONAMIENTO UNIDAD DEPORTIVA RESIDENCIAL ACOZAC','FRATERNIDAD','GEOVILLAS DE JESÚS MARÍA',
              'GEOVILLAS DE SAN JACINTO','GEOVILLAS DE SANTA BÁRBARA','GEOVILLAS IXTAPALUCA 2000','GONZALO LÓPEZ CID',
              'HORNOS DE SAN FRANCISCO','HORNOS DE SANTA BÁRBARA','HORNOS DE ZOQUIAPAN','HUMBERTO GUTIÉRREZ',
              'HUMBERTO VIDAL MENDOZA','ILHUILCAMINA','INDEPENDENCIA','IXTAPALUCA CENTRO','JACARANDAS','JESÚS MARÍA',
              'JOSÉ GUADALUPE POSADA (UPREZ)','JUAN ANTONIO SOBERANES','LA ANTORCHA','LA CAÑADA','LA GUADALUPANA',
              'LA HUERTA','LA MAGDALENA ATLIPAC','LA PRESA','LA RETAMA','LA VENTA','LA VIRGEN',
              'LAS PALMAS 2DA SECCIÓN','LAS PALMAS 3RA ETAPA','LAS PALMAS HACIENDA','LAVADEROS','LINDAVISTA','LLANO GRANDE',
              'LOMA BONITA','LOMA DEL RAYO','LOMAS DE AYOTLA','LOMAS DE COATEPEC','LOMAS DE IXTAPALUCA','LOS DEPÓSITOS',
              'LOS HÉROES','LOS HÉROES TEZONTLE','LUIS CÓRDOVA REYES','LUIS DONALDO COLOSIO','MANUEL SERRANO VALLEJO',
              'MARCO ANTONIO SOSA BALDERAS','MARGARITA MORAN','MELCHOR OCAMPO','MORELOS (NUEVA INDEPENDENCIA)',
              'NUEVA ANTORCHA','NUEVA ANTORCHISTA','NUEVO JARDÍN INDUSTRIAL IXTAPALUCA','PASEOS DE COATEPEC',
              'PEÑA DE LA ROSA DE CASTILLA','PIEDRAS GRANDES','PLUTARCO ELÍAS CALLES','PUEBLO NUEVO','RANCHO GUADALUPE',
              'RANCHO SAN JOSÉ','RANCHO VERDE','REAL DEL CAMPO','RESIDENCIAL AYOTLA','RESIDENTIAL PARK','REY IZCOATL',
              'REY IZCOATL 2DA SECCIÓN','REY IZCOATL 3RA SECCIÓN','RICARDO CALVA','RIGOBERTA MENCHÚ','RINCÓN DEL BOSQUE',
              'RÍO FRÍO','ROSA DE SAN FRANCISCO','SAN ANTONIO TLALPIZAHUAC.','SAN BUENAVENTURA','SAN FRANCISCO ACUAUTLA',
              'SAN ISIDRO','SAN JERÓNIMO','SAN JOSÉ DE LA PALMA','SAN JUAN','SAN JUAN SAN ANTONIO',
              'SAN JUAN TLALPIZAHUAC.','SAN MIGUEL','SANTA BÁRBARA','SANTA CRUZ TLALPIZAHUAC','SANTA CRUZ TLAPACOYA',
              'SANTO TOMÁS','TECOMATLAN','TEJALPA','TEPONAXTLE','TETITLA','TEZONTLE','TLACAELEL','TLAPACOYA PUEBLO',
              'TLAYEHUALE','UNIDAD MAGISTERIAL','VALLE VERDE','VICTORIO SOTO WENCESLAO','VILLAS DE ANTORCHA',
              'VILLAS DE AYOTLA','VOLCANES','WENCESLAO','XOCHITENCO','ZONA INDUSTRIAL AYOTLA','ZOQUIAPAN','Otro'
            ];

            foreach ($colonias as $c) {
              $selected = ($doc['colonia'] == $c) ? "selected" : "";
              echo "<option value='$c' $selected>$c</option>";
            }
            ?>
          </select>
        </div>

        <!-- Campo para otra colonia -->
        <div class="col-md-4" id="colonia_otro_div"
             style="<?php echo ($doc['colonia'] == 'Otro' ? '' : 'display:none;'); ?>">
          <label class="form-label">Especifique otra colonia</label>
          <input type="text" name="colonia_otro" id="colonia_otro"
                 value="<?php echo $doc['colonia'] == 'Otro' ? $doc['colonia'] : ''; ?>"
                 class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" value="<?php echo $doc['direccion']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Clave catastral</label>
          <input type="text" name="clave_catastral" value="<?php echo $doc['clave_catastral']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Base gravable</label>
          <input type="number" step="0.01" name="base_gravable" value="<?php echo $doc['base_gravable']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Bimestre</label>
          <select name="bimestre" class="form-select">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <option value="<?= $i ?>" <?php if ($doc['bimestre'] == $i) echo 'selected'; ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Superficie terreno</label>
          <input type="number" step="0.01" name="superficie_terreno"
                 value="<?php echo $doc['superficie_terreno']; ?>" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Superficie construcción</label>
          <input type="number" step="0.01" name="superficie_construccion"
                 value="<?php echo $doc['superficie_construccion']; ?>" class="form-control">
        </div>

        <!-- Contribuyente y validación -->
        <h5 class="mt-3">Contribuyente y validación</h5>
        <div class="col-md-4">
          <label class="form-label">Nombre del contribuyente</label>
          <input type="text" name="contribuyente" value="<?php echo $doc['contribuyente']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Subdirector</label>
          <input type="text" name="subdirector" value="<?php echo $doc['subdirector']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" name="cargo" value="<?php echo $doc['cargo']; ?>" class="form-control">
        </div>

        <!-- Recibos y certificación -->
        <h5 class="mt-3">Recibos y certificación</h5>
        <div class="col-md-4">
          <label class="form-label">Recibo oficial</label>
          <input type="text" name="recibo_oficial" value="<?php echo $doc['recibo_oficial']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Recibo mejoras</label>
          <input type="text" name="recibo_mejoras" value="<?php echo $doc['recibo_mejoras']; ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Costo de certificación</label>
          <input type="number" step="0.01" name="costo_certificacion"
                 value="<?php echo $doc['costo_certificacion']; ?>" class="form-control">
        </div>

        <div class="col-12 mt-3">
          <button type="submit" name="actualizar_doc" class="btn btn-primary w-100">Actualizar documento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleOtraColonia() {
  const select = document.getElementById("colonia");
  const otroDiv = document.getElementById("colonia_otro_div");
  if (select.value === "Otro") {
    otroDiv.style.display = "block";
  } else {
    otroDiv.style.display = "none";
    document.getElementById("colonia_otro").value = "";
  }
}
</script>
