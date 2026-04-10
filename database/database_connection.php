<?php
// Supabase PostgreSQL connection
// On Railway: set these as environment variables in the Railway dashboard
// DATABASE_URL or individual vars below

// Support DATABASE_URL env var (Railway/Heroku style)
if (getenv('DATABASE_URL')) {
    $url  = parse_url(getenv('DATABASE_URL'));
    $host = $url['host'];
    $port = $url['port'] ?? 5432;
    $database = ltrim($url['path'], '/');
    $user     = $url['user'];
    $password = $url['pass'];
} else {
    // Fallback: individual env vars or hardcoded Supabase values
    $host     = getenv('DB_HOST')     ?: 'db.vksdulfntdhilgpcmalh.supabase.co';
    $database = getenv('DB_NAME')     ?: 'postgres';
    $user     = getenv('DB_USER')     ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'ARVINBALIW666';
    $port     = getenv('DB_PORT')     ?: '5432';
}

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;sslmode=require",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Don't expose credentials in error
    error_log("DB connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
