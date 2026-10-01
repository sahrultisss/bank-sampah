<?php
/**
 * Kumpulan fungsi bantu (helper) yang dipakai di seluruh aplikasi
 */

function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tanggalIndo($tanggal): string
{
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $ts = is_numeric($tanggal) ? $tanggal : strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Flash message sederhana via session (survive 1x redirect) */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . baseUrl($path));
    exit;
}

/** Label tampilan yang rapi untuk role */
function labelRole(string $role): string
{
    $map = ['admin' => 'Admin', 'guru' => 'Guru', 'siswa' => 'Siswa', 'pengepul' => 'Pengepul'];
    return $map[$role] ?? ucfirst($role);
}
