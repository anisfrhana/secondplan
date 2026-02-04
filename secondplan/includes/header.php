<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * Usage in pages:
 *   <?php $title = 'Page Title · SecondPlan'; include __DIR__ . '/../includes/header.php'; ?>
 *   ... your content ...
 *   <?php include __DIR__ . '/../includes/footer.php'; ?>
 *
 * This header:
 *  - Sets <title> (uses $title if defined)
 *  - Loads FullCalendar CSS (you used it in existing pages)
 *  - Provides a dark nav that adapts to session + role (admin/member)
 *  - Adds a container wrapper
 */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= e($title ?? 'SecondPlan'); ?></title>

  <!-- Vendor CSS your app already uses -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css">

  <!-- Minimal dark theme to match Admin/Auth -->
  <style>
    :root{
      --bg:#0f172a; --nav:#0b1220; --border:#263142; --text:#e5e7eb; --sub:#9ca3af; --accent:#3b82f6;
    }
    *{ box-sizing:border-box }
    html,body{ height:100% }
    body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial, "Helvetica Neue", sans-serif; background: var(--bg); color: var(--text); }
    nav{
      background: linear-gradient(180deg, #0c1629, #0b1220);
      border-bottom:1px solid var(--border);
      color:#fff; padding:10px 16px; display:flex; gap:16px; align-items:center;
      position:sticky; top:0; z-index:50;
    }
    nav a{ color:#cfe1ff; text-decoration:none; margin-right:12px; }
    nav a:hover{ text-decoration:underline }
    .brand{ font-weight:700; color:#fff; margin-right:8px }
    .spacer{ margin-left:auto }
    .container{ padding:16px; max-width:1200px; margin:0 auto; }
    .badge{ font-size:12px; padding:2px 6px; border:1px solid #2a3a57; border-radius:999px; color:#9cc1ff; }
  </style>
</head>
<body>
  <nav>
    <span class="brand">SecondPlan</span>
    <a href="/index.php">Home</a>

    <?php if (!empty($_SESSION['user_id'])): ?>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="/admin/dashboard.php">Admin Dashboard</a>
        <a href="/admin/events.php">Events</a>
        <a href="/admin/expenses.php">Expenses</a>
        <a href="/admin/merchandise.php">Merchandise</a>
        <a href="/admin/tasks.php">Tasks</a>
        <a href="/admin/bookings.php">Bookings</a>
      <?php endif; ?>

      <?php if (($_SESSION['role'] ?? '') === 'member'): ?>
        <a href="/user/dashboard.php">My Dashboard</a>
      <?php endif; ?>

      <span class="spacer"></span>
      <span>Hi, <?= e($_SESSION['name'] ?? 'User'); ?></span>
      <?php if (!empty($_SESSION['role'])): ?>
        <span class="badge"><?= e($_SESSION['role']); ?></span>
      <?php endif; ?>
      <a href="/auth/logout.php">Logout</a>
    <?php else: ?>
      <span class="spacer"></span>
      <a href="/auth/login.php">Login</a>
      <a href="/auth/register.php">Register</a>
    <?php endif; ?>
  </nav>

  <div class="container">
exit;
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>SecondPlan</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css">
  <style>
    :root{ --bg:#0f172a; --nav:#0b1220; --border:#263142; --text:#e5e7eb; --sub:#9ca3af; --accent:#3b82f6; }
    body{ margin:0; font-family: system-ui,Arial,sans-serif; background:var(--bg); color:var(--text) }
    nav{ background:#0b1220; color:#fff; padding:10px 16px; display:flex; gap:16px; align-items:center; border-bottom:1px solid var(--border) }
    nav a{ color:#cfe1ff; text-decoration:none; margin-right:12px }
    nav a:hover{ text-decoration:underline }
    .brand{ font-weight:700 }
    .container{ padding:16px; max-width:1200px; margin:0 auto }
  </style>
</head>
<body>
  <nav>
    <span class="brand">SecondPlan</span>
    <a href="/index.php">Home</a>
    <span style="margin-left:auto"></span>
    <a href="/auth/login.php">Login</a>
    <a href="/auth/register.php">Register</a>
  </nav>
  <div class="container">