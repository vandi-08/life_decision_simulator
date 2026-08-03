<?php
/**
 * Application bootstrap — include this once at the top of every page.
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // never show raw PHP errors to users (see §57)
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e) {
    error_log('[UNCAUGHT] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo 'Terjadi masalah saat memproses permintaanmu. Silakan coba lagi.';
    exit;
});

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/calculations.php';
