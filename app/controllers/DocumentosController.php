<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once dirname(__DIR__, 2) . "/config/config.php";
include_once dirname(__DIR__, 2) . "/utils/helpers.php";

// Verificar usuario
if (!isset($_SESSION["usuario"])) {
    header("Location: index.php?url=login");
    exit;
}

$usuario = $_SESSION["usuario"];

$usuarios_globales = ['admin', 'Maciel', 'Marisol'];

// Obtener ID del capturista
$sqlUser = "SELECT id FROM QRusuarios WHERE usuario = ?";   
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$id_capturista = ($row = $resultUser->fetch_assoc()) ? $row["id"] : 0;

$condiciones = [];
$params = [];
$tipos = "";

// --- FILTROS ---
if (!empty($_GET["folio_no_adeudo"])) {
    $condiciones[] = "folio_no_adeudo LIKE ?";
    $params[] = "%" . $_GET["folio_no_adeudo"] . "%";
    $tipos .= "s";
}
if (!empty($_GET["folio_aportacion"])) {
    $condiciones[] = "folio_aportacion LIKE ?";
    $params[] = "%" . $_GET["folio_aportacion"] . "%";
    $tipos .= "s";
}
if (!empty($_GET["clave_catastral"])) {
    $condiciones[] = "clave_catastral LIKE ?";
    $params[] = "%" . $_GET["clave_catastral"] . "%";
    $tipos .= "s";
}
if (!empty($_GET["contribuyente"])) {
    $condiciones[] = "contribuyente LIKE ?";
    $params[] = "%" . $_GET["contribuyente"] . "%";
    $tipos .= "s";
}

// --- ORDEN DINÁMICO ---
$orden = $_GET["orden"] ?? "recientes";
$orderBy = "fecha_captura DESC, id DESC";

switch ($orden) {
    case "recientes":
        $orderBy = "fecha_captura DESC, id DESC";
        break;
    case "antiguos":
        $orderBy = "fecha_captura ASC, id ASC";
        break;
    case "noadeudo_recientes":
        $condiciones[] = "tipo_documento = 'no_adeudo'";
        $orderBy = "fecha_captura DESC, id DESC";
        break;
    case "noadeudo_antiguos":
        $condiciones[] = "tipo_documento = 'no_adeudo'";
        $orderBy = "fecha_captura ASC, id ASC";
        break;
    case "aportacion_recientes":
        $condiciones[] = "tipo_documento = 'aportacion_mejoras'";
        $orderBy = "fecha_captura DESC, id DESC";
        break;
    case "aportacion_antiguos":
        $condiciones[] = "tipo_documento = 'aportacion_mejoras'";
        $orderBy = "fecha_captura ASC, id ASC";
        break;
}

// ======================================================
//  CONSULTA PRINCIPAL SEGÚN USUARIO
// ======================================================
if (in_array($usuario, $usuarios_globales)) {
    // Usuarios con acceso global (ven todo)
    $sqlDocs = "SELECT d.id, d.fecha_captura, d.contribuyente, 
                       d.folio_no_adeudo, d.folio_aportacion, 
                       d.clave_catastral, d.tipo_documento,
                       IFNULL(d.estado_pdf,'activo') AS estado_pdf,
                       IFNULL(d.estado_entrega,'pendiente') AS estado_entrega,
                       u.usuario AS capturista
                FROM QRdocumentos d
                INNER JOIN QRusuarios u ON d.id_capturista = u.id";
} else {
    // Usuarios normales (solo sus capturas)
    $sqlDocs = "SELECT d.id, d.fecha_captura, d.contribuyente, 
                       d.folio_no_adeudo, d.folio_aportacion, 
                       d.clave_catastral, d.tipo_documento,
                       IFNULL(d.estado_pdf,'activo') AS estado_pdf,
                       IFNULL(d.estado_entrega,'pendiente') AS estado_entrega,
                       u.usuario AS capturista
                FROM QRdocumentos d
                INNER JOIN QRusuarios u ON d.id_capturista = u.id
                WHERE d.id_capturista = ?";
}


if (!empty($condiciones)) {
    $sqlDocs .= (in_array($usuario, $usuarios_globales) ? " WHERE " : " AND ") . implode(" AND ", $condiciones);
}

$sqlDocs .= " ORDER BY $orderBy";

$stmtDocs = $conn->prepare($sqlDocs);

if (!empty($params)) {
    $stmtDocs->bind_param($tipos, ...$params);
}

$stmtDocs->execute();
$docs = $stmtDocs->get_result();

// ======================================================
//  CONTEOS SEGÚN USUARIO
// ======================================================
if (in_array($usuario, $usuarios_globales)) {
    // Totales globales
    $sqlTotal = "SELECT COUNT(*) AS total FROM QRdocumentos";
    $totalGeneral = $conn->query($sqlTotal)->fetch_assoc()["total"];

    $sqlNoAdeudo = "SELECT COUNT(*) AS total FROM QRdocumentos WHERE tipo_documento = 'no_adeudo'";
    $totalNoAdeudo = $conn->query($sqlNoAdeudo)->fetch_assoc()["total"];

    $sqlAportacion = "SELECT COUNT(*) AS total FROM QRdocumentos WHERE tipo_documento = 'aportacion_mejoras'";
    $totalAportacion = $conn->query($sqlAportacion)->fetch_assoc()["total"];
} else {
    // Totales individuales
    $sqlTotal = "SELECT COUNT(*) AS total FROM QRdocumentos WHERE id_capturista = ?";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param("i", $id_capturista);
    $stmtTotal->execute();
    $totalGeneral = $stmtTotal->get_result()->fetch_assoc()["total"];

    $sqlNoAdeudo = "SELECT COUNT(*) AS total FROM QRdocumentos WHERE id_capturista = ? AND tipo_documento = 'no_adeudo'";
    $stmtNoAdeudo = $conn->prepare($sqlNoAdeudo);
    $stmtNoAdeudo->bind_param("i", $id_capturista);
    $stmtNoAdeudo->execute();
    $totalNoAdeudo = $stmtNoAdeudo->get_result()->fetch_assoc()["total"];

    $sqlAportacion = "SELECT COUNT(*) AS total FROM QRdocumentos WHERE id_capturista = ? AND tipo_documento = 'aportacion_mejoras'";
    $stmtAportacion = $conn->prepare($sqlAportacion);
    $stmtAportacion->bind_param("i", $id_capturista);
    $stmtAportacion->execute();
    $totalAportacion = $stmtAportacion->get_result()->fetch_assoc()["total"];
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Documentos Capturados</title>
    <link rel="icon" href="<?php echo buildAbsoluteUrl('public/assets/img/favicon.ico'); ?>" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background-color: #9F2241;
        }

        .navbar-brand,
        .nav-link,
        .navbar-text {
            color: #fff !important;
        }

        .btn-custom {
            background: none;
            border: none;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            padding: 0;
            transition: color 0.3s ease;
        }

        .btn-custom:hover {
            color: #ffd700;
            text-decoration: none;
        }

        .card-table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        table th {
            background-color: #9F2241 !important;
            color: #fff !important;
            text-transform: uppercase;
        }

        table td {
            vertical-align: middle;
        }

        .action-btn {
            white-space: nowrap;
            padding: 4px 10px;
            border: 1px solid #9F2241;
            border-radius: 4px;
            background: none;
            color: #9F2241;
            font-size: 0.85rem;
            margin-right: 6px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #9F2241;
            color: #fff;
        }

        td .d-flex {
            flex-wrap: nowrap;
        }

        th:last-child,
        td:last-child {
            min-width: 340px;
        }

        .btn-small {
            font-size: 0.9rem;
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid #9F2241;
            background-color: #9F2241;
            color: #fff;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .btn-small:hover {
            background-color: #BC955C;
            border-color: #BC955C;
            color: #fff;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php?url=dashboard">
                <img src="<?php echo buildAbsoluteUrl('public/assets/img/IXTALOGO.png'); ?>"
                    alt="Logo" height="40" class="me-2"> Sistema de Validación de Documentos
            </a>
            <ul class="navbar-nav ms-auto d-flex align-items-center">
                <li class="nav-item me-3"><a href="index.php?url=dashboard" class="btn-custom">Nuevo Documento</a></li>
                <li class="nav-item me-3"><a href="index.php?url=documentos" class="btn-custom">Documentos capturados</a></li>
                <li class="nav-item"><a href="index.php?url=logout" class="btn-custom">Cerrar sesión</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="mb-3">Documentos Capturados</h3>

        <!-- Filtros -->
        <form method="get" class="row g-2 mb-3">
            <input type="hidden" name="url" value="documentos">

            <div class="col-md-2">
                <input type="text" name="folio_no_adeudo" class="form-control" placeholder="Folio No adeudo" value="<?php echo $_GET['folio_no_adeudo'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="folio_aportacion" class="form-control" placeholder="Folio Aportación" value="<?php echo $_GET['folio_aportacion'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="clave_catastral" class="form-control" placeholder="Clave catastral" value="<?php echo $_GET['clave_catastral'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="contribuyente" class="form-control" placeholder="Contribuyente" value="<?php echo $_GET['contribuyente'] ?? '' ?>">
            </div>

            <!-- Orden -->
            <div class="col-md-3">
                <select name="orden" class="form-select" onchange="this.form.submit()">
                    <option value="recientes" <?php if ($orden == 'recientes') echo 'selected'; ?>>Más recientes primero (todos)</option>
                    <option value="antiguos" <?php if ($orden == 'antiguos') echo 'selected'; ?>>Más antiguos primero (todos)</option>

                    <option value="noadeudo_recientes" <?php if ($orden == 'noadeudo_recientes') echo 'selected'; ?>>No Adeudo (recientes primero)</option>
                    <option value="noadeudo_antiguos" <?php if ($orden == 'noadeudo_antiguos') echo 'selected'; ?>>No Adeudo (antiguos primero)</option>

                    <option value="aportacion_recientes" <?php if ($orden == 'aportacion_recientes') echo 'selected'; ?>>Aportación a Mejoras (recientes primero)</option>
                    <option value="aportacion_antiguos" <?php if ($orden == 'aportacion_antiguos') echo 'selected'; ?>>Aportación a Mejoras (antiguos primero)</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn-small w-100">Buscar</button>
            </div>
            <?php if (!empty($_GET)) { ?>
                <div class="col-md-2">
                    <a href="index.php?url=export_csv<?php echo '&' . http_build_query(array_diff_key($_GET, ['url' => ''])); ?>" class="btn-small w-100">Exportar CSV</a>
                </div>
            <?php } ?>
        </form>

        <div class="alert alert-info">
            <strong>Totales:</strong><br>
            Documentos No adeudo: <strong><?php echo $totalNoAdeudo; ?></strong><br>
            Documentos Aportación a mejoras: <strong><?php echo $totalAportacion; ?></strong><br>
            Total general de documentos: <strong><?php echo $totalGeneral; ?></strong>
        </div>

        <?php if (isset($_SESSION["msg_success"])): ?>
        <div class="alert alert-success"><?php echo $_SESSION["msg_success"]; unset($_SESSION["msg_success"]); ?></div>
        <?php elseif (isset($_SESSION["msg_error"])): ?>
       <div class="alert alert-danger"><?php echo $_SESSION["msg_error"]; unset($_SESSION["msg_error"]); ?></div>
       <?php endif; ?>


        <!-- Tabla -->
        <!-- Tabla -->
<div class="card-table">
  <div class="table-responsive">
    <table class="table table-striped align-middle text-center">
      <thead class="align-middle">
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Contribuyente</th>
          <th>Folio</th>
          <th>Clave</th>
          <th>Tipo</th>
          <th>Entrega</th>
          <th>Elaborado por</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($docs->num_rows > 0): ?>
          <?php while ($row = $docs->fetch_assoc()): ?>
            <tr class="<?php echo ($row['estado_entrega'] === 'entregado') ? 'table-success' : ''; ?>">
              <td><?php echo $row['id']; ?></td>
              <td><?php echo date('d/m/Y', strtotime($row['fecha_captura'])); ?></td>
              <td><?php echo htmlspecialchars($row['contribuyente']); ?></td>
              <td>
                <?php
                  echo $row['tipo_documento'] === 'no_adeudo'
                    ? $row['folio_no_adeudo']
                    : $row['folio_aportacion'];
                ?>
              </td>
              <td><?php echo htmlspecialchars($row['clave_catastral']); ?></td>
              <td>
                <span class="badge bg-<?php echo $row['tipo_documento'] == 'no_adeudo' ? 'success' : 'warning'; ?>">
                  <?php echo strtoupper(str_replace('_', ' ', $row['tipo_documento'])); ?>
                </span>
              </td>

              <!-- NUEVA COLUMNA: Entrega -->
              <td>
                <?php if ($row['estado_entrega'] === 'entregado'): ?>
                  <span class="badge bg-primary">Entregado</span>
                <?php else: ?>
                  <form method="post" action="index.php?url=entregar_doc" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('¿Marcar este documento como ENTREGADO?');">Entregar</button>
                </form>

                <?php endif; ?>
              </td>
              
              <!-- COLUMNA ELABORADO POR: -->
              <td><?php echo htmlspecialchars($row['capturista']); ?></td>

              <!-- Acciones -->
              <td>
                <?php if ($row["estado_pdf"] === "cancelado"): ?>
                  <span class="badge bg-danger me-2">PDF cancelado</span>
                  <a href="index.php?url=recuperar&id=<?php echo $row['id']; ?>"
                     class="action-btn"
                     onclick="return confirm('¿Deseas recuperar este documento y reactivar su PDF?');">
                     Recuperar
                  </a>
                <?php else: ?>
                  <div class="d-flex flex-nowrap">
                    <a href="index.php?url=editar&id=<?php echo $row['id']; ?>" class="action-btn">Editar</a>
                    <a href="index.php?url=ver_pdf&id=<?php echo $row['id']; ?>" class="action-btn">Ver</a>
                    <a href="index.php?url=descargar&id=<?php echo $row['id']; ?>" class="action-btn">Descargar</a>
                    <a href="index.php?url=cancelar&id=<?php echo $row['id']; ?>"
                       class="action-btn"
                       onclick="return confirm('¿Deseas cancelar este documento y eliminar su PDF validado?');">
                       Cancelar
                    </a>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center text-muted">No hay documentos registrados</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

    </div>
</body>

</html>