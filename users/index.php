<?php include '../includes/header.php'; ?>
<?php 



require '../auth/connection.php'; // Database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Fetch stats
$totalOrdersSql = "SELECT COUNT(*) AS total FROM neworders WHERE user_id = ?";
$pendingSql = "SELECT COUNT(*) AS total FROM neworders WHERE user_id = ? AND status='Pending'";
$deliveredSql = "SELECT COUNT(*) AS total FROM neworders WHERE user_id = ? AND status='Delivered'";
$activeTrackingsSql = "SELECT COUNT(*) AS total FROM neworders WHERE user_id = ? AND status='In Transit'";

$stmt = $conn->prepare($totalOrdersSql);
$stmt->bind_param("i", $user_id); $stmt->execute(); $totalOrders = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$stmt = $conn->prepare($pendingSql);
$stmt->bind_param("i", $user_id); $stmt->execute(); $pending = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$stmt = $conn->prepare($deliveredSql);
$stmt->bind_param("i", $user_id); $stmt->execute(); $delivered = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$stmt = $conn->prepare($activeTrackingsSql);
$stmt->bind_param("i", $user_id); $stmt->execute(); $activeTrackings = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

// Fetch recent 5 orders
$recentOrdersSql = "SELECT id, receiver_name, status, tracking_id, pickup_date 
                    FROM neworders 
                    WHERE user_id = ? 
                    ORDER BY id DESC 
                    LIMIT 5";
$stmt = $conn->prepare($recentOrdersSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentOrdersRes = $stmt->get_result();
$recentOrders = $recentOrdersRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>



<style>
/* Dashboard Cards */
.dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 2rem;
  padding: 2rem 5%;
}

.card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
  text-align: center;
  transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.card h2 {
  font-size: 2rem;
  color: #004aad;
}

.card p {
  font-size: 1rem;
  color: #555;
}

/* Recent Orders Table */
.recent-orders {
  margin: 2rem 5%;
  background: #fff;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.recent-orders table {
  width: 100%;
  border-collapse: collapse;
}

.recent-orders th, .recent-orders td {
  text-align: left;
  padding: 0.8rem;
  border-bottom: 1px solid #ddd;
}

.recent-orders th {
  background: #004aad;
  color: #fff;
  border-radius: 6px;
}

.recent-orders tr:hover {
  background: #f1f5f9;
}

/* Greeting */
.greeting {
  margin: 2rem 5%;
  font-size: 1.2rem;
  color: #333;
}
</style>

<div class="greeting">
<p>Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>!</p>
</div>

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
    <h2><?php echo $activeTrackings; ?></h2>
    <p>Active Trackings</p>
  </div>
</div>

<div class="recent-orders">
  <h3>Recent Orders</h3>
  <table>
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Recipient</th>
        <th>Status</th>
        <th>Tracking ID</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if($recentOrders): ?>
        <?php foreach($recentOrders as $order): ?>
          <tr>
            <td>#<?php echo $order['id']; ?></td>
            <td><?php echo htmlspecialchars($order['receiver_name']); ?></td>
            <td><?php echo htmlspecialchars($order['status']); ?></td>
            <td><?php echo htmlspecialchars($order['tracking_id']); ?></td>
            <td><?php echo htmlspecialchars($order['pickup_date']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
          <tr><td colspan="5" style="text-align:center;">No orders found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Highlight card on click
const cards = document.querySelectorAll('.card');
cards.forEach(card => {
  card.addEventListener('click', () => {
    cards.forEach(c => c.style.border = 'none');
    card.style.border = '2px solid #004aad';
  });
});
</script>
