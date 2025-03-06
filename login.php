<?php
session_start();

require_once ('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST["username"]);
  $password = trim($_POST["password_hash"]);

  if (empty($username) || empty($password)) {
    die(json_encode(['success' => false, 'message' => "Please enter both username and password."]));
  }

  $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row["password_hash"])) {
      $_SESSION["logged_in"] = true;
      $_SESSION["user_id"] = $row["id"];
      echo json_encode(['success' => true]);
    } else {
      echo json_encode(['success' => false, 'message' => "Invalid username or password."]);
    }
  } else {
    echo json_encode(['success' => false, 'message' => "Invalid username or password."]);
  }

  $stmt->close();
}

$conn->close();
?>