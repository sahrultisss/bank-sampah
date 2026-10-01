<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['guru', 'siswa']);

$pdo = getConnection();
$myId = currentUser()['id'];

$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([$myId]);
$saldo = $stmt->fetch()['saldo'];

// FIX: hanya setoran yang sudah disetujui admin
$stmt = $pdo->prepare("SELECT COALESCE(SUM(berat_kg),0) total_kg FROM transaksi WHERE id_user = ? AND status = 'disetujui'");
$stmt->execute([$myId]);
$totalKg = $stmt->fetch()['total_kg'];

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM transaksi WHERE id_user = ? AND status = 'pending'");
$stmt->execute([$myId]);
$pendingSetor = $stmt->fetch()['c'];

$stmt = $pdo->prepare(
    "SELECT t.*, s.jenis_sampah FROM transaksi t
     JOIN sampah s ON s.id_sampah = t.id_sampah
     WHERE t.id_user = ? ORDER BY t.tanggal DESC LIMIT 6"
);
$stmt->execute([$myId]);
$riwayatTerbaru = $stmt->fetchAll();

$pageTitle = 'Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Halo, <?= e($user['nama']) ?></div>
        <h1>Buku Tabungan Sampahmu</h1>
    </div>
</div>

<div class="stat-row">
    <div class="stat stamp-accent">
        <div class="stat-label">Saldo Kamu</div>
        <div class="stat-value"><?= rupiah($saldo) ?></div>
    </div>
    <div class="stat sage-accent">
        <div class="stat-label">Total Sampah Disetor</div>
        <div class="stat-value"><?= number_format($totalKg, 1) ?> kg</div>
    </div>
</div>

<?php if ($pendingSetor > 0): ?>
    <div class="alert info">
        Kamu punya <strong><?= $pendingSetor ?></strong> setoran yang menunggu verifikasi admin.
    </div>
<?php endif; ?>

<div class="panel">
   <div style="display:flex; gap:12px; flex-wrap:wrap;">
    <a href="<?= baseUrl('anggota/setor.php') ?>" class="btn sage">+ Setor Sampah</a>
    <a href="<?= baseUrl('anggota/tarik_saldo.php') ?>" class="btn stamp">Ajukan Tarik Saldo</a>
    <a href="<?= baseUrl('anggota/riwayat.php') ?>" class="btn outline">Lihat Semua Riwayat</a>
</div>
</div>

<div class="panel">
    <div class="panel-head"><h2 class="mt-0">Setoran Terakhir</h2></div>
    <table class="ledger">
        <thead><tr><th>Jenis Sampah</th><th class="num">Berat</th><th>Tanggal</th><th>Status</th><th class="num">Nominal</th></tr></thead>
        <tbody>
            <?php if (empty($riwayatTerbaru)): ?>
                <tr><td colspan="5" class="text-soft">Belum ada transaksi. Yuk mulai menabung sampah!</td></tr>
            <?php endif; ?>
            <?php foreach ($riwayatTerbaru as $t): ?>
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
