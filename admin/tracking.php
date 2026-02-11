<?php
session_start();
require '../auth/connection.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

$searchResult = [];
$searchQuery = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';

/* ================= SEARCH LOGIC ================= */
if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT * FROM neworders WHERE tracking_id LIKE ? OR sender_name LIKE ? OR receiver_name LIKE ? ORDER BY id DESC");
    $likeQuery = "%".$searchQuery."%";
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM neworders ORDER BY id DESC");
}

while($row = $result->fetch_assoc()){
    $searchResult[] = $row;
}
if(isset($stmt)) $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Shipment Tracker</title>
<style>
    :root { 
        --primary: #0077b6; 
        --delivered: #d4edda; --delivered-text: #155724;
        --pending: #fff3cd; --pending-text: #856404;
        --transit: #cce5ff; --transit-text: #004085;
    }

    body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; background: #f0f4f8; color: #333; }
    .container { max-width: 95%; margin: 30px auto; background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    
    header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    h2 { margin: 0; color: var(--primary); font-size: 1.6rem; }

    .search-box { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; }
    .search-box input { padding: 10px 15px; width: 250px; border: none; outline: none; }
    .search-box button { padding: 0 20px; border: none; background: var(--primary); color: #fff; cursor: pointer; }

    table { width: 100%; border-collapse: collapse; }
    th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px 10px; border-bottom: 2px solid #edf2f7; text-align: left; }
    td { padding: 15px 10px; border-bottom: 1px solid #edf2f7; font-size: 0.85rem; vertical-align: top; }
    
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; display: inline-block; text-transform: uppercase; }
    .status-Delivered { background: var(--delivered); color: var(--delivered-text); }
    .status-Pending { background: var(--pending); color: var(--pending-text); }
    .status-In-Transit { background: var(--transit); color: var(--transit-text); }
    .status-Picked-Up { background: #e2e3e5; color: #383d41; }
    .status-Out-for-Delivery { background: #e2d9f3; color: #6f42c1; }

    .address-info { font-size: 0.75rem; color: #64748b; margin-top: 3px; }
    .tracking-code { font-family: monospace; background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-weight: bold; color: var(--primary); }
    .highlight { background: #fffceb !important; }
</style>
</head>
<body>

<div class="container">
    <header>
        <h2>📦 Shipment Tracker</h2>
        <form method="GET" class="search-box">
            <input type="text" name="tracking" placeholder="Search..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
        </form>
    </header>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sender & Pickup</th>
                    <th>Receiver & Package</th>
                    <th>Tracking ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($searchResult) > 0): ?>
                    <?php foreach($searchResult as $row): 
                        $statusClass = str_replace(' ', '-', $row['status']);
                        $isMatch = (!empty($searchQuery) && stripos($row['tracking_id'], $searchQuery) !== false);
                    ?>
                    <tr class="<?php echo $isMatch ? 'highlight' : ''; ?>">
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['sender_name']); ?></strong>
                            <div class="address-info">📍 <?php echo htmlspecialchars($row['pickup_address'] ?? 'N/A'); ?></div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['receiver_name']); ?></strong>
                            <div class="address-info">🏁 <?php echo htmlspecialchars($row['delivery_address']); ?></div>
                            <div class="address-info">📦 Type: <?php echo htmlspecialchars($row['package_type']); ?></div>
                        </td>
                        <td>
                            <span class="tracking-code"><?php echo htmlspecialchars($row['tracking_id']); ?></span>
                        </td>
                        <td>
                            <span class="badge status-<?php echo $statusClass; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px;">No shipments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>