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

function getTrips($destination_id = null, $user_id) {
    global $conn;

    // 1. Fetch user interests
    $user_interest_query = "SELECT interest_id FROM user_interest WHERE user_id = $user_id";
    $user_interest_result = mysqli_query($conn, $user_interest_query);
    $user_interests = [];
    if ($user_interest_result && mysqli_num_rows($user_interest_result) > 0) {
        while ($row = mysqli_fetch_assoc($user_interest_result)) {
            $user_interests[] = $row['interest_id'];
        }
    }

    // 2. Base SQL query
    $sql = "
        SELECT trip.*, user.name AS createdBy, user.image AS user_image, destination.name AS location 
        FROM trip
        INNER JOIN user ON trip.created_by = user.id
        INNER JOIN destination ON trip.location = destination.id
        WHERE trip.is_active = 1
    ";

    if (!empty($destination_id)) {
        $destination_id = mysqli_real_escape_string($conn, $destination_id);
        $sql .= " AND trip.location = '$destination_id'";
    }

    $sql .= " ORDER BY trip.id";

    $result = mysqli_query($conn, $sql);

    $trips = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $trip_id = $row['id'];

            // 3. Fetch trip interests
            $interest_query = "
                SELECT interest.id, interest.name 
                FROM trip_interest
                INNER JOIN interest ON trip_interest.interest_id = interest.id
                WHERE trip_interest.trip_id = $trip_id
            ";
            $interest_result = mysqli_query($conn, $interest_query);
            $trip_interests = [];
            $trip_interest_ids = [];

            if ($interest_result && mysqli_num_rows($interest_result) > 0) {
                while ($irow = mysqli_fetch_assoc($interest_result)) {
                    $trip_interests[] = $irow['name'];
                    $trip_interest_ids[] = $irow['id'];
                }
            }
            $row['interests'] = $trip_interests;

            // 4. Calculate matching score
            $matched_interests = array_intersect($user_interests, $trip_interest_ids);
            $row['match_score'] = count($matched_interests);

            // 5. Handle image, duration, creator
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

            $row['same_creator'] = ($user_id == $row['created_by']) ? 1 : 0;

            // 6. Join request check
            $join_query = "SELECT * FROM join_request WHERE sender_id = $user_id AND trip_id = $trip_id";
            $join_result = mysqli_query($conn, $join_query);
            $row['join_request'] = ($join_result && mysqli_num_rows($join_result) > 0) ? 1 : 0;

            $trips[] = $row;
        }
    }

    // 7. Sort trips by match_score DESC
    usort($trips, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });

    return $trips;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination_id = $_POST['destination_id'] ?? null;
    $trips = getTrips($destination_id, $user_id);

    if ($trips) {
        echo json_encode($trips);
    } else {
        echo json_encode(["error" => "No trips found."]);
    }

    mysqli_close($conn);
}
?>

