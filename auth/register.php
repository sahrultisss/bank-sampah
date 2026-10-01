<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = null;
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $role      = $_POST['role'] ?? '';

    if ($nama === '' || $username === '' || $password === '' || !in_array($role, ['guru', 'siswa'], true)) {
        $error = 'Semua kolom wajib diisi (peran hanya boleh Guru atau Siswa).';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $pdo = getConnection();

        $cek = $pdo->prepare('SELECT id_user FROM users WHERE username = ?');
        $cek->execute([$username]);

        if ($cek->fetch()) {
            $error = 'Username sudah dipakai, coba yang lain.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (nama, username, password, role, saldo) VALUES (?, ?, ?, ?, 0)'
            );
            $stmt->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);

            setFlash('success', 'Pendaftaran berhasil! Silakan masuk dengan akun barumu.');
            redirect('auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Bank Sampah</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body>
    <div class="paper-bg">
        <div class="passbook" style="max-width:440px;">
            <div class="passbook-stamp">BS</div>
            <h1>Daftar Akun</h1>
            <p class="subtitle">Buka buku tabungan sampahmu sendiri</p>

            <?php if ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= e($old['nama'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($old['username'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="role">Saya adalah</label>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih --</option>
                        <option value="siswa" <?= ($old['role'] ?? '') === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="guru" <?= ($old['role'] ?? '') === 'guru' ? 'selected' : '' ?>>Guru</option>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="field">
                        <label for="password2">Ulangi Password</label>
                        <input type="password" id="password2" name="password2" required>
                    </div>
                </div>
                <button type="submit" class="btn stamp block">Daftar</button>
            </form>

            <p class="field-hint" style="text-align:center; margin-top:18px;">
                Sudah punya akun? <a href="<?= baseUrl('auth/login.php') ?>">Masuk di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
