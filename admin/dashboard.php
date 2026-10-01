<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();

$totalAnggota = $pdo->query("SELECT COUNT(*) c FROM users WHERE role IN ('guru','siswa')")->fetch()['c'];
$totalSaldo   = $pdo->query("SELECT COALESCE(SUM(saldo),0) s FROM users WHERE role IN ('guru','siswa')")->fetch()['s'];

// FIX: hanya setoran user yang sudah disetujui (tanpa penjualan ke pengepul / pending)
$totalSetorBulanIni = $pdo->query(
    "SELECT COALESCE(SUM(total_rp),0) s FROM transaksi
     WHERE tipe = 'beli_user' AND status = 'disetujui'
       AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())"
)->fetch()['s'];
$totalStok = $pdo->query("SELECT COALESCE(SUM(stok_kg),0) s FROM sampah")->fetch()['s'];
$pendingTarik  = $pdo->query("SELECT COUNT(*) c FROM tarik_saldo WHERE status = 'pending'")->fetch()['c'];
$pendingTopup  = $pdo->query("SELECT COUNT(*) c FROM topup WHERE status = 'pending'")->fetch()['c'];
$pendingSetor  = $pdo->query("SELECT COUNT(*) c FROM transaksi WHERE status = 'pending' AND tipe = 'beli_user'")->fetch()['c'];

$transaksiTerbaru = $pdo->query(
    "SELECT t.*, u.nama AS nama_user, s.jenis_sampah
     FROM transaksi t
     JOIN users u ON u.id_user = t.id_user
     JOIN sampah s ON s.id_sampah = t.id_sampah
     WHERE t.status = 'disetujui'
     ORDER BY t.tanggal DESC LIMIT 8"
)->fetchAll();

$grafik = $pdo->query("SELECT jenis_sampah, stok_kg FROM sampah ORDER BY stok_kg DESC")->fetchAll();

$grafikPenjualan = $pdo->query("
    SELECT s.jenis_sampah, COALESCE(SUM(t.berat_kg),0) total_jual
    FROM sampah s
    LEFT JOIN transaksi t ON t.id_sampah = s.id_sampah AND t.tipe = 'jual_pengepul' AND t.status = 'disetujui'
    GROUP BY s.id_sampah, s.jenis_sampah
")->fetchAll();

$pageTitle = 'Dashboard Admin';
$active = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Halo, <?= e($user['nama']) ?></div>
        <h1>Ringkasan Bank Sampah</h1>
    </div>
</div>

<div class="stat-row">
    <div class="stat">
        <div class="stat-label">Total Guru &amp; Siswa</div>
        <div class="stat-value"><?= number_format($totalAnggota) ?></div>
    </div>
    <div class="stat stamp-accent">
        <div class="stat-label">Total Saldo Beredar</div>
        <div class="stat-value"><?= rupiah($totalSaldo) ?></div>
    </div>
    <div class="stat sage-accent">
        <div class="stat-label">Setoran Bulan Ini</div>
        <div class="stat-value"><?= rupiah($totalSetorBulanIni) ?></div>
    </div>
    <div class="stat sage-accent">
        <div class="stat-label">Stok Sampah Saat Ini</div>
        <div class="stat-value"><?= number_format($totalStok, 1) ?> kg</div>
    </div>
</div>

<?php if ($pendingSetor > 0): ?>
    <div class="alert info">
        Ada <strong><?= $pendingSetor ?></strong> pengajuan setoran sampah yang menunggu ACC.
        <a href="<?= baseUrl('admin/transaksi.php') ?>">Lihat &rarr;</a>
    </div>
<?php endif; ?>
<?php if ($pendingTarik > 0): ?>
    <div class="alert info">
        Ada <strong><?= $pendingTarik ?></strong> pengajuan tarik saldo yang menunggu persetujuan.
        <a href="<?= baseUrl('admin/tarik_saldo.php') ?>">Lihat &rarr;</a>
    </div>
<?php endif; ?>
<?php if ($pendingTopup > 0): ?>
    <div class="alert info">
        Ada <strong><?= $pendingTopup ?></strong> pengajuan top up pengepul yang menunggu persetujuan.
        <a href="<?= baseUrl('admin/topup.php') ?>">Lihat &rarr;</a>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <h2 class="mt-0">Stok Sampah per Jenis</h2>
    </div>
    <div class="chart-box">
        <canvas id="grafikStok" height="90"></canvas>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2 class="mt-0">Grafik Penjualan Sampah ke Pengepul (kg)</h2></div>
    <div class="chart-box">
        <canvas id="grafikPenjualan" height="90"></canvas>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2 class="mt-0">Transaksi Terbaru</h2>
        <a href="<?= baseUrl('admin/transaksi.php') ?>" class="btn outline small">Lihat semua</a>
    </div>
    <table class="ledger">
        <thead><tr><th>Nama</th><th>Tipe</th><th>Jenis Sampah</th><th class="num">Berat</th><th>Tanggal</th><th class="num">Nominal</th></tr></thead>
        <tbody>
            <?php if (empty($transaksiTerbaru)): ?>
                <tr><td colspan="6" class="text-soft">Belum ada transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($transaksiTerbaru as $t): ?>
                <tr>
                    <td><?= e($t['nama_user']) ?></td>
                    <td>
                        <?php if ($t['tipe'] === 'beli_user'): ?>
                            <span class="badge setor">Beli dari User</span>
                        <?php else: ?>
                            <span class="badge tarik">Jual ke Pengepul</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($t['jenis_sampah']) ?></td>
                    <td class="num"><?= number_format($t['berat_kg'], 2) ?> kg</td>
                    <td><?= tanggalIndo($t['tanggal']) ?></td>
                    <td class="num"><?= rupiah($t['total_rp']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('grafikStok'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($grafik, 'jenis_sampah')) ?>,
        datasets: [{
            label: 'Stok (kg)',
            data: <?= json_encode(array_map('floatval', array_column($grafik, 'stok_kg'))) ?>,
            backgroundColor: '#A6432E',
            borderRadius: 3,
            maxBarThickness: 40
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#C9BE9E33' } }, x: { grid: { display: false } } }
    }
});
new Chart(document.getElementById('grafikPenjualan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($grafikPenjualan, 'jenis_sampah')) ?>,
        datasets: [{
            label: 'Terjual ke Pengepul (kg)',
            data: <?= json_encode(array_map('floatval', array_column($grafikPenjualan, 'total_jual'))) ?>,
            backgroundColor: '#7C8C5C',
            borderRadius: 3,
            maxBarThickness: 40
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#C9BE9E33' } }, x: { grid: { display: false } } }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
