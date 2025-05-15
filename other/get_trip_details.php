<?php

require_once "../db_conn.php";
include("../function/response.php"); 

$token = $_POST['token'];
$trip_id = $_POST['trip_id'] ?? null;

// Get user_id from token
$user_id = getUserIdFromToken($token); // You need to implement this
if (!$user_id) {
    api_error_response("Invalid token or user not found.");
    exit;
}

function getTripById($trip_id, $user_id) {
    global $conn;

    if (empty($trip_id)) {
        return null;
    }

    $trip_id = mysqli_real_escape_string($conn, $trip_id);

    $sql = "
        SELECT 
    trip.*, 
    d1.name AS trip_location,
    user.id AS user_id,
    user.name AS user_name,
    user.gender,
    user.dob,
    user.email,
    user.image AS user_image,
    user.about,
    user.budget_p,
    user.location AS user_location

FROM trip
INNER JOIN user ON trip.created_by = user.id
INNER JOIN destination AS d1 ON trip.location = d1.id
WHERE trip.id = $trip_id AND trip.is_active = 1

    ";

    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Get Trip interests
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

           // Get User interests
           $created_by = $row['created_by'];
        $user_interest_query = "
            SELECT interest.name 
    FROM user_interest 
    INNER JOIN interest ON user_interest.interest_id = interest.id 
    WHERE user_interest.user_id = $created_by
        ";
        $user_interest_result = mysqli_query($conn, $user_interest_query);
        $user_interests = [];
        if ($user_interest_result && mysqli_num_rows($user_interest_result) > 0) {
            while ($irow = mysqli_fetch_assoc($user_interest_result)) {
                $user_interests[] = $irow['name'];
            }
        }

        // Duration
        if (!empty($row['start_date']) && !empty($row['end_date'])) {
            $start_date = new DateTime($row['start_date']);
            $end_date = new DateTime($row['end_date']);
            $interval = $start_date->diff($end_date);
            $duration = $interval->days . ' days';
        } else {
            $duration = 'N/A';
        }


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

        // Build trip object
        $trip = [
            "id" => $row['id'],
            "name" => $row['name'],
            "description" => $row['description'],
            "budget" => $row['budget'],
            "location" => $row['trip_location'],
            "start_date" => $row['start_date'],
            "end_date" => $row['end_date'],
            "created_at" => $row['created_at'],
            "p_gender" => $row['p_gender'],
            "created_by" => $row['created_by'],
            "interests" => $interests,
            "duration" => $duration,
            "date" => $row['start_date'],
            "same_creator" => $row['same_creator'],
            "join_request" => $row['join_request']
        ];

        // User image
        $user_image = $row['user_image']
            ? 'http://localhost/trippartner/uploads/' . basename($row['user_image'])
            : 'http://localhost/trippartner/uploads/default_dest.jpg';

        // Build creator object
        $creator = [
            "id" => $row['user_id'],
            "name" => $row['user_name'],
            "gender" => $row['gender'],
            "dob" => $row['dob'],
            "image" => $user_image,
            "about" => $row['about'],
            "interest" => $user_interests,
            "location" => $row['user_location'],
            "budget" => $row['budget_p']
        ];

        return ["trip" => $trip, "creator" => $creator];
    } else {
        return null;
    }
}


// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $trip_data = getTripById($trip_id, $user_id);

    if ($trip_data) {
        echo json_encode($trip_data);
    } else {
        echo json_encode(["error" => "Trip not found."]);
    }

    mysqli_close($conn);
}
?>
