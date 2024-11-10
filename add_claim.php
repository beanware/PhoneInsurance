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
    die("<div class='alert alert-danger text-center' role='alert'>User not logged in. Please <a href='login.php'>login</a> to submit a claim.</div>");
}

// Initialize variables for feedback messages
$success_msg = "";
$error_msg = "";

// Fetch user's policies
$policies = [];
$stmt_policies = $conn->prepare("SELECT policy_id, plan_id FROM Policies WHERE user_id = ?");
if ($stmt_policies) {
    $stmt_policies->bind_param("i", $_SESSION['user_id']);
    $stmt_policies->execute();
    $result_policies = $stmt_policies->get_result();
    while ($row = $result_policies->fetch_assoc()) {
        $policies[] = $row;
    }
    $stmt_policies->close();
} else {
    $error_msg = "Error fetching policies: " . htmlspecialchars($conn->error);
}

// Claim date validation rule
$current_date = date("Y-m-d");
$seven_days_ago = date("Y-m-d", strtotime("-7 days"));

// Handle Claim Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_claim'])) {
    // Retrieve and sanitize form inputs
    $policy_id = intval($_POST['policy_id']);
    $claim_type = $_POST['claim_type'];
    $claim_description = trim($_POST['claim_description']);
    $claim_amount = floatval($_POST['claim_amount']);
    $claim_date = $_POST['claim_date'];

    // Validate claim date
    if ($claim_date > $current_date) {
        $error_msg = "Claim date cannot be in the future.";
    } elseif ($claim_date < $seven_days_ago) {
        $error_msg = "Claim date cannot be more than 7 days old.";
    } elseif (empty($policy_id) || empty($claim_type) || empty($claim_description) || empty($claim_amount) || empty($claim_date)) {
        $error_msg = "All fields are required.";
    } else {
        // Existing validation for policy ownership
        $stmt_verify = $conn->prepare("SELECT policy_id FROM Policies WHERE policy_id = ? AND user_id = ?");
        if ($stmt_verify) {
            $stmt_verify->bind_param("ii", $policy_id, $_SESSION['user_id']);
            $stmt_verify->execute();
            $stmt_verify->store_result();
            if ($stmt_verify->num_rows === 1) {
                $stmt_verify->close();

                // Prepare INSERT statement
                $stmt_insert = $conn->prepare("INSERT INTO Claims (policy_id, claim_type, claim_description, claim_amount, claim_date) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("issds", $policy_id, $claim_type, $claim_description, $claim_amount, $claim_date);
                    if ($stmt_insert->execute()) {
                        $success_msg = "Your claim has been submitted successfully.";
                    } else {
                        $error_msg = "Error submitting claim: " . htmlspecialchars($stmt_insert->error);
                    }
                    $stmt_insert->close();
                } else {
                    $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
                }
            } else {
                $error_msg = "Invalid policy selected.";
                $stmt_verify->close();
            }
        } else {
            $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
        }
    }
}


// Close the connection at the end
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Claim | Insurance Company</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .claim-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 50px auto;
        }
        h2 {
            text-align: center;
            color: #0d6efd;
            margin-bottom: 30px;
        }
    </style>
    <script>
        // Optional: Add any JavaScript if needed for additional functionality
    </script>
</head>
<body>

<div class="claim-container">
    <h2>Submit a Claim</h2>

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
    <!-- Date restriction alert -->
<div class="alert alert-info text-center" role="alert">
    Please ensure your claim date is not in the future and not more than 7 days in the past.
</div>


    <!-- Claim Submission Form -->
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
            <label for="policy_id" class="form-label">Select Policy:</label>
            <select id="policy_id" name="policy_id" class="form-select" required>
                <option value="">-- Select a Policy --</option>
                <?php foreach ($policies as $policy): ?>
                    <option value="<?php echo htmlspecialchars($policy['policy_id']); ?>">
                        Policy ID: <?php echo htmlspecialchars($policy['policy_id']); ?>
                        <!-- Optionally, fetch and display plan name -->
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="claim_type" class="form-label">Claim Type:</label>
            <select id="claim_type" name="claim_type" class="form-select" required>
                <option value="">-- Select Claim Type --</option>
                <option value="damage">Damage</option>
                <option value="theft">Theft</option>
                <option value="loss">Loss</option>
                <option value="mechanical">Mechanical</option>
                <option value="water">Water</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="claim_description" class="form-label">Claim Description:</label>
            <textarea id="claim_description" name="claim_description" class="form-control" rows="4" required></textarea>
        </div>

        <div class="mb-3">
            <label for="claim_amount" class="form-label">Claim Amount (KES):</label>
            <input type="number" id="claim_amount" name="claim_amount" class="form-control" step="0.01" min="0" required>
        </div>

        <div class="mb-3">
            <label for="claim_date" class="form-label">Claim Date:</label>
            <input type="date" id="claim_date" name="claim_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <button type="submit" name="submit_claim" class="btn btn-primary w-100">Submit Claim</button>
    </form>

    <!-- Navigation Link -->
    <div class="mt-3 text-center">
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
