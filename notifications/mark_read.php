<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $id = $_POST['id'] ?? null;
        $userId = current_user()['id'];
        
        if ($id) {
            // Ensure the notification belongs to the user
            db()->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
        }
    }
}

// Optional return URL parameter
$returnUrl = $_POST['return_url'] ?? 'index.php';
redirect('notifications/' . $returnUrl);
