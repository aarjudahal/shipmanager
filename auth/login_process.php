<?php
session_start();
require 'connection.php'; // DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim received data
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepare statement to prevent SQL injection
    $sql = "SELECT id, name, email, password, photo FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user found
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Store relative photo path or default photo
            if (!empty($user['photo']) && file_exists('uploads/' . $user['photo'])) {
                $_SESSION['user_photo'] = 'uploads/' . $user['photo'];
            } else {
                $_SESSION['user_photo'] = 'assets/default_profile.png';
            }

            // Redirect to user dashboard
            header("Location: ../users/index.php");
            exit();
        } else {
            // Wrong password
            $_SESSION['login_error'] = "❌ Invalid email or password.";
            header("Location: login.php");
            exit();
        }
    } else {
        // No user found with email
        $_SESSION['login_error'] = "❌ Invalid email or password.";
        header("Location: login.php");
        exit();
    }
} else {
    // Prevent direct access via GET
    header("Location: login.php");
    exit();
}
?>
