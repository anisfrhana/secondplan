(function() {
    var bellSvg = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M10 13a2 2 0 01-4 0"/><path d="M4 6a4 4 0 018 0c0 2 1 3 1.5 4H2.5c.5-1 1.5-2 1.5-4z"/></svg>';
    var apiBase = '../api/notifications.php';

    function init() {
        var btns = document.querySelectorAll('.notification-btn');
        btns.forEach(function(btn) {
            btn.innerHTML = bellSvg + '<span class="notification-badge-count"></span>';
            btn.style.position = 'relative';

            var dropdown = document.createElement('div');
            dropdown.className = 'notification-dropdown';
            dropdown.innerHTML =
                '<div class="notification-dropdown-header">' +
                    '<span>Notifications</span>' +
                    '<button onclick="markAllNotificationsRead()" style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:12px;">Mark all read</button>' +
                '</div>' +
                '<div class="notification-dropdown-body"></div>';
            btn.parentElement.style.position = 'relative';
            btn.parentElement.appendChild(dropdown);

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var isOpen = dropdown.classList.contains('active');
                closeAllDropdowns();
                if (!isOpen) {
                    dropdown.classList.add('active');
                    loadNotifications(dropdown);
                }
            });
        });

        loadUnreadCount();
        setInterval(loadUnreadCount, 30000);

        document.addEventListener('click', function() {
            closeAllDropdowns();
        });
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.notification-dropdown.active').forEach(function(d) {
            d.classList.remove('active');
        });
    }

    function loadUnreadCount() {
        fetch(apiBase + '?action=count')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var count = data.data.count || 0;
                    document.querySelectorAll('.notification-badge-count').forEach(function(badge) {
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.classList.add('active');
                        } else {
                            badge.classList.remove('active');
                            badge.textContent = '';
                        }
                    });
                }
            })
            .catch(function() {});
    }

    function loadNotifications(dropdown) {
        var body = dropdown.querySelector('.notification-dropdown-body');
        body.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-secondary);font-size:13px;">Loading...</div>';

        fetch(apiBase)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.data && data.data.length) {
                    body.innerHTML = data.data.map(function(n) {
                        return '<div class="notification-item' + (n.is_read == 0 ? ' unread' : '') + '" onclick="handleNotificationClick(' + n.notification_id + ',\'' + escAttr(n.link || '') + '\')">' +
                            '<div class="notif-title">' + esc(n.title) + '</div>' +
                            '<div class="notif-message">' + esc(n.message) + '</div>' +
                            '<div class="notif-time">' + timeAgo(n.created_at) + '</div>' +
                        '</div>';
                    }).join('');
                } else {
                    body.innerHTML = '<div style="padding:30px;text-align:center;color:var(--text-secondary);font-size:13px;">No notifications</div>';
                }
            })
            .catch(function() {
                body.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-secondary);font-size:13px;">Failed to load</div>';
            });
    }

    function esc(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function escAttr(text) {
        return (text || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function isSafeLink(link) {
        if (!link) return true;
        var trimmed = link.trim().toLowerCase();
        if (trimmed.startsWith('javascript:')) return false;
        if (trimmed.startsWith('data:')) return false;
        if (trimmed.startsWith('vbscript:')) return false;
        return true;
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var now = new Date();
        var seconds = Math.floor((now - date) / 1000);
        if (seconds < 60) return 'Just now';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        var days = Math.floor(hours / 24);
        if (days < 7) return days + 'd ago';
        return date.toLocaleDateString();
    }

    function getBaseUrl() {
        var path = window.location.pathname;
        var segments = ['/admin/', '/band/', '/user/', '/auth/'];
        for (var i = 0; i < segments.length; i++) {
            var idx = path.indexOf(segments[i]);
            if (idx !== -1) {
                return path.substring(0, idx);
            }
        }
        return '';
    }

    function getCSRF() {
        var meta = document.querySelector('meta[name="csrf"]');
        if (meta) return meta.getAttribute('content') || '';
        var m = document.cookie.match(/(?:^|;\s*)csrf=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    window.handleNotificationClick = function(id, link) {
        var fd = new FormData();
        fd.append('action', 'mark_read');
        fd.append('notification_id', id);
        fd.append('csrf', getCSRF());
        fetch(apiBase, { method: 'POST', body: fd })
            .then(function() {
                loadUnreadCount();
                if (link && isSafeLink(link)) {
                    var base = getBaseUrl();
                    window.location.href = base + link;
                } else {
                    closeAllDropdowns();
                    var dropdown = document.querySelector('.notification-dropdown');
                    if (dropdown) {
                        dropdown.classList.add('active');
                        loadNotifications(dropdown);
                    }
                }
            })
            .catch(function() {});
    };

    window.markAllNotificationsRead = function() {
        var fd = new FormData();
        fd.append('action', 'mark_all_read');
        fd.append('csrf', getCSRF());
        fetch(apiBase, { method: 'POST', body: fd })
            .then(function() {
                loadUnreadCount();
                document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                    item.classList.remove('unread');
                });
            })
            .catch(function() {});
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
