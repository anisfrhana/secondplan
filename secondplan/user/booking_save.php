<?php
// /user/booking_save.php
session_start();
require_once __DIR__ . '/../config/db.php'; // $conn (mysqli)
// If you have bootstrap with verify_csrf(): require and call it here:
// require_once __DIR__ . '/../config/bootstrap.php'; verify_csrf();

header('X-Content-Type-Options: nosniff');

// Determine response mode early
$isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

// Allow both keys during migration
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$user_id) {
  // Not logged in
  if ($isJson) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
  }
  header('Location: ../auth/login.php');
  exit;
}

// Collect fields
$company   = trim($_POST['company'] ?? '');
$title     = trim($_POST['title'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$event_time = trim($_POST['event_time'] ?? '');
$address   = trim($_POST['address'] ?? '');
$postal     = trim($_POST['postal_code'] ?? '');
$city       = trim($_POST['city'] ?? '');
$state      = trim($_POST['state'] ?? '');

// Simple validation
$errors = [];
foreach (['company'=>$company,'title'=>$title,'event_date'=>$event_date,'event_time'=>$event_time,'address'=>$address,'postal_code'=>$postal,'city'=>$city,'state'=>$state] as $k=>$v){
  if($v==='') $errors[] = "Missing: $k";
}

// Poster validation
$posterName = '';
if (!empty($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
  $tmp = $_FILES['poster']['tmp_name'];
  $size = (int)$_FILES['poster']['size'];

  // MIME validation using finfo
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: 'application/octet-stream';
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'application/pdf' => 'pdf',
  ];
  if (!isset($allowed[$mime])) $errors[] = 'Poster must be JPG, PNG, or PDF.';
  if ($size > 5*1024*1024) $errors[] = 'Poster size exceeds 5MB.';

  if (!$errors){
    $ext = $allowed[$mime];
    $posterName = bin2hex(random_bytes(16)) . '.' . $ext;

    // Ensure upload dir exists
    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (!move_uploaded_file($tmp, $dir.$posterName)) {
      $errors[] = 'Failed to save poster.';
    }
  }
} else {
  $errors[] = 'Poster is required.';
}

// Decide response mode
$isJson = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
if ($errors){
  if ($isJson){
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>implode('; ', $errors)]);
    exit;
  }
  // Fallback: set a flash in session if you have it, else just redirect back
  header('Location: booking.php?error=1'); exit;
}

// Insert into DB (mysqli, prepared)
$stmt = $conn->prepare("INSERT INTO event_booking
  (user_id, company, title, event_date, event_time, address, postal_code, city, state, posterEvent, status)
  VALUES (?,?,?,?,?,?,?,?,?,?, 'Pending')");
$stmt->bind_param(
  'isssssssss',
  $user_id, $company, $title, $event_date, $event_time, $address, $postal, $city, $state, $posterName
);
$ok = $stmt->execute();
if(!$ok){
  if ($isJson){
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Database error']); exit;
  }
  header('Location: booking.php?error=1'); exit;
}

if ($isJson){
  header('Content-Type: application/json');
  echo json_encode(['success'=>true,'message'=>'Booking submitted','redirect'=>'dashboard.php']); exit;
}
// Traditional redirect
header('Location: dashboard.php'); exit;

?>

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
