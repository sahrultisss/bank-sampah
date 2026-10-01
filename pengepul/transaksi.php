<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengepul');

$pdo = getConnection();
$myId = currentUser()['id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idSampah = (int)($_POST['id_sampah'] ?? 0);
    $berat    = (float)($_POST['berat_kg'] ?? 0);

    if (!$idSampah || $berat <= 0) {
        $error = 'Pilih jenis sampah dan masukkan berat lebih dari 0.';
    } else {
        $pdo->beginTransaction();
        try {
            // Kunci baris supaya cek stok & saldo aman dari klik ganda / transaksi bersamaan
            $stmt = $pdo->prepare('SELECT * FROM sampah WHERE id_sampah = ? FOR UPDATE');
            $stmt->execute([$idSampah]);
            $sampah = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ? FOR UPDATE');
            $stmt->execute([$myId]);
            $saldoSekarang = (float)$stmt->fetch()['saldo'];

            if (!$sampah) {
                $error = 'Jenis sampah tidak ditemukan.';
                $pdo->rollBack();
            } elseif ($sampah['stok_kg'] < $berat) {
                $error = 'Stok sampah di Admin tidak mencukupi. Stok tersedia: ' . number_format($sampah['stok_kg'], 2) . ' kg';
                $pdo->rollBack();
            } else {
                $totalHarga = $berat * $sampah['harga_per_kg'];

                if ($saldoSekarang < $totalHarga) {
                    $error = 'Saldo kamu tidak cukup untuk membeli sampah ini (' . rupiah($totalHarga) . '). Silakan Top Up terlebih dahulu.';
                    $pdo->rollBack();
                } else {
                    // FIX: status langsung 'disetujui' (sebelumnya default 'pending' sehingga bisa di-ACC admin dan menambah saldo/stok lagi)
                    $pdo->prepare("INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, status, tanggal) VALUES (?, ?, 'jual_pengepul', ?, ?, 'disetujui', NOW())")
                        ->execute([$myId, $idSampah, $berat, $totalHarga]);
                    $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id_user = ?")->execute([$totalHarga, $myId]);
                    $pdo->prepare("UPDATE sampah SET stok_kg = stok_kg - ? WHERE id_sampah = ?")->execute([$berat, $idSampah]);

                    $pdo->commit();
                    setFlash('success', 'Pembelian sampah berhasil! Saldo kamu terpotong ' . rupiah($totalHarga));
                    redirect('pengepul/transaksi.php');
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Terjadi kesalahan sistem saat memproses transaksi.';
        }
    }
}

$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([$myId]);
$saldoPengepul = $stmt->fetch()['saldo'];

$daftarSampah = $pdo->query("SELECT * FROM sampah WHERE stok_kg > 0 ORDER BY jenis_sampah")->fetchAll();

$pageTitle = 'Beli Sampah dari Admin';
$active = 'transaksi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Pengepul</div>
        <h1>Beli Sampah dari Admin</h1>
    </div>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:520px;">
    <p class="field-hint" style="margin-top:0;">Saldo Tersedia: <strong><?= rupiah($saldoPengepul) ?></strong></p>
    <form method="POST">
        <div class="field">
            <label>Jenis Sampah Tersedia</label>
            <select name="id_sampah" id="sampahSelect" required onchange="hitungTotal()">
                <option value="">-- Pilih Sampah --</option>
                <?php foreach ($daftarSampah as $s): ?>
                    <option value="<?= (int)$s['id_sampah'] ?>" data-harga="<?= (float)$s['harga_per_kg'] ?>" data-stok="<?= (float)$s['stok_kg'] ?>">
                        <?= e($s['jenis_sampah']) ?> — Stok: <?= number_format($s['stok_kg'],1) ?> kg (<?= rupiah($s['harga_per_kg']) ?>/kg)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Berat yang Dibeli (kg)</label>
            <input type="number" step="0.01" min="0.01" name="berat_kg" id="beratInput" required oninput="hitungTotal()">
        </div>
        <div class="flex-between" style="border-top: 2px solid var(--ink); padding-top:14px; margin-bottom:16px;">
            <h3 class="mt-0">Total Pembayaran</h3>
            <h3 class="mt-0" id="totalTampil">Rp 0</h3>
        </div>
        <button type="submit" class="btn stamp">Proses Pembelian</button>
    </form>
</div>

<script>
function formatRupiah(n) { return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
function hitungTotal() {
    const select = document.getElementById('sampahSelect');
    const harga = parseFloat(select.selectedOptions[0]?.dataset.harga || 0);
    const berat = parseFloat(document.getElementById('beratInput').value) || 0;
    document.getElementById('totalTampil').textContent = formatRupiah(harga * berat);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
