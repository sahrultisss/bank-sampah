<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES (?, ?, ?)');
        $stmt->execute([trim($_POST['jenis_sampah']), (float)$_POST['harga_per_kg'], (float)($_POST['stok_kg'] ?? 0)]);
        setFlash('success', 'Jenis sampah berhasil ditambahkan.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE sampah SET jenis_sampah=?, harga_per_kg=?, stok_kg=? WHERE id_sampah=?');
        $stmt->execute([
            trim($_POST['jenis_sampah']),
            (float)$_POST['harga_per_kg'],
            (float)$_POST['stok_kg'],
            (int)$_POST['id'],
        ]);
        setFlash('success', 'Jenis sampah berhasil diperbarui.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $cek = $pdo->prepare('SELECT COUNT(*) c FROM transaksi WHERE id_sampah = ?');
        $cek->execute([$id]);
        if ($cek->fetch()['c'] > 0) {
            setFlash('error', 'Jenis sampah tidak bisa dihapus karena sudah pernah dipakai di transaksi.');
        } else {
            $pdo->prepare('DELETE FROM sampah WHERE id_sampah = ?')->execute([$id]);
            setFlash('success', 'Jenis sampah berhasil dihapus.');
        }
    }
    redirect('admin/sampah.php');
}

$daftarSampah = $pdo->query("SELECT * FROM sampah ORDER BY jenis_sampah")->fetchAll();

$pageTitle = 'Jenis Sampah';
$active = 'sampah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Master data</div>
        <h1>Jenis Sampah &amp; Stok</h1>
    </div>
    <button class="btn stamp" onclick="openModal('modalTambah')">+ Tambah Jenis</button>
</div>

<div class="panel">
    <table class="ledger">
        <thead><tr><th>Nama Jenis</th><th class="num">Harga / kg</th><th class="num">Stok Saat Ini</th><th class="actions">Aksi</th></tr></thead>
        <tbody>
            <?php if (empty($daftarSampah)): ?>
                <tr><td colspan="4" class="text-soft">Belum ada jenis sampah.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarSampah as $s): ?>
                <tr>
                    <td><?= e($s['jenis_sampah']) ?></td>
                    <td class="num"><?= rupiah($s['harga_per_kg']) ?></td>
                    <td class="num"><?= number_format($s['stok_kg'], 2) ?> kg</td>
                    <td class="actions">
                        <button class="btn outline small"
                            onclick="fillEditForm('modalEdit', this)"
                            data-id="<?= (int)$s['id_sampah'] ?>"
                            data-jenis_sampah="<?= e($s['jenis_sampah']) ?>"
                            data-harga_per_kg="<?= e((string)$s['harga_per_kg']) ?>"
                            data-stok_kg="<?= e((string)$s['stok_kg']) ?>">Edit</button>
                        <button class="btn danger small" onclick="confirmDelete('modalHapus', <?= (int)$s['id_sampah'] ?>)">Hapus</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <h3>Tambah Jenis Sampah</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="field"><label>Nama Jenis</label><input type="text" name="jenis_sampah" required></div>
            <div class="form-grid">
                <div class="field"><label>Harga / kg (Rp)</label><input type="number" step="0.01" name="harga_per_kg" required></div>
                <div class="field"><label>Stok Awal (kg)</label><input type="number" step="0.01" name="stok_kg" value="0"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn outline small" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn stamp small">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEdit">
    <div class="modal-box">
        <h3>Edit Jenis Sampah</h3>
        <p class="text-soft" style="margin-top:-6px;">Ubah stok di sini hanya untuk koreksi manual.</p>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="field"><label>Nama Jenis</label><input type="text" name="jenis_sampah" id="edit_jenis_sampah" required></div>
            <div class="form-grid">
                <div class="field"><label>Harga / kg (Rp)</label><input type="number" step="0.01" name="harga_per_kg" id="edit_harga_per_kg" required></div>
                <div class="field"><label>Stok (kg)</label><input type="number" step="0.01" name="stok_kg" id="edit_stok_kg" required></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn outline small" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" class="btn stamp small">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalHapus">
    <div class="modal-box">
        <h3>Hapus Jenis Sampah?</h3>
        <p class="text-soft">Tindakan ini tidak bisa dibatalkan.</p>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <div class="modal-actions">
                <button type="button" class="btn outline small" onclick="closeModal('modalHapus')">Batal</button>
                <button type="submit" class="btn danger small">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= baseUrl('assets/js/script.js') ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
