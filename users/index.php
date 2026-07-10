<?php
$pageTitle = 'Manajemen User';
$activePage = 'users';
require_once __DIR__ . '/../includes/auth_check.php';

// Only Admin can access this page
require_role('Admin');

$users = db()->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Manajemen User</span>
        </div>
        <h1>Manajemen User</h1>
        <p>Kelola data mahasiswa, ketua kelompok, dan admin.</p>
    </div>
    <div class="page-header-right">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-toolbar">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari nama, NIM, atau email...">
            </div>
            <div class="table-filters">
                <select class="form-control" style="min-width: 150px; padding-top: 8px; padding-bottom: 8px;">
                    <option value="">Semua Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Ketua Kelompok">Ketua Kelompok</option>
                    <option value="Anggota">Anggota</option>
                </select>
            </div>
        </div>

        <?php if (empty($users)): ?>
        <div class="table-empty">
            <i class="fas fa-users-slash"></i>
            <p>Belum ada data user.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>NIM</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-center gap-1">
                                <div class="member-avatar" style="width: 32px; height: 32px; font-size: 0.7rem;">
                                    <?= get_initials($u['name']) ?>
                                </div>
                                <strong><?= sanitize($u['name']) ?></strong>
                            </div>
                        </td>
                        <td><?= sanitize($u['nim']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'Admin' ? 'badge-primary' : ($u['role'] === 'Ketua Kelompok' ? 'badge-info' : 'badge-secondary') ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td><?= format_date($u['created_at']) ?></td>
                        <td>
                            <div class="table-actions" style="justify-content: center;">
                                <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== current_user()['id']): ?>
                                <form id="delete-form-<?= $u['id'] ?>" action="delete.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Hapus" onclick="confirmDelete('delete-form-<?= $u['id'] ?>', '<?= addslashes(sanitize($u['name'])) ?>')">
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
