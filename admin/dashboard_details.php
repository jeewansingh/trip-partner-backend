<?php
require_once "../db_conn.php";
include("../function/response.php");

// Accept POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    api_error_response("Invalid request method.", 405);
    exit;
}

// Get token from POST (form-data or x-www-form-urlencoded)
$token = $_POST['token'] ?? '';
// If not found, try to get token from JSON body
if (!$token) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data) && isset($data['token'])) {
        $token = $data['token'];
    }
}
$expected_token = hash('sha256', 'adminadmin');
if ($token !== $expected_token) {
    api_error_response("Invalid or missing admin token.", 401);
    exit;
}

// Get all users
$users = [];
$user_result = mysqli_query($conn, "SELECT id, name, email, gender, location AS address, image FROM user");
if ($user_result && mysqli_num_rows($user_result) > 0) {
    while ($row = mysqli_fetch_assoc($user_result)) {
        $users[] = $row;
    }
}

// Get all active destinations
$active_destinations = [];
$active_dest_result = mysqli_query($conn, "SELECT id, name, location, continent, image, description FROM destination WHERE is_active = 1");
if ($active_dest_result && mysqli_num_rows($active_dest_result) > 0) {
    while ($row = mysqli_fetch_assoc($active_dest_result)) {
        $active_destinations[] = $row;
    }
}

// Get all active trips
$active_trips = [];
$active_trip_result = mysqli_query($conn, "SELECT t.id, t.name, u.name AS createdBy, t.created_at, t.start_date, t.end_date FROM trip t INNER JOIN user u ON t.created_by = u.id WHERE t.is_active = 1");
if ($active_trip_result && mysqli_num_rows($active_trip_result) > 0) {
    while ($row = mysqli_fetch_assoc($active_trip_result)) {
        // Fetch interests for this trip
        $trip_id = $row['id'];
        $interests = [];
        $interest_result = mysqli_query($conn, "SELECT i.name FROM trip_interest ti INNER JOIN interest i ON ti.interest_id = i.id WHERE ti.trip_id = $trip_id");
        if ($interest_result && mysqli_num_rows($interest_result) > 0) {
            while ($irow = mysqli_fetch_assoc($interest_result)) {
                $interests[] = $irow['name'];
            }
        }
        $row['interests'] = $interests;
        $active_trips[] = $row;
    }
}

// Get all non-active destinations (new destination requests)
$new_destination_requests = [];
$inactive_dest_result = mysqli_query($conn, "SELECT id, name, location, continent, image, description FROM destination WHERE is_active = 0");
if ($inactive_dest_result && mysqli_num_rows($inactive_dest_result) > 0) {
    while ($row = mysqli_fetch_assoc($inactive_dest_result)) {
        $new_destination_requests[] = $row;
    }
}

$response = [
    "users" => $users,
    "active_destinations" => $active_destinations,
    "active_trips" => $active_trips,
    "new_destination_requests" => $new_destination_requests
];

api_success_response(["data" => $response]);
?> 