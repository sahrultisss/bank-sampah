<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengepul');

$pdo = getConnection();
$myId = currentUser()['id'];

// FIX: semua statistik sekarang milik pengepul yang login (prepared statement)
$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([$myId]);
$saldo = $stmt->fetch()['saldo'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM transaksi WHERE tipe = 'jual_pengepul' AND id_user = ? AND DATE(tanggal) = CURDATE()");
$stmt->execute([$myId]);
$totalHariIni = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(berat_kg),0) kg FROM transaksi WHERE tipe = 'jual_pengepul' AND id_user = ? AND status = 'disetujui'");
$stmt->execute([$myId]);
$totalKg = $stmt->fetch()['kg'];

$stmt = $pdo->prepare("
    SELECT t.*, s.jenis_sampah
    FROM transaksi t
    JOIN sampah s ON s.id_sampah = t.id_sampah
    WHERE t.tipe = 'jual_pengepul' AND t.id_user = ?
    ORDER BY t.tanggal DESC LIMIT 10
");
$stmt->execute([$myId]);
$transaksiBeli = $stmt->fetchAll();

$pageTitle = 'Dashboard Pengepul';
$active = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Halo, <?= e($user['nama']) ?></div>
        <h1>Panel Pengepul</h1>
    </div>
</div>

<div class="stat-row">
    <div class="stat stamp-accent">
        <div class="stat-label">Saldo Kamu</div>
        <div class="stat-value"><?= rupiah($saldo) ?></div>
    </div>
    <div class="stat sage-accent">
        <div class="stat-label">Pembelian Hari Ini</div>
        <div class="stat-value"><?= number_format($totalHariIni) ?></div>
    </div>
    <div class="stat sage-accent">
        <div class="stat-label">Total Sampah Dibeli</div>
        <div class="stat-value"><?= number_format($totalKg, 1) ?> kg</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2 class="mt-0">Mulai Transaksi</h2>
    </div>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="<?= baseUrl('pengepul/transaksi.php') ?>" class="btn sage">+ Beli Sampah dari Admin</a>
        <a href="<?= baseUrl('pengepul/topup.php') ?>" class="btn stamp">Top Up Saldo</a>
        <a href="<?= baseUrl('pengepul/sampah.php') ?>" class="btn outline">Lihat Stok &amp; Harga</a>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2 class="mt-0">Riwayat Pembelian Sampah</h2>
    </div>
    <table class="ledger">
        <thead>
            <tr>
                <th>Jenis Sampah</th>
                <th class="num">Berat</th>
                <th>Tanggal</th>
                <th class="num">Total Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transaksiBeli)): ?>
                <tr><td colspan="4" class="text-soft">Belum ada transaksi pembelian.</td></tr>
            <?php else: ?>
                <?php foreach ($transaksiBeli as $tb): ?>
                    <tr>
                        <td><?= e($tb['jenis_sampah']) ?></td>
                        <td class="num"><?= number_format($tb['berat_kg'], 2) ?> kg</td>
                        <td><?= tanggalIndo($tb['tanggal']) ?></td>
                        <td class="num"><?= rupiah($tb['total_rp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
