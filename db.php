<?php
// ============================================================
//  db.php  —  PDO connection singleton
//  Include this file wherever a DB connection is needed.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'portfolio_db');
define('DB_USER', 'root');          // ← change to your MySQL user
define('DB_PASS', '');              // ← change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

function get_pdo(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error instead of printing it
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('<p style="font-family:monospace;color:#f87171;background:#0a0a0a;padding:2rem;">
                 Database connection error. Please try again later.</p>');
        }
    }

    return $pdo;
}
