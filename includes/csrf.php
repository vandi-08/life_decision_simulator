<?php
/**
 * CSRF protection helpers
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Call at the top of any POST handler. Kills the request with a 403
 * and a friendly message if the token is missing/invalid.
 */
function requireValidCsrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}
