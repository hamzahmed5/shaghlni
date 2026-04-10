<?php
/**
 * Application Configuration
 * Edit these values to match your environment before running.
 */

$config = [

    // ── Database ────────────────────────────────────────────────────────────
    'db' => [
        'host'    => 'localhost',
        'port'    => '3306',
        'name'    => 'jobs_platform',   // <-- change to your DB name
        'user'    => 'root',             // <-- change to your DB user
        'pass'    => '',                 // <-- change to your DB password
        'charset' => 'utf8mb4',
    ],

    // ── CORS ────────────────────────────────────────────────────────────────
    // List every frontend origin that is allowed to send credentialed requests.
    // IMPORTANT: '*' cannot be used with credentials:true — list origins explicitly.
    // Local dev example:  'http://localhost:3000', 'http://127.0.0.1:5500'
    // Production example: 'https://yourfrontenddomain.com'
    'cors_origins' => [
        'http://localhost:3000',
        'http://localhost:5500',
        'http://127.0.0.1:5500',
        'http://localhost',
        'http://127.0.0.1',
    ],

    // ── Session ─────────────────────────────────────────────────────────────
    'session_name'     => 'jobs_platform_sess',
    'session_lifetime' => 86400, // seconds (24 h)

    // ── File uploads ─────────────────────────────────────────────────────────
    'upload' => [
        'cv_path'      => __DIR__ . '/../storage/uploads/cvs/',
        'logo_path'    => __DIR__ . '/../storage/uploads/logos/',
        'max_size'     => 5 * 1024 * 1024, // 5 MB
        'allowed_cv'   => ['pdf', 'doc', 'docx'],
        'allowed_logo' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    // ── ML / Recommendation mode ─────────────────────────────────────────────
    // 'local'       → PHP shells out to Python script (requires Python installed)
    // 'cached'      → PHP reads pre-computed rows from `recommendations` table
    'ml_mode'        => 'cached',
    'python_bin'     => 'python3',                                     // or 'python'
    'recommend_script' => __DIR__ . '/../scripts/python/recommend.py',

    // ── App ──────────────────────────────────────────────────────────────────
    'debug' => (bool) getenv('JOBPILOT_DEBUG'),   // set env JOBPILOT_DEBUG=1 to enable
    'log_path' => __DIR__ . '/../logs/app.log',
];

$local = file_exists(__DIR__ . '/local.php') ? (require __DIR__ . '/local.php') : [];
return array_replace_recursive($config, $local);
