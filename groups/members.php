<?php
$pageTitle = 'Kelola Anggota Kelompok';
$activePage = 'groups';
require_once __DIR__ . '/../includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

$group = db()->fetch("
    SELECT g.*, c.course_name 
    FROM `groups` g
    JOIN courses c ON g.course_id = c.id
    WHERE g.id = ?
", [$id]);

if (!$group) {
    flash('error', 'Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

// Only Admin or the Group Leader can manage members
if (current_user()['role'] !== 'Admin' && current_user()['id'] != $group['leader_id']) {
    flash('error', 'Anda tidak memiliki akses untuk mengelola anggota kelompok ini.');
    redirect("groups/detail.php?id=$id");
}

// Handle Add Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (verify_csrf()) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id) {
            // Check if already a member
            $exists = db()->count('group_members', 'group_id = ? AND user_id = ?', [$id, $user_id]);
            
            if ($exists) {
                flash('warning', 'User tersebut sudah menjadi anggota kelompok ini.');
            } else {
                db()->insert('group_members', [
                    'group_id' => $id,
                    'user_id' => $user_id
                ]);
                
                // Get user name for logging
                $addedUser = db()->fetch("SELECT name FROM users WHERE id = ?", [$user_id]);
                log_activity(current_user()['id'], "Menambahkan " . $addedUser['name'] . " ke kelompok " . $group['group_name']);
                
                flash('success', 'Anggota berhasil ditambahkan.');
            }
        } else {
            flash('error', 'Silakan pilih user yang akan ditambahkan.');
        }
        redirect("groups/members.php?id=$id");
    }
}

// Handle Remove Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    if (verify_csrf()) {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($member_id) {
            // Prevent removing the leader
            if ($user_id == $group['leader_id']) {
                flash('error', 'Tidak dapat menghapus Ketua Kelompok. Silakan ubah ketua kelompok terlebih dahulu.');
            } else {
                $removedUser = db()->fetch("SELECT name FROM users WHERE id = ?", [$user_id]);
                
                db()->delete('group_members', 'id = ?', [$member_id]);
                
                log_activity(current_user()['id'], "Menghapus " . $removedUser['name'] . " dari kelompok " . $group['group_name']);
                flash('success', 'Anggota berhasil dihapus dari kelompok.');
            }
        }
        redirect("groups/members.php?id=$id");
    }
}

// Fetch current members
$members = db()->fetchAll("
    SELECT gm.id as member_id, u.id as user_id, u.name, u.nim, u.role, gm.joined_at
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY (u.id = ?) DESC, u.name ASC
", [$id, $group['leader_id']]); // Order by leader first, then name

// Fetch available users to add (not already in this group)
$availableUsers = db()->fetchAll("
    SELECT id, name, nim, role 
    FROM users 
    WHERE id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)
    AND role != 'Admin'
    ORDER BY name ASC
", [$id]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="index.php">Kelompok</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="detail.php?id=<?= $id ?>"><?= sanitize($group['group_name']) ?></a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Anggota</span>
        </div>
        <h1>Kelola Anggota Kelompok</h1>
        <p><?= sanitize($group['group_name']) ?> — <?= sanitize($group['course_name']) ?></p>
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
                <h3>Tambah Anggota Baru</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="d-flex align-center gap-2" style="flex-wrap: wrap;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group mb-0" style="flex: 1; min-width: 250px;">
                        <select name="user_id" class="form-control" required>
                            <option value="">-- Pilih Mahasiswa --</option>
                            <?php foreach ($availableUsers as $au): ?>
                            <option value="<?= $au['id'] ?>">
                                <?= sanitize($au['name']) ?> (<?= sanitize($au['nim']) ?>) - <?= sanitize($au['role']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: -2px;">
                        <i class="fas fa-plus"></i> Tambah
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Anggota Saat Ini (<?= count($members) ?>)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-wrapper" style="border: none; border-radius: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>NIM</th>
                                <th>Peran</th>
                                <th style="width: 80px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center gap-1">
                                        <div class="member-avatar" style="width: 32px; height: 32px; font-size: 0.7rem;">
                                            <?= get_initials($m['name']) ?>
                                        </div>
                                        <strong><?= sanitize($m['name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= sanitize($m['nim']) ?></td>
                                <td>
                                    <?php if ($m['user_id'] == $group['leader_id']): ?>
                                        <span class="badge badge-primary">Ketua Kelompok</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Anggota</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: center;">
                                        <?php if ($m['user_id'] != $group['leader_id']): ?>
                                        <form id="remove-form-<?= $m['member_id'] ?>" action="" method="POST" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="member_id" value="<?= $m['member_id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                                            <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Keluarkan" onclick="confirmDelete('remove-form-<?= $m['member_id'] ?>', '<?= addslashes(sanitize($m['name'])) ?>')">
                                                <i class="fas fa-user-minus"></i>
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
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Informasi Kelompok</h3>
            </div>
            <div class="card-body">
                <div class="detail-info" style="grid-template-columns: 1fr;">
                    <div class="detail-item">
                        <div class="detail-item-label">Mata Kuliah</div>
                        <div class="detail-item-value"><?= sanitize($group['course_name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item-label">Dibuat Pada</div>
                        <div class="detail-item-value"><?= format_date($group['created_at']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
