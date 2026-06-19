<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role([ROLE_MEMBER, ROLE_BAND, 'band_member']);

$flash = getFlash();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $category = sanitize($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? '';
    $vendor = sanitize($_POST['vendor'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($category)) $errors[] = 'Category is required';
    if ($amount <= 0) $errors[] = 'Amount must be greater than zero';
    if (empty($expense_date)) $errors[] = 'Date is required';

    $receiptFilename = null;
    if (!empty($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['receipt']);
        if ($upload['success']) {
            $receiptFilename = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO expenses (category, amount, expense_date, vendor, description, receipt, submitted_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$category, $amount, $expense_date, $vendor, $description, $receiptFilename, getUserId()]);

        $admins = $pdo->query("
            SELECT u.user_id FROM users u
            JOIN user_roles ur ON ur.user_id = u.user_id
            JOIN roles r ON r.role_id = ur.role_id
            WHERE r.role_name = 'admin'
        ")->fetchAll();
        foreach ($admins as $admin) {
            createNotification(
                $admin['user_id'],
                'expense_submitted',
                'New Expense Submitted',
                getUserData()['name'] . ' submitted an expense of ' . formatMoney($amount),
                '/admin/expenses.php'
            );
        }

        setFlash('success', 'Expense submitted successfully.');
        redirect('/band/my_expenses.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Expense - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/band.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
            <h2>Submit Expense</h2>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar"><?= strtoupper(substr(getUserData()['name'] ?? 'M', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="section">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select category</option>
                            <option value="transport">Transport</option>
                            <option value="equipment">Equipment</option>
                            <option value="food">Food & Beverage</option>
                            <option value="accommodation">Accommodation</option>
                            <option value="marketing">Marketing</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (RM)</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required value="<?= e($_POST['amount'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" required value="<?= e($_POST['expense_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="form-group">
                        <label>Vendor</label>
                        <input type="text" name="vendor" value="<?= e($_POST['vendor'] ?? '') ?>" placeholder="Store or service name">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Brief description of the expense"><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Receipt (optional)</label>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
                    </div>

                    <button type="submit" class="btn-primary"><i class="bi bi-send btn-icon"></i> Submit Expense</button>
                </form>
            </div>
        </main>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
