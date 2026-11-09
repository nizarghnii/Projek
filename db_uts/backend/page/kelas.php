<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireLogin();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/KelasRepository.php';

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$repo    = new KelasRepository((new Database())->getConnection());

$errors = [];
$form   = ['id_kelas' => '', 'nama_kelas' => '', 'deskripsi' => '', 'kapasitas' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $repo->delete((int) ($_POST['id_kelas'] ?? 0));
            $_SESSION['flash'] = ['success', 'Kelas berhasil dihapus.'];
            header('Location: kelas.php');
            exit;
        }

        if (in_array($action, ['create', 'update'], true)) {
            $form = [
                'id_kelas'   => (int) ($_POST['id_kelas'] ?? 0) ?: '',
                'nama_kelas' => trim($_POST['nama_kelas'] ?? ''),
                'deskripsi'  => trim($_POST['deskripsi'] ?? ''),
                'kapasitas'  => trim($_POST['kapasitas'] ?? ''),
            ];

            if ($form['nama_kelas'] === '' || mb_strlen($form['nama_kelas']) > 100) {
                $errors[] = 'Nama kelas wajib diisi (maks 100 karakter).';
            }
            if (filter_var($form['kapasitas'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]) === false) {
                $errors[] = 'Kapasitas harus angka 1-1000.';
            }

            if (!$errors) {
                $kapasitas = (int) $form['kapasitas'];
                $deskripsi = $form['deskripsi'] === '' ? null : $form['deskripsi'];

                if ($action === 'create') {
                    $repo->create($form['nama_kelas'], $deskripsi, $kapasitas);
                    $_SESSION['flash'] = ['success', 'Kelas berhasil ditambahkan.'];
                } else {
                    $repo->update((int) $form['id_kelas'], $form['nama_kelas'], $deskripsi, $kapasitas);
                    $_SESSION['flash'] = ['success', 'Kelas berhasil diperbarui.'];
                }
                header('Location: kelas.php');
                exit;
            }
        }
    } catch (DomainException $e) {
        if (($action ?? '') === 'delete') {
            $_SESSION['flash'] = ['danger', $e->getMessage()];
            header('Location: kelas.php');
            exit;
        }
        $errors[] = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $errors[] = 'Terjadi kesalahan sistem. Coba lagi nanti.';
    }
} elseif ($isAdmin && isset($_GET['edit'])) {
    $row = $repo->getById((int) $_GET['edit']);
    if ($row) {
        $form = [
            'id_kelas'   => $row['id_kelas'],
            'nama_kelas' => $row['nama_kelas'],
            'deskripsi'  => $row['deskripsi'] ?? '',
            'kapasitas'  => (string) $row['kapasitas'],
        ];
    }
}

$isEdit    = $form['id_kelas'] !== '';
$flash     = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$daftar    = $repo->getAll();

$pageTitle = 'Kelas';
require __DIR__ . '/../../frontend/partials/header.php';
?>

<h1 class="h4 mb-3"><?= $isAdmin ? 'Kelola Kelas' : 'Daftar Kelas' ?></h1>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash[0]) ?>"><?= e($flash[1]) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $isEdit ? 'Ubah Kelas' : 'Tambah Kelas' ?></h2>
        <form method="post" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <input type="hidden" name="id_kelas" value="<?= e((string) $form['id_kelas']) ?>">

            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" class="form-control" id="nama_kelas" name="nama_kelas"
                           value="<?= e($form['nama_kelas']) ?>" required>
                </div>
                <div class="col-12 col-md-5">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <input type="text" class="form-control" id="deskripsi" name="deskripsi"
                           value="<?= e($form['deskripsi']) ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label for="kapasitas" class="form-label">Kapasitas</label>
                    <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                           min="1" value="<?= e($form['kapasitas']) ?>" required>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah' ?></button>
                <?php if ($isEdit): ?>
                    <a href="kelas.php" class="btn btn-outline-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Kelas</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Terisi</th>
                    <th class="text-center">Status</th>
                    <?php if ($isAdmin): ?><th class="text-end">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada kelas.</td></tr>
                <?php endif; ?>

                <?php foreach ($daftar as $i => $k): ?>
                    <?php $sisa = (int) $k['kapasitas'] - (int) $k['jumlah_daftar']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($k['nama_kelas']) ?></td>
                        <td><?= e($k['deskripsi']) ?></td>
                        <td class="text-center"><?= (int) $k['jumlah_daftar'] ?> / <?= (int) $k['kapasitas'] ?></td>
                        <td class="text-center">
                            <?php if ($sisa <= 0): ?>
                                <span class="badge text-bg-danger">Penuh</span>
                            <?php else: ?>
                                <span class="badge text-bg-success">Sisa <?= $sisa ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td class="text-end text-nowrap">
                            <a href="kelas.php?edit=<?= (int) $k['id_kelas'] ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Hapus kelas ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_kelas" value="<?= (int) $k['id_kelas'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>