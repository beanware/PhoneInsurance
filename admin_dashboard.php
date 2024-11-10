<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurance_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "Access denied. Please log in as an admin.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Admin Dashboard</h2>
    <ul>
        <li><a href="insurance_plan_creation.php">Create Insurance Plan</a></li>
        <li><a href="policy_creation.php">Create Policy</a></li>
        <li><a href="claims_review.php">Review Claims</a></li>
    </ul>

    <h3>Log Out</h3>
    <form method="post" action="logout.php">
        <input type="submit" value="Log Out">
    </form>
</body>
</html>
