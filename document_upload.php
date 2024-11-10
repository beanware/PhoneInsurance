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

// Fetch claims for dropdown
$claims_query = "SELECT claim_id FROM Claims";
$claims_result = $conn->query($claims_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['document'])) {
    $claim_id = $_POST['claim_id'];
    $document_type = $_POST['document_type'];
    $document_path = 'uploads/' . basename($_FILES['document']['name']);

    // Create the uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    if (move_uploaded_file($_FILES['document']['tmp_name'], $document_path)) {
        $sql = "INSERT INTO Documents (claim_id, document_type, document_path)
                VALUES ('$claim_id', '$document_type', '$document_path')";

        if ($conn->query($sql) === TRUE) {
            echo "Document uploaded successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Error uploading file.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Document</title>
</head>
<body>
    <h2>Upload Document</h2>
    <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <label for="claim_id">Claim ID:</label>
        <select id="claim_id" name="claim_id" required>
            <?php
            if ($claims_result->num_rows > 0) {
                while ($row = $claims_result->fetch_assoc()) {
                    echo "<option value='{$row['claim_id']}'>{$row['claim_id']}</option>";
                }
            }
            ?>
        </select><br><br>

        <label for="document_type">Document Type:</label>
        <select id="document_type" name="document_type" required>
            <option value="photo">Photo</option>
            <option value="receipt">Receipt</option>
            <option value="report">Report</option>
        </select><br><br>

        <label for="document">Document File:</label>
        <input type="file" id="document" name="document" required><br><br>

        <input type="submit" value="Upload Document">
    </form>
</body>
</html>
