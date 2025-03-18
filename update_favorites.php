<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}


if (!isset($_POST['event_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];
$action = $_POST['action'];


$event_check = "SELECT event_id FROM events WHERE event_id = ?";
$check_stmt = $conn->prepare($event_check);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit;
}

if ($action === 'add') {
   
    $insert_query = "INSERT INTO user_favorites (user_id, event_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $user_id, $event_id);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} elseif ($action === 'remove') {

    $delete_query = "DELETE FROM user_favorites WHERE user_id = ? AND event_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $user_id, $event_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>