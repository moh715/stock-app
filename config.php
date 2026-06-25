<?php

// ── Load .env ─────────────────────────────────────────────────────────────────
// Reads key=value pairs from .env in the project root.
// Lines starting with # are comments and are ignored.

function load_env(string $path): void {
    if (!file_exists($path)) {
        die('Missing .env file at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) continue;

        // Split on the first = only
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

        $key   = trim($key);
        $value = trim($value);

        if ($key !== '') {
            putenv("$key=$value");
        }
    }
}

load_env(__DIR__ . '/.env');

// ── API Keys ──────────────────────────────────────────────────────────────────
define('TWELVE_DATA_KEY', getenv('TWELVE_DATA_KEY') ?: '');
define('GEMINI_API_KEY',  getenv('GEMINI_API_KEY')  ?: '');
define('NEWS_API_KEY',    getenv('NEWS_API_KEY')     ?: '');

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'stockapp');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── DB Connection (singleton) ─────────────────────────────────────────────────
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }

    return $pdo;
}

// ── Auth Helpers ──────────────────────────────────────────────────────────────
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user_id(): int|null {
    return $_SESSION['user_id'] ?? null;
}