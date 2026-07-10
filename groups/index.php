<?php
$pageTitle = 'Kelompok';
$activePage = 'groups';
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$userId = $user['id'];
$isAdmin = $user['role'] === 'Admin';

// Query to get groups based on role
if ($isAdmin) {
    // Admin sees all groups
    $query = "
        SELECT g.*, c.course_name, u.name as leader_name, 
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM `groups` g
        JOIN courses c ON g.course_id = c.id
        LEFT JOIN users u ON g.leader_id = u.id
        ORDER BY g.created_at DESC
    ";
    $params = [];
} else {
    // Other users see groups they belong to
    $query = "
        SELECT g.*, c.course_name, u.name as leader_name,
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM `groups` g
        JOIN courses c ON g.course_id = c.id
        LEFT JOIN users u ON g.leader_id = u.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC
    ";
    $params = [$userId];
}

$groups = db()->fetchAll($query, $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Kelompok</span>
        </div>
        <h1>Kelompok Belajar</h1>
        <p><?= $isAdmin ? 'Manajemen semua kelompok belajar.' : 'Daftar kelompok belajar Anda.' ?></p>
    </div>
    <div class="page-header-right">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Kelompok Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-toolbar">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari nama kelompok atau mata kuliah...">
            </div>
        </div>

        <?php if (empty($groups)): ?>
        <div class="table-empty">
            <i class="fas fa-users-slash"></i>
            <p><?= $isAdmin ? 'Belum ada kelompok yang dibuat.' : 'Anda belum tergabung dalam kelompok apapun.' ?></p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kelompok</th>
                        <th>Mata Kuliah</th>
                        <th>Ketua Kelompok</th>
                        <th style="text-align: center;">Anggota</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $g): ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($g['group_name']) ?></strong>
                            <?php if ($g['leader_id'] == $userId): ?>
                            <span class="badge badge-primary" style="margin-left: 8px;">Ketua Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($g['course_name']) ?></td>
                        <td>
                            <?php if ($g['leader_name']): ?>
                                <div class="d-flex align-center gap-1">
                                    <div class="member-avatar" style="width: 24px; height: 24px; font-size: 0.6rem;">
                                        <?= get_initials($g['leader_name']) ?>
                                    </div>
                                    <?= sanitize($g['leader_name']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted"><em>Belum ada ketua</em></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary"><?= $g['member_count'] ?> / 10</span>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content: center;">
                                <a href="detail.php?id=<?= $g['id'] ?>" class="btn btn-info btn-icon btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($isAdmin || $g['leader_id'] == $userId): ?>
                                <a href="members.php?id=<?= $g['id'] ?>" class="btn btn-primary btn-icon btn-sm" title="Kelola Anggota">
                                    <i class="fas fa-user-plus"></i>
                                </a>
                                <a href="edit.php?id=<?= $g['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form id="delete-form-<?= $g['id'] ?>" action="delete.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Hapus" onclick="confirmDelete('delete-form-<?= $g['id'] ?>', '<?= addslashes(sanitize($g['group_name'])) ?>')">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
