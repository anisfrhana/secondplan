<?php
$title = 'Book Event · SecondPlan';
require_once __DIR__ . '/../config/bootstrap.php';
require_login(); require_role(['member']); // or your guard
include __DIR__ . '/../includes/header.php';
?>
  <h1>Book an Event</h1>
  <form action="booking_save.php" method="post" enctype="multipart/form-data" style="max-width:720px">
    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
    <!-- Repeat the same inputs as in the HTML version -->
    <!-- company, title, event_date, event_time, address, postal_code, city, state, poster -->
    <!-- Keep your classes if you want the dark theme, or use your site’s base styles -->
    <!-- ... -->
  </form>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User · Event Booking · SecondPlan</title>

  <!-- If you render this via PHP, print the token:
  <meta name="csrf" content="<?php echo csrf_token(); ?>">
  -->

  <link rel="stylesheet" href="./assets/css/user.css">
  <script defer src="./assets/js/user.js"></script>
  <style>
    .preview{ margin-top:8px; display:flex; align-items:center; gap:10px }
    .preview img{ max-height:80px; border-radius:6px; border:1px solid #263142 }
  </style>
</head>
<body data-page="booking_form">
  <nav class="topbar">
    <span class="brand">SecondPlan</span>
    <a href="./dashboard.html">Dashboard</a>
    <a href="./booking.html">Booking</a>
    <a href="./merchandise.html">Merchandise</a>
    <a href="./tasks.html">Tasks</a>
    <span class="spacer"></span>
    <a href="../auth/logout.php">Logout</a>
  </nav>

  <div class="container">
    <div class="page-head"><div class="page-title">Event Booking Form</div></div>

    <!-- Same field names as your PHP -->
    <form id="booking-form" class="form" action="booking_save.php" method="post" enctype="multipart/form-data">
      <!-- If you render via PHP, include the CSRF input:
      <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
      -->
      <div class="field">
        <label>Company</label>
        <input class="input" name="company" required placeholder="Your company / client name">
      </div>
      <div class="field">
        <label>Event Title</label>
        <input class="input" name="title" required placeholder="e.g., Showcase Night">
      </div>
      <div class="field">
        <label>Date</label>
        <input class="input" name="event_date" type="date" required>
      </div>
      <div class="field">
        <label>Time</label>
        <input class="input" name="event_time" type="time" required>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label>Address</label>
        <input class="input" name="address" required placeholder="Street address">
      </div>
      <div class="field">
        <label>Postal Code</label>
        <input class="input" name="postal_code" required>
      </div>
      <div class="field">
        <label>City</label>
        <input class="input" name="city" required>
      </div>
      <div class="field">
        <label>State</label>
        <input class="input" name="state" required>
      </div>
      <div class="field" style="grid-column:1/-1">
        <label>Poster (JPG/PNG/PDF, max 5MB)</label>
        <input id="poster" class="input" name="poster" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
        <div class="preview" id="preview" style="display:none">
          <img id="preview-img" alt="Poster preview">
          <span id="preview-name" class="badge">…</span>
        </div>
      </div>

      <div class="actions" style="grid-column:1/-1">
        <button class="btn" type="reset">Reset</button>
        <button id="submit-booking" class="btn success" type="submit">Submit Booking</button>
      </div>
    </form>
  </div>

  <div class="toast"></div>
</body>
</html>
