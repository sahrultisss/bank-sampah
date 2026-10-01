<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['guru', 'siswa']);

$pdo = getConnection();
$myId = currentUser()['id'];
$error = null;

$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([$myId]);
$saldo = $stmt->fetch()['saldo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float)($_POST['nominal'] ?? 0);

    if ($nominal <= 0) {
        $error = 'Nominal harus lebih dari 0.';
    } elseif ($nominal > $saldo) {
        $error = 'Saldo kamu tidak mencukupi. Saldo saat ini: ' . rupiah($saldo);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tarik_saldo (id_user, nominal, tanggal, status) VALUES (?, ?, NOW(), 'pending')"
        );
        $stmt->execute([$myId, $nominal]);
        setFlash('success', 'Pengajuan penarikan sebesar ' . rupiah($nominal) . ' terkirim, menunggu persetujuan admin.');
        redirect('anggota/tarik_saldo.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM tarik_saldo WHERE id_user = ? ORDER BY tanggal DESC');
$stmt->execute([$myId]);
$riwayatTarik = $stmt->fetchAll();

$pageTitle = 'Tarik Saldo';
$active = 'tarik_saldo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Penarikan</div>
        <h1>Ajukan Tarik Saldo</h1>
    </div>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:460px;">
    <p class="field-hint" style="margin-top:0;">Saldo kamu saat ini: <strong><?= rupiah($saldo) ?></strong></p>
    <form method="POST">
        <div class="field">
            <label>Nominal Penarikan (Rp)</label>
            <input type="number" step="0.01" min="0.01" name="nominal" required>
        </div>
        <button type="submit" class="btn stamp">Ajukan Penarikan</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h2 class="mt-0">Riwayat Pengajuan</h2></div>
    <table class="ledger">
        <thead><tr><th>Tanggal Ajuan</th><th class="num">Nominal</th><th>Status</th></tr></thead>
        <tbody>
            <?php if (empty($riwayatTarik)): ?>
                <tr><td colspan="3" class="text-soft">Belum pernah mengajukan penarikan.</td></tr>
            <?php endif; ?>
            <?php foreach ($riwayatTarik as $t): ?>
                <tr>
                    <td><?= tanggalIndo($t['tanggal']) ?></td>
                    <td class="num"><?= rupiah($t['nominal']) ?></td>
                    <td><span class="badge <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
