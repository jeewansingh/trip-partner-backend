<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
require_once "../db_conn.php";
include("../function/response.php");

// Query to get top 4 destinations based on number of trips
$sql = "
    SELECT 
        d.id, 
        d.name, 
        d.description, 
        d.image,
        COUNT(t.id) AS trip_count
    FROM destination d
    LEFT JOIN trip t ON d.id = t.location
    WHERE d.is_active = 1
    GROUP BY d.id
    ORDER BY trip_count DESC
    LIMIT 4
";

$result = mysqli_query($conn, $sql);
$destinations = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $destinations[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'image' => $row['image'] ? 'http://localhost/trippartner/uploads/' . $row['image'] : 'http://localhost/trippartner/uploads/default_dest.jpg',
            'trips' => $row['trip_count']
        ];
    }
}

// Return the destinations as JSON
echo json_encode(['destinations' => $destinations]);

// Close the database connection
mysqli_close($conn);
?>
