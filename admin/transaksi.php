<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- FITUR 1: ACC setoran user ---
    if (isset($_POST['action']) && $_POST['action'] === 'acc') {
        $idTrx = (int)($_POST['id_transaksi'] ?? 0);

        $pdo->beginTransaction();
        try {
            // FIX: hanya setoran user (beli_user) yang boleh di-ACC, dan klaim status secara atomik
            $upd = $pdo->prepare("UPDATE transaksi SET status = 'disetujui' WHERE id_transaksi = ? AND status = 'pending' AND tipe = 'beli_user'");
            $upd->execute([$idTrx]);

            if ($upd->rowCount() !== 1) {
                $pdo->rollBack();
                setFlash('error', 'Setoran tidak ditemukan atau sudah diproses.');
            } else {
                $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
                $stmt->execute([$idTrx]);
                $trx = $stmt->fetch();

                // Admin membayar setoran dari saldo adminnya (cek & potong dalam satu query)
                $bayar = $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id_user = ? AND saldo >= ?");
                $bayar->execute([$trx['total_rp'], adminId($pdo), $trx['total_rp']]);

                if ($bayar->rowCount() !== 1) {
                    $pdo->rollBack();
                    setFlash('error', 'Saldo admin tidak cukup untuk membayar setoran ini (' . rupiah($trx['total_rp']) . '). Tambah modal saldo di Dashboard.');
                } else {
                    $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id_user = ?")->execute([$trx['total_rp'], $trx['id_user']]);
                    $pdo->prepare("UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?")->execute([$trx['berat_kg'], $trx['id_sampah']]);

                    $pdo->commit();
                    setFlash('success', 'Setoran user berhasil di-ACC!');
                }
            }
            redirect('admin/transaksi.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Gagal memproses ACC.';
        }

    // --- FITUR 2: Input manual ---
    } else {
        $idUser   = (int)($_POST['id_user'] ?? 0);
        $idSampah = (int)($_POST['id_sampah'] ?? 0);
        $berat    = (float)($_POST['berat_kg'] ?? 0);

        if (!$idUser || !$idSampah || $berat <= 0) {
            $error = 'Pilih user, jenis sampah, dan isi berat lebih dari 0.';
        } else {
            $stmt = $pdo->prepare("SELECT harga_per_kg FROM sampah WHERE id_sampah = ?");
            $stmt->execute([$idSampah]);
            $sampah = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT id_user FROM users WHERE id_user = ? AND role IN ('guru','siswa')");
            $stmt->execute([$idUser]);
            $userOk = $stmt->fetch();

            if (!$sampah) {
                $error = 'Jenis sampah tidak ditemukan.';
            } elseif (!$userOk) {
                $error = 'User tidak valid (harus guru atau siswa).';
            } else {
                $total = $berat * $sampah['harga_per_kg'];
                $pdo->beginTransaction();
                try {
                    // Admin membayar dari saldo adminnya
                    $bayar = $pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id_user = ? AND saldo >= ?");
                    $bayar->execute([$total, adminId($pdo), $total]);

                    if ($bayar->rowCount() !== 1) {
                        $pdo->rollBack();
                        $error = 'Saldo admin tidak cukup (butuh ' . rupiah($total) . '). Tambah modal saldo di Dashboard.';
                    } else {
                        $pdo->prepare("INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, status, tanggal) VALUES (?, ?, 'beli_user', ?, ?, 'disetujui', NOW())")
                            ->execute([$idUser, $idSampah, $berat, $total]);
                        $pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id_user = ?")->execute([$total, $idUser]);
                        $pdo->prepare("UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?")->execute([$berat, $idSampah]);

                        $pdo->commit();
                        setFlash('success', 'Berhasil membeli sampah dari user sebesar ' . rupiah($total));
                        redirect('admin/transaksi.php');
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Gagal memproses transaksi.';
                }
            }
        }
    }
}

$daftarAnggota = $pdo->query("SELECT id_user, nama, role FROM users WHERE role IN ('guru','siswa') ORDER BY nama")->fetchAll();
$daftarSampah  = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

$transaksiList = $pdo->query("
    SELECT t.*, u.nama AS nama_user, u.role, s.jenis_sampah
    FROM transaksi t
    JOIN users u ON u.id_user = t.id_user
    JOIN sampah s ON s.id_sampah = t.id_sampah
    ORDER BY (t.status = 'pending') DESC, t.tanggal DESC
")->fetchAll();

$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([adminId($pdo)]);
$saldoAdmin = $stmt->fetch()['saldo'];

$pageTitle = 'Kelola Transaksi';
$active = 'transaksi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Transaksi Master</div>
        <h1>Terima Sampah dari User</h1>
    </div>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:520px;">
    <h3>Input Pembelian Sampah dari User</h3>
    <p class="field-hint" style="margin-top:0;">Saldo Admin: <strong><?= rupiah($saldoAdmin) ?></strong></p>
    <form method="POST">
        <div class="field">
            <label>User (Guru / Siswa)</label>
            <select name="id_user" required>
                <option value="">-- Pilih User --</option>
                <?php foreach ($daftarAnggota as $a): ?>
                    <option value="<?= (int)$a['id_user'] ?>"><?= e($a['nama']) ?> (<?= labelRole($a['role']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Jenis Sampah</label>
            <select name="id_sampah" required>
                <option value="">-- Pilih Sampah --</option>
                <?php foreach ($daftarSampah as $s): ?>
                    <option value="<?= (int)$s['id_sampah'] ?>"><?= e($s['jenis_sampah']) ?> (<?= rupiah($s['harga_per_kg']) ?>/kg)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Berat (kg)</label>
            <input type="number" step="0.01" min="0.01" name="berat_kg" required>
        </div>
        <button type="submit" class="btn stamp">Proses &amp; Tambah Saldo User</button>
    </form>
</div>

<div class="panel">
    <h3>Semua Riwayat Transaksi (Pembelian &amp; Penjualan)</h3>
    <table class="ledger">
        <thead><tr><th>Nama</th><th>Tipe Transaksi</th><th>Sampah</th><th class="num">Berat</th><th>Tanggal</th><th class="num">Nominal</th><th>Status</th><th class="actions">Aksi</th></tr></thead>
        <tbody>
            <?php if (empty($transaksiList)): ?>
                <tr><td colspan="8" class="text-soft">Belum ada transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($transaksiList as $t): ?>
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
                    <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                    <td class="actions">
                        <?php if ($t['status'] === 'pending' && $t['tipe'] === 'beli_user'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="acc">
                                <input type="hidden" name="id_transaksi" value="<?= (int)$t['id_transaksi'] ?>">
                                <button type="submit" class="btn sage small" onclick="return confirm('ACC setoran sampah ini?')">ACC / Setujui</button>
                            </form>
                        <?php else: ?>
                            <span class="text-soft">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
