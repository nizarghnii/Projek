<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireLogin();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/PendaftaranService.php';

$isAdmin     = ($_SESSION['role'] ?? '') === 'admin';
$idMahasiswa = $_SESSION['id_mahasiswa'] ?? null;
$service     = new PendaftaranService((new Database())->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($idMahasiswa === null) {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    $action  = $_POST['action'] ?? '';
    $idKelas = (int) ($_POST['id_kelas'] ?? 0);

    try {
        if ($action === 'daftar') {
            $service->daftar((int) $idMahasiswa, $idKelas);
            $_SESSION['flash'] = ['success', 'Berhasil mendaftar kelas.'];
        } elseif ($action === 'batal') {
            $service->batal((int) $idMahasiswa, $idKelas);
            $_SESSION['flash'] = ['success', 'Pendaftaran kelas dibatalkan.'];
        }
    } catch (DomainException $e) {
        $_SESSION['flash'] = ['danger', $e->getMessage()];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['flash'] = ['danger', 'Terjadi kesalahan sistem. Coba lagi nanti.'];
    }

    header('Location: pendaftaran.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($isAdmin) {
    $semua = $service->getAll();
} elseif ($idMahasiswa !== null) {
    $kelasList = $service->getKelasUntukMahasiswa((int) $idMahasiswa);
    $saya      = $service->getByMahasiswa((int) $idMahasiswa);
}

$pageTitle = 'Pendaftaran';
require __DIR__ . '/../../frontend/partials/header.php';
?>

<h1 class="h4 mb-3"><?= $isAdmin ? 'Seluruh Pendaftaran' : 'Pendaftaran Kelas' ?></h1>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if ($isAdmin): ?>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Mahasiswa</th><th>NIM</th><th>Kelas</th><th>Tanggal</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$semua): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pendaftaran.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($semua as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($p['nama']) ?></td>
                            <td><?= e($p['nim']) ?></td>
                            <td><?= e($p['nama_kelas']) ?></td>
                            <td><?= e($p['tanggal_daftar']) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $p['status'] === 'terdaftar' ? 'success' : 'secondary' ?>">
                                    <?= e($p['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($idMahasiswa === null): ?>

    <div class="alert alert-warning">Akunmu belum terhubung dengan data mahasiswa. Hubungi admin.</div>

<?php else: ?>

    <h2 class="h6 mt-2">Kelas Tersedia</h2>
    <div class="card shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kelas</th>
                        <th class="d-none d-md-table-cell">Deskripsi</th>
                        <th class="text-center">Terisi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kelasList as $k): ?>
                        <?php
                            $penuh = (int) $k['jumlah_daftar'] >= (int) $k['kapasitas'];
                            $sudah = $k['status_saya'] === 'terdaftar';
                        ?>
                        <tr>
                            <td><?= e($k['nama_kelas']) ?></td>
                            <td class="d-none d-md-table-cell"><?= e($k['deskripsi']) ?></td>
                            <td class="text-center"><?= (int) $k['jumlah_daftar'] ?> / <?= (int) $k['kapasitas'] ?></td>
                            <td class="text-end">
                                <?php if ($sudah): ?>
                                    <span class="badge text-bg-success">Sudah terdaftar</span>
                                <?php elseif ($penuh): ?>
                                    <span class="badge text-bg-danger">Penuh</span>
                                <?php else: ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="daftar">
                                        <input type="hidden" name="id_kelas" value="<?= (int) $k['id_kelas'] ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Daftar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h2 class="h6">Riwayat Pendaftaranku</h2>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kelas</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$saya): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Kamu belum mendaftar kelas apa pun.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($saya as $p): ?>
                        <tr>
                            <td><?= e($p['nama_kelas']) ?></td>
                            <td><?= e($p['tanggal_daftar']) ?></td>
                            <td>
                                <span class="badge text-bg-<?= $p['status'] === 'terdaftar' ? 'success' : 'secondary' ?>">
                                    <?= e($p['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($p['status'] === 'terdaftar'): ?>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Batalkan pendaftaran kelas ini?');">
                                        <input type="hidden" name="action" value="batal">
                                        <input type="hidden" name="id_kelas" value="<?= (int) $p['id_kelas'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>