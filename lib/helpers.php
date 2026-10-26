<?php
// lib/helpers.php

function cfg(): array {
    static $c = null;
    if ($c === null) {
        $c = require __DIR__ . '/../config.php';
    }
    return $c;
}

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s, ?string $encoding = null): int { return strlen($s); }
}
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Elimina saltos de linea y caracteres que puedan inyectar cabeceras.
function clean_header_value(string $s): string {
    $s = str_replace(["\r", "\n", "\t"], ' ', $s);
    // Elimina comillas, angulos y comas para evitar inyeccion en From/Reply-To
    $s = str_replace(['"', '<', '>', ','], '', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

function valid_email(string $e): bool {
    $e = trim($e);
    if ($e === '' || strlen($e) > 254) return false;
    return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
}

// Rate limit desactivado (uso personal). Se mantiene la funcion para compatibilidad.
function rate_limit_ok(string $ip): bool {
    return true;
}

function client_ip(): string {
    // En hosting compartido suele venir en REMOTE_ADDR. No confiamos en X-Forwarded-For.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Token CSRF simple en sesion
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(string $t): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

