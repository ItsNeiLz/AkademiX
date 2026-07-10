<?php
$pageTitle = 'Buat Kelompok';
$activePage = 'groups';
require_once __DIR__ . '/../includes/auth_check.php';


$courses = db()->fetchAll("SELECT id, course_name FROM courses ORDER BY course_name ASC");

// Fetch available users to be leader (if Admin is creating)
$leaders = [];
if (current_user()['role'] === 'Admin') {
    $leaders = db()->fetchAll("SELECT id, name FROM users WHERE role = 'Ketua Kelompok' ORDER BY name ASC");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $group_name = sanitize($_POST['group_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        // Admin can select leader, Ketua Kelompok defaults to themselves
        $leader_id = current_user()['role'] === 'Admin' 
            ? (int)($_POST['leader_id'] ?? 0) 
            : current_user()['id'];

        $errors = validate_required([
            'group_name' => 'Nama Kelompok',
            'course_id' => 'Mata Kuliah'
        ], $_POST);

        if (current_user()['role'] === 'Admin' && empty($leader_id)) {
            $errors[] = 'Ketua Kelompok wajib dipilih.';
        }

        if (empty($errors)) {
            $groupId = db()->insert('groups', [
                'group_name' => $group_name,
                'description' => $description,
                'course_id' => $course_id,
                'leader_id' => $leader_id
            ]);

            if ($groupId) {
                // Add leader as the first member
                db()->insert('group_members', [
                    'group_id' => $groupId,
                    'user_id' => $leader_id
                ]);

                log_activity(current_user()['id'], "Membuat kelompok baru: $group_name");
                flash('success', 'Kelompok berhasil dibuat. Silakan tambahkan anggota.');
                redirect("groups/members.php?id=$groupId");
            } else {
                $errors[] = 'Gagal menyimpan ke database.';
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
            <a href="index.php">Kelompok</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Buat Kelompok</span>
        </div>
        <h1>Buat Kelompok Baru</h1>
    </div>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="group_name">Nama Kelompok <span class="required">*</span></label>
                <input type="text" id="group_name" name="group_name" class="form-control" value="<?= sanitize($_POST['group_name'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="course_id">Mata Kuliah <span class="required">*</span></label>
                    <select id="course_id" name="course_id" class="form-control" required>
                        <option value="">Pilih Mata Kuliah</option>
                        <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (($_POST['course_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= sanitize($c['course_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (current_user()['role'] === 'Admin'): ?>
                <div class="form-group">
                    <label class="form-label" for="leader_id">Ketua Kelompok <span class="required">*</span></label>
                    <select id="leader_id" name="leader_id" class="form-control" required>
                        <option value="">Pilih Ketua Kelompok</option>
                        <?php foreach ($leaders as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= (($_POST['leader_id'] ?? '') == $l['id']) ? 'selected' : '' ?>>
                            <?= sanitize($l['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Ketua Kelompok</label>
                    <input type="text" class="form-control" value="<?= current_user()['name'] ?>" disabled>
                    <div class="form-hint">Anda secara otomatis menjadi ketua.</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Deskripsi / Topik Proyek</label>
                <textarea id="description" name="description" class="form-control" placeholder="Jelaskan topik tugas atau proyek kelompok ini..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="index.php" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan & Lanjut Tambah Anggota</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
