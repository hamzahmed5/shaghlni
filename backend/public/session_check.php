<?php
/**
 * GET /public/session_check.php
 *
 * Lightweight endpoint the frontend can call to check if the session is
 * still active without hitting the full router.  Useful for page-load auth.
 *
 * Response:
 *   { "authenticated": true,  "user": { id, role, name } }
 *   { "authenticated": false, "user": null }
 */

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/app.php';

// CORS
$requestOrigin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = $config['cors_origins'] ?? [];
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('session.gc_maxlifetime', $config['session_lifetime']);
session_name($config['session_name']);
session_start();

if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user'          => [
            'id'   => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name'],
        ],
    ]);
} else {
    echo json_encode(['authenticated' => false, 'user' => null]);
}
