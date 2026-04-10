<?php
/**
 * Front Controller
 * Every HTTP request enters here.
 */

define('BASE_PATH', __DIR__);

// ── Autoload classes ─────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        // Helpers
        'Response'               => BASE_PATH . '/app/helpers/Response.php',
        'Request'                => BASE_PATH . '/app/helpers/Request.php',
        // Middleware
        'AuthMiddleware'         => BASE_PATH . '/app/middleware/AuthMiddleware.php',
        // DB
        'DB'                     => BASE_PATH . '/config/database.php',
        // Models
        'BaseModel'              => BASE_PATH . '/app/models/BaseModel.php',
        'UserModel'              => BASE_PATH . '/app/models/UserModel.php',
        'JobModel'               => BASE_PATH . '/app/models/JobModel.php',
        'ApplicationModel'       => BASE_PATH . '/app/models/ApplicationModel.php',
        'CandidateProfileModel'  => BASE_PATH . '/app/models/CandidateProfileModel.php',
        'EmployerProfileModel'   => BASE_PATH . '/app/models/EmployerProfileModel.php',
        'RecommendationModel'    => BASE_PATH . '/app/models/RecommendationModel.php',
        // Controllers
        'AuthController'         => BASE_PATH . '/app/controllers/AuthController.php',
        'JobController'          => BASE_PATH . '/app/controllers/JobController.php',
        'CandidateController'    => BASE_PATH . '/app/controllers/CandidateController.php',
        'EmployerController'     => BASE_PATH . '/app/controllers/EmployerController.php',
        'StatsController'        => BASE_PATH . '/app/controllers/StatsController.php',
        // Services
        'RecommendationService'  => BASE_PATH . '/app/services/RecommendationService.php',
    ];

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

// ── Load config ──────────────────────────────────────────────────────────────
$config = require BASE_PATH . '/config/app.php';

// ── Global exception handler ─────────────────────────────────────────────────
set_exception_handler(function (Throwable $e) use ($config): void {
    $msg = date('[Y-m-d H:i:s] ') . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    if (!empty($config['log_path'])) {
        error_log($msg, 3, $config['log_path']);
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => !empty($config['debug'])
            ? $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')'
            : 'An unexpected error occurred.',
    ]);
    exit;
});

// ── Session ──────────────────────────────────────────────────────────────────
ini_set('session.gc_maxlifetime', $config['session_lifetime']);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name($config['session_name']);
session_start();

// ── CORS headers ─────────────────────────────────────────────────────────────
// With credentials:true the browser sends the exact Origin — we must echo it
// back explicitly (wildcards are forbidden by the CORS spec in this case).
header('Content-Type: application/json; charset=utf-8');

$requestOrigin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = $config['cors_origins'] ?? [];

if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
} elseif (empty($requestOrigin)) {
    // Same-origin / Postman — no CORS header needed
} else {
    // Origin not in whitelist — deny (do not set header; browser blocks it)
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Dispatch ─────────────────────────────────────────────────────────────────
require BASE_PATH . '/routes/api.php';
