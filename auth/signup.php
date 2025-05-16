<?php
// Import DB connection and response helpers
require_once "../db_conn.php";
include("../function/response.php");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name       = $_POST['name'] ?? '';
    $gender     = $_POST['gender'] ?? '';
    $dob        = $_POST['dob'] ?? '';
    $email      = $_POST['email'] ?? '';
    $password   = md5($_POST['password'] ?? '');
    $token      = bin2hex(random_bytes(16));
    $location   = $_POST['location'] ?? '';
    $about      = $_POST['about'] ?? null;
    $preferred_budget = $_POST['preferred_budget'] ?? 'Moderate';
    $is_active  = 1;
    $created_at = date("Y-m-d H:i:s");

    // Handle interests (expected as JSON string or array)
    $interests = [];
    if (isset($_POST['interests'])) {
        $raw = $_POST['interests'];
        $interests = is_array($raw) ? $raw : json_decode($raw, true);
        if (!is_array($interests)) {
            $interests = [];
        }
    }

    // Image upload
    $target_dir = "../uploads/";
    $image_name = basename($_FILES["image"]["name"]);
    $unique_name = time() . "_" . $image_name;
    $target_file = $target_dir . $unique_name;

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        api_error_response("Failed to upload image.");
        exit;
    }

    // Insert into user table
    $sql = "INSERT INTO user (name, gender, dob, email, password, token, location, image, created_at, is_active, about, budget_p) 
            VALUES ('$name', '$gender', '$dob', '$email', '$password', '$token', '$location', '$target_file', '$created_at', '$is_active', '$about', '$preferred_budget')";

    if (mysqli_query($conn, $sql)) {
        
        $get_user_query = "SELECT id FROM user WHERE email = '$email'";
        $get_user_result = mysqli_query($conn, $get_user_query);

    if ($get_user_result && mysqli_num_rows($get_user_result) > 0) {
        $user_data = mysqli_fetch_assoc($get_user_result);
        $user_id = $user_data['id'];

        // Insert into user_interest table
        foreach ($interests as $interest_id) {
            $interest_id = intval($interest_id);
            $insert_query = "INSERT INTO user_interest (user_id, interest_id) VALUES ('$user_id', '$interest_id')";
            mysqli_query($conn, $insert_query);
        }

        mysqli_close($conn);
        api_success_response("User registered successfully.");
    } else {
        api_error_response("Error: " . mysqli_error($conn));
    }
}}
?>
