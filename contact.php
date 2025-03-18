<?php

require_once 'config.php';

$message = '';
$messageClass = '';

session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_email = '';
$user_name = '';

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $user_name = $row['full_name'];
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message_content = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $message = "Please fill in all fields";
        $messageClass = "alert-danger";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address";
        $messageClass = "alert-danger";
    } else {

        $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $user_id, $name, $email, $subject, $message_content);

        if ($stmt->execute()) {

            $to = "admin@campusconnect.com"; 
            $email_subject = "New Contact Form Submission: $subject";
            $email_body = "You have received a new message from the contact form.\n\n"
                . "Name: $name\n"
                . "Email: $email\n"
                . "Subject: $subject\n\n"
                . "Message:\n$message_content";
            $headers = "From: noreply@campusconnect.com";

            mail($to, $email_subject, $email_body, $headers);

            $message = "Your message has been sent. We'll get back to you soon!";
            $messageClass = "alert-success";

            $name = $email = $subject = $message_content = '';
        } else {
            $message = "Sorry, there was an error sending your message. Please try again later.";
            $messageClass = "alert-danger";
        }
        $stmt->close();
    }
}

$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 0,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

if ($conn->query($sql) !== TRUE) {

}

include 'header.php';

?>

<!-- Contact Page Content -->
<div class="contact-page">
    <!-- Banner Section -->
    <div class="contact-banner">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Have questions about campus events? We're here to help!</p>
        </div>
    </div>

    <!-- Main Contact Section -->
    <div class="container py-5">
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card contact-form-card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Send us a message</h2>

                        <?php if (!empty($message)): ?>
                            <div class="alert <?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $user_name ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user_email ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="card contact-info-card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Get in Touch</h2>

                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h5>Our Location</h5>
                                <p>123 Hampton, University Park<br>College, 1234 </p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h5>Email Us</h5>
                                <p><a href="mailto:events@campusconnect.com">events@campusconnect.com</a></p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <h5>Call Us</h5>
                                <p>(123) 3333333</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h5>Office Hours</h5>
                                <p>Monday - Friday: 9:00 AM - 5:00 PM<br>Weekend: Closed</p>
                            </div>
                        </div>

                        <div class="contact-social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campus Map Section -->
<div class="campus-map-section">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-12">
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.6175428857!2d-73.98656708430262!3d40.74881294295503!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQ0JzU1LjgiTiA3M8KwNTknMDguMCJX!5e0!3m2!1sen!2sus!4v1553815896076" width="100%" height="450" style="border:0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Contact Page -->
<style>

    .contact-banner {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://plus.unsplash.com/premium_photo-1683887034552-4635692bb57c?q=80&w=3869&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
        background-size: cover;
        background-position: center;
        color: #fff;
        padding: 80px 0;
        text-align: center;
        margin-bottom: 30px;
    }

    .contact-banner h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .contact-banner p {
        font-size: 1.25rem;
        max-width: 700px;
        margin: 0 auto;
    }

    .contact-form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        padding: 10px;
        height: 100%;
    }

    .contact-info-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        padding: 10px;
        background-color: #f8f9fa;
        height: 100%;
    }

    .contact-info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 25px;
    }

    .contact-info-item i {
        font-size: 1.5rem;
        color: #007bff;
        margin-right: 15px;
        margin-top: 5px;
    }

    .contact-info-item h5 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .contact-info-item p {
        margin-bottom: 0;
        color: #6c757d;
    }

    .contact-social-links {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }

    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #007bff;
        color: #fff;
        margin: 0 10px;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background-color: #0056b3;
        transform: translateY(-3px);
    }

    .map-container {
        position: relative;
        overflow: hidden;
        padding-top: 56.25%;
    }

    .map-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #ced4da;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        border-color: #80bdff;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }

    @keyframes formSuccess {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .alert-success {
        animation: formSuccess 0.5s ease;
    }

    @media (max-width: 991px) {
        .contact-info-card {
            margin-top: 30px;
        }
    }

    @media (max-width: 767px) {
        .contact-banner h1 {
            font-size: 2.5rem;
        }

        .contact-banner p {
            font-size: 1rem;
        }
    }
</style>

<!-- JavaScript for form validation and submission -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');

    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            let isValid = true;

            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');

            if (name.value.trim() === '') {
                highlightField(name);
                isValid = false;
            } else {
                resetField(name);
            }

            if (email.value.trim() === '' || !isValidEmail(email.value)) {
                highlightField(email);
                isValid = false;
            } else {
                resetField(email);
            }

            if (subject.value.trim() === '') {
                highlightField(subject);
                isValid = false;
            } else {
                resetField(subject);
            }

            if (message.value.trim() === '') {
                highlightField(message);
                isValid = false;
            } else {
                resetField(message);
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    function highlightField(field) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    }

    function resetField(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    const userEmail = '<?php echo $user_email; ?>';
    const userName = '<?php echo $user_name; ?>';

    if (userEmail && userName) {
        const emailField = document.getElementById('email');
        const nameField = document.getElementById('name');

        if (emailField && nameField) {
            emailField.value = userEmail;
            nameField.value = userName;
        }
    }
});
</script>

<?php

include 'footer.php';
?>