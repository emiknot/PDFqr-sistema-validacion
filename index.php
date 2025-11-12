<?php
// index.php → Router principal

// Cargar todas las rutas
$routes = require __DIR__ . "/routes.php";

// Obtener parámetro de URL (ej: index.php?url=dashboard)
$url = $_GET["url"] ?? "login";

// Buscar la ruta
if (array_key_exists($url, $routes)) {
    include __DIR__ . "/" . $routes[$url];
} else {
    http_response_code(404);
    echo "<h1>404 - Página no encontrada</h1>";
}

