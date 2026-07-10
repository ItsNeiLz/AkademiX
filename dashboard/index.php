<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();

// For PHP-rendered fallbacks (the JS will overwrite these if API succeeds)
// But it's good for SEO/initial load
$stats = [
    'total_mahasiswa' => db()->count('users', "role != 'Admin'"),
    'total_kelompok'  => db()->count('groups'),
    'total_matkul'    => db()->count('courses'),
    'total_tugas'     => db()->count('tasks'),
    'tugas_selesai'   => db()->count('tasks', "status = 'Completed'"),
    'tugas_belum'     => db()->count('tasks', "status != 'Completed'"),
    'tugas_terlambat' => db()->count('tasks', "deadline < CURDATE() AND status != 'Completed'")
];
$stats['persentase_selesai'] = $stats['total_tugas'] > 0 
    ? round(($stats['tugas_selesai'] / $stats['total_tugas']) * 100) 
    : 0;

$extraScripts = ['assets/js/charts.js'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Dashboard</span>
        </div>
        <h1>Halo, <?= sanitize(explode(' ', $user['name'])[0]) ?>! 👋</h1>
        <p>Berikut adalah ringkasan aktivitas dan tugas Anda hari ini.</p>
    </div>
    
    <?php if ($user['role'] !== 'Admin'): ?>
    <div class="page-header-right">
        <a href="<?= base_url('tasks/create.php') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Tugas Baru
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Total Tugas</span>
            <div class="stat-icon blue">
                <i class="fas fa-tasks"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-total-tugas"><?= $stats['total_tugas'] ?></div>
        <div class="stat-change text-muted">Seluruh tugas terdaftar</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Tugas Selesai</span>
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-tugas-selesai"><?= $stats['tugas_selesai'] ?></div>
        <div class="stat-change up">
            <i class="fas fa-arrow-up"></i> <?= $stats['persentase_selesai'] ?>% penyelesaian
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Sedang Dikerjakan</span>
            <div class="stat-icon orange">
                <i class="fas fa-spinner"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-tugas-belum"><?= $stats['tugas_belum'] ?></div>
        <div class="stat-change text-muted">Belum selesai</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Tugas Terlambat</span>
            <div class="stat-icon red">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-tugas-terlambat"><?= $stats['tugas_terlambat'] ?></div>
        <div class="stat-change <?= $stats['tugas_terlambat'] > 0 ? 'down' : 'text-muted' ?>">
            Melewati tenggat waktu
        </div>
    </div>

    <?php if ($user['role'] === 'Admin' || $user['role'] === 'Ketua Kelompok'): ?>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Total Kelompok</span>
            <div class="stat-icon purple">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-total-kelompok"><?= $stats['total_kelompok'] ?></div>
        <div class="stat-change text-muted">Kelompok belajar aktif</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-label">Total Mahasiswa</span>
            <div class="stat-icon indigo">
                <i class="fas fa-user-graduate"></i>
            </div>
        </div>
        <div class="stat-value" id="stat-total-mahasiswa"><?= $stats['total_mahasiswa'] ?></div>
        <div class="stat-change text-muted">Pengguna terdaftar</div>
    </div>
    <?php endif; ?>
</div>

<!-- Charts Grid -->
<div class="charts-grid">
    <!-- Pie Chart -->
    <div class="chart-card">
        <h3 class="chart-title">Status Tugas Keseluruhan</h3>
        <div class="chart-container">
            <canvas id="taskStatusChart"></canvas>
        </div>
    </div>

    <!-- Line Chart -->
    <div class="chart-card" style="grid-column: span 2;">
        <h3 class="chart-title">Tren Penyelesaian Tugas (6 Bulan Terakhir)</h3>
        <div class="chart-container">
            <canvas id="completionTrendChart"></canvas>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="chart-card">
        <h3 class="chart-title">Produktivitas Mahasiswa Teratas</h3>
        <div class="chart-container">
            <canvas id="productivityChart"></canvas>
        </div>
    </div>
</div>

<?php
// Get recent activities for timeline
$activities = db()->fetchAll("
    SELECT a.*, u.name, u.role 
    FROM activity_logs a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
?>

<!-- Recent Activity & Notifications -->
<div class="detail-grid">
    <div class="card">
        <div class="card-header">
            <h3>Tugas Mendekati Deadline</h3>
            <a href="<?= base_url('tasks/index.php') ?>" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body">
            <?php
            $upcomingTasks = db()->fetchAll("
                SELECT t.*, c.course_name 
                FROM tasks t
                LEFT JOIN courses c ON t.course_id = c.id
                WHERE t.status != 'Completed' AND t.deadline >= CURDATE()
                ORDER BY t.deadline ASC
                LIMIT 5
            ");
            
            if (empty($upcomingTasks)):
            ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <h3>Tidak ada tugas mendesak</h3>
                <p>Semua tugas Anda aman untuk saat ini.</p>
            </div>
            <?php else: ?>
            <div class="table-wrapper" style="border: none;">
                <table class="data-table">
                    <tbody>
                        <?php foreach ($upcomingTasks as $task): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($task['title']) ?></strong><br>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= sanitize($task['course_name']) ?></span>
                            </td>
                            <td><span class="badge <?= priority_class($task['priority']) ?>"><?= $task['priority'] ?></span></td>
                            <td class="text-right"><?= deadline_label($task['deadline']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Aktivitas Terbaru</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($activities as $act): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <strong><?= sanitize(explode(' ', $act['name'])[0]) ?></strong> 
                        <?= sanitize(str_replace(explode(' ', $act['name'])[0] . ' ', '', $act['activity'])) ?>
                    </div>
                    <div class="timeline-time">
                        <?= format_date($act['created_at'], true) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
