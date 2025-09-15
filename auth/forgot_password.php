<?php
// forget.php

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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p class='error'>Invalid email address.</p>";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // Email exists, create reset token and send email
            $token = bin2hex(random_bytes(50));
            $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Insert token with expiration
            $stmtDel = $conn->prepare("DELETE FROM password_reset_temp WHERE email = ?");
            $stmtDel->bind_param("s", $email);
            $stmtDel->execute();

            $stmtInsert = $conn->prepare("INSERT INTO password_reset_temp (email, token, expDate) VALUES (?, ?, ?)");
            $stmtInsert->bind_param("sss", $email, $token, $expiration);
            $stmtInsert->execute();

            // Send password reset email with token link
            $resetLink = "http://localhost/delivery/auth/reset.php?email=" . urlencode($email) . "&token=" . $token;

            $subject = "Password Reset Request";
            $body = "Hello,<br>Click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a><br>This link expires in 1 hour.";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: Shipmanager <no-reply@shipmanager.com>\r\n";

            if (mail($email, $subject, $body, $headers)) {
                $message = "<p class='success'>An email with reset instructions has been sent to your email address.</p>";
            } else {
                $message = "<p class='error'>Failed to send reset email. Please try later.</p>";
            }
        } else {
            $message = "<p class='error'>No user registered with this email.</p>";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Forgot Password - Shipmanager</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #71b7e6, #9b59b6);
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
        box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        width: 350px;
        text-align: center;
    }
    h2 {
        margin-bottom: 25px;
        font-weight: 700;
        color: #5a2a83;
    }
    input[type="email"] {
        width: 100%;
        padding: 12px 15px;
        margin: 15px 0 25px 0;
        border-radius: 8px;
        border: 2px solid #ddd;
        font-size: 1em;
        transition: border-color 0.3s ease;
    }
    input[type="email"]:focus {
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
    }
    .error {
        color: #e74c3c;
    }
    .success {
        color: #27ae60;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <form action="" method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Enter your email address" required />
        <input type="submit" value="Send Reset Link" />
    </form>
    <div class="message"><?php echo $message; ?></div>
</div>
</body>
</html>
