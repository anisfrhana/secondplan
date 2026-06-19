<?php
if (!defined('BASE_PATH')) {
    exit('Direct access not permitted');
}

function sendEmail($to, $subject, $body, $isHtml = true) {
    if (APP_ENV === 'development') {
        return logEmailDev($to, $subject, $body);
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = $isHtml ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: text/plain; charset=UTF-8';
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $success = @mail($to, $subject, $body, implode("\r\n", $headers));

    if ($success) {
        logEmailSent($to, $subject);
    } else {
        error_log("Email failed to send to: $to, subject: $subject");
    }

    return $success;
}

function logEmailDev($to, $subject, $body) {
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/emails.log';
    $entry = "\n" . str_repeat('=', 60) . "\n";
    $entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $entry .= "To: $to\n";
    $entry .= "Subject: $subject\n";
    $entry .= str_repeat('-', 60) . "\n";
    $entry .= strip_tags($body) . "\n";

    file_put_contents($logFile, $entry, FILE_APPEND);
    return true;
}

function logEmailSent($to, $subject) {
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/emails_sent.log';
    $entry = date('Y-m-d H:i:s') . " | To: $to | Subject: $subject\n";
    file_put_contents($logFile, $entry, FILE_APPEND);
}

function getEmailTemplate($template, $data = []) {
    extract($data);

    $header = '
    <div style="background:#f8f5f0;padding:40px 20px;font-family:system-ui,-apple-system,sans-serif;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="background:#1c1917;padding:24px 32px;">
                <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">' . e(APP_NAME) . '</h1>
            </div>
            <div style="padding:32px;">';

    $footer = '
            </div>
            <div style="background:#f8f5f0;padding:24px 32px;text-align:center;font-size:13px;color:#6b7280;">
                <p style="margin:0;">&copy; ' . date('Y') . ' ' . e(APP_NAME) . '. All rights reserved.</p>
                <p style="margin:8px 0 0;"><a href="' . APP_URL . '" style="color:#DC2626;text-decoration:none;">Visit our website</a></p>
            </div>
        </div>
    </div>';

    $content = '';

    switch ($template) {
        case 'password_reset':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Password Reset Request</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">We received a request to reset your password. Click the button below to create a new password:</p>
                <div style="text-align:center;margin:32px 0;">
                    <a href="' . e($resetLink) . '" style="display:inline-block;background:#DC2626;color:#ffffff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Reset Password</a>
                </div>
                <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">This link will expire in 1 hour.</p>
                <p style="margin:0;color:#6b7280;font-size:14px;">If you didn\'t request this, please ignore this email.</p>';
            break;

        case 'booking_submitted':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Booking Request Received</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">Thank you for your booking request. We have received your inquiry and will review it shortly.</p>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <h3 style="margin:0 0 16px;color:#1e1e1e;font-size:16px;">Booking Details</h3>
                    <table style="width:100%;font-size:14px;color:#4b5563;">
                        <tr><td style="padding:6px 0;"><strong>Reference:</strong></td><td>' . e($quotationNumber) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Event:</strong></td><td>' . e($eventName) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Date:</strong></td><td>' . e($eventDate) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Location:</strong></td><td>' . e($location) . '</td></tr>
                    </table>
                </div>
                <p style="margin:0;color:#6b7280;font-size:14px;">We will contact you within 1-2 business days with a quote.</p>';
            break;

        case 'booking_approved':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Booking Approved!</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">Great news! Your booking request has been approved.</p>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <h3 style="margin:0 0 16px;color:#1e1e1e;font-size:16px;">Invoice Details</h3>
                    <table style="width:100%;font-size:14px;color:#4b5563;">
                        <tr><td style="padding:6px 0;"><strong>Invoice:</strong></td><td>' . e($invoiceNumber) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Event:</strong></td><td>' . e($eventName) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Date:</strong></td><td>' . e($eventDate) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Amount:</strong></td><td style="font-size:18px;font-weight:700;color:#DC2626;">RM ' . number_format($amount, 2) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Due Date:</strong></td><td>' . e($dueDate) . '</td></tr>
                    </table>
                </div>
                <div style="text-align:center;margin:32px 0;">
                    <a href="' . APP_URL . '/user/bookings.php" style="display:inline-block;background:#DC2626;color:#ffffff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;">View Booking</a>
                </div>';
            break;

        case 'booking_rejected':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Booking Update</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">We regret to inform you that your booking request could not be approved at this time.</p>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <table style="width:100%;font-size:14px;color:#4b5563;">
                        <tr><td style="padding:6px 0;"><strong>Reference:</strong></td><td>' . e($quotationNumber) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Event:</strong></td><td>' . e($eventName) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Date:</strong></td><td>' . e($eventDate) . '</td></tr>
                    </table>
                </div>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">If you have any questions, please don\'t hesitate to contact us.</p>';
            break;

        case 'payment_confirmed':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Payment Confirmed</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">We have received your payment. Thank you!</p>
                <div style="background:#dcfce7;border:1px solid #22c55e;border-radius:8px;padding:20px;margin:24px 0;text-align:center;">
                    <div style="font-size:14px;color:#166534;">Payment Received</div>
                    <div style="font-size:28px;font-weight:700;color:#166534;margin-top:8px;">RM ' . number_format($amount, 2) . '</div>
                </div>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <table style="width:100%;font-size:14px;color:#4b5563;">
                        <tr><td style="padding:6px 0;"><strong>Invoice:</strong></td><td>' . e($invoiceNumber) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Event:</strong></td><td>' . e($eventName) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Date:</strong></td><td>' . e($eventDate) . '</td></tr>
                        <tr><td style="padding:6px 0;"><strong>Paid On:</strong></td><td>' . e($paidDate) . '</td></tr>
                    </table>
                </div>
                <p style="margin:0;color:#6b7280;font-size:14px;">We look forward to seeing you at your event!</p>';
            break;

        case 'order_confirmed':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">Order Confirmed</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">Thank you for your order! Here are your order details:</p>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <h3 style="margin:0 0 16px;color:#1e1e1e;font-size:16px;">Order #' . e($orderNumber) . '</h3>
                    ' . $itemsHtml . '
                    <div style="border-top:1px solid #e5e7eb;margin-top:16px;padding-top:16px;text-align:right;">
                        <span style="font-size:18px;font-weight:700;color:#1e1e1e;">Total: RM ' . number_format($total, 2) . '</span>
                    </div>
                </div>
                <div style="text-align:center;margin:32px 0;">
                    <a href="' . APP_URL . '/user/orders.php" style="display:inline-block;background:#DC2626;color:#ffffff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;">View Order</a>
                </div>';
            break;

        case 'task_assigned':
            $content = '
                <h2 style="margin:0 0 16px;color:#1e1e1e;font-size:20px;">New Task Assigned</h2>
                <p style="margin:0 0 16px;color:#4b5563;line-height:1.6;">Hi ' . e($name) . ',</p>
                <p style="margin:0 0 24px;color:#4b5563;line-height:1.6;">You have been assigned a new task:</p>
                <div style="background:#f8f5f0;border-radius:8px;padding:20px;margin:24px 0;">
                    <h3 style="margin:0 0 12px;color:#1e1e1e;font-size:18px;">' . e($taskTitle) . '</h3>
                    ' . ($taskDescription ? '<p style="margin:0 0 16px;color:#4b5563;font-size:14px;">' . e($taskDescription) . '</p>' : '') . '
                    <table style="width:100%;font-size:14px;color:#4b5563;">
                        <tr><td style="padding:6px 0;"><strong>Priority:</strong></td><td><span style="background:' . ($priority === 'urgent' ? '#fecaca' : ($priority === 'high' ? '#fed7aa' : '#e5e7eb')) . ';padding:4px 12px;border-radius:4px;font-size:12px;text-transform:uppercase;">' . e($priority) . '</span></td></tr>
                        <tr><td style="padding:6px 0;"><strong>Due Date:</strong></td><td>' . e($dueDate) . '</td></tr>
                    </table>
                </div>
                <div style="text-align:center;margin:32px 0;">
                    <a href="' . APP_URL . '/band/tasks.php" style="display:inline-block;background:#DC2626;color:#ffffff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;">View Task</a>
                </div>';
            break;

        default:
            $content = '<p style="color:#4b5563;">' . nl2br(e($body ?? '')) . '</p>';
    }

    return $header . $content . $footer;
}

function sendPasswordResetEmail($email, $name, $resetLink) {
    $subject = 'Reset Your Password - ' . APP_NAME;
    $body = getEmailTemplate('password_reset', [
        'name' => $name,
        'resetLink' => $resetLink
    ]);
    return sendEmail($email, $subject, $body);
}

function sendBookingSubmittedEmail($email, $name, $quotationNumber, $eventName, $eventDate, $location) {
    $subject = 'Booking Request Received - ' . $quotationNumber;
    $body = getEmailTemplate('booking_submitted', [
        'name' => $name,
        'quotationNumber' => $quotationNumber,
        'eventName' => $eventName,
        'eventDate' => $eventDate,
        'location' => $location
    ]);
    return sendEmail($email, $subject, $body);
}

function sendBookingApprovedEmail($email, $name, $invoiceNumber, $eventName, $eventDate, $amount, $dueDate) {
    $subject = 'Booking Approved - ' . $invoiceNumber;
    $body = getEmailTemplate('booking_approved', [
        'name' => $name,
        'invoiceNumber' => $invoiceNumber,
        'eventName' => $eventName,
        'eventDate' => $eventDate,
        'amount' => $amount,
        'dueDate' => $dueDate
    ]);
    return sendEmail($email, $subject, $body);
}

function sendBookingRejectedEmail($email, $name, $quotationNumber, $eventName, $eventDate) {
    $subject = 'Booking Update - ' . $quotationNumber;
    $body = getEmailTemplate('booking_rejected', [
        'name' => $name,
        'quotationNumber' => $quotationNumber,
        'eventName' => $eventName,
        'eventDate' => $eventDate
    ]);
    return sendEmail($email, $subject, $body);
}

function sendPaymentConfirmedEmail($email, $name, $invoiceNumber, $eventName, $eventDate, $amount, $paidDate) {
    $subject = 'Payment Confirmed - ' . $invoiceNumber;
    $body = getEmailTemplate('payment_confirmed', [
        'name' => $name,
        'invoiceNumber' => $invoiceNumber,
        'eventName' => $eventName,
        'eventDate' => $eventDate,
        'amount' => $amount,
        'paidDate' => $paidDate
    ]);
    return sendEmail($email, $subject, $body);
}

function sendOrderConfirmedEmail($email, $name, $orderNumber, $items, $total) {
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e5e7eb;">';
        $itemsHtml .= '<span>' . e($item['name']) . ' x' . $item['quantity'] . '</span>';
        $itemsHtml .= '<span>RM ' . number_format($item['subtotal'], 2) . '</span>';
        $itemsHtml .= '</div>';
    }

    $subject = 'Order Confirmed - #' . $orderNumber;
    $body = getEmailTemplate('order_confirmed', [
        'name' => $name,
        'orderNumber' => $orderNumber,
        'itemsHtml' => $itemsHtml,
        'total' => $total
    ]);
    return sendEmail($email, $subject, $body);
}

function sendTaskAssignedEmail($email, $name, $taskTitle, $taskDescription, $priority, $dueDate) {
    $subject = 'New Task Assigned: ' . $taskTitle;
    $body = getEmailTemplate('task_assigned', [
        'name' => $name,
        'taskTitle' => $taskTitle,
        'taskDescription' => $taskDescription,
        'priority' => $priority,
        'dueDate' => $dueDate
    ]);
    return sendEmail($email, $subject, $body);
}
