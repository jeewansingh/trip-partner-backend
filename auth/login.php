<?php

// Import the DB connection
require_once "../db_conn.php";
include("../function/response.php");

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $email      = $_POST['email'];
    $password   = md5($_POST['password']);
    $token      = bin2hex(random_bytes(16));
    
    // Handle Signin
        $sql = "SELECT token, id, password FROM user WHERE email = '$email' AND password='$password' AND is_active = 1";
        $result = mysqli_query($conn, $sql);
 
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $token = $row['token'];
            $id = $row['id'];

            $response = [
                "message" => "Logged in successfully",
                "token" => $token
            ];
            api_success_response($response);
            
        } else {
            api_error_response("Invalid email or password.");
        }


    mysqli_close($conn);
}
?>
