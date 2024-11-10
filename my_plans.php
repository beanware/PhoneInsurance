<?php
// Start the session
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
    die("<div class='alert alert-danger text-center' role='alert'>Connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("<div class='alert alert-danger text-center' role='alert'>User not logged in. Please <a href='login.php'>login</a> to view your policies.</div>");
}

// Initialize variables for feedback messages
$success_msg = "";
$error_msg = "";

// Fetch Policies for the logged-in user
$policies = [];
$stmt = $conn->prepare("SELECT * FROM Policies WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $policies[] = $row;
    }
    $stmt->close();
} else {
    $error_msg = "Error fetching policies: " . htmlspecialchars($conn->error);
}

// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Policies | Insurance Company</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .policy-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: 50px auto;
        }
        h2 {
            text-align: center;
            color: #0d6efd;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #0d6efd;
            color: white;
        }
        .action-buttons a {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="policy-container">
    <h2>Your Policies</h2>

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

    <!-- Existing Policies Table -->
    <?php if (!empty($policies)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Policy ID</th>
                    <th>Plan Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Re-establish database connection to fetch plan details
                $conn = new mysqli($servername, $username_db, $password_db, $dbname);
                if ($conn->connect_error) {
                    echo "<tr><td colspan='5' class='text-center text-danger'>Database connection error.</td></tr>";
                } else {
                    foreach ($policies as $policy) {
                        // Fetch plan details
                        $stmt_p = $conn->prepare("SELECT plan_name, tier, coverage_amount, premium, deductible, coverage_description FROM InsurancePlans WHERE plan_id = ?");
                        if ($stmt_p) {
                            $stmt_p->bind_param("i", $policy['plan_id']);
                            $stmt_p->execute();
                            $stmt_p->bind_result($plan_name, $tier, $coverage_amount, $premium, $deductible, $coverage_description);
                            $stmt_p->fetch();
                            $stmt_p->close();
                        } else {
                            $plan_name = "N/A";
                            $tier = "N/A";
                            $coverage_amount = "N/A";
                            $premium = "N/A";
                            $deductible = "N/A";
                            $coverage_description = "N/A";
                        }

                        // Prepare details based on the new structure
                        $details = "Tier: " . htmlspecialchars($tier) . "<br>"
                                 . "Coverage Amount: KES " . number_format($coverage_amount, 2) . "<br>"
                                 . "Premium: KES " . number_format($premium, 2) . "<br>"
                                 . "Deductible: KES " . number_format($deductible, 2) . "<br>"
                                 . "Description: " . htmlspecialchars($coverage_description);

                        echo "<tr>
                                <td>" . htmlspecialchars($policy['policy_id']) . "</td>
                                <td>" . htmlspecialchars($plan_name) . "</td>
                                <td>" . htmlspecialchars($policy['start_date']) . "</td>
                                <td>" . htmlspecialchars($policy['end_date']) . "</td>
                                <td>" . $details . "</td>
                              </tr>";
                    }
                    $conn->close();
                }
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">You have no policies at the moment.</div>
    <?php endif; ?>

    <!-- Navigation Link -->
    <div class="mt-4 text-center">
        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
