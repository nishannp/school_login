<?php
require_once '../config.php';

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection error']);
    exit();
}


session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Insufficient permissions"]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
    $admin_id = $_POST['admin_id'];

    if (!is_numeric($admin_id)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid admin ID']);
        exit();
    }

    $query = "SELECT admin_id, username, email, full_name, phone_number, role FROM admins WHERE admin_id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($result)) {
            // Return as json
            header('Content-Type: application/json');
            echo json_encode($admin);
        } else {
          
            header('Content-Type: application/json'); // Set content type
            echo json_encode(['error' => 'Admin not found']);
        }
        mysqli_stmt_close($stmt);
    } else {
        
        header('Content-Type: application/json'); // Set content type
        echo json_encode(['error' => 'Error fetching admin data.']);
    }
} else {
   
     header('Content-Type: application/json'); // Set content type
    echo json_encode(['error' => 'Invalid request']);
}