<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = current_user()['id'];
    $count = db()->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
