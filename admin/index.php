<?php
session_start();
require '../auth/connection.php';

// Restrict to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Fetch counts dynamically
$totalOrdersSql = "SELECT COUNT(*) AS total FROM neworders";
$pendingSql = "SELECT COUNT(*) AS total FROM neworders WHERE status='Pending'";
$deliveredSql = "SELECT COUNT(*) AS total FROM neworders WHERE status='Delivered'";
$activeTrackingSql = "SELECT COUNT(*) AS total FROM neworders WHERE status='In Transit'";

$totalOrders = $conn->query($totalOrdersSql)->fetch_assoc()['total'];
$pending = $conn->query($pendingSql)->fetch_assoc()['total'];
$delivered = $conn->query($deliveredSql)->fetch_assoc()['total'];
$activeTracking = $conn->query($activeTrackingSql)->fetch_assoc()['total'];

// Fetch recent orders
$recentOrdersSql = "SELECT * FROM neworders ORDER BY id DESC LIMIT 5";
$recentOrders = $conn->query($recentOrdersSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - ShipManager</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin:0; padding:0;
    background: #f0f4f8;
}

/* Navbar */
.navbar {
    display:flex;
    justify-content: space-between;
    align-items: center;
    background: #004aad;
    padding: 15px 30px;
    color: #fff;
}
.navbar h1 { margin:0; font-size:1.8rem; }
.navbar ul {
    list-style:none;
    display:flex;
    margin:0; padding:0;
    gap: 20px;
}
.navbar ul li a {
    color:#fff;
    text-decoration:none;
    font-weight:500;
    transition: color 0.3s;
}
.navbar ul li a:hover { color:#00b4d8; }

/* Container */
.container { max-width: 1200px; margin:auto; padding:30px; }
h2 { text-align:center; font-size:2rem; margin-bottom:20px; color:#004aad; }

/* Dashboard Cards */
.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 2rem;
    margin-bottom: 30px;
}
.card {
    background: #fff;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
.card h2 { font-size:2rem; color:#0077b6; margin-bottom:10px; }
.card p { font-size:1.1rem; color:#555; }

/* Recent Orders Table */
.recent-orders { background:#fff; padding:20px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
.recent-orders h3 { margin-bottom:15px; color:#004aad; }
.recent-orders table { width:100%; border-collapse: collapse; }
.recent-orders th, .recent-orders td { padding:12px 15px; border-bottom:1px solid #ddd; text-align:center; }
.recent-orders th { background:#0077b6; color:#fff; border-radius:6px; }
.status-delivered { color:green; font-weight:bold; }
.status-pending { color:orange; font-weight:bold; }
.status-intransit { color:blue; font-weight:bold; }

/* Logout Modal */

.modal {
  display: none;   /* ✅ by default hidden */
  position: fixed;
  top:0; left:0;
  width:100%; height:100%;
  background: rgba(0,0,0,0.6);
  align-items:center;
  justify-content:center;
  z-index:1000;
}

.modal-content {
  background:#fff;
  padding:25px;
  border-radius:12px;
  text-align:center;
  width:90%;
  max-width:400px;
  box-shadow:0 8px 25px rgba(0,0,0,0.2);
  animation: fadeIn 0.3s ease;
}
.modal-content h3 {
  margin:0 0 10px;
  color:#004aad;
}
.modal-content p {
  margin:0 0 20px;
  font-size:15px;
  color:#444;
}
.modal-buttons {
  display:flex;
  justify-content:space-around;
  gap:15px;
}
.btn {
  padding:10px 20px;
  border:none;
  border-radius:8px;
  font-weight:600;
  cursor:pointer;
  text-decoration:none;
  display:inline-block;
}
.btn.yes {
  background:#004aad;
  color:#fff;
}
.btn.no {
  background:#ccc;
  color:#000;
}
.btn.yes:hover { background:#0066cc; }
.btn.no:hover { background:#aaa; }
@keyframes fadeIn {
  from {opacity:0; transform:scale(0.9);}
  to {opacity:1; transform:scale(1);}
}

/* Responsive */
@media(max-width:768px){
    .dashboard{grid-template-columns:1fr;}
    .navbar ul { flex-direction: column; gap:10px; margin-top:10px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h1>ShipManager Admin</h1>
    <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="agents.php">Agents</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="tracking.php">Tracking</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="#" onclick="openLogoutModal()">Logout</a></li>
    </ul>
</div>

<div class="container">
    <h2>Admin Dashboard</h2>
    <div class="dashboard">
        <div class="card">
            <h2><?php echo $totalOrders; ?></h2>
            <p>Total Orders</p>
        </div>
        <div class="card">
            <h2><?php echo $pending; ?></h2>
            <p>Pending Deliveries</p>
        </div>
        <div class="card">
            <h2><?php echo $delivered; ?></h2>
            <p>Delivered Orders</p>
        </div>
        <div class="card">
            <h2><?php echo $activeTracking; ?></h2>
            <p>Active Trackings</p>
        </div>
    </div>

    <div class="recent-orders">
        <h3>Recent Orders</h3>
        <?php if($recentOrders->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Tracking ID</th>
                    <th>Status</th>
                    <th>Pickup Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $recentOrders->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['sender_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['receiver_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['tracking_id']); ?></td>
                    <td class="<?php 
                        if($row['status']=='Delivered') echo 'status-delivered';
                        elseif($row['status']=='Pending') echo 'status-pending';
                        else echo 'status-intransit';
                    ?>"><?php echo $row['status']; ?></td>
                    <td><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center; font-weight:bold;">No recent orders.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="modal-buttons">
      <a href="../auth/admin_logout.php" class="btn yes">Yes</a>
      <button onclick="closeLogoutModal()" class="btn no">No</button>
    </div>
  </div>
</div>

<script>
function openLogoutModal() {
  document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
  document.getElementById('logoutModal').style.display = 'none';
}

</script>

</body>
</html>
