/* user.js - single JS powering all user pages */

const API = {
  dashboard: 'dashboard.php',
  booking: 'booking.php',
  bookingSave: 'booking_save.php',
  merchandise: 'merchandise.php',
  tasks: 'tasks.php',
};

// --- Utilities ---
function $(sel, scope=document){ return scope.querySelector(sel); }
function $all(sel, scope=document){ return [...scope.querySelectorAll(sel)]; }

function toast(message, type='info'){
  let t = $('.toast');
  if(!t){
    t = document.createElement('div');
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = message;
  t.style.borderColor = type==='success' ? '#1e6442' : type==='error' ? '#7b2d2d' : '#263142';
  t.classList.add('show');
  setTimeout(()=> t.classList.remove('show'), 2400);
}

function statusBadge(status){
  const el = document.createElement('span');
  const s = (status || 'pending').toLowerCase();
  el.className = 'badge status ' + (s==='approved'?'green':s==='rejected'?'red':'amber');
  el.textContent = s.toUpperCase();
  return el;
}

function fmtRM(n){ n = Number(n||0); return 'RM ' + n.toFixed(2); }
function fmtDate(s){ if(!s) return '-'; const d = new Date(s); return Number.isNaN(d)? s : d.toLocaleString(); }

function getCSRF(){
  // If your backend needs CSRF, expose it in a cookie named "csrf" or a <meta> tag.
  const meta = document.querySelector('meta[name="csrf"]');
  if (meta) return meta.getAttribute('content') || '';
  const m = document.cookie.match(/(?:^|;\s*)csrf=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : '';
}

async function fetchJSON(url, opts={}){
  const res = await fetch(url, {
    credentials:'same-origin',
    headers: { 'Accept':'application/json', ...(opts.headers||{}) },
    ...opts
  });
  const text = await res.text();
  let data = null;
  try{ data = JSON.parse(text); }catch(e){ /* server returned HTML - not JSON */ }
  if(!res.ok){
    const msg = (data && data.message) ? data.message : `HTTP ${res.status}`;
    throw new Error(msg);
  }
  if(data && data.success===false){
    throw new Error(data.message || 'Request failed');
  }
  return data ?? { success:true, raw:text };
}

async function postForm(url, formOrObj){
  const fd = (formOrObj instanceof FormData) ? formOrObj : new FormData();
  if(!(formOrObj instanceof FormData) && formOrObj && typeof formOrObj==='object'){
    Object.entries(formOrObj).forEach(([k,v])=> fd.append(k, v));
  }
  const csrf = getCSRF();
  if(csrf && !fd.has('csrf')) fd.append('csrf', csrf);
  return fetchJSON(url, { method:'POST', body: fd });
}

function renderTable(container, columns, rows){
  const el = typeof container==='string' ? $(container) : container;
  el.innerHTML = '';
  const table = document.createElement('table'); table.className='table';
  const thead = document.createElement('thead'); const trh = document.createElement('tr');
  columns.forEach(c=>{ const th=document.createElement('th'); th.textContent=c.label; trh.appendChild(th); });
  thead.appendChild(trh);
  const tbody = document.createElement('tbody');
  rows.forEach(r=>{
    const tr = document.createElement('tr');
    columns.forEach(c=>{
      const td = document.createElement('td');
      td.appendChild( c.render ? c.render(r) : document.createTextNode(r[c.key] ?? '') );
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });
  table.appendChild(thead); table.appendChild(tbody); el.appendChild(table);
}

// --- Pages ---

// Dashboard: show quick stats for the logged-in user
async function dashboardPage(){
  try{
    const {success, data, message} = await fetchJSON(`${API.dashboard}?api=stats`);
    if(!success) throw new Error(message || 'Failed to load stats');

    const {
      myBookings = 0,
      upcomingEvents = 0,
      myTasks = 0
    } = data || {};

    $('#cards').innerHTML = `
      <div class="card"><div class="label">My Bookings</div><div class="value">${myBookings}</div><div class="sub">Total submitted</div></div>
      <div class="card"><div class="label">Upcoming Events</div><div class="value">${upcomingEvents}</div><div class="sub">Next 30 days</div></div>
      <div class="card"><div class="label">My Tasks</div><div class="value">${myTasks}</div><div class="sub">Assigned to me</div></div>
    `;
  }catch(e){ toast('Dashboard: '+e.message, 'error'); }
}

// Booking: list available events + my bookings; allow quick booking
async function bookingPage(){
  const search = $('#search');
  const refreshBtn = $('#refresh');

  async function loadLists(){
    // Available events
    try{
      const q = search.value.trim();
      const {success, data, message} = await fetchJSON(`${API.booking}?api=events${q?`&q=${encodeURIComponent(q)}`:''}`);
      if(!success) throw new Error(message || 'Failed to load events');

      renderTable('#events-table', [
        {key:'title', label:'Event'},
        {key:'date', label:'Date', render:r=>document.createTextNode(fmtDate(r.date))},
        {key:'location', label:'Location'},
        {key:'capacity_left', label:'Seats Left'},
        {key:'actions', label:'', render:r=>{
          const wrap=document.createElement('div');
          const btn=document.createElement('button'); btn.className='btn success'; btn.textContent='Book';
          btn.onclick = async ()=>{
            try{
              const fd = new FormData();
              fd.append('event_id', r.id);
              // optional note (could be a prompt or a textarea on page)
              const res = await postForm(API.bookingSave, fd);
              if(!res.success) throw new Error(res.message||'Booking failed');
              toast('Booking submitted', 'success');
              await loadMyBookings(); // refresh my bookings
            }catch(e){ toast(e.message,'error'); }
          };
          wrap.appendChild(btn); return wrap;
        }},
      ], data || []);
    }catch(e){ toast('Events: '+e.message, 'error'); }

    // My bookings
    await loadMyBookings();
  }

  async function loadMyBookings(){
    try{
      const json = await fetchJSON(`${API.booking}?api=my`);
      renderTable('#my-table', [
        {key:'event', label:'Event'},
        {key:'requested_at', label:'Requested At', render:r=>document.createTextNode(fmtDate(r.requested_at))},
        {key:'status', label:'Status', render:r=>statusBadge(r.status)},
      ], (json.data||[]));
    }catch(e){ toast('My bookings: '+e.message, 'error'); }
  }

  refreshBtn.addEventListener('click', loadLists);
  search.addEventListener('input', ()=>{ clearTimeout(bookingPage._t); bookingPage._t=setTimeout(loadLists, 300); });
  await loadLists();
}

// Merchandise: list items and allow simple order/request
async function merchandisePage(){
  const search = $('#search');
  const refreshBtn = $('#refresh');

  async function load(){
    try{
      const q = search.value.trim();
      const {success, data, message} = await fetchJSON(`${API.merchandise}?api=list${q?`&q=${encodeURIComponent(q)}`:''}`);
      if(!success) throw new Error(message || 'Failed to load items');

      renderTable('#items-table', [
        {key:'name', label:'Item'},
        {key:'sku', label:'SKU'},
        {key:'price', label:'Price', render:r=>document.createTextNode(fmtRM(r.price))},
        {key:'stock', label:'Stock'},
        {key:'actions', label:'', render:r=>{
          const wrap=document.createElement('div');
          const qty=document.createElement('input');
          qty.type='number'; qty.min='1'; qty.value='1'; qty.className='input'; qty.style.width='80px'; qty.title='Qty';

          const btn=document.createElement('button'); btn.className='btn primary'; btn.textContent='Order';
          btn.onclick = async ()=>{
            try{
              const fd = new FormData();
              fd.append('action','order'); fd.append('item_id', r.id); fd.append('qty', qty.value);
              const res = await postForm(API.merchandise, fd);
              if(!res.success) throw new Error(res.message||'Order failed');
              toast('Order placed', 'success');
            }catch(e){ toast(e.message,'error'); }
          };
          wrap.appendChild(qty); wrap.appendChild(btn); return wrap;
        }},
      ], data || []);
    }catch(e){ toast('Merchandise: '+e.message, 'error'); }
  }

  refreshBtn.addEventListener('click', load);
  search.addEventListener('input', ()=>{ clearTimeout(merchandisePage._t); merchandisePage._t=setTimeout(load, 300); });
  await load();
}

// Tasks: list my tasks; allow mark done
async function tasksPage(){
  const search = $('#search');
  const refreshBtn = $('#refresh');

  async function load(){
    try{
      const q = search.value.trim();
      const json = await fetchJSON(`${API.tasks}?api=my${q?`&q=${encodeURIComponent(q)}`:''}`);
      renderTable('#tasks-table', [
        {key:'title', label:'Task'},
        {key:'due_date', label:'Due', render:r=>document.createTextNode(fmtDate(r.due_date))},
        {key:'priority', label:'Priority', render:r=>{ const b=document.createElement('span'); b.className='badge status amber'; b.textContent=(r.priority||'').toUpperCase(); return b; }},
        {key:'status', label:'Status', render:r=>statusBadge(r.status)},
        {key:'actions', label:'', render:r=>{
          const wrap=document.createElement('div');
          const btn=document.createElement('button'); btn.className='btn success'; btn.textContent='Mark Done';
          btn.disabled = (r.status||'').toLowerCase()==='approved' || (r.status||'').toLowerCase()==='done';
          btn.onclick = async ()=>{
            try{
              const fd = new FormData(); fd.append('action','complete'); fd.append('id', r.id);
              const res = await postForm(API.tasks, fd);
              if(!res.success) throw new Error(res.message||'Update failed');
              toast('Task updated', 'success'); load();
            }catch(e){ toast(e.message,'error'); }
          };
          wrap.appendChild(btn); return wrap;
        }},
      ], json.data || []);
    }catch(e){ toast('Tasks: '+e.message, 'error'); }
  }

  refreshBtn.addEventListener('click', load);
  search.addEventListener('input', ()=>{ clearTimeout(tasksPage._t); tasksPage._t=setTimeout(load, 300); });
  await load();
}

// Boot
document.addEventListener('DOMContentLoaded', ()=>{
  const page = document.body.dataset.page;
  const map = {
    dashboard: dashboardPage,
    booking: bookingPage,
    merchandise: merchandisePage,
    tasks: tasksPage
  };
  if(map[page]) mappage;
});

// --- Add to API map if you like, or just hardcode the action URL ---
API.bookingSave = 'booking_save.php';

// --- Booking Form Page (progressive) ---
function bookingFormPage(){
  const form = document.getElementById('booking-form');
  if(!form) return;

  const poster = document.getElementById('poster');
  const preview = document.getElementById('preview');
  const previewImg = document.getElementById('preview-img');
  const previewName = document.getElementById('preview-name');
  const submitBtn = document.getElementById('submit-booking');

  // Basic preview (images only)
  poster.addEventListener('change', ()=>{
    const f = poster.files?.[0];
    preview.style.display = 'none';
    if(!f) return;
    previewName.textContent = `${f.name} (${Math.round(f.size/1024)} KB)`;

    if (f.type.startsWith('image/')){
      const url = URL.createObjectURL(f);
      previewImg.src = url;
      preview.style.display = 'flex';
    }
  });

  function setLoading(isLoading){
    if(!submitBtn) return;
    submitBtn.disabled = !!isLoading;
    submitBtn.dataset.oldText ??= submitBtn.textContent;
    submitBtn.textContent = isLoading ? 'Submitting…' : submitBtn.dataset.oldText;
  }

  form.addEventListener('submit', async (e)=>{
    // Client checks: file size/type
    const f = poster.files?.[0];
    if(!f){ toast('Please attach a poster.', 'error'); e.preventDefault(); return; }
    const okTypes = ['image/jpeg','image/png','application/pdf'];
    if(!okTypes.includes(f.type)){ toast('Poster must be JPG, PNG, or PDF.', 'error'); e.preventDefault(); return; }
    if(f.size > 5*1024*1024){ toast('Poster max size is 5MB.', 'error'); e.preventDefault(); return; }

    // Try AJAX JSON mode; if server returns HTML (no JSON), fall back to default submit
    setLoading(true);
    try{
      const fd = new FormData(form);

      // If you expose CSRF via <meta name="csrf">, attach it
      const meta = document.querySelector('meta[name="csrf"]');
      if(meta && !fd.has('csrf')) fd.append('csrf', meta.getAttribute('content') || '');

      const res = await fetch(API.bookingSave, { method:'POST', body: fd, headers:{ 'Accept':'application/json' }, credentials:'same-origin' });
      const text = await res.text();
      let json = null;
      try{ json = JSON.parse(text); }catch(_){ /* not JSON → fallback */ }

      if(json){
        e.preventDefault();
        if(!res.ok || json.success === false) throw new Error(json.message || `HTTP ${res.status}`);
        toast(json.message || 'Booking submitted', 'success');
        setTimeout(()=> location.href = json.redirect || 'dashboard.html', 700);
      }
      // else: let normal submit proceed (no preventDefault)
    }catch(err){
      e.preventDefault();
      toast(err.message || 'Submit failed', 'error');
    }finally{
      setLoading(false);
    }
  });
}

// ---- Attach to boot map ----
document.addEventListener('DOMContentLoaded', ()=>{
  // ... existing boot code ...
  const page = document.body.dataset.page;
  const map = {
    dashboard: dashboardPage,
    booking: bookingPage,
    merchandise: merchandisePage,
    tasks: tasksPage,
    booking_form: bookingFormPage,   // <-- add this
  };
  if(map[page]) mappage;
});

const fd = new FormData();
fd.append('task_id', r.id);
await fetch('/api/task.php?action=complete', { method:'POST', body: fd, credentials:'same-origin' });

const cal = new FullCalendar.Calendar(calEl, {
  initialView: 'dayGridMonth',
  eventSources: [
    { url: '/api/events.php' },
    { url: '/api/tasks_calendar.php' },
  ],
  editable: true,
  eventDrop: async (info) => {
    try {
      if (info.event.groupId === 'task') {
        const payload = { due_at: info.event.start.toISOString() };
        const r = await fetch(`/api/task.php/${info.event.id}/move`, {
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
        });
        const data = await r.json();
        if (!data.ok) info.revert(); else toast('Task rescheduled');
      } else {
        const payload = { start: info.event.start.toISOString(), end: info.event.end?.toISOString() };
        const r = await fetch(`/api/events.php/${info.event.id}/move`, {
          method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
        });
        const data = await r.json();
        if (!data.ok) info.revert(); else toast('Event rescheduled');
      }
    } catch (e) { info.revert(); }
  }
});