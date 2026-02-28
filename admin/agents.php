<?php
session_start();
require '../auth/connection.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Handle agent addition
if (isset($_POST['add_agent'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validation Logic
    if (empty($name) || empty($email) || empty($phone)) {
        $_SESSION['errorMsg'] = "❌ All fields are required!";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $_SESSION['errorMsg'] = "❌ Name should only contain letters and spaces!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errorMsg'] = "❌ Invalid email format!";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['errorMsg'] = "❌ Phone number must be exactly 10 digits!";
    } else {
        $stmt = $conn->prepare("INSERT INTO agents (name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $phone);
        if ($stmt->execute()) {
            $_SESSION['successMsg'] = "✅ Agent added successfully!";
        } else {
            $_SESSION['errorMsg'] = "❌ Failed to add agent.";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle agent deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM agents WHERE id = $id");
    $_SESSION['successMsg'] = "✅ Agent deleted successfully!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle agent edit
if (isset($_POST['edit_agent'])) {
    $id    = intval($_POST['agent_id']);
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validation Logic for Edit
    if (empty($name) || empty($email) || empty($phone)) {
        $_SESSION['errorMsg'] = "❌ All fields are required!";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $_SESSION['errorMsg'] = "❌ Name should only contain letters and spaces!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errorMsg'] = "❌ Invalid email format!";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['errorMsg'] = "❌ Phone number must be exactly 10 digits!";
    } else {
        $stmt = $conn->prepare("UPDATE agents SET name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $id);
        if ($stmt->execute()) {
            $_SESSION['successMsg'] = "✅ Agent updated successfully!";
        } else {
            $_SESSION['errorMsg'] = "❌ Failed to update agent.";
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all agents
$agents = $conn->query("SELECT * FROM agents ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Agents - Admin Panel</title>
<style>
body { font-family:'Segoe UI', Tahoma, Geneva, Verdana,sans-serif; background:#f0f4f8; margin:0; padding:0;}
.container { max-width:1100px; margin:40px auto; padding:30px; background:#fff; border-radius:15px; box-shadow:0 15px 40px rgba(0,0,0,0.1);}
h2 { text-align:center; font-size:2rem; margin-bottom:25px; color:#004aad; }
form { display:flex; flex-wrap:wrap; gap:15px; justify-content:center; margin-bottom:30px;}
form input, form button { padding:10px 15px; border-radius:10px; border:1px solid #ccc; flex:1 1 200px; }
form button { border:none; background:#0077b6; color:#fff; font-weight:bold; cursor:pointer; transition:0.3s; }
form button:hover { background:#005f99; }
.success-msg, .error-msg { text-align:center; margin-bottom:15px; padding:10px; border-radius:8px; font-weight:500;}
.success-msg { background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; }
.error-msg { background:#f8d7da; color:#842029; border:1px solid #f5c2c7; }

/* Table styling */
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { padding:12px 10px; border-bottom:1px solid #eee; text-align:center; }
th { background: linear-gradient(135deg,#0077b6,#00b4d8); color:#fff; font-weight:600; }
tr:hover { background:#f5faff; }
.action-btn { padding:6px 12px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.3s; }
.edit-btn { background:#28a745; color:#fff; }
.edit-btn:hover { background:#218838; }
.delete-btn { background:#dc3545; color:#fff; }
.delete-btn:hover { background:#b02a37; }

/* Modal styling */
.modal {
  display:none;
  position:fixed;
  z-index:1000;
  left:0; top:0;
  width:100%; height:100%;
  background:rgba(0,0,0,0.5);
  justify-content:center;
  align-items:center;
}
.modal-content {
  background:#fff;
  padding:30px;
  border-radius:15px;
  width:90%;
  max-width:500px;
  position:relative;
}
.modal-content h3 { margin-top:0; color:#004aad; }
.modal-content input { width:100%; margin:10px 0; padding:10px; border-radius:8px; border:1px solid #ccc; }
.modal-content button { margin-top:10px; background:#0077b6; color:#fff; width: 100%; }
.close-btn {
  position:absolute;
  top:15px; right:15px;
  font-size:1.2rem;
  cursor:pointer;
  background:#dc3545;
  color:#fff;
  border:none;
  padding:5px 10px;
  border-radius:8px;
}
</style>
</head>
<body>
<div class="container">
<h2>Manage Agents</h2>

<?php if(isset($_SESSION['successMsg'])): ?>
    <div class="success-msg"><?php echo $_SESSION['successMsg']; unset($_SESSION['successMsg']); ?></div>
<?php endif; ?>
<?php if(isset($_SESSION['errorMsg'])): ?>
    <div class="error-msg"><?php echo $_SESSION['errorMsg']; unset($_SESSION['errorMsg']); ?></div>
<?php endif; ?>

<form method="POST">
    <input type="text" name="name" placeholder="Agent Name" pattern="[A-Za-z\s]+" title="Letters and spaces only" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="tel" name="phone" placeholder="Phone Number" pattern="[0-9]{10}" title="Must be 10 digits" required>
    <button type="submit" name="add_agent">Add Agent</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if($agents->num_rows > 0): ?>
            <?php while($row = $agents->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td>
                        <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $row['id']; ?>,'<?php echo htmlspecialchars($row['name']); ?>','<?php echo htmlspecialchars($row['email']); ?>','<?php echo htmlspecialchars($row['phone']); ?>')">Edit</button>
                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;font-weight:bold;">No agents found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

<div class="modal" id="editModal">
  <div class="modal-content">
    <button class="close-btn" onclick="closeModal()">X</button>
    <h3>Edit Agent</h3>
    <form method="POST">
        <input type="hidden" id="agent_id" name="agent_id">
        <input type="text" id="agent_name" name="name" placeholder="Name" pattern="[A-Za-z\s]+" title="Letters and spaces only" required>
        <input type="email" id="agent_email" name="email" placeholder="Email" required>
        <input type="tel" id="agent_phone" name="phone" placeholder="Phone" pattern="[0-9]{10}" title="Must be 10 digits" required>
        <button type="submit" name="edit_agent">Save Changes</button>
    </form>
  </div>
</div>

<script>
function confirmDelete(agentId){
    if(confirm("Are you sure you want to delete this agent?")){
        window.location.href = "?delete=" + agentId;
    }
}

function openEditModal(id, name, email, phone){
    document.getElementById('agent_id').value = id;
    document.getElementById('agent_name').value = name;
    document.getElementById('agent_email').value = email;
    document.getElementById('agent_phone').value = phone;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal(){
    document.getElementById('editModal').style.display = 'none';
}

// Close modal on click outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if(event.target === modal){
        closeModal();
    }
}
</script>
</body>
</html>