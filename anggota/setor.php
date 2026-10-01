<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['guru', 'siswa']); // FIX: sebelumnya tidak ada proteksi login/role

$pdo = getConnection();
$user = currentUser();
$userId = $user['id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idSampah = (int)($_POST['id_sampah'] ?? 0);
    $berat    = (float)($_POST['berat_kg'] ?? 0);

    if (!$idSampah || $berat <= 0) {
        $error = 'Pilih jenis sampah dan masukkan perkiraan berat lebih dari 0.';
    } else {
        $stmt = $pdo->prepare("SELECT harga_per_kg FROM sampah WHERE id_sampah = ?");
        $stmt->execute([$idSampah]);
        $sampah = $stmt->fetch();

        if ($sampah) {
            $total = $berat * $sampah['harga_per_kg'];

            $stmt = $pdo->prepare("INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, status, tanggal) VALUES (?, ?, 'beli_user', ?, ?, 'pending', NOW())");
            $stmt->execute([$userId, $idSampah, $berat, $total]);

            setFlash('success', 'Pengajuan setoran sampah berhasil disubmit! Silakan bawa sampah ke Admin untuk diverifikasi & di-ACC.');
            redirect('anggota/riwayat.php');
        } else {
            $error = 'Jenis sampah tidak ditemukan.';
        }
    }
}

$daftarSampah = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

$pageTitle = 'Setor Sampah';
$active    = 'setor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Pengajuan Setoran</div>
        <h1>Setor Sampah</h1>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="panel" style="max-width:520px;">
    <h3>Form Setor Sampah Online</h3>
    <form method="POST">
        <div class="field">
            <label>Jenis Sampah</label>
            <select name="id_sampah" required>
                <option value="">-- Pilih Sampah --</option>
                <?php foreach ($daftarSampah as $s): ?>
                    <option value="<?= (int)$s['id_sampah'] ?>">
                        <?= e($s['jenis_sampah']) ?> (<?= rupiah($s['harga_per_kg']) ?>/kg)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Perkiraan Berat (kg)</label>
            <input type="number" step="0.01" min="0.01" name="berat_kg" placeholder="Contoh: 2.5" required>
        </div>

        <button type="submit" class="btn stamp">Kirim Pengajuan Setoran</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
