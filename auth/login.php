
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - ShipManager</title>
<style>
body { display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; background:linear-gradient(135deg, #0a2540, #004aad); }
.login-container { background:#fff; padding:3rem 2.5rem; border-radius:15px; box-shadow:0 6px 20px rgba(0,0,0,0.15); width:100%; max-width:400px; text-align:center; }
.login-container h2 { margin-bottom:1rem; color:#0a2540; }
.login-container p { margin-bottom:2rem; color:#555; }
.login-container input { width:100%; padding:0.9rem 1rem; margin-bottom:1rem; border:1px solid #ddd; border-radius:8px; font-size:1rem; }
.login-container input:focus { border-color:#004aad; outline:none; box-shadow:0 0 5px rgba(0,74,173,0.3); }
.login-container button { width:100%; background:#004aad; color:#fff; padding:0.9rem; border:none; border-radius:8px; font-size:1rem; font-weight:bold; cursor:pointer; }
.login-container button:hover { background:#0a2540; }
.login-container .links { margin-top:1.5rem; font-size:0.9rem; }
.login-container .links a { color:#004aad; text-decoration:none; margin:0 5px; }
.login-container .links a:hover { text-decoration:underline; }
.error { color:red; margin-bottom:1rem; }
</style>
</head>
<body>
<div class="login-container">
    <h2>Welcome Back</h2>
    <p>Login to continue to ShipManager</p>

    <?php
    session_start();
    if(isset($_SESSION['login_error'])){
        echo "<p class='error'>".$_SESSION['login_error']."</p>";
        unset($_SESSION['login_error']);
    }
    ?>

    <form action="login_process.php" method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="links">
        <p><a href="forgot_password.php">Forgot Password?</a></p>
        <p>Don't have an account? <a href="register.php">Sign Up</a></p>
    </div>
</div>
</body>
</html>
