/**
 * AkademiX Notifications Integration
 * Fetches unread notifications count via AJAX
 */

document.addEventListener('DOMContentLoaded', () => {
    fetchNotificationCount();
    
    // Poll every 60 seconds
    setInterval(fetchNotificationCount, 60000);
});

function fetchNotificationCount() {
    const badge = document.querySelector('.navbar-actions .badge-notification');
    if (!badge) return; // Not logged in or badge doesn't exist

    fetch(getBaseUrl() + 'api/notifications_count.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Error fetching notifications:', err));
}
