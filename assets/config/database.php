<?php
if (basename($_SERVER["PHP_SELF"] ?? "") === "database.php") {
    http_response_code(403);
    die("403 - Access Forbidden");
}

function maple_env(string $name, ?string $default = null): string {
    $value = getenv($name);
    if ($value === false || $value === "") {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Required environment variable {$name} is not configured.");
    }
    return $value;
}

$host = [
    'hostname' => maple_env('MAPLE_DB_HOST', '127.0.0.1'),
    'user' => maple_env('MAPLE_DB_USER'),
    'password' => maple_env('MAPLE_DB_PASS', ''),
    'database' => maple_env('MAPLE_DB_NAME', 'cosmic'),
    'port' => (int) maple_env('MAPLE_DB_PORT', '3306'),
];

$prefix = maple_env('MAPLE_DB_PREFIX', 'bit_');
if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
    throw new RuntimeException('MAPLE_DB_PREFIX may only contain letters, numbers, and underscores.');
}

$loginport = maple_env('MAPLE_LOGIN_PORT', '8484');
$worldport = maple_env('MAPLE_CHANNEL_PORT', '7575');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(
    $host['hostname'],
    $host['user'],
    $host['password'],
    $host['database'],
    $host['port']
);
$mysqli->set_charset('utf8mb4');
