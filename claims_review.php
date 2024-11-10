<?php
// Start the session
session_start();

// Database connection parameters
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "insurance_db";

// Create connection using MySQLi with error reporting
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger text-center' role='alert'>
            Connection failed: " . htmlspecialchars($conn->connect_error) . "
         </div>");
}

// Ensure user is logged in and has appropriate role (assuming role management is implemented)
if (!isset($_SESSION['user_id'])) {
    die("<div class='alert alert-danger text-center' role='alert'>User not logged in.</div>");
}

// Fetch user role (assuming 'admin' can edit/delete claims)
$user_role = 'user'; // default role

$stmt_role = $conn->prepare("SELECT role FROM Users WHERE user_id = ?");
if ($stmt_role) {
    $stmt_role->bind_param("i", $_SESSION['user_id']);
    $stmt_role->execute();
    $stmt_role->bind_result($role);
    if ($stmt_role->fetch()) {
        $user_role = $role;
    }
    $stmt_role->close();
} else {
    die("<div class='alert alert-danger text-center' role='alert'>Error fetching user role.</div>");
}

// Initialize variables for feedback messages
$success_msg = "";
$error_msg = "";

// Handle form submissions for update and delete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_claim'])) {
        // Update Claim
        $claim_id = intval($_POST['claim_id']);
        $status = $_POST['status'];
        $comments = trim($_POST['comments']);
        
        // Set resolution_date to current date if not provided
        $resolution_date = !empty($_POST['resolution_date']) ? $_POST['resolution_date'] : date('Y-m-d');

        // Validate inputs
        $allowed_statuses = ['submitted', 'under_review', 'approved', 'rejected'];
        if (!in_array($status, $allowed_statuses)) {
            $error_msg = "Invalid claim status selected.";
        } else {
            // Prepare and bind
            $stmt_update = $conn->prepare("UPDATE Claims SET claim_status = ?, comments = ?, resolution_date = ? WHERE claim_id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("sssi", $status, $comments, $resolution_date, $claim_id);
                if ($stmt_update->execute()) {
                    $success_msg = "Claim updated successfully.";
                } else {
                    $error_msg = "Error updating claim: " . htmlspecialchars($stmt_update->error);
                }
                $stmt_update->close();
            } else {
                $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
            }
        }
    }elseif (isset($_POST['delete_claim'])) {
        // Delete Claim
        if ($user_role !== 'admin') {
            $error_msg = "You do not have permission to delete claims.";
        } else {
            $claim_id = intval($_POST['delete_claim_id']);

            // Verify claim exists
            $stmt_verify = $conn->prepare("SELECT claim_id FROM Claims WHERE claim_id = ?");
            if ($stmt_verify) {
                $stmt_verify->bind_param("i", $claim_id);
                $stmt_verify->execute();
                $stmt_verify->store_result();
                if ($stmt_verify->num_rows === 1) {
                    $stmt_verify->close();

                    // Proceed to delete
                    $stmt_delete = $conn->prepare("DELETE FROM Claims WHERE claim_id = ?");
                    if ($stmt_delete) {
                        $stmt_delete->bind_param("i", $claim_id);
                        if ($stmt_delete->execute()) {
                            $success_msg = "Claim deleted successfully.";
                        } else {
                            $error_msg = "Error deleting claim: " . htmlspecialchars($stmt_delete->error);
                        }
                        $stmt_delete->close();
                    } else {
                        $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
                    }
                } else {
                    $error_msg = "Claim not found.";
                    $stmt_verify->close();
                }
            } else {
                $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
            }
        }
    }
}

// Handle Edit Claim (Fetch existing data)
$edit_claim = null;
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['edit'])) {
    $claim_id = intval($_GET['edit']);

    // Only admins can edit claims
    if ($user_role !== 'admin') {
        $error_msg = "You do not have permission to edit claims.";
    } else {
        // Fetch claim details securely
        $stmt_fetch = $conn->prepare("SELECT claim_id, policy_id, claim_type, claim_description, claim_status, claim_amount, claim_date, resolution_date, comments FROM Claims WHERE claim_id = ?");
        if ($stmt_fetch) {
            $stmt_fetch->bind_param("i", $claim_id);
            $stmt_fetch->execute();
            $result = $stmt_fetch->get_result();
            if ($result->num_rows === 1) {
                $edit_claim = $result->fetch_assoc();
            } else {
                $error_msg = "Claim not found.";
            }
            $stmt_fetch->close();
        } else {
            $error_msg = "Prepare failed: " . htmlspecialchars($conn->error);
        }
    }
}

// Fetch all claims for display
$claims = [];
$sql_fetch_claims = "SELECT claim_id, policy_id, claim_type, claim_description, claim_status, claim_amount, claim_date, resolution_date, comments FROM Claims ORDER BY claim_date DESC";
$stmt_claims = $conn->prepare($sql_fetch_claims);

if ($stmt_claims) {
    $stmt_claims->execute();
    $result_claims = $stmt_claims->get_result();
    while ($row = $result_claims->fetch_assoc()) {
        $claims[] = $row;
    }
    $stmt_claims->close();
} else {
    $error_msg = "Error fetching claims: " . htmlspecialchars($conn->error);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Claims</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            padding: 30px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .action-buttons a {
            margin-right: 5px;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Review Claims</h2>

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

    <!-- Display Claims Data -->
    <h3>Claims List</h3>
    <?php if (count($claims) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Claim ID</th>
                    <th>Policy ID</th>
                    <th>Claim Type</th>
                    <th>Claim Description</th>
                    <th>Claim Status</th>
                    <th>Claim Amount</th>
                    <th>Claim Date</th>
                    <th>Resolution Date</th>
                    <th>Comments</th>
                    <?php if ($user_role === 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($claims as $claim): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                        <td><?php echo htmlspecialchars($claim['policy_id']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($claim['claim_type'])); ?></td>
                        <td><?php echo htmlspecialchars($claim['claim_description']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($claim['claim_status'])); ?></td>
                        <td><?php echo number_format($claim['claim_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($claim['claim_date']); ?></td>
                        <td><?php echo htmlspecialchars($claim['resolution_date']); ?></td>
                        <td><?php echo htmlspecialchars($claim['comments']); ?></td>
                        <?php if ($user_role === 'admin'): ?>
                            <td class="action-buttons">
                                <a href="?edit=<?php echo htmlspecialchars($claim['claim_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline;">
                                    <input type="hidden" name="delete_claim_id" value="<?php echo htmlspecialchars($claim['claim_id']); ?>">
                                    <button type="submit" name="delete_claim" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this claim?');">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info" role="alert">No claims found.</div>
    <?php endif; ?>

    <!-- Edit Claim Form -->
    <?php if ($edit_claim): ?>
        <h3>Edit Claim</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-5">
            <input type="hidden" name="claim_id" value="<?php echo htmlspecialchars($edit_claim['claim_id']); ?>">

            <div class="mb-3">
                <label for="policy_id" class="form-label">Policy ID:</label>
                <input type="number" id="policy_id" name="policy_id" value="<?php echo htmlspecialchars($edit_claim['policy_id']); ?>" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label for="claim_type" class="form-label">Claim Type:</label>
                <input type="text" id="claim_type" name="claim_type" value="<?php echo htmlspecialchars($edit_claim['claim_type']); ?>" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label for="claim_description" class="form-label">Claim Description:</label>
                <textarea id="claim_description" name="claim_description" rows="4" class="form-control" readonly><?php echo htmlspecialchars($edit_claim['claim_description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="submitted" <?php if ($edit_claim['claim_status'] == 'submitted') echo 'selected'; ?>>Submitted</option>
                    <option value="under_review" <?php if ($edit_claim['claim_status'] == 'under_review') echo 'selected'; ?>>Under Review</option>
                    <option value="approved" <?php if ($edit_claim['claim_status'] == 'approved') echo 'selected'; ?>>Approved</option>
                    <option value="rejected" <?php if ($edit_claim['claim_status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="claim_amount" class="form-label">Claim Amount:</label>
                <input type="number" id="claim_amount" name="claim_amount" value="<?php echo htmlspecialchars($edit_claim['claim_amount']); ?>" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label for="claim_date" class="form-label">Claim Date:</label>
                <input type="date" id="claim_date" name="claim_date" value="<?php echo htmlspecialchars($edit_claim['claim_date']); ?>" class="form-control" readonly>
            </div>

            <div class="mb-3">
    <label for="resolution_date" class="form-label">Resolution Date:</label>
    <input type="date" id="resolution_date" name="resolution_date"
           value="<?php echo htmlspecialchars($edit_claim['resolution_date'] ?? date('Y-m-d')); ?>" 
           class="form-control">
</div>


            <div class="mb-3">
                <label for="comments" class="form-label">Comments:</label>
                <textarea id="comments" name="comments" rows="4" class="form-control"><?php echo htmlspecialchars($edit_claim['comments']); ?></textarea>
            </div>

            <button type="submit" name="update_claim" class="btn btn-primary">Update Claim</button>
            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<!-- Bootstrap JS for interactivity -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
