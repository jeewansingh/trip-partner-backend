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

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        api_error_response("Invalid user ID.");
    }
    $check_sql = "SELECT id FROM user WHERE id = $id AND is_active = 1";
    $check_result = mysqli_query($conn, $check_sql);
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $delete_sql = "UPDATE user SET is_active = 0 WHERE id = $id";
        if (mysqli_query($conn, $delete_sql)) {
            api_success_response(["message" => "User deleted successfully!"]);
        } else {
            api_error_response("Failed to delete user.");
        }
    } else {
        api_error_response("User not found or already deleted.");
    }
    exit;
}

$users = [];
$user_result = mysqli_query($conn, "SELECT id, name, email, gender, location AS address, image FROM user WHERE is_active = 1");
if ($user_result && mysqli_num_rows($user_result) > 0) {
    while ($row = mysqli_fetch_assoc($user_result)) {
        $users[] = $row;
    }
}

api_success_response(["data" => $users]);
?> 