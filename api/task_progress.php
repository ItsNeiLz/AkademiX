<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized or invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$checklist_id = isset($input['checklist_id']) ? (int)$input['checklist_id'] : 0;
$is_completed = isset($input['is_completed']) ? (int)$input['is_completed'] : 0;

if (!$checklist_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // 1. Verify ownership/access
    $checklist = db()->fetch("
        SELECT tc.task_assignment_id, ta.user_id, ta.task_id 
        FROM task_checklists tc
        JOIN task_assignments ta ON tc.task_assignment_id = ta.id
        WHERE tc.id = ?
    ", [$checklist_id]);

    if (!$checklist) {
        echo json_encode(['success' => false, 'message' => 'Checklist not found']);
        exit;
    }

    if ($checklist['user_id'] != current_user()['id'] && current_user()['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => 'Not authorized to update this checklist']);
        exit;
    }

    // 2. Update checklist status
    db()->update('task_checklists', ['is_completed' => $is_completed], 'id = ?', [$checklist_id]);

    // 3. Update assignment completion percentage & status
    $assignmentId = $checklist['task_assignment_id'];
    $percentage = get_completion_percentage($assignmentId);
    $aStatus = ($percentage == 100) ? 'Completed' : (($percentage > 0) ? 'In Progress' : 'Not Started');
    $completedAt = ($percentage == 100) ? date('Y-m-d H:i:s') : null;
    
    db()->update('task_assignments', [
        'completion_percentage' => $percentage,
        'status' => $aStatus,
        'completed_at' => $completedAt
    ], 'id = ?', [$assignmentId]);

    // 4. Update overall task status (ONLY to In Progress, not Completed automatically)
    $taskId = $checklist['task_id'];
    $taskProgress = get_task_progress($taskId);
    $tStatus = ($taskProgress > 0) ? 'In Progress' : 'Not Started';
    
    // Only update if current status is Not Started (so we don't downgrade from Completed)
    $currentTStatus = db()->fetchColumn("SELECT status FROM tasks WHERE id = ?", [$taskId]);
    if ($currentTStatus === 'Not Started' && $tStatus === 'In Progress') {
        db()->update('tasks', ['status' => $tStatus], 'id = ?', [$taskId]);
    } else {
        $tStatus = $currentTStatus; // Keep existing status for response
    }

    // 5. Log activity
    if ($is_completed) {
        $itemText = db()->fetchColumn("SELECT checklist_item FROM task_checklists WHERE id = ?", [$checklist_id]);
        log_activity(current_user()['id'], "Menyelesaikan checklist: $itemText");
    }

    echo json_encode([
        'success' => true, 
        'progress' => $taskProgress,
        'assignment_progress' => $percentage,
        'task_status' => $tStatus
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
