<?php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$successMsg = $_SESSION['successMsg'] ?? "";
$errorMsg   = $_SESSION['errorMsg'] ?? "";
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_SESSION['user_id'];
    $sender_name = trim($_POST['sender_name']);
    $sender_phone = trim($_POST['sender_phone']);
    $sender_email = trim($_POST['sender_email']);
    $pickup_address = trim($_POST['pickup_address']);

    $receiver_name = trim($_POST['receiver_name']);
    $receiver_phone = trim($_POST['receiver_phone']);
    $receiver_email = trim($_POST['receiver_email']);
    $delivery_address = trim($_POST['delivery_address']);

    $package_type = $_POST['package_type'];
    $instructions = trim($_POST['instructions']);

    if (!$sender_name || !$receiver_name || !$pickup_address || !$delivery_address || !$package_type) {
        $_SESSION['errorMsg'] = "❌ All required fields must be filled.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $tracking_id = 'TRK' . strtoupper(substr(md5(uniqid()), 0, 8));
    $status = 'Pending';

    /* =====================================================
       AGENT ASSIGNMENT LOGIC (NO RANDOM)
       ===================================================== */

    // Active statuses (means agent is captured/busy)
    $activeStatuses = array('Pending','Assigned','In Transit');
    $statusesList = "'" . implode("','", $activeStatuses) . "'";

    // 1️⃣ First try: Find agent NOT currently assigned to active orders
    $agentRes = $conn->query(
        "SELECT id, email 
         FROM agents 
         WHERE id NOT IN (
             SELECT agent_id 
             FROM neworders 
             WHERE status IN ($statusesList) 
               AND agent_id IS NOT NULL
         )
         ORDER BY id ASC
         LIMIT 1"
    );

    // 2️⃣ If all agents are busy → assign serially (least assigned first)
    if (!$agentRes || $agentRes->num_rows === 0) {
        $agentRes = $conn->query(
            "SELECT a.id, a.email
             FROM agents a
             LEFT JOIN (
                 SELECT agent_id, COUNT(*) AS total_orders
                 FROM neworders
                 GROUP BY agent_id
             ) t ON a.id = t.agent_id
             ORDER BY COALESCE(t.total_orders,0) ASC, a.id ASC
             LIMIT 1"
        );
    }

    $agentData = $agentRes ? $agentRes->fetch_assoc() : null;
    $agent_id = $agentData['id'] ?? NULL;

    /* ===================================================== */

    $sql = "INSERT INTO neworders 
            (user_id, sender_name, sender_phone, sender_email, pickup_address, 
             receiver_name, receiver_phone, receiver_email, delivery_address, 
             package_type, instructions, status, tracking_id, agent_id) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "issssssssssssi",
        $user_id,
        $sender_name,
        $sender_phone,
        $sender_email,
        $pickup_address,
        $receiver_name,
        $receiver_phone,
        $receiver_email,
        $delivery_address,
        $package_type,
        $instructions,
        $status,
        $tracking_id,
        $agent_id
    );

    if ($stmt->execute()) {
        $_SESSION['successMsg'] = "✅ Order placed! Tracking ID: $tracking_id";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['errorMsg'] = "❌ Failed to save order.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Delivery Order - ShipManager</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: linear-gradient(135deg,#004aad,#0077b6);
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(15px);
    padding: 40px;
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    animation: fadeIn 0.8s ease, popIn 0.5s ease;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.9rem;
    font-weight: bold;
    background: linear-gradient(135deg,#004aad,#0077b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.form-group {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
}

label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #0B2E33;
    font-size: 0.95rem;
}

input, select, textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.2);
    font-size: 1rem;
    background: rgba(255,255,255,0.8);
    color: #0B2E33;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

input::placeholder, textarea::placeholder {
    color: #64748b;
}

textarea {
    height: 80px;
    resize: none;
}

select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background: rgba(255,255,255,0.8) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpolygon points='0,0 16,0 8,8' fill='%230077b6'/%3E%3C/svg%3E") no-repeat right 12px center;
    background-size: 12px;
    padding-right: 30px;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #004aad;
    background: #fff;
    box-shadow: 0 0 8px rgba(0,77,173,0.3);
}

button {
    width: 100%;
    background: linear-gradient(135deg,#004aad,#0077b6);
    color: #fff;
    border: none;
    padding: 15px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.3s ease;
}

button:hover {
    transform: scale(1.02);
    background: linear-gradient(135deg,#003580,#005f99);
}

.success-message, .error-message {
    margin-bottom: 15px;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
}

.success-message { background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
.error-message { background:#f8d7da; color:#842029; border:1px solid #f5c2c7; }

@keyframes fadeIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
@keyframes popIn { from { transform:scale(0.95); opacity:0; } to { transform:scale(1); opacity:1; } }
</style>
</head>
<body>

<div class="container">
<h2>Create New Delivery Order</h2>

<?php if($successMsg): ?><div class="success-message"><?php echo $successMsg; ?></div><?php endif; ?>
<?php if($errorMsg): ?><div class="error-message"><?php echo $errorMsg; ?></div><?php endif; ?>

<form method="POST" autocomplete="off">

  <!-- Sender Section -->
  <h3 style="color:#004aad; margin-bottom:10px; font-weight:600;">Sender Details</h3>
  <div class="form-group">
    <label>Sender Full Name*</label>
    <input type="text" name="sender_name" placeholder="Enter sender name" required>
  </div>
  <div class="form-group">
    <label>Sender Phone*</label>
    <input type="tel" name="sender_phone" placeholder="e.g. 9812345678" pattern="[0-9]{10}" required>
  </div>
  <div class="form-group">
    <label>Sender Email</label>
    <input type="email" name="sender_email" placeholder="Optional">
  </div>
  <div class="form-group">
    <label>Pickup Address*</label>
    <textarea name="pickup_address" placeholder="Enter pickup address" required></textarea>
  </div>

  <!-- Separator -->
  <hr style="border:0; border-top:2px solid rgba(0,77,173,0.3); margin:25px 0;">

  <!-- Receiver Section -->
  <h3 style="color:#004aad; margin-bottom:10px; font-weight:600;">Receiver Details</h3>
  <div class="form-group">
    <label>Receiver Full Name*</label>
    <input type="text" name="receiver_name" placeholder="Enter receiver name" required>
  </div>
  <div class="form-group">
    <label>Receiver Phone*</label>
    <input type="tel" name="receiver_phone" placeholder="e.g. 9812345678" pattern="[0-9]{10}" required>
  </div>
  <div class="form-group">
    <label>Receiver Email</label>
    <input type="email" name="receiver_email" placeholder="Optional">
  </div>
  <div class="form-group">
    <label>Delivery Address*</label>
    <textarea name="delivery_address" placeholder="Enter delivery address" required></textarea>
  </div>

  <!-- Package Section -->
  <div class="form-group">
    <label>Package Type*</label>
    <select name="package_type" required>
      <option value="">Select package type</option>
      <option value="Documents">Documents</option>
      <option value="Electronics">Electronics</option>
      <option value="Clothing">Clothing</option>
      <option value="Fragile">Fragile</option>
      <option value="Bulky">Bulky</option>
      <option value="Other">Other</option>
    </select>
  </div>

  <div class="form-group">
    <label>Special Instructions</label>
    <textarea name="instructions" placeholder="Optional instructions"></textarea>
  </div>

  <button type="submit">Place Order</button>
</form>
</div>

</body>
</html>