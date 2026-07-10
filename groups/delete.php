<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            flash('error', 'ID Kelompok tidak valid.');
            redirect('groups/index.php');
        }

        $group = db()->fetch("SELECT group_name, leader_id FROM `groups` WHERE id = ?", [$id]);
        
        if ($group) {
            // Only Admin or the Group Leader can delete
            if (current_user()['role'] !== 'Admin' && current_user()['id'] != $group['leader_id']) {
                flash('error', 'Anda tidak memiliki akses untuk menghapus kelompok ini.');
                redirect('groups/index.php');
            }

            try {
                // Cascading delete is handled by DB constraints
                db()->delete('groups', 'id = ?', [$id]);
                log_activity(current_user()['id'], "Menghapus kelompok: " . $group['group_name']);
                flash('success', 'Kelompok berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Gagal menghapus kelompok karena ada tugas yang terkait.');
            }
        } else {
            flash('error', 'Kelompok tidak ditemukan.');
        }
    } else {
        flash('error', 'Sesi kadaluarsa. Silakan coba lagi.');
    }
}

redirect('groups/index.php');
