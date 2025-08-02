<?php
require_once "../db_conn.php";
include("../function/response.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    api_error_response("Invalid request method.", 405);
    exit;
}

$token = $_POST['token'] ?? '';
if (!$token) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data) && isset($data['token'])) {
        $token = $data['token'];
    }
}
$expected_token = hash('sha256', 'adminadmin');
if ($token !== $expected_token) {
    api_error_response("Invalid or missing admin token.", 401);
    exit;
}

$active_destinations = [];
$active_dest_result = mysqli_query($conn, "SELECT d.id, d.name, d.location, d.continent, d.image, d.description, COUNT(CASE WHEN t.is_active = 1 AND u.is_active = 1 THEN t.id END) AS trip_count FROM destination d LEFT JOIN trip t ON d.id = t.location LEFT JOIN user u ON t.created_by = u.id WHERE d.is_active = 1 GROUP BY d.id");
if ($active_dest_result && mysqli_num_rows($active_dest_result) > 0) {
    while ($row = mysqli_fetch_assoc($active_dest_result)) {
        $active_destinations[] = $row;
    }
}

api_success_response(["data" => $active_destinations]);
?> 