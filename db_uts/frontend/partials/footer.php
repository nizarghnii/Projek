</main>

<footer class="bg-dark text-white-50 text-center py-3 mt-auto">
    <small>&copy; <?= date('Y') ?> Pendaftaran Kelas &middot; UTS Pemrograman Web</small>
</footer>

<?php
if (!isset($baseUrl)) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $baseDir = preg_match('#/frontend(?:/|$)#', $scriptPath)
        ? dirname($scriptPath, 2)
        : dirname($scriptPath);
    $baseUrl = rtrim($baseDir, '/');
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/frontend/js/main.js"></script>
</body>
</html>