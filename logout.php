<?php
// Start the session
session_start();

// Check if user is actually logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If not logged in, redirect to login page
    header("Location: index.php");
    exit;
}

// Initialize message variables
$message = "";
$messageType = "";

// Process logout
if (isset($_POST['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // If a session cookie is used, delete it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Set success message
    $message = "You have been successfully logged out.";
    $messageType = "success";
    
    // Redirect to login page after a short delay
    header("refresh:3;url=login.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | Open day</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #2e59d9;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --bg-color: #f8f9fc;
            --card-bg: #ffffff;
            --text-color: #5a5c69;
            --border-radius: 8px;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 450px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            position: relative;
        }

        .card-header {
            background-image: linear-gradient(135deg, var(--primary-color), #3a57e8);
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-header h2 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .card-header p {
            opacity: 0.8;
            font-weight: 300;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 30px;
            text-align: center;
        }

        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
        }

        .message.success {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 1rem;
            margin: 8px;
            min-width: 120px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.3);
        }

        .btn-secondary {
            background-color: #f8f9fc;
            color: var(--secondary-color);
            border: 1px solid #e3e6f0;
        }

        .btn-secondary:hover {
            background-color: #eaecf4;
            transform: translateY(-3px);
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 15px;
        }

        .user-details {
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .user-role {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .divider {
            margin: 20px 0;
            border-top: 1px solid #e3e6f0;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            color: var(--secondary-color);
            font-size: 0.85rem;
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .confirmation-text {
            margin-bottom: 25px;
            color: #2d3748;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 20px;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Account Logout</h2>
                <p>Secure Session Management</p>
            </div>
            <div class="card-body">
                <?php if(!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                    <div class="confirmation-text">
                        Redirecting you to the login page...
                    </div>
                <?php else: ?>
                    <div class="user-info">
                        <div class="avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <div class="user-role">Active Session</div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="confirmation-text">
                        Are you sure you want to end your current session?
                    </div>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <button type="submit" name="logout" class="btn btn-primary">Yes, Logout</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer">
            &copy; <?php echo date("Y"); ?> Your Website | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a>
        </div>
    </div>
</body>
</html>