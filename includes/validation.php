<?php
/**
 * Input validation helpers
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

function v_required($value): bool
{
    return $value !== null && trim((string) $value) !== '';
}

function v_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function v_min_length(string $value, int $min): bool
{
    return mb_strlen($value) >= $min;
}

function v_numeric($value): bool
{
    return is_numeric($value);
}

function v_positive_or_zero($value): bool
{
    return is_numeric($value) && (float) $value >= 0;
}

function v_int_between($value, int $min, int $max): bool
{
    return is_numeric($value) && (int) $value >= $min && (int) $value <= $max;
}

/**
 * Sanitize a rupiah-formatted input like "5.000.000" or "5,000,000" into a float.
 */
function parseRupiah($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }
    $clean = preg_replace('/[^0-9]/', '', (string) $value);
    return $clean === '' ? 0.0 : (float) $clean;
}

function cleanString(?string $value): string
{
    return trim(strip_tags((string) $value));
}
