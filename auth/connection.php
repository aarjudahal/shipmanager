<?php
$servername = "localhost";  // usually localhost on XAMPP
$username = "root";         // default XAMPP user
$password = "";             // default XAMPP password is empty
$dbname = "shipmanager";    // your existing DB name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
