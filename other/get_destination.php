<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
require_once "../db_conn.php";
include("../function/response.php");

// Get the list of destinations from the database
$sql = "SELECT id, name, description, image FROM destination WHERE is_active = 1";
$result = mysqli_query($conn, $sql);

$destinations = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $destinationId = $row['id'];

        // Get the number of trips for this destination
        $tripQuery = "SELECT COUNT(*) as trip_count FROM trip WHERE location = $destinationId";
        $tripResult = mysqli_query($conn, $tripQuery);
        $tripCount = 0;

        if ($tripResult) {
            $tripRow = mysqli_fetch_assoc($tripResult);
            $tripCount = $tripRow['trip_count'];
        }

        $destinations[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'image' => $row['image'] ? 'http://localhost/trippartner/uploads/' . $row['image'] : 'http://localhost/trippartner/uploads/default_dest.jpg',
            'trips' => $tripCount
        ];
    }
}

// Return the destinations as JSON
echo json_encode(['destinations' => $destinations]);

// Close the database connection
mysqli_close($conn);
?>
