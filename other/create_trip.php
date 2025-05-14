<?php

require_once "../db_conn.php";
include("../function/response.php"); 

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


    $token       = $_POST['token'];
    $name        = $_POST['name'];
    $description = $_POST['description'];
    $budget      = $_POST['budget'];
    $location    = $_POST['location'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $p_gender    = $_POST['p_gender'];
   $interests = json_decode($_POST['interests'], true); // Expecting array of interest_ids
    $is_active   = isset($_POST['is_active']) ? $_POST['is_active'] : 1;

    $user_id = getUserIdFromToken($token);

    if (!$user_id) {
        api_error_response("Invalid token or user not found.");
        exit;
    }

    if (empty($name) || empty($description) || empty($interests) || empty($budget) || empty($location) || empty($start_date) || empty($end_date) || empty($p_gender)) {
        api_error_response("All fields are required.");
    }

    // Insert the trip
    $sql_insert = "INSERT INTO trip (name, description, budget, location, start_date, end_date, created_by, p_gender, is_active)
                   VALUES ('$name', '$description', '$budget', '$location', '$start_date', '$end_date', '$user_id', '$p_gender', '$is_active')";

    if (mysqli_query($conn, $sql_insert)) {
        $trip_id = mysqli_insert_id($conn); // Get inserted trip ID

        // Insert interests into trip_interest
        foreach ($interests as $interest_id) {
            $interest_id = mysqli_real_escape_string($conn, $interest_id);
            $insert_interest = "INSERT INTO trip_interest (trip_id, interest_id) VALUES ('$trip_id', '$interest_id')";
            mysqli_query($conn, $insert_interest); // Optional: check for failure
        }

        api_success_response("Trip created successfully.");
    } else {
        api_error_response("Error creating trip: " . mysqli_error($conn));
    }

    mysqli_close($conn);
}

?>
