<?php
$pageTitle = 'Edit Kelompok';
$activePage = 'groups';
require_once __DIR__ . '/../includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

$group = db()->fetch("SELECT * FROM `groups` WHERE id = ?", [$id]);
if (!$group) {
    flash('error', 'Kelompok tidak ditemukan.');
    redirect('groups/index.php');
}

// Only Admin or the Group Leader can edit
if (current_user()['role'] !== 'Admin' && current_user()['id'] != $group['leader_id']) {
    flash('error', 'Anda tidak memiliki akses untuk mengubah kelompok ini.');
    redirect('groups/index.php');
}

$courses = db()->fetchAll("SELECT id, course_name FROM courses ORDER BY course_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $group_name = sanitize($_POST['group_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);

        $errors = validate_required([
            'group_name' => 'Nama Kelompok',
            'course_id' => 'Mata Kuliah'
        ], $_POST);

        if (empty($errors)) {
            db()->update('groups', [
                'group_name' => $group_name,
                'description' => $description,
                'course_id' => $course_id
            ], 'id = ?', [$id]);

            log_activity(current_user()['id'], "Mengubah data kelompok: $group_name");
            flash('success', 'Data kelompok berhasil diperbarui.');
            redirect('groups/index.php');
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
            <span class="active">Edit Kelompok</span>
        </div>
        <h1>Edit Kelompok</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="group_name">Nama Kelompok <span class="required">*</span></label>
                <input type="text" id="group_name" name="group_name" class="form-control" value="<?= sanitize($_POST['group_name'] ?? $group['group_name']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="course_id">Mata Kuliah <span class="required">*</span></label>
                <select id="course_id" name="course_id" class="form-control" required>
                    <option value="">Pilih Mata Kuliah</option>
                    <?php 
                    $selectedCourse = $_POST['course_id'] ?? $group['course_id'];
                    foreach ($courses as $c): 
                    ?>
                    <option value="<?= $c['id'] ?>" <?= ($selectedCourse == $c['id']) ? 'selected' : '' ?>>
                        <?= sanitize($c['course_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Deskripsi / Topik Proyek</label>
                <textarea id="description" name="description" class="form-control" placeholder="Jelaskan topik tugas atau proyek kelompok ini..."><?= sanitize($_POST['description'] ?? $group['description']) ?></textarea>
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
