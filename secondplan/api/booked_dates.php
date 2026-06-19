<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

header('Content-Type: application/json');

try {
    $bookings = $pdo->query("
        SELECT event_date as start, event_name as title, status
        FROM bookings
        WHERE status IN ('pending', 'approved')
        AND event_date >= CURDATE()
    ")->fetchAll(PDO::FETCH_ASSOC);

    $events = $pdo->query("
        SELECT date as start, title, status
        FROM events
        WHERE status = 'scheduled'
        AND date >= CURDATE()
    ")->fetchAll(PDO::FETCH_ASSOC);

    $calendarEvents = [];

    foreach ($bookings as $b) {
        $calendarEvents[] = [
            'start' => $b['start'],
            'title' => $b['title'],
            'display' => 'background',
            'color' => $b['status'] === 'approved' ? '#ef4444' : '#f97316'
        ];
    }

    foreach ($events as $ev) {
        $calendarEvents[] = [
            'start' => $ev['start'],
            'title' => $ev['title'],
            'display' => 'background',
            'color' => '#f97316'
        ];
    }

    echo json_encode($calendarEvents);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([]);
}
