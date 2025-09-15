<?php
session_start();
require './auth/connection.php';






// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShipManager - Smart Delivery Solutions</title>
<style>
/* 🌟 Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background:#f9f9f9; color:#333; transition: filter 0.3s; }
a { text-decoration:none; }

/* 🌟 Navbar */
nav {
  background: #0a2540;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 8%;
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 100;
}
nav h1 { font-size:1.6rem; font-weight:bold; }
nav ul { list-style:none; display:flex; gap:2rem; align-items:center; }
nav ul li a { color:#fff; font-weight:500; transition:0.3s; }
nav ul li a:hover { color:#ffd43b; }

/* 🌟 Profile dropdown */
.profile { position: relative; cursor:pointer; }
.profile-img { width:40px; height:40px; border-radius:50%; }
.dropdown {
  display: none;
  position: absolute;
  top:50px;
  right:0;
  background:#fff;
  color:#000;
  border-radius:6px;
  box-shadow:0 5px 15px rgba(0,0,0,0.2);
  min-width:120px;
  list-style:none;
  padding:5px 0;
  z-index:100;
}
.dropdown li a { color:#000; padding:8px 15px; display:block; }
.dropdown li a:hover { background:#004aad; color:#fff; }
.show-dropdown .dropdown { display:block; }

/* 🌟 Hero Section */
.hero {
  height:100vh;
  background:linear-gradient(rgba(10,37,64,0.7), rgba(10,37,64,0.7)), url('assets/images/business-delivery.jpg') center/cover no-repeat;
  display:flex;
  justify-content:center;
  align-items:center;
  flex-direction:column;
  color:#fff;
  text-align:center;
  padding:0 12%;
  margin-top:70px;
}
.hero h2 { font-size:3.2rem; margin-bottom:1rem; }
.hero p { font-size:1.2rem; margin-bottom:2rem; }
.hero a { background:#ffd43b; color:#0a2540; padding:0.9rem 2rem; border-radius:30px; font-size:1rem; font-weight:bold; transition:0.3s; }
.hero a:hover { background:#ffcd00; }

/* 🌟 Services */
.services { padding:5rem 10%; background:#fff; display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:2rem; text-align:center; }
.service { background:#f1f5f9; padding:2rem; border-radius:12px; transition: transform 0.3s, box-shadow 0.3s; }
.service:hover { transform:translateY(-8px); box-shadow:0 6px 20px rgba(0,0,0,0.1); }
.service h3 { margin-bottom:1rem; color:#0a2540; }

/* 🌟 Call to Action */
.cta { padding:4rem 10%; text-align:center; background:#ffd43b; }
.cta h2 { margin-bottom:1rem; font-size:2rem; color:#0a2540; }
.cta a { background:#0a2540; color:#fff; padding:0.8rem 2rem; border-radius:30px; font-weight:bold; transition:0.3s; }
.cta a:hover { background:#142f54; }

/* 🌟 Footer */
footer { background:#0a2540; color:#fff; text-align:center; padding:2rem 5%; margin-top:2rem; }
footer p { margin-bottom:0.5rem; }
.social-links a { margin:0 10px; color:#ffd43b; font-weight:bold; }

/* 🌟 Logout Modal */
.modal-logout {
  display: none;
  position: fixed;
  top:0; left:0; right:0; bottom:0;
  background: rgba(0,0,0,0.3);
  backdrop-filter: blur(3px);
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.modal-logout .modal-content {
  background: #fff;
  padding: 2rem 2.5rem;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  animation: slideDown 0.3s ease-out;
}
.modal-logout .modal-content p { font-size:1.2rem; margin-bottom:1.5rem; color:#0a2540; }
.modal-logout button {
  margin: 0 1rem;
  padding: 0.6rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  font-size: 1rem;
}
.modal-logout .yes { background: #004aad; color: #fff; }
.modal-logout .no { background: #ddd; color: #333; }
.modal-logout .yes:hover { background: #0a2540; }
.modal-logout .no:hover { background: #bbb; }

@keyframes slideDown {
  from { transform: translateY(-20px); opacity:0; }
  to { transform: translateY(0); opacity:1; }
}
</style>
</head>
<body>

<!-- 🌟 Navbar -->
<nav>

  <h1>ShipManager</h1>
  <ul>
    <li><a href="">Home</a></li>
    <li><a href="users/neworder.php">Place Order</a></li>
    <li><a href="users/track.php">Track</a></li>

    <?php if(isset($_SESSION['user_id'])): ?>
      <li class="profile" id="profileMenu">
 


<img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="" class="profile-img" id="profileImg">






<ul class="dropdown" id="profileDropdown">
  <li><a href="users/index.php" id="profileLink">Profile</a></li>
  <li><a href="#" id="logoutBtn">Logout</a></li>
</ul>

      </li>
    <?php else: ?>
      <li><a href="auth/login.php">Login</a></li>
    <?php endif; ?>

  </ul>
</nav>

<!-- 🌟 Logout Modal -->
<div class="modal-logout" id="logoutModal">
  <div class="modal-content">
    <p>Do you want to logout?</p>
    <button class="yes" id="confirmLogout">Yes</button>
    <button class="no" id="cancelLogout">No</button>
  </div>
</div>

<!-- 🌟 Hero -->
<section class="hero">
  <h2>Smart, Reliable & Fast Delivery Solutions</h2>
  <p>Helping businesses and individuals deliver packages on time with full transparency.</p>
  <a href="users/neworder.php">Start Shipping</a>
</section>

<!-- 🌟 Services -->
<section class="services">
  <div class="service">
    <h3>📦 Quick Orders</h3>
    <p>Create delivery requests in minutes with easy-to-use forms.</p>
  </div>
  <div class="service">
    <h3>🚚 Real-Time Tracking</h3>
    <p>Know exactly where your package is at any moment.</p>
  </div>
  <div class="service">
    <h3>🌍 Wide Coverage</h3>
    <p>Deliver across cities and countries with confidence.</p>
  </div>
  <div class="service">
    <h3>💼 Business Solutions</h3>
    <p>Custom shipping options designed for enterprises.</p>
  </div>
</section>

<!-- 🌟 Call to Action -->
<section class="cta">
  <h2>Grow Your Business With Reliable Delivery</h2>
  <a href="auth/register.php">Partner With Us</a>
</section>

<!-- 🌟 Footer -->
<footer>
  <p>&copy; 2025 ShipManager. All rights reserved.</p>
  <div class="social-links">
    <a href="https://www.facebook.com">Facebook</a> |
    <a href="https://www.linkedin.com">LinkedIn</a> |
    <a href="https://x.com">Twitter</a>
  </div>
</footer>

<script>
// Toggle dropdown on profile image click
const profileImg = document.getElementById('profileImg');
const profileMenu = document.getElementById('profileMenu');

if(profileImg && profileMenu){
  profileImg.addEventListener('click', function(e){
    e.stopPropagation();
    profileMenu.classList.toggle('show-dropdown');
  });

  document.addEventListener('click', function(e){
    if(!profileMenu.contains(e.target)){
      profileMenu.classList.remove('show-dropdown');
    }
  });
}

// Logout modal
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');

if(logoutBtn && logoutModal && confirmLogout && cancelLogout){
  logoutBtn.addEventListener('click', function(e){
    e.preventDefault();
    logoutModal.style.display = 'flex';
    profileMenu.classList.remove('show-dropdown');
  });
  confirmLogout.addEventListener('click', function(){
    window.location.href = "index.php?logout=1";
  });
  cancelLogout.addEventListener('click', function(){
    logoutModal.style.display = 'none';
  });
  logoutModal.addEventListener('click', function(e){
    if(e.target === logoutModal){
      logoutModal.style.display = 'none';
    }
  });
}
</script>

</body>
</html>
