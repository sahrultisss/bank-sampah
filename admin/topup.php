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
        // FIX: klaim status 'pending' secara atomik supaya saldo tidak bisa ditambah dua kali
        $newStatus = $action === 'approve' ? 'disetujui' : 'ditolak';
        $upd = $pdo->prepare("UPDATE topup SET status = ? WHERE id_topup = ? AND status = 'pending'");
        $upd->execute([$newStatus, $id]);

        if ($upd->rowCount() !== 1) {
            $pdo->rollBack();
            setFlash('error', 'Pengajuan topup tidak ditemukan atau sudah diproses.');
        } elseif ($action === 'reject') {
            $pdo->commit();
            setFlash('success', 'Pengajuan top up ditolak.');
        } else {
            $stmt = $pdo->prepare('SELECT id_user, nominal FROM topup WHERE id_topup = ?');
            $stmt->execute([$id]);
            $topup = $stmt->fetch();

            $pdo->prepare('UPDATE users SET saldo = saldo + ? WHERE id_user = ?')->execute([$topup['nominal'], $topup['id_user']]);
            $pdo->commit();
            setFlash('success', 'Top up disetujui, saldo pengepul berhasil ditambahkan.');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('error', 'Gagal memproses persetujuan top up.');
    }
    redirect('admin/topup.php');
}

$stmt = $pdo->query("SELECT tp.*, u.nama FROM topup tp JOIN users u ON u.id_user = tp.id_user ORDER BY (tp.status = 'pending') DESC, tp.tanggal DESC");
$daftarTopup = $stmt->fetchAll();

$pageTitle = 'Persetujuan Top Up Pengepul';
$active = 'topup';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Persetujuan</div>
        <h1>Top Up Saldo Pengepul</h1>
    </div>
</div>

<div class="panel">
    <table class="ledger">
        <thead><tr><th>Nama Pengepul</th><th>Tanggal Ajuan</th><th class="num">Nominal</th><th>Status</th><th class="actions">Aksi</th></tr></thead>
        <tbody>
            <?php if (empty($daftarTopup)): ?>
                <tr><td colspan="5" class="text-soft">Belum ada pengajuan top up.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarTopup as $t): ?>
                <tr>
                    <td><?= e($t['nama']) ?></td>
                    <td><?= tanggalIndo($t['tanggal']) ?></td>
                    <td class="num"><?= rupiah($t['nominal']) ?></td>
                    <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                    <td class="actions">
                        <?php if ($t['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?= (int)$t['id_topup'] ?>">
                                <button type="submit" class="btn sage small">Setujui</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?= (int)$t['id_topup'] ?>">
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
