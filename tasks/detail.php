<?php
$pageTitle = 'Detail Tugas';
$activePage = 'tasks';
require_once __DIR__ . '/../includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID Tugas tidak ditemukan.');
    redirect('tasks/index.php');
}

$user = current_user();

// Fetch task info
$task = db()->fetch("
    SELECT t.*, c.course_name, g.group_name, g.leader_id, u.name as creator_name
    FROM tasks t
    LEFT JOIN courses c ON t.course_id = c.id
    LEFT JOIN `groups` g ON t.group_id = g.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
", [$id]);

if (!$task) {
    flash('error', 'Tugas tidak ditemukan.');
    redirect('tasks/index.php');
}

$isAdmin = ($user['role'] === 'Admin');
$isCreator = ($task['created_by'] == $user['id']);
$canEdit = $isAdmin || $isCreator;

$isGroupLeader = (!empty($task['group_id']) && $task['leader_id'] == $user['id']);
$isIndividualTaskCreator = (empty($task['group_id']) && $task['created_by'] == $user['id']);
$canFinishTask = ($isGroupLeader || $isIndividualTaskCreator) && $task['status'] !== 'Completed';
$canUploadFile = ($isAdmin || $isGroupLeader || $isIndividualTaskCreator);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        // Handle Finish Task
        if (isset($_POST['mark_completed'])) {
            if ($canFinishTask) {
                db()->update('tasks', ['status' => 'Completed'], 'id = ?', [$id]);
                log_activity($user['id'], "Menyelesaikan tugas secara manual: " . $task['title']);
                flash('success', 'Tugas berhasil diselesaikan secara manual!');
                redirect("tasks/detail.php?id=$id");
            } else {
                flash('error', 'Anda tidak memiliki hak akses untuk menyelesaikan tugas ini.');
            }
        }

        // Handle File Upload
        if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
            if ($canUploadFile) {
                $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
                $file_name = $_FILES['task_file']['name'];
                $file_size = $_FILES['task_file']['size'];
                $file_tmp = $_FILES['task_file']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_exts)) {
                    flash('error', 'Format file tidak diizinkan.');
                } elseif ($file_size > 10 * 1024 * 1024) { 
                    flash('error', 'Ukuran file maksimal 10MB.');
                } else {
                    $new_file_name = uniqid('task_') . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $file_name);
                    $upload_dir = __DIR__ . '/../assets/uploads/tasks/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                        $file_path = 'assets/uploads/tasks/' . $new_file_name;
                        db()->update('tasks', ['file_path' => $file_path], 'id = ?', [$id]);
                        flash('success', 'Lampiran file berhasil diunggah.');
                        redirect("tasks/detail.php?id=$id");
                    } else {
                        flash('error', 'Gagal mengunggah file.');
                    }
                }
            } else {
                flash('error', 'Anda tidak memiliki hak akses untuk mengunggah file pada tugas ini.');
            }
        }
    }
}

// Fetch assignments with user info
$assignments = db()->fetchAll("
    SELECT ta.*, u.name, u.nim, u.role
    FROM task_assignments ta
    JOIN users u ON ta.user_id = u.id
    WHERE ta.task_id = ?
", [$id]);

// Organize checklists by assignment
$checklistsByAssignment = [];
if (!empty($assignments)) {
    $assignmentIds = array_column($assignments, 'id');
    $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
    
    $checklists = db()->fetchAll("
        SELECT * FROM task_checklists 
        WHERE task_assignment_id IN ($placeholders)
        ORDER BY id ASC
    ", $assignmentIds);

    foreach ($checklists as $c) {
        $checklistsByAssignment[$c['task_assignment_id']][] = $c;
    }
}

// Check if current user is assigned
$myAssignment = null;
foreach ($assignments as $a) {
    if ($a['user_id'] == $user['id']) {
        $myAssignment = $a;
        break;
    }
}

// Calculate overall progress
$progress = get_task_progress($id);

// Update task status based on progress (auto-update)
$newStatus = $task['status'];
if ($progress > 0 && $task['status'] === 'Not Started') {
    $newStatus = 'In Progress';
}

if ($newStatus !== $task['status']) {
    db()->update('tasks', ['status' => $newStatus], 'id = ?', [$id]);
    $task['status'] = $newStatus;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="index.php">Tugas</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Detail</span>
        </div>
        <div class="d-flex align-center gap-2">
            <h1 style="margin-bottom: 0;"><?= sanitize($task['title']) ?></h1>
            <span class="badge <?= priority_class($task['priority']) ?>"><?= $task['priority'] ?> Priority</span>
            <span class="badge <?= status_class($task['status']) ?>"><?= $task['status'] ?></span>
        </div>
        <p><?= sanitize($task['group_name'] ?? 'Tugas Individu') ?> <?= $task['course_name'] ? '— ' . sanitize($task['course_name']) : '' ?></p>
    </div>
    <div class="page-header-right d-flex gap-1">
        <?php if ($canFinishTask): ?>
        <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Selesaikan tugas ini? Progress checklist tidak akan berubah, namun status tugas akan ditandai selesai.');">
            <?= csrf_field() ?>
            <input type="hidden" name="mark_completed" value="1">
            <button type="submit" class="btn" style="background: var(--success); color: white;">
                <i class="fas fa-check-circle"></i> Selesaikan Tugas
            </button>
        </form>
        <?php endif; ?>
        <?php if ($canEdit && !empty($task['group_id'])): ?>
        <a href="assign.php?id=<?= $id ?>" class="btn btn-ghost">
            <i class="fas fa-users-cog"></i> Kelola Pembagian Tugas
        </a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Tugas
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Informasi Tugas</h3>
            </div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="detail-item">
                        <div class="detail-item-label">Deadline</div>
                        <div class="detail-item-value">
                            <?= format_date($task['deadline']) ?> 
                            <div class="mt-1"><?= deadline_label($task['deadline']) ?></div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Dibuat Oleh</div>
                        <div class="detail-item-value"><?= sanitize($task['creator_name']) ?></div>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-item-label">Deskripsi Lengkap</div>
                        <div class="detail-item-value" style="font-weight: 400; line-height: 1.6;">
                            <?= nl2br(sanitize($task['description'] ?: 'Belum ada deskripsi tugas.')) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($task['file_path'])): ?>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-item-label">Lampiran File Tugas</div>
                        <div class="detail-item-value mt-1">
                            <a href="<?= base_url(sanitize($task['file_path'])) ?>" class="btn btn-outline btn-sm" target="_blank" download>
                                <i class="fas fa-download"></i> Unduh Lampiran
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($canUploadFile): ?>
                    <div class="detail-item mt-2" style="grid-column: span 2; border-top: 1px solid var(--border); padding-top: 1rem;">
                        <form action="" method="POST" enctype="multipart/form-data" class="d-flex align-center gap-2">
                            <?= csrf_field() ?>
                            <input type="file" name="task_file" class="form-control" style="max-width: 300px;" required>
                            <button type="submit" class="btn btn-primary btn-sm">Upload Lampiran Baru</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mt-3">
                    <div class="progress-label">
                        <span>Progress Keseluruhan</span>
                        <span class="progress-percentage" id="overallProgress"><?= $progress ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar <?= $progress == 100 ? 'success' : 'warning' ?>" style="width: <?= $progress ?>%" id="overallProgressBar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><?= empty($task['group_id']) ? 'Checklist Tugas' : 'Pembagian Tugas (Sub-tugas)' ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Tugas belum dibagi ke anggota kelompok.</p>
                    <?php if ($canEdit): ?>
                    <a href="assign.php?id=<?= $id ?>" class="btn btn-primary mt-2">Bagikan Sekarang</a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <div class="assignments-list">
                    <?php foreach ($assignments as $a): 
                        $aProgress = get_completion_percentage($a['id']);
                        $isMe = ($a['user_id'] == $user['id']);
                        $chk = $checklistsByAssignment[$a['id']] ?? [];
                    ?>
                    <div class="assignment-item mb-3 p-3" style="border: 1px solid var(--border); border-radius: var(--radius-md); background: <?= $isMe ? 'rgba(102, 126, 234, 0.05)' : 'var(--bg-glass)' ?>;">
                        <div class="d-flex align-center justify-between mb-2">
                            <div class="d-flex align-center gap-1">
                                <div class="member-avatar" style="width: 32px; height: 32px; font-size: 0.7rem;">
                                    <?= get_initials($a['name']) ?>
                                </div>
                                <div>
                                    <strong><?= sanitize($a['name']) ?> <?= $isMe ? '(Anda)' : '' ?></strong>
                                    <div class="text-muted" style="font-size: 0.75rem;">Status: <?= $a['status'] ?></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div style="font-size: 0.85rem; font-weight: 600;"><?= $aProgress ?>% Selesai</div>
                            </div>
                        </div>

                        <?php if (empty($chk)): ?>
                            <div class="text-muted" style="font-size: 0.8rem; font-style: italic;">Belum ada checklist tugas.</div>
                        <?php else: ?>
                            <ul class="checklist mt-2">
                                <?php foreach ($chk as $c): ?>
                                <li class="checklist-item">
                                    <input type="checkbox" class="checklist-checkbox" 
                                        <?= $c['is_completed'] ? 'checked' : '' ?> 
                                        <?= !$isMe ? 'disabled' : '' ?>
                                        onchange="toggleChecklist(<?= $c['id'] ?>, this)">
                                    <span class="checklist-label <?= $c['is_completed'] ? 'completed' : '' ?>">
                                        <?= sanitize($c['checklist_item']) ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
