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

function getAcceptedInvitations($user_id) {
    global $conn;
    $sql = "
        SELECT
            jr.id,
            t.id AS trip_id,
            t.name AS trip_name,
            t.start_date AS date,
            t.end_date,
            t.created_by,
            t.is_active,
            u1.id AS sender_id,
            u1.name AS sender_name,
            u1.email AS sender_email,
            u2.id AS creator_id,
            u2.name AS creator_name,
            u2.email AS creator_email
        FROM join_request jr
        INNER JOIN trip t ON jr.trip_id = t.id
        INNER JOIN user u1 ON jr.sender_id = u1.id
        INNER JOIN user u2 ON t.created_by = u2.id
        WHERE jr.status = 'accepted' AND t.is_active = 1 AND u1.is_active = 1 AND u2.is_active = 1
            AND (t.created_by = $user_id OR jr.sender_id = $user_id)
        ORDER BY jr.created_at DESC
    ";
    $result = mysqli_query($conn, $sql);
    $invitations = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Calculate duration
            if (!empty($row['date']) && !empty($row['end_date'])) {
                $start_date = new DateTime($row['date']);
                $end_date = new DateTime($row['end_date']);
                $interval = $start_date->diff($end_date);
                $row['duration'] = $interval->days . ' days';
            } else {
                $row['duration'] = 'N/A';
            }
            // Determine partner
            if ($user_id == $row['creator_id']) {
                // User is trip creator, partner is sender
                $partner_name = $row['sender_name'];
                $partner_email = $row['sender_email'];
            } else {
                // User is sender, partner is creator
                $partner_name = $row['creator_name'];
                $partner_email = $row['creator_email'];
            }
            $invitations[] = [
                'id' => $row['id'],
                'trip_id' => $row['trip_id'],
                'trip_name' => $row['trip_name'],
                'partner_name' => $partner_name,
                'partner_email' => $partner_email,
                'date' => $row['date'],
                'duration' => $row['duration']
            ];
        }
    }
    return $invitations;
}

function sendAcceptanceEmail($to, $tripName, $partnerName) {
    $subject = "Your invitation for '$tripName' has been accepted!";
    $message = "Hello $partnerName,\n\nYour invitation for the trip '$tripName' has been accepted!\n\nSee you on the trip!";
    $headers = "From: noreply@trippartner.com";
    // Uncomment the next line to actually send email in production
    // mail($to, $subject, $message, $headers);
    // For now, just return true as a stub
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'get';
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            api_error_response("Invalid invitation ID.");
        }
        // Allow delete if the user is either the trip creator or the sender
        $check_sql = "SELECT jr.id FROM join_request jr INNER JOIN trip t ON jr.trip_id = t.id WHERE jr.id = $id AND jr.status = 'accepted' AND t.is_active = 1 AND (t.created_by = $user_id OR jr.sender_id = $user_id)";
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
        $check_sql = "SELECT jr.id, t.name AS trip_name, u.email, u.name AS partner_name FROM join_request jr INNER JOIN trip t ON jr.trip_id = t.id INNER JOIN user u ON jr.sender_id = u.id WHERE jr.id = $id AND t.created_by = $user_id AND t.is_active = 1";
        $check_result = mysqli_query($conn, $check_sql);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $row = mysqli_fetch_assoc($check_result);
            $update_sql = "UPDATE join_request SET status = 'accepted' WHERE id = $id";
            if (mysqli_query($conn, $update_sql)) {
                // Send email
                sendAcceptanceEmail($row['email'], $row['trip_name'], $row['partner_name']);
                api_success_response("Invitation accepted and email sent.");
            } else {
                api_error_response("Failed to accept invitation.");
            }
        } else {
            api_error_response("Invitation not found or not authorized.");
        }
        mysqli_close($conn);
        exit;
    }
    // Default: get accepted invitations
    $invitations = getAcceptedInvitations($user_id);
    if ($invitations) {
        echo json_encode($invitations);
    } else {
        echo json_encode(["error" => "No invitations found."]);
    }
    mysqli_close($conn);
}
?> 