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
if ($action === 'delete') {
    $trip_id = intval($_POST['trip_id'] ?? 0);
    if ($trip_id <= 0) {
        api_error_response("Invalid trip ID.");
    }
    $check_sql = "SELECT t.id FROM trip t INNER JOIN user u ON t.created_by = u.id WHERE t.id = $trip_id AND t.is_active = 1 AND u.is_active = 1";
    $check_result = mysqli_query($conn, $check_sql);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $delete_sql = "UPDATE trip SET is_active = 0 WHERE id = $trip_id";
        if (mysqli_query($conn, $delete_sql)) {
            api_success_response(["message" => "Trip deleted successfully!"]);
        } else {
            api_error_response("Failed to delete trip.");
        }
    } else {
        api_error_response("Trip not found, already deleted, or creator is inactive.");
    }
    exit;
}

$active_trips = [];
$active_trip_result = mysqli_query($conn, "SELECT t.id, t.name, u.name AS createdBy, t.created_at, t.start_date, t.end_date FROM trip t INNER JOIN user u ON t.created_by = u.id WHERE t.is_active = 1 AND u.is_active = 1");
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

api_success_response(["data" => $active_trips]);
?> 