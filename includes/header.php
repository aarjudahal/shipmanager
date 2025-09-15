<?php
session_start();
require '../auth/connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit();
}

$email = $_SESSION['user_email'];

// Fetch user data
$sql = "SELECT name, photo FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Database prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Prepare photo path with fallback
$photoPath = !empty($user['photo']) ? '../auth/' . $user['photo'] : '../assets/default_profile.png';
if (!file_exists($photoPath)) {
    $photoPath = '../assets/default_profile.png';
}

$user_name = $user['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Dashboard - ShipManager</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; background:#f4f6f9; }
nav { background:#004aad; color:#fff; display:flex; justify-content:space-between; align-items:center; padding:1rem 5%; position:sticky; top:0; z-index:100; }
nav h1 { font-size:1.5rem; }
nav ul { list-style:none; display:flex; gap:1.5rem; }
nav ul li a { color:#fff; text-decoration:none; font-weight:500; transition:0.3s; }
nav ul li a:hover { color:#ffd43b; }
.profile-container { position:relative; cursor:pointer; }
.profile-container img { width:40px; height:40px; border-radius:50%; border:2px solid #fff; object-fit:cover; }
.dropdown { display:none; position:absolute; right:0; background:#fff; color:#004aad; min-width:120px; box-shadow:0 4px 10px rgba(0,0,0,0.2); border-radius:8px; overflow:hidden; z-index:1000; }
.dropdown button { width:100%; padding:0.8rem; border:none; background:none; cursor:pointer; font-weight:bold; text-align:left; transition:background 0.2s; color:#004aad; }
.dropdown button:hover { background:#f1f5f9; }
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:2000; }
.modal-content { background:#fff; padding:2rem; border-radius:12px; text-align:center; max-width:400px; }
.modal-content button { padding:0.6rem 1.2rem; margin:0.5rem; border-radius:6px; border:none; cursor:pointer; font-weight:bold; }
.confirm-btn { background:#004aad; color:#fff; }
.cancel-btn { background:#ddd; color:#333; }
</style>
</head>
<body>

<nav>
  <h1>ShipManager</h1>
  <ul>
    <li><a href="index.php">Dashboard</a></li>
    <li><a href="neworder.php">New Order</a></li>
    <li><a href="track.php">Track Shipment</a></li>
    <li><a href="orders.php">Orders</a></li>
  </ul>

  <div class="profile-container" id="profileContainer" title="<?php echo htmlspecialchars($user_name); ?>">
    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Profile">
    <div class="dropdown" id="dropdownMenu">
      <button id="logoutBtn">Logout</button>
    </div>
  </div>
</nav>

<div class="modal" id="logoutModal" aria-hidden="true" role="dialog" aria-labelledby="logoutTitle">
  <div class="modal-content">
    <h3 id="logoutTitle">Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button class="confirm-btn" id="confirmLogout">Yes, Logout</button>
    <button class="cancel-btn" id="cancelLogout">Cancel</button>
  </div>
</div>

<script>
const profileContainer = document.getElementById('profileContainer');
const dropdownMenu = document.getElementById('dropdownMenu');

profileContainer.addEventListener('click', e => {
  e.stopPropagation();
  dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
});

const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');

logoutBtn.addEventListener('click', e => {
  e.stopPropagation();
  dropdownMenu.style.display = 'none';
  logoutModal.style.display = 'flex';
  logoutModal.setAttribute('aria-hidden', 'false');
});

cancelLogout.addEventListener('click', () => {
  logoutModal.style.display = 'none';
  logoutModal.setAttribute('aria-hidden', 'true');
});

confirmLogout.addEventListener('click', () => {
  window.location.href = '../auth/logout.php';
});

window.addEventListener('click', e => {
  if (!profileContainer.contains(e.target)) dropdownMenu.style.display = 'none';
  if (e.target === logoutModal) {
    logoutModal.style.display = 'none';
    logoutModal.setAttribute('aria-hidden', 'true');
  }
});
</script>

</body>
</html>
