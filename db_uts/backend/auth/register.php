<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/UserRepository.php';

$errors = [];
$old    = ['username' => '', 'nim' => '', 'nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $nim      = trim($_POST['nim'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $old      = compact('username', 'nim', 'nama', 'email');

    if (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $errors[] = 'Username 4-50 karakter (huruf, angka, underscore).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }
    if (!preg_match('/^[0-9]{5,20}$/', $nim)) {
        $errors[] = 'NIM harus berupa angka (5-20 digit).';
    }
    if ($nama === '' || mb_strlen($nama) > 100) {
        $errors[] = 'Nama wajib diisi (maks 100 karakter).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors) {
        try {
            $repo = new UserRepository((new Database())->getConnection());
            $repo->registerMahasiswa($username, $password, $nim, $nama, $email);
            header('Location: login.php?registered=1');
            exit;
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem. Coba lagi nanti.';
        }
    }
}

$pageTitle = 'Registrasi';
require __DIR__ . '/../../frontend/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-9 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Registrasi Mahasiswa</h1>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama" name="nama"
                               value="<?= e($old['nama']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nim" class="form-label">NIM</label>
                            <input type="text" class="form-control" id="nim" name="nim"
                                   value="<?= e($old['nim']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e($old['email']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($old['username']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirm_password"
                                   name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Daftar</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>