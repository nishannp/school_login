<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}



if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

require_once '../config.php';  

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0; 
$username = isset($_POST['username']) ? mysqli_real_escape_string($conn, $_POST['username']) : '';
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
$firstName = isset($_POST['first_name']) ? mysqli_real_escape_string($conn, $_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? mysqli_real_escape_string($conn, $_POST['last_name']) : '';
$phoneNumber = isset($_POST['phone_number']) ? mysqli_real_escape_string($conn, $_POST['phone_number']) : null;
$academicInterest = isset($_POST['academic_interest']) ? mysqli_real_escape_string($conn, $_POST['academic_interest']) : null;
$password = isset($_POST['password']) ? $_POST['password'] : null; 

if (!$userId || !$username || !$email || !$firstName || !$lastName) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$query = "UPDATE users SET 
          username = ?,
          email = ?,
          first_name = ?,
          last_name = ?,
          phone_number = ?,
          academic_interest = ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$stmt->bind_param('ssssss', $username, $email, $firstName, $lastName, $phoneNumber, $academicInterest);

if ($password) {

    $stmt->close();

    $query .= ", password_hash = ?";  
    $query .= " WHERE user_id = ?";    

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt->bind_param('sssssss', $username, $email, $firstName, $lastName, $phoneNumber, $academicInterest, $passwordHash);

      $stmt = $conn->prepare($query);
       if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
         exit();
     }
      $stmt->bind_param('sssssssi', $username, $email, $firstName, $lastName, $phoneNumber, $academicInterest, $passwordHash, $userId);

} else {
   $query .= " WHERE user_id = ?";    

    $stmt = $conn->prepare($query);
       if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
         exit();
      }
     $stmt->bind_param('ssssssi', $username, $email, $firstName, $lastName, $phoneNumber, $academicInterest, $userId);

}

if ($stmt->execute()) {

    $affectedRows = $stmt->affected_rows;

    if ($affectedRows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
    }
} else {

    if (mysqli_errno($conn) == 1062) { 
        if (strpos(mysqli_error($conn), 'username') !== false) {
            $message = 'Username already exists';
        } elseif (strpos(mysqli_error($conn), 'email') !== false) {
            $message = 'Email already exists';
        } else {
            $message = 'Duplicate entry error';
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    }
     else {

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}

$stmt->close();
$conn->close();

?>