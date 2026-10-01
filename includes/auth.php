<?php
/**
 * Helper autentikasi & proteksi akses berbasis role
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'   => $_SESSION['user_id'],
        'nama' => $_SESSION['nama'],
        'role' => $_SESSION['role'],
    ];
}

/**
 * Wajib login. Jika belum login, redirect ke halaman login.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('auth/login.php'));
        exit;
    }
}

/**
 * Wajib login DAN role tertentu. Contoh: requireRole('admin')
 * atau requireRole(['guru', 'siswa'])
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];

    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<h2>403 - Akses ditolak</h2><p>Kamu tidak memiliki izin untuk mengakses halaman ini.</p><a href="' . baseUrl('index.php') . '">Kembali</a>');
    }
}

/**
 * Menghasilkan URL relatif dari root project.
 * Bekerja baik saat project ada di subfolder (XAMPP) maupun di root (hosting).
 */
function baseUrl(string $path = ''): string
{
    static $appBase = null;

    if ($appBase === null) {
        $projectRoot = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
        $docRoot     = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT']));

        $relative = '';
        if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
            $relative = substr($projectRoot, strlen($docRoot));
        }

        // rtrim supaya kalau project ada di root hasilnya "/" (bukan "//")
        $appBase = rtrim('/' . trim($relative, '/'), '/') . '/';
    }

    return $appBase . ltrim($path, '/');
}