<?php
$pageTitle = 'Detail Kelompok';
$activePage = 'groups';
require_once __DIR__ . '/../includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

$user = current_user();

// Fetch group info
$group = db()->fetch("
    SELECT g.*, c.course_name, c.lecturer_name, u.name as leader_name
    FROM `groups` g
    JOIN courses c ON g.course_id = c.id
    LEFT JOIN users u ON g.leader_id = u.id
    WHERE g.id = ?
", [$id]);

if (!$group) {
    flash('error', 'Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

$isLeader = ($group['leader_id'] == $user['id']);
$isAdmin = ($user['role'] === 'Admin');
$canManage = $isAdmin || $isLeader;

// Fetch members
$members = db()->fetchAll("
    SELECT u.id, u.name, u.nim, u.role, gm.joined_at
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY (u.id = ?) DESC, u.name ASC
", [$id, $group['leader_id']]);

// Fetch tasks for this group
$tasks = db()->fetchAll("
    SELECT t.*, u.name as creator_name,
    (SELECT COUNT(*) FROM task_assignments WHERE task_id = t.id) as assignment_count
    FROM tasks t
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.group_id = ?
    ORDER BY t.deadline ASC
", [$id]);

// Calculate group overall progress
$totalTasksProgress = 0;
foreach ($tasks as $t) {
    $totalTasksProgress += get_task_progress($t['id']);
}
$groupProgress = count($tasks) > 0 ? round($totalTasksProgress / count($tasks)) : 0;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="index.php">Kelompok</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Detail</span>
        </div>
        <h1><?= sanitize($group['group_name']) ?></h1>
        <p><?= sanitize($group['course_name']) ?></p>
    </div>
    <div class="page-header-right d-flex gap-1">
        <?php if ($canManage): ?>
        <a href="members.php?id=<?= $id ?>" class="btn btn-ghost">
            <i class="fas fa-users"></i> Kelola Anggota
        </a>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Kelompok
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Informasi Proyek</h3>
            </div>
            <div class="card-body">
                <div class="detail-info">
                    <div class="detail-item">
                        <div class="detail-item-label">Mata Kuliah</div>
                        <div class="detail-item-value"><?= sanitize($group['course_name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Dosen Pengampu</div>
                        <div class="detail-item-value"><?= sanitize($group['lecturer_name']) ?></div>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-item-label">Deskripsi / Topik</div>
                        <div class="detail-item-value" style="font-weight: 400; line-height: 1.6;">
                            <?= nl2br(sanitize($group['description'] ?: 'Belum ada deskripsi.')) ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="progress-label">
                        <span>Progress Keseluruhan Kelompok</span>
                        <span class="progress-percentage"><?= $groupProgress ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar <?= $groupProgress == 100 ? 'success' : 'warning' ?>" style="width: <?= $groupProgress ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Tugas Kelompok (<?= count($tasks) ?>)</h3>
                <a href="<?= base_url('tasks/create.php?group_id=' . $id) ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Tugas
                </a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($tasks)): ?>
                <div class="empty-state" style="padding: 40px 24px;">
                    <i class="fas fa-tasks"></i>
                    <p>Belum ada tugas untuk kelompok ini.</p>
                </div>
                <?php else: ?>
                <div class="table-wrapper" style="border: none; border-radius: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul Tugas</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th style="text-align: center;">Progress</th>
                                <th style="width: 80px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $t): 
                                $progress = get_task_progress($t['id']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($t['title']) ?></strong><br>
                                    <span class="badge <?= priority_class($t['priority']) ?> mt-1"><?= $t['priority'] ?></span>
                                </td>
                                <td><span class="badge <?= status_class($t['status']) ?>"><?= $t['status'] ?></span></td>
                                <td><?= deadline_label($t['deadline']) ?></td>
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
                                        <a href="<?= base_url('tasks/detail.php?id=' . $t['id']) ?>" class="btn btn-ghost btn-icon btn-sm" title="Lihat Tugas">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
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

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Anggota (<?= count($members) ?>)</h3>
            </div>
            <div class="card-body">
                <div class="members-grid" style="grid-template-columns: 1fr;">
                    <?php foreach ($members as $m): ?>
                    <div class="member-card">
                        <div class="member-avatar">
                            <?= get_initials($m['name']) ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= sanitize($m['name']) ?></div>
                            <div class="member-role-badge">
                                <?= $m['id'] == $group['leader_id'] ? '<i class="fas fa-star" style="color: var(--warning); margin-right: 4px;"></i> Ketua Kelompok' : 'Anggota' ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
