<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $loginId = sanitize($_POST['login_id'] ?? ''); // can be email or nim
        $password = $_POST['password'] ?? '';

        $errors = validate_required([
            'login_id' => 'Email atau NIM',
            'password' => 'Password'
        ], $_POST);

        if (empty($errors)) {
            // Find user by email or nim
            $user = db()->fetch(
                "SELECT * FROM users WHERE email = ? OR nim = ?",
                [$loginId, $loginId]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'nim'   => $user['nim'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                log_activity($user['id'], 'Melakukan login ke sistem');
                flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
                redirect('dashboard/index.php');
            } else {
                $errors[] = 'Email/NIM atau password salah.';
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
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>AkademiX</h1>
                <p>Platform Manajemen Tugas & Kolaborasi</p>
            </div>

            <?= display_flash() ?>

            <form action="" method="POST" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="login_id">Email atau NIM <span class="required">*</span></label>
                    <input type="text" id="login_id" name="login_id" class="form-control" placeholder="Masukkan email atau NIM Anda" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <div class="auth-divider">ATAU</div>

            <div class="auth-footer">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </div>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
