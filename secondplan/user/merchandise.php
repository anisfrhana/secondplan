<?php
include("../config/db.php");

$q=mysqli_query($conn,"SELECT * FROM merchandise_item");

while($r=mysqli_fetch_assoc($q)){
echo "
<img src='../uploads/{$r['image']}' width='100'><br>
{$r['itemName']}<br>
RM {$r['price']}<br><br>
";
}
?>

<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (isset($_GET['api']) && $_GET['api']==='list') {
header('Content-Type: application/json');
$rows = [];
$q = mysqli_query($conn, "SELECT merch_id AS id, itemName AS name, sku, price, stock, image FROM merchandise_item");
while($r = mysqli_fetch_assoc($q)){
$rows[] = [
'id' => (int)$r['id'],
'name' => $r['name'],
'sku' => $r['sku'] ?? '',
'price' => (float)$r['price'],
'stock' => (int)$r['stock'],
'image' => $r['image'] ? ('../uploads/'.$r['image']) : null,
];
}
echo json_encode(['success'=>true,'data'=>$rows]); exit;
}

// (existing HTML output below)
$q=mysqli_query($conn,"SELECT * FROM merchandise_item");
while($r=mysqli_fetch_assoc($q)){
echo "
<img src='../uploads/{$r['image']}' width='100'><br>
{$r['itemName']}<br>
RM {$r['price']}<br><br>
";
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User · Merchandise · SecondPlan</title>
  <link rel="stylesheet" href="assets/css/user.css">
  <script src="assets/js/user.js" defer></script>
</head>
<body data-page="merchandise">
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
      <div class="page-title">Merchandise</div>
      <div class="toolbar">
        <input id="search" class="search" placeholder="Search items…">
        <button id="refresh" class="btn">Refresh</button>
      </div>
    </div>

    <div id="items-table"></div>
  </div>

  <div class="toast"></div>
</body>
</html>