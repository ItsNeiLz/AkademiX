<?php
$pageTitle = 'Tambah Mata Kuliah';
$activePage = 'courses';
require_once __DIR__ . '/../includes/auth_check.php';

require_role('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $course_name = sanitize($_POST['course_name'] ?? '');
        $lecturer_name = sanitize($_POST['lecturer_name'] ?? '');
        $semester = (int)($_POST['semester'] ?? 0);

        $errors = validate_required([
            'course_name' => 'Nama Mata Kuliah',
            'lecturer_name' => 'Nama Dosen',
            'semester' => 'Semester'
        ], $_POST);

        if ($semester < 1 || $semester > 8) {
            $errors[] = 'Semester tidak valid (1-8).';
        }

        if (empty($errors)) {
            $courseId = db()->insert('courses', [
                'course_name' => $course_name,
                'lecturer_name' => $lecturer_name,
                'semester' => $semester
            ]);

            if ($courseId) {
                log_activity(current_user()['id'], "Menambahkan mata kuliah: $course_name");
                flash('success', 'Mata kuliah berhasil ditambahkan.');
                redirect('courses/index.php');
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
            <a href="index.php">Mata Kuliah</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Tambah Mata Kuliah</span>
        </div>
        <h1>Tambah Mata Kuliah</h1>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="course_name">Nama Mata Kuliah <span class="required">*</span></label>
                <input type="text" id="course_name" name="course_name" class="form-control" value="<?= sanitize($_POST['course_name'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="lecturer_name">Nama Dosen Pengampu <span class="required">*</span></label>
                <input type="text" id="lecturer_name" name="lecturer_name" class="form-control" value="<?= sanitize($_POST['lecturer_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="semester">Semester <span class="required">*</span></label>
                <select id="semester" name="semester" class="form-control" required>
                    <option value="">Pilih Semester</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= (($_POST['semester'] ?? '') == $i) ? 'selected' : '' ?>>Semester <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="index.php" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
