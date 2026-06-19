<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'count') {
        jsonSuccess('OK', ['count' => getUnreadNotificationCount($userId)]);
    }

    $limit = max(1, min((int)($_GET['limit'] ?? 20), 50));
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    jsonSuccess('OK', $stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf'] ?? '')) {
        jsonError('Invalid request', 403);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?")
                ->execute([$notificationId, $userId]);
        }
        jsonSuccess('Marked as read');
    }

    if ($action === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
            ->execute([$userId]);
        jsonSuccess('All marked as read');
    }

    jsonError('Invalid action');
}
