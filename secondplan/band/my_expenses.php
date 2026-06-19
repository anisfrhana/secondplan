<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$stmt = $pdo->prepare("
    SELECT * FROM expenses
    WHERE submitted_by = ?
    ORDER BY created_at DESC
");
$stmt->execute([getUserId()]);
$expenses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Expenses - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h2>My Expenses</h2>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if (empty($expenses)): ?>
                <div class="empty-state">No expenses submitted yet.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $exp): ?>
                            <tr>
                                <td><?= e($exp['category']) ?></td>
                                <td><?= e($exp['description'] ?? '-') ?></td>
                                <td><?= formatMoney($exp['amount']) ?></td>
                                <td><?= formatDate($exp['expense_date']) ?></td>
                                <td><span class="badge status-<?= $exp['status'] ?>"><?= ucfirst($exp['status']) ?></span></td>
                                <td>
                                    <?php if (!empty($exp['receipt'])): ?>
                                        <button class="btn-secondary btn-small" onclick="viewReceipt('<?= e($exp['receipt']) ?>')">
                                            <i class="bi bi-eye btn-icon"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);font-size:13px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>
</div>

<div class="modal" id="receiptModal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>Receipt</h3>
            <button class="close-btn" onclick="closeReceiptModal()">&times;</button>
        </div>
        <div class="modal-body" id="receiptModalBody" style="text-align:center;"></div>
    </div>
</div>

<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
function viewReceipt(filename) {
    var body = document.getElementById('receiptModalBody');
    var url = '../uploads/' + filename;
    var ext = filename.split('.').pop().toLowerCase();

    if (ext === 'pdf') {
        body.innerHTML = '<iframe src="' + url + '" style="width:100%;height:60vh;border:none;border-radius:8px;"></iframe>';
    } else {
        body.innerHTML = '<img src="' + url + '" style="max-width:100%;max-height:60vh;border-radius:8px;" alt="Receipt">';
    }

    var modal = document.getElementById('receiptModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeReceiptModal() {
    var modal = document.getElementById('receiptModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
    document.getElementById('receiptModalBody').innerHTML = '';
}

window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('receiptModal')) closeReceiptModal();
});
</script>
</body>
</html>
