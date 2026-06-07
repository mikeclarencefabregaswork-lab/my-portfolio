<?php
// ============================================================
//  src/admin/csrf.php — CSRF token generation + validation
// ============================================================

declare(strict_types=1);

function ensure_csrf_token(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function validate_csrf_from_request(): void
{
    if (
        empty($_POST['csrf_token'])
        || !hash_equals(csrf_token(), (string)$_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}
