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
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$edit_policy = null;
$policy_id = null;

// Fetch user ID and role from session (assuming user role is stored in session)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch insurance plans for dropdown
$plans_result = $conn->query("SELECT plan_id, plan_name FROM InsurancePlans");
$plans = $plans_result->fetch_all(MYSQLI_ASSOC);

// Check if we're editing a policy
if (isset($_GET['policy_id'])) {
    $policy_id = $_GET['policy_id'];
    $stmt = $conn->prepare("SELECT * FROM Policies WHERE policy_id = ?");
    $stmt->bind_param("i", $policy_id); // Binding the parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_policy = $result->fetch_assoc();
    $stmt->close();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_id = $_POST['plan_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $phone_model = $_POST['phone_model'];
    $serial_number = $_POST['serial_number'];
    
    if (isset($_POST['policy_id']) && !empty($_POST['policy_id'])) {
        // Check if user is admin before updating
        if ($is_admin) {
            // Update existing policy
            $policy_id = $_POST['policy_id'];
            $stmt = $conn->prepare("UPDATE Policies SET user_id = ?, plan_id = ?, start_date = ?, end_date = ?, phone_model = ?, serial_number = ? WHERE policy_id = ?");
            $stmt->bind_param("iissssi", $user_id, $plan_id, $start_date, $end_date, $phone_model, $serial_number, $policy_id);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "<script>alert('You do not have permission to edit policies.');</script>";
        }
    } else {
        // Create new policy
        $stmt = $conn->prepare("INSERT INTO Policies (user_id, plan_id, start_date, end_date, phone_model, serial_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $user_id, $plan_id, $start_date, $end_date, $phone_model, $serial_number);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete policy
if (isset($_GET['delete_id'])) {
    if ($is_admin) {
        $delete_id = $_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM Policies WHERE policy_id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: policy_creation.php"); // Redirect after deletion
    } else {
        echo "<script>alert('You do not have permission to delete policies.');</script>";
    }
}

// Read policies for display
$result = $conn->query("SELECT * FROM Policies WHERE user_id = $user_id");
$policies = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Policy Management</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-blue-600">Policy Management</h1>

        <!-- Policy Form -->
        <form action="" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <input type="hidden" name="policy_id" id="policy_id" value="<?php echo $edit_policy ? $edit_policy['policy_id'] : ''; ?>">
            
            <div class="mb-4">
                <label for="user_id" class="block text-gray-700 text-sm font-bold mb-2">User ID</label>
                <input type="number" name="user_id" readonly value="<?php echo $user_id; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="User ID">
            </div>
            
            <div class="mb-4">
                <label for="plan_id" class="block text-gray-700 text-sm font-bold mb-2">Select Plan</label>
                <select name="plan_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="" disabled selected>Select a plan</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo $plan['plan_id']; ?>" <?php echo $edit_policy && $edit_policy['plan_id'] == $plan['plan_id'] ? 'selected' : ''; ?>>
                            <?php echo $plan['plan_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Start Date</label>
                <input type="date" name="start_date" required value="<?php echo $edit_policy ? $edit_policy['start_date'] : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-4">
                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">End Date</label>
                <input type="date" name="end_date" required value="<?php echo $edit_policy ? $edit_policy['end_date'] : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div class="mb-4">
                <label for="phone_model" class="block text-gray-700 text-sm font-bold mb-2">Phone Model</label>
                <input type="text" name="phone_model" value="<?php echo $edit_policy ? $edit_policy['phone_model'] : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Phone Model">
            </div>
            
            <div class="mb-4">
                <label for="serial_number" class="block text-gray-700 text-sm font-bold mb-2">Serial Number</label>
                <input type="text" name="serial_number" value="<?php echo $edit_policy ? $edit_policy['serial_number'] : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Serial Number">
            </div>
            
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <?php echo $edit_policy ? 'Update Policy' : 'Create Policy'; ?>
                </button>
                <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <?php echo 'Home'; ?>
                </a>
            </div>
        </form>

        <!-- Policies Table -->
        <h2 class="text-2xl font-bold mb-4 text-blue-600">Existing Policies</h2>
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">Policy ID</th>
                    <th class="py-2 px-4 border-b">User ID</th>
                    <th class="py-2 px-4 border-b">Plan ID</th>
                    <th class="py-2 px-4 border-b">Start Date</th>
                    <th class="py-2 px-4 border-b">End Date</th>
                    <th class="py-2 px-4 border-b">Phone Model</th>
                    <th class="py-2 px-4 border-b">Serial Number</th>
                    <?php if ($is_admin): ?>
                    <th class="py-2 px-4 border-b">Actions</th>
                    <?php endif; ?>
                    
                </tr>
            </thead>
            <tbody>
                <?php foreach ($policies as $policy): ?>
                <tr>
                    <td class="py-2 px-4 border-b"><?php echo $policy['policy_id']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['user_id']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['plan_id']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['start_date']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['end_date']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['phone_model']; ?></td>
                    <td class="py-2 px-4 border-b"><?php echo $policy['serial_number']; ?></td>
                    <td class="py-2 px-4 border-b">
                        <?php if ($is_admin): ?>
                            <a href="?policy_id=<?php echo $policy['policy_id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            <a href="?delete_id=<?php echo $policy['policy_id']; ?>" class="text-red-600 hover:text-red-800">Delete</a>
                        <?php else: ?>
                            <!-- <span class="text-gray-500">Only for admins</span> -->
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
