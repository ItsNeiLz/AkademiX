<?php
$pageTitle = 'Tambah User';
$activePage = 'users';
require_once __DIR__ . '/../includes/auth_check.php';

// Only Admin can access this page
require_role('Admin');

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
            'password' => 'Password',
            'role' => 'Role'
        ], $_POST);

        if (!empty($email) && !validate_email($email)) {
            $errors[] = 'Format email tidak valid.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        if (!in_array($role, ['Admin', 'Ketua Kelompok', 'Anggota'])) {
            $errors[] = 'Role tidak valid.';
        }

        if (empty($errors)) {
            $existing = db()->fetch("SELECT email, nim FROM users WHERE email = ? OR nim = ?", [$email, $nim]);
            if ($existing) {
                if ($existing['email'] === $email) $errors[] = 'Email sudah terdaftar.';
                if ($existing['nim'] === $nim) $errors[] = 'NIM sudah terdaftar.';
            } else {
                $userId = db()->insert('users', [
                    'name' => $name,
                    'nim' => $nim,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role
                ]);

                if ($userId) {
                    log_activity(current_user()['id'], "Menambahkan user baru: $name");
                    flash('success', 'User berhasil ditambahkan.');
                    redirect('users/index.php');
                } else {
                    $errors[] = 'Gagal menyimpan data ke database.';
                }
            }
        }

        foreach ($errors as $error) {
            flash('error', $error);
        }
    } else {
        flash('error', 'Sesi kadaluarsa. Silakan coba lagi.');
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
            <span class="active">Tambah User</span>
        </div>
        <h1>Tambah User Baru</h1>
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
                <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nim">NIM <span class="required">*</span></label>
                    <input type="text" id="nim" name="nim" class="form-control" value="<?= sanitize($_POST['nim'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Role / Peran <span class="required">*</span></label>
                <select id="role" name="role" class="form-control" required>
                    <option value="Anggota" <?= (($_POST['role'] ?? '') === 'Anggota') ? 'selected' : '' ?>>Anggota</option>
                    <option value="Ketua Kelompok" <?= (($_POST['role'] ?? '') === 'Ketua Kelompok') ? 'selected' : '' ?>>Ketua Kelompok</option>
                    <option value="Admin" <?= (($_POST['role'] ?? '') === 'Admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="form-hint">Minimal 6 karakter.</div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="index.php" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
