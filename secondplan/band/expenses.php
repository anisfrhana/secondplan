<?php
require_once __DIR__ . '/../config/config.php';
initSession();
requireLogin();
requireRole(ROLE_BAND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf'] ?? null)) {
        setFlash('error', 'CSRF token failed.');
        redirect('expenses.html');
    }

    $desc = sanitize($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if ($desc && $amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, description, amount, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([getUserId(), $desc, $amount]);

        setFlash('success', 'Expense added.');
        redirect('my_expenses.php');
    } else {
        setFlash('error', 'Invalid input.');
        redirect('expenses.html');
    }
}
?>

<!DOCTYPE html>

<html>
<head>
    <title>Add Expense - SecondPlan</title>
</head>

<h1>Add Expense</h1>
<form action="expenses.php" method="POST">
    <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">
    <label>Description</label>
    <input type="text" name="description" required>
    <label>Amount (RM)</label>
    <input type="number" step="0.01" name="amount" required>
    <button type="submit">Add Expense</button>
</form>
</html>