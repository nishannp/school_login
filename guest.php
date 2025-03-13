<?php

session_start();

require_once 'config.php';


$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';
$page_url = $_SERVER['REQUEST_URI'];
$visit_time = date('Y-m-d H:i:s');
$browser = get_browser_name($user_agent);
$device_type = get_device_type($user_agent);
$os = get_operating_system($user_agent);


if ($conn->connect_error == false) {
    $sql = "INSERT INTO guests (ip_address, user_agent, referer, page_url, visit_time, browser, device_type, os) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $ip_address, $user_agent, $referer, $page_url, $visit_time, $browser, $device_type, $os);
    
    if (!$stmt->execute()) {
        error_log("Error recording visitor info: " . $stmt->error);
    }
    
    $stmt->close();
}


function get_browser_name($user_agent) {
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
    return 'Unknown';
}


function get_device_type($user_agent) {
    $mobile_agents = array('Android', 'webOS', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone');
    foreach ($mobile_agents as $device) {
        if (strpos($user_agent, $device) !== false) {
            return 'Mobile';
        }
    }
    return 'Desktop';
}


function get_operating_system($user_agent) {
    $os_platform = "Unknown";
    $os_array = array(
        '/windows nt 10/i'      => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/windows nt 5.0/i'     => 'Windows 2000',
        '/windows me/i'         => 'Windows ME',
        '/win98/i'              => 'Windows 98',
        '/win95/i'              => 'Windows 95',
        '/win16/i'              => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/linux/i'              => 'Linux',
        '/ubuntu/i'             => 'Ubuntu',
        '/iphone/i'             => 'iPhone',
        '/ipod/i'               => 'iPod',
        '/ipad/i'               => 'iPad',
        '/android/i'            => 'Android',
        '/blackberry/i'         => 'BlackBerry',
        '/webos/i'              => 'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Open Day - Guest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
       
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #4285f4;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 25px;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 5px 8px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
   
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-university me-2"></i>University Open Day
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events"><i class="fas fa-calendar-alt me-1"></i> Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#map"><i class="fas fa-map-marker-alt me-1"></i> Campus Map</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#faq"><i class="fas fa-question-circle me-1"></i> FAQ</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="index.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a href="signup.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Signup
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <header class="header text-center">
        <div class="container">
            <h1><i class="fas fa-graduation-cap me-3"></i>Welcome to Our University Open Day</h1>
            <p class="lead">Explore our campus, attend informative sessions, and discover your future with us!</p>
            <div class="mt-4">
                <a href="#events" class="btn btn-light btn-lg me-2">
                    <i class="fas fa-calendar-check me-1"></i> View Schedule
                </a>
                <a href="#map" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-map me-1"></i> Campus Map
                </a>
            </div>
        </div>
    </header>

    <div class="container">

        <div class="alert alert-info" role="alert">
            <i class="fas fa-bullhorn me-2"></i>
            <strong>Announcement:</strong> The Computer Science talk has been moved to Room 205B at 2:30 PM.
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

    
        <section id="featured" class="mb-5">
            <h2 class="section-title">Featured Sessions</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header text-center">
                            <i class="fas fa-star me-1"></i> Highlight
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-laptop-code feature-icon"></i>
                            <h5 class="card-title">Computer Science Showcase</h5>
                            <p class="card-text">Explore the latest technologies and see student projects in action.</p>
                            <p><i class="far fa-clock me-1"></i> 1:00 PM - 3:00 PM</p>
                            <p><i class="fas fa-map-marker-alt me-1"></i> Technology Building</p>
                            <a href="#" class="btn btn-primary">Add to My Schedule</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header text-center">
                            <i class="fas fa-star me-1"></i> Highlight
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-flask feature-icon"></i>
                            <h5 class="card-title">Science Lab Tours</h5>
                            <p class="card-text">Visit our state-of-the-art laboratories and see demonstrations.</p>
                            <p><i class="far fa-clock me-1"></i> 11:00 AM - 2:00 PM</p>
                            <p><i class="fas fa-map-marker-alt me-1"></i> Science Complex</p>
                            <a href="#" class="btn btn-primary">Add to My Schedule</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header text-center">
                            <i class="fas fa-star me-1"></i> Highlight
                        </div>
                        <div class="card-body text-center">
                            <i class="fas fa-comments feature-icon"></i>
                            <h5 class="card-title">Student Panel Q&A</h5>
                            <p class="card-text">Hear directly from current students about campus life and academics.</p>
                            <p><i class="far fa-clock me-1"></i> 3:30 PM - 4:30 PM</p>
                            <p><i class="fas fa-map-marker-alt me-1"></i> Student Union</p>
                            <a href="#" class="btn btn-primary">Add to My Schedule</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

   
        <section id="events" class="mb-5">
            <h2 class="section-title">Event Schedule</h2>
            <div class="card">
                <div class="card-body">
                    <div class="btn-group mb-4" role="group">
                        <button type="button" class="btn btn-outline-primary active">All Events</button>
                        <button type="button" class="btn btn-outline-primary">Academic</button>
                        <button type="button" class="btn btn-outline-primary">Campus Life</button>
                        <button type="button" class="btn btn-outline-primary">Tours</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>9:00 AM - 10:00 AM</td>
                                    <td>Welcome and Registration</td>
                                    <td>Main Hall</td>
                                    <td><span class="badge bg-secondary">General</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>10:15 AM - 11:15 AM</td>
                                    <td>Introduction to Business School</td>
                                    <td>Business Building, Room 101</td>
                                    <td><span class="badge bg-info">Academic</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>10:15 AM - 11:15 AM</td>
                                    <td>Engineering Department Presentation</td>
                                    <td>Engineering Hall</td>
                                    <td><span class="badge bg-info">Academic</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>11:30 AM - 12:30 PM</td>
                                    <td>Campus Housing Information</td>
                                    <td>Student Center</td>
                                    <td><span class="badge bg-success">Campus Life</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>12:30 PM - 1:30 PM</td>
                                    <td>Lunch Break</td>
                                    <td>Cafeteria</td>
                                    <td><span class="badge bg-secondary">General</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>1:45 PM - 2:45 PM</td>
                                    <td>Financial Aid Workshop</td>
                                    <td>Administration Building</td>
                                    <td><span class="badge bg-warning text-dark">Services</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                                <tr>
                                    <td>3:00 PM - 4:30 PM</td>
                                    <td>Campus Tour</td>
                                    <td>Meeting at Visitor Center</td>
                                    <td><span class="badge bg-danger">Tour</span></td>
                                    <td><button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

     
        <section id="map" class="mb-5">
            <h2 class="section-title">Campus Map</h2>
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-3">
                      
                        <div class="bg-light p-5 rounded">
                            <i class="fas fa-map-marked-alt" style="font-size: 5rem; color: var(--primary-color);"></i>
                            <h4 class="mt-3">Interactive Campus Map</h4>
                            <p class="text-muted">Explore buildings, event locations, and facilities.</p>
                            <button class="btn btn-primary mt-2">
                                <i class="fas fa-location-arrow me-1"></i> Show My Location
                            </button>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-building me-2"></i>Key Locations</h5>
                            <ul class="list-group">
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Main Hall - Registration & Welcome</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Student Union - Information Center</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Library - Tours Every Hour</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Science Complex - Lab Demonstrations</li>
                                <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Cafeteria - Lunch Available</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle me-2"></i>Facilities</h5>
                            <ul class="list-group">
                                <li class="list-group-item"><i class="fas fa-restroom me-2 text-primary"></i>Restrooms in all buildings</li>
                                <li class="list-group-item"><i class="fas fa-wifi me-2 text-primary"></i>Free Wi-Fi throughout campus</li>
                                <li class="list-group-item"><i class="fas fa-coffee me-2 text-primary"></i>Coffee shops in Student Union and Library</li>
                                <li class="list-group-item"><i class="fas fa-first-aid me-2 text-primary"></i>First Aid station in Main Hall</li>
                                <li class="list-group-item"><i class="fas fa-parking me-2 text-primary"></i>Visitor parking in Lots A and B</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

       
        <section id="faq" class="mb-5">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                            <i class="fas fa-question-circle me-2"></i> What should I bring to Open Day?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We recommend bringing a notebook, water bottle, comfortable shoes for walking around campus, and a list of questions you might have about our programs or facilities.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            <i class="fas fa-question-circle me-2"></i> Is parking available on campus?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, free visitor parking is available in Lots A and B. Follow the signs when you arrive on campus. Overflow parking will be directed by our staff if needed.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            <i class="fas fa-question-circle me-2"></i> Can I bring my parents or guardians?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Absolutely! We encourage family members to join you. There are specific parent sessions scheduled throughout the day to address questions that parents typically have.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                            <i class="fas fa-question-circle me-2"></i> Do I need to register for individual sessions?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Most sessions are open to all attendees, but some specialized workshops or lab tours may have limited capacity. We recommend creating an account and adding sessions to your personal schedule to reserve your spot.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                            <i class="fas fa-question-circle me-2"></i> Will there be food available?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, our cafeteria will be open throughout the day. Additionally, there will be complimentary refreshments available at various locations across campus. Food options include vegetarian, vegan, and gluten-free choices.
                        </div>
                    </div>
                </div>
            </div>
        </section>

       
        <section id="feedback" class="mb-5">
            <h2 class="section-title">Share Your Experience</h2>
            <div class="card">
                <div class="card-body">
                    <form id="feedbackForm">
                        <div class="mb-3">
                            <label for="sessionName" class="form-label">Which session did you attend?</label>
                            <select class="form-select" id="sessionName" required>
                                <option value="" selected disabled>Select a session</option>
                                <option value="welcome">Welcome and Registration</option>
                                <option value="business">Introduction to Business School</option>
                                <option value="engineering">Engineering Department Presentation</option>
                                <option value="housing">Campus Housing Information</option>
                                <option value="financial">Financial Aid Workshop</option>
                                <option value="tour">Campus Tour</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">How would you rate this session?</label>
                            <div class="rating">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="rating" id="star5" value="5" required>
                                    <label class="btn btn-outline-warning" for="star5"><i class="fas fa-star"></i> 5</label>
                                    
                                    <input type="radio" class="btn-check" name="rating" id="star4" value="4">
                                    <label class="btn btn-outline-warning" for="star4"><i class="fas fa-star"></i> 4</label>
                                    
                                    <input type="radio" class="btn-check" name="rating" id="star3" value="3">
                                    <label class="btn btn-outline-warning" for="star3"><i class="fas fa-star"></i> 3</label>
                                    
                                    <input type="radio" class="btn-check" name="rating" id="star2" value="2">
                                    <label class="btn btn-outline-warning" for="star2"><i class="fas fa-star"></i> 2</label>
                                    
                                    <input type="radio" class="btn-check" name="rating" id="star1" value="1">
                                    <label class="btn btn-outline-warning" for="star1"><i class="fas fa-star"></i> 1</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="feedbackComment" class="form-label">Comments or suggestions:</label>
                            <textarea class="form-control" id="feedbackComment" rows="3" placeholder="Please share your thoughts..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5><i class="fas fa-university me-2"></i>University Open Day</h5>
                    <p>Join us to explore your future academic journey and discover the opportunities waiting for you.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5><i class="fas fa-phone-alt me-2"></i>Contact Us</h5>
                    <p><i class="fas fa-envelope me-2"></i>universityopenday@gmail.com</p>
                    <p><i class="fas fa-phone me-2"></i>(921) 0000000</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i>University Open Day</p>
                </div>
                <div class="col-md-4">
                    <h5><i class="fas fa-link me-2"></i>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white"><i class="fas fa-angle-right me-2"></i>University Homepage</a></li>
                        <li><a href="#" class="text-white"><i class="fas fa-angle-right me-2"></i>Admissions</a></li>
                        <li><a href="#" class="text-white"><i class="fas fa-angle-right me-2"></i>Programs & Degrees</a></li>
                        <li><a href="#" class="text-white"><i class="fas fa-angle-right me-2"></i>Campus Life</a></li>
                        <li><a href="#" class="text-white"><i class="fas fa-angle-right me-2"></i>Virtual Tour</a></li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4 bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 University Open Day. All rights reserved.</p>
            </div>
        </div>
    </footer>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
   
    <script>
       
        document.addEventListener('DOMContentLoaded', function() {
            
          
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            const addButtons = document.querySelectorAll('.btn-outline-primary');
            addButtons.forEach(button => {
                button.addEventListener('click', function() {
                   
                    if (this.innerHTML.includes('Add')) {
                        this.innerHTML = '<i class="fas fa-check"></i> Added';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success');
                        
                    
                        alert('Event added to your schedule!');
                    } else {
                        this.innerHTML = '<i class="fas fa-plus"></i> Add';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }
                });
            });
            
      
            const feedbackForm = document.getElementById('feedbackForm');
            if (feedbackForm) {
                feedbackForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                 
                    const sessionName = document.getElementById('sessionName').value;
                    const rating = document.querySelector('input[name="rating"]:checked')?.value;
                    const comment = document.getElementById('feedbackComment').value;
                    
                    
                    if (!sessionName || !rating) {
                        alert('Please select a session and provide a rating.');
                        return;
                    }
                    
                  
                    alert('Thank you for your feedback! Your input helps us improve future Open Days.');
                    
                   
                    this.reset();
                });
            }
            
           
            setTimeout(function() {
                const newNotification = document.createElement('div');
                newNotification.className = 'alert alert-warning alert-dismissible fade show';
                newNotification.role = 'alert';
                newNotification.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Update:</strong> Due to high interest, we've added an extra Computer Science session at 4:00 PM.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
              
                const existingAlert = document.querySelector('.alert');
                existingAlert.parentNode.insertBefore(newNotification, existingAlert.nextSibling);
            }, 5000); 
        });
    </script>
</body>
</html>