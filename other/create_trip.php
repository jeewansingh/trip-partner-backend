<?php

require_once "../db_conn.php";
include("../function/response.php"); 
function getUserIdFromToken($token) {
    global $conn;
    $sql = "SELECT id FROM user WHERE token = '$token' AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['id'];
    } else {
        return null; // Invalid token
    }
}


$sql_create = "CREATE TABLE IF NOT EXISTS trip (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10, 2) NOT NULL,
    location INT(11) NOT NULL,  
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    p_gender ENUM('male', 'female', 'other') NOT NULL,
    created_by INT(11),  
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE SET NULL,  
    FOREIGN KEY (location) REFERENCES destination(id) ON DELETE CASCADE  
)";


if (mysqli_query($conn, $sql_create)) {
   
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}


// Function to fetch trips based on user id (Token)


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token       = $_POST['token']; // Token passed from frontend
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $budget      = $_POST['budget'];
    $location    = $_POST['location'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $p_gender    = $_POST['p_gender'];
    $is_active   = isset($_POST['is_active']) ? $_POST['is_active'] : 1; 
    
    $user_id = getUserIdFromToken($token);

    if (!$user_id) {
        api_error_response("Invalid token or user not found.");
        exit;
    }


    if (empty($name) || empty($description) || empty($budget) || empty($location) || empty($start_date) || empty($end_date) || empty($p_gender)) {
        api_error_response("All fields are required.");
    }

    $sql_insert = "INSERT INTO trip (name, description, budget, location, start_date, end_date, created_by, p_gender, is_active)
                   VALUES ('$name', '$description', '$budget', '$location', '$start_date', '$end_date', '$user_id', '$p_gender', '$is_active')";


    if (mysqli_query($conn, $sql_insert)) {
        api_success_response("Trip created successfully.");
    } else {
        api_error_response("Error creating trip: " . mysqli_error($conn));
    }


    mysqli_close($conn);
}


// Handle GET method for fetching trips
if ($_SERVER["REQUEST_METHOD"] == "GE T") {
    $token = $_GET['token']; // Token passed from frontend

    // Get the user ID from the token
    $user_id = getUserIdFromToken($token);

    if (!$user_id) {
        api_error_response("Invalid token or user not found.");
        exit;
    }

    // Fetch trips created by the user
    $trips = getTrips($user_id);

    if ($trips) {
        echo json_encode($trips); // Return the trips as JSON response
    } else {
        api_error_response("No trips found.");
    }

    mysqli_close($conn);
}
?>
