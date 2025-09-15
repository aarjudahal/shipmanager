<?php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Handle status update via POST
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status   = $_POST['status'];

    // 1. Update status
    $stmt = $conn->prepare("UPDATE neworders SET status=? WHERE id=?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();

    // 2. Get user's email + details
    $sql = "SELECT u.email, o.tracking_id, o.sender_name, o.receiver_name
            FROM users u
            JOIN neworders o ON u.id = o.user_id
            WHERE o.id = ?";
    $getUser = $conn->prepare($sql);
    if ($getUser === false) {
        die("SQL Error: " . $conn->error);
    }
    $getUser->bind_param("i", $order_id);
    $getUser->execute();
    $getUser->bind_result($email, $tracking_id, $sender_name, $receiver_name);
    $getUser->fetch();
    $getUser->close();

    if ($email) {
        // 3. Prepare email content
        $subject = "Order Status Update - Tracking ID: $tracking_id";

        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f8fb; margin:0; padding:20px; }
                .email-container { max-width:600px; margin:auto; background:#ffffff; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.1); padding:25px; }
                h2 { color:#0077b6; }
                .status-box { background:#e0f7fa; border-left:5px solid #0077b6; padding:15px; margin:20px 0; font-size:16px; }
                .footer { text-align:center; margin-top:30px; font-size:12px; color:#666; }
                .btn { display:inline-block; padding:10px 20px; background:#0077b6; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; }
                .btn:hover { background:#005f99; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <h2>📦Order Status Update</h2>
                <p>Dear Customer,</p>
                <p>Your order with <strong>Tracking ID: $tracking_id</strong> has been updated.</p>
                
                <div class='status-box'>
                    Current Status: <strong>$status</strong>
                </div>

                <p><b>Sender:</b> $sender_name<br>
                <b>Receiver:</b> $receiver_name</p>

                <p>You can track your package using the button below:</p>
                <p><a href='http://localhost/delivery/admin/tracking.php?tid=$tracking_id' class='btn'>Track My Order</a></p>

                <div class='footer'>
                    &copy; " . date("Y") . " ShipManager. All rights reserved.
                </div>
            </div>
        </body>
        </html>";

        // 4. Set headers
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: ShipManager <no-reply@shipmanager.com>" . "\r\n";

        // 5. Send email
        if (mail($email, $subject, $message, $headers)) {
            // success (you can show a message if you want)
            // echo "Mail sent successfully";
        } else {
            echo "Mail could not be sent.";
        }
    }
}


// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch orders
if ($search !== '') {
    $searchSql = "SELECT * FROM neworders 
                    WHERE sender_name LIKE ? 
                       OR receiver_name LIKE ? 
                       OR tracking_id LIKE ? 
                  ORDER BY 
                    CASE 
                      WHEN sender_name LIKE ? THEN 1
                      WHEN receiver_name LIKE ? THEN 2
                      WHEN tracking_id LIKE ? THEN 3
                      ELSE 4 
                    END, id DESC";
    $stmt = $conn->prepare($searchSql);
    $likeSearch = "%".$search."%";
    $stmt->bind_param("ssssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $ordersSql = "SELECT * FROM neworders ORDER BY id DESC";
    $orders = $conn->query($ordersSql);
}

// Auto-generate tracking IDs if empty
while ($row = $orders->fetch_assoc()) {
    if (empty($row['tracking_id'])) {
        $tracking_id = "TRK".date("Ymd")."-".$row['id'];
        $update = $conn->prepare("UPDATE neworders SET tracking_id=? WHERE id=?");
        $update->bind_param("si", $tracking_id, $row['id']);
        $update->execute();
    }
}
// Re-fetch updated orders after tracking ID generation
if ($search !== '') {
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query($ordersSql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - Admin Panel</title>
<style>
/* GENERAL BODY */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; padding:0; 
    background: #e0f0ff;
}

/* CONTAINER CARD */
.container {
    max-width: 1200px;
    margin: 0px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* TITLE */
h2 {
    text-align:center;
    font-size:2.2rem;
    margin-bottom:25px;
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* SEARCH BOX */
.search-box {
    margin-bottom: 20px;
    display: flex;
    justify-content: flex-end;
}
.search-box input {
    padding: 8px 12px;
    border-radius: 8px 0 0 8px;
    border: 1px solid #ccc;
    width: 250px;
}
.search-box button {
    padding: 8px 15px;
    border:none;
    background: #0077b6;
    color:#fff;
    font-weight:bold;
    border-radius:0 8px 8px 0;
    cursor:pointer;
    transition: 0.3s;
}
.search-box button:hover {
    background: #005f99;
}

/* TABLE STYLING */
table {
    width:100%;
    border-collapse: collapse;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
th, td {
    padding:14px 10px;
    text-align:center;
    border-bottom: 1px solid #eee;
}
th {
    background: linear-gradient(135deg, #0077b6, #00b4d8);
    color:#fff;
    font-weight:600;
    text-transform: uppercase;
}
tr:hover {
    background:#f5faff;
}

/* STATUS STYLING */
.status-delivered { color:green; font-weight:bold; }
.status-pending { color:orange; font-weight:bold; }
.status-intransit { color:blue; font-weight:bold; }

/* BUTTONS */
button.update-btn {
    padding:6px 12px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    background: #0077b6;
    color:#fff;
    transition:0.3s;
}
button.update-btn:hover {
    background: #005f99;
}

select {
    padding:5px 8px;
    border-radius:8px;
    border:1px solid #ccc;
}

/* HIGHLIGHTING */
.highlight { 
    background: yellow; 
    font-weight: bold; 
    padding: 2px 4px; 
    border-radius: 3px;
}

/* RESPONSIVE */
@media(max-width:992px){
    .search-box{flex-direction:column; align-items:flex-end; gap:10px;}
    table, th, td{font-size:0.85rem;}
}
</style>
</head>
<body>
<div class="container">
<h2>Manage Orders</h2>

<form method="GET" class="search-box">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by sender, receiver or tracking ID">
    <button type="submit" class="update-btn">Search</button>
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
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if($orders->num_rows > 0){
        while($row = $orders->fetch_assoc()):

            // Highlight search words
            $sender = $receiver = $tracking = '';
            if($search !== ''){
                $sender = str_ireplace($search, "<span class='highlight'>".$search."</span>", htmlspecialchars($row['sender_name']));
                $receiver = str_ireplace($search, "<span class='highlight'>".$search."</span>", htmlspecialchars($row['receiver_name']));
                $tracking = str_ireplace($search, "<span class='highlight'>".$search."</span>", htmlspecialchars($row['tracking_id']));
            } else {
                $sender = htmlspecialchars($row['sender_name']);
                $receiver = htmlspecialchars($row['receiver_name']);
                $tracking = htmlspecialchars($row['tracking_id']);
            }
    ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $sender; ?></td>
            <td><?php echo $receiver; ?></td>
            <td><?php echo htmlspecialchars($row['delivery_address']); ?></td>
            <td><?php echo htmlspecialchars($row['receiver_phone']); ?></td>
            <td><?php echo htmlspecialchars($row['package_type']); ?></td>
            <td><?php echo $row['weight']; ?></td>
            <td><?php echo $row['pickup_date']; ?></td>
            <td><?php echo $tracking; ?></td>
            <td class="<?php
                if($row['status']=='Delivered') echo 'status-delivered';
                elseif($row['status']=='Pending') echo 'status-pending';
                else echo 'status-intransit';
            ?>"><?php echo $row['status']; ?></td>
            <td>
                <form method="POST" style="display:flex; gap:5px; justify-content:center;">
                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                    <select name="status">
                        <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                        <option value="In Transit" <?php if($row['status']=='In Transit') echo 'selected'; ?>>In Transit</option>
                        <option value="Delivered" <?php if($row['status']=='Delivered') echo 'selected'; ?>>Delivered</option>
                    </select>
                    <button type="submit" name="update_status" class="update-btn">Update</button>
                </form>
            </td>
        </tr>
    <?php
        endwhile;
    } else {
        echo "<tr><td colspan='11' style='text-align:center;font-weight:bold;'>No orders found.</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>
</body>
</html>
