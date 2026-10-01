<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengepul');

$pdo = getConnection();
$daftarSampah = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

$pageTitle = 'Stok Sampah';
$active = 'sampah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Referensi</div>
        <h1>Stok &amp; Harga Sampah</h1>
    </div>
</div>

<div class="panel">
    <table class="ledger">
        <thead><tr><th>Jenis Sampah</th><th class="num">Harga / kg</th><th class="num">Stok Saat Ini</th></tr></thead>
        <tbody>
            <?php if (empty($daftarSampah)): ?>
                <tr><td colspan="3" class="text-soft">Belum ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarSampah as $s): ?>
                <tr>
                    <td><?= e($s['jenis_sampah']) ?></td>
                    <td class="num"><?= rupiah($s['harga_per_kg']) ?></td>
                    <td class="num"><?= number_format($s['stok_kg'], 2) ?> kg</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
