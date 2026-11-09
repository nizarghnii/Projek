<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hitung base URL otomatis, jadi tidak bergantung pada nama folder project
$projectRoot = str_replace('\\', '/', dirname(__DIR__, 2));
$docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$baseUrl     = rtrim(str_replace($docRoot, '', $projectRoot), '/');

$pageTitle = $pageTitle ?? 'Pendaftaran Kelas';

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Pendaftaran Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/frontend/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<nav class="navbar navbar-expand-md navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= $baseUrl ?>/index.php">Pendaftaran Kelas</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['id_user'])): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/page/home.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/page/kelas.php">Kelas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/page/pendaftaran.php">Pendaftaran</a></li>

                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/page/mahasiswa.php">Data Mahasiswa</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/auth/logout.php">Logout (<?= e($_SESSION['username'] ?? '') ?>)</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/backend/auth/register.php">Registrasi</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">