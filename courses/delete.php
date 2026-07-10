<?php
require_once __DIR__ . '/../includes/auth_check.php';

require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            flash('error', 'ID Mata Kuliah tidak valid.');
            redirect('courses/index.php');
        }

        $course = db()->fetch("SELECT course_name FROM courses WHERE id = ?", [$id]);
        
        if ($course) {
            try {
                // Cascading delete is handled by DB constraints
                db()->delete('courses', 'id = ?', [$id]);
                log_activity(current_user()['id'], "Menghapus mata kuliah: " . $course['course_name']);
                flash('success', 'Mata kuliah berhasil dihapus.');
            } catch (PDOException $e) {
                flash('error', 'Gagal menghapus mata kuliah karena sudah memiliki kelompok atau tugas yang terkait.');
            }
        } else {
            flash('error', 'Mata kuliah tidak ditemukan.');
        }
    } else {
        flash('error', 'Sesi kadaluarsa. Silakan coba lagi.');
    }
}

redirect('courses/index.php');
