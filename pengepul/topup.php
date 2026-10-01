<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('pengepul');

$pdo = getConnection();
$myId = currentUser()['id'];
$error = null;

$stmt = $pdo->prepare('SELECT saldo FROM users WHERE id_user = ?');
$stmt->execute([$myId]);
$saldo = $stmt->fetch()['saldo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = (float)($_POST['nominal'] ?? 0);

    if ($nominal <= 0) {
        $error = 'Nominal top up harus lebih dari 0.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO topup (id_user, nominal, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$myId, $nominal]);
        setFlash('success', 'Pengajuan top up sebesar ' . rupiah($nominal) . ' berhasil dikirim. Menunggu persetujuan Admin.');
        redirect('pengepul/topup.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM topup WHERE id_user = ? ORDER BY tanggal DESC');
$stmt->execute([$myId]);
$riwayatTopup = $stmt->fetchAll();

$pageTitle = 'Top Up Saldo Pengepul';
$active = 'topup';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Dompet Pengepul</div>
        <h1>Isi Saldo (Top Up)</h1>
    </div>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:460px;">
    <p class="field-hint" style="margin-top:0;">Saldo Kamu Saat Ini: <strong><?= rupiah($saldo) ?></strong></p>
    <form method="POST">
        <div class="field">
            <label>Nominal Top Up (Rp)</label>
            <input type="number" step="1000" min="1000" name="nominal" required placeholder="Contoh: 500000">
        </div>
        <button type="submit" class="btn stamp">Kirim Pengajuan Top Up</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h2 class="mt-0">Riwayat Pengajuan Top Up</h2></div>
    <table class="ledger">
        <thead><tr><th>Tanggal</th><th class="num">Nominal</th><th>Status</th></tr></thead>
        <tbody>
            <?php if (empty($riwayatTopup)): ?>
                <tr><td colspan="3" class="text-soft">Belum ada riwayat top up.</td></tr>
            <?php endif; ?>
            <?php foreach ($riwayatTopup as $t): ?>
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