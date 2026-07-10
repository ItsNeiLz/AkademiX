<?php
$pageTitle = 'Tugas';
$activePage = 'tasks';
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$userId = $user['id'];
$isAdmin = $user['role'] === 'Admin';

// Query to get tasks based on role
if ($isAdmin) {
    // Admin sees all tasks
    $query = "
        SELECT t.*, c.course_name, g.group_name 
        FROM tasks t
        LEFT JOIN courses c ON t.course_id = c.id
        LEFT JOIN `groups` g ON t.group_id = g.id
        ORDER BY t.deadline ASC, t.priority DESC
    ";
    $params = [];
} else {
    // Other users see tasks from their groups or their individual tasks
    $query = "
        SELECT t.*, c.course_name, g.group_name 
        FROM tasks t
        LEFT JOIN courses c ON t.course_id = c.id
        LEFT JOIN `groups` g ON t.group_id = g.id
        WHERE t.group_id IN (SELECT group_id FROM group_members WHERE user_id = ?) 
           OR (t.group_id IS NULL AND t.created_by = ?)
        ORDER BY t.deadline ASC, t.priority DESC
    ";
    $params = [$userId, $userId];
}

$tasks = db()->fetchAll($query, $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Tugas</span>
        </div>
        <h1>Daftar Tugas</h1>
        <p><?= $isAdmin ? 'Semua tugas dari seluruh kelompok.' : 'Tugas dari kelompok belajar Anda.' ?></p>
    </div>
    <div class="page-header-right">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Tugas Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-toolbar">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari judul tugas, mata kuliah, kelompok...">
            </div>
            <div class="table-filters">
                <select class="form-control" style="min-width: 140px; padding-top: 8px; padding-bottom: 8px;" id="statusFilter" onchange="filterTable()">
                    <option value="">Semua Status</option>
                    <option value="Not Started">Not Started</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
        </div>

        <?php if (empty($tasks)): ?>
        <div class="table-empty">
            <i class="fas fa-clipboard-list"></i>
            <p>Belum ada tugas yang tersedia.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table" id="tasksTable">
                <thead>
                    <tr>
                        <th>Tugas</th>
                        <th>Kelompok / Matkul</th>
                        <th>Deadline</th>
                        <th style="text-align: center;">Prioritas</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Progress</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): 
                        $progress = get_task_progress($t['id']);
                    ?>
                    <tr data-status="<?= sanitize($t['status']) ?>">
                        <td>
                            <strong><?= sanitize($t['title']) ?></strong>
                        </td>
                        <td>
                            <div style="font-size: 0.85rem;">
                                <?php if ($t['group_name']): ?>
                                    <?= sanitize($t['group_name']) ?>
                                <?php else: ?>
                                    <span class="badge" style="background-color: var(--primary); color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">Tugas Individu</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($t['course_name']): ?>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= sanitize($t['course_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= deadline_label($t['deadline']) ?></td>
                        <td style="text-align: center;">
                            <span class="badge <?= priority_class($t['priority']) ?>"><?= $t['priority'] ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge <?= status_class($t['status']) ?>"><?= $t['status'] ?></span>
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex align-center gap-1" style="justify-content: center;">
                                <div class="progress" style="width: 60px; height: 6px; margin: 0;">
                                    <div class="progress-bar <?= $progress == 100 ? 'success' : 'warning' ?>" style="width: <?= $progress ?>%"></div>
                                </div>
                                <span style="font-size: 0.75rem; width: 30px;"><?= $progress ?>%</span>
                            </div>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content: center;">
                                <a href="detail.php?id=<?= $t['id'] ?>" class="btn btn-info btn-icon btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($isAdmin || $t['created_by'] == $userId): ?>
                                <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form id="delete-form-<?= $t['id'] ?>" action="delete.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Hapus" onclick="confirmDelete('delete-form-<?= $t['id'] ?>', '<?= addslashes(sanitize($t['title'])) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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

<script>
function filterTable() {
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#tasksTable tbody tr');
    
    rows.forEach(row => {
        if (status === '' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
