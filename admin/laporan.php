<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
if ($bulan < 1 || $bulan > 12) $bulan = (int)date('n');

// FIX: rekap hanya setoran user yang sudah disetujui (kondisi ada di JOIN agar jenis tanpa setoran tetap tampil 0)
$stmt = $pdo->prepare(
    "SELECT s.jenis_sampah, COALESCE(SUM(t.berat_kg),0) total_kg, COALESCE(SUM(t.total_rp),0) total_nilai
     FROM sampah s
     LEFT JOIN transaksi t ON t.id_sampah = s.id_sampah
          AND t.tipe = 'beli_user' AND t.status = 'disetujui'
          AND MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?
     GROUP BY s.id_sampah, s.jenis_sampah
     ORDER BY total_kg DESC"
);
$stmt->execute([$bulan, $tahun]);
$rekapJenis = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_rp),0) total FROM transaksi
     WHERE tipe = 'beli_user' AND status = 'disetujui' AND MONTH(tanggal)=? AND YEAR(tanggal)=?"
);
$stmt->execute([$bulan, $tahun]);
$totalSetor = $stmt->fetch()['total'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_rp),0) total FROM transaksi
     WHERE tipe = 'jual_pengepul' AND status = 'disetujui' AND MONTH(tanggal)=? AND YEAR(tanggal)=?"
);
$stmt->execute([$bulan, $tahun]);
$totalJual = $stmt->fetch()['total'];

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(nominal),0) total FROM tarik_saldo
     WHERE status='disetujui' AND MONTH(tanggal)=? AND YEAR(tanggal)=?"
);
$stmt->execute([$bulan, $tahun]);
$totalTarik = $stmt->fetch()['total'];

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$pageTitle = 'Laporan';
$active = 'laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar no-print">
    <div>
        <div class="eyebrow">Rekap bulanan</div>
        <h1>Laporan Bank Sampah</h1>
    </div>
    <button class="btn stamp" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="panel no-print">
    <form method="GET" class="form-grid" style="align-items:end;">
        <div class="field">
            <label>Bulan</label>
            <select name="bulan">
                <?php foreach ($namaBulan as $n => $label): ?>
                    <option value="<?= $n ?>" <?= $n === $bulan ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label>Tahun</label><input type="number" name="tahun" value="<?= $tahun ?>"></div>
        <div class="field"><button class="btn outline" type="submit">Tampilkan</button></div>
    </form>
</div>

<div class="panel">
    <h2 class="mt-0">Laporan Periode <?= $namaBulan[$bulan] ?> <?= $tahun ?></h2>
    <p class="text-soft" style="margin-top:-8px;">Dicetak pada <?= tanggalIndo(time()) ?></p>

    <div class="stat-row">
        <div class="stat sage-accent">
            <div class="stat-label">Total Setoran (Disetujui)</div>
            <div class="stat-value"><?= rupiah($totalSetor) ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Penjualan ke Pengepul</div>
            <div class="stat-value"><?= rupiah($totalJual) ?></div>
        </div>
        <div class="stat stamp-accent">
            <div class="stat-label">Total Penarikan (Disetujui)</div>
            <div class="stat-value"><?= rupiah($totalTarik) ?></div>
        </div>
    </div>

    <h3>Rekap Setoran per Jenis Sampah</h3>
    <table class="ledger">
        <thead><tr><th>Jenis Sampah</th><th class="num">Total Berat</th><th class="num">Total Nilai</th></tr></thead>
        <tbody>
            <?php foreach ($rekapJenis as $r): ?>
                <tr>
                    <td><?= e($r['jenis_sampah']) ?></td>
                    <td class="num"><?= number_format($r['total_kg'], 2) ?> kg</td>
                    <td class="num"><?= rupiah($r['total_nilai']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="chart-box no-print" style="margin-top:20px;">
        <canvas id="grafikLaporan" height="90"></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('grafikLaporan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($rekapJenis, 'jenis_sampah')) ?>,
        datasets: [{
            label: 'Berat (kg)',
            data: <?= json_encode(array_map('floatval', array_column($rekapJenis, 'total_kg'))) ?>,
            backgroundColor: '#7C8C5C',
            borderRadius: 3,
            maxBarThickness: 40
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
