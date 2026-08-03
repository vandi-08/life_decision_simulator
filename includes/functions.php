<?php
/**
 * General helper functions
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

/** Escape for safe HTML output */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format a number as Indonesian Rupiah, e.g. Rp5.000.000 */
function formatRupiah($amount): string
{
    $amount = (float) $amount;
    $formatted = number_format($amount, 0, ',', '.');
    return 'Rp' . $formatted;
}

/** Format a percentage with 1 decimal, Indonesian style (comma decimal) */
function formatPercent($value, int $decimals = 1): string
{
    return number_format((float) $value, $decimals, ',', '.') . '%';
}

/** Format a "X,X bulan" style duration */
function formatMonths($value): string
{
    return number_format((float) $value, 1, ',', '.') . ' bulan';
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Look up a system setting with a fallback to the hardcoded default */
function getSetting(string $key)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM system_settings');
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // fall through to defaults only
        }
    }
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    return DEFAULT_ASSUMPTIONS[$key] ?? null;
}

function scoreBand(float $score): array
{
    foreach (SCORE_BANDS as $band) {
        if ($score >= $band['min']) {
            return $band;
        }
    }
    return end(SCORE_BANDS);
}

/** Log an internal error without leaking details to the user (see includes/functions.php error handler) */
function logError(string $context, Throwable $e): void
{
    error_log(sprintf('[%s] %s in %s:%d', $context, $e->getMessage(), $e->getFile(), $e->getLine()));
}
