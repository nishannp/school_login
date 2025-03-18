<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID is required']);
    exit();
}

require_once '../config.php'; 

$query = "SELECT * FROM users WHERE user_id = ?"; 
$stmt = $conn->prepare($query);

if ($stmt) {

    $stmt->bind_param('i', $_GET['user_id']);
    $stmt->execute();

    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    header('Content-Type: application/json');
    echo json_encode($user);

    $stmt->close();
} else {

    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
}

$conn->close();
?>