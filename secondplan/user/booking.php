<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        #availabilityCalendar {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .fc { color: var(--text); font-size: 13px; }
        .fc .fc-toolbar-title { color: var(--text); font-size: 1.1em; }
        .fc .fc-button { background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.3); color: var(--text); font-size: 12px; padding: 4px 10px; border-radius: 6px; }
        .fc .fc-button:hover { background: rgba(245,158,11,0.4); }
        .fc .fc-button-active { background: var(--accent); border-color: var(--accent); color: #fff; }
        .fc .fc-daygrid-day { background: transparent; cursor: pointer; }
        .fc .fc-daygrid-day:hover { background: rgba(245,158,11,0.08); }
        .fc .fc-daygrid-day-number { color: var(--text-secondary); }
        .fc .fc-day-today { background: rgba(245,158,11,0.08) !important; }
        .fc .fc-col-header-cell-cushion { color: var(--text-secondary); }
        .fc th, .fc td { border-color: var(--border); }
        .fc .fc-scrollgrid { border-color: var(--border); }
        .cal-legend { display: flex; gap: 16px; margin-top: 10px; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary); }
        .cal-legend-dot { width: 10px; height: 10px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <div>
                <h2>Event Booking</h2>
                <div class="subtitle">Submit a booking request for your event</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'U', 0, 1)) ?></div>
            </div>
        </header>

        <main class="content">
            <div id="alertBox"></div>

            <div class="booking-layout">
            <div class="booking-calendar">
                <div id="availabilityCalendar"></div>
                <div class="cal-legend">
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#ef4444;"></div><span>Booked (Approved)</span></div>
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#f97316;"></div><span>Pending Booking</span></div>
                    <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#f97316;"></div><span>Scheduled Event</span></div>
                </div>
            </div>

            <div class="booking-form-section section">
                <div class="step-indicator" id="stepIndicator">
                    <div class="step-dot-wrapper active" data-step="1">
                        <div class="step-dot active">1</div>
                        <div class="step-name">Details</div>
                    </div>
                    <div class="step-line" id="stepLine1"></div>
                    <div class="step-dot-wrapper" data-step="2">
                        <div class="step-dot">2</div>
                        <div class="step-name">Date & Venue</div>
                    </div>
                    <div class="step-line" id="stepLine2"></div>
                    <div class="step-dot-wrapper" data-step="3">
                        <div class="step-dot">3</div>
                        <div class="step-name">Review</div>
                    </div>
                </div>

                <form id="bookingForm" method="POST" action="booking_save.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="step-group active" id="step1">
                        <h3 style="margin-bottom:16px;">Event Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Company / Client Name</label>
                                <input type="text" name="company" required placeholder="Your company name">
                            </div>
                            <div class="form-group">
                                <label>Event Title</label>
                                <input type="text" name="title" required placeholder="e.g., Showcase Night">
                            </div>
                            <div class="form-group">
                                <label>Budget / Quotation Price (RM per day)</label>
                                <input type="number" name="quotation_price" step="0.01" min="0" placeholder="e.g. 300.00">
                            </div>
                            <div class="form-group full">
                                <label>Notes (optional)</label>
                                <textarea name="notes" rows="2" placeholder="Special requirements"></textarea>
                            </div>
                        </div>
                        <div class="step-nav">
                            <div></div>
                            <button type="button" class="btn-step-next" onclick="goToStep(2)">Next</button>
                        </div>
                    </div>

                    <div class="step-group" id="step2">
                        <h3 style="margin-bottom:16px;">Date & Venue</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Event Date</label>
                                <input type="date" name="event_date" id="eventDateInput" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label>Event Time</label>
                                <input type="time" name="event_time" required>
                            </div>
                            <div class="form-group full">
                                <label>Address</label>
                                <input type="text" name="address" required placeholder="Street address">
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" required>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" required>
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" required>
                            </div>
                        </div>
                        <div class="step-nav">
                            <button type="button" class="btn-step-back" onclick="goToStep(1)">Back</button>
                            <button type="button" class="btn-step-next" onclick="goToStep(3)">Next</button>
                        </div>
                    </div>

                    <div class="step-group" id="step3">
                        <h3 style="margin-bottom:16px;">Upload & Review</h3>
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Event Poster (JPG/PNG/PDF, max 5MB)</label>
                                <input type="file" name="poster" id="posterInput" accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="preview" id="preview">
                                    <img id="previewImg" alt="Preview">
                                </div>
                            </div>
                        </div>
                        <div class="step-nav">
                            <button type="button" class="btn-step-back" onclick="goToStep(2)">Back</button>
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-send btn-icon"></i>
                                Submit Booking
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
document.getElementById('posterInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('previewImg').src = ev.target.result;
            document.getElementById('preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('preview').style.display = 'none';
    }
});

function goToStep(step) {
    document.querySelectorAll('.step-group').forEach(function(g) { g.classList.remove('active'); });
    document.getElementById('step' + step).classList.add('active');

    document.querySelectorAll('.step-dot-wrapper').forEach(function(w) {
        var s = parseInt(w.getAttribute('data-step'));
        var dot = w.querySelector('.step-dot');
        w.classList.remove('active', 'done');
        dot.classList.remove('active', 'done');
        if (s < step) {
            w.classList.add('done');
            dot.classList.add('done');
            dot.innerHTML = '<i class="bi bi-check-lg" style="color:white;font-size:16px;"></i>';
        } else if (s === step) {
            w.classList.add('active');
            dot.classList.add('active');
            dot.textContent = s;
        } else {
            dot.textContent = s;
        }
    });

    var line1 = document.getElementById('stepLine1');
    var line2 = document.getElementById('stepLine2');
    if (step >= 2) { line1.classList.add('done'); } else { line1.classList.remove('done'); }
    if (step >= 3) { line2.classList.add('done'); } else { line2.classList.remove('done'); }
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('availabilityCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        },
        eventSources: [{
            url: '../api/booked_dates.php',
            failure: function() {}
        }],
        dateClick: function(info) {
            var today = new Date().toISOString().split('T')[0];
            if (info.dateStr >= today) {
                document.getElementById('eventDateInput').value = info.dateStr;
            }
        }
    });
    calendar.render();
});
</script>
</body>
</html>
