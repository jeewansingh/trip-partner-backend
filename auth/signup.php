<?php
// Import the DB connection
require_once "../db_conn.php";
include("../function/response.php");

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name       = $_POST['name'];
    $gender     = $_POST['gender'];
    $dob        = $_POST['dob'];
    $email      = $_POST['email'];
    $password   =md5($_POST['password']);
    $token      = bin2hex(random_bytes(16));
    $location   = $_POST['location'];
    $is_active  = 1;
    $created_at = date("Y-m-d H:i:s");

    // Handle image upload
    $target_dir = "../uploads/";
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . time() . "_" . $image_name;


    // Handle Signup
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO user (name, gender, dob, email, password, token, location, image, created_at, is_active) 
                VALUES ('$name', '$gender', '$dob', '$email', '$password', '$token', '$location', '$target_file', '$created_at', '$is_active')";

        if (mysqli_query($conn, $sql)) {
           
            api_success_response("User registered successfully.");
        } else {
            echo "Error: " . mysqli_error($conn);
            
        }
    } else {
        api_error_response("Failed to upload image.");
    }

    mysqli_close($conn);
}
?>
