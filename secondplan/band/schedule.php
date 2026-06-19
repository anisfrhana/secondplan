<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        #calendar {
            margin-top: 16px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }
        .fc {
            color: var(--text);
        }
        .fc .fc-toolbar-title {
            color: var(--text);
            font-size: 1.2em;
        }
        .fc .fc-button {
            background: rgba(220,38,38,0.15);
            border: 1px solid rgba(220,38,38,0.3);
            color: var(--text);
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 6px;
        }
        .fc .fc-button:hover {
            background: rgba(220,38,38,0.3);
        }
        .fc .fc-button-active {
            background: var(--accent);
            border-color: var(--accent);
        }
        .fc .fc-daygrid-day {
            background: transparent;
        }
        .fc .fc-daygrid-day-number {
            color: var(--text-secondary);
        }
        .fc .fc-day-today {
            background: rgba(220,38,38,0.08) !important;
        }
        .fc .fc-col-header-cell-cushion {
            color: var(--text-secondary);
        }
        .fc .fc-event {
            border: none;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 12px;
            cursor: pointer;
        }
        .fc .fc-timegrid-slot {
            border-color: var(--border);
        }
        .fc th, .fc td {
            border-color: var(--border);
        }
        .fc .fc-scrollgrid {
            border-color: var(--border);
        }
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 16px;
            padding: 12px 16px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>My Schedule</h2>
                <div class="subtitle">Events and tasks at a glance</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background:#DC2626;"></div>
                    <span>Events</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#ef4444;"></div>
                    <span>Urgent Tasks</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#f97316;"></div>
                    <span>High Priority</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#6b7280;"></div>
                    <span>Normal Tasks</span>
                </div>
            </div>

            <div id="calendar"></div>
        </main>
    </div>
</div>

<div class="modal" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="eventModalTitle">Details</h3>
            <button class="close-btn" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="modal-body" id="eventModalBody"></div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeEventModal()">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
var USER_ID = <?= $user_id ?>;

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        eventSources: [
            {
                url: '../api/events.php',
                color: '#DC2626',
                failure: function() {}
            },
            {
                url: '../api/tasks.php?assigned_to=' + USER_ID,
                failure: function() {}
            }
        ],
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var evt = info.event;
            var props = evt.extendedProps || {};

            if (props.type === 'event') {
                document.getElementById('eventModalTitle').textContent = 'Event Details';
                var timeStr = (evt.start ? evt.start.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '-') + (evt.end ? ' - ' + evt.end.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '');
                document.getElementById('eventModalBody').innerHTML =
                    '<div style="display:flex;flex-direction:column;">' +
                        '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                            '<div style="color:var(--text-secondary);font-size:13px;">Event</div>' +
                            '<div style="font-size:18px;font-weight:600;margin-top:4px;">' + esc(evt.title) + '</div>' +
                        '</div>' +
                        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                            '<div><div style="color:var(--text-secondary);font-size:13px;">Date</div><div style="font-size:14px;margin-top:4px;">' + (evt.start ? evt.start.toLocaleDateString() : '-') + '</div></div>' +
                            '<div><div style="color:var(--text-secondary);font-size:13px;">Time</div><div style="font-size:14px;margin-top:4px;">' + timeStr + '</div></div>' +
                            (props.venue ? '<div><div style="color:var(--text-secondary);font-size:13px;">Venue</div><div style="font-size:14px;margin-top:4px;">' + esc(props.venue) + '</div></div>' : '') +
                            '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="margin-top:4px;"><span class="badge status-' + (props.status || '') + '">' + (props.status || '').toUpperCase() + '</span></div></div>' +
                        '</div>' +
                    '</div>';
            } else {
                document.getElementById('eventModalTitle').textContent = 'Task Details';
                var html =
                    '<div style="display:flex;flex-direction:column;">' +
                        '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                            '<div style="color:var(--text-secondary);font-size:13px;">Task</div>' +
                            '<div style="font-size:18px;font-weight:600;margin-top:4px;">' + esc(evt.title) + '</div>' +
                        '</div>';
                if (props.description) {
                    html += '<div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">' +
                        '<div style="color:var(--text-secondary);font-size:13px;">Description</div>' +
                        '<div style="font-size:14px;margin-top:4px;line-height:1.6;">' + esc(props.description) + '</div>' +
                    '</div>';
                }
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Due Date</div><div style="font-size:14px;margin-top:4px;">' + (evt.start ? evt.start.toLocaleDateString() : '-') + '</div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Priority</div><div style="margin-top:4px;"><span class="badge priority-' + (props.priority || '') + '">' + (props.priority || '').toUpperCase() + '</span></div></div>' +
                    '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="font-size:14px;margin-top:4px;">' + (props.status || '').replace('_', ' ').toUpperCase() + '</div></div>' +
                '</div>' +
                '</div>';
                document.getElementById('eventModalBody').innerHTML = html;
            }

            var modal = document.getElementById('eventModal');
            modal.style.display = 'flex';
            modal.classList.add('active');
        }
    });
    calendar.render();
});

function closeEventModal() {
    var modal = document.getElementById('eventModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
}

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('eventModal')) closeEventModal();
});
</script>
</body>
</html>
