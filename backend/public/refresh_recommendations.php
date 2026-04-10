<?php
define('BASE_PATH', dirname(__DIR__));
$cfg = require BASE_PATH . '/config/app.php';
$expected = getenv('RECO_CRON_TOKEN') ?: 'change-me-in-env';
if (($_GET['token'] ?? '') !== $expected) {
    http_response_code(403); exit('Forbidden');
}
$python = escapeshellcmd($cfg['python_bin'] ?? 'python3');
$script = escapeshellarg($cfg['recommend_script']);
passthru("$python $script 2>&1");
