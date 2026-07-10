<?php
$pageTitle = 'Edit User';
$activePage = 'users';
require_once __DIR__ . '/../includes/auth_check.php';

require_role('Admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID User tidak ditemukan.');
    redirect('users/index.php');
}

$editUser = db()->fetch("SELECT * FROM users WHERE id = ?", [$id]);
if (!$editUser) {
    flash('error', 'User tidak ditemukan.');
    redirect('users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $nim = sanitize($_POST['nim'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Anggota';

        $errors = validate_required([
            'name' => 'Nama Lengkap',
            'nim' => 'NIM',
            'email' => 'Email',
            'role' => 'Role'
        ], $_POST);

        if (!empty($email) && !validate_email($email)) {
            $errors[] = 'Format email tidak valid.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        if (empty($errors)) {
            $existing = db()->fetch("SELECT id, email, nim FROM users WHERE (email = ? OR nim = ?) AND id != ?", [$email, $nim, $id]);
            if ($existing) {
                if ($existing['email'] === $email) $errors[] = 'Email sudah digunakan oleh user lain.';
                if ($existing['nim'] === $nim) $errors[] = 'NIM sudah digunakan oleh user lain.';
            } else {
                $data = [
                    'name' => $name,
                    'nim' => $nim,
                    'email' => $email,
                    'role' => $role
                ];

                if (!empty($password)) {
                    $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                db()->update('users', $data, 'id = ?', [$id]);
                log_activity(current_user()['id'], "Mengubah data user: $name");
                flash('success', 'Data user berhasil diperbarui.');
                redirect('users/index.php');
            }
        }

        foreach ($errors as $error) {
            flash('error', $error);
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
            <a href="index.php">Manajemen User</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Edit User</span>
        </div>
        <h1>Edit User</h1>
    </div>
    <div class="page-header-right">
        <a href="index.php" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? $editUser['name']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nim">NIM <span class="required">*</span></label>
                    <input type="text" id="nim" name="nim" class="form-control" value="<?= sanitize($_POST['nim'] ?? $editUser['nim']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? $editUser['email']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Role / Peran <span class="required">*</span></label>
                <select id="role" name="role" class="form-control" required <?= $editUser['id'] === current_user()['id'] ? 'disabled' : '' ?>>
                    <?php $selectedRole = $_POST['role'] ?? $editUser['role']; ?>
                    <option value="Anggota" <?= $selectedRole === 'Anggota' ? 'selected' : '' ?>>Anggota</option>
                    <option value="Ketua Kelompok" <?= $selectedRole === 'Ketua Kelompok' ? 'selected' : '' ?>>Ketua Kelompok</option>
                    <option value="Admin" <?= $selectedRole === 'Admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <?php if ($editUser['id'] === current_user()['id']): ?>
                <input type="hidden" name="role" value="<?= $editUser['role'] ?>">
                <div class="form-hint text-warning">Anda tidak dapat mengubah role Anda sendiri.</div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password Baru</label>
                <input type="password" id="password" name="password" class="form-control">
                <div class="form-hint">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter.</div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="index.php" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
