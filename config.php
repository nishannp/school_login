<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_login";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}
?>