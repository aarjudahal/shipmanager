<?php
session_start();
require './auth/connection.php';

// --- IMAGE LOGIC START ---
$photoUrl = 'assets/default.png'; // Your default placeholder

if (isset($_SESSION['user_id']) || isset($_SESSION['user_email'])) {
    // Identify user by ID or Email depending on what you stored in session
    $userId = $_SESSION['user_id'] ?? null;
    $userEmail = $_SESSION['user_email'] ?? null;
    
    if ($userId) {
        $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $conn->prepare("SELECT photo FROM users WHERE email = ?");
        $stmt->bind_param("s", $userEmail);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['photo'])) {
            /** * PATH FIX:
             * Since your dashboard (in /users) uses '../auth/', 
             * your root file must use './auth/' to see the same folder.
             */
            $photoUrl = 'auth/' . $row['photo']; 
            
            // Optional: If that folder doesn't work, uncomment the line below to try the other:
            // if (!file_exists($photoUrl)) { $photoUrl = 'users/uploads/' . $row['photo']; }
        }
    }
    $stmt->close();
}
// --- IMAGE LOGIC END ---

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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
<style>
/* Reset & Global */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; scroll-behavior: smooth; }
body { background: #f0f4f8; color: #0B2E33; overflow-x: hidden;}
a { text-decoration: none; color: inherit; }

/* Typography */
h1,h2,h3 { font-weight: 700; }
p { font-weight: 400; line-height: 1.6; }

/* Navbar */
nav {
  background: linear-gradient(135deg, #0B2E33, #4F7C82);
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 8%;
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 1000;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
nav h1 { font-size: 1.8rem; letter-spacing: 1px; }
nav ul { list-style: none; display: flex; gap: 2rem; align-items: center; }
nav ul li a { font-weight: 600; transition: color 0.3s ease, transform 0.3s ease; }
nav ul li a:hover { color: #B8E3E9; transform: translateY(-2px); }

/* Profile Dropdown */
.profile { position: relative; cursor: pointer; }
.profile-img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #B8E3E9; transition: transform 0.3s; }
.profile-img:hover { transform: scale(1.1); }
.dropdown {
  display: none;
  position: absolute;
  top: 50px; right: 0;
  background: #fff;
  color: #0B2E33;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  min-width: 140px;
  overflow: hidden;
}
.dropdown li a { padding: 12px 20px; display: block; transition: background 0.3s; }
.dropdown li a:hover { background: #B8E3E9; color: #0B2E33; }
.show-dropdown .dropdown { display: block; }

/* 🌟 Hero Section */
.hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  min-height: 100vh;
  padding: 6rem 8% 0 8%;
  background: linear-gradient(135deg,#e0f2ff,#c7d2fe,#e9d5ff);
  position: relative;
  overflow: hidden;
}
.hero-content {
  flex: 1 1 500px;
  max-width: 600px;
  z-index: 2;
  text-align: left;
}
.hero-content h2 {
  font-size: 3.5rem;
  margin-bottom: 1.2rem;
  color: #0f172a;
  animation: fadeInUp 1s ease;
}
.hero-content p {
  font-size: 1.4rem;
  margin-bottom: 2.5rem;
  max-width: 700px;
  color: #334155;
  animation: fadeInUp 1.2s ease;
}
.hero-content a {
  display: inline-block;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  color: #fff;
  padding: 1rem 2.5rem;
  border-radius: 50px;
  font-weight: bold;
  font-size: 1.1rem;
  box-shadow: 0 12px 30px rgba(99,102,241,0.45);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  animation: fadeInUp 1.4s ease;
}
.hero-content a:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 40px rgba(99,102,241,0.6);
}

/* Hero Image */
.hero-visual {
  flex: 1 1 400px;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  z-index: 1;
}
.hero-visual img {
  width: 100%;
  max-width: 420px;
  height: auto;
  object-fit: contain;
  animation: floatImage 4s ease-in-out infinite;
}
.glow {
  position: absolute;
  width: 380px;
  height: 380px;
  background: radial-gradient(circle, rgba(124,58,237,0.55), rgba(37,99,235,0.45), transparent 70%);
  filter: blur(60px);
  z-index: 0;
}
@keyframes floatImage { 0%{transform:translateY(0);} 50%{transform:translateY(-12px);} 100%{transform:translateY(0);} }

/* Services */
.services {
  padding: 6rem 8%;
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(280px,1fr));
  gap: 2.5rem;
  text-align: center;
}
.service {
  background: #fff;
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  transition: transform 0.4s ease, box-shadow 0.4s ease;
  opacity: 0;
  transform: translateY(30px);
  animation: slideUp 0.8s ease forwards;
}
.service:nth-child(1) { animation-delay: 0.2s; }
.service:nth-child(2) { animation-delay: 0.4s; }
.service:nth-child(3) { animation-delay: 0.6s; }
.service:nth-child(4) { animation-delay: 0.8s; }
.service:hover { transform: translateY(-10px); box-shadow: 0 12px 35px rgba(0,0,0,0.12); }
.service h3 { font-size: 1.8rem; margin-bottom: 1rem; color: #0B2E33; }
.service p { color: #64748b; }

/* Call to Action */
.cta {
  padding: 5rem 8%;
  text-align: center;
  background: linear-gradient(135deg,#B8E3E9,#93B1B5);
  border-radius: 30px;
  margin: 3rem 8%;
  box-shadow: 0 10px 30px rgba(184,227,233,0.3);
}
.cta h2 { margin-bottom: 1.2rem; font-size: 2.2rem; color: #0B2E33; }
.cta a {
  background: linear-gradient(135deg,#0B2E33,#4F7C82);
  color: #fff;
  padding: 1rem 2.5rem;
  border-radius: 50px;
  font-weight: bold;
  box-shadow: 0 4px 15px rgba(11,46,51,0.3);
  transition: transform 0.3s;
}
.cta a:hover { transform: scale(1.05); }

/* Footer */
footer {
  background: linear-gradient(135deg,#0B2E33,#4F7C82);
  color: #fff;
  text-align: center;
  padding: 2.5rem 5%;
}
footer p { margin-bottom: 1rem; font-size: 1rem; }
.social-links a { margin: 0 15px; color: #B8E3E9; font-size: 1.2rem; transition: color 0.3s, transform 0.3s; }
.social-links a:hover { color: #93B1B5; transform: scale(1.15); }

/* Logout Modal */
/* Enhanced Logout Modal UI */
.modal-logout {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(11, 46, 51, 0.4); /* Darker overlay for focus */
  backdrop-filter: blur(10px);
  justify-content: center;
  align-items: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

.modal-logout .modal-content {
  background: #fff;
  padding: 3rem 2rem;
  border-radius: 24px;
  text-align: center;
  max-width: 380px;
  width: 90%;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  transform: translateY(0);
  animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal-content p {
  font-size: 1.2rem;
  color: #0B2E33;
  margin-bottom: 2rem;
  font-weight: 600;
}

/* Button Flex Container */
.modal-buttons {
  display: flex;
  gap: 1rem; /* This separates the buttons perfectly */
  justify-content: center;
}

.modal-logout button {
  flex: 1; /* Makes both buttons equal width */
  padding: 0.8rem 1.5rem;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

/* "Yes" Button - Primary Action */
.modal-logout .yes {
  background: #0B2E33;
  color: #fff;
  box-shadow: 0 4px 12px rgba(11, 46, 51, 0.2);
}

.modal-logout .yes:hover {
  background: #ff4757; /* Changes to red on hover to signal logout */
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(255, 71, 87, 0.3);
}

/* "No" Button - Cancel Action */
.modal-logout .no {
  background: #f1f5f9;
  color: #64748b;
}

.modal-logout .no:hover {
  background: #e2e8f0;
  color: #0B2E33;
  transform: translateY(-3px);
}

/* New Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalPop {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Animations */
@keyframes fadeInUp { from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }
@keyframes slideUp { from{opacity:0; transform:translateY(50px);} to{opacity:1; transform:translateY(0);} }

/* Responsive */
@media(max-width:768px){
  .hero { flex-direction: column; text-align:center; padding:3rem 5%; }
  .hero-content { text-align:center; margin-bottom:2rem; }
  .hero-visual img { height:60vh; }
  .services { grid-template-columns:1fr; padding:5rem 5%; }
  .cta { padding:4rem 5%; margin:2rem 5%; border-radius:20px; }
}
@media(max-width:480px){
  .hero h2 { font-size:2rem; }
  .hero p { font-size:1rem; }
  .hero-content a { padding:0.8rem 2rem; font-size:0.9rem; }
}

</style>
</head>
<body>

<nav>
  <h1>ShipManager</h1>
  <ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="users/neworder.php">Place Order</a></li>
    <li><a href="users/track.php">Track</a></li>
    <?php if(isset($_SESSION['user_id']) || isset($_SESSION['user_email'])): ?>
      <li class="profile" id="profileMenu">
        <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile" class="profile-img" id="profileImg">
        <ul class="dropdown">
          <li><a href="users/index.php">Profile</a></li>
          <li><a href="#" id="logoutBtn">Logout</a></li>
        </ul>
      </li>
    <?php else: ?>
      <li><a href="auth/login.php">Login</a></li>
    <?php endif; ?>
  </ul>
</nav>

<div class="modal-logout" id="logoutModal">
  <div class="modal-content">
    <p>Are you sure you want to logout?</p>
    <div class="modal-buttons">
      <button class="yes" id="confirmLogout">Yes, Logout</button>
      <button class="no" id="cancelLogout">Cancel</button>
    </div>
  </div>
</div>

<section class="hero">
  <div class="hero-content">
    <h2>Smart Delivery, Made Simple</h2>
    <p>ShipManager helps you deliver goods faster with real-time tracking, secure handling, and reliable service for everyone.</p>
    <a href="users/neworder.php">Start Delivery</a>
  </div>
  <div class="hero-visual">
    <div class="glow"></div>
    <img src="rn.png" alt="Delivery System">
  </div>
</section>

<section class="services">
  <div class="service"><h3><i class="fas fa-box-open"></i> Quick Orders</h3><p>Create delivery requests in minutes with easy-to-use forms.</p></div>
  <div class="service"><h3><i class="fas fa-map-marker-alt"></i> Real-Time Tracking</h3><p>Know exactly where your package is at any moment.</p></div>
  <div class="service"><h3><i class="fas fa-globe-americas"></i> Wide Coverage</h3><p>Deliver across cities and countries with confidence.</p></div>
  <div class="service"><h3><i class="fas fa-briefcase"></i> Business Solutions</h3><p>Custom shipping options designed for enterprises.</p></div>
</section>

<section class="cta">
  <h2>Grow Your Business With Reliable Delivery</h2>
  <a href="auth/register.php">Partner With Us</a>
</section>

<footer>
  <p>&copy; 2025 ShipManager. All rights reserved.</p>
  <div class="social-links">
    <a href="https://www.facebook.com"><i class="fab fa-facebook-f"></i> Facebook</a> |
    <a href="https://www.linkedin.com"><i class="fab fa-linkedin-in"></i> LinkedIn</a> |
    <a href="https://x.com"><i class="fab fa-twitter"></i> Twitter</a>
  </div>
</footer>

<script>
// Profile Dropdown
const profileImg = document.getElementById('profileImg');
const profileMenu = document.getElementById('profileMenu');
if(profileImg && profileMenu){
  profileImg.addEventListener('click', e => { e.stopPropagation(); profileMenu.classList.toggle('show-dropdown'); });
  document.addEventListener('click', e => { if(!profileMenu.contains(e.target)){ profileMenu.classList.remove('show-dropdown'); }});
}

// Logout Modal
const logoutBtn = document.getElementById('logoutBtn');
const logoutModal = document.getElementById('logoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');
if(logoutBtn && logoutModal && confirmLogout && cancelLogout){
  logoutBtn.addEventListener('click', e=>{ e.preventDefault(); logoutModal.style.display='flex'; profileMenu.classList.remove('show-dropdown'); });
  confirmLogout.addEventListener('click', ()=>{ window.location.href="index.php?logout=1"; });
  cancelLogout.addEventListener('click', ()=>{ logoutModal.style.display='none'; });
  logoutModal.addEventListener('click', e=>{ if(e.target===logoutModal){ logoutModal.style.display='none'; } });
}
</script>

</body>
</html>