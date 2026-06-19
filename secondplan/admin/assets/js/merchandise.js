var merchandise = [];
var searchQuery = '';
var editingMerchId = null;
var viewingMerchId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadMerchandise();
    setupSearch();
});

function loadMerchandise() {
    fetch('merchandise.php?api=list')
        .then(function(res) {
            if (!res.ok) throw new Error('Server error');
            return res.json();
        })
        .then(function(data) {
            if (data.success && data.data) {
                merchandise = data.data;
            }
            renderMerchandise();
            updateStats();
        })
        .catch(function() {
            showToast('Failed to load merchandise', 'error');
            var grid = document.getElementById('merchGrid');
            if (grid) grid.innerHTML = '<div class="empty-state">Failed to load merchandise</div>';
        });
}

function renderMerchandise() {
    var grid = document.getElementById('merchGrid');
    var filtered = merchandise.slice();

    if (searchQuery) {
        filtered = filtered.filter(function(m) {
            return (m.name || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (m.sku || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (m.category || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (m.description || '').toLowerCase().indexOf(searchQuery) !== -1;
        });
    }

    if (!filtered.length) {
        grid.innerHTML = '<div class="empty-state">No merchandise found</div>';
        return;
    }

    grid.innerHTML = filtered.map(function(m) {
        var stock = Number(m.stock) || 0;
        var threshold = Number(m.low_stock_threshold) || 0;
        var stockPct = threshold > 0 ? Math.min(100, Math.round((stock / (threshold * 3)) * 100)) : (stock > 0 ? 100 : 0);
        var stockClass = stock <= threshold && stock > 0 ? ' low' : '';
        var lowBadge = '';
        if (stock === 0) {
            lowBadge = '<span class="low-stock-badge" style="background:#ef4444;color:#fff;">OUT OF STOCK</span>';
        } else if (m.stock_status === 'low_stock') {
            lowBadge = '<span class="low-stock-badge">LOW STOCK</span>';
        }

        var imgSrc = m.image ? ((m.image.indexOf('assets/') === 0) ? '../' + m.image : '../uploads/' + m.image) : '';
        var imgHtml = m.image
            ? '<img src="' + imgSrc + '" alt="' + esc(m.name) + '">'
            : '<svg style="width:48px;height:48px;opacity:0.3;" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="2" width="12" height="12" rx="2"/><circle cx="5.5" cy="5.5" r="1.5"/><path d="M14 11l-3-3-4 4-2-2-3 3"/></svg>';

        return '<div class="merch-card" onclick="viewMerchandise(' + m.id + ')">' +
            '<div class="merch-image">' + imgHtml + lowBadge + '</div>' +
            '<div class="merch-details">' +
                '<h4>' + esc(m.name) + '</h4>' +
                (m.sku ? '<div class="merch-sku">SKU: ' + esc(m.sku) + '</div>' : '') +
                '<div class="merch-stats">' +
                    '<div><div class="merch-stat-label">Price</div><div class="merch-stat-value">RM ' + Number(m.price).toFixed(2) + '</div></div>' +
                    '<div><div class="merch-stat-label">Stock</div><div class="merch-stat-value">' + stock + '</div></div>' +
                '</div>' +
                '<div class="stock-bar"><div class="stock-fill' + stockClass + '" style="width:' + stockPct + '%"></div></div>' +
                '<div class="merch-actions">' +
                    '<button onclick="event.stopPropagation();editMerchandise(' + m.id + ')" class="btn-secondary btn-small">' + icon('edit') + ' Edit</button>' +
                    '<button onclick="event.stopPropagation();deleteMerchandise(' + m.id + ')" class="btn-danger btn-small">' + icon('trash') + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function updateStats() {
    var active = merchandise.filter(function(m) { return m.status === 'active'; });
    var totalValue = merchandise.reduce(function(s, m) { return s + (Number(m.stock) * Number(m.price)); }, 0);
    var lowStock = merchandise.filter(function(m) { return m.stock_status === 'low_stock'; }).length;

    document.getElementById('totalItems').textContent = merchandise.length;
    document.getElementById('totalValue').textContent = 'RM ' + totalValue.toLocaleString(undefined, {minimumFractionDigits:2});
    document.getElementById('lowStock').textContent = lowStock;
    document.getElementById('activeItems').textContent = active.length;
}

function viewMerchandise(id) {
    var m = merchandise.find(function(item) { return item.id == id; });
    if (!m) return;

    viewingMerchId = id;
    document.getElementById('viewModalTitle').textContent = m.name;

    var viewImgSrc = m.image ? ((m.image.indexOf('assets/') === 0) ? '../' + m.image : '../uploads/' + m.image) : '';
    var imgHtml = m.image
        ? '<img src="' + viewImgSrc + '" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:16px;">'
        : '';

    var statusBadge = '<span class="badge status-' + m.status + '">' + (m.status || '').toUpperCase() + '</span>';

    document.getElementById('viewModalBody').innerHTML =
        imgHtml +
        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Price</div><div style="font-size:18px;font-weight:700;margin-top:4px;">RM ' + Number(m.price).toFixed(2) + '</div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Cost</div><div style="font-size:18px;font-weight:700;margin-top:4px;">' + (m.cost ? 'RM ' + Number(m.cost).toFixed(2) : '-') + '</div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Stock</div><div style="font-size:18px;font-weight:700;margin-top:4px;">' + m.stock + '</div></div>' +
            '<div><div style="color:var(--text-secondary);font-size:13px;">Status</div><div style="margin-top:4px;">' + statusBadge + '</div></div>' +
            (m.sku ? '<div><div style="color:var(--text-secondary);font-size:13px;">SKU</div><div style="margin-top:4px;">' + esc(m.sku) + '</div></div>' : '') +
            (m.category ? '<div><div style="color:var(--text-secondary);font-size:13px;">Category</div><div style="margin-top:4px;">' + esc(m.category) + '</div></div>' : '') +
        '</div>' +
        (m.description ? '<div style="margin-top:16px;"><div style="color:var(--text-secondary);font-size:13px;">Description</div><div style="margin-top:4px;">' + esc(m.description) + '</div></div>' : '');

    var modal = document.getElementById('viewModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeViewModal() {
    var modal = document.getElementById('viewModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    viewingMerchId = null;
}

function editFromView() {
    if (viewingMerchId) {
        closeViewModal();
        editMerchandise(viewingMerchId);
    }
}

function editMerchandise(id) {
    var m = merchandise.find(function(item) { return item.id == id; });
    if (!m) return;

    editingMerchId = id;
    document.getElementById('editModalTitle').textContent = 'Edit Item';
    document.getElementById('merchId').value = id;
    document.getElementById('merchName').value = m.name || '';
    document.getElementById('merchSku').value = m.sku || '';
    document.getElementById('merchPrice').value = m.price || '';
    document.getElementById('merchCost').value = m.cost || '';
    document.getElementById('merchStock').value = m.stock || 0;
    document.getElementById('merchThreshold').value = m.low_stock_threshold || 10;
    document.getElementById('merchCategory').value = m.category || '';
    document.getElementById('merchStatus').value = m.status || 'active';
    document.getElementById('merchDesc').value = m.description || '';
    document.getElementById('merchImage').value = '';

    var modal = document.getElementById('editModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function openAddModal() {
    editingMerchId = null;
    document.getElementById('editModalTitle').textContent = 'Add Item';
    document.getElementById('merchId').value = '';
    document.getElementById('merchName').value = '';
    document.getElementById('merchSku').value = '';
    document.getElementById('merchPrice').value = '';
    document.getElementById('merchCost').value = '';
    document.getElementById('merchStock').value = '0';
    document.getElementById('merchThreshold').value = '10';
    document.getElementById('merchCategory').value = '';
    document.getElementById('merchStatus').value = 'active';
    document.getElementById('merchDesc').value = '';
    document.getElementById('merchImage').value = '';

    var modal = document.getElementById('editModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeEditModal() {
    var modal = document.getElementById('editModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    editingMerchId = null;
}

function saveMerchandise() {
    var name = document.getElementById('merchName').value.trim();
    var price = document.getElementById('merchPrice').value;

    if (!name || !price) {
        showToast('Name and price are required', 'error');
        return;
    }

    var fd = new FormData();
    fd.append('api', editingMerchId ? 'update' : 'create');
    if (editingMerchId) fd.append('id', editingMerchId);
    fd.append('name', name);
    fd.append('sku', document.getElementById('merchSku').value.trim());
    fd.append('price', price);
    fd.append('cost', document.getElementById('merchCost').value);
    fd.append('stock', document.getElementById('merchStock').value);
    fd.append('low_stock_threshold', document.getElementById('merchThreshold').value);
    fd.append('category', document.getElementById('merchCategory').value.trim());
    fd.append('status', document.getElementById('merchStatus').value);
    fd.append('description', document.getElementById('merchDesc').value.trim());

    var fileInput = document.getElementById('merchImage');
    if (fileInput.files.length > 0) {
        fd.append('image', fileInput.files[0]);
    }

    var isEdit = !!editingMerchId;
    fetch('merchandise.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                closeEditModal();
                showToast(isEdit ? 'Item updated' : 'Item created', 'success');
                loadMerchandise();
            } else {
                showToast(data.message || 'Failed to save', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function deleteMerchandise(id) {
    if (!confirm('Delete this item permanently?')) return;
    var fd = new FormData();
    fd.append('api', 'delete');
    fd.append('id', id);
    fetch('merchandise.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Item deleted', 'success');
                loadMerchandise();
            } else {
                showToast(data.message || 'Failed to delete', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function exportInventory() {
    if (!merchandise.length) {
        showToast('No merchandise to export', 'error');
        return;
    }

    var csv = 'ID,Name,SKU,Category,Price,Cost,Stock,Threshold,Status\n';
    merchandise.forEach(function(m) {
        csv += m.id + ',' +
            '"' + (m.name || '').replace(/"/g, '""') + '",' +
            '"' + (m.sku || '').replace(/"/g, '""') + '",' +
            '"' + (m.category || '').replace(/"/g, '""') + '",' +
            m.price + ',' +
            (m.cost || '') + ',' +
            m.stock + ',' +
            m.low_stock_threshold + ',' +
            (m.status || '') + '\n';
    });

    var blob = new Blob([csv], { type: 'text/csv' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'inventory_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(ev) {
            searchQuery = ev.target.value.toLowerCase();
            renderMerchandise();
        });
    }
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '-';
    return d.innerHTML;
}

window.addEventListener('click', function(ev) {
    if (ev.target === document.getElementById('viewModal')) closeViewModal();
    if (ev.target === document.getElementById('editModal')) closeEditModal();
});
