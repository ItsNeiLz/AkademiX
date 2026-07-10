<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $nim = sanitize($_POST['nim'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = validate_required([
            'name' => 'Nama Lengkap',
            'nim' => 'NIM',
            'email' => 'Email',
            'password' => 'Password',
            'confirm_password' => 'Konfirmasi Password'
        ], $_POST);

        if (!empty($email) && !validate_email($email)) {
            $errors[] = 'Format email tidak valid.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }

        if (empty($errors)) {
            // Check if email or NIM already exists
            $existing = db()->fetch(
                "SELECT email, nim FROM users WHERE email = ? OR nim = ?",
                [$email, $nim]
            );

            if ($existing) {
                if ($existing['email'] === $email) $errors[] = 'Email sudah terdaftar.';
                if ($existing['nim'] === $nim) $errors[] = 'NIM sudah terdaftar.';
            } else {
                // Insert new user
                $userId = db()->insert('users', [
                    'name' => $name,
                    'nim' => $nim,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'Anggota' // Default role
                ]);

                if ($userId) {
                    log_activity($userId, 'Mendaftar akun baru di sistem');
                    flash('success', 'Registrasi berhasil! Silakan login.');
                    redirect('auth/login.php');
                } else {
                    $errors[] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                }
            }
        }

        foreach ($errors as $error) {
            flash('error', $error);
        }
    } else {
        flash('error', 'Sesi telah kadaluarsa. Silakan coba lagi.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — AkademiX</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 500px;">
            <div class="auth-brand">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Buat Akun</h1>
                <p>Bergabung dengan AkademiX sekarang</p>
            </div>

            <?= display_flash() ?>

            <form action="" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Masukkan nama lengkap" value="<?= sanitize($_POST['name'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nim">NIM <span class="required">*</span></label>
                        <input type="text" id="nim" name="nim" class="form-control" placeholder="Masukkan NIM" value="<?= sanitize($_POST['nim'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-1">
                    <i class="fas fa-user-check"></i> Daftar
                </button>
            </form>

            <div class="auth-divider">ATAU</div>

            <div class="auth-footer">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </div>
    </div>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
