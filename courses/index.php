<?php
$pageTitle = 'Mata Kuliah';
$activePage = 'courses';
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$isAdmin = $user['role'] === 'Admin';

// Fetch courses with count of groups
$courses = db()->fetchAll("
    SELECT c.*, COUNT(g.id) as group_count 
    FROM courses c
    LEFT JOIN `groups` g ON c.id = g.course_id
    GROUP BY c.id
    ORDER BY c.semester ASC, c.course_name ASC
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Mata Kuliah</span>
        </div>
        <h1>Mata Kuliah</h1>
        <p>Daftar mata kuliah yang memiliki tugas kelompok.</p>
    </div>
    <?php if ($isAdmin): ?>
    <div class="page-header-right">
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Mata Kuliah
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-toolbar">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari nama mata kuliah atau dosen...">
            </div>
            <div class="table-filters">
                <select class="form-control" style="min-width: 120px; padding-top: 8px; padding-bottom: 8px;" id="semesterFilter" onchange="filterSemester(this.value)">
                    <option value="">Semua Semester</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Semester 3</option>
                    <option value="4">Semester 4</option>
                    <option value="5">Semester 5</option>
                    <option value="6">Semester 6</option>
                    <option value="7">Semester 7</option>
                    <option value="8">Semester 8</option>
                </select>
            </div>
        </div>

        <?php if (empty($courses)): ?>
        <div class="table-empty">
            <i class="fas fa-book-open"></i>
            <p>Belum ada data mata kuliah.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table" id="coursesTable">
                <thead>
                    <tr>
                        <th>Mata Kuliah</th>
                        <th>Dosen Pengampu</th>
                        <th style="text-align: center;">Semester</th>
                        <th style="text-align: center;">Jml Kelompok</th>
                        <?php if ($isAdmin): ?>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr data-semester="<?= $c['semester'] ?>">
                        <td>
                            <strong><?= sanitize($c['course_name']) ?></strong>
                        </td>
                        <td><?= sanitize($c['lecturer_name']) ?></td>
                        <td style="text-align: center;">
                            <span class="badge badge-primary">Semester <?= $c['semester'] ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-secondary"><?= $c['group_count'] ?> Kelompok</span>
                        </td>
                        <?php if ($isAdmin): ?>
                        <td>
                            <div class="table-actions" style="justify-content: center;">
                                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form id="delete-form-<?= $c['id'] ?>" action="delete.php" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm" style="color: var(--danger);" title="Hapus" onclick="confirmDelete('delete-form-<?= $c['id'] ?>', '<?= addslashes(sanitize($c['course_name'])) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterSemester(sem) {
    const rows = document.querySelectorAll('#coursesTable tbody tr');
    rows.forEach(row => {
        if (sem === '' || row.dataset.semester === sem) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
