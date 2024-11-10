<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurance_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Access denied. Please log in.";
    exit;
}

// Initialize variables for feedback messages
$success_msg = "";
$error_msg = "";

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch claims submitted by the logged-in user's policies
$stmt = $conn->prepare("SELECT c.claim_id, c.claim_type, c.claim_description, c.claim_status, 
                        c.claim_amount, c.claim_date, c.resolution_date, c.comments 
                        FROM Claims c 
                        JOIN Policies p ON c.policy_id = p.policy_id 
                        WHERE p.user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all claims for the logged-in user
    $claims = [];
    while ($row = $result->fetch_assoc()) {
        $claims[] = $row;
    }
    $stmt->close();
} else {
    $error_msg = "Error fetching claims: " . htmlspecialchars($conn->error);
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>My Claims</title>
</head>
<body class="bg-gray-100 p-6">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">My Claims</h1>

        <!-- Display Success and Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>
        <center><a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <?php echo 'Home'; ?>
                </a></center><br>

        <!-- Claims Table -->
        <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-gray-800 text-white">
                    <th class="py-3 px-4 text-left">Claim ID</th>
                    <th class="py-3 px-4 text-left">Claim Type</th>
                    <th class="py-3 px-4 text-left">Description</th>
                    <th class="py-3 px-4 text-left">Status</th>
                    <th class="py-3 px-4 text-left">Amount (KES)</th>
                    <th class="py-3 px-4 text-left">Claim Date</th>
                    <th class="py-3 px-4 text-left">Resolution Date</th>
                    <th class="py-3 px-4 text-left">Comments</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($claims)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No claims found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_type']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_description']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_status']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_amount']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['claim_date']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['resolution_date']); ?></td>
                            <td class="border px-4 py-2"><?php echo htmlspecialchars($claim['comments']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
