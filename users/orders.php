<?php
session_start();
require '../auth/connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $conn->prepare("
    SELECT 
        id,
        sender_name, sender_phone, sender_email, pickup_address,
        receiver_name, receiver_phone, receiver_email, delivery_address,
        package_type,
        tracking_id,
        status,
        created_at
    FROM neworders
    WHERE user_id = ?
    ORDER BY id DESC
");
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
    margin:0;
    padding:20px;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    min-height:100vh;
}
.container {
    max-width: 1300px;
    margin: auto;
    background: rgba(255,255,255,0.96);
    border-radius: 16px;
    padding: 25px;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    animation: fadeIn 0.8s ease forwards;
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
    min-width: 1200px;
}
th, td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #ddd;
    font-size: 0.9rem;
}
th {
    background: #0077b6;
    color: #fff;
    font-weight: 600;
}
tr:hover {
    background: #f1f5f9;
}

.status-delivered { color: #0f5132; font-weight: bold; }
.status-pending { color: #856404; font-weight: bold; }
.status-intransit { color: #055160; font-weight: bold; }

.contact {
    font-size: 0.85rem;
    color: #444;
}

.address {
    font-size: 0.85rem;
    line-height: 1.4;
    color: #333;
}

@keyframes fadeIn {
    from { opacity:0; transform:translateY(-15px); }
    to { opacity:1; transform:translateY(0); }
}
</style>
</head>

<body>
<div class="container">
<h2>My Order History</h2>

<?php if ($result->num_rows > 0): ?>
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Sender</th>
            <th>Sender Contact</th>
            <th>Pickup Address</th>
            <th>Receiver</th>
            <th>Receiver Contact</th>
            <th>Delivery Address</th>
            <th>Package</th>
            <th>Tracking ID</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $row['id']; ?></td>

            <td><?php echo htmlspecialchars($row['sender_name']); ?></td>
            <td class="contact">
                📞 <?php echo htmlspecialchars($row['sender_phone']); ?><br>
                ✉️ <?php echo $row['sender_email'] ?: '—'; ?>
            </td>

            <td class="address">
                <?php echo htmlspecialchars($row['pickup_address']); ?>
            </td>

            <td><?php echo htmlspecialchars($row['receiver_name']); ?></td>
            <td class="contact">
                📞 <?php echo htmlspecialchars($row['receiver_phone']); ?><br>
                ✉️ <?php echo $row['receiver_email'] ?: '—'; ?>
            </td>

            <td class="address">
                <?php echo htmlspecialchars($row['delivery_address']); ?>
            </td>

            <td><?php echo htmlspecialchars($row['package_type']); ?></td>
            <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>

            <td class="<?php
                if ($row['status'] === 'Delivered') echo 'status-delivered';
                elseif ($row['status'] === 'Pending') echo 'status-pending';
                else echo 'status-intransit';
            ?>">
                <?php echo $row['status']; ?>
            </td>

            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php else: ?>
    <p style="text-align:center; font-weight:bold;">No orders placed yet.</p>
<?php endif; ?>

</div>
</body>
</html>