<?php
$pageTitle = 'Buat Tugas Baru';
$activePage = 'tasks';
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$userId = $user['id'];
$isAdmin = $user['role'] === 'Admin';

// Fetch courses for optional selection in individual tasks
$courses = db()->fetchAll("SELECT id, course_name FROM courses ORDER BY course_name ASC");

// Fetch groups based on role
if ($isAdmin) {
    $groups = db()->fetchAll("
        SELECT g.id, g.group_name, c.course_name, c.id as course_id
        FROM `groups` g
        JOIN courses c ON g.course_id = c.id
        ORDER BY c.course_name ASC, g.group_name ASC
    ");
} else {
    $groups = db()->fetchAll("
        SELECT g.id, g.group_name, c.course_name, c.id as course_id
        FROM `groups` g
        JOIN courses c ON g.course_id = c.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ?
        ORDER BY c.course_name ASC, g.group_name ASC
    ", [$userId]);
}

$preselectedGroup = $_GET['group_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $task_type = $_POST['task_type'] ?? 'kelompok';
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'Medium');

        $errors = validate_required([
            'title' => 'Judul Tugas',
            'deadline' => 'Deadline',
            'priority' => 'Prioritas'
        ], $_POST);

        $group_id = null;
        $course_id = null;

        if ($task_type === 'kelompok') {
            $group_id = (int)($_POST['group_id'] ?? 0);
            if (!$group_id) {
                $errors[] = 'Kelompok harus dipilih untuk tugas kelompok.';
            } else {
                foreach ($groups as $g) {
                    if ($g['id'] == $group_id) {
                        $course_id = $g['course_id'];
                        break;
                    }
                }
                if (!$course_id) {
                    $errors[] = 'Kelompok tidak valid.';
                }
            }
        } else {
            // Individual Task
            $course_id_input = (int)($_POST['course_id'] ?? 0);
            if ($course_id_input > 0) {
                $course_id = $course_id_input;
            }
        }

        $file_path = null;
        if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
            $file_name = $_FILES['task_file']['name'];
            $file_size = $_FILES['task_file']['size'];
            $file_tmp = $_FILES['task_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = 'Format file tidak diizinkan. Ekstensi yang diizinkan: ' . implode(', ', $allowed_exts);
            } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
                $errors[] = 'Ukuran file maksimal 10MB.';
            } else {
                $new_file_name = uniqid('task_') . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $file_name);
                $upload_dir = __DIR__ . '/../assets/uploads/tasks/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $file_path = 'assets/uploads/tasks/' . $new_file_name;
                } else {
                    $errors[] = 'Gagal mengunggah file.';
                }
            }
        }

        if (empty($errors)) {
            $taskId = db()->insert('tasks', [
                'title' => $title,
                'description' => $description,
                'file_path' => $file_path,
                'course_id' => $course_id,
                'group_id' => $group_id,
                'created_by' => $userId,
                'deadline' => $deadline,
                'priority' => $priority,
                'status' => 'Not Started'
            ]);

            if ($taskId) {
                log_activity($userId, "Membuat tugas baru: $title");
                
                $checklists = array_filter(array_map('trim', $_POST['checklists'] ?? []));
                
                // Assign to creator directly so checklists can be tied to it
                $assignmentId = db()->insert('task_assignments', [
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'status' => 'Not Started',
                    'completion_percentage' => 0
                ]);
                
                if (!empty($checklists)) {
                    foreach ($checklists as $item) {
                        if (!empty($item)) {
                            db()->insert('task_checklists', [
                                'task_assignment_id' => $assignmentId,
                                'checklist_item' => $item,
                                'is_completed' => 0
                            ]);
                        }
                    }
                }
                
                if ($task_type === 'individu') {
                    flash('success', 'Tugas Individu beserta To-Do list berhasil dibuat.');
                    redirect("tasks/detail.php?id=$taskId");
                } else {
                    flash('success', 'Tugas Kelompok dibuat. To-Do list awal (jika ada) ditugaskan ke Anda.');
                    redirect("tasks/detail.php?id=$taskId");
                }
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
            <a href="index.php">Tugas</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Buat Tugas</span>
        </div>
        <h1>Buat Tugas Baru</h1>
    </div>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form action="" method="POST" id="taskForm" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group mb-4">
                <label class="form-label d-block">Jenis Tugas <span class="required">*</span></label>
                <div class="d-flex" style="gap: 20px;">
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="task_type" value="kelompok" id="typeKelompok" <?= (($_POST['task_type'] ?? 'kelompok') === 'kelompok') ? 'checked' : '' ?>>
                        Tugas Kelompok
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="task_type" value="individu" id="typeIndividu" <?= (($_POST['task_type'] ?? '') === 'individu') ? 'checked' : '' ?>>
                        Tugas Individu
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="title">Judul Tugas <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-row">
                <!-- Dropdown Kelompok (Muncul jika Tugas Kelompok) -->
                <div class="form-group" id="groupContainer">
                    <label class="form-label" for="group_id">Kelompok / Mata Kuliah <span class="required">*</span></label>
                    <select id="group_id" name="group_id" class="form-control">
                        <option value="">Pilih Kelompok</option>
                        <?php 
                        $selectedGroup = $_POST['group_id'] ?? $preselectedGroup;
                        foreach ($groups as $g): 
                        ?>
                        <option value="<?= $g['id'] ?>" <?= ($selectedGroup == $g['id']) ? 'selected' : '' ?>>
                            <?= sanitize($g['group_name']) ?> — <?= sanitize($g['course_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dropdown Course (Muncul jika Tugas Individu) -->
                <div class="form-group" id="courseContainer" style="display: none;">
                    <label class="form-label" for="course_id">Mata Kuliah (Opsional)</label>
                    <select id="course_id" name="course_id" class="form-control">
                        <option value="">-- Tidak terikat mata kuliah --</option>
                        <?php 
                        $selectedCourse = $_POST['course_id'] ?? '';
                        foreach ($courses as $c): 
                        ?>
                        <option value="<?= $c['id'] ?>" <?= ($selectedCourse == $c['id']) ? 'selected' : '' ?>>
                            <?= sanitize($c['course_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="priority">Prioritas <span class="required">*</span></label>
                    <select id="priority" name="priority" class="form-control" required>
                        <option value="Low" <?= (($_POST['priority'] ?? '') === 'Low') ? 'selected' : '' ?>>Low (Rendah)</option>
                        <option value="Medium" <?= (($_POST['priority'] ?? 'Medium') === 'Medium') ? 'selected' : '' ?>>Medium (Sedang)</option>
                        <option value="High" <?= (($_POST['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High (Tinggi)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="max-width: 50%;">
                <label class="form-label" for="deadline">Deadline (Tenggat Waktu) <span class="required">*</span></label>
                <input type="date" id="deadline" name="deadline" class="form-control" value="<?= sanitize($_POST['deadline'] ?? '') ?>" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Deskripsi Lengkap Tugas</label>
                <textarea id="description" name="description" class="form-control" placeholder="Jelaskan detail tugas yang harus dikerjakan..."><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group mt-4">
                <label class="form-label" for="task_file">Lampiran File Tugas (Opsional)</label>
                <div class="form-hint mb-2">Unggah file pendukung tugas (Max 10MB). Format: pdf, doc, xls, ppt, zip, rar, jpg, png.</div>
                <input type="file" id="task_file" name="task_file" class="form-control" style="padding: 8px;">
            </div>

            <div class="form-group mt-4">
                <label class="form-label">To-Do List / Daftar Pekerjaan (Opsional)</label>
                <div class="form-hint mb-2">Tambahkan sub-tugas secara spesifik. Progress akan otomatis terhitung.</div>
                
                <div id="checklist-container">
                    <div class="d-flex align-center gap-1 mb-2">
                        <input type="text" name="checklists[]" class="form-control" placeholder="Contoh: Membuat struktur file laporan">
                    </div>
                </div>
                
                <button type="button" class="btn btn-ghost btn-sm mt-1" onclick="addChecklistInput()">
                    <i class="fas fa-plus"></i> Tambah To-Do
                </button>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin: 24px 0;">

            <div class="d-flex" style="justify-content: flex-end; gap: 12px;">
                <a href="index.php" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Tugas</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioKelompok = document.getElementById('typeKelompok');
    const radioIndividu = document.getElementById('typeIndividu');
    const groupContainer = document.getElementById('groupContainer');
    const courseContainer = document.getElementById('courseContainer');
    const groupIdInput = document.getElementById('group_id');
    const submitBtn = document.getElementById('submitBtn');

    function toggleTaskType() {
        if (radioIndividu.checked) {
            groupContainer.style.display = 'none';
            courseContainer.style.display = 'block';
            groupIdInput.removeAttribute('required');
            submitBtn.textContent = 'Simpan Tugas Individu';
        } else {
            groupContainer.style.display = 'block';
            courseContainer.style.display = 'none';
            groupIdInput.setAttribute('required', 'required');
            submitBtn.textContent = 'Simpan & Lanjut Assign';
        }
    }

    radioKelompok.addEventListener('change', toggleTaskType);
    radioIndividu.addEventListener('change', toggleTaskType);

    // Initial load
    toggleTaskType();
});

function addChecklistInput() {
    const container = document.getElementById('checklist-container');
    const div = document.createElement('div');
    div.className = 'd-flex align-center gap-1 mb-2';
    div.innerHTML = `
        <input type="text" name="checklists[]" class="form-control" placeholder="">
        <button type="button" class="btn btn-ghost btn-icon btn-sm text-danger" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
