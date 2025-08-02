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

// Get total users
$user_result = mysqli_query($conn, "SELECT COUNT(*) AS total_users FROM user WHERE is_active = 1");
$user_row = mysqli_fetch_assoc($user_result);
$total_users = $user_row['total_users'] ?? 0;

// Get total active destinations
$active_dest_result = mysqli_query($conn, "SELECT COUNT(*) AS total_active_dest FROM destination WHERE is_active = 1");
$active_dest_row = mysqli_fetch_assoc($active_dest_result);
$total_active_dest = $active_dest_row['total_active_dest'] ?? 0;

// Get total non-active destinations (new destination requests)
$inactive_dest_result = mysqli_query($conn, "SELECT COUNT(*) AS total_inactive_dest FROM destination WHERE is_active = 0");
$inactive_dest_row = mysqli_fetch_assoc($inactive_dest_result);
$total_inactive_dest = $inactive_dest_row['total_inactive_dest'] ?? 0;

// Get total active trips
$active_trip_result = mysqli_query($conn, "SELECT COUNT(*) AS total_active_trips FROM trip t INNER JOIN user u ON t.created_by = u.id WHERE t.is_active = 1 AND u.is_active = 1");
$active_trip_row = mysqli_fetch_assoc($active_trip_result);
$total_active_trips = $active_trip_row['total_active_trips'] ?? 0;

$response = [
    "total_users" => $total_users,
    "total_active_destinations" => $total_active_dest,
    "total_active_trips" => $total_active_trips,
    "new_destination_requests" => $total_inactive_dest
];

api_success_response(["data" => $response]);
?> 