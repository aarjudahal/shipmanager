<?php
// reset.php

// Database connection
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "shipmanager";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Validate presence of required parameters
if (!$email || !$token) {
    die("Invalid request.");
}

// Check if the token and email exist in the password_reset_temp table
$stmt = $conn->prepare("SELECT expDate FROM password_reset_temp WHERE email = ? AND token = ?");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    die("Invalid or expired reset link.");
}

$stmt->bind_result($expDate);
$stmt->fetch();

// Check if the reset link has expired
if (strtotime($expDate) < time()) {
    die("The reset link has expired.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $message = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the user's password in the users table
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashed_password, $email);
        $updateStmt->execute();

        // Delete the token from password_reset_temp to invalidate it
        $delStmt = $conn->prepare("DELETE FROM password_reset_temp WHERE email = ?");
        $delStmt->bind_param("s", $email);
        $delStmt->execute();

        $message = "Password has been reset successfully.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Reset Password - Shipmanager</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f2f2f2, #c6c6c6);
        height: 100vh;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #333;
    }
    .container {
        background: white;
        padding: 40px 50px;
        border-radius: 12px;
        box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        width: 350px;
        text-align: center;
    }
    h2 {
        margin-bottom: 25px;
        font-weight: 700;
        color: #5a2a83;
    }
    input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin: 10px 0 20px 0;
        border-radius: 8px;
        border: 2px solid #ddd;
        font-size: 1em;
        transition: border-color 0.3s ease;
    }
    input[type="password"]:focus {
        border-color: #7d5fff;
        outline: none;
    }
    input[type="submit"] {
        background: #7d5fff;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1em;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    input[type="submit"]:hover {
        background: #5a2a83;
    }
    .message {
        margin-top: 20px;
        font-weight: 600;
        color: #e74c3c;
    }
    .success {
        color: #27ae60;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>
    <?php if ($message && strpos($message, 'successfully') === false): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if (empty($message) || strpos($message, 'successfully') === false): ?>
    <form action="" method="POST" autocomplete="off">
        <input type="password" name="password" placeholder="Enter new password" required />
        <input type="password" name="password_confirm" placeholder="Confirm new password" required />
        <input type="submit" value="Reset Password" />
    </form>
    <?php else: ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <p><a href="login.php">Click here to login</a></p>
    <?php endif; ?>
</div>
</body>
</html>
