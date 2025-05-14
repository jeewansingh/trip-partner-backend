<?php

require_once "../db_conn.php";
include("../function/response.php"); 

function getTrips($destination_id = null) {
    global $conn;

    // Base SQL query
    $sql = "
        SELECT trip.*, user.name AS createdBy, user.image AS user_image, destination.name AS location 
        FROM trip
        INNER JOIN user ON trip.created_by = user.id
        INNER JOIN destination ON trip.location = destination.id
        WHERE trip.is_active = 1
    ";

    // If a destination_id is provided, add it to the query
    if (!empty($destination_id)) {
        $destination_id = mysqli_real_escape_string($conn, $destination_id);
        $sql .= " AND trip.location = '$destination_id'";
    }

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $trips = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['user_image'] = $row['user_image']
                ? 'http://localhost/trippartner/uploads/' . $row['user_image']
                : 'http://localhost/trippartner/uploads/default_dest.jpg';

            $row['interests'] = ['beach', 'adventure', 'culture'];

            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                $start_date = new DateTime($row['start_date']);
                $end_date = new DateTime($row['end_date']);
                $interval = $start_date->diff($end_date);
                $row['duration'] = $interval->days . ' days';
            } else {
                $row['duration'] = 'N/A';
            }

            $row['date'] = $row['start_date'];
            $trips[] = $row;
        }
        return $trips;
    } else {
        return [];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination_id = $_POST['destination_id'] ?? null;
    $trips = getTrips($destination_id);

    if ($trips) {
        echo json_encode($trips);
    } else {
        echo json_encode(["error" => "No trips found."]);
    }

    mysqli_close($conn);
}
?>
