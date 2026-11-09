<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole('admin');
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../classes/MahasiswaRepository.php';

$conn     = (new Database())->getConnection();
$repo     = new MahasiswaRepository($conn);
$userRepo = new UserRepository($conn);

$errors = [];
$form   = ['id_mahasiswa' => '', 'username' => '', 'nim' => '', 'nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $repo->delete((int) ($_POST['id_mahasiswa'] ?? 0));
            $_SESSION['flash'] = ['success', 'Mahasiswa berhasil dihapus.'];
            header('Location: mahasiswa.php');
            exit;
        }

        if (in_array($action, ['create', 'update'], true)) {
            $form = [
                'id_mahasiswa' => (int) ($_POST['id_mahasiswa'] ?? 0) ?: '',
                'username'     => trim($_POST['username'] ?? ''),
                'nim'          => trim($_POST['nim'] ?? ''),
                'nama'         => trim($_POST['nama'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
            ];
            $password = $_POST['password'] ?? '';

            if (!preg_match('/^[0-9]{5,20}$/', $form['nim'])) {
                $errors[] = 'NIM harus berupa angka (5-20 digit).';
            }
            if ($form['nama'] === '' || mb_strlen($form['nama']) > 100) {
                $errors[] = 'Nama wajib diisi (maks 100 karakter).';
            }
            if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 100) {
                $errors[] = 'Format email tidak valid.';
            }
            if ($action === 'create') {
                if (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $form['username'])) {
                    $errors[] = 'Username 4-50 karakter (huruf, angka, underscore).';
                }
                if (strlen($password) < 8) {
                    $errors[] = 'Password minimal 8 karakter.';
                }
            }

            if (!$errors) {
                if ($action === 'create') {
                    $userRepo->registerMahasiswa(
                        $form['username'], $password, $form['nim'], $form['nama'], $form['email']
                    );
                    $_SESSION['flash'] = ['success', 'Mahasiswa berhasil ditambahkan.'];
                } else {
                    $repo->update((int) $form['id_mahasiswa'], $form['nim'], $form['nama'], $form['email']);
                    $_SESSION['flash'] = ['success', 'Data mahasiswa berhasil diperbarui.'];
                }
                header('Location: mahasiswa.php');
                exit;
            }
        }
    } catch (DomainException $e) {
        if ($action === 'delete') {
            $_SESSION['flash'] = ['danger', $e->getMessage()];
            header('Location: mahasiswa.php');
            exit;
        }
        $errors[] = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $errors[] = 'Terjadi kesalahan sistem. Coba lagi nanti.';
    }
} elseif (isset($_GET['edit'])) {
    $row = $repo->getById((int) $_GET['edit']);
    if ($row) {
        $form = [
            'id_mahasiswa' => $row['id_mahasiswa'],
            'username'     => $row['username'],
            'nim'          => $row['nim'],
            'nama'         => $row['nama'],
            'email'        => $row['email'],
        ];
    }
}

$isEdit = $form['id_mahasiswa'] !== '';
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$daftar = $repo->getAll();

$pageTitle = 'Data Mahasiswa';
require __DIR__ . '/../../frontend/partials/header.php';
?>

<h1 class="h4 mb-3">Data Mahasiswa</h1>

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

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $isEdit ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa' ?></h2>
        <form method="post" novalidate>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <input type="hidden" name="id_mahasiswa" value="<?= e((string) $form['id_mahasiswa']) ?>">

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" class="form-control" id="nama" name="nama"
                           value="<?= e($form['nama']) ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="nim" class="form-label">NIM</label>
                    <input type="text" class="form-control" id="nim" name="nim"
                           value="<?= e($form['nim']) ?>" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($form['email']) ?>" required>
                </div>

                <?php if (!$isEdit): ?>
                <div class="col-12 col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= e($form['username']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Minimal 8 karakter.</div>
                </div>
                <?php else: ?>
                <div class="col-12">
                    <div class="form-text">Username akun: <strong><?= e($form['username']) ?></strong> (tidak bisa diubah di sini).</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah' ?></button>
                <?php if ($isEdit): ?>
                    <a href="mahasiswa.php" class="btn btn-outline-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th class="text-center">Kelas Aktif</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data mahasiswa.</td></tr>
                <?php endif; ?>

                <?php foreach ($daftar as $i => $m): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($m['nama']) ?></td>
                        <td><?= e($m['nim']) ?></td>
                        <td><?= e($m['email']) ?></td>
                        <td><?= e($m['username']) ?></td>
                        <td class="text-center"><?= (int) $m['kelas_aktif'] ?></td>
                        <td class="text-end text-nowrap">
                            <a href="mahasiswa.php?edit=<?= (int) $m['id_mahasiswa'] ?>"
                               class="btn btn-sm btn-outline-primary">Ubah</a>
                            <form method="post" class="d-inline"
                                  onsubmit="return confirm('Hapus mahasiswa ini beserta akunnya?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_mahasiswa" value="<?= (int) $m['id_mahasiswa'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>