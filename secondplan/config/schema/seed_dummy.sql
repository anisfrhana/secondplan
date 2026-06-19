SET @admin_id = (SELECT user_id FROM users WHERE email = 'admin@secondplan.local');
SET @ameer_id = (SELECT user_id FROM users WHERE email = 'ameer@secondplan.local');
SET @zimi_id = (SELECT user_id FROM users WHERE email = 'zimi@secondplan.local');
SET @fairuz_id = (SELECT user_id FROM users WHERE email = 'fairuz@secondplan.local');
SET @one_id = (SELECT user_id FROM users WHERE email = 'one@secondplan.local');

INSERT INTO `events` (`title`, `description`, `date`, `start_time`, `end_time`, `venue`, `location`, `capacity`, `seats_booked`, `price`, `status`, `created_by`) VALUES
('Jazz Night at The Bee', 'An evening of smooth jazz and original compositions', '2026-02-28', '20:00:00', '23:00:00', 'The Bee Publika', 'Kuala Lumpur', 150, 0, 50.00, 'scheduled', @admin_id),
('Corporate Dinner - Petronas', 'Live band performance for Petronas annual dinner', '2026-03-15', '19:00:00', '22:30:00', 'Mandarin Oriental KL', 'Kuala Lumpur', 300, 0, NULL, 'scheduled', @admin_id),
('Wedding Reception - Alia & Farhan', 'Full set performance for wedding reception', '2026-03-22', '20:00:00', '23:30:00', 'The Majestic Hotel', 'Kuala Lumpur', 200, 0, NULL, 'scheduled', @admin_id),
('Urbanscapes Festival 2026', 'Outdoor festival performance - Main Stage', '2026-04-12', '17:00:00', '18:30:00', 'The Gasket Alley', 'Petaling Jaya', 500, 0, 80.00, 'scheduled', @admin_id),
('Acoustic Session at Merdekarya', 'Intimate stripped-down acoustic set', '2026-01-18', '21:00:00', '23:00:00', 'Merdekarya', 'Kuala Lumpur', 80, 65, 30.00, 'completed', @admin_id);

SET @evt1 = (SELECT event_id FROM events WHERE title = 'Jazz Night at The Bee');
SET @evt2 = (SELECT event_id FROM events WHERE title = 'Corporate Dinner - Petronas');
SET @evt3 = (SELECT event_id FROM events WHERE title = 'Wedding Reception - Alia & Farhan');
SET @evt4 = (SELECT event_id FROM events WHERE title = 'Urbanscapes Festival 2026');
SET @evt5 = (SELECT event_id FROM events WHERE title = 'Acoustic Session at Merdekarya');

SET @cust1 = (SELECT user_id FROM users WHERE email = 'band@secondplan.local');
SET @cust2 = (SELECT user_id FROM users WHERE email = 'user@secondplan.local');
SET @cust3 = (SELECT user_id FROM users WHERE email = 'syafiqa@gmail.com');

INSERT INTO `bookings` (`user_id`, `event_id`, `company_name`, `event_name`, `event_date`, `location`, `price`, `status`) VALUES
(@cust1, @evt1, 'Anis Events Sdn Bhd', 'Private Jazz Night', '2026-02-28', 'Kuala Lumpur', 3500.00, 'approved'),
(@cust2, @evt2, 'Petronas Carigali', 'Annual Corporate Dinner', '2026-03-15', 'Kuala Lumpur', 8000.00, 'approved'),
(@cust3, NULL, 'Alia & Farhan', 'Wedding Reception Live Band', '2026-03-22', 'Kuala Lumpur', 5000.00, 'pending'),
(@cust1, @evt4, 'Urbanscapes Organizer', 'Festival Main Stage Slot', '2026-04-12', 'Petaling Jaya', 6000.00, 'pending'),
(@cust2, NULL, 'Taylor University', 'University Convocation Dinner', '2026-05-10', 'Subang Jaya', 4500.00, 'rejected');

INSERT INTO `tasks` (`title`, `description`, `assigned_to`, `assigned_by`, `event_id`, `priority`, `status`, `due_date`) VALUES
('Prepare setlist for Jazz Night', 'Create and finalize the setlist for The Bee performance. Include 3 original songs and 7 covers.', @ameer_id, @admin_id, @evt1, 'high', 'in_progress', '2026-02-25'),
('Equipment check for Petronas event', 'Verify all PA system, monitors, and instruments are ready. Coordinate with venue sound engineer.', @fairuz_id, @admin_id, @evt2, 'urgent', 'todo', '2026-03-10'),
('Rehearsal for wedding set', 'Practice wedding-appropriate songs. Focus on romantic ballads and dinner music.', @zimi_id, @admin_id, @evt3, 'medium', 'todo', '2026-03-18'),
('Update social media for Urbanscapes', 'Post announcement graphics on Instagram and Facebook. Tag @urbanscapes.', @one_id, @admin_id, @evt4, 'low', 'completed', '2026-02-01'),
('Record demo for new single', 'Track guitar and vocals for upcoming single at studio session.', @ameer_id, @admin_id, NULL, 'medium', 'in_progress', '2026-03-01');

INSERT INTO `expenses` (`category`, `amount`, `expense_date`, `vendor`, `reference`, `description`, `status`, `submitted_by`, `event_id`) VALUES
('Equipment', 250.00, '2026-01-20', 'Guitar Store KL', 'INV-2026-001', 'New guitar strings and picks for upcoming shows', 'approved', @zimi_id, NULL),
('Transport', 180.00, '2026-01-25', 'Grab Malaysia', 'GRB-88291', 'Transport to Merdekarya venue and back with equipment', 'approved', @fairuz_id, @evt5),
('Rental', 500.00, '2026-02-10', 'ProSound Rental', 'PSR-0210', 'PA system rental for Jazz Night rehearsal', 'pending', @ameer_id, @evt1),
('Marketing', 350.00, '2026-02-05', 'PrintHub Sdn Bhd', 'PH-4422', 'Poster and flyer printing for Urbanscapes promo', 'pending', @one_id, @evt4),
('Food', 120.00, '2026-01-18', 'Nasi Kandar Pelita', 'CASH', 'Team dinner after Merdekarya acoustic session', 'rejected', @ameer_id, @evt5);

INSERT INTO `merchandise` (`name`, `sku`, `description`, `price`, `cost`, `stock`, `low_stock_threshold`, `category`, `status`) VALUES
('SecondPlan Logo Tee - Black', 'SP-TEE-BLK', 'Premium cotton tee with SecondPlan logo print', 69.90, 25.00, 50, 10, 'Apparel', 'active'),
('SecondPlan Hoodie - Navy', 'SP-HOOD-NVY', 'Heavyweight hoodie with embroidered logo', 149.90, 55.00, 30, 5, 'Apparel', 'active'),
('SecondPlan Cap - White', 'SP-CAP-WHT', 'Adjustable snapback cap with embroidered logo', 49.90, 15.00, 40, 10, 'Accessories', 'active'),
('Live at Merdekarya EP (Digital)', 'SP-EP-LIVE', 'Digital download of 5-track live EP recorded at Merdekarya', 19.90, 0.00, 999, 1, 'Music', 'active'),
('SecondPlan Sticker Pack', 'SP-STICKER', 'Set of 5 die-cut vinyl stickers', 12.90, 3.00, 100, 20, 'Accessories', 'active');

SET @merch1 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-TEE-BLK');
SET @merch2 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-HOOD-NVY');
SET @merch3 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-CAP-WHT');
SET @merch4 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-EP-LIVE');
SET @merch5 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-STICKER');

INSERT INTO `orders` (`user_id`, `order_number`, `total_amount`, `status`, `payment_status`, `payment_method`, `shipping_address`) VALUES
(@cust1, 'SP-20260201-A1B2C3D4', 219.80, 'delivered', 'paid', 'Online Banking', '12 Jalan SS15/4, 47500 Subang Jaya, Selangor'),
(@cust2, 'SP-20260203-E5F6G7H8', 69.90, 'shipped', 'paid', 'Credit Card', '45 Persiaran KLCC, 50088 Kuala Lumpur'),
(@cust3, 'SP-20260205-I9J0K1L2', 182.70, 'processing', 'paid', 'FPX', '8 Jalan Bukit Bintang, 55100 Kuala Lumpur'),
(@cust1, 'SP-20260206-M3N4O5P6', 19.90, 'pending', 'unpaid', NULL, '12 Jalan SS15/4, 47500 Subang Jaya, Selangor'),
(@cust2, 'SP-20260207-Q7R8S9T0', 62.80, 'cancelled', 'refunded', 'Online Banking', '45 Persiaran KLCC, 50088 Kuala Lumpur');

SET @ord1 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260201-A1B2C3D4');
SET @ord2 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260203-E5F6G7H8');
SET @ord3 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260205-I9J0K1L2');
SET @ord4 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260206-M3N4O5P6');
SET @ord5 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260207-Q7R8S9T0');

INSERT INTO `order_items` (`order_id`, `merch_id`, `quantity`, `price`, `subtotal`) VALUES
(@ord1, @merch1, 2, 69.90, 139.80),
(@ord1, @merch3, 1, 49.90, 49.90),
(@ord1, @merch4, 1, 19.90, 19.90),
(@ord2, @merch1, 1, 69.90, 69.90),
(@ord3, @merch2, 1, 149.90, 149.90),
(@ord3, @merch5, 1, 12.90, 12.90),
(@ord3, @merch4, 1, 19.90, 19.90),
(@ord4, @merch4, 1, 19.90, 19.90),
(@ord5, @merch3, 1, 49.90, 49.90),
(@ord5, @merch5, 1, 12.90, 12.90);
