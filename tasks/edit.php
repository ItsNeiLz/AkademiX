<?php
$pageTitle = 'Edit Tugas';
$activePage = 'tasks';
require_once __DIR__ . '/../includes/auth_check.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    flash('error', 'ID Tugas tidak ditemukan.');
    redirect('tasks/index.php');
}

$task = db()->fetch("SELECT * FROM tasks WHERE id = ?", [$id]);
if (!$task) {
    flash('error', 'Tugas tidak ditemukan.');
    redirect('tasks/index.php');
}

$user = current_user();
$isAdmin = $user['role'] === 'Admin';

// Check if user is allowed to edit this task
if (!$isAdmin && $task['created_by'] != $user['id']) {
    flash('error', 'Anda tidak memiliki akses untuk mengubah tugas ini.');
    redirect('tasks/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'Medium');

        $errors = validate_required([
            'title' => 'Judul Tugas',
            'deadline' => 'Deadline',
            'priority' => 'Prioritas'
        ], $_POST);

        if (empty($errors)) {
            db()->update('tasks', [
                'title' => $title,
                'description' => $description,
                'deadline' => $deadline,
                'priority' => $priority
            ], 'id = ?', [$id]);

            log_activity($user['id'], "Mengubah tugas: $title");
            flash('success', 'Tugas berhasil diperbarui.');
            redirect("tasks/detail.php?id=$id");
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
            <a href="index.php">Tugas</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="detail.php?id=<?= $id ?>"><?= sanitize($task['title']) ?></a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Edit Tugas</span>
        </div>
        <h1>Edit Tugas</h1>
    </div>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="title">Judul Tugas <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? $task['title']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="deadline">Deadline (Tenggat Waktu) <span class="required">*</span></label>
                    <input type="date" id="deadline" name="deadline" class="form-control" value="<?= sanitize($_POST['deadline'] ?? $task['deadline']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="priority">Prioritas <span class="required">*</span></label>
                    <select id="priority" name="priority" class="form-control" required>
                        <?php $selectedPriority = $_POST['priority'] ?? $task['priority']; ?>
                        <option value="Low" <?= $selectedPriority === 'Low' ? 'selected' : '' ?>>Low (Rendah)</option>
                        <option value="Medium" <?= $selectedPriority === 'Medium' ? 'selected' : '' ?>>Medium (Sedang)</option>
                        <option value="High" <?= $selectedPriority === 'High' ? 'selected' : '' ?>>High (Tinggi)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Deskripsi Lengkap Tugas</label>
                <textarea id="description" name="description" class="form-control" placeholder="Jelaskan detail tugas yang harus dikerjakan..."><?= sanitize($_POST['description'] ?? $task['description']) ?></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
