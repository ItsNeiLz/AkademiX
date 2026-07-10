<?php
require_once __DIR__ . '/../includes/auth_check.php';

require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            flash('error', 'ID User tidak valid.');
            redirect('users/index.php');
        }

        // Prevent self-deletion
        if ($id == current_user()['id']) {
            flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            redirect('users/index.php');
        }

        $user = db()->fetch("SELECT name FROM users WHERE id = ?", [$id]);
        
        if ($user) {
            try {
                // Cascading delete is handled by DB constraints
                db()->delete('users', 'id = ?', [$id]);
                log_activity(current_user()['id'], "Menghapus user: " . $user['name']);
                flash('success', 'User berhasil dihapus.');
            } catch (PDOException $e) {
                // In case of any foreign key constraint issues not covered by CASCADE/SET NULL
                flash('error', 'Gagal menghapus user karena data ini berelasi dengan data lain.');
            }
        } else {
            flash('error', 'User tidak ditemukan.');
        }
    } else {
        flash('error', 'Sesi kadaluarsa. Silakan coba lagi.');
    }
}

redirect('users/index.php');
