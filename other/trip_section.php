<?php

require_once "../db_conn.php";
include("../function/response.php");

$token = $_POST['token'];

// Get user_id from token
$user_id = getUserIdFromToken($token); // You need to implement this
if (!$user_id) {
    api_error_response("Invalid token or user not found.");
    exit;
}

function getTrips($destination_id = null, $user_id)
{
    global $conn;

    // Escape destination ID if provided
    $destination_filter = "";
    if (!empty($destination_id)) {
        $destination_id = mysqli_real_escape_string($conn, $destination_id);
        $destination_filter = " AND trip.location = '$destination_id'";
    }

    // Query to get trips with join_request count
    $sql = "
        SELECT 
            trip.*, 
            user.name AS createdBy, 
            user.image AS user_image, 
            destination.name AS location,
            COUNT(join_request.id) AS join_count
        FROM trip
        INNER JOIN user ON trip.created_by = user.id
        INNER JOIN destination ON trip.location = destination.id
        LEFT JOIN join_request ON trip.id = join_request.trip_id
        WHERE trip.is_active = 1 $destination_filter
        GROUP BY trip.id
        ORDER BY join_count DESC
        LIMIT 3
    ";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $trips = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $trip_id = $row['id'];

            // Get interests for the trip
            $interest_query = "
                SELECT interest.name 
                FROM trip_interest
                INNER JOIN interest ON trip_interest.interest_id = interest.id
                WHERE trip_interest.trip_id = $trip_id
            ";
            $interest_result = mysqli_query($conn, $interest_query);
            $interests = [];
            if ($interest_result && mysqli_num_rows($interest_result) > 0) {
                while ($irow = mysqli_fetch_assoc($interest_result)) {
                    $interests[] = $irow['name'];
                }
            }
            $row['interests'] = $interests;

            // Image path
            $row['user_image'] = $row['user_image']
                ? 'http://localhost/trippartner/uploads/' . $row['user_image']
                : 'http://localhost/trippartner/uploads/default_dest.jpg';

            // Duration
            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                $start_date = new DateTime($row['start_date']);
                $end_date = new DateTime($row['end_date']);
                $interval = $start_date->diff($end_date);
                $row['duration'] = $interval->days . ' days';
            } else {
                $row['duration'] = 'N/A';
            }

            $row['date'] = $row['start_date'];

            // Created by same user?
            $row['same_creator'] = ($user_id == $row['created_by']) ? 1 : 0;

            // User already sent join request?
            $join_check_query = "SELECT id FROM join_request WHERE sender_id = $user_id AND trip_id = $trip_id";
            $join_result = mysqli_query($conn, $join_check_query);
            $row['join_request'] = ($join_result && mysqli_num_rows($join_result) > 0) ? 1 : 0;

            $trips[] = $row;
        }
        return $trips;
    } else {
        return [];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination_id = $_POST['destination_id'] ?? null;
    $trips = getTrips($destination_id, $user_id);

    echo json_encode($trips ?: ["error" => "No trips found."]);
    mysqli_close($conn);
}
?>
