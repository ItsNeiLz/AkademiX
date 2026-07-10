<?php
/**
 * AkademiX Sidebar Navigation
 */

$user = current_user();
$isAdmin = $user && $user['role'] === 'Admin';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <span class="brand-text">AkademiX</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Menu Utama</span>

            <a href="<?= base_url('dashboard/index.php') ?>"
               class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= base_url('tasks/index.php') ?>"
               class="nav-link <?= $activePage === 'tasks' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i>
                <span>Tugas</span>
                <?php
                $pendingCount = db()->count('tasks', "status != 'Completed'");
                if ($pendingCount > 0):
                ?>
                <span class="nav-badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= base_url('groups/index.php') ?>"
               class="nav-link <?= $activePage === 'groups' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Kelompok</span>
            </a>

            <a href="<?= base_url('courses/index.php') ?>"
               class="nav-link <?= $activePage === 'courses' ? 'active' : '' ?>">
                <i class="fas fa-book"></i>
                <span>Mata Kuliah</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Monitoring</span>

            <a href="<?= base_url('notifications/index.php') ?>"
               class="nav-link <?= $activePage === 'notifications' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Notifikasi</span>
                <?php
                $unreadCount = unread_notification_count();
                if ($unreadCount > 0):
                ?>
                <span class="nav-badge pulse"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <?php if ($isAdmin): ?>
        <div class="nav-section">
            <span class="nav-section-title">Administrasi</span>

            <a href="<?= base_url('users/index.php') ?>"
               class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i>
                <span>Manajemen User</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= get_initials($user['name']) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= sanitize($user['name']) ?></span>
                <span class="sidebar-user-role"><?= sanitize($user['role']) ?></span>
            </div>
        </div>
    </div>
</aside>
