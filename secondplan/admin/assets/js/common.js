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
    close: '<i class="bi bi-x-lg btn-icon"></i>',
    receipt: '<i class="bi bi-receipt btn-icon"></i>',
    upload: '<i class="bi bi-upload btn-icon"></i>'
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
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}

function getCSRF() {
    var meta = document.querySelector('meta[name="csrf"]');
    if (meta) return meta.getAttribute('content') || '';
    var m = document.cookie.match(/(?:^|;\s*)csrf=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

function showLoading(el) {
    if (typeof el === 'string') el = document.getElementById(el);
    if (el) el.classList.add('is-loading');
}

function hideLoading(el) {
    if (typeof el === 'string') el = document.getElementById(el);
    if (el) el.classList.remove('is-loading');
}

function btnLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
        btn.dataset.originalText = btn.textContent;
        btn.disabled = true;
        btn.classList.add('btn-loading');
    } else {
        btn.disabled = false;
        btn.classList.remove('btn-loading');
        if (btn.dataset.originalText) btn.textContent = btn.dataset.originalText;
    }
}

function renderPagination(containerId, page, totalPages, callbackName) {
    var container = document.getElementById(containerId);
    if (!container || totalPages <= 1) {
        if (container) container.innerHTML = '';
        return;
    }
    var html = '<div class="pagination">';
    html += '<button class="page-btn" ' + (page <= 1 ? 'disabled' : '') + ' onclick="' + callbackName + '(' + (page - 1) + ')">Prev</button>';
    var start = Math.max(1, page - 2);
    var end = Math.min(totalPages, start + 4);
    start = Math.max(1, end - 4);
    if (start > 1) {
        html += '<button class="page-btn" onclick="' + callbackName + '(1)">1</button>';
        if (start > 2) html += '<span class="page-dots">...</span>';
    }
    for (var i = start; i <= end; i++) {
        html += '<button class="page-btn ' + (i === page ? 'active' : '') + '" onclick="' + callbackName + '(' + i + ')">' + i + '</button>';
    }
    if (end < totalPages) {
        if (end < totalPages - 1) html += '<span class="page-dots">...</span>';
        html += '<button class="page-btn" onclick="' + callbackName + '(' + totalPages + ')">' + totalPages + '</button>';
    }
    html += '<button class="page-btn" ' + (page >= totalPages ? 'disabled' : '') + ' onclick="' + callbackName + '(' + (page + 1) + ')">Next</button>';
    html += '</div>';
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('.sidebar-overlay')) {
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = toggleSidebar;
        document.body.appendChild(overlay);
    }
});
