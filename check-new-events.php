<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$last_update = isset($_GET['last_update']) ? intval($_GET['last_update']) : 0;
$last_update_date = date('Y-m-d H:i:s', $last_update / 1000); // Convert milliseconds to seconds

$query = "SELECT event_id, title, location, event_date, start_time 
          FROM events 
          WHERE created_at > ? AND latitude IS NOT NULL AND longitude IS NOT NULL"; 
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]); 
    exit;
}

$stmt->bind_param("s", $last_update_date);
$stmt->execute();
$result = $stmt->get_result();

$new_events = [];
while ($row = $result->fetch_assoc()) {
    $new_events[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'new_events' => $new_events]);
?>