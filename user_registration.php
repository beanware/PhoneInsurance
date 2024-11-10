<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "insurance_db";

// Create connection using MySQLi
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger text-center' role='alert'>
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
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic server-side validation
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($username) || empty($password) || empty($role)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif (!preg_match('/^\d{10,15}$/', $phone_number)) {
        $error_msg = "Phone number must contain only digits and be between 10 to 15 characters.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Check for duplicate email or username
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ? OR username = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error_msg = "A user with this email or username already exists.";
            } else {
                // Hash the password using BCRYPT
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Prepare and bind for insertion
                $insert_stmt = $conn->prepare("INSERT INTO Users (full_name, email, phone_number, username, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param("ssssss", $full_name, $email, $phone_number, $username, $password_hash, $role);

                    // Execute the statement
                    if ($insert_stmt->execute()) {
                        $success_msg = "New user registered successfully.";
                        // Optionally, redirect to a login page
                        // header("Location: user_login.php");
                        // exit();
                    } else {
                        $error_msg = "Error: " . htmlspecialchars($insert_stmt->error);
                    }

                    $insert_stmt->close();
                } else {
                    $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
                }
            }

            $stmt->close(); // Close the statement once
        } else {
            $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
        }
    }

    // Close the connection
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- [Your existing HTML head content remains unchanged] -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | Insurance Company</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* [Your existing CSS styles remain unchanged] */
        body {
            background-image: url('https://images.unsplash.com/photo-1580894908361-0b2f5728e3b0?w=1200&auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh; /* Ensures the body takes at least the full viewport height */
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        .form-title {
            margin-bottom: 20px;
            text-align: center;
            color: #0d6efd;
        }
        /* Optional: Add smooth scrolling for better UX */
        html {
            scroll-behavior: smooth;
        }
        @media (max-width: 576px) {
            .form-container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">User Registration</h2>

            <!-- Display Success Message -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Display Error Message -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <!-- Full Name -->
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number:</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" pattern="\d{10,15}" title="Phone number must be between 10 to 15 digits." required>
                </div>

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                </div>

                <!-- Role -->
                <div class="mb-3">
                    <label for="role" class="form-label">Role:</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="user" selected>User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>

            <!-- Additional Information -->
            <div class="mt-4 text-center">
                <p>Already have an account? <a href="user_login.php" class="btn btn-link">Log In</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
