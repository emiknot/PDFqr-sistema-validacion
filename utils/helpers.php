<?php
// Funciones auxiliares

/**
 * Construye una URL absoluta para un recurso del sistema.
 * - Si está en localhost, usa la IP LAN definida.
 * - Si está en servidor, usa el host detectado.
 */
function buildAbsoluteUrl($path = '', $params = [])
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    //  Cambia esta IP por la de tu máquina si usas LAN
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $host = '10.0.0.7';
    }

    // Ruta base del proyecto
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $base = rtrim($scriptName, '/');

    $url = $scheme . '://' . $host . $base . '/' . ltrim($path, '/');

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Verifica si el usuario tiene permisos globales (puede ver/editar todo).
 * Puedes agregar más usuarios según lo necesites.
 */
function tienePermisoGlobal($usuario)
{
    // Lista de usuarios con acceso global
    $usuariosPermitidos = ["admin", "Maciel", "Marisol"];

    // Devuelve true si el usuario está en la lista
    return in_array($usuario, $usuariosPermitidos);
}
