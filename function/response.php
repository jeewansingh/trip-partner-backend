<?php
// function api_success_response($message) {
//     if (is_string($message)) {
//         $message =  [
//             "message" => $message
//         ];
//     }
//     http_response_code(200);
//     echo json_encode($message);
//     exit();
// }

function api_success_response($message) {
    if (is_string($message)) {
        $message = [
            "success" => true,
            "message" => $message
        ];
    }

    http_response_code(200); // Ensure it's a 200 OK
    echo json_encode($message); // Send back the response
    exit();
}

function api_error_response($message, $error_code = 400) {
    if (is_string($message)) {
        $message =  [
            "message" => $message
        ];
    }
    http_response_code($error_code);
    echo json_encode($message);
    exit();
}

function api_nodata_response($data) {
    if (is_string($data)) {
        $data =  [
            "message" => $data
        ];
    }
    http_response_code(200);
    echo json_encode($data);
    exit();
}


// Function to get user ID from the token
function getUserIdFromToken($token) {
    global $conn;
    $sql = "SELECT id FROM user WHERE token = '$token' AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['id'];
    } else {
        return null;
    }
}
?>