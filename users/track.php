<?php
// filepath: c:\xampp\htdocs\delivery\users\track.php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$searchResult = null;
$currentStep = 0; // Default: No steps completed

// Define your status flow
$statuses = ["Pending", "Picked Up", "In Transit", "Out for Delivery", "Delivered"];

if (isset($_GET['tracking'])) {
    $trackingNo = trim($_GET['tracking']);
    $sql = "SELECT * FROM neworders WHERE tracking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $trackingNo);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResult = $result->fetch_assoc();
    $stmt->close();

    if ($searchResult) {
        // Find which index the current status is at
        $dbStatus = $searchResult['status'] ?? 'Pending';
        $currentStep = array_search($dbStatus, $statuses);
        if ($currentStep === false) $currentStep = 0; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Real-time Track - ShipManager</title>
<style>
    :root {
        --primary-color: #004aad;
        --secondary-color: #0077b6;
        --bg-gradient: linear-gradient(135deg, #004aad, #0077b6);
        --gray-light: #e0e0e0;
        --success-color: #28a745;
    }

    body {
        font-family: "Segoe UI", sans-serif;
        margin: 0;
        background: #f4f7f6;
        display: flex;
        justify-content: center;
        padding-top: 50px;
    }

    .container {
        background: #fff;
        max-width: 800px;
        width: 90%;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    h2 { text-align: center; color: var(--primary-color); }

    /* Search Box */
    .search-box { display: flex; margin-bottom: 40px; }
    .search-box input {
        flex: 1; padding: 15px; border: 2px solid var(--gray-light);
        border-radius: 10px 0 0 10px; outline: none;
    }
    .search-box button {
        padding: 15px 25px; border: none; background: var(--bg-gradient);
        color: white; font-weight: bold; border-radius: 0 10px 10px 0; cursor: pointer;
    }

    /* --- THE TIMELINE CSS --- */
    .timeline-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        margin: 40px 0;
    }

    /* The connecting line */
    .timeline-container::before {
        content: '';
        position: absolute;
        top: 25%;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--gray-light);
        z-index: 1;
    }

    /* The progress line */
    .progress-line {
        position: absolute;
        top: 25%;
        left: 0;
        height: 4px;
        background: var(--success-color);
        z-index: 1;
        transition: width 0.5s ease-in-out;
        width: <?php echo ($currentStep / (count($statuses) - 1)) * 100; ?>%;
    }

    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }

    .circle {
        width: 35px;
        height: 35px;
        background: #fff;
        border: 4px solid var(--gray-light);
        border-radius: 50%;
        margin: 0 auto 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        transition: 0.3s;
    }

    .step.active .circle {
        border-color: var(--success-color);
        background: var(--success-color);
        color: white;
    }

    .step.active .label {
        color: var(--success-color);
        font-weight: bold;
    }

    .label { font-size: 0.85rem; color: #777; }

    /* Details Table */
    .details-card {
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .info-item label { color: #888; font-size: 0.8rem; display: block; }
    .info-item span { font-weight: 600; color: #333; }

    @media (max-width: 600px) {
        .timeline-container { flex-direction: column; align-items: flex-start; gap: 20px; }
        .timeline-container::before, .progress-line { display: none; }
        .step { display: flex; align-items: center; gap: 15px; }
        .circle { margin: 0; }
    }
</style>
</head>
<body>

<div class="container">
    <h2>Track Your Shipment</h2>
    
    <form class="search-box" method="GET">
        <input type="text" name="tracking" placeholder="Enter Tracking ID (e.g. SHIP123)" required 
               value="<?php echo isset($_GET['tracking']) ? htmlspecialchars($_GET['tracking']) : ''; ?>">
        <button type="submit">Track</button>
    </form>

    <?php if ($searchResult): ?>
        <div class="timeline-container">
            <div class="progress-line"></div>
            <?php foreach ($statuses as $index => $statusText): ?>
                <div class="step <?php echo ($index <= $currentStep) ? 'active' : ''; ?>">
                    <div class="circle">
                        <?php if ($index < $currentStep): ?> ✓ <?php else: echo $index + 1; endif; ?>
                    </div>
                    <div class="label"><?php echo $statusText; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="details-card">
            <h3>Package Information</h3>
            <div class="info-grid">
                <div class="info-item"><label>Sender</label><span><?php echo htmlspecialchars($searchResult['sender_name']); ?></span></div>
                <div class="info-item"><label>Receiver</label><span><?php echo htmlspecialchars($searchResult['receiver_name']); ?></span></div>
                <div class="info-item"><label>Destination</label><span><?php echo htmlspecialchars($searchResult['delivery_address']); ?></span></div>
                <div class="info-item"><label>Package</label><span><?php echo htmlspecialchars($searchResult['package_type']); ?></span></div>
                <div class="info-item"><label>Current Status</label><span style="color:var(--success-color)"><?php echo htmlspecialchars($dbStatus); ?></span></div>
            </div>
        </div>

    <?php elseif (isset($_GET['tracking'])): ?>
        <div style="text-align:center; color: #d9534f; padding: 20px; background: #fdf7f7; border-radius: 10px;">
            No records found for that ID. Please check and try again.
        </div>
    <?php endif; ?>
</div>

</body>
</html>