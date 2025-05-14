<?php

require_once "../db_conn.php";


$sql = "CREATE TABLE IF NOT EXISTS interest (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
)";


if (mysqli_query($conn, $sql)) {
    echo "Table 'interest' created successfully.<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}


$interests = [
    "Adventure", "Beach", "City", "Culture", "Food", "Hiking",
    "History", "Island", "Nature", "Nightlife", "Relaxation",
    "Safari", "Sightseeing", "Wildlife"
];

foreach ($interests as $interest) {
    $sql = "INSERT INTO interest (name) VALUES ('$interest')";

    if (mysqli_query($conn, $sql)) {
        echo "Interest '$interest' inserted successfully.<br>";
    } else {
        echo "Error inserting '$interest': " . mysqli_error($conn) . "<br>";
    }
}

mysqli_close($conn);
?>
