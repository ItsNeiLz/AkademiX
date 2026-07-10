<?php
/**
 * Cron Job script to check for upcoming deadlines and generate notifications.
 * This script should be run via a cron job on the server (e.g., daily at 00:00).
 * Or it can be triggered manually by an Admin.
 */

// Allow running from CLI or web
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../includes/functions.php';
    if (!is_logged_in() || current_user()['role'] !== 'Admin') {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    // If running from CLI, we need to load dependencies manually
    // Adjust paths if necessary for CLI environment
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/includes/functions.php';
}

$notificationsSent = 0;

try {
    // Find tasks that have a deadline tomorrow and are NOT completed
    $upcomingTasks = db()->fetchAll("
        SELECT id, title, group_id 
        FROM tasks 
        WHERE status != 'Completed' AND deadline = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");

    foreach ($upcomingTasks as $task) {
        $taskId = $task['id'];
        $title = $task['title'];
        $groupId = $task['group_id'];

        // Get all members of the group
        $members = db()->fetchAll("SELECT user_id FROM group_members WHERE group_id = ?", [$groupId]);
        
        foreach ($members as $m) {
            // Check if we already notified this user for this task today to prevent duplicates
            $notifTitle = "Deadline Besok: $title";
            $exists = db()->count('notifications', "user_id = ? AND title = ? AND DATE(created_at) = CURDATE()", [$m['user_id'], $notifTitle]);
            
            if (!$exists) {
                create_notification(
                    $m['user_id'],
                    $notifTitle,
                    "Tugas '$title' harus dikumpulkan besok. Pastikan Anda telah menyelesaikan bagian Anda.",
                    'Deadline'
                );
                $notificationsSent++;
            }
        }
    }

    if (php_sapi_name() !== 'cli') {
        flash('success', "Cron berhasil dijalankan. $notificationsSent notifikasi deadline dikirim.");
        redirect('dashboard/index.php');
    } else {
        echo "Cron ran successfully. Sent $notificationsSent notifications.\n";
    }

} catch (Exception $e) {
    if (php_sapi_name() !== 'cli') {
        flash('error', "Gagal menjalankan cron: " . $e->getMessage());
        redirect('dashboard/index.php');
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
