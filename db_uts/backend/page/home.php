<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireLogin();

$pageTitle = 'Beranda';
require __DIR__ . '/../../frontend/partials/header.php';

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>

<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm">
    <h1 class="h3">Halo, <?= e($_SESSION['username']) ?>!</h1>
    <p class="mb-0 text-muted">
        Aplikasi pendaftaran kelas: lihat kelas yang tersedia, daftar, dan kelola pendaftaranmu.
    </p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Kelas</h2>
                <p class="text-muted">Lihat daftar kelas, kapasitas, dan sisa kuota.</p>
                <a href="kelas.php" class="btn btn-primary">Buka</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Pendaftaran</h2>
                <p class="text-muted">Daftar atau batalkan kelas yang kamu ikuti.</p>
                <a href="pendaftaran.php" class="btn btn-primary">Buka</a>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Data Mahasiswa</h2>
                <p class="text-muted">Kelola data mahasiswa (khusus admin).</p>
                <a href="mahasiswa.php" class="btn btn-primary">Buka</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>