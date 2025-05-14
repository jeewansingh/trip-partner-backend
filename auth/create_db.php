<?php
$servername = "localhost";
$username = "root";  // Default XAMPP username
$password = "";  // Default XAMPP password (empty)
$dbname = "trippartner";  // Your desired database name

// Create connection
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select the database
mysqli_select_db($conn, $dbname);

// User table creation
$sql = "CREATE TABLE IF NOT EXISTS user (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    dob DATE NOT NULL,
    about TEXT DEFAULT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(255) DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1
)";

// Execute query
if (mysqli_query($conn, $sql)) {
    echo "Table 'user' created successfully.<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Close the connection
mysqli_close($conn);
?>

<!-- CREATE TABLE IF NOT EXISTS user_interest (
    user_id INT NOT NULL,
    interest_id INT NOT NULL,
    PRIMARY KEY (user_id, interest_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interest(id) ON DELETE CASCADE ON UPDATE CASCADE
); -->

<!-- CREATE TABLE IF NOT EXISTS trip_interest (
    trip_id INT NOT NULL,
    interest_id INT NOT NULL,
    PRIMARY KEY (trip_id, interest_id),
    FOREIGN KEY (trip_id) REFERENCES trip(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interest(id) ON DELETE CASCADE ON UPDATE CASCADE
); -->

<!-- CREATE TABLE IF NOT EXISTS join_request (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    sender_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trip(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES user(id) ON DELETE CASCADE ON UPDATE CASCADE
); -->
