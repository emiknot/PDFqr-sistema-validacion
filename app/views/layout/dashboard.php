<?php
session_start();
include "config/config.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

// Función para obtener el id del usuario logueado
function getUserId($usuario, $conn) {
    $sql = "SELECT id FROM QRusuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row["id"];
    }
    return null;
}

$id_capturista = getUserId($_SESSION["usuario"], $conn);

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// Variables para mostrar BOTONES 
$tipo_doc_guardado = null;
$id_doc_guardado   = null;
// <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

// Insertar documento si se envía formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["guardar_doc"])) {
    $fecha_captura = !empty($_POST["fecha_captura"]) ? date("Y-m-d", strtotime($_POST["fecha_captura"])) : date("Y-m-d");
    $fecha_expedicion_pago = $_POST["fecha_expedicion_pago"];
    $anio_fiscal = $_POST["anio_fiscal"];
    $tipo_documento = $_POST["tipo_documento"];
    $folio_no_adeudo = $_POST["folio_no_adeudo"];
    $folio_aportacion = $_POST["folio_aportacion"];
    $linea_captura = $_POST["linea_captura"];
    $tipo_predio = $_POST["tipo_predio"];

    // Manejo de colonia y colonia_otro
    $colonia = $_POST["colonia"];
    $colonia_otro = isset($_POST["colonia_otro"]) ? $_POST["colonia_otro"] : null;
    if ($colonia === "Otro" && !empty($colonia_otro)) {
        $colonia = $colonia_otro;
    }

    $direccion = $_POST["direccion"];
    $contribuyente = $_POST["contribuyente"];
    $clave_catastral = $_POST["clave_catastral"];
    $base_gravable = $_POST["base_gravable"];
    $bimestre = $_POST["bimestre"];
    $superficie_terreno = $_POST["superficie_terreno"];
    $superficie_construccion = $_POST["superficie_construccion"];
    $subdirector = $_POST["subdirector"];
    $cargo = $_POST["cargo"];
    $recibo_oficial = $_POST["recibo_oficial"];
    $recibo_mejoras = $_POST["recibo_mejoras"];
    $costo_certificacion = $_POST["costo_certificacion"];

  // ======================================================
  // Consulta preparada
  // ======================================================

  // --- Mantener las cantidades exactamente como las escribió el capturista ---
  $superficie_terreno = trim($_POST["superficie_terreno"]);
  $superficie_construccion = trim($_POST["superficie_construccion"]);
  $costo_certificacion = trim($_POST["costo_certificacion"]);

  $sql = "INSERT INTO QRdocumentos (
    id, fecha_captura, fecha_expedicion_pago, id_capturista, anio_fiscal, tipo_documento,
    folio_no_adeudo, folio_aportacion, linea_captura,
    tipo_predio, colonia, direccion, clave_catastral,
    base_gravable, bimestre, superficie_terreno, superficie_construccion,
    contribuyente, subdirector, cargo, recibo_oficial, recibo_mejoras, costo_certificacion
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    //  Caso especial: AMBOS
    if ($tipo_documento === "ambos") {
        // ---- Primer documento → No adeudo ----
        $id1 = uniqid("", true);
        $tipo1 = "no_adeudo";
        $stmt->bind_param(
          "sssissssssssssdiisdssss",
           $id1, $fecha_captura, $fecha_expedicion_pago, $id_capturista, $anio_fiscal, $tipo1,
           $folio_no_adeudo, $folio_aportacion, $linea_captura,
           $tipo_predio, $colonia, $direccion, $clave_catastral,
           $base_gravable, $bimestre, $superficie_terreno, $superficie_construccion,
           $contribuyente, $subdirector, $cargo,
           $recibo_oficial, $recibo_mejoras, $costo_certificacion
          );
        $stmt->execute();

        // ---- Segundo documento → Aportación a mejoras ----
        $id2 = uniqid("", true);
        $tipo2 = "aportacion_mejoras";
        $stmt->bind_param(
          "sssissssssssssdiisdssss",
           $id2, $fecha_captura, $fecha_expedicion_pago, $id_capturista, $anio_fiscal, $tipo2,
           $folio_no_adeudo, $folio_aportacion, $linea_captura,
           $tipo_predio, $colonia, $direccion, $clave_catastral,
           $base_gravable, $bimestre, $superficie_terreno, $superficie_construccion,
           $contribuyente, $subdirector, $cargo,
           $recibo_oficial, $recibo_mejoras, $costo_certificacion
          );
        $stmt->execute();

        $msg = " Se generaron ambos documentos correctamente.";
        $tipo_doc_guardado = "ambos";
        $ids_ambos = [$id1, $id2]; // guardamos ambos IDs para usarlos en el botón

    } else {
        // ======================================================
        // Caso normal → solo un documento
        // ======================================================
        $id = uniqid("", true);
        $stmt->bind_param(
          "sssissssssssssdiisdssss",
          $id, $fecha_captura, $fecha_expedicion_pago, $id_capturista, $anio_fiscal, $tipo_documento,
          $folio_no_adeudo, $folio_aportacion, $linea_captura,
          $tipo_predio, $colonia, $direccion, $clave_catastral,
          $base_gravable, $bimestre, $superficie_terreno, $superficie_construccion,
          $contribuyente, $subdirector, $cargo,
          $recibo_oficial, $recibo_mejoras, $costo_certificacion
        );

        if ($stmt->execute()) {
            $msg = " Documento guardado correctamente.";
            $id_doc_guardado   = $id;
            $tipo_doc_guardado = $tipo_documento;
            $no_adeudo_lleno   = !empty($folio_no_adeudo);
            $aportacion_lleno  = !empty($folio_aportacion);
        } else {
            $error = " Error: " . $conn->error;
        }
    }
}


?>
<?php include "app/views/layout/header.php"; ?>

<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="card p-4 mb-4">
      <h4 class="text-center">Registrar Nuevo Documento</h4>
      <?php if (isset($msg)) { ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php if (isset($msg)) { ?>

        <!-- ========================================= -->
        <!-- BOTONES de impresión condicionales -->
  <div class="text-center mb-3">
    <!-- Si el documento guardado es de tipo no_adeudo -->
    <?php if ($tipo_doc_guardado === "no_adeudo" && $no_adeudo_lleno) { ?>
      <a href="index.php?url=ver_pdf&id=<?php echo $id_doc_guardado; ?>" target="_blank" class="btn btn-success me-2">
        Imprimir No Adeudo predial
      </a>
    <?php } ?>

    <!-- Si el documento guardado es de tipo aportacion_mejoras -->
    <?php if ($tipo_doc_guardado === "aportacion_mejoras" && $aportacion_lleno) { ?>
      <a href="index.php?url=ver_pdf&id=<?php echo $id_doc_guardado; ?>" target="_blank" class="btn btn-warning me-2">
        Imprimir Aportación a Mejoras
      </a>
    <?php } ?>

    <!-- Si el documento guardado es "ambos" -->
    <?php if ($tipo_doc_guardado === "ambos" && !empty($ids_ambos)) { ?>
      <a href="index.php?url=ver_pdf_ambos&id1=<?php echo $ids_ambos[0]; ?>&id2=<?php echo $ids_ambos[1]; ?>" 
         target="_blank" 
         class="btn btn-primary">
         Imprimir Ambos Documentos
      </a>
    <?php } ?>
  </div>
<?php } ?>


        <!-- ========================================= -->

      <?php } ?>
      <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php } ?>

      <form method="post" class="row g-3">
        <!-- Datos del documento -->
        <h5 class="mt-3">Datos del documento</h5>
        <div class="col-md-4">
          <label class="form-label">Fecha de CAPTURA</label>
          <input type="date" name="fecha_captura" value="<?php echo date('d/m/Y'); ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Año fiscal</label>
          <input type="number" name="anio_fiscal" value="2025" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Tipo de documento</label>
          <select name="tipo_documento" id="tipo_documento" class="form-select" onchange="toggleFolios()">
          <option value="no_adeudo">NO ADEUDO PREDIAL</option>
          <option value="aportacion_mejoras">APORTACIÓN A MEJORAS</option>
          <option value="ambos">LLENAR AMBOS</option>
        </select>
      </div>
      
      <!-- Identificación y folios -->
       <h5 class="mt-3">Identificación y folios</h5>
       <div class="col-md-4">
        <label class="form-label">Folio No adeudo predial</label>
        <input type="text" id="folio_no_adeudo" name="folio_no_adeudo" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Folio Aportación</label>
        <input type="text" id="folio_aportacion" name="folio_aportacion" class="form-control">
      </div>
      <div class="col-md-4">
          <label class="form-label">Línea de captura</label>
          <input type="text" name="linea_captura" class="form-control">
        </div>
        <div class="col-md-4">
          <label for="fecha_expedicion_pago" class="form-label">Fecha de EXPEDICIÓN del Pago</label>
          <input type="date" class="form-control" name="fecha_expedicion_pago" id="fecha_expedicion_pago" required>
        </div>
        <style>
         /* Estilo para que se note el campo bloqueado */
         .readonly {
         background-color: #f1f3f5;
         pointer-events: none;
         }
         </style>

        <!-- Datos del predio -->
        <h5 class="mt-3">Datos del predio</h5>
        <div class="col-md-4">
          <label class="form-label">Tipo de predio</label>
          <select name="tipo_predio" class="form-select">
            <option value="CONSTRUIDO">CONSTRUIDO</option>
            <option value="SIN CONSTRUIR">SIN CONSTRUIR</option>
            <option value="BALDÍO">BALDÍO</option>
            <option value="OTRO">OTRO</option>
          </select>
        </div>

        <!-- Colonia -->
        <div class="col-md-4">
          <label class="form-label">Colonia</label>
          <select name="colonia" id="colonia" class="form-select" onchange="toggleOtraColonia()">
            <option value="">Seleccione una colonia</option>
            <?php
            $colonias = [ '18 DE AGOSTO','1RO DE MAYO','20 DE NOVIEMBRE','6 DE JUNIO','ALFREDO DEL MAZO',
            'AMPLIACIÓN 6 DE JUNIO','AMPLIACIÓN ACOZAC','AMPLIACIÓN DR. JORGE JIMÉNEZ CANTÚ','AMPLIACIÓN ESCALERILLAS',
            'AMPLIACIÓN LOMA BONITA','AMPLIACIÓN LUIS CÓRDOVA REYES','AMPLIACIÓN MORELOS',
            'AMPLIACIÓN PLUTARCO ELÍAS CALLES','AMPLIACIÓN SAN FRANCISCO','AMPLIACIÓN SANTO TOMÁS',
            'AMPLIACIÓN TEJALPA','AQUILES CÓRDOBA MORÁN','ÁVILA CAMACHO','AYOTLA CENTRO','AZIZINTLA','BENITO QUEZADA',
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
            'VILLAS DE AYOTLA','VOLCANES','WENCESLAO','XOCHITENCO','ZONA INDUSTRIAL AYOTLA','ZOQUIAPAN','Otro' ];
            foreach ($colonias as $c) {
                echo "<option value='$c'>$c</option>";
            }
            ?>
          </select>
        </div>

        <!-- Campo para otra colonia -->
        <div class="col-md-4" id="colonia_otro_div" style="display:none;">
          <label class="form-label">Especifique otra colonia </label>
          <input type="text" name="colonia_otro" id="colonia_otro" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Clave catastral</label>
          <input type="text" name="clave_catastral" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Base gravable</label>
          <input type="number" step="0.01" name="base_gravable" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Bimestre</label>
          <select name="bimestre" class="form-select">
            <?php for ($i=1; $i<=6; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Superficie terreno </label>
          <input type="number" step="0.01" name="superficie_terreno" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Superficie construcción </label>
          <input type="number" step="0.01" name="superficie_construccion" class="form-control">
        </div>

        <!-- Contribuyente y validación -->
        <h5 class="mt-3">Contribuyente y validación</h5>
        <div class="col-md-4">
          <label class="form-label">Nombre del contribuyente</label>
          <input type="text" name="contribuyente" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Subdirector</label>
          <input type="text" name="subdirector" value="<?php echo SUBDIRECTOR; ?>" class="form-control"readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label">Cargo</label>
          <input type="text" name="cargo" value="SUBDIRECTOR DE REACAUDACIÓN" class="form-control">
        </div>

        <!-- Recibos y certificación -->
        <h5 class="mt-3">Recibos y certificación</h5>
        <div class="col-md-4">
          <label class="form-label">Recibo oficial</label>
          <input type="text" name="recibo_oficial" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Recibo mejoras</label>
          <input type="text" name="recibo_mejoras" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Costo de certificación</label>
          <input type="number" step="0.01" name="costo_certificacion" class="form-control">
        </div>

        <div class="col-12 mt-3">
          <button type="submit" name="guardar_doc" class="btn btn-primary w-100">Guardar documento</button>
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

<script>
function toggleFolios() {
  const tipo = document.getElementById("tipo_documento").value;
  const fNoAdeudo = document.getElementById("folio_no_adeudo");
  const fAporta   = document.getElementById("folio_aportacion");

  const setRO = (el, ro, required) => {
    el.readOnly = ro;
    el.required = !!required;
    el.classList.toggle('readonly', ro);
    if (ro) el.value = ""; // opcional: limpia si se bloquea
  };

  if (tipo === "no_adeudo") {
    setRO(fNoAdeudo, false, true);
    setRO(fAporta,   true,  false);
  } else if (tipo === "aportacion_mejoras") {
    setRO(fNoAdeudo, true,  false);
    setRO(fAporta,   false, true);
  } else { // ambos
    setRO(fNoAdeudo, false, true);
    setRO(fAporta,   false, true);
  }
}

// Ejecutar una vez al cargar la página
document.addEventListener("DOMContentLoaded", toggleFolios);
</script>


