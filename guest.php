<?php
session_start();
require_once 'config.php';


$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';
$page_url = $_SERVER['REQUEST_URI'];
$visit_time = date('Y-m-d H:i:s');


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
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/linux/i'              => 'Linux',
        '/ubuntu/i'             => 'Ubuntu',
        '/iphone/i'             => 'iPhone',
        '/ipad/i'               => 'iPad',
        '/android/i'            => 'Android'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}

$browser = get_browser_name($user_agent);
$device_type = get_device_type($user_agent);
$os = get_operating_system($user_agent);


if ($conn && $conn->connect_error == false) {
    $sql = "INSERT INTO guests (ip_address, user_agent, referer, page_url, visit_time, browser, device_type, os) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $ip_address, $user_agent, $referer, $page_url, $visit_time, $browser, $device_type, $os);
    
    if (!$stmt->execute()) {
        error_log("Error recording visitor info: " . $stmt->error);
    }
    
    $stmt->close();
}

// dummy events for now
$events = [
    [
        'time' => '9:00 AM - 10:00 AM',
        'title' => 'Welcome and Registration',
        'location' => 'Main Hall',
        'category' => 'General'
    ],
    [
        'time' => '10:15 AM - 11:15 AM',
        'title' => 'Introduction to Business School',
        'location' => 'Business Building, Room 101',
        'category' => 'Academic'
    ],
    [
        'time' => '10:15 AM - 11:15 AM',
        'title' => 'Engineering Department Presentation',
        'location' => 'Engineering Hall',
        'category' => 'Academic'
    ],
    [
        'time' => '11:30 AM - 12:30 PM',
        'title' => 'Campus Housing Information',
        'location' => 'Student Center',
        'category' => 'Campus Life'
    ],
    [
        'time' => '12:30 PM - 1:30 PM',
        'title' => 'Lunch Break',
        'location' => 'Cafeteria',
        'category' => 'General'
    ],
    [
        'time' => '1:45 PM - 2:45 PM',
        'title' => 'Financial Aid Workshop',
        'location' => 'Administration Building',
        'category' => 'Services'
    ],
    [
        'time' => '3:00 PM - 4:30 PM',
        'title' => 'Campus Tour',
        'location' => 'Meeting at Visitor Center',
        'category' => 'Tour'
    ]
];

// dummy features session for now
$featured = [
    [
        'title' => 'Computer Science Showcase',
        'description' => 'Explore the latest technologies and see student projects in action.',
        'time' => '1:00 PM - 3:00 PM',
        'location' => 'Technology Building',
        'icon' => 'laptop-code'
    ],
    [
        'title' => 'Science Lab Tours',
        'description' => 'Visit our state-of-the-art laboratories and see demonstrations.',
        'time' => '11:00 AM - 2:00 PM',
        'location' => 'Science Complex',
        'icon' => 'flask'
    ],
    [
        'title' => 'Student Panel Q&A',
        'description' => 'Hear directly from current students about campus life and academics.',
        'time' => '3:30 PM - 4:30 PM',
        'location' => 'Student Union',
        'icon' => 'comments'
    ]
];


$faq = [
    [
        'question' => 'What should I bring to Campus Connect?',
        'answer' => 'We recommend bringing a notebook, water bottle, comfortable shoes for walking around campus, and a list of questions you might have about our programs or facilities.'
    ],
    [
        'question' => 'Is parking available on campus?',
        'answer' => 'Yes, free visitor parking is available in Lots A and B. Follow the signs when you arrive on campus. Overflow parking will be directed by our staff if needed.'
    ],
    [
        'question' => 'Can I bring my parents or guardians?',
        'answer' => 'Absolutely! We encourage family members to join you. There are specific parent sessions scheduled throughout the day to address questions that parents typically have.'
    ],
    [
        'question' => 'Do I need to register for individual sessions?',
        'answer' => 'Most sessions are open to all attendees, but some specialized workshops or lab tours may have limited capacity. We recommend creating an account and adding sessions to your personal schedule to reserve your spot.'
    ],
    [
        'question' => 'Will there be food available?',
        'answer' => 'Yes, our cafeteria will be open throughout the day with vegetarian, vegan, and gluten-free options. Complimentary refreshments will also be available at various locations across campus.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest - Campus Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png">
<link rel="manifest" href="favicon_/site.webmanifest">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary: #4cc9f0;
            --accent: #f72585;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #48cae4;
            --gradient: linear-gradient(120deg, var(--primary), var(--primary-dark));
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: var(--dark);
            line-height: 1.6;
        }

     
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.7rem 1rem;
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 80%;
        }

        .btn-auth {
            font-weight: 500;
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-login {
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-login:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-signup {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-signup:hover {
            background: var(--primary-dark);
            color: white;
        }

        
        .hero {
            background: var(--gradient);
            padding: 5rem 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('path/to/pattern.svg');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-weight: 700;
            font-size: 3rem;
        }

        .hero .lead {
            font-size: 1.25rem;
            font-weight: 300;
            margin-bottom: 2rem;
        }

        .btn-hero {
            border-radius: 30px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-light {
            background: white;
            color: var(--primary-dark);
        }

        .btn-light:hover {
            background: var(--light);
            transform: translateY(-3px);
        }

        .btn-outline-light:hover {
            transform: translateY(-3px);
        }

      
        section {
            padding: 5rem 0;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 50px;
            height: 3px;
            background-color: var(--accent);
        }

        
        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .card-featured {
            border-top: 4px solid var(--primary);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .card-text {
            color: var(--gray);
        }

        .card-detail {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        
        .event-filters {
            margin-bottom: 2rem;
        }

        .event-filter-btn {
            border-radius: 30px;
            padding: 0.5rem 1.5rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
            background-color: white;
            color: var(--dark);
            border: 1px solid #eee;
        }

        .event-filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .event-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .event-table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            border: none;
        }

        .event-table tbody tr {
            transition: all 0.3s ease;
        }

        .event-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .badge-category {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 500;
        }

        .badge-general {
            background-color: #e9ecef;
            color: var(--dark);
        }

        .badge-academic {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .badge-campus {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--secondary);
        }

        .badge-tour {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--accent);
        }

        .badge-services {
            background-color: rgba(72, 202, 228, 0.1);
            color: var(--success);
        }

        .btn-add {
            border-radius: 30px;
            padding: 0.3rem 1rem;
            font-weight: 500;
        }

        
        .map-container {
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .map-placeholder {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 3rem 0;
            text-align: center;
        }

        .location-list {
            margin-top: 2rem;
        }

        .location-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .location-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .location-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 1rem;
            color: white;
        }

        .location-text {
            flex: 1;
        }

        .location-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .location-subtitle {
            font-size: 0.85rem;
            color: var(--gray);
        }

        
        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 15px !important;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .accordion-button {
            font-weight: 500;
            padding: 1.5rem;
            background-color: white;
            color: var(--dark);
        }

        .accordion-button:not(.collapsed) {
            background-color: white;
            color: var(--primary);
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(67, 97, 238, 0.1);
        }

        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%234361ee'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }

       
        footer {
            background-color: var(--dark);
            color: var(--light);
            padding: 4rem 0 2rem;
        }

        footer h5 {
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
        }

        footer p, footer a {
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }

        footer a:hover {
            color: white;
            text-decoration: none;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 0.75rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            display: inline-block;
            position: relative;
        }

        .footer-links a::before {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: white;
            transition: all 0.3s ease;
        }

        .footer-links a:hover::before {
            width: 100%;
        }

        .copyright {
            padding-top: 2rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
        }

    
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate {
            animation: fadeIn 0.6s ease forwards;
        }

       
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 4rem 0;
            }
            .hero h1 {
                font-size: 2rem;
            }
            section {
                padding: 3rem 0;
            }
        }

      
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            max-width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1rem;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.5s ease;
        }

        .notification.show {
            transform: translateX(0);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .notification-title {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .notification-close {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
        }
        
        .notification-body {
            color: var(--dark);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-university me-2"></i> Campus Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#featured">Featured</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#schedule">Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#map">Campus Map</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#faq">FAQ</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="index.php" class="btn btn-auth btn-login me-2">
                        Login
                    </a>
                    <a href="signup.php" class="btn btn-auth btn-signup">
                        Sign Up
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <section class="hero" id="home">
        <div class="container hero-content text-center">
            <h1 class="animate">Discover Your Future</h1>
            <p class="lead animate" style="animation-delay: 0.1s">
                Explore our campus, meet faculty and students, and find the perfect program for your academic journey.
            </p>
            <div class="animate" style="animation-delay: 0.2s">
                <a href="#schedule" class="btn btn-hero btn-light me-2">
                    <i class="fas fa-calendar-check me-2"></i>View Schedule
                </a>
                <a href="#map" class="btn btn-hero btn-outline-light">
                    <i class="fas fa-map-marker-alt me-2"></i>Campus Map
                </a>
            </div>
        </div>
    </section>
    <div class="container mt-4">
        <div class="alert alert-primary d-flex align-items-center" role="alert">
            <i class="fas fa-bullhorn me-3 fs-4"></i>
            <div>
                <strong>Important Update:</strong> The Computer Science talk has been moved to Room 205B at 2:30 PM.
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <section id="featured">
        <div class="container">
            <h2 class="section-title">Featured Sessions</h2>
            <div class="row g-4">
                <?php foreach($featured as $item): ?>
                <div class="col-md-4">
                    <div class="card card-featured h-100 text-center">
                        <div class="card-body">
                            <div class="card-icon">
                                <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo $item['title']; ?></h5>
                            <p class="card-text"><?php echo $item['description']; ?></p>
                            <p class="card-detail">
                                <i class="far fa-clock me-1"></i> <?php echo $item['time']; ?>
                            </p>
                            <p class="card-detail">
                                <i class="fas fa-map-marker-alt me-1"></i> <?php echo $item['location']; ?>
                            </p>
                            <button class="btn btn-primary mt-3 add-event" data-event="<?php echo $item['title']; ?>">
                                <i class="fas fa-plus me-1"></i> Add to My Schedule
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <section id="schedule" class="bg-light">
        <div class="container">
            <h2 class="section-title">Event Schedule</h2>
            
            <div class="event-filters">
                <button class="event-filter-btn active" data-filter="all">All Events</button>
                <button class="event-filter-btn" data-filter="Academic">Academic</button>
                <button class="event-filter-btn" data-filter="Campus Life">Campus Life</button>
                <button class="event-filter-btn" data-filter="Tour">Tours</button>
                <button class="event-filter-btn" data-filter="Services">Services</button>
                <button class="event-filter-btn" data-filter="General">General</button>
            </div>

            <div class="table-responsive event-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($events as $event): ?>
                        <tr class="event-item" data-category="<?php echo $event['category']; ?>">
                            <td><?php echo $event['time']; ?></td>
                            <td><strong><?php echo $event['title']; ?></strong></td>
                            <td><?php echo $event['location']; ?></td>
                            <td>
                                <?php 
                                $badgeClass = 'badge-general';
                                if($event['category'] == 'Academic') $badgeClass = 'badge-academic';
                                if($event['category'] == 'Campus Life') $badgeClass = 'badge-campus';
                                if($event['category'] == 'Tour') $badgeClass = 'badge-tour';
                                if($event['category'] == 'Services') $badgeClass = 'badge-services';
                                ?>
                                <span class="badge-category <?php echo $badgeClass; ?>"><?php echo $event['category']; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm btn-add add-event" data-event="<?php echo $event['title']; ?>">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <section id="map">
        <div class="container">
            <h2 class="section-title">Campus Map</h2>
            
            <div class="map-container">
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt" style="font-size: 4rem; color: var(--primary);"></i>
                    <h4 class="mt-3">Interactive Campus Map</h4>
                    <p class="text-muted">Explore buildings, event locations, and facilities</p>
                    <button class="btn btn-primary mt-2">
                        <i class="fas fa-location-arrow me-1"></i> Show My Location
                    </button>
                </div>
                
                <div class="location-list">
                    <h5>Key Locations</h5>
                    <div class="location-item">
                        <div class="location-icon" style="background-color: var(--primary);">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="location-text">
                            <div class="location-title">Main Hall</div>
                            <div class="location-subtitle">Registration and Welcome</div>
                        </div>
                    </div>
                    <div class="location-item">
                        <div class="location-icon" style="background-color: var(--secondary);">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="location-text">
                            <div class="location-title">Cafeteria</div>
                            <div class="location-subtitle">Lunch Break Area</div>
                        </div>
                    </div>
                    <div class="location-item">
                        <div class="location-icon" style="background-color: var(--accent);">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div class="location-text">
                            <div class="location-title">Technology Building</div>
                            <div class="location-subtitle">Computer Science Showcase</div>
                        </div>
                    </div>
                    <div class="location-item">
                        <div class="location-icon" style="background-color: var(--success);">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="location-text">
                            <div class="location-title">Science Complex</div>
                            <div class="location-subtitle">Lab Tours</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="faq" class="bg-light">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            
            <div class="accordion" id="faqAccordion">
                <?php for($i = 0; $i < count($faq); $i++): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo $i; ?>">
                        <button class="accordion-button <?php echo $i > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $i; ?>" aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $i; ?>">
                            <?php echo $faq[$i]['question']; ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $i; ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?php echo $faq[$i]['answer']; ?>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <section id="contact">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h2 class="section-title">Get in Touch</h2>
                    <p class="mb-4">Have questions about Campus Connect or our programs? Contact us and we'll be happy to assist you.</p>
                    
                    <form id="contactForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Contact Information</h4>
                            
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-map-marker-alt fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Visit Us</h5>
                                    <p class="mb-0">123 University Avenue, College Town, CT 12345</p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-phone-alt fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Call Us</h5>
                                    <p class="mb-0">(123) 456-7890</p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-envelope fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Email Us</h5>
                                    <p class="mb-0">openday@university.edu</p>
                                </div>
                            </div>
                            
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Open Hours</h5>
                                    <p class="mb-0">Monday - Friday: 8:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5>Campus Connect</h5>
                    <p>Discover your future with us. Explore our campus, meet faculty and students, and find the perfect program for your academic journey.</p>
                    <div class="social-links mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#featured">Featured</a></li>
                        <li><a href="#schedule">Schedule</a></li>
                        <li><a href="#map">Campus Map</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5>Programs</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#">Business School</a></li>
                        <li><a href="#">Engineering</a></li>
                        <li><a href="#">Computer Science</a></li>
                        <li><a href="#">Arts & Humanities</a></li>
                        <li><a href="#">Health Sciences</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Newsletter</h5>
                    <p>Subscribe to our newsletter for updates on upcoming events and admissions.</p>
                    <form class="mt-3">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your email">
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center copyright">
                <hr class="my-4">
                <p>© 2025 Campus Connect. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
<!-- Toast notification for every event added  lol -->
    <div class="notification" id="notification">
        <div class="notification-header">
            <span class="notification-title">Event Added</span>
            <button class="notification-close" id="notificationClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notification-body">
            <p id="notificationText">Event has been added to your schedule.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.event-filter-btn').forEach(button => {
            button.addEventListener('click', function() {
            
                document.querySelectorAll('.event-filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');
                document.querySelectorAll('.event-item').forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-category') === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        document.querySelectorAll('.add-event').forEach(button => {
            button.addEventListener('click', function() {
                const eventName = this.getAttribute('data-event');
                const notification = document.getElementById('notification');
                const notificationText = document.getElementById('notificationText');
                
                notificationText.textContent = `"${eventName}" has been added to your schedule.`;
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);

                console.log(`Added event: ${eventName}`);
            });
        });

        document.getElementById('notificationClose').addEventListener('click', function() {
            document.getElementById('notification').classList.remove('show');
        });
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
   
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        window.addEventListener('scroll', function() {
            const sections = ['home', 'featured', 'schedule', 'map', 'faq'];
            let current = '';
            
            sections.forEach(section => {
                const element = document.getElementById(section);
                if (element) {
                    const rect = element.getBoundingClientRect();
                    if (rect.top <= 100) {
                        current = section;
                    }
                }
            });
            
            if (current) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
        
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();

            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>