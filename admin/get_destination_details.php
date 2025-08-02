<?php
require_once "../db_conn.php";
include("../function/response.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    api_error_response("Invalid request method.", 405);
    exit;
}

$token = $_POST['token'] ?? '';
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

$action = $_POST['action'] ?? '';
if ($action === 'accept') {
    $destination_id = intval($_POST['destination_id'] ?? 0);
    if ($destination_id <= 0) {
        api_error_response("Invalid destination ID.");
    }
    $check_sql = "SELECT id FROM destination WHERE id = $destination_id AND is_active = 0";
    $check_result = mysqli_query($conn, $check_sql);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE destination SET is_active = 1 WHERE id = $destination_id";
        if (mysqli_query($conn, $update_sql)) {
            api_success_response(["message" => "Destination accepted successfully!"]);
        } else {
            api_error_response("Failed to accept destination.");
        }
    } else {
        api_error_response("Destination not found or already active.");
    }
    exit;
} else if ($action === 'delete') {
    $destination_id = intval($_POST['destination_id'] ?? 0);
    if ($destination_id <= 0) {
        api_error_response("Invalid destination ID.");
    }
    $check_sql = "SELECT id FROM destination WHERE id = $destination_id";
    $check_result = mysqli_query($conn, $check_sql);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $delete_sql = "DELETE FROM destination WHERE id = $destination_id AND is_active = 0";
        if (mysqli_query($conn, $delete_sql)) {
            api_success_response(["message" => "Destination deleted successfully!"]);
        } else {
            api_error_response("Failed to delete destination.");
        }
    } else {
        api_error_response("Destination not found.");
    }
    exit;
}

$destination_id = intval($_POST['destination_id'] ?? 0);
if ($destination_id <= 0) {
    api_error_response("Invalid destination ID.");
    exit;
}

// Get destination details
$dest_result = mysqli_query($conn, "SELECT * FROM destination WHERE id = $destination_id");
if (!$dest_result || mysqli_num_rows($dest_result) == 0) {
    api_error_response("Destination not found.");
    exit;
}

$destination = mysqli_fetch_assoc($dest_result);

// // Get all active trips for this destination
// $trips = [];
// $trip_result = mysqli_query($conn, "SELECT t.id, t.name, t.description, t.budget, t.start_date, t.end_date, t.created_at, t.p_gender, u.name AS creator_name, u.email AS creator_email FROM trip t INNER JOIN user u ON t.created_by = u.id WHERE t.location = $destination_id AND t.is_active = 1 AND u.is_active = 1");
// if ($trip_result && mysqli_num_rows($trip_result) > 0) {
//     while ($row = mysqli_fetch_assoc($trip_result)) {
//         // Get interests for this trip
//         $trip_id = $row['id'];
//         $interests = [];
//         $interest_result = mysqli_query($conn, "SELECT i.name FROM trip_interest ti INNER JOIN interest i ON ti.interest_id = i.id WHERE ti.trip_id = $trip_id");
//         if ($interest_result && mysqli_num_rows($interest_result) > 0) {
//             while ($irow = mysqli_fetch_assoc($interest_result)) {
//                 $interests[] = $irow['name'];
//             }
//         }
//         $row['interests'] = $interests;
//         $trips[] = $row;
//     }
// }

$response = [
    "destination" => $destination,
    // "trips" => $trips
];

api_success_response(["data" => $response]);
?> 