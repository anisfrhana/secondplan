var logs = [];
var searchQuery = '';

var actionColors = {
    login: 'success',
    register: 'success',
    order_placed: 'success',
    booking_approved: 'success',
    password_reset_complete: 'success',
    login_failed: 'danger',
    logout: 'danger',
    profile_updated: 'info',
    settings_updated: 'info',
    social_media_updated: 'info',
    media_settings_updated: 'info',
    booking_submit: 'warning',
    booking_created: 'warning',
    task_status_update: 'warning',
    password_changed: 'warning',
    password_reset_request: 'warning'
};

document.addEventListener('DOMContentLoaded', function() {
    loadLogs();
    loadActions();
    setupSearch();
});

function loadLogs() {
    fetch('activity_log.php?api=list')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                logs = data.data;
            }
            renderLogs();
            updateStats();
        })
        .catch(function() {
            showToast('Failed to load activity log', 'error');
        });
}

function loadActions() {
    fetch('activity_log.php?api=actions')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                var select = document.getElementById('actionFilter');
                data.data.forEach(function(action) {
                    var opt = document.createElement('option');
                    opt.value = action;
                    opt.textContent = formatActionLabel(action);
                    select.appendChild(opt);
                });
            }
        })
        .catch(function() {});
}

function renderLogs() {
    var tbody = document.getElementById('logsTable');
    var filtered = logs.slice();

    var actionFilter = document.getElementById('actionFilter').value;
    var dateFrom = document.getElementById('dateFrom').value;
    var dateTo = document.getElementById('dateTo').value;

    if (actionFilter) {
        filtered = filtered.filter(function(l) { return l.action === actionFilter; });
    }

    if (dateFrom) {
        filtered = filtered.filter(function(l) {
            return l.created_at && l.created_at.split(' ')[0] >= dateFrom;
        });
    }

    if (dateTo) {
        filtered = filtered.filter(function(l) {
            return l.created_at && l.created_at.split(' ')[0] <= dateTo;
        });
    }

    if (searchQuery) {
        filtered = filtered.filter(function(l) {
            return (l.user_name || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (l.action || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (l.user_email || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (l.details || '').toLowerCase().indexOf(searchQuery) !== -1;
        });
    }

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No activity logs found</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(function(l) {
        return '<tr>' +
            '<td>' + formatTime(l.created_at) + '</td>' +
            '<td>' + esc(l.user_name || 'System') + (l.user_email ? '<div style="font-size:11px;color:#6b7280;">' + esc(l.user_email) + '</div>' : '') + '</td>' +
            '<td>' + formatAction(l.action) + '</td>' +
            '<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;">' + formatDetails(l.details) + '</td>' +
            '<td><span style="font-family:monospace;font-size:12px;color:#6b7280;">' + esc(l.ip_address) + '</span></td>' +
            '</tr>';
    }).join('');
}

function updateStats() {
    document.getElementById('totalEntries').textContent = logs.length;

    var today = new Date().toISOString().split('T')[0];
    document.getElementById('todayEntries').textContent = logs.filter(function(l) {
        return l.created_at && l.created_at.split(' ')[0] === today;
    }).length;

    var weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    var weekStr = weekAgo.toISOString().split('T')[0];
    document.getElementById('weekEntries').textContent = logs.filter(function(l) {
        return l.created_at && l.created_at.split(' ')[0] >= weekStr;
    }).length;

    var users = {};
    logs.forEach(function(l) {
        if (l.user_id) users[l.user_id] = true;
    });
    document.getElementById('uniqueUsers').textContent = Object.keys(users).length;
}

function filterLogs() {
    renderLogs();
}

function formatActionLabel(action) {
    return (action || '').replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
}

function formatAction(action) {
    var color = actionColors[action] || 'info';
    var colorMap = {
        success: '#059669',
        danger: '#dc2626',
        info: '#2563eb',
        warning: '#d97706'
    };
    var bgMap = {
        success: '#ecfdf5',
        danger: '#fef2f2',
        info: '#eff6ff',
        warning: '#fffbeb'
    };
    var label = formatActionLabel(action);
    return '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:500;color:' + colorMap[color] + ';background:' + bgMap[color] + ';">' + esc(label) + '</span>';
}

function formatDetails(json) {
    if (!json) return '<span style="color:#9ca3af;">-</span>';
    try {
        var obj = JSON.parse(json);
        var parts = [];
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                parts.push('<span style="color:#6b7280;">' + esc(key) + ':</span> ' + esc(String(obj[key])));
            }
        }
        return parts.join(', ') || esc(json);
    } catch (e) {
        return esc(json);
    }
}

function formatTime(datetime) {
    if (!datetime) return '-';
    try {
        var d = new Date(datetime);
        return d.toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' }) +
            ' <span style="color:#6b7280;">' + d.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' }) + '</span>';
    } catch (e) {
        return esc(datetime);
    }
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(e) {
            searchQuery = e.target.value.toLowerCase();
            renderLogs();
        });
    }
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '-';
    return d.innerHTML;
}
