<?php

session_start();

// Checking if the user is logged in; if not, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php"); 
    exit();
}


require_once ('header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
    <p>Your role is: <?php echo htmlspecialchars($_SESSION['admin_role']); ?></p> 
    <p>This is your dashboard.</p>

    <h2>Session Data (var_dump):</h2>
    <pre><?php var_dump($_SESSION); ?></pre> 

    <a href="logout.php">Logout</a>
</body>
</html>