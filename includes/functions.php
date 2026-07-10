<?php
/**
 * AkademiX Utility Functions
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize user input
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 * Returns array of error messages
 */
function validate_required(array $fields, array $data): array
{
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "{$label} wajib diisi.";
        }
    }
    return $errors;
}

/**
 * Validate email format
 */
function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Flash message system
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,    // success, error, warning, info
        'message' => $message,
    ];
}

/**
 * Get and clear flash message
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function display_flash(): string
{
    $flash = get_flash();
    if (!$flash) return '';

    $icons = [
        'success' => 'fas fa-check-circle',
        'error'   => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info'    => 'fas fa-info-circle',
    ];
    $icon = $icons[$flash['type']] ?? 'fas fa-info-circle';

    return '<div class="alert alert-' . sanitize($flash['type']) . '" id="flash-alert">
                <i class="' . $icon . '"></i>
                <span>' . sanitize($flash['message']) . '</span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>';
}

/**
 * Log user activity
 */
function log_activity(int $userId, string $activity): void
{
    db()->insert('activity_logs', [
        'user_id'  => $userId,
        'activity' => $activity,
    ]);
}

/**
 * Create a notification
 */
function create_notification(int $userId, string $title, string $message, string $type = 'Reminder'): void
{
    db()->insert('notifications', [
        'user_id' => $userId,
        'title'   => $title,
        'message' => $message,
        'type'    => $type,
    ]);
}

/**
 * Check deadlines and generate notifications
 */
function check_deadlines(): int
{
    $count = 0;
    $intervals = [
        ['days' => 7,  'label' => '7 hari lagi'],
        ['days' => 3,  'label' => '3 hari lagi'],
        ['days' => 1,  'label' => '1 hari lagi'],
        ['days' => 0,  'label' => 'hari ini'],
    ];

    foreach ($intervals as $interval) {
        $targetDate = date('Y-m-d', strtotime("+{$interval['days']} days"));

        $tasks = db()->fetchAll(
            "SELECT t.id, t.title, t.deadline, ta.user_id
             FROM tasks t
             JOIN task_assignments ta ON t.id = ta.task_id
             WHERE t.deadline = ? AND t.status != 'Completed'",
            [$targetDate]
        );

        foreach ($tasks as $task) {
            // Check if notification already exists
            $existing = db()->fetchColumn(
                "SELECT COUNT(*) FROM notifications
                 WHERE user_id = ? AND type = 'Deadline'
                 AND message LIKE ? AND DATE(created_at) = CURDATE()",
                [$task['user_id'], '%' . $task['title'] . '%']
            );

            if ($existing == 0) {
                if ($interval['days'] === 0) {
                    $title = 'Deadline Hari Ini';
                    $message = "Deadline tugas \"{$task['title']}\" adalah hari ini!";
                } else {
                    $title = 'Deadline Mendekati';
                    $message = "Deadline tugas \"{$task['title']}\" tinggal {$interval['label']}.";
                }
                create_notification($task['user_id'], $title, $message, 'Deadline');
                $count++;
            }
        }
    }

    // Overdue tasks
    $overdueTasks = db()->fetchAll(
        "SELECT t.id, t.title, t.deadline, ta.user_id
         FROM tasks t
         JOIN task_assignments ta ON t.id = ta.task_id
         WHERE t.deadline < CURDATE() AND t.status != 'Completed'"
    );

    foreach ($overdueTasks as $task) {
        $existing = db()->fetchColumn(
            "SELECT COUNT(*) FROM notifications
             WHERE user_id = ? AND type = 'Deadline'
             AND message LIKE ? AND message LIKE '%melewati%' AND DATE(created_at) = CURDATE()",
            [$task['user_id'], '%' . $task['title'] . '%']
        );

        if ($existing == 0) {
            create_notification(
                $task['user_id'],
                'Deadline Terlewat',
                "Tugas \"{$task['title']}\" telah melewati batas waktu.",
                'Deadline'
            );
            $count++;
        }
    }

    return $count;
}

/**
 * Format date to Indonesian locale
 */
function format_date(string $date, bool $withTime = false): string
{
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int) date('m', $timestamp)];
    $year = date('Y', $timestamp);

    $formatted = "{$day} {$month} {$year}";
    if ($withTime) {
        $formatted .= ' ' . date('H:i', $timestamp);
    }
    return $formatted;
}

/**
 * Calculate completion percentage from checklists
 */
function get_completion_percentage(int $assignmentId): int
{
    $total = db()->fetchColumn(
        "SELECT COUNT(*) FROM task_checklists WHERE task_assignment_id = ?",
        [$assignmentId]
    );
    if ($total == 0) return 0;

    $completed = db()->fetchColumn(
        "SELECT COUNT(*) FROM task_checklists WHERE task_assignment_id = ? AND is_completed = 1",
        [$assignmentId]
    );

    return (int) round(($completed / $total) * 100);
}

/**
 * Get task overall progress
 */
function get_task_progress(int $taskId): int
{
    $assignments = db()->fetchAll(
        "SELECT id FROM task_assignments WHERE task_id = ?",
        [$taskId]
    );
    if (empty($assignments)) return 0;

    $total = 0;
    foreach ($assignments as $a) {
        $total += get_completion_percentage($a['id']);
    }
    return (int) round($total / count($assignments));
}

/**
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output CSRF hidden field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf(): bool
{
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    // Regenerate token after verification
    unset($_SESSION['csrf_token']);
    return $valid;
}

/**
 * Get current logged-in user
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Check user role
 */
function has_role(string $role): bool
{
    return is_logged_in() && $_SESSION['user']['role'] === $role;
}

/**
 * Get base URL
 */
function base_url(string $path = ''): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Find the project root relative to the script
    $projectRoot = '';
    $currentPath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    $rootPath = realpath(__DIR__ . '/..');
    if ($currentPath && $rootPath) {
        $relative = str_replace('\\', '/', substr($currentPath, strlen($rootPath)));
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // Remove the relative path from script dir to get project base
        if ($relative && $relative !== '/') {
            $projectRoot = substr($scriptDir, 0, strlen($scriptDir) - strlen($relative));
        } else {
            $projectRoot = $scriptDir;
        }
    }
    $projectRoot = rtrim($projectRoot, '/');
    return $protocol . '://' . $host . $projectRoot . '/' . ltrim($path, '/');
}

/**
 * Redirect to a URL
 */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Get user initials for avatar
 */
function get_initials(string $name): string
{
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials;
}

/**
 * Get unread notification count
 */
function unread_notification_count(?int $userId = null): int
{
    $userId = $userId ?? (current_user()['id'] ?? 0);
    return db()->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}

/**
 * Get priority badge class
 */
function priority_class(string $priority): string
{
    return match ($priority) {
        'High'   => 'badge-danger',
        'Medium' => 'badge-warning',
        'Low'    => 'badge-info',
        default  => 'badge-secondary',
    };
}

/**
 * Get status badge class
 */
function status_class(string $status): string
{
    return match ($status) {
        'Completed'   => 'badge-success',
        'In Progress' => 'badge-warning',
        'Not Started' => 'badge-secondary',
        default       => 'badge-secondary',
    };
}

/**
 * Calculate days until deadline
 */
function days_until(string $date): int
{
    $now = new DateTime(date('Y-m-d'));
    $deadline = new DateTime($date);
    $diff = $now->diff($deadline);
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * Get deadline label
 * Jika status 'Completed', tampilkan tanggal biasa tanpa peringatan keterlambatan
 */
function deadline_label(string $date, string $status = ''): string
{
    // Jika tugas sudah selesai, tampilkan tanggal saja tanpa warning
    if ($status === 'Completed') {
        return '<span class="deadline-safe">' . format_date($date) . '</span>';
    }

    $days = days_until($date);
    if ($days < 0) {
        return '<span class="deadline-overdue">Terlambat ' . abs($days) . ' hari</span>';
    } elseif ($days === 0) {
        return '<span class="deadline-today">Hari ini</span>';
    } elseif ($days <= 3) {
        return '<span class="deadline-urgent">' . $days . ' hari lagi</span>';
    } elseif ($days <= 7) {
        return '<span class="deadline-warning">' . $days . ' hari lagi</span>';
    } else {
        return '<span class="deadline-safe">' . $days . ' hari lagi</span>';
    }
}
