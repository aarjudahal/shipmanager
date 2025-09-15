<?php
// Database connection
$servername = "localhost";
$username = "root";   // default in XAMPP
$password = "";       // default in XAMPP
$dbname = "shipmanager";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Collect form data safely
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if ($password !== $confirm_password) {
        $message = "<p style='color:red;'>❌ Passwords do not match.</p>";
    } else {
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $photo_name = $_FILES['photo']['name'];
            $photo_tmp = $_FILES['photo']['tmp_name'];
            $photo_ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($photo_ext, $allowed_ext)) {
                $new_photo_name = uniqid('profile_', true) . '.' . $photo_ext;
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $photo_path = $upload_dir . $new_photo_name;
                move_uploaded_file($photo_tmp, $photo_path);
            } else {
                $message = "<p style='color:red;'>❌ Invalid photo type. Allowed: jpg, jpeg, png, gif.</p>";
            }
        } else {
            $photo_path = ''; // No photo uploaded
        }

        // Hash password before saving
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $sql = "INSERT INTO users (name, email, phone, password, photo) 
                VALUES ('$name', '$email', '$phone', '$hashed_password', '$photo_path')";

        if ($conn->query($sql) === TRUE) {
            $message = "<p style='color:green;'>✅ Registration successful! <a href='login.php'>Login here</a></p>";
        } else {
            $message = "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - ShipManager</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg, #004aad, #0a2540); }
    .register-container { background: #fff; padding: 2rem 1.5rem; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); width: 100%; max-width: 360px; text-align: center; }
    .register-container h2 { margin-bottom: 0.8rem; font-size: 1.8rem; color: #0a2540; }
    .register-container p { margin-bottom: 1.2rem; color: #555; font-size: 0.95rem; }
    .register-container input { width: 100%; padding: 0.7rem 0.9rem; margin-bottom: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; transition: 0.3s; }
    .register-container input:focus { border-color: #004aad; outline: none; box-shadow: 0 0 5px rgba(0,74,173,0.3); }
    .register-container button { width: 100%; background: #004aad; color: #fff; padding: 0.8rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 0.5rem; }
    .register-container button:hover { background: #0a2540; }
    .register-container .links { margin-top: 0.8rem; font-size: 0.85rem; }
    .register-container .links a { color: #004aad; text-decoration: none; margin: 0 4px; transition: 0.3s; }
    .register-container .links a:hover { text-decoration: underline; }
    .error { color: red; margin-bottom: 1rem; }
    .success { color: green; margin-bottom: 1rem; }
  </style>
</head>
<body>

  <div class="register-container">
    <h2>Create Account</h2>
    <p>Join ShipManager and start shipping today</p>
    
    <?php if (!empty($message)) echo $message; ?>

    <form action="" method="POST" enctype="multipart/form-data">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email Address" required>
      <input type="text" name="phone" placeholder="Phone Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <input type="file" name="photo" accept="image/*" required>
      <button type="submit">Register</button>
    </form>

    <div class="links">
      <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
  </div>

</body>
</html>
