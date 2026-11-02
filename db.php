<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Local development configuration. Environment variables take precedence.
// The .env file is deliberately excluded from Git.
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        [$key, $value] = array_map('trim', $parts);
        if (!preg_match('/^DB_(HOST|PORT|NAME|USER|PASSWORD)$/', $key)) continue;
        if (getenv($key) === false) putenv($key . '=' . trim($value, "\"'"));
    }
}
$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD');
$pass = $pass === false ? '' : $pass;
$dbname = getenv('DB_NAME') ?: 'bank';
try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Sino Money database connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check local configuration and MySQL service.');
}
