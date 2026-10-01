<?php
/**
 * Layout bersama untuk halaman dashboard (admin/pengepul/guru/siswa).
 * Variabel yang harus di-set SEBELUM include file ini:
 *   $pageTitle  (string)  - judul halaman
 *   $active     (string)  - key menu yang aktif, contoh: 'dashboard'
 *
 * Wajib sudah requireRole(...) dipanggil sebelum file ini di-include.
 * Catatan: guru & siswa punya fungsi sama persis (setor sampah, ajukan
 * tarik saldo), jadi keduanya dilayani oleh folder /anggota/ yang sama.
 */

$user = currentUser();
$role = $user['role'];
$navRole = in_array($role, ['guru', 'siswa'], true) ? 'anggota' : $role;

$menus = [
    'admin' => [
        'dashboard'   => ['label' => 'Dashboard',        'url' => 'admin/dashboard.php'],
        'users'       => ['label' => 'Data Pengguna',    'url' => 'admin/users.php'],
        'sampah'      => ['label' => 'Jenis Sampah',     'url' => 'admin/sampah.php'],
        'transaksi'   => ['label' => 'Transaksi',        'url' => 'admin/transaksi.php'],
        'tarik_saldo' => ['label' => 'Tarik Saldo User', 'url' => 'admin/tarik_saldo.php'],
        'topup'       => ['label' => 'Topup Pengepul',   'url' => 'admin/topup.php'],
        'laporan'     => ['label' => 'Laporan',          'url' => 'admin/laporan.php'],
    ],
    'pengepul' => [
        'dashboard'   => ['label' => 'Dashboard',         'url' => 'pengepul/dashboard.php'],
        'transaksi'   => ['label' => 'Beli Sampah',       'url' => 'pengepul/transaksi.php'],
        'sampah'      => ['label' => 'Stok & Harga',      'url' => 'pengepul/sampah.php'],
        'topup'       => ['label' => 'Top Up Saldo',      'url' => 'pengepul/topup.php'],
    ],
    'anggota' => [
    'dashboard'   => ['label' => 'Dashboard',      'url' => 'anggota/dashboard.php'],
    'setor'       => ['label' => 'Setor Sampah',   'url' => 'anggota/setor.php'],
    'transaksi'   => ['label' => 'Transaksi Saya', 'url' => 'anggota/riwayat.php'],
    'tarik_saldo' => ['label' => 'Tarik Saldo',    'url' => 'anggota/tarik_saldo.php'],
], 
];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Bank Sampah') ?> — Bank Sampah</title>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar no-print">
        <div class="brand">Bank Sampah</div>
        <div class="brand-sub">Sistem Tabungan Sampah</div>
        <nav>
            <?php foreach ($menus[$navRole] as $key => $item): ?>
                <a href="<?= baseUrl($item['url']) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>">
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="user-box">
            <div class="name"><?= e($user['nama']) ?></div>
            <div class="role"><?= e(labelRole($role)) ?></div>
            <a href="<?= baseUrl('auth/logout.php') ?>">Keluar &rarr;</a>
        </div>
    </aside>

    <main class="content">
        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
