<?php
session_start();
include("../config/db.php");

$uid = $_SESSION['id'];

$q=mysqli_query($conn,"SELECT * FROM tasks WHERE user_id='$uid'");

echo "<h2>My Tasks</h2>";

while($row=mysqli_fetch_assoc($q)){
 echo "
 <b>".$row['taskType']."</b><br>
 ".$row['description']."<br>
 ".$row['date_time']."<br><hr>
 ";
}
?>

<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$uid = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

if (isset($_GET['api']) && $_GET['api']==='my') {
  header('Content-Type: application/json');
  $rows = [];
  $uid_safe = (int)$uid;
  $q = mysqli_query($conn, "SELECT id AS task_id, taskType AS title, description, date_time AS due_date, status, priority FROM tasks WHERE user_id={$uid_safe}");
  while($r=mysqli_fetch_assoc($q)){
    $rows[] = [
      'id' => (int)$r['task_id'],
      'title' => $r['title'] ?: ($r['taskType'] ?? 'Task'),
      'due_date' => $r['due_date'],
      'priority' => $r['priority'] ?? '',
      'status' => $r['status'] ?? 'pending',
    ];
  }
  echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

// (existing HTML output)
echo "<h2>My Tasks</h2>";
$q=mysqli_query($conn,"SELECT * FROM tasks WHERE user_id='{$uid}'");
while($row=mysqli_fetch_assoc($q)){
  echo "
   <b>".$row['taskType']."</b><br>
   ".$row['description']."<br>
   ".$row['date_time']."<br><hr>
  ";
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User · Tasks · SecondPlan</title>
  <link rel="stylesheet" href="assets/css/user.css">
  <script src="assets/js/user.js" defer></script>
</head>
<body data-page="tasks">
  <nav class="topbar">
    <span class="brand">SecondPlan</span>
    <a href="dashboard.html">Dashboard</a>
    <a href="booking.html">Booking</a>
    <a href="merchandise.html">Merchandise</a>
    <a href="tasks.html">Tasks</a>
    <span class="spacer"></span>
    <a href="../auth/logout.php">Logout</a>
  </nav>

  <div class="container">
    <div class="page-head">
      <div class="page-title">My Tasks</div>
      <div class="toolbar">
        <input id="search" class="search" placeholder="Search tasks…">
        <button id="refresh" class="btn">Refresh</button>
      </div>
    </div>

    <div id="tasks-table"></div>
  </div>

  <div class="toast"></div>
</body>
</html>

