<?php

require_once "../db_conn.php";
include("../function/response.php");

$token = $_POST['token'] ?? null;
$action = $_POST['action'] ?? 'get'; // default is 'get'

// Step 1: Validate token
if (!$token) {
    api_error_response("Token is required.");
    exit;
}

// Get user ID from token
$user_id = getUserIdFromToken($token);
if (!$user_id) {
    api_error_response("Invalid token or user not found.");
    exit;
}

// STEP 2: SELECT USER PROFILE
if ($action === 'get') {
    $sql = "SELECT name, image, gender, dob, email, about, budget_p, location 
            FROM user 
            WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        api_error_response("User not found.");
        exit;
    }

    $user = mysqli_fetch_assoc($result);

    // Get interests
    $interest_sql = "
        SELECT i.id AS interest_id, i.name AS interest_name
        FROM user_interest ui
        JOIN interest i ON ui.interest_id = i.id
        WHERE ui.user_id = $user_id
    ";
    $interest_result = mysqli_query($conn, $interest_sql);
    $interests = [];

    while ($row = mysqli_fetch_assoc($interest_result)) {
        $interests[] = $row;
    }

    $user['interests'] = $interests;
    echo json_encode([
        "success" => true,
        "data" => $user
      ]);
      

}

// STEP 3: UPDATE PROFILE
else if ($action === 'update') {
    // Get fields from POST
    $name     = mysqli_real_escape_string($conn, $_POST['name']);
    $gender   = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob      = mysqli_real_escape_string($conn, $_POST['dob']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $about    = mysqli_real_escape_string($conn, $_POST['about']);
    $budget   = mysqli_real_escape_string($conn, $_POST['budget']);
    $image    = mysqli_real_escape_string($conn, $_POST['image']);
    $interests = isset($_POST['interests']) ? $_POST['interests'] : [];

    // Update user table
    $update_sql = "
        UPDATE user 
        SET name='$name', gender='$gender', dob='$dob', location='$location',
            about='$about', budget_p='$budget', image='$image'
        WHERE id=$user_id
    ";
    $update_result = mysqli_query($conn, $update_sql);

    if (!$update_result) {
        api_error_response("Failed to update user info.");
        exit;
    }

    // Update user_interest table
    // 1. Delete old interests
    mysqli_query($conn, "DELETE FROM user_interest WHERE user_id = $user_id");

    // 2. Insert new ones
    foreach ($interests as $interest_id) {
        $interest_id = intval($interest_id);
        mysqli_query($conn, "INSERT INTO user_interest (user_id, interest_id) VALUES ($user_id, $interest_id)");
    }

    api_success_response("Profile updated successfully.");
}

// Invalid action
else {
    api_error_response("Invalid action.");
}


?>