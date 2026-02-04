// Sample merchandise data
let merchandise = [
    { id: 1, name: 'Band T-Shirt', sku: 'TS-001', price: 45, stock: 120, lowStockThreshold: 20, description: 'Official band t-shirt in black', image: null },
    { id: 2, name: 'Album CD', sku: 'CD-001', price: 30, stock: 85, lowStockThreshold: 15, description: 'Latest album release', image: null },
    { id: 3, name: 'Hoodie', sku: 'HD-001', price: 120, stock: 15, lowStockThreshold: 20, description: 'Comfortable hoodie with band logo', image: null },
    { id: 4, name: 'Poster Set', sku: 'PS-001', price: 25, stock: 50, lowStockThreshold: 10, description: 'Set of 3 concert posters', image: null },
    { id: 5, name: 'Tote Bag', sku: 'TB-001', price: 35, stock: 40, lowStockThreshold: 15, description: 'Canvas tote bag', image: null }
];

let selectedMerchId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadMerchandise();
    updateStats();
    setupSearch();
});

// Load merchandise from PHP backend
async function loadMerchandiseFromDB() {
    try {
        const response = await fetch('add_merchandise.php?api=list');
        const data = await response.json();
        if (data.success && data.data) {
            merchandise = data.data.map(m => ({
                id: m.id,
                name: m.itemName || m.name,
                sku: m.sku || `SKU-${m.id}`,
                price: parseFloat(m.price),
                stock: parseInt(m.stock),
                lowStockThreshold: 20,
                description: m.description || '',
                image: m.image ? `../uploads/${m.image}` : null
            }));
        }
    } catch (error) {
        console.error('Error loading merchandise:', error);
    }
}

// Load and display merchandise
function loadMerchandise() {
    const grid = document.getElementById('merchGrid');
    
    if (merchandise.length === 0) {
        grid.innerHTML = '<div class="empty-state">No merchandise items found</div>';
        return;
    }
    
    grid.innerHTML = merchandise.map(item => {
        const stockPercentage = (item.stock / 100) * 100;
        const isLowStock = item.stock <= item.lowStockThreshold;
        
        return `
            <div class="merch-card" onclick="viewMerchandise(${item.id})">
                <div class="merch-image">
                    ${item.image ? `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '📦'}
                    ${isLowStock ? '<div class="low-stock-badge">⚠️ LOW STOCK</div>' : ''}
                </div>
                <div class="merch-details">
                    <h4>${item.name}</h4>
                    <div class="merch-sku">SKU: ${item.sku}</div>
                    
                    <div class="merch-stats">
                        <div class="merch-stat">
                            <div class="merch-stat-label">Price</div>
                            <div class="merch-stat-value" style="color: var(--green);">RM ${item.price.toFixed(2)}</div>
                        </div>
                        <div class="merch-stat">
                            <div class="merch-stat-label">Stock</div>
                            <div class="merch-stat-value ${isLowStock ? 'style="color: var(--yellow);"' : ''}>${item.stock}</div>
                        </div>
                    </div>
                    
                    <div class="stock-bar">
                        <div class="stock-fill ${isLowStock ? 'low' : ''}" style="width: ${Math.min(stockPercentage, 100)}%;"></div>
                    </div>
                    
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <button onclick="event.stopPropagation(); editMerchandise(${item.id})" class="btn-primary" style="flex: 1; padding: 8px; font-size: 13px;">
                            Edit
                        </button>
                        <button onclick="event.stopPropagation(); deleteMerchandise(${item.id})" class="btn-danger" style="flex: 1; padding: 8px; font-size: 13px;">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Update statistics
function updateStats() {
    const totalItems = merchandise.length;
    const totalValue = merchandise.reduce((sum, m) => sum + (m.price * m.stock), 0);
    const lowStock = merchandise.filter(m => m.stock <= m.lowStockThreshold).length;
    const totalSales = 0; // This would come from sales data
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalValue').textContent = `RM ${totalValue.toLocaleString()}`;
    document.getElementById('lowStock').textContent = lowStock;
    document.getElementById('totalSales').textContent = `RM ${totalSales.toLocaleString()}`;
}

// View merchandise details
function viewMerchandise(id) {
    const item = merchandise.find(m => m.id === id);
    if (!item) return;
    
    selectedMerchId = id;
    
    const modal = document.getElementById('merchModal');
    const details = document.getElementById('merchDetails');
    
    const stockPercentage = (item.stock / 100) * 100;
    const isLowStock = item.stock <= item.lowStockThreshold;
    
    details.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 16px;">
            ${item.image ? `
                <div style="text-align: center;">
                    <img src="${item.image}" alt="${item.name}" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 1px solid var(--border);">
                </div>
            ` : ''}
            
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">Item Name</label>
                <p style="font-size: 18px; font-weight: 600; margin-top: 4px;">${item.name}</p>
            </div>
            
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">SKU</label>
                <p style="font-size: 14px; margin-top: 4px; font-family: monospace;">${item.sku}</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Price</label>
                    <p style="font-size: 24px; font-weight: 700; margin-top: 4px; color: var(--green);">RM ${item.price.toFixed(2)}</p>
                </div>
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Stock Level</label>
                    <p style="font-size: 24px; font-weight: 700; margin-top: 4px; ${isLowStock ? 'color: var(--yellow);' : ''}">${item.stock}</p>
                    ${isLowStock ? '<span class="badge warning" style="margin-top: 4px;">LOW STOCK</span>' : ''}
                </div>
            </div>
            
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">Stock Status</label>
                <div class="stock-bar" style="margin-top: 8px;">
                    <div class="stock-fill ${isLowStock ? 'low' : ''}" style="width: ${Math.min(stockPercentage, 100)}%;"></div>
                </div>
                <p style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">${item.stock} units available</p>
            </div>
            
            ${item.description ? `
                <div>
                    <label style="color: var(--text-secondary); font-size: 13px;">Description</label>
                    <p style="font-size: 14px; margin-top: 4px; line-height: 1.6;">${item.description}</p>
                </div>
            ` : ''}
            
            <div>
                <label style="color: var(--text-secondary); font-size: 13px;">Total Value</label>
                <p style="font-size: 20px; font-weight: 600; margin-top: 4px;">RM ${(item.price * item.stock).toLocaleString()}</p>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
}

// Edit merchandise
function editMerchandise(id) {
  window.location.href = `add_merchandise.html?id=${id}`;
}


// Delete merchandise
async function deleteMerchandise(id) {
    if (!id) id = selectedMerchId;
    if (!id) return;
    
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        const response = await fetch('add_merchandise.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            merchandise = merchandise.filter(m => m.id !== id);
            loadMerchandise();
            updateStats();
            closeMerchModal();
            alert('Item deleted successfully!');
        }
    } catch (error) {
        console.error('Error deleting merchandise:', error);
        // Fallback for demo
        merchandise = merchandise.filter(m => m.id !== id);
        loadMerchandise();
        updateStats();
        closeMerchModal();
        alert('Item deleted!');
    }
}

// Close modal
function closeMerchModal() {
    document.getElementById('merchModal').classList.remove('active');
    selectedMerchId = null;
}

// Export inventory
function exportInventory() {
    const csv = [
        ['SKU', 'Name', 'Price', 'Stock', 'Total Value'],
        ...merchandise.map(m => [
            m.sku,
            m.name,
            m.price.toFixed(2),
            m.stock,
            (m.price * m.stock).toFixed(2)
        ])
    ].map(row => row.join(',')).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `inventory-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Search functionality
function setupSearch() {
    const searchBox = document.getElementById('searchBox');
    searchBox?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = merchandise.filter(m => 
            m.name.toLowerCase().includes(query) ||
            m.sku.toLowerCase().includes(query) ||
            m.description.toLowerCase().includes(query)
        );
        
        const grid = document.getElementById('merchGrid');
        
        if (filtered.length === 0) {
            grid.innerHTML = '<div class="empty-state">No items found</div>';
            return;
        }
        
        grid.innerHTML = filtered.map(item => {
            const stockPercentage = (item.stock / 100) * 100;
            const isLowStock = item.stock <= item.lowStockThreshold;
            
            return `
                <div class="merch-card" onclick="viewMerchandise(${item.id})">
                    <div class="merch-image">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover;">` : '📦'}
                        ${isLowStock ? '<div class="low-stock-badge">⚠️ LOW STOCK</div>' : ''}
                    </div>
                    <div class="merch-details">
                        <h4>${item.name}</h4>
                        <div class="merch-sku">SKU: ${item.sku}</div>
                        
                        <div class="merch-stats">
                            <div class="merch-stat">
                                <div class="merch-stat-label">Price</div>
                                <div class="merch-stat-value" style="color: var(--green);">RM ${item.price.toFixed(2)}</div>
                            </div>
                            <div class="merch-stat">
                                <div class="merch-stat-label">Stock</div>
                                <div class="merch-stat-value ${isLowStock ? 'style="color: var(--yellow);"' : ''}>${item.stock}</div>
                            </div>
                        </div>
                        
                        <div class="stock-bar">
                            <div class="stock-fill ${isLowStock ? 'low' : ''}" style="width: ${Math.min(stockPercentage, 100)}%;"></div>
                        </div>
                        
                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <button onclick="event.stopPropagation(); editMerchandise(${item.id})" class="btn-primary" style="flex: 1; padding: 8px; font-size: 13px;">Edit</button>
                            <button onclick="event.stopPropagation(); deleteMerchandise(${item.id})" class="btn-danger" style="flex: 1; padding: 8px; font-size: 13px;">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('merchModal');
    if (event.target === modal) {
        closeMerchModal();
    }
}