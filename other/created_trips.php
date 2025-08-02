<?php

require_once "../db_conn.php";
include("../function/response.php");

$token = $_POST['token'] ?? null;
if (!$token) {
    api_error_response("Token is required.");
    exit;
}

$user_id = getUserIdFromToken($token);
if (!$user_id) {
    api_error_response("Invalid token or user not found.");
    exit;
}

function getCreatedTrips($user_id) {
    global $conn;
    $sql = "
        SELECT trip.*, user.name AS createdBy, user.image AS user_image, destination.name AS location 
        FROM trip
        INNER JOIN user ON trip.created_by = user.id
        INNER JOIN destination ON trip.location = destination.id
        WHERE trip.is_active = 1 AND trip.created_by = $user_id
        ORDER BY trip.id DESC
    ";
    $result = mysqli_query($conn, $sql);
    $trips = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $trip_id = $row['id'];
            // Get trip interests
            $interest_query = "
                SELECT interest.id, interest.name 
                FROM trip_interest
                INNER JOIN interest ON trip_interest.interest_id = interest.id
                WHERE trip_interest.trip_id = $trip_id
            ";
            $interest_result = mysqli_query($conn, $interest_query);
            $trip_interests = [];
            if ($interest_result && mysqli_num_rows($interest_result) > 0) {
                while ($irow = mysqli_fetch_assoc($interest_result)) {
                    $trip_interests[] = $irow['name'];
                }
            }
            $row['interests'] = $trip_interests;
            // Handle image, duration
            $row['user_image'] = $row['user_image']
                ? 'http://localhost/trippartner/uploads/' . $row['user_image']
                : 'http://localhost/trippartner/uploads/default_dest.jpg';
            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                $start_date = new DateTime($row['start_date']);
                $end_date = new DateTime($row['end_date']);
                $interval = $start_date->diff($end_date);
                $row['duration'] = $interval->days . ' days';
            } else {
                $row['duration'] = 'N/A';
            }
            $row['date'] = $row['start_date'];
            $row['same_creator'] = 1; // Always true for created trips
            $trips[] = $row;
        }
    }
    return $trips;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'get';
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            api_error_response("Invalid trip ID.");
        }
        // Only allow delete if created_by matches user
        $check_sql = "SELECT * FROM trip WHERE id = $id AND created_by = $user_id AND is_active = 1";
        $check_result = mysqli_query($conn, $check_sql);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $delete_sql = "UPDATE trip SET is_active = 0 WHERE id = $id";
            if (mysqli_query($conn, $delete_sql)) {
                api_success_response("Trip deleted successfully.");
            } else {
                api_error_response("Failed to delete trip.");
            }
        } else {
            api_error_response("Trip not found or not authorized.");
        }
        mysqli_close($conn);
        exit;
    }
    // Default: get created trips
    $trips = getCreatedTrips($user_id);
    if ($trips) {
        echo json_encode($trips);
    } else {
        echo json_encode(["error" => "No trips found."]);
    }
    mysqli_close($conn);
}
?> 