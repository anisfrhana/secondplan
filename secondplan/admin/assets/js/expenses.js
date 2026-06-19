var expenses = [];
var searchQuery = '';
var editingExpenseId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadExpenses();
    setupSearch();
});

function loadExpenses() {
    fetch('expenses.php?api=list')
        .then(function(res) {
            if (!res.ok) throw new Error('Server error');
            return res.json();
        })
        .then(function(data) {
            if (data.success && data.data) {
                expenses = data.data;
            }
            renderExpenses();
            updateStats();
        })
        .catch(function() {
            showToast('Failed to load expenses', 'error');
            var tbody = document.getElementById('expensesTable');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="empty-state">Failed to load expenses</td></tr>';
        });
}

function getFiltered() {
    var filtered = expenses.slice();
    var catEl = document.getElementById('filterCategory');
    var statusEl = document.getElementById('filterStatus');
    var dateFromEl = document.getElementById('filterDateFrom');
    var dateToEl = document.getElementById('filterDateTo');
    var cat = catEl ? catEl.value : '';
    var status = statusEl ? statusEl.value : '';
    var dateFrom = dateFromEl ? dateFromEl.value : '';
    var dateTo = dateToEl ? dateToEl.value : '';

    if (cat) {
        filtered = filtered.filter(function(e) { return e.category === cat; });
    }
    if (status) {
        filtered = filtered.filter(function(e) { return e.status === status; });
    }
    if (dateFrom) {
        filtered = filtered.filter(function(e) { return e.expense_date >= dateFrom; });
    }
    if (dateTo) {
        filtered = filtered.filter(function(e) { return e.expense_date <= dateTo; });
    }
    if (searchQuery) {
        filtered = filtered.filter(function(e) {
            return (e.category || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (e.vendor || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (e.description || '').toLowerCase().indexOf(searchQuery) !== -1 ||
                (e.submitted_by_name || '').toLowerCase().indexOf(searchQuery) !== -1;
        });
    }
    return filtered;
}

function renderExpenses() {
    var tbody = document.getElementById('expensesTable');
    var filtered = getFiltered();

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No expenses found</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(function(e) {
        var actionsHtml = '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
        if (e.status === 'pending') {
            actionsHtml += '<button onclick="event.stopPropagation();approveExpense(' + e.id + ')" class="btn-success btn-small">' + icon('check') + '</button>';
            actionsHtml += '<button onclick="event.stopPropagation();rejectExpense(' + e.id + ')" class="btn-danger btn-small">' + icon('x') + '</button>';
        }
        if (e.receipt) {
            actionsHtml += '<button onclick="event.stopPropagation();viewReceipt(\'' + esc(e.receipt) + '\')" class="btn-secondary btn-small">' + icon('eye') + '</button>';
        }
        actionsHtml += '<button onclick="event.stopPropagation();editExpense(' + e.id + ')" class="btn-secondary btn-small">' + icon('edit') + '</button>';
        actionsHtml += '<button onclick="event.stopPropagation();deleteExpense(' + e.id + ')" class="btn-danger btn-small">' + icon('trash') + '</button>';
        actionsHtml += '</div>';

        return '<tr>' +
            '<td>#' + e.id + '</td>' +
            '<td>' + esc(e.category) + '</td>' +
            '<td><strong>RM ' + Number(e.amount).toLocaleString(undefined, {minimumFractionDigits:2}) + '</strong></td>' +
            '<td>' + (e.expense_date || '-') + '</td>' +
            '<td>' + esc(e.vendor) + '</td>' +
            '<td>' + esc(e.description) + '</td>' +
            '<td>' + esc(e.submitted_by_name) + '</td>' +
            '<td><span class="badge status-' + e.status + '">' + (e.status || '').toUpperCase() + '</span></td>' +
            '<td>' + actionsHtml + '</td>' +
            '</tr>';
    }).join('');
}

function updateStats() {
    document.getElementById('totalExpenses').textContent = expenses.length;
    document.getElementById('pendingExpenses').textContent = expenses.filter(function(e) { return e.status === 'pending'; }).length;
    document.getElementById('approvedExpenses').textContent = expenses.filter(function(e) { return e.status === 'approved'; }).length;
    var total = expenses.filter(function(e) { return e.status === 'approved'; }).reduce(function(s, e) { return s + Number(e.amount || 0); }, 0);
    document.getElementById('totalAmount').textContent = 'RM ' + total.toLocaleString(undefined, {minimumFractionDigits:2});
}

function approveExpense(id) {
    if (!confirm('Approve this expense?')) return;
    var fd = new FormData();
    fd.append('api', 'approve');
    fd.append('id', id);
    fetch('expenses.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Expense approved', 'success');
                loadExpenses();
            } else {
                showToast(data.message || 'Failed to approve', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function rejectExpense(id) {
    if (!confirm('Reject this expense?')) return;
    var fd = new FormData();
    fd.append('api', 'reject');
    fd.append('id', id);
    fetch('expenses.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Expense rejected', 'success');
                loadExpenses();
            } else {
                showToast(data.message || 'Failed to reject', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function deleteExpense(id) {
    if (!confirm('Delete this expense permanently?')) return;
    var fd = new FormData();
    fd.append('api', 'delete');
    fd.append('id', id);
    fetch('expenses.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Expense deleted', 'success');
                loadExpenses();
            } else {
                showToast(data.message || 'Failed to delete', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function editExpense(id) {
    var exp = expenses.find(function(e) { return e.id == id; });
    if (!exp) return;

    editingExpenseId = id;
    document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
    document.getElementById('expenseId').value = id;
    document.getElementById('expCategory').value = exp.category || '';
    document.getElementById('expAmount').value = exp.amount || '';
    document.getElementById('expDate').value = exp.expense_date || '';
    document.getElementById('expVendor').value = exp.vendor || '';
    document.getElementById('expReference').value = exp.reference || '';
    document.getElementById('expDescription').value = exp.description || '';
    document.getElementById('expReceipt').value = '';

    var modal = document.getElementById('expenseModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function openExpenseModal() {
    editingExpenseId = null;
    document.getElementById('expenseModalTitle').textContent = 'Add Expense';
    document.getElementById('expenseId').value = '';
    document.getElementById('expCategory').value = '';
    document.getElementById('expAmount').value = '';
    document.getElementById('expDate').value = '';
    document.getElementById('expVendor').value = '';
    document.getElementById('expReference').value = '';
    document.getElementById('expDescription').value = '';
    document.getElementById('expReceipt').value = '';

    var modal = document.getElementById('expenseModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeExpenseModal() {
    var modal = document.getElementById('expenseModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    editingExpenseId = null;
}

function saveExpense() {
    var category = document.getElementById('expCategory').value;
    var amount = document.getElementById('expAmount').value;
    var expDate = document.getElementById('expDate').value;

    if (!category || !amount || !expDate) {
        showToast('Category, amount and date are required', 'error');
        return;
    }

    var fd = new FormData();
    fd.append('api', editingExpenseId ? 'update' : 'create');
    if (editingExpenseId) fd.append('id', editingExpenseId);
    fd.append('category', category);
    fd.append('amount', amount);
    fd.append('expense_date', expDate);
    fd.append('vendor', document.getElementById('expVendor').value);
    fd.append('reference', document.getElementById('expReference').value);
    fd.append('description', document.getElementById('expDescription').value);

    var fileInput = document.getElementById('expReceipt');
    if (fileInput.files.length > 0) {
        fd.append('receipt', fileInput.files[0]);
    }

    var isEdit = !!editingExpenseId;
    fetch('expenses.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                closeExpenseModal();
                showToast(isEdit ? 'Expense updated' : 'Expense created', 'success');
                loadExpenses();
            } else {
                showToast(data.message || 'Failed to save', 'error');
            }
        })
        .catch(function() { showToast('Operation failed', 'error'); });
}

function viewReceipt(filename) {
    var body = document.getElementById('receiptModalBody');
    var ext = filename.split('.').pop().toLowerCase();
    var url = '../uploads/' + filename;

    if (ext === 'pdf') {
        body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:400px;border:none;border-radius:8px;"></iframe>';
    } else {
        body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:400px;border-radius:8px;">';
    }

    var modal = document.getElementById('receiptModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeReceiptModal() {
    var modal = document.getElementById('receiptModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function exportExpenses() {
    var filtered = getFiltered();
    if (!filtered.length) {
        showToast('No expenses to export', 'error');
        return;
    }

    var csv = 'ID,Category,Amount,Date,Vendor,Reference,Description,Submitted By,Status\n';
    filtered.forEach(function(e) {
        csv += e.id + ',' +
            '"' + (e.category || '').replace(/"/g, '""') + '",' +
            e.amount + ',' +
            (e.expense_date || '') + ',' +
            '"' + (e.vendor || '').replace(/"/g, '""') + '",' +
            '"' + (e.reference || '').replace(/"/g, '""') + '",' +
            '"' + (e.description || '').replace(/"/g, '""') + '",' +
            '"' + (e.submitted_by_name || '').replace(/"/g, '""') + '",' +
            (e.status || '') + '\n';
    });

    var blob = new Blob([csv], { type: 'text/csv' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'expenses_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}

function setupSearch() {
    var searchBox = document.getElementById('searchBox');
    if (searchBox) {
        searchBox.addEventListener('input', function(ev) {
            searchQuery = ev.target.value.toLowerCase();
            renderExpenses();
        });
    }
}

function esc(text) {
    var d = document.createElement('div');
    d.textContent = text || '-';
    return d.innerHTML;
}

window.addEventListener('click', function(ev) {
    if (ev.target === document.getElementById('expenseModal')) closeExpenseModal();
    if (ev.target === document.getElementById('receiptModal')) closeReceiptModal();
});
