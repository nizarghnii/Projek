<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/UserRepository.php';

if (isset($_SESSION['id_user'])) {
    header('Location: ../page/home.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        try {
            $repo = new UserRepository((new Database())->getConnection());
            $user = $repo->findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_user']      = (int) $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['id_mahasiswa'] = $repo->findMahasiswaIdByUser((int) $user['id_user']);

                header('Location: ../page/home.php');
                exit;
            }
            $error = 'Username atau password salah.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Terjadi kesalahan sistem. Coba lagi nanti.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/../../frontend/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Login</h1>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">Registrasi berhasil. Silakan login.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($username) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Belum punya akun? <a href="register.php">Registrasi</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../frontend/partials/footer.php'; ?>