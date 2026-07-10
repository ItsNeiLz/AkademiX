<?php
$pageTitle = 'Pembagian Tugas';
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
    SELECT t.*, c.course_name, g.group_name 
    FROM tasks t
    LEFT JOIN courses c ON t.course_id = c.id
    LEFT JOIN `groups` g ON t.group_id = g.id
    WHERE t.id = ?
", [$id]);

if (!$task) {
    flash('error', 'Tugas tidak ditemukan.');
    redirect('tasks/index.php');
}

// If it's an individual task, block assignment page
if (empty($task['group_id'])) {
    flash('error', 'Tugas Individu tidak dapat dibagikan ke pengguna lain.');
    redirect("tasks/detail.php?id=$id");
}

// Only Admin or creator can manage assignments
if ($user['role'] !== 'Admin' && $task['created_by'] != $user['id']) {
    flash('error', 'Anda tidak memiliki akses untuk mengelola pembagian tugas ini.');
    redirect("tasks/detail.php?id=$id");
}

// Fetch group members
$groupMembers = db()->fetchAll("
    SELECT u.id, u.name, u.nim, u.role
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY u.name ASC
", [$task['group_id']]);

// Fetch current assignments
$assignments = db()->fetchAll("
    SELECT * FROM task_assignments WHERE task_id = ?
", [$id]);

$assignedUserIds = array_column($assignments, 'user_id');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_assignment') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $checklists = array_filter(array_map('trim', $_POST['checklists'] ?? []));

            if (!$user_id) {
                flash('error', 'Silakan pilih anggota kelompok.');
            } else if (in_array($user_id, $assignedUserIds)) {
                flash('error', 'Anggota tersebut sudah mendapatkan bagian tugas ini.');
            } else {
                try {
                    db()->beginTransaction();

                    // Create assignment
                    $assignmentId = db()->insert('task_assignments', [
                        'task_id' => $id,
                        'user_id' => $user_id,
                        'status' => 'Not Started',
                        'completion_percentage' => 0
                    ]);

                    // Create checklists
                    if (!empty($checklists)) {
                        foreach ($checklists as $item) {
                            if (!empty($item)) {
                                db()->insert('task_checklists', [
                                    'task_assignment_id' => $assignmentId,
                                    'checklist_item' => $item,
                                    'is_completed' => 0
                                ]);
                            }
                        }
                    }

                    // Update task status if it was "Completed"
                    if ($task['status'] === 'Completed') {
                        db()->update('tasks', ['status' => 'In Progress'], 'id = ?', [$id]);
                    }

                    db()->commit();
                    
                    // Create notification
                    $assignedUser = db()->fetch("SELECT name FROM users WHERE id = ?", [$user_id]);
                    create_notification($user_id, 'Tugas Baru', 'Anda telah ditugaskan untuk mengerjakan bagian dari "' . $task['title'] . '".', 'Assignment');
                    log_activity($user['id'], "Membagikan tugas kepada " . $assignedUser['name']);
                    
                    flash('success', 'Tugas berhasil dibagikan.');
                    redirect("tasks/assign.php?id=$id");

                } catch (Exception $e) {
                    db()->rollback();
                    flash('error', 'Terjadi kesalahan sistem.');
                }
            }
        } else if ($action === 'remove_assignment') {
            $assignment_id = (int)($_POST['assignment_id'] ?? 0);
            
            if ($assignment_id) {
                db()->delete('task_assignments', 'id = ?', [$assignment_id]);
                flash('success', 'Pembagian tugas berhasil dihapus.');
            }
            redirect("tasks/assign.php?id=$id");
        }
    }
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
            <a href="detail.php?id=<?= $id ?>"><?= sanitize($task['title']) ?></a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Pembagian</span>
        </div>
        <h1>Pembagian Tugas</h1>
        <p><?= sanitize($task['group_name']) ?> — <?= sanitize($task['title']) ?></p>
    </div>
    <div class="page-header-right">
        <a href="detail.php?id=<?= $id ?>" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail
        </a>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Bagikan Tugas Baru</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_assignment">

                    <div class="form-group">
                        <label class="form-label" for="user_id">Pilih Anggota <span class="required">*</span></label>
                        <select id="user_id" name="user_id" class="form-control" required>
                            <option value="">-- Pilih Mahasiswa --</option>
                            <?php foreach ($groupMembers as $m): ?>
                                <?php if (!in_array($m['id'], $assignedUserIds)): ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= sanitize($m['name']) ?> (<?= sanitize($m['role']) ?>)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Daftar Pekerjaan (Checklist)</label>
                        <div class="form-hint mb-2">Tambahkan sub-tugas yang harus diselesaikan oleh anggota ini.</div>
                        
                        <div id="checklist-container">
                            <div class="d-flex align-center gap-1 mb-2">
                                <input type="text" name="checklists[]" class="form-control" placeholder="Contoh: Membuat wireframe halaman login">
                            </div>
                            <div class="d-flex align-center gap-1 mb-2">
                                <input type="text" name="checklists[]" class="form-control" placeholder="Contoh: Menyiapkan aset gambar">
                            </div>
                            <div class="d-flex align-center gap-1 mb-2">
                                <input type="text" name="checklists[]" class="form-control" placeholder="">
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-ghost btn-sm mt-1" onclick="addChecklistInput()">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                    </div>

                    <div class="text-right mt-3">
                        <button type="submit" class="btn btn-primary">Bagikan Tugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Daftar Pembagian Saat Ini</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <p>Belum ada tugas yang dibagikan ke anggota.</p>
                </div>
                <?php else: ?>
                <div class="table-wrapper" style="border: none; border-radius: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th style="text-align: center;">Item</th>
                                <th style="width: 80px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): 
                                // Get user name
                                $assigneeName = 'Unknown';
                                foreach ($groupMembers as $m) {
                                    if ($m['id'] == $a['user_id']) {
                                        $assigneeName = $m['name'];
                                        break;
                                    }
                                }
                                
                                // Count checklists
                                $chkCount = db()->count('task_checklists', 'task_assignment_id = ?', [$a['id']]);
                            ?>
                            <tr>
                                <td><strong><?= sanitize($assigneeName) ?></strong></td>
                                <td style="text-align: center;">
                                    <span class="badge badge-secondary"><?= $chkCount ?> checklist</span>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: center;">
                                        <form id="remove-assign-<?= $a['id'] ?>" action="" method="POST" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove_assignment">
                                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                            <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Hapus Pembagian" onclick="confirmDelete('remove-assign-<?= $a['id'] ?>', 'Pembagian tugas untuk <?= addslashes(sanitize($assigneeName)) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function addChecklistInput() {
    const container = document.getElementById('checklist-container');
    const div = document.createElement('div');
    div.className = 'd-flex align-center gap-1 mb-2';
    div.innerHTML = `
        <input type="text" name="checklists[]" class="form-control" placeholder="">
        <button type="button" class="btn btn-ghost btn-icon btn-sm text-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
