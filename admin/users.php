<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getConnection();
$action = $_POST['action'] ?? '';
$rolesValid = ['admin', 'guru', 'siswa', 'pengepul'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $nama     = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role     = in_array($_POST['role'], $rolesValid, true) ? $_POST['role'] : 'siswa';

        $cek = $pdo->prepare('SELECT id_user FROM users WHERE username = ?');
        $cek->execute([$username]);

        if ($cek->fetch()) {
            setFlash('error', 'Username sudah dipakai.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (nama, username, password, role, saldo) VALUES (?, ?, ?, ?, 0)');
            $stmt->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            setFlash('success', 'Pengguna baru berhasil ditambahkan.');
        }
    } elseif ($action === 'update') {
        $id   = (int)$_POST['id'];
        $nama = trim($_POST['nama']);
        $role = in_array($_POST['role'], $rolesValid, true) ? $_POST['role'] : 'siswa';
        if ($id === (int)currentUser()['id']) { $role = 'admin'; } // admin tidak bisa mengubah role akunnya sendiri

        $pdo->prepare('UPDATE users SET nama = ?, role = ? WHERE id_user = ?')->execute([$nama, $role, $id]);

        if (!empty($_POST['password_baru'])) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id_user = ?')
                ->execute([password_hash($_POST['password_baru'], PASSWORD_DEFAULT), $id]);
        }
        setFlash('success', 'Data pengguna berhasil diperbarui.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === (int)currentUser()['id']) {
            setFlash('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
            redirect('admin/users.php');
        }
        $cekTransaksi = $pdo->prepare('SELECT COUNT(*) c FROM transaksi WHERE id_user = ?');
        $cekTransaksi->execute([$id]);
        $cekTarik = $pdo->prepare('SELECT COUNT(*) c FROM tarik_saldo WHERE id_user = ?');
        $cekTarik->execute([$id]);

        if ($cekTransaksi->fetch()['c'] > 0 || $cekTarik->fetch()['c'] > 0) {
            setFlash('error', 'Pengguna tidak bisa dihapus karena sudah punya riwayat transaksi/penarikan.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id_user = ?')->execute([$id]);
            setFlash('success', 'Pengguna berhasil dihapus.');
        }
    }
    redirect('admin/users.php');
}

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (nama LIKE ? OR username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (in_array($roleFilter, $rolesValid, true)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
$sql .= " ORDER BY id_user DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarUser = $stmt->fetchAll();

$pageTitle = 'Data Pengguna';
$active = 'users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="eyebrow">Master data</div>
        <h1>Data Pengguna</h1>
    </div>
    <button class="btn stamp" onclick="openModal('modalTambah')">+ Tambah Pengguna</button>
</div>

<div class="panel">
    <form method="GET" class="form-grid" style="align-items:end; margin-bottom:10px;">
        <div class="field">
            <label>Cari nama / username</label>
            <input type="text" name="q" value="<?= e($search) ?>">
        </div>
        <div class="field">
            <label>Peran</label>
            <select name="role">
                <option value="">Semua</option>
                <?php foreach ($rolesValid as $r): ?>
                    <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= labelRole($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><button class="btn outline" type="submit">Terapkan</button></div>
    </form>

    <table class="ledger">
        <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th class="num">Saldo</th><th class="actions">Aksi</th></tr></thead>
        <tbody>
            <?php if (empty($daftarUser)): ?>
                <tr><td colspan="5" class="text-soft">Belum ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftarUser as $u): ?>
                <tr>
                    <td><?= e($u['nama']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><span class="badge aktif"><?= labelRole($u['role']) ?></span></td>
                    <td class="num"><?= rupiah($u['saldo']) ?></td>
                    <td class="actions">
                        <button class="btn outline small"
                            onclick="fillEditForm('modalEdit', this)"
                            data-id="<?= (int)$u['id_user'] ?>"
                            data-nama="<?= e($u['nama']) ?>"
                            data-role="<?= e($u['role']) ?>">Edit</button>
                        <button class="btn danger small" onclick="confirmDelete('modalHapus', <?= (int)$u['id_user'] ?>)">Hapus</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <h3>Tambah Pengguna</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" required></div>
            <div class="form-grid">
                <div class="field"><label>Username</label><input type="text" name="username" required></div>
                <div class="field"><label>Password</label><input type="password" name="password" required></div>
            </div>
            <div class="field">
                <label>Peran</label>
                <select name="role">
                    <?php foreach ($rolesValid as $r): ?>
                        <option value="<?= $r ?>"><?= labelRole($r) ?></option>
                    <?php endforeach; ?>
                </select>
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
        <h3>Edit Pengguna</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" id="edit_nama" required></div>
            <div class="field">
                <label>Peran</label>
                <select name="role" id="edit_role">
                    <?php foreach ($rolesValid as $r): ?>
                        <option value="<?= $r ?>"><?= labelRole($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Reset Password (opsional)</label>
                <input type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah">
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
        <h3>Hapus Pengguna?</h3>
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
