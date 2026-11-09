<?php
$pageTitle = 'Beranda';
require __DIR__ . '/frontend/partials/header.php';
?>

<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm text-center">
    <h1 class="display-6 fw-semibold">Aplikasi Pendaftaran Kelas</h1>
    <p class="lead text-muted mb-4">
        Lihat kelas yang tersedia, daftar, dan kelola pendaftaranmu dalam satu tempat.
    </p>

    <?php if (isset($_SESSION['id_user'])): ?>
        <a href="<?= $baseUrl ?>/backend/page/home.php" class="btn btn-primary btn-lg">Masuk ke Dashboard</a>
    <?php else: ?>
        <a href="<?= $baseUrl ?>/backend/auth/login.php" class="btn btn-primary btn-lg me-2">Login</a>
        <a href="<?= $baseUrl ?>/backend/auth/register.php" class="btn btn-outline-primary btn-lg">Registrasi</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm"><div class="card-body">
            <h2 class="h5">Lihat Kelas</h2>
            <p class="text-muted mb-0">Cek daftar kelas beserta kapasitas dan sisa kuota secara langsung.</p>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm"><div class="card-body">
            <h2 class="h5">Daftar &amp; Batal</h2>
            <p class="text-muted mb-0">Daftar ke kelas pilihan, atau batalkan kapan saja. Kuota ikut diperbarui otomatis.</p>
        </div></div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm"><div class="card-body">
            <h2 class="h5">Kelola Data</h2>
            <p class="text-muted mb-0">Admin mengelola data kelas dan mahasiswa dari satu dashboard.</p>
        </div></div>
    </div>
</div>

<?php require __DIR__ . '/frontend/partials/footer.php'; ?>