<?php
// Import the DB connection
require_once "../db_conn.php";
include("../function/response.php"); // Include the response handling functions

header("Content-Type: application/json");



// Function to handle the image upload
function handleImageUpload($file) {
    $target_dir = "../uploads/"; // Directory to store the uploaded images
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if the image file is a valid image
    if (getimagesize($file["tmp_name"]) === false) {
        api_error_response("File is not an image.");
        exit;
    }

    // Check file size (e.g., 5MB max)
    if ($file["size"] > 5000000) {
        api_error_response("Sorry, your file is too large.");
        exit;
    }

    // Allow only certain image formats (e.g., JPG, PNG, GIF)
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        api_error_response("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        exit;
    }

    // Try to upload the file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file; // Return the file path
    } else {
        api_error_response("Sorry, there was an error uploading your file.");
        exit;
    }
}

// Create the table only if it doesn't exist (optional step in production)
$sql_create = "CREATE TABLE IF NOT EXISTS destination (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    reason TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    continent VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE SET NULL
)";
mysqli_query($conn, $sql_create);

// Only handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token       = $_POST['token'] ?? '';
    $name        = $_POST['destinationName'] ?? '';
    $description = $_POST['description'] ?? '';
    $reason      = $_POST['reason'] ?? '';
    $location    = $_POST['country'] ?? '';
    $continent   = $_POST['continent'] ?? '';
    $image       = $_FILES['image'] ?? null; // Get image file from POST
    $is_active   = $_POST['is_active'] ?? 1;

    // Check for token
    $user_id = getUserIdFromToken($token);
    if (!$user_id) {
        api_error_response("Invalid token or user not found.");
        exit;
    }

    // Basic validation
    if (!$name || !$description || !$reason || !$location || !$continent) {
        api_error_response("All fields are required.");
        exit;
    }

    // Handle the image upload
    $image_path = null;
    if ($image) {
        $image_path = handleImageUpload($image); // Get the uploaded image path
    }

    // Insert the destination
    $sql_insert = "INSERT INTO destination (name, description, reason, location, continent, image, created_by, is_active)
                   VALUES ('$name', '$description', '$reason', '$location', '$continent', '$image_path', '$user_id', '$is_active')";

    if (mysqli_query($conn, $sql_insert)) {
        api_success_response("Destination created successfully.");
    } else {
        api_error_response("Error creating destination: " . mysqli_error($conn));
    }
}
?>
