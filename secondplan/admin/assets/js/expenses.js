// Sample expenses data
let expenses = [
    { id: 1, date: '2025-01-28', category: 'Equipment', description: 'Professional microphones', vendor: 'Audio Pro Malaysia', amount: 4500, status: 'paid', receipt: 'receipt1.pdf' },
    { id: 2, date: '2025-01-26', category: 'Transportation', description: 'Van rental for event', vendor: 'Express Logistics', amount: 850, status: 'paid', receipt: 'receipt2.pdf' },
    { id: 3, date: '2025-01-25', category: 'Marketing', description: 'Facebook ads campaign', vendor: 'Digital Ads Co', amount: 2200, status: 'pending', receipt: null },
    { id: 4, date: '2025-01-24', category: 'Venue', description: 'Rehearsal space rental', vendor: 'Studio Space KL', amount: 1200, status: 'paid', receipt: 'receipt4.pdf' },
    { id: 5, date: '2025-01-23', category: 'Equipment', description: 'Guitar strings and accessories', vendor: 'Music Shop', amount: 350, status: 'paid', receipt: 'receipt5.pdf' }
];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadExpenses();
    updateStats();
    setupSearch();
});

// Load expenses from PHP backend
async function loadExpensesFromDB() {
    try {
        const response = await fetch('expenses.php?api=list');
        const data = await response.json();
        if (data.success && data.data) {
            expenses = data.data.map(e => ({
                id: e.id,
                date: e.date || e.expense_date,
                category: e.category,
                description: e.description || e.item,
                vendor: e.vendor,
                amount: parseFloat(e.amount),
                status: e.status || 'pending',
                receipt: e.receipt || e.receipt_path
            }));
        }
    } catch (error) {
        console.error('Error loading expenses:', error);
    }
}

// Load and display expenses
function loadExpenses() {
    const tbody = document.getElementById('expensesTable');
    
    if (expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No expenses found</td></tr>';
        return;
    }
    
    tbody.innerHTML = expenses.map(expense => `
        <tr>
            <td>${expense.date}</td>
            <td><span class="badge info">${expense.category}</span></td>
            <td>${expense.description}</td>
            <td>${expense.vendor}</td>
            <td><strong>RM ${expense.amount.toLocaleString()}</strong></td>
            <td><span class="badge ${expense.status === 'paid' ? 'success' : 'warning'}">${expense.status.toUpperCase()}</span></td>
            <td>
                ${expense.receipt ? `
                    <button onclick="viewReceipt('${expense.receipt}')" style="padding: 4px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.3); color: var(--blue); border-radius: 4px; cursor: pointer; font-size: 12px;">
                        View
                    </button>
                ` : '<span style="color: var(--text-secondary);">No receipt</span>'}
            </td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button onclick="editExpense(${expense.id})" style="padding: 4px 8px; background: transparent; border: 1px solid var(--border); color: var(--text-secondary); border-radius: 4px; cursor: pointer;">Edit</button>
                    <button onclick="deleteExpense(${expense.id})" style="padding: 4px 8px; background: transparent; border: 1px solid var(--border); color: var(--red); border-radius: 4px; cursor: pointer;">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Update statistics
function updateStats() {
    const total = expenses.reduce((sum, e) => sum + e.amount, 0);
    const equipment = expenses.filter(e => e.category === 'Equipment').reduce((sum, e) => sum + e.amount, 0);
    const marketing = expenses.filter(e => e.category === 'Marketing').reduce((sum, e) => sum + e.amount, 0);
    const pending = expenses.filter(e => e.status === 'pending').reduce((sum, e) => sum + e.amount, 0);
    
    document.getElementById('totalExpenses').textContent = `RM ${total.toLocaleString()}`;
    document.getElementById('equipmentExpenses').textContent = `RM ${equipment.toLocaleString()}`;
    document.getElementById('marketingExpenses').textContent = `RM ${marketing.toLocaleString()}`;
    document.getElementById('pendingPayments').textContent = `RM ${pending.toLocaleString()}`;
}

// Filter expenses
function filterExpenses() {
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let filtered = expenses;
    
    if (category) {
        filtered = filtered.filter(e => e.category === category);
    }
    
    if (status) {
        filtered = filtered.filter(e => e.status === status);
    }
    
    if (startDate) {
        filtered = filtered.filter(e => e.date >= startDate);
    }
    
    if (endDate) {
        filtered = filtered.filter(e => e.date <= endDate);
    }
    
    const tbody = document.getElementById('expensesTable');
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No expenses found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map(expense => 
        <tr>
            <td>${expense.date}</td>
            <td><span class="badge info">${expense.category}</span></td>
            <td>${expense.description}</td>
            <td>${expense.vendor}</td>
            <td><strong>RM ${expense.amount.toLocaleString()}</strong></td>
            <td><span class="badge ${expense.status === 'paid' ? 'success' : 'warning'}">${expense.status.toUpperCase()}</span></td>
            <td>
                ${expense.receipt ? `
                    <button onclick="viewReceipt('${expense.receipt}')" style="padding: 4px 12px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.3); color: var(--blue); border-radius: 4px; cursor: pointer; font-size: 12px;">
                        View
                    </button>
                ` : '<span style="color: var(--text-secondary);">No receipt</span>'}
            </td> </tr>
    ).join('');
}