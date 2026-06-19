<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/booking.php');
}

verify_csrf();

$userId = getUserId();

$company = sanitize($_POST['company'] ?? '');
$title = sanitize($_POST['title'] ?? '');
$eventDate = $_POST['event_date'] ?? '';
$eventTime = $_POST['event_time'] ?? '';
$address = sanitize($_POST['address'] ?? '');
$postalCode = sanitize($_POST['postal_code'] ?? '');
$city = sanitize($_POST['city'] ?? '');
$state = sanitize($_POST['state'] ?? '');
$notes = sanitize($_POST['notes'] ?? '');
$quotationPrice = !empty($_POST['quotation_price']) ? (float)$_POST['quotation_price'] : null;

$errors = [];
if (empty($company)) $errors[] = 'Company name is required';
if (empty($title)) $errors[] = 'Event title is required';
if (empty($eventDate)) {
    $errors[] = 'Event date is required';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate) || strtotime($eventDate) === false) {
    $errors[] = 'Invalid event date format';
} elseif (strtotime($eventDate) < strtotime(date('Y-m-d'))) {
    $errors[] = 'Event date cannot be in the past';
}
if (empty($eventTime)) $errors[] = 'Event time is required';
if (empty($address)) $errors[] = 'Address is required';
if (empty($postalCode)) $errors[] = 'Postal code is required';
if (empty($city)) $errors[] = 'City is required';
if (empty($state)) $errors[] = 'State is required';

$posterFilename = null;
if (!empty($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadFile($_FILES['poster'], ALLOWED_IMAGE_TYPES);
    if ($upload['success']) {
        $posterFilename = $upload['filename'];
    } else {
        $errors[] = $upload['error'];
    }
}

if (!empty($errors)) {
    $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    if ($isJson) {
        jsonError(implode('; ', $errors));
    }
    setFlash('error', implode('. ', $errors));
    redirect('/user/booking.php');
}

$location = $address . ', ' . $city . ', ' . $state . ' ' . $postalCode;
$quotationNumber = generateQuotationNumber();

$stmt = $pdo->prepare("
    INSERT INTO bookings (user_id, company_name, event_name, event_date, event_time, location, address, postal_code, city, state, poster_event, notes, quotation_number, quotation_price, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([
    $userId, $company, $title, $eventDate, $eventTime,
    $location, $address, $postalCode, $city, $state,
    $posterFilename, $notes, $quotationNumber, $quotationPrice
]);

$bookingId = $pdo->lastInsertId();

$admins = $pdo->query("
    SELECT u.user_id FROM users u
    JOIN user_roles ur ON ur.user_id = u.user_id
    JOIN roles r ON r.role_id = ur.role_id
    WHERE r.role_name = 'admin'
")->fetchAll();

foreach ($admins as $admin) {
    $notifMsg = getUserData()['name'] . ' submitted a booking for "' . $title . '"';
    if ($quotationPrice) {
        $notifMsg .= ' | Budget: RM ' . number_format($quotationPrice, 2) . ' per day';
    }
    createNotification(
        $admin['user_id'],
        'booking_submitted',
        'New Booking Request',
        $notifMsg,
        '/admin/bookings.php'
    );
}

logActivity($userId, 'booking_created', ['booking_id' => $bookingId, 'event_name' => $title]);

$userData = getUserData();
sendBookingSubmittedEmail($userData['email'], $userData['name'], $quotationNumber, $title, $eventDate, $location);

$isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
if ($isJson) {
    jsonSuccess('Booking submitted', ['booking_id' => $bookingId, 'quotation_number' => $quotationNumber]);
}

setFlash('success', 'Booking submitted successfully! Your quotation reference is: ' . $quotationNumber);
redirect('/user/my_bookings.php');
