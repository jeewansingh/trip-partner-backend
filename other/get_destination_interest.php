<?php
// Include database connection
require_once "../db_conn.php";
include("../function/response.php"); // Include the response handling functions


// Set header to return JSON
header("Content-Type: application/json");

// Fetch destinations
$destinationQuery = "SELECT id, name FROM destination WHERE is_active = 1";
$destinationResult = mysqli_query($conn, $destinationQuery);

$destinations = [];
if ($destinationResult && mysqli_num_rows($destinationResult) > 0) {
    while ($row = mysqli_fetch_assoc($destinationResult)) {
        $destinations[] = $row;
    }
}

// Fetch interests
$interestQuery = "SELECT id, name FROM interest";
$interestResult = mysqli_query($conn, $interestQuery);

$interests = [];
if ($interestResult && mysqli_num_rows($interestResult) > 0) {
    while ($row = mysqli_fetch_assoc($interestResult)) {
        $interests[] = $row;
    }
}

// Send response
$response = [
    "destinations" => $destinations,
    "interests" => $interests
];

api_success_response($response);
?>
