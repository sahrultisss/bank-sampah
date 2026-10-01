<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
        case 'pengepul':
            redirect('pengepul/dashboard.php');
        case 'guru':
        case 'siswa':
            redirect('anggota/dashboard.php');
    }
}

redirect('auth/login.php');
