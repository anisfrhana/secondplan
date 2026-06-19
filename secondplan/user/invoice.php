<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$bookingId = (int)($_GET['id'] ?? 0);
if (!$bookingId) {
    redirect('/user/my_bookings.php');
}

$stmt = $pdo->prepare("
    SELECT b.*, u.name as client_name, u.email as client_email, u.phone as client_phone
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, getUserId()]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || empty($booking['invoice_number'])) {
    setFlash('error', 'Invoice not found or not yet generated.');
    redirect('/user/my_bookings.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($booking['invoice_number']) ?> - SecondPlan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #f8f5f0; color: #1a1a1a; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: #ffffff; border: 1px solid #e0d6c8; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .invoice-header { background: linear-gradient(135deg, #1c1917, #292524); padding: 40px; display: flex; justify-content: space-between; align-items: flex-start; }
        .invoice-brand h1 { font-size: 28px; font-weight: 700; color: #DC2626; }
        .invoice-brand p { color: #a8a29e; font-size: 13px; margin-top: 4px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 24px; font-weight: 700; color: #e7e5e4; margin-bottom: 8px; }
        .invoice-meta p { font-size: 13px; color: #a8a29e; line-height: 1.8; }
        .invoice-meta strong { color: #e7e5e4; }
        .invoice-body { padding: 40px; }
        .invoice-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .invoice-party h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #DC2626; margin-bottom: 12px; }
        .invoice-party p { font-size: 14px; line-height: 1.8; color: #6b7280; }
        .invoice-party strong { color: #1a1a1a; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th { text-align: left; padding: 12px 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; background: #f0ebe4; border-bottom: 1px solid #e0d6c8; }
        .invoice-table td { padding: 16px; border-bottom: 1px solid #f0ebe4; font-size: 14px; }
        .invoice-total { display: flex; justify-content: flex-end; }
        .invoice-total-box { background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.25); border-radius: 12px; padding: 20px 30px; text-align: right; }
        .invoice-total-box .label { font-size: 13px; color: #6b7280; }
        .invoice-total-box .amount { font-size: 32px; font-weight: 700; color: #B91C1C; margin-top: 4px; }
        .payment-info { margin-top: 20px; padding: 16px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; }
        .payment-info.unpaid { background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.25); }
        .payment-info.paid { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); }
        .payment-info .status-label { font-size: 14px; font-weight: 600; }
        .payment-info.unpaid .status-label { color: #B91C1C; }
        .payment-info.paid .status-label { color: #16a34a; }
        .payment-info .due-date { font-size: 13px; color: #6b7280; }
        .invoice-footer { padding: 24px 40px; border-top: 1px solid #e0d6c8; text-align: center; color: #9ca3af; font-size: 12px; }
        .print-actions { text-align: center; margin: 20px 0; }
        .print-actions button { padding: 10px 24px; background: linear-gradient(135deg, #DC2626, #B91C1C); border: none; border-radius: 8px; color: white; font-size: 14px; cursor: pointer; margin: 0 8px; }
        .print-actions .btn-back { background: #ffffff; border: 1px solid #e0d6c8; color: #1a1a1a; }
        @media print {
            body { background: white; color: #111; padding: 0; }
            .invoice-container { border: none; box-shadow: none; background: white; }
            .invoice-header { background: white; border-bottom: 2px solid #DC2626; }
            .invoice-brand h1 { color: #B91C1C; }
            .invoice-brand p, .invoice-meta p, .invoice-party p, .invoice-footer { color: #666; }
            .invoice-meta h2, .invoice-meta strong, .invoice-party strong { color: #111; }
            .invoice-body { padding: 30px 40px; }
            .invoice-table th { background: #f8f9fa; color: #333; border-bottom: 2px solid #ddd; }
            .invoice-table td { color: #333; border-bottom: 1px solid #eee; }
            .invoice-total-box { background: #fef2f2; border-color: #DC2626; }
            .invoice-total-box .label { color: #666; }
            .invoice-total-box .amount { color: #B91C1C; }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="downloadPDF()"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
        <button onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button class="btn-back" onclick="window.location.href='my_bookings.php'"><i class="bi bi-arrow-left"></i> Back</button>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-brand">
                <h1>SecondPlan</h1>
                <p>Event Management & Band Services</p>
            </div>
            <div class="invoice-meta">
                <h2>INVOICE</h2>
                <p>
                    <strong><?= e($booking['invoice_number']) ?></strong><br>
                    Date: <?= formatDate(date('Y-m-d')) ?><br>
                    Quotation Ref: <?= e($booking['quotation_number'] ?? '-') ?>
                </p>
            </div>
        </div>

        <div class="invoice-body">
            <div class="invoice-parties">
                <div class="invoice-party">
                    <h3>Bill To</h3>
                    <p>
                        <strong><?= e($booking['client_name']) ?></strong><br>
                        <?= e($booking['company_name'] ?? '') ?><br>
                        <?= e($booking['client_email'] ?? '') ?><br>
                        <?= e($booking['client_phone'] ?? '') ?>
                    </p>
                </div>
                <div class="invoice-party">
                    <h3>Event Details</h3>
                    <p>
                        <strong><?= e($booking['event_name']) ?></strong><br>
                        Date: <?= formatDate($booking['event_date']) ?><br>
                        Time: <?= $booking['event_time'] ? date('g:i A', strtotime($booking['event_time'])) : '-' ?><br>
                        Location: <?= e($booking['location'] ?? '-') ?>
                    </p>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Details</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Event Booking Service</strong></td>
                        <td><?= e($booking['event_name']) ?> - <?= formatDate($booking['event_date']) ?></td>
                        <td style="text-align:right;font-weight:600;"><?= formatMoney($booking['price']) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="invoice-total">
                <div class="invoice-total-box">
                    <div class="label">Total Amount</div>
                    <div class="amount"><?= formatMoney($booking['price']) ?></div>
                </div>
            </div>

            <div class="payment-info <?= ($booking['payment_status'] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid' ?>">
                <div>
                    <div class="status-label"><?= ($booking['payment_status'] ?? 'unpaid') === 'paid' ? 'PAID' : 'UNPAID' ?></div>
                    <?php if (($booking['payment_status'] ?? '') === 'paid' && !empty($booking['paid_at'])): ?>
                        <div class="due-date">Paid on: <?= formatDate($booking['paid_at']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($booking['payment_due_date'])): ?>
                    <div class="due-date">Payment Due: <?= formatDate($booking['payment_due_date']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (($booking['payment_status'] ?? 'unpaid') !== 'paid'): ?>
        <div style="padding:24px 40px;">
            <div style="background:#fffbeb;border:1px solid #fbbf24;border-radius:12px;padding:20px 24px;">
                <h3 style="margin:0 0 12px;font-size:15px;color:#92400e;"><i class="bi bi-bank" style="margin-right:6px;"></i> Payment Instructions</h3>
                <table style="font-size:14px;color:#78350f;line-height:2;">
                    <tr><td style="padding-right:16px;font-weight:600;">Bank</td><td>Maybank</td></tr>
                    <tr><td style="padding-right:16px;font-weight:600;">Account Name</td><td>Sofarz Manager</td></tr>
                    <tr><td style="padding-right:16px;font-weight:600;">Account Number</td><td>1234 5678 9012</td></tr>
                    <tr><td style="padding-right:16px;font-weight:600;">Reference</td><td><strong><?= e($booking['invoice_number']) ?></strong></td></tr>
                </table>
                <p style="margin:12px 0 0;font-size:12px;color:#a16207;">Please use the invoice number as your payment reference. Upload your receipt via My Bookings after payment.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="invoice-footer">
            SecondPlan Event Management | info@secondplan.local | Thank you for your business!
        </div>
    </div>

    <script>
    function downloadPDF() {
        var element = document.querySelector('.invoice-container');
        var invoiceNumber = '<?= e($booking['invoice_number']) ?>';
        var opt = {
            margin: 10,
            filename: invoiceNumber + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
</body>
</html>
