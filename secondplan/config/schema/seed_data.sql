SET @pwd = '$2y$10$1ywvWuYJBRP7PrfxVfUpvOOq.UPfgcT6Ij8HOLZVaimjUFjxO545G';

INSERT INTO `users` (`email`, `password_hash`, `name`, `phone`, `position`, `status`, `email_verified`) VALUES
('ameer@secondplan.local', @pwd, 'Amir Mahyidin', '0123456001', 'Vocalist', 'active', TRUE),
('zimi@secondplan.local', @pwd, 'Zarimi Zahari', '0123456002', 'Guitarist', 'active', TRUE),
('fairuz@secondplan.local', @pwd, 'Ahmad Fairouz Mohamad', '0123456003', 'Bassist', 'active', TRUE),
('one@secondplan.local', @pwd, 'Wan Shahbaharom Niktah', '0123456004', 'Drummer', 'active', TRUE),
('sarah@email.com', @pwd, 'Sarah Lim', '0191234567', NULL, 'active', TRUE),
('michael@email.com', @pwd, 'Michael Tan', '0177654321', NULL, 'active', TRUE),
('aina@email.com', @pwd, 'Aina Rashid', '0169876543', NULL, 'active', TRUE);

INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.user_id, r.role_id
FROM users u, roles r
WHERE u.email IN ('ameer@secondplan.local', 'zimi@secondplan.local', 'fairuz@secondplan.local', 'one@secondplan.local')
AND r.role_name = 'band_member';

INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.user_id, r.role_id
FROM users u, roles r
WHERE u.email IN ('sarah@email.com', 'michael@email.com', 'aina@email.com')
AND r.role_name = 'customer';

SET @admin_id = (SELECT user_id FROM users WHERE email = 'admin@secondplan.local');
SET @ameer_id = (SELECT user_id FROM users WHERE email = 'ameer@secondplan.local');
SET @zimi_id = (SELECT user_id FROM users WHERE email = 'zimi@secondplan.local');
SET @fairuz_id = (SELECT user_id FROM users WHERE email = 'fairuz@secondplan.local');
SET @one_id = (SELECT user_id FROM users WHERE email = 'one@secondplan.local');
SET @sarah_id = (SELECT user_id FROM users WHERE email = 'sarah@email.com');
SET @michael_id = (SELECT user_id FROM users WHERE email = 'michael@email.com');
SET @aina_id = (SELECT user_id FROM users WHERE email = 'aina@email.com');

INSERT INTO `events` (`title`, `description`, `date`, `start_time`, `end_time`, `venue`, `location`, `capacity`, `seats_booked`, `price`, `status`, `created_by`) VALUES
('Jazz Night at The Bee', 'An evening of smooth jazz and original compositions', '2026-02-28', '20:00:00', '23:00:00', 'The Bee Publika', 'Kuala Lumpur', 150, 0, 50.00, 'scheduled', @admin_id),
('Corporate Dinner - Petronas', 'Live band performance for Petronas annual dinner', '2026-03-15', '19:00:00', '22:30:00', 'Mandarin Oriental KL', 'Kuala Lumpur', 300, 0, NULL, 'scheduled', @admin_id),
('Wedding Reception - Alia & Farhan', 'Full set performance for wedding reception', '2026-03-22', '20:00:00', '23:30:00', 'The Majestic Hotel', 'Kuala Lumpur', 200, 0, NULL, 'scheduled', @admin_id),
('Urbanscapes Festival 2026', 'Outdoor festival performance - Main Stage', '2026-04-12', '17:00:00', '18:30:00', 'The Gasket Alley', 'Petaling Jaya', 500, 0, 80.00, 'scheduled', @admin_id),
('Penang International Jazz Fest', 'Featured act at PIJF 2026', '2026-04-25', '21:00:00', '22:30:00', 'Bayview Hotel', 'Georgetown, Penang', 400, 0, 120.00, 'scheduled', @admin_id),
('Private Party - CEO Birthday', 'Exclusive birthday celebration performance', '2026-05-03', '20:00:00', '23:00:00', 'The Club Bukit Utama', 'Petaling Jaya', 100, 0, NULL, 'scheduled', @admin_id),
('Artisan Market Live', 'Afternoon acoustic set at artisan weekend market', '2026-05-17', '14:00:00', '16:00:00', 'APW Bangsar', 'Kuala Lumpur', 200, 0, NULL, 'scheduled', @admin_id),
('Charity Concert for Education', 'Benefit concert raising funds for underprivileged students', '2026-06-01', '19:30:00', '22:00:00', 'Istana Budaya', 'Kuala Lumpur', 800, 0, 100.00, 'scheduled', @admin_id),
('Acoustic Session at Merdekarya', 'Intimate stripped-down acoustic set', '2026-01-18', '21:00:00', '23:00:00', 'Merdekarya', 'Kuala Lumpur', 80, 65, 30.00, 'completed', @admin_id),
('New Year Countdown 2026', 'NYE countdown performance with fireworks', '2025-12-31', '22:00:00', '01:00:00', 'KLCC Esplanade', 'Kuala Lumpur', 1000, 950, 150.00, 'completed', @admin_id),
('KL Live Music Week - Day 3', 'Featured band at KL Live Music Week festival', '2025-11-15', '20:00:00', '21:30:00', 'Live House KLCC', 'Kuala Lumpur', 300, 280, 60.00, 'completed', @admin_id),
('Corporate Team Building - Maybank', 'Musical entertainment for Maybank team building event', '2025-10-20', '16:00:00', '18:00:00', 'Sunway Resort Hotel', 'Petaling Jaya', 250, 250, NULL, 'completed', @admin_id),
('Raya Open House - Minister Event', 'Performance at ministers Hari Raya open house', '2025-09-28', '19:00:00', '21:00:00', 'Seri Perdana', 'Putrajaya', 500, 400, NULL, 'completed', @admin_id),
('JB Arts Festival', 'Performing arts showcase in Johor Bahru', '2025-08-10', '19:00:00', '21:00:00', 'Persada JB', 'Johor Bahru', 600, 520, 45.00, 'completed', @admin_id),
('Wedding - Haziq & Nadia', 'Wedding dinner entertainment', '2025-07-19', '20:00:00', '23:00:00', 'Grand Hyatt KL', 'Kuala Lumpur', 200, 200, NULL, 'completed', @admin_id),
('Shah Alam City Day Concert', 'City anniversary celebration concert', '2025-06-14', '18:00:00', '20:00:00', 'i-City Shah Alam', 'Shah Alam', 1500, 1200, NULL, 'completed', @admin_id),
('Indie Music Showcase', 'Monthly indie showcase featuring 5 bands', '2025-05-24', '20:00:00', '23:00:00', 'Rumah Api', 'Kuala Lumpur', 100, 95, 25.00, 'completed', @admin_id),
('Brand Launch - Tech Startup', 'Performance at technology startup product launch', '2025-04-30', '19:00:00', '20:30:00', 'Colony KLCC', 'Kuala Lumpur', 150, 130, NULL, 'completed', @admin_id),
('Studio Open Day', 'Open rehearsal and fan meet-and-greet session', '2025-03-22', '14:00:00', '17:00:00', 'SecondPlan Studio', 'Petaling Jaya', 50, 50, NULL, 'completed', @admin_id),
('University Orientation Week', 'Performance at university welcome event', '2026-07-05', '19:00:00', '21:00:00', 'Dewan Tunku Canselor', 'Shah Alam', 500, 0, NULL, 'cancelled', @admin_id);

SET @evt1 = (SELECT event_id FROM events WHERE title = 'Jazz Night at The Bee' LIMIT 1);
SET @evt2 = (SELECT event_id FROM events WHERE title = 'Corporate Dinner - Petronas' LIMIT 1);
SET @evt3 = (SELECT event_id FROM events WHERE title = 'Wedding Reception - Alia & Farhan' LIMIT 1);
SET @evt4 = (SELECT event_id FROM events WHERE title = 'Urbanscapes Festival 2026' LIMIT 1);
SET @evt5 = (SELECT event_id FROM events WHERE title = 'Acoustic Session at Merdekarya' LIMIT 1);
SET @evt6 = (SELECT event_id FROM events WHERE title = 'Penang International Jazz Fest' LIMIT 1);
SET @evt7 = (SELECT event_id FROM events WHERE title = 'Private Party - CEO Birthday' LIMIT 1);
SET @evt8 = (SELECT event_id FROM events WHERE title = 'Charity Concert for Education' LIMIT 1);
SET @evt9 = (SELECT event_id FROM events WHERE title = 'New Year Countdown 2026' LIMIT 1);
SET @evt10 = (SELECT event_id FROM events WHERE title = 'KL Live Music Week - Day 3' LIMIT 1);
SET @evt11 = (SELECT event_id FROM events WHERE title = 'Corporate Team Building - Maybank' LIMIT 1);
SET @evt12 = (SELECT event_id FROM events WHERE title = 'JB Arts Festival' LIMIT 1);

INSERT INTO `bookings` (`user_id`, `company_name`, `event_name`, `event_date`, `event_time`, `location`, `address`, `city`, `state`, `price`, `status`, `quotation_number`, `invoice_number`, `payment_status`, `payment_due_date`, `paid_at`, `notes`, `approved_by`, `approved_at`) VALUES
(@sarah_id, 'Anis Events Sdn Bhd', 'Private Jazz Night', '2026-02-28', '20:00:00', 'Kuala Lumpur', '1 Jalan Dutamas 1', 'Kuala Lumpur', 'WP Kuala Lumpur', 3500.00, 'approved', 'QT-20260201-0001', 'INV-20260201-0001', 'paid', '2026-02-15', '2026-02-10 14:30:00', 'Need acoustic set for first hour', @admin_id, '2026-02-01 10:00:00'),
(@michael_id, 'Petronas Carigali', 'Annual Corporate Dinner', '2026-03-15', '19:00:00', 'Kuala Lumpur', '50 Jalan KLCC', 'Kuala Lumpur', 'WP Kuala Lumpur', 8000.00, 'approved', 'QT-20260205-0002', 'INV-20260205-0002', 'paid', '2026-02-19', '2026-02-18 09:15:00', 'Full band setup with sound system', @admin_id, '2026-02-05 11:00:00'),
(@aina_id, 'Alia & Farhan', 'Wedding Reception Live Band', '2026-03-22', '20:00:00', 'Kuala Lumpur', 'Jalan Sultan Hishamuddin', 'Kuala Lumpur', 'WP Kuala Lumpur', 5000.00, 'approved', 'QT-20260208-0003', 'INV-20260208-0003', 'unpaid', '2026-03-08', NULL, 'Romantic songs for first dance', @admin_id, '2026-02-08 16:00:00'),
(@sarah_id, 'Urbanscapes Organizer', 'Festival Main Stage Slot', '2026-04-12', '17:00:00', 'Petaling Jaya', 'Jalan SS16/1', 'Petaling Jaya', 'Selangor', 6000.00, 'approved', 'QT-20260210-0004', 'INV-20260210-0004', 'unpaid', '2026-03-26', NULL, 'Main stage 90-minute set', @admin_id, '2026-02-10 09:00:00'),
(@michael_id, 'Taylor University', 'University Convocation Dinner', '2026-05-10', '19:00:00', 'Subang Jaya', 'Jalan SS15/8', 'Subang Jaya', 'Selangor', 4500.00, 'rejected', 'QT-20260212-0005', NULL, 'unpaid', NULL, NULL, 'Date conflicts with other event', NULL, NULL),
(NULL, 'Ahmad Consulting', 'Company Anniversary Party', '2026-04-05', '19:30:00', 'Shah Alam', '12 Persiaran Sultan', 'Shah Alam', 'Selangor', NULL, 'pending', 'QT-20260215-0006', NULL, 'unpaid', NULL, NULL, 'Looking for live band for 50 pax', NULL, NULL),
(@aina_id, 'Nadia Events', 'Birthday Bash Live Music', '2026-03-28', '20:00:00', 'Kuala Lumpur', '22 Changkat Bukit Bintang', 'Kuala Lumpur', 'WP Kuala Lumpur', NULL, 'pending', 'QT-20260218-0007', NULL, 'unpaid', NULL, NULL, '30th birthday celebration', NULL, NULL),
(NULL, 'Majlis Perbandaran Klang', 'Klang Heritage Day', '2026-05-20', '18:00:00', 'Klang', 'Dataran Klang', 'Klang', 'Selangor', NULL, 'pending', 'QT-20260220-0008', NULL, 'unpaid', NULL, NULL, 'Outdoor stage with own PA', NULL, NULL),
(@sarah_id, 'SMK Bukit Jalil', 'School Annual Concert', '2026-04-18', '10:00:00', 'Kuala Lumpur', 'Jalan Jalil Perkasa 1', 'Kuala Lumpur', 'WP Kuala Lumpur', NULL, 'pending', 'QT-20260222-0009', NULL, 'unpaid', NULL, NULL, 'School event, need family-friendly setlist', NULL, NULL),
(NULL, 'JW Marriott KL', 'Hotel Gala Dinner Entertainment', '2026-05-30', '20:00:00', 'Kuala Lumpur', '183 Jalan Bukit Bintang', 'Kuala Lumpur', 'WP Kuala Lumpur', NULL, 'pending', 'QT-20260225-0010', NULL, 'unpaid', NULL, NULL, 'Formal dinner, jazz and easy listening', NULL, NULL),
(@michael_id, 'TechCorp Malaysia', 'Product Launch After Party', '2025-12-15', '21:00:00', 'Kuala Lumpur', 'Menara TM', 'Kuala Lumpur', 'WP Kuala Lumpur', 5500.00, 'completed', 'QT-20251201-0011', 'INV-20251201-0011', 'paid', '2025-12-29', '2025-12-20 11:00:00', NULL, @admin_id, '2025-12-01 09:00:00'),
(@aina_id, 'Sapura Energy', 'Year End Dinner', '2025-11-22', '19:00:00', 'Kuala Lumpur', 'Hilton KL', 'Kuala Lumpur', 'WP Kuala Lumpur', 7000.00, 'completed', 'QT-20251110-0012', 'INV-20251110-0012', 'paid', '2025-11-24', '2025-11-23 15:30:00', NULL, @admin_id, '2025-11-10 10:00:00'),
(@sarah_id, 'Penang Hill Corp', 'Heritage Night', '2025-10-05', '19:30:00', 'Georgetown', 'Eastern & Oriental Hotel', 'Georgetown', 'Penang', 4000.00, 'completed', 'QT-20250920-0013', 'INV-20250920-0013', 'paid', '2025-10-04', '2025-10-01 09:00:00', NULL, @admin_id, '2025-09-20 14:00:00'),
(@michael_id, 'CIMB Foundation', 'Charity Gala', '2025-09-13', '19:00:00', 'Kuala Lumpur', 'KLCC Convention Centre', 'Kuala Lumpur', 'WP Kuala Lumpur', 6500.00, 'completed', 'QT-20250901-0014', 'INV-20250901-0014', 'paid', '2025-09-15', '2025-09-12 16:00:00', NULL, @admin_id, '2025-09-01 08:00:00'),
(NULL, 'Unknown Caller', 'Test Booking', '2025-08-01', '20:00:00', 'Kuala Lumpur', NULL, NULL, NULL, NULL, 'rejected', 'QT-20250725-0015', NULL, 'unpaid', NULL, NULL, 'Spam inquiry', NULL, NULL),
(@aina_id, 'Iskandar Invest', 'JB Investment Summit', '2025-07-25', '19:00:00', 'Johor Bahru', 'Thistle Hotel JB', 'Johor Bahru', 'Johor', 5000.00, 'completed', 'QT-20250710-0016', 'INV-20250710-0016', 'paid', '2025-07-24', '2025-07-22 10:00:00', NULL, @admin_id, '2025-07-10 11:00:00'),
(@sarah_id, 'WeWork Malaysia', 'Co-Working Launch Party', '2025-06-28', '18:00:00', 'Kuala Lumpur', 'Equatorial Plaza', 'Kuala Lumpur', 'WP Kuala Lumpur', 3000.00, 'completed', 'QT-20250615-0017', 'INV-20250615-0017', 'paid', '2025-06-29', '2025-06-25 14:00:00', NULL, @admin_id, '2025-06-15 09:30:00'),
(NULL, 'Persatuan Warga Taman Desa', 'Community Fun Day', '2025-06-07', '16:00:00', 'Kuala Lumpur', 'Taman Desa Park', 'Kuala Lumpur', 'WP Kuala Lumpur', 1500.00, 'completed', 'QT-20250525-0018', 'INV-20250525-0018', 'paid', '2025-06-09', '2025-06-06 11:00:00', NULL, @admin_id, '2025-05-25 10:00:00'),
(@michael_id, 'Grab Malaysia', 'GrabFood Festival', '2025-05-17', '12:00:00', 'Kuala Lumpur', 'Pavilion KL', 'Kuala Lumpur', 'WP Kuala Lumpur', 4000.00, 'rejected', 'QT-20250505-0019', NULL, 'unpaid', NULL, NULL, 'Budget too low for full band', NULL, NULL),
(@aina_id, 'Berjaya Times Square', 'Mall Anniversary Show', '2025-04-12', '15:00:00', 'Kuala Lumpur', 'Berjaya Times Square', 'Kuala Lumpur', 'WP Kuala Lumpur', 3500.00, 'completed', 'QT-20250401-0020', 'INV-20250401-0020', 'paid', '2025-04-14', '2025-04-11 16:00:00', NULL, @admin_id, '2025-04-01 09:00:00');

INSERT INTO `tasks` (`title`, `description`, `assigned_to`, `assigned_by`, `event_id`, `priority`, `status`, `due_date`, `completed_at`) VALUES
('Prepare setlist for Jazz Night', 'Create and finalize the setlist for The Bee performance. Include 3 original songs and 7 covers.', @ameer_id, @admin_id, @evt1, 'high', 'in_progress', '2026-02-25', NULL),
('Equipment check for Petronas event', 'Verify all PA system, monitors, and instruments are ready. Coordinate with venue sound engineer.', @fairuz_id, @admin_id, @evt2, 'urgent', 'todo', '2026-03-10', NULL),
('Rehearsal for wedding set', 'Practice wedding-appropriate songs. Focus on romantic ballads and dinner music.', @zimi_id, @admin_id, @evt3, 'medium', 'todo', '2026-03-18', NULL),
('Update social media for Urbanscapes', 'Post announcement graphics on Instagram and Facebook. Tag @urbanscapes.', @one_id, @admin_id, @evt4, 'low', 'completed', '2026-02-01', '2026-01-28 16:00:00'),
('Record demo for new single', 'Track guitar and vocals for upcoming single at studio session.', @ameer_id, @admin_id, NULL, 'medium', 'in_progress', '2026-03-01', NULL),
('Design merch for Urbanscapes', 'Create limited edition Urbanscapes x SecondPlan tee design.', @one_id, @admin_id, @evt4, 'medium', 'in_progress', '2026-03-20', NULL),
('Book rehearsal studio for March', 'Reserve studio for 4 sessions in March for wedding and corporate prep.', @fairuz_id, @admin_id, NULL, 'high', 'completed', '2026-02-15', '2026-02-14 10:00:00'),
('Confirm Penang hotel booking', 'Book accommodation for band members for PIJF 2026 in Penang.', @zimi_id, @admin_id, @evt6, 'medium', 'todo', '2026-04-10', NULL),
('Sound check coordination - Petronas', 'Contact venue AV team to schedule sound check on March 14.', @ameer_id, @admin_id, @evt2, 'urgent', 'todo', '2026-03-05', NULL),
('Video edit for Jazz Night promo', 'Edit rehearsal footage into 30-second promo clip for Instagram.', @one_id, @admin_id, @evt1, 'low', 'completed', '2026-02-10', '2026-02-08 22:00:00'),
('Purchase new bass strings', 'Get Ernie Ball Cobalt strings for upcoming shows.', @fairuz_id, @admin_id, NULL, 'low', 'completed', '2026-01-20', '2026-01-19 11:00:00'),
('Coordinate with wedding planner', 'Call Alina from Dream Weddings to finalize timeline and song requests.', @ameer_id, @admin_id, @evt3, 'high', 'in_progress', '2026-03-15', NULL),
('Update band portfolio', 'Add recent show photos and testimonials to portfolio document.', @zimi_id, @admin_id, NULL, 'low', 'completed', '2026-01-30', '2026-01-29 15:00:00'),
('Arrange transport for Penang', 'Book van rental for band + equipment to Penang. Compare quotes.', @fairuz_id, @admin_id, @evt6, 'medium', 'todo', '2026-04-15', NULL),
('Submit expense claims for January', 'Compile all January receipts and submit for reimbursement.', @one_id, @admin_id, NULL, 'medium', 'completed', '2026-02-05', '2026-02-03 14:00:00'),
('Set up in-ear monitor system', 'Install and test new IEM system before Jazz Night show.', @zimi_id, @admin_id, @evt1, 'high', 'in_progress', '2026-02-26', NULL),
('Create charity concert poster', 'Design A3 poster for Istana Budaya charity concert.', @one_id, @admin_id, @evt8, 'medium', 'todo', '2026-04-30', NULL),
('Finalize CEO party song list', 'Get final approval on 20-song playlist from client for birthday.', @ameer_id, @admin_id, @evt7, 'high', 'todo', '2026-04-25', NULL),
('Backup guitarist arrangement', 'Contact session guitarist in case Zimi is unavailable for Penang.', @ameer_id, @admin_id, @evt6, 'low', 'cancelled', '2026-04-01', NULL),
('Inventory check on merchandise', 'Count all current merch stock and update system.', @fairuz_id, @admin_id, NULL, 'medium', 'completed', '2026-02-01', '2026-01-31 09:00:00');

INSERT INTO `expenses` (`category`, `amount`, `expense_date`, `vendor`, `reference`, `description`, `status`, `submitted_by`, `event_id`) VALUES
('Equipment', 250.00, '2026-01-20', 'Guitar Store KL', 'INV-2026-001', 'New guitar strings and picks for upcoming shows', 'approved', @zimi_id, NULL),
('Transport', 180.00, '2026-01-25', 'Grab Malaysia', 'GRB-88291', 'Transport to Merdekarya venue and back with equipment', 'approved', @fairuz_id, @evt5),
('Rental', 500.00, '2026-02-10', 'ProSound Rental', 'PSR-0210', 'PA system rental for Jazz Night rehearsal', 'pending', @ameer_id, @evt1),
('Marketing', 350.00, '2026-02-05', 'PrintHub Sdn Bhd', 'PH-4422', 'Poster and flyer printing for Urbanscapes promo', 'pending', @one_id, @evt4),
('Food', 120.00, '2026-01-18', 'Nasi Kandar Pelita', 'CASH', 'Team dinner after Merdekarya acoustic session', 'rejected', @ameer_id, @evt5),
('Equipment', 890.00, '2026-01-15', 'Swee Lee Music', 'SL-20260115', 'In-ear monitor system for live shows', 'approved', @zimi_id, NULL),
('Transport', 450.00, '2026-02-01', 'Budget Car Rental', 'BCR-0201', 'Van rental for equipment transport to Shah Alam', 'approved', @fairuz_id, @evt11),
('Venue', 200.00, '2026-02-12', 'Studio 28 PJ', 'STD-0212', 'Rehearsal studio booking - 4 hours', 'approved', @ameer_id, NULL),
('Marketing', 150.00, '2026-02-08', 'Meta Platforms', 'FB-ADS-0208', 'Instagram ad campaign for Jazz Night promotion', 'approved', @one_id, @evt1),
('Food', 85.00, '2026-02-14', 'Restoran Nasi Lemak Famous', 'CASH', 'Team lunch during rehearsal day', 'pending', @fairuz_id, NULL),
('Equipment', 320.00, '2026-01-28', 'Lazada Malaysia', 'LZD-928371', 'Drum head replacement and cymbal stands', 'approved', @one_id, NULL),
('Transport', 95.00, '2026-02-03', 'Grab Malaysia', 'GRB-90112', 'Transport to meeting with Petronas event coordinator', 'approved', @ameer_id, @evt2),
('Rental', 1200.00, '2026-02-15', 'KL Sound Rental', 'KLSR-0215', 'Full sound system rental deposit for Urbanscapes', 'pending', @fairuz_id, @evt4),
('Marketing', 280.00, '2026-02-18', 'Canva Pro', 'CANVA-2026', 'Annual Canva Pro subscription for design work', 'approved', @one_id, NULL),
('Other', 75.00, '2026-01-10', 'MBB Insurance', 'INS-2026', 'Monthly equipment insurance premium', 'approved', @fairuz_id, NULL),
('Equipment', 180.00, '2026-02-20', 'Guitar Store KL', 'INV-2026-005', 'Microphone cable replacements x5', 'pending', @zimi_id, NULL),
('Food', 160.00, '2026-02-22', 'Pizza Hut Malaysia', 'PH-DLV-0222', 'Team dinner during late night rehearsal', 'pending', @ameer_id, NULL),
('Transport', 600.00, '2025-12-30', 'Hertz Car Rental', 'HCR-1230', 'Van rental for NYE countdown gig at KLCC', 'approved', @fairuz_id, @evt9),
('Venue', 350.00, '2025-11-10', 'Studio 28 PJ', 'STD-1110', 'Rehearsal studio for KL Live Music Week prep', 'approved', @ameer_id, @evt10),
('Other', 55.00, '2026-02-25', 'Pos Malaysia', 'POSL-0225', 'Shipping merch orders to customers', 'pending', @one_id, NULL);

INSERT INTO `merchandise` (`name`, `sku`, `description`, `price`, `cost`, `stock`, `low_stock_threshold`, `category`, `image`, `status`) VALUES
('SecondPlan Logo Tee - Black', 'SP-TEE-BLK', 'Premium cotton tee with SecondPlan logo print', 69.90, 25.00, 50, 10, 'Apparel', 'assets/images/merchandise/SP-TEE-BLK.jpg', 'active'),
('SecondPlan Hoodie - Navy', 'SP-HOOD-NVY', 'Heavyweight hoodie with embroidered logo', 149.90, 55.00, 30, 5, 'Apparel', 'assets/images/merchandise/SP-HOOD-NVY.jpg', 'active'),
('SecondPlan Cap - White', 'SP-CAP-WHT', 'Adjustable snapback cap with embroidered logo', 49.90, 15.00, 40, 10, 'Accessories', 'assets/images/merchandise/SP-CAP-WHT.jpg', 'active'),
('Live at Merdekarya EP (Digital)', 'SP-EP-LIVE', 'Digital download of 5-track live EP recorded at Merdekarya', 19.90, 0.00, 999, 1, 'Music', 'assets/images/merchandise/SP-EP-LIVE.jpg', 'active'),
('SecondPlan Sticker Pack', 'SP-STICKER', 'Set of 5 die-cut vinyl stickers', 12.90, 3.00, 100, 20, 'Accessories', 'assets/images/merchandise/SP-STICKER.jpg', 'active'),
('SecondPlan Logo Tee - White', 'SP-TEE-WHT', 'Premium cotton tee with gold SecondPlan logo', 69.90, 25.00, 35, 10, 'Apparel', 'assets/images/merchandise/SP-TEE-WHT.jpg', 'active'),
('SecondPlan Tote Bag', 'SP-TOTE-BLK', 'Canvas tote bag with screen-printed logo', 39.90, 12.00, 60, 15, 'Accessories', 'assets/images/merchandise/SP-TOTE-BLK.jpg', 'active'),
('Guitar Pick Set (6pcs)', 'SP-PICK-SET', 'Custom SecondPlan guitar picks - mixed gauges', 15.00, 3.00, 200, 30, 'Accessories', 'assets/images/merchandise/SP-PICK-SET.jpg', 'active'),
('SecondPlan Poster - Jazz Night', 'SP-POST-JN', 'A2 poster from Jazz Night series, matte finish', 25.00, 5.00, 45, 10, 'Collectibles', 'assets/images/merchandise/SP-POST-JN.jpg', 'active'),
('SecondPlan Wristband', 'SP-WRIST', 'Silicone wristband with debossed logo', 9.90, 2.00, 150, 25, 'Accessories', 'assets/images/merchandise/SP-WRIST.jpg', 'active'),
('Acoustic Sessions CD', 'SP-CD-ACOU', 'Physical CD of acoustic cover sessions', 35.00, 8.00, 25, 5, 'Music', 'assets/images/merchandise/SP-CD-ACOU.jpg', 'active'),
('SecondPlan Keychain', 'SP-KEY-01', 'Metal keychain with SecondPlan guitar logo', 19.90, 5.00, 80, 15, 'Accessories', 'assets/images/merchandise/SP-KEY-01.jpg', 'active'),
('SecondPlan Hoodie - Black', 'SP-HOOD-BLK', 'Heavyweight hoodie in black with gold embroidery', 149.90, 55.00, 20, 5, 'Apparel', 'assets/images/merchandise/SP-HOOD-BLK.jpg', 'active'),
('Limited Edition Vinyl', 'SP-VINYL-01', 'First pressing vinyl of debut album', 89.90, 30.00, 10, 3, 'Music', 'assets/images/merchandise/SP-VINYL-01.jpg', 'active'),
('SecondPlan Mug', 'SP-MUG-BLK', 'Ceramic mug with gold logo, 330ml', 29.90, 8.00, 55, 10, 'Accessories', 'assets/images/merchandise/SP-MUG-BLK.jpg', 'active'),
('Band Photo Print (Signed)', 'SP-PHOTO-S', 'Signed 8x10 glossy band photo', 45.00, 10.00, 15, 5, 'Collectibles', 'assets/images/merchandise/SP-PHOTO-S.jpg', 'active'),
('SecondPlan Lanyard', 'SP-LANY-01', 'Polyester lanyard with detachable buckle', 14.90, 3.00, 120, 20, 'Accessories', 'assets/images/merchandise/SP-LANY-01.jpg', 'active'),
('SecondPlan Beanie - Black', 'SP-BEAN-BLK', 'Knitted beanie with small logo tag', 39.90, 12.00, 3, 5, 'Apparel', 'assets/images/merchandise/SP-BEAN-BLK.jpg', 'active'),
('Drum Stick (Pair)', 'SP-DRUM-STK', 'Custom branded maple drum sticks', 29.90, 8.00, 0, 5, 'Accessories', 'assets/images/merchandise/SP-DRUM-STK.jpg', 'active'),
('SecondPlan Varsity Jacket', 'SP-VARS-BLK', 'Premium varsity jacket with chenille patches', 249.90, 90.00, 8, 3, 'Apparel', 'assets/images/merchandise/SP-VARS-BLK.jpg', 'active');

SET @merch1 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-TEE-BLK' LIMIT 1);
SET @merch2 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-HOOD-NVY' LIMIT 1);
SET @merch3 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-CAP-WHT' LIMIT 1);
SET @merch4 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-EP-LIVE' LIMIT 1);
SET @merch5 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-STICKER' LIMIT 1);
SET @merch6 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-TEE-WHT' LIMIT 1);
SET @merch7 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-TOTE-BLK' LIMIT 1);
SET @merch8 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-PICK-SET' LIMIT 1);
SET @merch9 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-POST-JN' LIMIT 1);
SET @merch10 = (SELECT merch_id FROM merchandise WHERE sku = 'SP-KEY-01' LIMIT 1);

INSERT INTO `orders` (`user_id`, `order_number`, `total_amount`, `status`, `payment_status`, `payment_method`, `shipping_address`) VALUES
(@sarah_id, 'SP-20260201-A1B2C3D4', 219.80, 'delivered', 'paid', 'Online Banking', '12 Jalan SS15/4, 47500 Subang Jaya, Selangor'),
(@michael_id, 'SP-20260203-E5F6G7H8', 69.90, 'shipped', 'paid', 'Credit Card', '45 Persiaran KLCC, 50088 Kuala Lumpur'),
(@aina_id, 'SP-20260205-I9J0K1L2', 182.70, 'processing', 'paid', 'FPX', '8 Jalan Bukit Bintang, 55100 Kuala Lumpur'),
(@sarah_id, 'SP-20260206-M3N4O5P6', 19.90, 'pending', 'unpaid', NULL, '12 Jalan SS15/4, 47500 Subang Jaya, Selangor'),
(@michael_id, 'SP-20260207-Q7R8S9T0', 62.80, 'cancelled', 'refunded', 'Online Banking', '45 Persiaran KLCC, 50088 Kuala Lumpur'),
(@aina_id, 'SP-20260210-U1V2W3X4', 109.80, 'delivered', 'paid', 'Credit Card', '22 Jalan Ampang, 50450 Kuala Lumpur'),
(@sarah_id, 'SP-20260215-Y5Z6A7B8', 149.90, 'shipped', 'paid', 'Online Banking', '12 Jalan SS15/4, 47500 Subang Jaya, Selangor'),
(@michael_id, 'SP-20260218-C9D0E1F2', 84.80, 'processing', 'paid', 'FPX', '45 Persiaran KLCC, 50088 Kuala Lumpur'),
(@aina_id, 'SP-20260220-G3H4I5J6', 45.00, 'pending', 'unpaid', NULL, '22 Jalan Ampang, 50450 Kuala Lumpur'),
(@sarah_id, 'SP-20260222-K7L8M9N0', 259.80, 'delivered', 'paid', 'Credit Card', '12 Jalan SS15/4, 47500 Subang Jaya, Selangor');

SET @ord1 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260201-A1B2C3D4' LIMIT 1);
SET @ord2 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260203-E5F6G7H8' LIMIT 1);
SET @ord3 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260205-I9J0K1L2' LIMIT 1);
SET @ord4 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260206-M3N4O5P6' LIMIT 1);
SET @ord5 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260207-Q7R8S9T0' LIMIT 1);
SET @ord6 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260210-U1V2W3X4' LIMIT 1);
SET @ord7 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260215-Y5Z6A7B8' LIMIT 1);
SET @ord8 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260218-C9D0E1F2' LIMIT 1);
SET @ord9 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260220-G3H4I5J6' LIMIT 1);
SET @ord10 = (SELECT order_id FROM orders WHERE order_number = 'SP-20260222-K7L8M9N0' LIMIT 1);

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
(@ord5, @merch5, 1, 12.90, 12.90),
(@ord6, @merch6, 1, 69.90, 69.90),
(@ord6, @merch7, 1, 39.90, 39.90),
(@ord7, @merch2, 1, 149.90, 149.90),
(@ord8, @merch8, 2, 15.00, 30.00),
(@ord8, @merch9, 1, 25.00, 25.00),
(@ord8, @merch10, 1, 19.90, 19.90),
(@ord8, @merch5, 1, 12.90, 12.90),
(@ord9, @merch9, 1, 25.00, 25.00),
(@ord9, @merch4, 1, 19.90, 19.90),
(@ord10, @merch1, 1, 69.90, 69.90),
(@ord10, @merch2, 1, 149.90, 149.90),
(@ord10, @merch7, 1, 39.90, 39.90),
(@ord10, @merch8, 1, 15.00, 15.00),
(@ord10, @merch5, 2, 12.90, 25.80);

INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(@sarah_id, 'booking_approved', 'Booking Approved', 'Your booking for "Private Jazz Night" has been approved. Price: RM 3,500.00', '/user/my_bookings.php', TRUE, '2026-02-01 10:05:00'),
(@michael_id, 'booking_approved', 'Booking Approved', 'Your booking for "Annual Corporate Dinner" has been approved. Price: RM 8,000.00', '/user/my_bookings.php', TRUE, '2026-02-05 11:05:00'),
(@aina_id, 'booking_approved', 'Booking Approved', 'Your booking for "Wedding Reception Live Band" has been approved. Price: RM 5,000.00', '/user/my_bookings.php', FALSE, '2026-02-08 16:05:00'),
(@sarah_id, 'booking_approved', 'Booking Approved', 'Your booking for "Festival Main Stage Slot" has been approved. Price: RM 6,000.00', '/user/my_bookings.php', FALSE, '2026-02-10 09:05:00'),
(@michael_id, 'booking_rejected', 'Booking Rejected', 'Your booking for "University Convocation Dinner" has been rejected.', '/user/my_bookings.php', TRUE, '2026-02-12 14:00:00'),
(@sarah_id, 'payment_confirmed', 'Payment Confirmed', 'Payment for your booking "Private Jazz Night" has been confirmed.', '/user/my_bookings.php', TRUE, '2026-02-10 14:35:00'),
(@michael_id, 'payment_confirmed', 'Payment Confirmed', 'Payment for your booking "Annual Corporate Dinner" has been confirmed.', '/user/my_bookings.php', FALSE, '2026-02-18 09:20:00'),
(@admin_id, 'new_booking', 'New Booking Request', 'Ahmad Consulting submitted a booking for "Company Anniversary Party"', '/admin/bookings.php', FALSE, '2026-02-15 08:30:00'),
(@admin_id, 'new_booking', 'New Booking Request', 'Nadia Events submitted a booking for "Birthday Bash Live Music"', '/admin/bookings.php', FALSE, '2026-02-18 11:00:00'),
(@admin_id, 'new_booking', 'New Booking Request', 'Majlis Perbandaran Klang submitted a booking for "Klang Heritage Day"', '/admin/bookings.php', FALSE, '2026-02-20 09:00:00'),
(@ameer_id, 'task_assigned', 'New Task Assigned', 'You have been assigned: "Prepare setlist for Jazz Night"', '/band/my_tasks.php', TRUE, '2026-02-01 09:00:00'),
(@fairuz_id, 'task_assigned', 'New Task Assigned', 'You have been assigned: "Equipment check for Petronas event"', '/band/my_tasks.php', FALSE, '2026-02-01 09:05:00'),
(@zimi_id, 'task_assigned', 'New Task Assigned', 'You have been assigned: "Rehearsal for wedding set"', '/band/my_tasks.php', FALSE, '2026-02-01 09:10:00'),
(@one_id, 'task_assigned', 'New Task Assigned', 'You have been assigned: "Update social media for Urbanscapes"', '/band/my_tasks.php', TRUE, '2026-02-01 09:15:00'),
(@admin_id, 'expense_submitted', 'New Expense Submitted', 'Zimi submitted an expense of RM 890.00 for "In-ear monitor system"', '/admin/expenses.php', TRUE, '2026-01-15 10:00:00'),
(@admin_id, 'expense_submitted', 'New Expense Submitted', 'Fairuz submitted an expense of RM 1,200.00 for "Full sound system rental deposit"', '/admin/expenses.php', FALSE, '2026-02-15 15:00:00'),
(@sarah_id, 'order_placed', 'Order Placed', 'Your order SP-20260201-A1B2C3D4 has been placed successfully.', '/user/orders.php', TRUE, '2026-02-01 12:00:00'),
(@michael_id, 'order_placed', 'Order Placed', 'Your order SP-20260203-E5F6G7H8 has been placed successfully.', '/user/orders.php', TRUE, '2026-02-03 14:00:00'),
(@aina_id, 'order_placed', 'Order Placed', 'Your order SP-20260205-I9J0K1L2 has been placed successfully.', '/user/orders.php', FALSE, '2026-02-05 10:00:00'),
(@admin_id, 'new_booking', 'New Booking Request', 'SMK Bukit Jalil submitted a booking for "School Annual Concert"', '/admin/bookings.php', FALSE, '2026-02-22 10:00:00');

INSERT INTO `activity_log` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(@admin_id, 'login', NULL, NULL, '{"ip":"127.0.0.1"}', '127.0.0.1', '2026-02-05 08:00:00'),
(@ameer_id, 'login', NULL, NULL, '{"ip":"192.168.1.10"}', '192.168.1.10', '2026-02-05 09:30:00'),
(@sarah_id, 'login', NULL, NULL, '{"ip":"203.0.113.50"}', '203.0.113.50', '2026-02-04 14:00:00'),
(@michael_id, 'login', NULL, NULL, '{"ip":"203.0.113.51"}', '203.0.113.51', '2026-02-03 16:00:00'),
(@sarah_id, 'booking_submit', 'booking', 1, '{"event_name":"Private Jazz Night"}', '203.0.113.50', '2026-02-01 09:55:00'),
(@admin_id, 'booking_approved', 'booking', 1, '{"price":3500}', '127.0.0.1', '2026-02-01 10:00:00'),
(@michael_id, 'booking_submit', 'booking', 2, '{"event_name":"Annual Corporate Dinner"}', '203.0.113.51', '2026-02-05 10:50:00'),
(@admin_id, 'booking_approved', 'booking', 2, '{"price":8000}', '127.0.0.1', '2026-02-05 11:00:00'),
(@aina_id, 'booking_submit', 'booking', 3, '{"event_name":"Wedding Reception Live Band"}', '203.0.113.52', '2026-02-08 15:50:00'),
(@admin_id, 'booking_approved', 'booking', 3, '{"price":5000}', '127.0.0.1', '2026-02-08 16:00:00'),
(@admin_id, 'settings_updated', NULL, NULL, '{"action":"general_settings"}', '127.0.0.1', '2026-02-04 10:00:00'),
(@admin_id, 'social_media_updated', NULL, NULL, '{}', '127.0.0.1', '2026-02-04 10:05:00'),
(@sarah_id, 'order_placed', 'order', 1, '{"total":219.80}', '203.0.113.50', '2026-02-01 12:00:00'),
(@michael_id, 'order_placed', 'order', 2, '{"total":69.90}', '203.0.113.51', '2026-02-03 14:00:00'),
(@aina_id, 'order_placed', 'order', 3, '{"total":182.70}', '203.0.113.52', '2026-02-05 10:00:00'),
(@zimi_id, 'login', NULL, NULL, '{"ip":"192.168.1.11"}', '192.168.1.11', '2026-02-05 10:00:00'),
(@fairuz_id, 'login', NULL, NULL, '{"ip":"192.168.1.12"}', '192.168.1.12', '2026-02-05 10:15:00'),
(@one_id, 'login', NULL, NULL, '{"ip":"192.168.1.13"}', '192.168.1.13', '2026-02-04 20:00:00'),
(@aina_id, 'login', NULL, NULL, '{"ip":"203.0.113.52"}', '203.0.113.52', '2026-02-05 08:30:00'),
(@admin_id, 'login', NULL, NULL, '{"ip":"127.0.0.1"}', '127.0.0.1', '2026-02-04 08:00:00');

INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('social_instagram', 'https://instagram.com/secondplanband', 'string', 'Instagram URL'),
('social_facebook', 'https://facebook.com/secondplanband', 'string', 'Facebook URL'),
('social_tiktok', 'https://tiktok.com/@secondplanband', 'string', 'TikTok URL'),
('social_youtube', 'https://youtube.com/@secondplanband', 'string', 'YouTube URL'),
('social_whatsapp', 'https://api.whatsapp.com/send?phone=+60185850628&text=%20I%2FWe%20would%20like%20to%20inquire%2Fbooking%20SOFAZR%20Band', 'string', 'WhatsApp contact URL'),
('spotify_embed_url', 'https://open.spotify.com/embed/artist/4gzpq5DPGxSnKTe4SA8HAU', 'string', 'Spotify embed URL'),
('youtube_embed_url', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'string', 'YouTube embed URL'),
('years_active', '5', 'string', 'Years band has been active')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
