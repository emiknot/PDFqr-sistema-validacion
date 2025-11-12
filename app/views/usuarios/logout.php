<?php
session_start();
session_unset();
session_destroy();

// Incluir el helper (subimos 3 niveles desde views/usuarios)
require_once __DIR__ . '/../../../utils/helpers.php';

// Redirigir al login usando el helper híbrido
header("Location: " . buildAbsoluteUrl('index.php', ['url' => 'login']));
exit;
