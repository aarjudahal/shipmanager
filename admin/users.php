<?php
session_start();
require '../auth/connection.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Handle user deletion
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
}

// Fetch search query
$searchQuery = '';
if(isset($_GET['search'])){
    $searchQuery = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ?");
    $likeQuery = "%".$searchQuery."%";
    $stmt->bind_param("ss", $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Admin Panel</title>
<style>
body {
    font-family:'Segoe UI', Tahoma, Geneva, Verdana,sans-serif;
    margin:0; padding:0; background:#f0f4f8;
}
.container {
    max-width:1200px; margin:40px auto; padding:30px;
    background:#fff; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.1);
}
h2 {
    text-align:center; font-size:2.2rem; margin-bottom:30px;
    background: linear-gradient(135deg,#0077b6,#00b4d8);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
}

/* Search Box */
.search-box {
    display:flex; justify-content:flex-end; margin-bottom:20px;
}
.search-box input {
    padding:8px 12px; border-radius:8px 0 0 8px; border:1px solid #ccc; width:250px;
}
.search-box button {
    padding:8px 15px; border:none; border-radius:0 8px 8px 0;
    background:#0077b6; color:#fff; font-weight:bold; cursor:pointer; transition:0.3s;
}
.search-box button:hover { background:#005f99; }

/* Table Styling */
table {
    width:100%; border-collapse:collapse; box-shadow:0 5px 15px rgba(0,0,0,0.05);
}
th, td {
    padding:14px 10px; text-align:center; border-bottom:1px solid #eee;
}
th {
    background: linear-gradient(135deg,#0077b6,#00b4d8); color:#fff; font-weight:600;
}
tr:hover { background:#f5faff; }
.highlight { background: #fff3b0 !important; font-weight:bold; }

/* Action Buttons */
button.action-btn {
    padding:6px 12px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.3s;
}
button.delete-btn { background:#dc3545; color:#fff; }
button.delete-btn:hover { background:#b02a37; }

/* Responsive */
@media(max-width:768px){
    .search-box{flex-direction:column; align-items:flex-end; gap:10px;}
    table, th, td{font-size:0.85rem;}
}
</style>
<script>
function confirmDelete(userId){
    if(confirm("Are you sure you want to delete this user?")){
        window.location.href = "?delete=" + userId;
    }
}
</script>
</head>
<body>
<div class="container">
<h2>Manage Users</h2>

<!-- Search Form -->
<form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($searchQuery); ?>">
    <button type="submit">Search</button>
</form>

<!-- Users Table -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Registered At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr class="<?php echo (stripos($row['name'],$searchQuery)!==false || stripos($row['email'],$searchQuery)!==false)?'highlight':''; ?>">
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo $row['created_at']; ?></td>
            <td>
                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5" style="text-align:center;font-weight:bold;">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</body>
</html>
