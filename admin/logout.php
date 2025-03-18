<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : '';

$confirmed = isset($_GET['confirm']) && $_GET['confirm'] == 'yes';

if ($confirmed) {

    require_once '../config.php';
    if (isset($_SESSION['admin_id'])) {
        $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
        $updateStmt->bind_param("i", $_SESSION['admin_id']);
        $updateStmt->execute();
        $updateStmt->close();
    }

    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_role']);

    header("Location: ../");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect Events | Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4e2a84;
            --secondary-color: #7c4dff;
            --accent-color: #ff5722;
            --light-color: #f5f5f5;
            --dark-color: #2c2c2c;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .logout-container {
            max-width: 500px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(78, 42, 132, 0.3);
        }

        .logout-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .logout-message {
            margin-bottom: 2rem;
            font-size: 1.1rem;
            color: #555;
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .btn-logout {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(124, 77, 255, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .btn-logout:hover, .btn-logout:focus {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(124, 77, 255, 0.4);
            color: white;
        }

        .btn-cancel {
            background: transparent;
            color: var(--dark-color);
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .btn-cancel:hover, .btn-cancel:focus {
            background-color: #f5f5f5;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .username {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .bg-bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-bubbles li {
            position: absolute;
            list-style: none;
            display: block;
            width: 40px;
            height: 40px;
            background-color: rgba(124, 77, 255, 0.05);
            bottom: -160px;
            border-radius: 50%;
            animation: square 25s infinite;
            transition-timing-function: linear;
        }

        .bg-bubbles li:nth-child(1) {
            left: 10%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .bg-bubbles li:nth-child(2) {
            left: 20%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 17s;
        }

        .bg-bubbles li:nth-child(3) {
            left: 25%;
            animation-delay: 4s;
        }

        .bg-bubbles li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-duration: 22s;
        }

        .bg-bubbles li:nth-child(5) {
            left: 70%;
            width: 120px;
            height: 120px;
        }

        .bg-bubbles li:nth-child(6) {
            left: 80%;
            width: 30px;
            height: 30px;
            animation-delay: 3s;
            animation-duration: 18s;
        }

        @keyframes square {
            0% {
                transform: translateY(0);
                opacity: 0.4;
            }
            100% {
                transform: translateY(-900px) rotate(600deg);
                opacity: 0;
            }
        }

        .logout-footer {
            margin-top: 2rem;
            color: #777;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="bg-bubbles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </div>

    <div class="logout-container animate__animated animate__fadeIn">
        <div class="user-avatar animate__animated animate__zoomIn">
            <i class="fas fa-user"></i>
        </div>

        <h2 class="logout-title animate__animated animate__fadeInUp">Confirm Logout</h2>

        <p class="logout-message animate__animated animate__fadeInUp animate__delay-1s">
            Are you sure you want to log out, <span class="username"><?php echo htmlspecialchars($username); ?></span>?
        </p>

        <div class="btn-group animate__animated animate__fadeInUp animate__delay-2s">
            <a href="logout.php?confirm=yes" class="btn btn-logout">
                <i class="fas fa-sign-out-alt me-2"></i>Yes, Logout
            </a>
            <a href="dashboard.php" class="btn btn-cancel">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>

        <div class="logout-footer animate__animated animate__fadeInUp animate__delay-3s">
            <p>&copy; <?php echo date("Y"); ?> Campus Connect Events. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.querySelector('.btn-logout');
            logoutBtn.addEventListener('mouseenter', function() {
                this.classList.add('animate__pulse');
            });
            logoutBtn.addEventListener('mouseleave', function() {
                this.classList.remove('animate__pulse');
            });
        });
    </script>
</body>
</html>