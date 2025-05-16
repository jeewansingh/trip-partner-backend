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

    // Base SQL query
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

$sql .= " ORDER BY trip.id";  // Move this outside the main SQL string

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $trips = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $trip_id = $row['id'];

            // Get dynamic interests from trip_interest table
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

            // Handle user image
            $row['user_image'] = $row['user_image']
                ? 'http://localhost/trippartner/uploads/' . $row['user_image']
                : 'http://localhost/trippartner/uploads/default_dest.jpg';

            // Trip duration
            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                $start_date = new DateTime($row['start_date']);
                $end_date = new DateTime($row['end_date']);
                $interval = $start_date->diff($end_date);
                $row['duration'] = $interval->days . ' days';
            } else {
                $row['duration'] = 'N/A';
            }

            $row['date'] = $row['start_date'];
            

            if ($user_id == $row['created_by']){
                    $row['same_creator'] = 1;
            } else {
                $row['same_creator'] = 0;
            }

            // Check if already have join request
            $join_query = "SELECT * FROM join_request WHERE sender_id = $user_id AND trip_id = $trip_id";
            $join_result = mysqli_query($conn, $join_query);
           
            if ($join_result && mysqli_num_rows($join_result) > 0) {
               $row['join_request'] = 1;
            } else {
                $row['join_request'] = 0;
            }

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

    if ($trips) {
        echo json_encode($trips);
    } else {
        echo json_encode(["error" => "No trips found."]);
    }

    mysqli_close($conn);
}
?>
