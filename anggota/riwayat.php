<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['guru', 'siswa']);

$pdo = getConnection();
$myId = currentUser()['id'];

$stmt = $pdo->prepare(
    "SELECT t.*, s.jenis_sampah FROM transaksi t
     JOIN sampah s ON s.id_sampah = t.id_sampah
     WHERE t.id_user = ? ORDER BY t.tanggal DESC"
);
$stmt->execute([$myId]);
$semuaTransaksi = $stmt->fetchAll();

$pageTitle = 'Riwayat Setoran';
$active = 'transaksi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Buku tabungan</div>
        <h1>Riwayat Setoran</h1>
    </div>
</div>

<div class="panel">
    <table class="ledger">
        <thead><tr><th>Jenis Sampah</th><th class="num">Berat</th><th>Tanggal</th><th>Status</th><th class="num">Nominal</th></tr></thead>
        <tbody>
            <?php if (empty($semuaTransaksi)): ?>
                <tr><td colspan="5" class="text-soft">Belum ada transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($semuaTransaksi as $t): ?>
                <tr>
                    <td><?= e($t['jenis_sampah']) ?></td>
                    <td class="num"><?= number_format($t['berat_kg'], 2) ?> kg</td>
                    <td><?= tanggalIndo($t['tanggal']) ?></td>
                    <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                    <td class="num"><?= $t['status'] === 'disetujui' ? '+ ' : '' ?><?= rupiah($t['total_rp']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
