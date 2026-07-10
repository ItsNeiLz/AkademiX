<?php
/**
 * Authentication Guard
 * Include this file at the top of any protected page
 */

require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    flash('error', 'Silakan login terlebih dahulu.');
    redirect('auth/login.php');
}

/**
 * Require specific role(s)
 * Usage: require_role('Admin') or require_role('Admin', 'Ketua Kelompok')
 */
function require_role(string ...$roles): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles)) {
        flash('error', 'Anda tidak memiliki akses ke halaman ini.');
        redirect('dashboard/index.php');
    }
}
