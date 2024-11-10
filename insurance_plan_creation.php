<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurance_db";

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Start session management
session_start();

// Ensure user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "Access denied. Please log in as an admin.";
    exit;
}

// Feedback messages
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = "";
$editing_plan = null;

// Handle form submissions for adding/updating plans
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and validate inputs
    $plan_name = trim($_POST['plan_name']);
    $tier = in_array($_POST['tier'], ['tier1', 'tier2', 'premium']) ? $_POST['tier'] : null;
    $coverage_amount = floatval($_POST['coverage_amount']);
    $premium = floatval($_POST['premium_amount']);
    $deductible = floatval($_POST['deductible']);
    $coverage_description = trim($_POST['coverage_details']);

    // Check required fields
    if (empty($plan_name) || !$tier || $coverage_amount <= 0 || $premium <= 0 || $deductible < 0) {
        $error_msg = "Please fill in all required fields correctly.";
    } else {
        // Determine if updating an existing plan or adding a new one
        if (!empty($_POST['plan_id'])) {
            // Update plan
            $plan_id = intval($_POST['plan_id']);
            $stmt = $conn->prepare("UPDATE InsurancePlans SET plan_name = ?, tier = ?, coverage_amount = ?, premium = ?, deductible = ?, coverage_description = ? WHERE plan_id = ?");
            if ($stmt) {
                $stmt->bind_param("ssddsssi", $plan_name, $tier, $coverage_amount, $premium, $deductible, $coverage_description, $plan_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Insurance plan updated successfully.";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error_msg = "Error updating plan: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
            }
        } else {
            // Insert new plan
            $stmt = $conn->prepare("INSERT INTO InsurancePlans (plan_name, tier, coverage_amount, premium, deductible, coverage_description) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                // Correct parameter types in bind_param
                $stmt->bind_param("ssddds", $plan_name, $tier, $coverage_amount, $premium, $deductible, $coverage_description);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "New insurance plan created successfully.";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error_msg = "Error creating plan: " . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
            }

        }
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM InsurancePlans WHERE plan_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Insurance plan deleted successfully.";
        } else {
            $error_msg = "Error deleting plan: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
    }
}

// Handle edit request
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM InsurancePlans WHERE plan_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $editing_plan = $result->fetch_assoc();
        $stmt->close();
    }
}

// Fetch all existing plans
$plans = [];
$result = $conn->query("SELECT * FROM InsurancePlans");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
}

// Close the database connection
$conn->close();

// Display success message if available
if ($success_msg) {
    echo "<p style='color: green;'>$success_msg</p>";
    unset($_SESSION['success_msg']);
}
?>


<!DOCTYPE html>
<html>
<head>
    <title><?php echo $editing_plan ? 'Edit Insurance Plan' : 'Add Insurance Plan'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $editing_plan ? 'Edit Insurance Plan' : 'Add New Insurance Plan'; ?></h2>

        <!-- Display Success and Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form for Adding or Editing Plan -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-5">
            <input type="hidden" name="plan_id" value="<?php echo $editing_plan['plan_id'] ?? ''; ?>">
            <div class="mb-3">
                <label for="plan_name" class="form-label">Plan Name:</label>
                <input type="text" id="plan_name" name="plan_name" class="form-control" required value="<?php echo htmlspecialchars($editing_plan['plan_name'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="tier" class="form-label">Tier:</label>
                <select id="tier" name="tier" class="form-control" required>
                    <option value="tier1" <?php echo ($editing_plan['tier'] ?? '') === 'tier1' ? 'selected' : ''; ?>>Tier 1</option>
                    <option value="tier2" <?php echo ($editing_plan['tier'] ?? '') === 'tier2' ? 'selected' : ''; ?>>Tier 2</option>
                    <option value="premium" <?php echo ($editing_plan['tier'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="coverage_amount" class="form-label">Coverage Amount (in USD):</label>
                <input type="number" id="coverage_amount" name="coverage_amount" class="form-control" step="0.01" required value="<?php echo htmlspecialchars($editing_plan['coverage_amount'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="premium_amount" class="form-label">Premium Amount (in USD):</label>
                <input type="number" id="premium_amount" name="premium_amount" class="form-control" step="0.01" required value="<?php echo htmlspecialchars($editing_plan['premium'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="deductible" class="form-label">Deductible (in USD):</label>
                <input type="number" id="deductible" name="deductible" class="form-control" step="0.01" required value="<?php echo htmlspecialchars($editing_plan['deductible'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="coverage_details" class="form-label">Coverage Description:</label>
                <textarea id="coverage_details" name="coverage_details" class="form-control"><?php echo htmlspecialchars($editing_plan['coverage_description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $editing_plan ? 'Update Plan' : 'Add Plan'; ?></button>
        </form>

        <!-- List of Insurance Plans -->
        <h2>Existing Insurance Plans</h2>
        <?php if (count($plans) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plan Name</th>
                        <th>Tier</th>
                        <th>Coverage Amount</th>
                        <th>Premium</th>
                        <th>Deductible</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plan['plan_id']); ?></td>
                            <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                            
                            <td><?php echo htmlspecialchars($plan['tier']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($plan['coverage_amount'], 2)); ?></td>
                            <td><?php echo htmlspecialchars(number_format($plan['premium'], 2)); ?></td>
                            <td><?php echo htmlspecialchars(number_format($plan['deductible'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($plan['coverage_description']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?edit_id=' . $plan['plan_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?delete_id=' . $plan['plan_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this plan?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No insurance plans found.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
