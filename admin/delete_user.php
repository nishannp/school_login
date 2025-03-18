<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit();
}

require_once '../config.php'; 

$query = "DELETE FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {

    $userId = intval($_POST['user_id']); 
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
    }

    $stmt->close();
} else {

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

$conn->close();

?>