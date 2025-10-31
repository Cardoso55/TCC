<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "macawsystems";
// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
?>
