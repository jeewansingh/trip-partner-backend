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

$new_destination_requests = [];
$inactive_dest_result = mysqli_query($conn, "SELECT id, name, location, continent, image, description FROM destination WHERE is_active = 0");
if ($inactive_dest_result && mysqli_num_rows($inactive_dest_result) > 0) {
    while ($row = mysqli_fetch_assoc($inactive_dest_result)) {
        $new_destination_requests[] = $row;
    }
}

api_success_response(["data" => $new_destination_requests]);
?> 