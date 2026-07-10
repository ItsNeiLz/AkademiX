<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            flash('error', 'ID Tugas tidak valid.');
            redirect('tasks/index.php');
        }

        $task = db()->fetch("SELECT title, created_by FROM tasks WHERE id = ?", [$id]);
        
        if ($task) {
            // Only Admin or creator can delete
            if (current_user()['role'] !== 'Admin' && current_user()['id'] != $task['created_by']) {
                flash('error', 'Anda tidak memiliki akses untuk menghapus tugas ini.');
                redirect('tasks/index.php');
            }

            try {
                // Cascading delete handles assignments and checklists
                db()->delete('tasks', 'id = ?', [$id]);
                log_activity(current_user()['id'], "Menghapus tugas: " . $task['title']);
                flash('success', 'Tugas berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Gagal menghapus tugas.');
            }
        } else {
            flash('error', 'Tugas tidak ditemukan.');
        }
    } else {
        flash('error', 'Sesi kadaluarsa. Silakan coba lagi.');
    }
}

redirect('tasks/index.php');
