<?php
session_start();
$error = "";
$success = "";

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $academic_interest = $_POST['academic_interest'];
    
   
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "All required fields must be filled";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username or email already exists
        $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            
            $profile_photo_original = "";
            $profile_photo_resized = "";
            
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
                $filename = $_FILES['profile_photo']['name'];
                $filetype = $_FILES['profile_photo']['type'];
                $filesize = $_FILES['profile_photo']['size'];
                
                
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if (!array_key_exists($ext, $allowed)) {
                    $error = "Please select a valid file format (JPG, JPEG, PNG)";
                } else {
                    // 5mb maximum
                    $maxsize = 5 * 1024 * 1024;
                    if ($filesize > $maxsize) {
                        $error = "File size must be less than 5MB";
                    } else {
                       
                        if (!file_exists('uploads/profile_photos')) {
                            mkdir('uploads/profile_photos', 0777, true);
                        }
                        
                        
                        $new_filename = uniqid() . "." . $ext;
                        $original_path = "uploads/profile_photos/original_" . $new_filename;
                        $resized_path = "uploads/profile_photos/resized_" . $new_filename;
                        
                       
                        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $original_path)) {
                            // Create resized version
                            $source = imagecreatefromstring(file_get_contents($original_path));
                            $width = imagesx($source);
                            $height = imagesy($source);
                            $new_width = 150;
                            $new_height = floor($height * ($new_width / $width));
                            $resized = imagecreatetruecolor($new_width, $new_height);
                            imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                            
                            
                            if ($ext == 'jpg' || $ext == 'jpeg') {
                                imagejpeg($resized, $resized_path, 80); 
                            } else {
                                imagepng($resized, $resized_path, 8); 
                            }
                            
                          
                            imagedestroy($source);
                            imagedestroy($resized);
                            
                            $profile_photo_original = $original_path;
                            $profile_photo_resized = $resized_path;
                        } else {
                            $error = "Failed to upload file";
                        }
                    }
                }
            }
            
            if (empty($error)) {
               
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                
                $insert_query = "INSERT INTO users (username, email, password_hash, first_name, 
                                last_name, phone_number, academic_interest, profile_photo_original, 
                                profile_photo_resized, account_created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("sssssssss", $username, $email, $password_hash, $first_name, 
                                        $last_name, $phone_number, $academic_interest, 
                                        $profile_photo_original, $profile_photo_resized);
                
                if ($insert_stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                   
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    
                    $success = "Account created successfully!";
                    
                  
                    header("refresh:2;url=dashboard.php");
                } else {
                    $error = "Error: " . $insert_stmt->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - University Open Day</title>
    
   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --accent-color: #ff6b6b;
            --light-color: #f9f9f9;
            --dark-color: #333;
            --success-color: #28a745;
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
        }
        
        .circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .circles li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }
        
        .circles li:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .circles li:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }
        
        .circles li:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }
        
        .circles li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }
        
        .circles li:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }
        
        .circles li:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }
        
        .circles li:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }
        
        .circles li:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }
        
        .circles li:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }
        
        .circles li:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }
        
        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 50%;
            }
            
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        .container {
            margin: 50px 0;
        }
        
        .signup-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            backdrop-filter: blur(10px);
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .row {
            margin: 0;
        }
        
        .image-side {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100%;
            padding: 0;
        }
        
        .image-content {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 30px;
        }
        
        .form-side {
            padding: 40px;
        }
        
        .form-title {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 84, 200, 0.25);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-signup {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(142, 148, 251, 0.4);
            transition: all 0.3s ease;
        }
        
        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(142, 148, 251, 0.6);
        }
        
        .guest-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .guest-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .guest-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .social-login {
            margin-top: 30px;
            position: relative;
            text-align: center;
        }
        
        .social-login::before {
            content: "or";
            display: inline-block;
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: white;
            padding: 0 10px;
            color: #777;
            font-size: 0.8rem;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .social-icon.facebook {
            background-color: #3b5998;
            color: white;
        }
        
        .social-icon.google {
            background-color: #db4437;
            color: white;
        }
        
        .social-icon.twitter {
            background-color: #1da1f2;
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .form-check {
            margin-bottom: 20px;
        }
        
        .form-check-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid var(--primary-color);
            overflow: hidden;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-preview .placeholder {
            font-size: 4rem;
            color: #ccc;
        }
        
        .form-file {
            margin-bottom: 20px;
        }
        
        .form-file-label {
            width: 100%;
            height: calc(3.5rem + 2px);
            border: 1px dashed var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .form-file-label:hover {
            background-color: rgba(78, 84, 200, 0.05);
        }
        
        .form-file-input {
            display: none;
        }
        
        @media (max-width: 991.98px) {
            .image-side {
                min-height: 300px;
            }
            
            .container {
                margin: 20px;
            }
            
            .form-side {
                padding: 30px;
            }
        }
        
        @media (max-width: 767.98px) {
            .signup-container {
                max-width: 90%;
            }
            
            .form-side {
                padding: 20px;
            }
        }
        
        @media (max-width: 575.98px) {
            .signup-container {
                max-width: 95%;
                margin: 10px;
            }
            
            .image-side {
                min-height: 200px;
            }
            
            .form-side {
                padding: 15px;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .btn-signup {
                width: 100%;
            }
        }
        
        
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
   
    <ul class="circles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>
    
    
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>
    
    <div class="container">
    <div class="signup-container">
        <div class="row">
         
            <div class="col-lg-6 image-side">
                <div class="image-content text-center">
                    <h1 class="display-4 mb-4">Welcome!</h1>
                    <p>Join us for an incredible Open Day experience at our university.</p>
                    <p class="mt-4">Discover your future, meet our faculty, and explore our campus!</p>
                </div>
            </div>
            
            
            <div class="col-lg-6 form-side">
                <h2 class="form-title">Create an Account</h2>
                <p class="form-subtitle">Sign up to explore our university's Open Day events and create your personalized schedule.</p>
                <p class="form-note text-muted mb-3"><small>Fields marked with <span class="text-danger">*</span> are required.</small></p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" id="signupForm">
                    <div class="photo-preview" id="photoPreview">
                        <div class="placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="profile_photo" class="form-file-label">
                            <i class="fas fa-cloud-upload-alt me-2"></i> Choose Profile Picture <span class="text-muted">(Optional)</span>
                        </label>
                        <input type="file" class="form-file-input" id="profile_photo" name="profile_photo" accept="image/jpeg, image/jpg, image/png" onchange="previewImage(this)">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                <label for="first_name"><i class="fas fa-user me-2"></i> First Name <span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                <label for="last_name"><i class="fas fa-user me-2"></i> Last Name <span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <label for="username"><i class="fas fa-user-tag me-2"></i> Username <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i> Email Address <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="Phone Number">
                        <label for="phone_number"><i class="fas fa-phone me-2"></i> Phone Number <span class="text-muted">(Optional)</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" id="academic_interest" name="academic_interest">
                            <option value="" selected disabled>Select your interest</option>
                            <option value="Business">Business & Management</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Arts">Arts & Humanities</option>
                            <option value="Medicine">Medicine & Health</option>
                            <option value="Law">Law</option>
                            <option value="Science">Science</option>
                            <option value="Social Sciences">Social Sciences</option>
                            <option value="Other">Other</option>
                        </select>
                        <label for="academic_interest"><i class="fas fa-graduation-cap me-2"></i> Academic Interest <span class="text-muted">(Optional)</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i> Password <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="confirm_password"><i class="fas fa-lock me-2"></i> Confirm Password <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> <span class="text-danger">*</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-signup btn-lg w-100" id="submitBtn">
                        <i class="fas fa-user-plus me-2"></i> Sign Up
                    </button>
                    
                    <div class="social-login">
                        <div class="social-icons">
                            <a href="#" class="social-icon facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-icon google">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-icon twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="login-link">
                        Already have an account? <a href="index.php">Log In</a>
                    </div>
                    
                    <div class="guest-link">
                        <a href="guest.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-clock me-2"></i> Continue as Guest
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    
   
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. User Registration</h5>
                    <p>By registering for an account, you agree to provide accurate and complete information about yourself. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                    
                    <h5>2. Privacy Policy</h5>
                    <p>Your personal information will be handled in accordance with our Privacy Policy. We collect your information solely for the purpose of enhancing your Open Day experience.</p>
                    
                    <h5>3. App Usage</h5>
                    <p>The University Open Day app is designed to help you navigate our campus and participate in events. Any misuse of the application is prohibited.</p>
                    
                    <h5>4. User Content</h5>
                    <p>Any content you submit, including feedback and comments, may be used by the university for improving future events.</p>
                    
                    <h5>5. Termination</h5>
                    <p>We reserve the right to terminate or suspend your account at any time if you violate these terms.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
     
        function previewImage(input) {
            const preview = document.getElementById('photoPreview');
            const placeholder = preview.querySelector('.placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                   
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                  
                    let img = preview.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        preview.appendChild(img);
                    }
                    
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        
        document.getElementById('signupForm').addEventListener('submit', function() {
            document.getElementById('loading').style.display = 'flex';
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating account...';
        });
        
      
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.createElement('div');
            strengthBar.className = 'progress mt-2';
            strengthBar.style.height = '5px';
            
            if (!document.querySelector('.password-strength')) {
                this.parentNode.insertAdjacentHTML('afterend', '<div class="password-strength"></div>');
            }
            
            const strengthIndicator = document.querySelector('.password-strength');
            
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
            
            let strengthText = '';
            let progressClass = '';
            
            switch (strength) {
                case 0:
                case 1:
                    strengthText = 'Weak';
                    progressClass = 'bg-danger';
                    break;
                case 2:
                case 3:
                    strengthText = 'Medium';
                    progressClass = 'bg-warning';
                    break;
                case 4:
                case 5:
                    strengthText = 'Strong';
                    progressClass = 'bg-success';
                    break;
            }
            
            strengthIndicator.innerHTML = `
                <div class="progress mt-2" style="height: 5px">
                    <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${strength * 20}%" aria-valuenow="${strength * 20}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted mt-1 d-block">${strengthText}</small>
            `;
        });
        
      
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (!document.querySelector('.password-match')) {
                this.parentNode.insertAdjacentHTML('afterend', '<div class="password-match"></div>');
            }
            
            const matchIndicator = document.querySelector('.password-match');
            
            if (confirmPassword === '') {
                matchIndicator.innerHTML = '';
            } else if (password === confirmPassword) {
                matchIndicator.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Passwords match</small>';
            } else {
                matchIndicator.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Passwords do not match</small>';
            }
        });
        
        
        (function() {
            'use strict';
            
            window.addEventListener('load', function() {
               
                const forms = document.getElementsByClassName('needs-validation');
                
         
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            if (username.length > 0) {
                checkAvailability(username, 'username');
            }
        });
        
     
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            if (email.length > 0 && validateEmail(email)) {
                checkAvailability(email, 'email');
            }
        });
        
        function validateEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
        
        function checkAvailability(value, type) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_availability.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    const field = document.getElementById(type);
                    
                   
                    const existingFeedback = field.parentNode.querySelector('.availability-feedback');
                    if (existingFeedback) {
                        existingFeedback.remove();
                    }
                    
                    if (response.available) {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                        field.parentNode.insertAdjacentHTML('beforeend', '<div class="availability-feedback text-success mt-1"><small><i class="fas fa-check-circle"></i> Available</small></div>');
                    } else {
                        field.classList.remove('is-valid');
                        field.classList.add('is-invalid');
                        field.parentNode.insertAdjacentHTML('beforeend', '<div class="availability-feedback text-danger mt-1"><small><i class="fas fa-times-circle"></i> Already taken</small></div>');
                    }
                }
            };
            
            xhr.send(`type=${type}&value=${value}`);
        }
    </script>
</body>
</html>