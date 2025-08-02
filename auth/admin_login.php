<?php
require_once "../db_conn.php";
include("../function/response.php");

// Accept POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    api_error_response("Invalid request method.", 405);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === 'admin' && $password === 'admin') {
    $token = hash('sha256', $username . $password);
    $response = [
        "message" => "Admin login successful.",
        "token" => $token
    ];
    api_success_response($response);
} else {
    api_error_response("Invalid username or password.");
}
?> 