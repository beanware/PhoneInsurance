<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurance_db";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger' role='alert'>
            Connection failed: " . htmlspecialchars($conn->connect_error) . "
         </div>");
}

// Initialize variables for feedback messages
$success_msg = "";
$error_msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($role)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif (!preg_match('/^\d{10,15}$/', $phone_number)) {
        $error_msg = "Phone number must contain only digits and be between 10 to 15 characters.";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO Users (full_name, email, phone_number, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("sssss", $full_name, $email, $phone_number, $password_hash, $role);

            // Execute the statement
            if ($stmt->execute()) {
                $success_msg = "New user added successfully.";
            } else {
                // Check for duplicate email
                if ($conn->errno === 1062) { // Duplicate entry error code
                    $error_msg = "A user with this email already exists.";
                } else {
                    $error_msg = "Error: " . htmlspecialchars($stmt->error);
                }
            }

            // Close the statement
            $stmt->close();
        }
    }

    // Close the connection
    $conn->close();
}
?>
