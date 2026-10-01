<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['approve', 'reject'], true)) {
    $id = (int)$_POST['id'];

    $pdo->beginTransaction();
    try {
        // FIX: klaim status 'pending' secara atomik supaya tidak bisa terproses dua kali
        $newStatus = $action === 'approve' ? 'disetujui' : 'ditolak';
        $upd = $pdo->prepare("UPDATE tarik_saldo SET status = ? WHERE id_tarik = ? AND status = 'pending'");
        $upd->execute([$newStatus, $id]);

        if ($upd->rowCount() !== 1) {
            $pdo->rollBack();
            setFlash('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
        } elseif ($action === 'reject') {
            $pdo->commit();
            setFlash('success', 'Pengajuan penarikan ditolak.');
        } else {
            $stmt = $pdo->prepare('SELECT id_user, nominal FROM tarik_saldo WHERE id_tarik = ?');
            $stmt->execute([$id]);
            $tarik = $stmt->fetch();

            // Potong saldo hanya jika saldo masih cukup (cek & potong dalam satu query)
            $potong = $pdo->prepare('UPDATE users SET saldo = saldo - ? WHERE id_user = ? AND saldo >= ?');
            $potong->execute([$tarik['nominal'], $tarik['id_user'], $tarik['nominal']]);

            if ($potong->rowCount() !== 1) {
                $pdo->rollBack();
                setFlash('error', 'Saldo pengguna sudah tidak mencukupi, tidak bisa disetujui.');
            } else {
                $pdo->commit();
                setFlash('success', 'Penarikan disetujui dan saldo sudah dipotong.');
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('error', 'Gagal memproses pengajuan.');
    }
    redirect('admin/tarik_saldo.php');
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT tr.*, u.nama, u.role FROM tarik_saldo tr JOIN users u ON u.id_user = tr.id_user WHERE 1=1";
$params = [];
if (in_array($statusFilter, ['pending', 'disetujui', 'ditolak'], true)) {
    $sql .= " AND tr.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY (tr.status = 'pending') DESC, tr.tanggal DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarTarik = $stmt->fetchAll();

$pageTitle = 'Tarik Saldo';
$active = 'tarik_saldo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Persetujuan</div>
        <h1>Pengajuan Tarik Saldo</h1>
    </div>
</div>

<div class="panel">
    <form method="GET" class="flex-between" style="margin-bottom:16px;">
        <div class="field" style="margin-bottom:0;">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="disetujui" <?= $statusFilter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                <option value="ditolak" <?= $statusFilter === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
        </div>
    </form>

    <table class="ledger">
        <thead><tr><th>Nama</th><th>Peran</th><th>Tanggal Ajuan</th><th class="num">Nominal</th><th>Status</th><th class="actions">Aksi</th></tr></thead>
        <tbody>
            <?php if (empty($daftarTarik)): ?>
                <tr><td colspan="6" class="text-soft">Belum ada pengajuan.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarTarik as $t): ?>
                <tr>
                    <td><?= e($t['nama']) ?></td>
                    <td><span class="badge aktif"><?= labelRole($t['role']) ?></span></td>
                    <td><?= tanggalIndo($t['tanggal']) ?></td>
                    <td class="num"><?= rupiah($t['nominal']) ?></td>
                    <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                    <td class="actions">
                        <?php if ($t['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?= (int)$t['id_tarik'] ?>">
                                <button type="submit" class="btn sage small">Setujui</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?= (int)$t['id_tarik'] ?>">
                                <button type="submit" class="btn danger small">Tolak</button>
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
