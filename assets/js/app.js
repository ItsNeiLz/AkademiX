/**
 * AkademiX Main JavaScript
 * Handles sidebar, dropdowns, modals, and animations
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initDropdowns();
    initModals();
    initFlashMessages();
    initTableSearch();
});

// ============================================
// SIDEBAR
// ============================================
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
}

// ============================================
// DROPDOWNS (Notification + User menu)
// ============================================
function initDropdowns() {
    // Notification dropdown
    const notifBell = document.getElementById('notifBell');
    const notifDropdown = document.getElementById('notifDropdown');

    if (notifBell && notifDropdown) {
        notifBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            // Close user dropdown
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown) userDropdown.classList.remove('active');

            // Load notifications
            if (notifDropdown.classList.contains('active')) {
                loadNotifications();
            }
        });
    }

    // User dropdown
    const userMenu = document.getElementById('userMenu');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenu && userDropdown) {
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            // Close notif dropdown
            if (notifDropdown) notifDropdown.classList.remove('active');
        });
    }

    // Close all dropdowns on outside click
    document.addEventListener('click', () => {
        if (notifDropdown) notifDropdown.classList.remove('active');
        if (userDropdown) userDropdown.classList.remove('active');
    });
}

// ============================================
// NOTIFICATIONS (Dropdown content loader)
// ============================================
function loadNotifications() {
    const notifList = document.getElementById('notifList');
    if (!notifList) return;

    fetch(getBaseUrl() + 'api/notifications_count.php?action=recent')
        .then(res => res.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                notifList.innerHTML = data.notifications.map(n => {
                    const iconClass = getNotifIconClass(n.type);
                    const icon = getNotifIcon(n.type);
                    return `
                        <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                            <div class="notif-icon ${iconClass}">
                                <i class="${icon}"></i>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title">${escapeHtml(n.title)}</div>
                                <div class="notif-message">${escapeHtml(n.message)}</div>
                                <div class="notif-time">${n.time_ago}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                notifList.innerHTML = '<div class="notif-loading">Tidak ada notifikasi</div>';
            }
        })
        .catch(() => {
            notifList.innerHTML = '<div class="notif-loading">Gagal memuat notifikasi</div>';
        });
}

function getNotifIconClass(type) {
    const map = {
        'Deadline': 'deadline',
        'Assignment': 'assignment',
        'Reminder': 'reminder',
        'Completed': 'completed',
    };
    return map[type] || 'reminder';
}

function getNotifIcon(type) {
    const map = {
        'Deadline': 'fas fa-clock',
        'Assignment': 'fas fa-user-plus',
        'Reminder': 'fas fa-bell',
        'Completed': 'fas fa-check-circle',
    };
    return map[type] || 'fas fa-bell';
}

// ============================================
// MODALS
// ============================================
function initModals() {
    // Open modal buttons
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modal);
            if (modal) modal.classList.add('active');
        });
    });

    // Close modal buttons
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) modal.classList.remove('active');
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('active');
            }
        });
    });
}

// ============================================
// FLASH MESSAGES (auto-dismiss)
// ============================================
function initFlashMessages() {
    const flash = document.getElementById('flash-alert');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    }
}

// ============================================
// TABLE SEARCH (Client-side filter)
// ============================================
function initTableSearch() {
    const searchInputs = document.querySelectorAll('.table-search input');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const table = input.closest('.card, .table-wrapper')?.querySelector('.data-table tbody');
            if (!table) return;

            table.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

// ============================================
// HELPERS
// ============================================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getBaseUrl() {
    const path = window.location.pathname;
    const parts = path.split('/');
    // Find project root (Website Akademix or akademix)
    let base = '/';
    for (let i = 0; i < parts.length; i++) {
        if (parts[i].toLowerCase().includes('akademi')) {
            base = parts.slice(0, i + 1).join('/') + '/';
            break;
        }
    }
    return base;
}

// Delete confirmation
function confirmDelete(formId, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`)) {
        document.getElementById(formId).submit();
    }
}

// Toggle checklist item via AJAX
function toggleChecklist(checklistId, checkbox) {
    const isCompleted = checkbox.checked ? 1 : 0;
    const label = checkbox.closest('.checklist-item')?.querySelector('.checklist-label');

    fetch(getBaseUrl() + 'api/task_progress.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ checklist_id: checklistId, is_completed: isCompleted })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (label) {
                label.classList.toggle('completed', isCompleted === 1);
            }
            // Update progress bar if present
            if (data.progress !== undefined) {
                const progressBar = document.querySelector('.progress-bar');
                const progressLabel = document.querySelector('.progress-percentage');
                if (progressBar) progressBar.style.width = data.progress + '%';
                if (progressLabel) progressLabel.textContent = data.progress + '%';
            }
        }
    })
    .catch(err => console.error('Checklist update failed:', err));
}
