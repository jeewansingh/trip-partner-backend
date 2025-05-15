<?php
require_once "../db_conn.php";
include("../function/response.php");

// Allow only POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $token = $_POST['token'];
    $trip_id = $_POST['trip_id'];

  // Get user_id from token
    $user_id = getUserIdFromToken($token); // You need to implement this
    if (!$user_id) {
        api_error_response("Invalid token or user not found.");
    exit;
    }

     if (empty($trip_id)) {
        api_error_response("Trip Id is required.");
    }

    // Check if already sent
    $sql_check = "SELECT * FROM join_request WHERE sender_id = $user_id AND trip_id = $trip_id";
    $result = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($result) > 0) {
        api_error_response("Join request already sent");
        exit;
    }

    // Insert new join request
    $sql_insert = "INSERT INTO join_request (sender_id, trip_id, status) VALUES ($user_id, $trip_id, 'pending')";
    if (mysqli_query($conn, $sql_insert)) {
        api_success_response("Join request sent successfully");
    } else {
        api_error_response("Database error: " . mysqli_error($conn));
    }

}

?>
