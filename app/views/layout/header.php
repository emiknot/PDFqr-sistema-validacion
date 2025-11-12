<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once dirname(__DIR__, 3) . "/config/config.php"; 
require_once dirname(__DIR__, 3) . "/utils/helpers.php"; 

// (El header ya no carga documentos, eso queda en DocumentosController.php)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Captura de Documentos</title>
    <link rel="icon" href="<?php echo buildAbsoluteUrl('public/assets/img/favicon.ico'); ?>" type="image/x-icon"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <style>
        body {
            background-color: #f4f4f4;
        }
        .navbar {
            background-color: #9F2241;
        }
        .navbar-brand, .nav-link, .navbar-text {
            color: #fff !important;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0px 2px 6px rgba(15, 18, 15, 0.1);
        }
        .btn-primary {
            background-color: #BC955C;
            border: none;
        }
        .btn-primary:hover {
            background-color: #a58343ff;
        }
        footer {
            background: #003366;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 30px;
        }
        .btn-docs, .btn-logout, .btn-new-doc {
            background: none;
            border: none;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            padding: 0;
            transition: color 0.3s ease;
        }
        .btn-docs:hover, .btn-logout:hover, .btn-new-doc:hover {
            color: #ffd700;
            text-decoration: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
<a class="navbar-brand d-flex align-items-center" href="index.php?url=dashboard">
  <img src="<?php echo buildAbsoluteUrl('public/assets/img/IXTALOGO.png'); ?>" 
       alt="Logo" height="40" class="me-2">
  Sistema de Validación de Documentos
</a>

    <?php if (isset($_SESSION["usuario"])) { ?>
    <ul class="navbar-nav ms-auto d-flex align-items-center">
        <li class="nav-item me-3">
            <a href="index.php?url=dashboard" class="btn-new-doc">Nuevo Documento</a>
        </li>
        <li class="nav-item me-3">
            <a href="index.php?url=documentos" class="btn-docs">Documentos capturados</a>
        </li>
        <li class="nav-item">
            <a href="index.php?url=logout" class="btn-logout">Cerrar sesión</a>
        </li>
    </ul>
    <?php } ?>
  </div>
</nav>

<div class="container mt-4">
