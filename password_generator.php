<?php
$password = "surajnepali";  // Replace with the password you want to use
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed Password: " . $hashed_password . "\n";
?>