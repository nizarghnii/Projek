<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    if (!isset($_SESSION['id_user'])) {
        $projectRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $baseUrl     = rtrim(str_replace($docRoot, '', $projectRoot), '/');

        header('Location: ' . $baseUrl . '/backend/auth/login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}