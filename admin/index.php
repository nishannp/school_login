<?php
session_start();

require_once '../config.php';
$loginError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$usernameOrEmail = $_POST['usernameOrEmail'];
$password = $_POST['password'];
$stmt = $conn->prepare("SELECT admin_id, username, password_hash, role FROM admins WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
$row = $result->fetch_assoc();
if (password_verify($password, $row['password_hash'])) {
$_SESSION['admin_id'] = $row['admin_id'];
$_SESSION['admin_username'] = $row['username'];
$_SESSION['admin_role'] = $row['role'];
$updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
$updateStmt->bind_param("i", $row['admin_id']);
$updateStmt->execute();
$updateStmt->close();
header("Location: dashboard.php");
exit();
 } else {
$loginError = "Invalid username/email or password.";
 }
 } else {
$loginError = "Invalid username/email or password.";
 }
$stmt->close();
}
if (isset($_SESSION['admin_id'])) {
header("Location: dashboard.php");
exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect Events | Admin Login</title>
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

        .login-wrapper {
            width: 100%;
            max-width: 900px;
            display: flex;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .login-banner {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            display: none;
        }

        .login-banner h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .login-banner p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .banner-shape {
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        .banner-shape:before {
            content: '';
            position: absolute;
            top: -70px;
            left: -70px;
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .login-form {
            flex: 1;
            background-color: white;
            padding: 3rem 2rem;
        }

        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
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
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(78, 42, 132, 0.3);
        }

        .login-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            border: none;
            border-bottom: 2px solid #e0e0e0;
            border-radius: 0;
            padding: 0.75rem 0.75rem 0.75rem 2.5rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--secondary-color);
            background-color: white;
        }

        .form-icon {
            position: absolute;
            left: 0;
            top: 12px;
            color: #aaa;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .form-control:focus + .form-icon {
            color: var(--secondary-color);
        }

        .btn-login {
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

        .btn-login:hover, .btn-login:focus {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(124, 77, 255, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #777;
            font-size: 0.9rem;
        }

        .error-message {
            background-color: #ffe6e6;
            color: #d9534f;
            padding: 0.7rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .form-floating {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 12px;
            right: 10px;
            cursor: pointer;
            color: #aaa;
            z-index: 10;
        }

        .fade-in {
            animation: fadeIn 0.8s ease forwards;
        }

        .slide-up {
            animation: slideUp 0.8s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (min-width: 768px) {
            .login-banner {
                display: flex;
            }

            .login-form {
                padding: 3rem;
            }
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
            background-color: rgba(255, 255, 255, 0.1);
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

        .bg-bubbles li:nth-child(7) {
            left: 32%;
            width: 160px;
            height: 160px;
            animation-delay: 7s;
        }

        .bg-bubbles li:nth-child(8) {
            left: 55%;
            width: 20px;
            height: 20px;
            animation-delay: 15s;
            animation-duration: 40s;
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
        <li></li>
        <li></li>
    </div>

    <div class="login-wrapper animate__animated animate__fadeIn">
        <div class="login-banner">
            <div class="banner-shape"></div>
            <h1 class="animate__animated animate__fadeInUp">Campus Connect Events</h1>
            <p class="animate__animated animate__fadeInUp animate__delay-1s">Manage all your campus events from one central dashboard.</p>
            <div class="banner-icons animate__animated animate__fadeInUp animate__delay-2s">
                <i class="fas fa-calendar-alt fa-3x me-4"></i>
                <i class="fas fa-users fa-3x me-4"></i>
                <i class="fas fa-chart-line fa-3x"></i>
            </div>
        </div>

        <div class="login-form">
            <div class="login-container">
                <div class="login-logo fade-in">
                    <div class="logo-circle animate__animated animate__zoomIn">CC</div>
                    <h4 class="mb-0 animate__animated animate__fadeIn animate__delay-1s">Campus Connect</h4>
                </div>

                <h2 class="login-title slide-up">Admin Login</h2>

                <?php if (!empty($loginError)): ?>
                <div class="error-message animate__animated animate__shakeX">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $loginError; ?>
                </div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="slide-up">
                    <div class="form-group">
                        <input type="text" class="form-control" id="usernameOrEmail" name="usernameOrEmail" placeholder="Username or Email" required autocomplete="off">
                        <i class="fas fa-user form-icon"></i>
                    </div>

                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <i class="fas fa-lock form-icon"></i>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-end mb-4">
                        <a href="#" class="text-decoration-none" style="color: var(--secondary-color); font-size: 0.9rem;">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-login w-100 animate__animated animate__pulse animate__infinite animate__slower">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>

                <div class="login-footer slide-up">
                    <p>&copy; <?php echo date("Y"); ?> Campus Connect Events. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            const staggeredElements = document.querySelectorAll('.slide-up');
            staggeredElements.forEach((element, index) => {
                element.style.animationDelay = `${0.2 * (index + 1)}s`;
            });

            setTimeout(() => {
                document.getElementById('usernameOrEmail').focus();
            }, 1500);
        });

        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>