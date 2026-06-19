<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    $sql = "SELECT event_id, title, date, start_time, end_time, venue, status FROM events WHERE status = 'scheduled'";
    $params = [];

    if ($start) {
        $sql .= " AND date >= ?";
        $params[] = $start;
    }
    if ($end) {
        $sql .= " AND date <= ?";
        $params[] = $end;
    }

    $sql .= " ORDER BY date ASC, start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    $calendarEvents = [];
    foreach ($events as $evt) {
        $start = $evt['start_time'] ? $evt['date'] . 'T' . $evt['start_time'] : $evt['date'];
        $end = $evt['end_time'] ? $evt['date'] . 'T' . $evt['end_time'] : null;
        $entry = [
            'id' => $evt['event_id'],
            'title' => $evt['title'],
            'start' => $start,
            'extendedProps' => [
                'type' => 'event',
                'venue' => $evt['venue'],
                'status' => $evt['status'],
            ],
        ];
        if ($end) {
            $entry['end'] = $end;
        }
        $calendarEvents[] = $entry;
    }

    header('Content-Type: application/json');
    echo json_encode($calendarEvents);
    exit;
}
