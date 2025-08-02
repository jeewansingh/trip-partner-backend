<?php

require_once "../db_conn.php";
include("../function/response.php");

$token = $_POST['token'] ?? null;
if (!$token) {
    api_error_response("Token is required.");
    exit;
}

$user_id = getUserIdFromToken($token);
if (!$user_id) {
    api_error_response("Invalid token or user not found.");
    exit;
}

function getReceivedInvitations($user_id) {
    global $conn;
    $sql = "
        SELECT jr.id, t.name AS trip_name, t.start_date AS trip_date, u.name AS sent_by, jr.status
        FROM join_request jr
        INNER JOIN trip t ON jr.trip_id = t.id
        INNER JOIN user u ON jr.sender_id = u.id
        INNER JOIN user creator ON t.created_by = creator.id
        WHERE t.created_by = $user_id AND t.is_active = 1 AND creator.is_active = 1 AND status = 'pending'
        ORDER BY jr.created_at DESC
    ";
    $result = mysqli_query($conn, $sql);
    $invitations = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $invitations[] = $row;
        }
    }
    return $invitations;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'get';
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            api_error_response("Invalid invitation ID.");
        }
        // Only allow delete if the trip is created by this user
        $check_sql = "SELECT jr.id FROM join_request jr INNER JOIN trip t ON jr.trip_id = t.id WHERE jr.id = $id AND t.created_by = $user_id";
        $check_result = mysqli_query($conn, $check_sql);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $delete_sql = "DELETE FROM join_request WHERE id = $id";
            if (mysqli_query($conn, $delete_sql)) {
                api_success_response("Invitation deleted successfully.");
            } else {
                api_error_response("Failed to delete invitation.");
            }
        } else {
            api_error_response("Invitation not found or not authorized.");
        }
        mysqli_close($conn);
        exit;
    } else if ($action === 'accept') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            api_error_response("Invalid invitation ID.");
        }
        // Only allow accept if the trip is created by this user
        $check_sql = "SELECT jr.id FROM join_request jr INNER JOIN trip t ON jr.trip_id = t.id WHERE jr.id = $id AND t.created_by = $user_id";
        $check_result = mysqli_query($conn, $check_sql);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $update_sql = "UPDATE join_request SET status = 'accepted' WHERE id = $id";
            if (mysqli_query($conn, $update_sql)) {
                api_success_response("Invitation accepted successfully.");
            } else {
                api_error_response("Failed to accept invitation.");
            }
        } else {
            api_error_response("Invitation not found or not authorized.");
        }
        mysqli_close($conn);
        exit;
    }
    // Default: get invitations
    $invitations = getReceivedInvitations($user_id);
    if ($invitations) {
        echo json_encode($invitations);
    } else {
        echo json_encode(["error" => "No invitations found."]);
    }
    mysqli_close($conn);
}
?> 