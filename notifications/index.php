<?php
$pageTitle = 'Pemberitahuan';
$activePage = 'notifications';
require_once __DIR__ . '/../includes/auth_check.php';

$userId = current_user()['id'];

// Handle mark all as read
if (isset($_GET['mark_all']) && $_GET['mark_all'] == 1) {
    db()->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
    flash('success', 'Semua pemberitahuan telah ditandai sudah dibaca.');
    redirect('notifications/index.php');
}

// Fetch notifications
$notifications = db()->fetchAll("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
", [$userId]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <span>AkademiX</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active">Pemberitahuan</span>
        </div>
        <h1>Pemberitahuan</h1>
        <p>Aktivitas dan peringatan terbaru untuk Anda.</p>
    </div>
    <div class="page-header-right">
        <?php 
        $unreadCount = 0;
        foreach ($notifications as $n) {
            if (!$n['is_read']) $unreadCount++;
        }
        if ($unreadCount > 0): 
        ?>
        <a href="index.php?mark_all=1" class="btn btn-ghost">
            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
        <div class="empty-state" style="padding: 60px 24px;">
            <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 16px;"></i>
            <h3>Belum ada pemberitahuan</h3>
            <p>Anda belum menerima notifikasi apapun.</p>
        </div>
        <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $n): 
                $icon = 'fa-bell';
                $color = 'var(--primary)';
                
                if ($n['type'] === 'Deadline') {
                    $icon = 'fa-clock';
                    $color = 'var(--danger)';
                } elseif ($n['type'] === 'Assignment') {
                    $icon = 'fa-tasks';
                    $color = 'var(--info)';
                } elseif ($n['type'] === 'System') {
                    $icon = 'fa-info-circle';
                    $color = 'var(--warning)';
                }
            ?>
            <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="padding: 16px 24px; border-bottom: 1px solid var(--border); display: flex; gap: 16px; align-items: flex-start; <?= !$n['is_read'] ? 'background-color: rgba(102, 126, 234, 0.05);' : '' ?>">
                <div class="notification-icon" style="width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; color: <?= $color ?>; flex-shrink: 0; font-size: 1.2rem;">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="notification-content" style="flex: 1;">
                    <div class="d-flex justify-between align-center mb-1">
                        <strong style="color: var(--text-primary);"><?= sanitize($n['title']) ?></strong>
                        <span class="text-muted" style="font-size: 0.75rem;"><?= format_date($n['created_at'], true) ?></span>
                    </div>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;"><?= sanitize($n['message']) ?></p>
                </div>
                <?php if (!$n['is_read']): ?>
                <div class="notification-action">
                    <form action="mark_read.php" method="POST" style="margin:0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $n['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="Tandai dibaca">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
