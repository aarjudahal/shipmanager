<?php
session_start();
require '../auth/connection.php'; // Database connection

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Display messages once
$successMsg = $_SESSION['successMsg'] ?? "";
$errorMsg   = $_SESSION['errorMsg'] ?? "";
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $sender    = trim($_POST['sender']);
    $receiver  = trim($_POST['receiver']);
    $address   = trim($_POST['address']);
    $phone     = trim($_POST['phone']);
    $package   = trim($_POST['package']);
    $weight    = floatval($_POST['weight']);
    $date      = $_POST['date'];
    $user_id   = $_SESSION['user_id'];

  

    if ($sender && $receiver && $address && $phone && $package && $weight && $date) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $_SESSION['errorMsg'] = "❌ Invalid date format.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } elseif ($date < date('Y-m-d')) {
    $_SESSION['errorMsg'] = "❌ Pickup date cannot be in the past.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}else {
            // Generate tracking ID
            $tracking_id = 'TRK' . strtoupper(substr(md5(uniqid()), 0, 8));
            $status = 'Pending';

            // 🎯 Random Agent (fetch id + email)
            $agentRes = $conn->query("SELECT id, email FROM agents ORDER BY RAND() LIMIT 1");
            $agentData = $agentRes->fetch_assoc();
            $agent_id = $agentData['id'] ?? NULL;
            $agent_email = $agentData['email'] ?? NULL;

            // Save Order
   $sql = "INSERT INTO neworders 
(user_id, sender_name, receiver_name, delivery_address, receiver_phone, package_type, weight, pickup_date, status, tracking_id, agent_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isssssdsssi",
    $user_id, $sender, $receiver, $address, $phone, $package, $weight,
    $date, $status, $tracking_id, $agent_id
);




            if ($stmt->execute()) {
                $_SESSION['successMsg'] = "✅ Order placed successfully! Tracking ID: $tracking_id";

                // 📧 Send Email using mail()
              
if ($agent_email) {
    $to = $agent_email;
    $subject = "📦 New Delivery Assigned - Tracking ID $tracking_id";

    $message = "
    <div style='font-family:Arial, sans-serif; background:#f4f6f8; padding:20px;'>
      <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);'>
        
        <div style='background:#004aad; color:#ffffff; padding:15px 20px; font-size:18px; font-weight:bold;'>
          📦 New Delivery Assigned
        </div>
        
        <div style='padding:20px; color:#333;'>
          <p style='font-size:15px;'>Hello Agent,</p>
          <p style='font-size:15px;'>You have been assigned a new delivery. Here are the details:</p>
          
          <table style='width:100%; border-collapse:collapse; font-size:14px;'>
            <tr><td style='padding:8px; font-weight:bold;'>Sender:</td><td>$sender</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Receiver:</td><td>$receiver</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Phone:</td><td>$phone</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Address:</td><td>$address</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Package:</td><td>$package ($weight kg)</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Pickup Date:</td><td>$date</td></tr>
            <tr><td style='padding:8px; font-weight:bold;'>Tracking ID:</td><td><b style='color:#004aad;'>$tracking_id</b></td></tr>
          </table>
          
          <p style='margin-top:20px; font-size:15px;'>Please check your 
          <a href='http://localhost/delivery/users/orders.php' style='color:#004aad; text-decoration:none; font-weight:bold;'>dashboard</a> 
          for more details.</p>
        </div>
        
        <div style='background:#f1f5f9; padding:12px; text-align:center; font-size:13px; color:#555;'>
          ShipManager &copy; " . date("Y") . "
        </div>
        
      </div>
    </div>
    ";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ShipManager <no-reply@shipmanager.com>" . "\r\n";

    if (!mail($to, $subject, $message, $headers)) {
        $_SESSION['errorMsg'] = "⚠️ Order saved, but email failed to send.";
    }
}


                // 🚀 Redirect after success (avoid resubmission)
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['errorMsg'] = "❌ Failed to place order.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } else {
        $_SESSION['errorMsg'] = "❌ All fields are required.";
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
<title>New Order - ShipManager</title>
<style>
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    margin:0; 
    background: linear-gradient(135deg,#004aad,#0077b6); 
    display:flex; 
    justify-content:center; 
    align-items:center; 
    min-height:100vh; 
    padding:20px; 
}
.container {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(15px);
    padding:40px;
    border-radius:20px;
    border:1px solid rgba(255,255,255,0.3);
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
    max-width:600px;
    width:100%;
    margin:0 auto;
    animation:fadeIn 0.8s ease, popIn 0.5s ease;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.container:hover {
    transform: translateY(-4px);
    box-shadow:0 15px 30px rgba(0,0,0,0.25);
}
.container h2 {
    text-align:center;
    margin-bottom:25px;
    font-size:1.9rem;
    font-weight:bold;
    background: linear-gradient(135deg,#004aad,#0077b6);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.form-group {
    margin-bottom:20px; 
    display:flex; 
    flex-direction:column;
}
label {
    font-weight:600; 
    margin-bottom:8px; 
    color:#222; 
    font-size:0.95rem;
}
input, select, textarea {
    width:100%; 
    min-height:44px; 
    padding:12px 14px; 
    border:1px solid #ccc; 
    border-radius:10px; 
    font-size:1rem; 
    background:#f9f9f9; 
    transition: all 0.3s ease; 
    box-sizing:border-box;
}
textarea { resize:none; height:90px; }
select {
    appearance:none; -webkit-appearance:none; -moz-appearance:none;
    background:#f9f9f9 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpolygon points='0,0 16,0 8,8' fill='%230077b6'/%3E%3C/svg%3E") no-repeat right 12px center;
    background-size:12px;
    padding-right:30px;
}
input:focus, select:focus, textarea:focus { border-color:#0077b6; outline:none; background:#fff; box-shadow:0 0 8px rgba(0,119,182,0.3);}
button {
    width:100%; 
    background:linear-gradient(135deg,#004aad,#0077b6); 
    color:#fff; 
    border:none; 
    padding:15px; 
    border-radius:12px; 
    font-size:1rem; 
    font-weight:bold; 
    cursor:pointer; 
    transition: transform 0.2s ease, background 0.3s ease;
}
button:hover { background:linear-gradient(135deg,#003580,#005f99); transform:scale(1.02);}
.success-message, .error-message {
    margin-top:15px; 
    padding:12px; 
    border-radius:8px; 
    text-align:center; 
    font-weight:500;
}
.success-message { background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
.error-message { background:#f8d7da; color:#842029; border:1px solid #f5c2c7; }
@keyframes fadeIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
@keyframes popIn { from { transform:scale(0.95); opacity:0; } to { transform:scale(1); opacity:1; } }
@media(max-width:600px){.container{padding:30px 20px}.container h2{font-size:1.5rem}}
</style>
</head>
<body>
<div class="container">
<h2>Create New Order</h2>

<?php if($successMsg): ?>
  <div class="success-message" style="display:block;"><?php echo $successMsg; ?></div>
<?php endif; ?>
<?php if($errorMsg): ?>
  <div class="error-message" style="display:block;"><?php echo $errorMsg; ?></div>
<?php endif; ?>

<form id="orderForm" method="POST" autocomplete="off">
  <div class="form-group"><label for="sender">Sender Name</label><input type="text" id="sender" name="sender" required></div>
  <div class="form-group"><label for="receiver">Receiver Name</label><input type="text" id="receiver" name="receiver" required></div>
  <div class="form-group"><label for="address">Delivery Address (From-To)</label><textarea id="address" name="address" required></textarea></div>
  <div class="form-group"><label for="phone">Receiver Phone</label><input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="e.g. 9812345678" required></div>
  <div class="form-group"><label for="package">Package Type</label><select id="package" name="package" required>
    <option value="">Select Package</option>
    <option value="Documents">Documents</option>
    <option value="Electronics">Electronics</option>
    <option value="Clothing">Clothing</option>
    <option value="Fragile">Fragile</option>
    <option value="Other">Other</option>
  </select></div>
  <div class="form-group"><label for="weight">Weight (kg)</label><input type="number" id="weight" name="weight" step="0.1" required></div>
  <div class="form-group"><label for="date">Pickup Date</label><input type="date" id="date" name="date" required></div>
  <button type="submit">Place Order</button>
</form>
</div>
</body>
</html>
