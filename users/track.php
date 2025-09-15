<?php
// filepath: c:\xampp\htdocs\delivery\users\track.php
session_start();
require '../auth/connection.php';
 // Database connection
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$searchResult = null;

if (isset($_GET['tracking'])) {
    $trackingNo = trim($_GET['tracking']);
    $sql = "SELECT * FROM neworders WHERE tracking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $trackingNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResult = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order - ShipManager</title>
<style>
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #004aad, #0077b6);
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.container {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(15px);
    max-width: 700px;
    width: 95%;
    margin: 50px auto;
    padding: 30px 40px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    animation: fadeIn 0.8s ease, popIn 0.5s ease;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 2rem;
    background: linear-gradient(135deg, #004aad, #0077b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.search-box {
    display: flex;
    justify-content: center;
    margin-bottom: 25px;
}

.search-box input {
    width: 60%;
    padding: 12px 15px;
    border-radius: 10px 0 0 10px;
    border: 1px solid #ccc;
    font-size: 1rem;
}

.search-box button {
    padding: 12px 20px;
    border: none;
    border-radius: 0 10px 10px 0;
    background: linear-gradient(135deg, #004aad, #0077b6);
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, background 0.3s;
}

.search-box button:hover {
    background: linear-gradient(135deg, #003580, #005f99);
    transform: scale(1.05);
}

.order-info {
    margin-top: 20px;
}

.order-info table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.order-info th, .order-info td {
    padding: 12px 15px;
    border: 1px solid #ccc;
    text-align: left;
}

.order-info th {
    background: linear-gradient(135deg, #004aad, #0077b6);
    color: #fff;
}

.no-result {
    text-align: center;
    font-size: 1.1rem;
    color: #842029;
    background: #f8d7da;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #f5c2c7;
    margin-top: 15px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes popIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@media (max-width: 768px) {
    .search-box input {
        width: 100%;
        margin-bottom: 10px;
        border-radius: 10px;
    }
    .search-box button {
        width: 100%;
        border-radius: 10px;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>Track Your Order</h2>
    <form class="search-box" method="GET">
        <input type="text" name="tracking" placeholder="Enter Tracking Number" required>
        <button type="submit">Track</button>
    </form>

    <div class="order-info">
        <?php if ($searchResult): ?>
            <table>
                <tr><th>Sender Name</th><td><?php echo htmlspecialchars($searchResult['sender_name']); ?></td></tr>
                <tr><th>Receiver Name</th><td><?php echo htmlspecialchars($searchResult['receiver_name']); ?></td></tr>
                <tr><th>Delivery Address</th><td><?php echo htmlspecialchars($searchResult['delivery_address']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($searchResult['receiver_phone']); ?></td></tr>
                <tr><th>Package Type</th><td><?php echo htmlspecialchars($searchResult['package_type']); ?></td></tr>
                <tr><th>Weight (kg)</th><td><?php echo htmlspecialchars($searchResult['weight']); ?></td></tr>
                <tr><th>Pickup Date</th><td><?php echo htmlspecialchars($searchResult['pickup_date']); ?></td></tr>
                <tr><th>Status</th><td><?php echo htmlspecialchars($searchResult['status'] ?? 'Pending'); ?></td></tr>
            </table>
        <?php elseif (isset($_GET['tracking'])): ?>
            <div class="no-result">❌ No order found with this tracking number.</div>
        <?php endif; ?>
    </div>
</div>

<script>
// Optional: Focus on input after page load
document.querySelector('input[name="tracking"]').focus();
</script>
</body>
</html>
