<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$zoom = isset($_POST['zoom']) ? intval($_POST['zoom']) : null;

if ($lat === null || $lng === null || $zoom === null) { 
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$location = $lat . ',' . $lng;

$check_query = "SELECT * FROM user_preferences WHERE user_id = ?";
$check_stmt = $conn->prepare($check_query);

if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]); //Checking Prepare error
    exit;
}

$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $update_query = "UPDATE user_preferences SET default_map_location = ?, default_map_zoom = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);

    if (!$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);//Checking Prepare error
         exit;
    }
    $update_stmt->bind_param("sii", $location, $zoom, $user_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    $insert_query = "INSERT INTO user_preferences (user_id, default_map_location, default_map_zoom) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    if (!$insert_stmt) {
         echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);//Checking Prepare error
         exit;
    }
    $insert_stmt->bind_param("isi", $user_id, $location, $zoom);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

$check_stmt->close();

echo json_encode(['success' => $success, 'message' => $success ? 'Preferences saved' : 'Error saving preferences']);

?>