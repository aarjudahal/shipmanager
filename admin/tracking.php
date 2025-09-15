<?php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

$searchResult = [];
$searchQuery = '';

if(isset($_GET['tracking'])){
    $searchQuery = trim($_GET['tracking']);
    $stmt = $conn->prepare("SELECT * FROM neworders WHERE tracking_id LIKE ?");
    $likeQuery = "%".$searchQuery."%";
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $searchResult[] = $row;
    }
    $stmt->close();
}

// Fetch all orders if no search
if(empty($searchQuery)){
    $result = $conn->query("SELECT * FROM neworders ORDER BY id DESC");
    while($row = $result->fetch_assoc()){
        $searchResult[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Shipments - Admin Panel</title>
<style>
body {
    font-family:'Segoe UI', Tahoma, Geneva, Verdana,sans-serif;
    margin:0; padding:0; background:#f0f4f8;
}
.container {
    max-width:1200px; margin:40px auto; padding:30px;
    background:#fff; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.1);
}
h2 {
    text-align:center; font-size:2.2rem; margin-bottom:30px;
    background: linear-gradient(135deg,#0077b6,#00b4d8); -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.search-box {
    display:flex; justify-content:flex-end; margin-bottom:20px;
}
.search-box input {
    padding:8px 12px; border-radius:8px 0 0 8px; border:1px solid #ccc; width:250px;
}
.search-box button {
    padding:8px 15px; border:none; border-radius:0 8px 8px 0;
    background:#0077b6; color:#fff; font-weight:bold; cursor:pointer; transition:0.3s;
}
.search-box button:hover { background:#005f99; }
table {
    width:100%; border-collapse:collapse; box-shadow:0 5px 15px rgba(0,0,0,0.05);
}
th, td {
    padding:14px 10px; text-align:center; border-bottom:1px solid #eee;
}
th {
    background: linear-gradient(135deg,#0077b6,#00b4d8); color:#fff; font-weight:600;
}
tr:hover { background:#f5faff; }
.status-delivered { color:green; font-weight:bold; }
.status-pending { color:orange; font-weight:bold; }
.status-intransit { color:blue; font-weight:bold; }
.highlight { background: #fff3b0 !important; font-weight:bold; }
@media(max-width:768px){ .search-box{flex-direction:column; align-items:flex-end; gap:10px;} table, th, td{font-size:0.85rem;} }
</style>
</head>
<body>
<div class="container">
<h2>Track Shipments</h2>

<form method="GET" class="search-box">
    <input type="text" name="tracking" placeholder="Enter Tracking ID" value="<?php echo htmlspecialchars($searchQuery); ?>">
    <button type="submit">Search</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Sender</th>
            <th>Receiver</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Package</th>
            <th>Weight</th>
            <th>Pickup Date</th>
            <th>Tracking ID</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php if(count($searchResult) > 0): ?>
        <?php foreach($searchResult as $row): ?>
        <tr class="<?php echo (stripos($row['tracking_id'],$searchQuery)!==false)?'highlight':''; ?>">
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['sender_name']); ?></td>
            <td><?php echo htmlspecialchars($row['receiver_name']); ?></td>
            <td><?php echo htmlspecialchars($row['delivery_address']); ?></td>
            <td><?php echo htmlspecialchars($row['receiver_phone']); ?></td>
            <td><?php echo htmlspecialchars($row['package_type']); ?></td>
            <td><?php echo $row['weight']; ?></td>
            <td><?php echo $row['pickup_date']; ?></td>
            <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>
            <td class="<?php
                if($row['status']=='Delivered') echo 'status-delivered';
                elseif($row['status']=='Pending') echo 'status-pending';
                else echo 'status-intransit';
            ?>"><?php echo $row['status']; ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="10" style="text-align:center; font-weight:bold;">No shipments found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<script>
// Optional: Auto-focus on search input
document.querySelector('input[name="tracking"]').focus();
</script>
</body>
</html>
