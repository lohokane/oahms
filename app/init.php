<?php
// Common bootstrap for OAHMS

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Redirect to a given path under /public.
 *
 * @param string $path
 * @return void
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Escape output for HTML.
 *
 * @param string|null $value
 * @return string
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

