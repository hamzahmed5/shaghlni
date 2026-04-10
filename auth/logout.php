<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
start_session();
session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');
redirect(SITE_URL . '/auth/login.php');
