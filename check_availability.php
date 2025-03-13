<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $value = trim($_POST['value']);
    $type = $_POST['type'];
    
    if (empty($value) || empty($type) || !in_array($type, ['username', 'email'])) {
        echo json_encode(['available' => false, 'error' => 'Invalid input']);
        exit;
    }

    if ($type === 'username') {
        $query = "SELECT * FROM users WHERE username = ?";
    } else {
        $query = "SELECT * FROM users WHERE email = ?";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // we send back request in json format so the javascript can handle it  according to the response it will get
    echo json_encode(['available' => ($result->num_rows === 0)]);
}
?>