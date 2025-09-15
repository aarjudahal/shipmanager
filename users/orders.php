<?php
session_start();
require '../auth/connection.php'; // DB connection

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch only this user’s orders
$stmt = $conn->prepare("SELECT * FROM neworders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - ShipManager</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; padding:20px;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    min-height:100vh;
}
.container {
    max-width: 1100px;
    margin: auto;
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    padding: 25px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    animation: fadeIn 1s ease forwards;
}
h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}
th {
    background: #0077b6;
    color: #fff;
    font-weight: 600;
}
tr:hover {background: #f1f1f1;}
.status-delivered {color:green;font-weight:bold;}
.status-pending {color:orange;font-weight:bold;}
.status-intransit {color:blue;font-weight:bold;}
@keyframes fadeIn {from {opacity:0; transform:translateY(-15px);} to {opacity:1; transform:translateY(0);}}
</style>
</head>
<body>
<div class="container">
<h2>My Orders</h2>
<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Sender</th>
            <th>Receiver</th>
            <th>Address</th>
            <th>Phone</th>
            <th>Package</th>
            <th>Weight (kg)</th>
            <th>Pickup Date</th>
            <th>Tracking ID</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
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
    <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center; font-weight:bold;">No orders placed yet.</p>
<?php endif; ?>
</div>
</body>
</html>
