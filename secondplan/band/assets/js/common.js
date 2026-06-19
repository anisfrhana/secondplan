var icons = {
    check: '<i class="bi bi-check-circle btn-icon"></i>',
    x: '<i class="bi bi-x-circle btn-icon"></i>',
    trash: '<i class="bi bi-trash btn-icon"></i>',
    download: '<i class="bi bi-download btn-icon"></i>',
    plus: '<i class="bi bi-plus-circle btn-icon"></i>',
    edit: '<i class="bi bi-pencil-square btn-icon"></i>',
    eye: '<i class="bi bi-eye btn-icon"></i>',
    key: '<i class="bi bi-key btn-icon"></i>',
    play: '<i class="bi bi-play-circle btn-icon"></i>',
    save: '<i class="bi bi-floppy btn-icon"></i>',
    upload: '<i class="bi bi-upload btn-icon"></i>',
    receipt: '<i class="bi bi-receipt btn-icon"></i>'
};

function icon(name) {
    return icons[name] || '';
}

function showToast(msg, type) {
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'info');
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(function() { toast.classList.add('show'); });
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    var overlay = document.querySelector('.sidebar-overlay');
    if (overlay) overlay.classList.toggle('active');
}

function getCSRF() {
    var meta = document.querySelector('meta[name="csrf"]');
    if (meta) return meta.getAttribute('content') || '';
    var m = document.cookie.match(/(?:^|;\s*)csrf=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('.sidebar-overlay')) {
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = toggleSidebar;
        document.body.appendChild(overlay);
    }
});
