<?php
require_once "../db_conn.php";
include("../function/response.php");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';

    $query = "SELECT id FROM user WHERE token = '$token' AND is_active = 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        api_success_response("Token valid");
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token"]);
    }
    mysqli_close($conn);
}
