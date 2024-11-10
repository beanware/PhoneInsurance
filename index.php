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
    die("<div class='alert alert-danger text-center' role='alert'>
            Connection failed: " . htmlspecialchars($conn->connect_error) . "
         </div>");
}

// Fetch user role
$user_role = 'user'; // default

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

$loggedIn = isset($_SESSION['user_id']);  // Check if user is logged in
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insure | Protect What Matters Most</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1580894908361-0b2f5728e3b0?w=1200&auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh; /* Ensures the body takes at least the full viewport height */
            background-color: #f8f9fa; /* Fallback background color */
        }
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold text-blue-600">
                <a href="/">Insure</a>
            </div>
            <div class="flex space-x-4">
                <?php if ($loggedIn): ?>
                    <a href="my_plans.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">View My Plans</a>
                    <a href="add_claim.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Submit Claims</a>
                    <a href="my_claims.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">View My Claims</a>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="claims_review.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Review Claims</a>
                        <a href="insurance_plan_creation.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Modify Plans</a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Logout</a>
                <?php else: ?>
                    <a href="user_login.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Login</a>
                    <a href="user_registration.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="relative h-screen flex items-center justify-center text-center text-white bg-cover" style="background-image: url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8aW5zdXJhbmNlJTIwcHJvbW90aW9uc3xlbnwwfHwwfHx8MA%3D%3D');">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative z-10 p-8">
            <h1 class="text-4xl font-bold mb-4">Mobi Insure</h1>
            <p class="text-lg mb-2">Protect Your Device!</p>
            <p class="mb-8">At Mobi Insure, we offer a range of comprehensive insurance plans tailored to fit your mobile phone insurance needs and budget.</p>
            <a href="#plans" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">View Our Policies</a>
        </div>
    </header>

    <!-- Insurance Plans -->
    <section class="container mx-auto p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="plans">
            <!-- Health Insurance Plan -->
            <div class="bg-white rounded-lg shadow-md transition-transform transform hover:scale-105">
                <img src="https://images.unsplash.com/photo-1522125670776-3c7abb882bc2?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTR8fGNoZWFwJTIwbW9iaWxlfGVufDB8fDB8fHww" alt="Health Insurance" class="rounded-t-lg h-48 w-full object-cover">
                <div class="p-4">
                    <h2 class="text-2xl text-blue-600 mb-2">Tier 1 Mobile Phones</h2>
                    <ul class="list-disc pl-5 mb-4">
                        <li>Coverage Amount: KES 3,000</li>
                        <li>Premium Amount: KES 1,000 per year</li>
                        <li>Deductible Amount: KES 500</li>
                    </ul>
                    <p class="mb-4">For Phones worth 35,000 and below</p>
                    <ul class="list-disc pl-5">
                        <li>Phone Parts coverage: In case of any damage to your screen, battery, charging ports, sensors, cameras, etc.</li>
                        <li>Network of Hospitals: Access to top repair experts.</li>
                        <li>24/7 Customer Support: Assistance whenever you need it.</li>
                    </ul>
                </div>
            </div>

            <!-- Motor Insurance Plan -->
            <div class="bg-white rounded-lg shadow-md transition-transform transform hover:scale-105">
                <img src="https://images.unsplash.com/photo-1525770041010-2a1233dd8152?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTZ8fGNoZWFwJTIwbW9iaWxlfGVufDB8fDB8fHww" alt="Motor Insurance" class="rounded-t-lg h-48 w-full object-cover">
                <div class="p-4">
                    <h2 class="text-2xl text-blue-600 mb-2">Tier 2 Mobile Phones</h2>
                    <ul class="list-disc pl-5 mb-4">
                        <li>Coverage Amount: KES 8,000</li>
                        <li>Premium Amount: KES 3,500 per year</li>
                        <li>Deductible Amount: KES 1,500</li>
                    </ul>
                    <p class="mb-4">For Phones Worth 35,000 - 50,000</p>
                    <ul class="list-disc pl-5">
                        <li>High Cost Phone Parts coverage: In case of any damage to your screen, battery, charging ports, sensors, cameras, etc.</li>
                        <li>Repair Assistance: 24/7 support for repairs.</li>
                        <li>24/7 Customer Support: Assistance whenever you need it.</li>
                    </ul>
                </div>
            </div>

            <!-- Home Insurance Plan -->
            <div class="bg-white rounded-lg shadow-md transition-transform transform hover:scale-105">
                <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=600&auto=format&fit=crop&q=60&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8N3x8Y2hlYXAlMjBtb2JpbGV8ZW58MHx8MHx8fDA%3D" alt="Home Insurance" class="rounded-t-lg h-48 w-full object-cover">
                <div class="p-4">
                    <h2 class="text-2xl text-blue-600 mb-2">Tier 3 Mobile Phones</h2>
                    <ul class="list-disc pl-5 mb-4">
                        <li>Coverage Amount: KES 15,000</li>
                        <li>Premium Amount: KES 7,500 per year</li>
                        <li>Deductible Amount: KES 3,000</li>
                    </ul>
                    <p class="mb-4">For Phones Worth above 50,000</p>
                    <ul class="list-disc pl-5">
                        <li>Complete Phone Parts coverage: In case of any damage to your screen, battery, charging ports, sensors, cameras, etc.</li>
                        <li>Dedicated support: Tailored support for high-end devices.</li>
                        <li>Priority Service: Fast-tracked repairs.</li>
                    </ul>
                </div>
            </div>
        </div>
        <br>
        <center>
        
        <a href="<?php echo $loggedIn ? 'policy_creation.php?plan=life' : 'user_login.php'; ?>" class="bg-blue-600 text-white px-5 py-3 rounded hover:bg-gray-700 transition">
            Sign Up for Insure
        </a>
                </center>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2024 Mobi Insure. All rights reserved.</p>
        </div>
        <div class="container mx-auto flex justify-center items-center">
                <a href="#" aria-label="Facebook"><img src="https://img.icons8.com/ios-filled/24/ffffff/facebook.png" alt="Facebook"></a>
                <a href="#" aria-label="Twitter"><img src="https://img.icons8.com/ios-filled/24/ffffff/twitter.png" alt="Twitter"></a>
                <a href="#" aria-label="LinkedIn"><img src="https://img.icons8.com/ios-filled/24/ffffff/linkedin.png" alt="LinkedIn"></a>
                <a href="#" aria-label="Instagram"><img src="https://img.icons8.com/ios-filled/24/ffffff/instagram-new.png" alt="Instagram"></a>
            </div>
    </footer>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
