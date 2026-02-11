<?php
session_start();
require '../auth/connection.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

/* ================= DELETE LOGIC (Single & Bulk) ================= */
if (isset($_POST['delete_single'])) {
    $id = (int)$_POST['order_id'];

    // 1. Check status before deleting to see if we need to send a cancellation mail
    $stmt = $conn->prepare("SELECT u.email, u.name, o.tracking_id, o.status FROM users u 
                            JOIN neworders o ON u.id = o.user_id WHERE o.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $current_status = $res['status'];
        
        // 2. If Pending, send Cancellation Email
        if ($current_status === 'Pending') {
            $email = $res['email'];
            $user_name = $res['name'];
            $track_id = $res['tracking_id'];

            $subject = "Order Cancelled: $track_id";
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;'>
                <div style='background: #dc3545; padding: 20px; text-align: center; color: white;'>
                    <h2 style='margin:0;'>ShipManager Logistics</h2>
                </div>
                <div style='padding: 25px; color: #444;'>
                    <p>Hi <strong>$user_name</strong>,</p>
                    <p>We regret to inform you that your delivery request has been <strong>Cancelled</strong> and removed from our system.</p>
                    <div style='background: #fff5f5; border: 1px dashed #dc3545; padding: 15px; text-align: center; margin: 20px 0;'>
                        <span style='font-size: 14px; color: #666;'>Tracking ID</span><br>
                        <strong style='font-size: 22px; color: #dc3545;'>$track_id</strong>
                    </div>
                    <p>If you believe this was a mistake, please contact our support team immediately.</p>
                </div>
            </div>";

            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: ShipManager <no-reply@shipmanager.com>" . "\r\n";
            mail($email, $subject, $message, $headers);
        }

        // 3. Perform Delete
        $conn->query("DELETE FROM neworders WHERE id = $id");
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=true");
        exit;
    }
}

// Bulk delete (Keep for Delivered items)
if (isset($_POST['delete_bulk']) && !empty($_POST['order_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['order_ids']));
    $conn->query("DELETE FROM neworders WHERE id IN ($ids)");
    header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=true");
    exit;
}

/* ================= UPDATE STATUS LOGIC ================= */
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE neworders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $order_id);
    
    if($stmt->execute()){
        $sql = "SELECT u.email, u.name, o.tracking_id FROM users u 
                JOIN neworders o ON u.id = o.user_id WHERE o.id = ?";
        $mailQ = $conn->prepare($sql);
        $mailQ->bind_param("i", $order_id);
        $mailQ->execute();
        $mailQ->bind_result($email, $user_real_name, $tracking_id);
        $mailQ->fetch();
        $mailQ->close();

        if ($email) {
            $subject = "Shipment Update: $tracking_id";
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;'>
                <div style='background: #0077b6; padding: 20px; text-align: center; color: white;'>
                    <h2 style='margin:0;'>ShipManager Logistics</h2>
                </div>
                <div style='padding: 25px; color: #444;'>
                    <p>Hi <strong>$user_real_name</strong>,</p>
                    <p>Your shipment status has been updated to:</p>
                    <div style='background: #f4f9ff; border: 1px dashed #0077b6; padding: 15px; text-align: center; margin: 20px 0;'>
                        <strong style='font-size: 22px; color: #0077b6;'>$status</strong>
                    </div>
                    <p><strong>Tracking ID:</strong> $tracking_id</p>
                </div>
            </div>";

            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: ShipManager <no-reply@shipmanager.com>" . "\r\n";
            mail($email, $subject, $message, $headers);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=true");
        exit();
    }
}

/* ================= SEARCH LOGIC ================= */
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM neworders";
if ($search !== '') {
    $query .= " WHERE sender_name LIKE ? OR receiver_name LIKE ? OR tracking_id LIKE ?";
    $stmt = $conn->prepare($query . " ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query($query . " ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Logistics Management</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --blue: #0077b6; --green: #28a745; --red: #dc3545; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; }
        .container { max-width: 98%; margin: 20px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { background: #f8f9fa; color: #555; padding: 12px; border-bottom: 2px solid #eee; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Delivered { background: #d4edda; color: #155724; }
        .btn-update { background: var(--blue); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .btn-delete { background: var(--red); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .action-cell { display: flex; gap: 8px; align-items: center; }
        select { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2 style="color: var(--blue);">📦 Logistics Command Center</h2>
        <div class="bulk-actions">
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" style="padding:10px; border-radius:5px; border:1px solid #ddd; width:250px;" placeholder="Search...">
                <button type="submit" class="btn-update">Search</button>
            </form>
        </div>
    </div>

    <form id="bulkDeleteForm" method="POST">
    <table>
        <thead>
            <tr>
                <th style="width: 30px;"><input type="checkbox" id="checkAll"></th>
                <th>Sender / Pickup</th>
                <th>Receiver / Delivery</th>
                <th>Tracking ID</th>
                <th>Status</th>
                <th>Action Center</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $orders->fetch_assoc()): 
                $status = trim($row['status']);
                $statusClass = str_replace(' ', '-', $status); 
            ?>
            <tr>
                <td><input type="checkbox" name="order_ids[]" value="<?= $row['id'] ?>" class="item-checkbox"></td>
                <td><strong><?= htmlspecialchars($row['sender_name']) ?></strong></td>
                <td><strong><?= htmlspecialchars($row['receiver_name']) ?></strong></td>
                <td><code><?= $row['tracking_id'] ?></code></td>
                <td><span class="badge status-<?= $statusClass ?>"><?= $status ?></span></td>
                <td class="action-cell">
                    
                    <?php if ($status !== 'Delivered'): ?>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                            <select name="status">
                                <?php 
                                $options = ["Pending", "Picked Up", "In Transit", "Out for Delivery", "Delivered"];
                                foreach($options as $opt) {
                                    $selected = ($status == $opt) ? 'selected' : '';
                                    echo "<option value='$opt' $selected>$opt</option>";
                                }
                                ?>
                            </select>
                            <button name="update_status" class="btn-update">Update</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($status === 'Pending' || $status === 'Delivered'): ?>
                        <form method="POST" onsubmit="return confirm('Delete this record? <?php echo ($status==='Pending') ? 'User will receive a cancellation email.' : ''; ?>')">
                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                            <button name="delete_single" class="btn-delete">Delete</button>
                        </form>
                    <?php endif; ?>

                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </form>
</div>

<script>
    document.getElementById('checkAll').onclick = function() {
        var checkboxes = document.querySelectorAll('.item-checkbox');
        for (var checkbox of checkboxes) { checkbox.checked = this.checked; }
    }
    <?php if(isset($_GET['updated'])): ?>
        Swal.fire({ icon: 'success', title: 'Updated!', text: 'Status changed.', timer: 1500, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?>
        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Record removed.', timer: 1500, showConfirmButton: false });
    <?php endif; ?>
</script>
</body>
</html>