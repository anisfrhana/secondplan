<?php
$title = 'My Dashboard · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); require_role(['member']);
$userId = (int)$_SESSION['user_id'];
include __DIR__ . '/../includes/header.php';
?>
<h1>My Schedule &amp; Tasks</h1>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:16px;">
  <section style="border:1px solid #ddd; border-radius:8px; padding:16px; background:#fff;">
    <h3>Event Schedule</h3>
    <div id="calendar"></div>
    <p style="color:#666">Drag to reschedule (if allowed). Click to view.</p>
  </section>

  <section style="border:1px solid #ddd; border-radius:8px; padding:16px; background:#fff;">
    <h3>My Tasks</h3>
    <div id="tasks"></div>
    <p style="color:#666">New assignments will pop up automatically.</p>
  </section>
</div>

<div id="toast" style="position:fixed; right:16px; bottom:16px; background:#0a7; color:#fff; padding:10px 14px; border-radius:8px; display:none;"></div>

<script>
const USER_ID = <?php echo $userId; ?>;

function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  setTimeout(()=> t.style.display='none', 2500);
}

async function loadTasks() {
  try {
    // FIXED: use /api/task.php (singular)
    const res = await fetch(`/api/task.php?assigned_to=${USER_ID}`, { credentials:'same-origin' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const list = await res.json();

    const el = document.getElementById('tasks');
    el.innerHTML = '';
    list.forEach(t => {
      const d = document.createElement('div');
      d.style.borderBottom = '1px solid #eee';
      d.style.padding = '8px 4px';
      const due = t.due_at ? new Date(t.due_at).toLocaleString() : '-';
      d.innerHTML = `
        <div><strong>${t.title}</strong></div>
        <div style="color:#666">Due: ${due} · ${t.status ?? ''} · ${t.priority ?? ''}</div>`;
      el.appendChild(d);
    });
  } catch (e) {
    console.error(e);
  }
}

async function pollNotifications() {
  try {
    const r = await fetch('/api/notifications.php', { credentials:'same-origin' });
    if (!r.ok) return;
    const items = await r.json();
    const unsent = items.filter(n => n.type === 'task_assignment' && Number(n.is_sent) === 0);
    if (unsent.length) {
      const n = unsent[0];
      toast(n.title + ': ' + n.message);
      if ('Notification' in window) {
        if (Notification.permission === 'granted') {
          new Notification(n.title, { body: n.message });
        } else if (Notification.permission !== 'denied') {
          Notification.requestPermission().then(p => {
            if (p==='granted') new Notification(n.title, { body: n.message });
          });
        }
      }
      const f = new FormData(); f.append('mark_read','1');
      await fetch('/api/notifications.php', { method:'POST', body:f, credentials:'same-origin' });
      loadTasks();
    }
  } catch(e) { /* ignore polling errors */ }
}

document.addEventListener('DOMContentLoaded', async () => {
  if ('Notification' in window && Notification.permission !== 'granted') Notification.requestPermission();

const cal = new FullCalendar.Calendar(calEl, {
  initialView: 'dayGridMonth',
  eventSources: [
    { url: '/api/events.php' },          // events (your bookings or events table)
    { url: '/api/tasks_calendar.php' }   // tasks as calendar items
  ],
  editable: true,
  eventDrop: async (info) => {
    try {
      if (info.event.groupId === 'task') {
        // (Optional) If you implemented /api/task.php/{id}/move:
        const payload = { due_at: info.event.start.toISOString() };
        const r = await fetch(`/api/task.php/${info.event.id}/move`, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials:'same-origin'
        });
        const data = await r.json();
        if (!data.ok) info.revert(); else toast('Task rescheduled');
      } else {
        // Default: events move via /api/events.php/{id}/move
        const payload = { start: info.event.start.toISOString(), end: info.event.end?.toISOString() };
        const r = await fetch(`/api/events.php/${info.event.id}/move`, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials:'same-origin'
        });
        const data = await r.json();
        if (!data.ok) info.revert(); else toast('Event rescheduled');
      }
    } catch (e) { info.revert(); }
  }
});

  cal.render();

  await loadTasks();
  setInterval(pollNotifications, 15000);
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User · Dashboard · SecondPlan</title>
  <link rel="stylesheet" href="assets/css/user.css">
  <script src="assets/js/user.js" defer></script>
</head>
<body data-page="dashboard">
  <nav class="topbar">
    <span class="brand">SecondPlan</span>
    <a href="dashboard.html">Dashboard</a>
    <a href="booking.html">Booking</a>
    <a href="merchandise.html">Merchandise</a>
    <a href="tasks.html">Tasks</a>
    <span class="spacer"></span>
    <a href="../auth/logout.php">Logout</a>
  </nav>

  <div class="container">
    <div class="page-head"><div class="page-title">My Dashboard</div></div>
    <section class="cards" id="cards"></section>

    <section style="margin-top:22px">
      <p class="sub">Quick overview of your bookings, upcoming events and assigned tasks.</p>
    </section>
  </div>

  <div class="toast"></div>
</body>
</html>
