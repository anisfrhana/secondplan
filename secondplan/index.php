<?php
require_once __DIR__ . '/config/bootstrap.php';

$upcomingEvents = [];
try {
    $stmt = $pdo->query("
        SELECT title, description, date, start_time, end_time, venue, location, price, capacity,
               COALESCE(capacity - seats_booked, NULL) AS available_seats
        FROM events
        WHERE date >= CURDATE() AND status = 'scheduled'
        ORDER BY date ASC
        LIMIT 6
    ");
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log('Index events query: ' . $e->getMessage()); }

$merchandiseItems = [];
try {
    $stmt = $pdo->query("
        SELECT name, description, price, image, stock
        FROM merchandise
        WHERE status = 'active' AND stock > 0
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $merchandiseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log('Index merchandise query: ' . $e->getMessage()); }

$bandMembers = [];
try {
    $stmt = $pdo->query("
        SELECT u.name, u.position, u.profile_image
        FROM users u
        JOIN user_roles ur ON u.user_id = ur.user_id
        JOIN roles r ON ur.role_id = r.role_id
        WHERE r.role_name IN ('band_member','band') AND u.status = 'active'
        ORDER BY FIELD(u.position, 'Vocalist', 'Guitarist', 'Bassist', 'Drummer', '')
    ");
    $bandMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log('Index band members query: ' . $e->getMessage()); }

$bookingMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_submit'])) {
    try {
        if (!verify_csrf()) {
            $bookingMsg = 'error';
            throw new Exception('Invalid request');
        }

        $name = trim($_POST['client_name'] ?? '');
        $email = trim($_POST['client_email'] ?? '');
        $eventName = trim($_POST['event_type'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $location = trim($_POST['event_location'] ?? '');
        $budget = max(0, (float)($_POST['budget'] ?? 0));
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($eventName) || empty($eventDate) || !isValidEmail($email)) {
            $bookingMsg = 'error';
        } else {
            $companyName = $name . ' (' . $email . ')';
            $fullEventName = $eventName . ($message ? ' - ' . $message : '');
            $quotationNumber = 'QT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            $stmt = $pdo->prepare("
                INSERT INTO bookings (company_name, event_name, event_date, location, price, quotation_number, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$companyName, $fullEventName, $eventDate, $location, $budget, $quotationNumber]);

            sendBookingSubmittedEmail($email, $name, $quotationNumber, $eventName, $eventDate, $location);

            $bookingMsg = 'success';
        }
    } catch (Exception $e) {
        error_log('Index booking submit: ' . $e->getMessage());
        $bookingMsg = 'error';
    }
}

$totalEvents = 0;
$totalBookings = 0;
$bandMemberCount = 0;
try {
    $totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn();
    $bandMemberCount = count($bandMembers) ?: 4;
} catch (Exception $e) { error_log('Index stats query: ' . $e->getMessage()); }

$yearsActive = function_exists('getSetting') ? (getSetting('years_active', '5')) : '5';

$pastEvents = [];
try {
    $stmt = $pdo->query("
        SELECT title, date, venue, location
        FROM events
        WHERE date < CURDATE()
        ORDER BY date DESC
        LIMIT 6
    ");
    $pastEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log('Index past events query: ' . $e->getMessage()); }

$spotifyEmbedUrl = function_exists('getSetting') ? getSetting('spotify_embed_url', '') : '';
$youtubeEmbedUrl = function_exists('getSetting') ? getSetting('youtube_embed_url', '') : '';

$socialInstagram = function_exists('getSetting') ? getSetting('social_instagram', '') : '';
$socialFacebook = function_exists('getSetting') ? getSetting('social_facebook', '') : '';
$socialTiktok = function_exists('getSetting') ? getSetting('social_tiktok', '') : '';
$socialYoutube = function_exists('getSetting') ? getSetting('social_youtube', '') : '';
$socialWhatsapp = function_exists('getSetting') ? getSetting('social_whatsapp', '') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECONDPLAN - Professional Band Management</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="container nav-container">
        <a href="#home" class="nav-brand">
            <img src="assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
            <span>SECONDPLAN</span>
        </a>
        <div class="nav-menu" id="navMenu">
            <a href="#home" class="nav-link active">Home</a>
            <a href="#about" class="nav-link">About</a>
            <a href="#events" class="nav-link">Events</a>
            <a href="#services" class="nav-link">Services</a>
            <a href="#music" class="nav-link">Music</a>
            <a href="#merchandise" class="nav-link">Merch</a>
            <a href="#booking" class="nav-link">Book Us</a>
        </div>
        <div class="nav-actions">
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-ghost">Login</a>
            <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary">Sign Up</a>
        </div>
        <button class="nav-toggle" id="navToggle" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<section class="hero" id="home">
    <div class="hero-bg"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">SECONDPLAN Band</div>
            <h1>Live Music.<br>Real Energy.</h1>
            <p class="hero-subtitle">
                We are SecondPlan — a Malaysian band bringing unforgettable live performances to corporate events, weddings, private parties, and concerts.
            </p>
            <div class="hero-actions">
                <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                <a href="#booking" class="btn btn-outline btn-lg">Book Us</a>
            </div>
            <?php if (!empty($upcomingEvents)): ?>
            <?php $nextEvent = $upcomingEvents[0]; ?>
            <div class="countdown-section">
                <div class="countdown-label">Next Event</div>
                <div class="countdown" id="countdown">
                    <div class="countdown-unit">
                        <div class="countdown-value" id="cd-days">00</div>
                        <div class="countdown-desc">Days</div>
                    </div>
                    <div class="countdown-sep">:</div>
                    <div class="countdown-unit">
                        <div class="countdown-value" id="cd-hours">00</div>
                        <div class="countdown-desc">Hours</div>
                    </div>
                    <div class="countdown-sep">:</div>
                    <div class="countdown-unit">
                        <div class="countdown-value" id="cd-mins">00</div>
                        <div class="countdown-desc">Minutes</div>
                    </div>
                    <div class="countdown-sep">:</div>
                    <div class="countdown-unit">
                        <div class="countdown-value" id="cd-secs">00</div>
                        <div class="countdown-desc">Seconds</div>
                    </div>
                </div>
                <div class="countdown-event">
                    <strong><?= htmlspecialchars($nextEvent['title']) ?></strong>
                    <span><?= htmlspecialchars($nextEvent['venue']) ?><?= $nextEvent['location'] ? ', ' . htmlspecialchars($nextEvent['location']) : '' ?></span>
                </div>
            </div>
            <script>
            var eventDate = new Date("<?= $nextEvent['date'] ?>T<?= $nextEvent['start_time'] ?>").getTime();
            </script>
            <?php else: ?>
            <div class="countdown-section">
                <div class="countdown-label">Stay Tuned</div>
                <p style="color:var(--text-secondary);margin-top:12px;">No upcoming events scheduled. Check back soon!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="about reveal" id="about">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">About Us</span>
            <h2>Meet the Band</h2>
            <p>Bringing energy and talent to every stage we step on</p>
        </div>

        <?php if (!empty($bandMembers)): ?>
        <div class="members-grid">
            <?php foreach ($bandMembers as $member): ?>
            <div class="member-card">
                <?php if (!empty($member['profile_image'])): ?>
                <div class="member-photo">
                    <img src="<?= htmlspecialchars($member['profile_image']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                </div>
                <?php else: ?>
                <div class="member-avatar"><?= strtoupper(substr($member['name'], 0, 1)) ?></div>
                <?php endif; ?>
                <h4><?= htmlspecialchars($member['name']) ?></h4>
                <?php if (!empty($member['position'])): ?>
                    <p class="member-position"><?= htmlspecialchars($member['position']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="about-text">
            <p>We are SecondPlan — four musicians united by a passion for live music. From corporate stages to intimate celebrations, we deliver performances that move the crowd.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<hr class="section-divider">

<section class="stats-section">
    <div class="container">
        <div class="stats-counter reveal">
            <div class="stat-counter-item">
                <div class="stat-counter-value" data-target="<?= $totalEvents ?>">0</div>
                <div class="stat-counter-label">Total Events</div>
            </div>
            <div class="stat-counter-item">
                <div class="stat-counter-value" data-target="<?= $bandMemberCount ?>">0</div>
                <div class="stat-counter-label">Band Members</div>
            </div>
            <div class="stat-counter-item">
                <div class="stat-counter-value" data-target="<?= (int)$yearsActive ?>">0</div>
                <div class="stat-counter-label">Years Active</div>
            </div>
            <div class="stat-counter-item">
                <div class="stat-counter-value" data-target="<?= $totalBookings ?>">0</div>
                <div class="stat-counter-label">Bookings Done</div>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider">

<section class="events-section reveal" id="events">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Upcoming</span>
            <h2>Upcoming Events</h2>
            <p>Catch us live at these upcoming performances</p>
        </div>

        <?php if (!empty($upcomingEvents)): ?>
        <div class="events-grid">
            <?php foreach ($upcomingEvents as $event): ?>
            <div class="event-card">
                <div class="event-date-badge">
                    <div class="event-day"><?= date('d', strtotime($event['date'])) ?></div>
                    <div class="event-month"><?= date('M', strtotime($event['date'])) ?></div>
                </div>
                <div class="event-info">
                    <h3><?= htmlspecialchars($event['title']) ?></h3>
                    <div class="event-meta">
                        <span><i class="bi bi-geo-alt event-icon"></i> <?= htmlspecialchars($event['venue']) ?><?= $event['location'] ? ', ' . htmlspecialchars($event['location']) : '' ?></span>
                    </div>
                    <div class="event-meta">
                        <span><i class="bi bi-clock event-icon"></i> <?= date('g:i A', strtotime($event['start_time'])) ?><?= $event['end_time'] ? ' - ' . date('g:i A', strtotime($event['end_time'])) : '' ?></span>
                    </div>
                    <?php if ($event['price']): ?>
                    <div class="event-price">RM <?= number_format($event['price'], 2) ?></div>
                    <?php else: ?>
                    <div class="event-price free">Free Entry</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-message">No upcoming events scheduled. Check back soon!</div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($pastEvents)): ?>
<section class="past-events-section reveal">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Past Shows</span>
            <h2>Previous Performances</h2>
            <p>A look back at where we've been</p>
        </div>
        <div class="past-events-grid">
            <?php foreach ($pastEvents as $pe): ?>
            <div class="past-event-card">
                <h4><?= htmlspecialchars($pe['title']) ?></h4>
                <div class="event-meta"><?= date('d M Y', strtotime($pe['date'])) ?> &bull; <?= htmlspecialchars($pe['venue']) ?><?= $pe['location'] ? ', ' . htmlspecialchars($pe['location']) : '' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($spotifyEmbedUrl || $youtubeEmbedUrl): ?>
<section class="music-section reveal" id="music">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Listen</span>
            <h2>Our Music</h2>
            <p>Listen to our latest tracks and performances</p>
        </div>
        <div class="music-grid <?= ($spotifyEmbedUrl && $youtubeEmbedUrl) ? '' : 'single' ?>">
            <?php if ($spotifyEmbedUrl): ?>
            <div class="music-embed">
                <iframe src="<?= htmlspecialchars($spotifyEmbedUrl) ?>" width="100%" height="352" frameBorder="0" allowfullscreen allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
            </div>
            <?php endif; ?>
            <?php if ($youtubeEmbedUrl): ?>
            <div class="music-embed">
                <iframe src="<?= htmlspecialchars($youtubeEmbedUrl) ?>" width="100%" height="352" frameBorder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="gallery-section reveal" id="gallery">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Gallery</span>
            <h2>Band Gallery</h2>
            <p>Moments from our shows and studio sessions</p>
        </div>
        <div class="gallery-grid">
            <div class="gallery-item"><img src="assets/images/gallery/event1.jpeg" alt="Live Performance" loading="lazy"><div class="gallery-overlay">Live Performance</div></div>
            <div class="gallery-item"><img src="assets/images/gallery/event2.jpeg" alt="On Stage" loading="lazy"><div class="gallery-overlay">On Stage</div></div>
            <div class="gallery-item"><img src="assets/images/gallery/event3.jpeg" alt="Live Show" loading="lazy"><div class="gallery-overlay">Live Show</div></div>
            <div class="gallery-item"><img src="assets/images/gallery/event6.jpeg" alt="Drumming" loading="lazy"><div class="gallery-overlay">Drumming</div></div>
            <div class="gallery-item"><img src="assets/images/gallery/event4.jpeg" alt="Band Duo" loading="lazy"><div class="gallery-overlay">Band Duo</div></div>
            <div class="gallery-item"><img src="assets/images/gallery/event5.jpeg" alt="Guitar Solo" loading="lazy"><div class="gallery-overlay">Guitar Solo</div></div>
        </div>
    </div>
</section>

<section class="services reveal" id="services">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">What We Offer</span>
            <h2>Our Services</h2>
            <p>Professional entertainment for every occasion</p>
        </div>

        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-mic"></i></div>
                <h3>Live Performances</h3>
                <p>High-energy live music for concerts, festivals, and club nights with professional sound and lighting</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-building"></i></div>
                <h3>Corporate Events</h3>
                <p>Professional entertainment for corporate dinners, product launches, and company celebrations</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-heart"></i></div>
                <h3>Weddings</h3>
                <p>Create magical moments with live music tailored to your special day</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-music-note-beamed"></i></div>
                <h3>Private Events</h3>
                <p>Birthday parties, anniversary celebrations, and exclusive private gatherings</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-headphones"></i></div>
                <h3>Studio Sessions</h3>
                <p>Professional recording and collaboration sessions for original music and covers</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="bi bi-clipboard-check"></i></div>
                <h3>Event Planning</h3>
                <p>Full event coordination including sound, lighting, and stage management</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($merchandiseItems)): ?>
<section class="merch-section reveal" id="merchandise">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Shop</span>
            <h2>Official Merchandise</h2>
            <p>Get exclusive band gear and support the music</p>
        </div>

        <div class="merch-grid">
            <?php foreach ($merchandiseItems as $item): ?>
            <a href="<?= APP_URL ?>/auth/register.php" style="text-decoration:none;color:inherit;display:block;">
            <div class="merch-card">
                <div class="merch-image">
                    <?php if ($item['image']): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <?php else: ?>
                    <span class="merch-placeholder"><i class="bi bi-box-seam"></i></span>
                    <?php endif; ?>
                </div>
                <div class="merch-info">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <?php if ($item['description']): ?>
                    <p><?= htmlspecialchars(substr($item['description'], 0, 60)) ?></p>
                    <?php endif; ?>
                    <div class="merch-price">RM <?= number_format($item['price'], 2) ?></div>
                </div>
            </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="section-footer">
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline">View Full Store</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="booking-section reveal" id="booking">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Contact</span>
            <h2>Book Us For Your Event</h2>
            <p>Fill in the form below and we'll get back to you within 24 hours</p>
        </div>

        <?php if ($bookingMsg === 'success'): ?>
        <div class="alert alert-success">
            Your booking inquiry has been submitted. We will contact you shortly!
        </div>
        <?php elseif ($bookingMsg === 'error'): ?>
        <div class="alert alert-error">
            Something went wrong. Please fill all required fields and try again.
        </div>
        <?php endif; ?>

        <form class="booking-form" method="POST" action="#booking">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="client_name" required placeholder="Anis Farhana">
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="client_email" required placeholder="anis@example.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Event Type *</label>
                    <select name="event_type" required>
                        <option value="">Select Event Type</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Concert">Concert</option>
                        <option value="Private Party">Private Party</option>
                        <option value="Festival">Festival</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Date *</label>
                    <input type="date" name="event_date" required min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Location / Venue</label>
                    <input type="text" name="event_location" placeholder="e.g., Kuala Lumpur">
                </div>
                <div class="form-group">
                    <label>Budget (RM)</label>
                    <input type="number" name="budget" step="100" min="0" placeholder="e.g., 5000">
                </div>
            </div>
            <div class="form-group full">
                <label>Additional Details</label>
                <textarea name="message" rows="4" placeholder="Tell us about your event, guest count, special requests..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="booking_submit" class="btn btn-primary btn-lg">Submit Inquiry</button>
            </div>
        </form>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-brand">
                    <img src="assets/images/logo.jpg" alt="SecondPlan" class="brand-icon">
                    <span>SECONDPLAN</span>
                </div>
                <p>Malaysian band delivering unforgettable live music for every occasion.</p>
                <?php if ($socialInstagram || $socialFacebook || $socialTiktok || $socialYoutube || $socialWhatsapp): ?>
                <div class="footer-social">
                    <?php if ($socialInstagram): ?><a href="<?= htmlspecialchars($socialInstagram) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a><?php endif; ?>
                    <?php if ($socialFacebook): ?><a href="<?= htmlspecialchars($socialFacebook) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a><?php endif; ?>
                    <?php if ($socialTiktok): ?><a href="<?= htmlspecialchars($socialTiktok) ?>" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a><?php endif; ?>
                    <?php if ($socialYoutube): ?><a href="<?= htmlspecialchars($socialYoutube) ?>" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a><?php endif; ?>
                    <?php if ($socialWhatsapp): ?><a href="<?= htmlspecialchars($socialWhatsapp) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="footer-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#events">Events</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#merchandise">Merchandise</a></li>
                    <li><a href="#booking">Book Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="<?= APP_URL ?>/auth/login.php">Login</a></li>
                    <li><a href="<?= APP_URL ?>/auth/register.php">Register</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li>Kuala Lumpur, Malaysia</li>
                    <li>info@secondplan.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> SECONDPLAN. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="main.js"></script>
</body>
</html>
