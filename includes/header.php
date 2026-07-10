<?php
/**
 * AkademiX Header
 * Shared HTML head + top navbar
 *
 * Variables expected:
 * $pageTitle - Page title string
 * $activePage - Active page identifier for sidebar highlight
 */

if (!isset($pageTitle)) $pageTitle = 'AkademiX';
if (!isset($activePage)) $activePage = '';

$user = current_user();
$notifCount = is_logged_in() ? unread_notification_count() : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AkademiX - Platform Manajemen Tugas Akademik dan Kolaborasi Kelompok">
    <title><?= sanitize($pageTitle) ?> — AkademiX</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php if (is_logged_in()): ?>
    <!-- Top Navbar -->
    <nav class="topbar" id="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Cari tugas, kelompok, mata kuliah..." id="globalSearch">
            </div>
        </div>
        <div class="topbar-right">
            <!-- Notifications -->
            <div class="topbar-icon" id="notifBell">
                <i class="fas fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                <span class="notif-badge" id="notifBadge"><?= $notifCount ?></span>
                <?php endif; ?>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <h4>Notifikasi</h4>
                        <a href="<?= base_url('notifications/index.php') ?>">Lihat Semua</a>
                    </div>
                    <div class="notif-list" id="notifList">
                        <div class="notif-loading">
                            <i class="fas fa-spinner fa-spin"></i> Memuat...
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="user-menu" id="userMenu">
                <div class="user-avatar">
                    <?= get_initials($user['name']) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= sanitize($user['name']) ?></span>
                    <span class="user-role"><?= sanitize($user['role']) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
                <div class="user-dropdown" id="userDropdown">
                    <a href="<?= base_url('users/profile.php') ?>">
                        <i class="fas fa-user"></i> Profil Saya
                    </a>
                    <a href="<?= base_url('auth/logout.php') ?>" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <?= display_flash() ?>
    <?php endif; ?>
