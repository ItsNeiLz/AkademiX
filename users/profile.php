<?php
$pageTitle = 'Profil Saya';
$activePage = 'profile';
require_once __DIR__ . '/../includes/auth_check.php';

$userId = current_user()['id'];
$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = validate_required(['name' => 'Nama Lengkap'], $_POST);

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Password baru minimal 6 karakter.';
            }
            if ($password !== $confirm_password) {
                $errors[] = 'Konfirmasi password tidak cocok.';
            }
        }

        if (empty($errors)) {
            $data = ['name' => $name];

            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            db()->update('users', $data, 'id = ?', [$userId]);
            
            // Update session
            $_SESSION['user']['name'] = $name;

            log_activity($userId, "Memperbarui profil akun");
            flash('success', 'Profil berhasil diperbarui.');
            redirect('users/profile.php');
        }

        foreach ($errors as $error) {
            flash('error', $error);
        }
    }
}

// Get user's recent activity
$activities = db()->fetchAll("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
", [$userId]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Profil Saya</span>
        </div>
        <h1>Profil Saya</h1>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Informasi Profil</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? $user['name']) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIM</label>
                            <input type="text" class="form-control" value="<?= sanitize($user['nim']) ?>" disabled>
                            <div class="form-hint">NIM tidak dapat diubah. Hubungi Admin jika ada kesalahan.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                            <div class="form-hint">Email tidak dapat diubah. Hubungi Admin jika ada kesalahan.</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= sanitize($user['role']) ?>" disabled>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">
                    
                    <h4 style="margin-bottom: 16px;">Ubah Password</h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password Baru</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                        </div>
                    </div>

                    <div class="d-flex" style="justify-content: flex-end; margin-top: 16px;">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Aktivitas Terakhir Saya</h3>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>Belum ada aktivitas.</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($activities as $act): ?>
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <?= sanitize(str_replace(explode(' ', $user['name'])[0] . ' ', '', $act['activity'])) ?>
                        </div>
                        <div class="timeline-time">
                            <?= format_date($act['created_at'], true) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
