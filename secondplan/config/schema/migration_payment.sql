ALTER TABLE bookings
  ADD COLUMN payment_status ENUM('unpaid','paid') DEFAULT 'unpaid' AFTER invoice_number,
  ADD COLUMN payment_due_date DATE NULL AFTER payment_status,
  ADD COLUMN paid_at DATETIME NULL AFTER payment_due_date;
