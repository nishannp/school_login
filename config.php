<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_login"; 

//we have to change these in production...

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}
?>