<?php
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    log_activity(current_user()['id'], 'Melakukan logout dari sistem');
}

// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to login with success message
session_start();
flash('success', 'Anda telah berhasil keluar.');
redirect('auth/login.php');
