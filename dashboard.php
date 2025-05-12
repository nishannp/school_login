<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 1) {
    $user = $user_result->fetch_assoc();

    $update_login = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_login);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
} else {

    session_destroy();
    header("Location: index.php");
    exit;
}

$events_query = "SELECT 
    e.event_id as id, 
    e.title, 
    e.description, 
    DATE_FORMAT(e.event_date, '%Y-%m-%d') as event_date, 
    DATE_FORMAT(e.start_time, '%H:%i') as start_time, 
    DATE_FORMAT(e.end_time, '%H:%i') as end_time, 
    CONCAT(DATE_FORMAT(e.start_time, '%H:%i'), ' - ', DATE_FORMAT(e.end_time, '%H:%i')) as time,
    e.location, 
    e.department as category, 
    COALESCE(e.event_image_resized, e.event_image_original, 'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') as image
FROM events e
ORDER BY e.event_date, e.start_time";

$events_result = $conn->query($events_query);
$events = [];

if ($events_result->num_rows > 0) {
    while ($row = $events_result->fetch_assoc()) {
        $events[] = $row;
    }
} else {

    $events = [

    ];
}

$favorites_query = "SELECT event_id FROM user_favorites WHERE user_id = ?";
$favorites_stmt = $conn->prepare($favorites_query);
$favorites_stmt->bind_param("i", $user_id);
$favorites_stmt->execute();
$favorites_result = $favorites_stmt->get_result();

$user_favorites = [];
if ($favorites_result->num_rows > 0) {
    while ($row = $favorites_result->fetch_assoc()) {
        $user_favorites[] = $row['event_id'];
    }
}

$schedules_query = "SELECT event_id FROM user_schedules WHERE user_id = ?";
$schedules_stmt = $conn->prepare($schedules_query);
$schedules_stmt->bind_param("i", $user_id);
$schedules_stmt->execute();
$schedules_result = $schedules_stmt->get_result();

$user_schedules = [];
if ($schedules_result->num_rows > 0) {
    while ($row = $schedules_result->fetch_assoc()) {
        $user_schedules[] = $row['event_id'];
    }
}

$user_events = $user_favorites;

$schedule_count_query = "SELECT COUNT(*) as count FROM user_schedules WHERE user_id = ?";
$schedule_count_stmt = $conn->prepare($schedule_count_query);
$schedule_count_stmt->bind_param("i", $user_id);
$schedule_count_stmt->execute();
$schedule_count_result = $schedule_count_stmt->get_result();
$schedule_count = $schedule_count_result->fetch_assoc()['count'];

$hours_query = "SELECT 
    SUM(TIME_TO_SEC(TIMEDIFF(e.end_time, e.start_time))/3600) as total_hours 
    FROM user_schedules us 
    JOIN events e ON us.event_id = e.event_id 
    WHERE us.user_id = ?";
$hours_stmt = $conn->prepare($hours_query);
$hours_stmt->bind_param("i", $user_id);
$hours_stmt->execute();
$hours_result = $hours_stmt->get_result();
$total_hours = $hours_result->fetch_assoc()['total_hours'];
$total_hours = $total_hours ? round($total_hours, 1) : 0;

require_once 'header.php';
?>

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
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border: none;
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .welcome-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-title {
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .welcome-subtitle {
            font-weight: 300;
            max-width: 700px;
            margin: 0 auto 30px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .welcome-btn {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .welcome-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .section-title {
            position: relative;
            margin-bottom: 40px;
            font-weight: 700;
            color: var(--dark-color);
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            background-color: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 25px;
        }

        .card-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .card-text {
            margin-bottom: 20px;
            color: #666;
        }

        .card-footer {
            background-color: white;
            border-top: 1px solid #f0f0f0;
            padding: 15px 25px;
        }

        .badge {
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        .badge-tour {
            background-color: #6610f2;
            color: white;
        }

        .badge-demo {
            background-color: #fd7e14;
            color: white;
        }

        .badge-workshop {
            background-color: #20c997;
            color: white;
        }

        .badge-social {
            background-color: #e83e8c;
            color: white;
        }

        .badge-panel {
            background-color: #17a2b8;
            color: white;
        }

        .event-meta {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .event-location, .event-time {
            margin-right: 15px;
        }

        .btn-event {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-event:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            box-shadow: 0 5px 15px rgba(142, 148, 251, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            box-shadow: 0 8px 20px rgba(142, 148, 251, 0.6);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-favorite {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: white;
            color: #ccc;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .btn-favorite:hover {
            transform: scale(1.1);
        }

        .btn-favorite.active {
            color: var(--accent-color);
        }

        .stats-card {
            padding: 25px;
            border-radius: 15px;
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stats-card-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .stats-card-secondary {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stats-card-tertiary {
            background: linear-gradient(135deg, #FC466B 0%, #3F5EFB 100%);
        }

        .stats-card-quaternary {
            background: linear-gradient(135deg, #ff9966 0%, #ff5e62 100%);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stats-label {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .user-profile {
            background-color: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-right: 20px;
        }

        .profile-info h4 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: #666;
            margin-bottom: 0;
        }

        .profile-interest {
            background-color: var(--light-color);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.9rem;
            color: var(--dark-color);
            display: inline-block;
            margin-top: 10px;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .dropdown-item {
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        .dropdown-divider {
            margin: 5px 0;
        }

        .event-tabs .nav-link {
            color: var(--dark-color) !important;
            font-weight: 600;
            padding: 15px 25px;
            border: none;
            border-radius: 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .event-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: all 0.3s ease;
        }

        .event-tabs .nav-link.active {
            color: var(--primary-color) !important;
            background-color: transparent;
        }

        .event-tabs .nav-link.active::after {
            width: 100%;
        }

        .event-tabs .nav-link:hover::after {
            width: 50%;
        }

        .tab-content {
            padding: 30px 0;
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 50px 0 20px;
            margin-top: 50px;
        }

        .footer-brand {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .footer-text {
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background-color: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-links h5 {
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            height: 300px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .countdown-container {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1517048676732-d65bc937f952?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 50px 0;
            border-radius: 15px;
            margin-bottom: 40px;
            text-align: center;
        }

        .countdown-title {
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .countdown-item {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            min-width: 100px;
        }

        .countdown-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .countdown-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: red;
    color: white;
    font-size: 12px;
    font-weight: bold;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: translate(50%, -50%);
}

        @media (max-width: 991.98px) {
            .welcome-banner {
                padding: 70px 0;
            }

            .stats-card {
                margin-bottom: 20px;
            }

            .countdown {
                flex-wrap: wrap;
            }

            .countdown-item {
                min-width: 80px;
            }

            .countdown-value {
                font-size: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .welcome-banner {
                padding: 50px 0;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .user-profile {
                padding: 20px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .event-tabs .nav-link {
                padding: 10px 15px;
            }
        }

        @media (max-width: 575.98px) {
            .welcome-banner {
                padding: 40px 0;
            }

            .welcome-title {
                font-size: 1.8rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .card-body {
                padding: 20px;
            }

            .footer {
                padding: 30px 0 10px;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }

            .countdown-item {
                min-width: 70px;
                padding: 15px;
            }

            .countdown-value {
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="welcome-banner">
        <div class="container">
            <h1 class="welcome-title">Welcome to Campus Connect, <?php echo $user['first_name']; ?>!</h1>
            <p class="welcome-subtitle">Explore our campus, meet faculty members, and discover what makes our university special. Create your personalized schedule for the perfect visit experience.</p>
            <a href="#events-explorer" class="btn btn-light welcome-btn">
                <i class="fas fa-calendar-alt me-2"></i> Browse Events
            </a>
        </div>
    </div>

    <div class="container">
        <div class="countdown-container">
            <h2 class="countdown-title">Campus Connect Begins In</h2>
            <div class="countdown">
                <div class="countdown-item">
                    <div class="countdown-value" id="days">14</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="hours">22</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="minutes">36</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="seconds">51</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="user-profile">
                    <div class="profile-header">
                        <?php if (!empty($user['profile_photo_resized'])): ?>
                            <img src="<?php echo $user['profile_photo_resized']; ?>" alt="User Avatar" class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar bg-light d-flex align-items-center justify-content-center">
                                <i class="fas fa-user fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="profile-info">
                        <h4><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                            <p><?php echo $user['email']; ?></p>
                            <?php if (!empty($user['academic_interest'])): ?>
                                <span class="profile-interest"><i class="fas fa-graduation-cap me-1"></i> <?php echo $user['academic_interest']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p><i class="fas fa-phone me-2"></i> <?php echo !empty($user['phone_number']) ? $user['phone_number'] : 'No phone number added'; ?></p>
                        <p><i class="fas fa-clock me-2"></i> Account created: <?php echo date('F j, Y', strtotime($user['account_created_at'])); ?></p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> Edit Profile
                        </a>
                        <a href="my_schedule.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-check me-2"></i> My Schedule
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 col-lg-12 mb-4">
                        <div class="stats-card stats-card-primary">
                            <div class="stats-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stats-value"><?php echo count($user_favorites); ?></div>
                            <div class="stats-label">Events Saved</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-12 mb-4">
                        <div class="stats-card stats-card-secondary">
                            <div class="stats-icon"><i class="fas fa-clock"></i></div>
                            <div class="stats-value"><?php echo $total_hours; ?></div>
                            <div class="stats-label">Hours of Activities</div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="events-explorer" class="col-lg-8">
            <ul class="nav nav-tabs event-tabs mb-4" id="eventTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="all-events-tab" data-bs-toggle="tab" data-bs-target="#all-events" type="button" role="tab" aria-controls="all-events" aria-selected="true">
            <i class="fas fa-calendar-alt me-2"></i> All Events
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="my-events-tab" data-bs-toggle="tab" data-bs-target="#my-events" type="button" role="tab" aria-controls="my-events" aria-selected="false">
            <i class="fas fa-heart me-2"></i> My Favorites
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="scheduled-events-tab" data-bs-toggle="tab" data-bs-target="#scheduled-events" type="button" role="tab" aria-controls="scheduled-events" aria-selected="false">
            <i class="fas fa-calendar-check me-2"></i> My Schedule
        </button>
    </li>
</ul>

                <div class="tab-content" id="eventTabsContent">
                    <div class="tab-pane fade show active" id="all-events" role="tabpanel" aria-labelledby="all-events-tab">
                        <h3 class="section-title">All Available Events</h3>

                        <div  class="row">
                            <?php foreach ($events as $event): ?>
                                <div class="col-md-6">
                                    <div class="card">
                                        <button class="btn-favorite <?php echo in_array($event['id'], $user_events) ? 'active' : ''; ?>" data-event-id="<?php echo $event['id']; ?>">
                                            <i class="fas <?php echo in_array($event['id'], $user_events) ? 'fa-heart' : 'fa-heart'; ?>"></i>
                                        </button>
                                        <img src="<?php echo $event['image']; ?>" class="card-img-top" alt="<?php echo $event['title']; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $event['title']; ?></h5>
                                            <div class="event-meta">
                                                <span class="event-time"><i class="far fa-clock me-1"></i> <?php echo $event['time']; ?></span>
                                                <span class="event-location"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $event['location']; ?></span>
                                            </div>
                                            <p class="card-text"><?php echo $event['description']; ?></p>
                                            <span class="badge badge-<?php echo strtolower($event['category']); ?>"><?php echo $event['category']; ?></span>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between">
                                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-event">
                                                <i class="fas fa-info-circle me-1"></i> Details
                                            </a>

                                            <a href="#" class="btn <?php echo in_array($event['id'], $user_schedules) ? 'btn-success' : 'btn-outline-primary'; ?> btn-event add-to-schedule" data-event-id="<?php echo $event['id']; ?>" <?php echo in_array($event['id'], $user_schedules) ? 'disabled' : ''; ?>>
                                                <i class="fas <?php echo in_array($event['id'], $user_schedules) ? 'fa-check' : 'fa-plus'; ?> me-1"></i> <?php echo in_array($event['id'], $user_schedules) ? 'Added' : 'Add to Schedule'; ?>
                                            </a>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="my-events" role="tabpanel" aria-labelledby="my-events-tab">
    <h3 class="section-title">My Favorite Events</h3>

    <?php if (!empty($user_favorites)): ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
                <?php if (in_array($event['id'], $user_favorites)): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <button class="btn-favorite active" data-event-id="<?php echo $event['id']; ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                            <img src="<?php echo $event['image']; ?>" class="card-img-top" alt="<?php echo $event['title']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $event['title']; ?></h5>
                                <div class="event-meta">
                                    <span class="event-time"><i class="far fa-clock me-1"></i> <?php echo $event['time']; ?></span>
                                    <span class="event-location"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $event['location']; ?></span>
                                </div>
                                <p class="card-text"><?php echo $event['description']; ?></p>
                                <span class="badge badge-<?php echo strtolower($event['category']); ?>"><?php echo $event['category']; ?></span>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="#" class="btn btn-primary btn-event">
                                    <i class="fas fa-info-circle me-1"></i> Details
                                </a>
                                <a href="#" class="btn btn-outline-danger btn-event remove-from-favorites" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-times me-1"></i> Remove
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> You haven't saved any favorites yet. Browse the "All Events" tab to find events you're interested in.
        </div>
    <?php endif; ?>
</div>

                    <div class="tab-pane fade" id="scheduled-events" role="tabpanel" aria-labelledby="scheduled-events-tab">
    <h3 class="section-title">My Scheduled Events</h3>

    <?php if (!empty($user_schedules)): ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
                <?php if (in_array($event['id'], $user_schedules)): ?>
                    <div class="col-md-6">
                        <div class="card">
                            <button class="btn-favorite <?php echo in_array($event['id'], $user_favorites) ? 'active' : ''; ?>" data-event-id="<?php echo $event['id']; ?>">
                                <i class="fas <?php echo in_array($event['id'], $user_favorites) ? 'fa-heart' : 'fa-heart'; ?>"></i>
                            </button>
                            <img src="<?php echo $event['image']; ?>" class="card-img-top" alt="<?php echo $event['title']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $event['title']; ?></h5>
                                <div class="event-meta">
                                    <span class="event-time"><i class="far fa-clock me-1"></i> <?php echo $event['time']; ?></span>
                                    <span class="event-location"><i class="fas fa-map-marker-alt me-1"></i> <?php echo $event['location']; ?></span>
                                </div>
                                <p class="card-text"><?php echo $event['description']; ?></p>
                                <span class="badge badge-<?php echo strtolower($event['category']); ?>"><?php echo $event['category']; ?></span>
                            </div>
                            <div class="card-footer d-flex justify-content-between">
                                <a href="#" class="btn btn-primary btn-event">
                                    <i class="fas fa-info-circle me-1"></i> Details
                                </a>
                                <a href="#" class="btn btn-outline-danger btn-event remove-from-schedule" data-event-id="<?php echo $event['id']; ?>">
                                    <i class="fas fa-times me-1"></i> Remove
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> You haven't scheduled any events yet. Browse the "All Events" tab to find events you'd like to attend.
        </div>
    <?php endif; ?>
</div>
                </div>
            </div>
        </div>

        <section id="map" class="my-5">
            <h3 class="section-title">Campus Map</h3>
            <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3288.7855190415685!2d-2.130058022587022!3d52.588052772079216!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x487083cb0f37dc97%3A0x9cb8e3cc0509a0d0!2sUniversity%20of%20Wolverhampton!5e1!3m2!1sne!2snp!4v1741792514045!5m2!1sne!2snp" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-car me-2"></i> Parking Information</h5>
                            <p class="card-text">Visitor parking is available in the Main Campus Garage. The cost is $5 for the entire day with your Campus Connect registration.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-bus me-2"></i> Public Transportation</h5>
                            <p class="card-text">Bus routes 10, 15, and 32 stop directly at the main entrance. The nearest subway station is a 10-minute walk from campus.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-wheelchair me-2"></i> Accessibility</h5>
                            <p class="card-text">All buildings are wheelchair accessible. If you need special accommodations, please contact our accessibility office at 555-123-4567.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationsModalLabel">Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">New Event Added: Engineering Workshop</h6>
                                <small>3 hours ago</small>
                            </div>
                            <p class="mb-1">A new engineering workshop has been added to the schedule.</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Registration Confirmation</h6>
                                <small>1 day ago</small>
                            </div>
                            <p class="mb-1">Your registration for Campus Connect has been confirmed.</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Schedule Change: Campus Tour</h6>
                                <small>2 days ago</small>
                            </div>
                            <p class="mb-1">The Campus Tour has been rescheduled from 10:00 AM to 9:00 AM.</p>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Mark All as Read</button>
                </div>
            </div>
        </div>
    </div>

   

    <script>

        function updateCountdown() {
            const openDayDate = new Date();
            openDayDate.setDate(openDayDate.getDate() + 14);
            openDayDate.setHours(8, 0, 0, 0); 
            const now = new Date().getTime();
            const distance = openDayDate - now;
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            document.getElementById("days").innerHTML = days;
            document.getElementById("hours").innerHTML = hours;
            document.getElementById("minutes").innerHTML = minutes;
            document.getElementById("seconds").innerHTML = seconds;
            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById("days").innerHTML = "0";
                document.getElementById("hours").innerHTML = "0";
                document.getElementById("minutes").innerHTML = "0";
                document.getElementById("seconds").innerHTML = "0";
            }
        }
        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);

    </script>

<script>

document.querySelectorAll('.btn-favorite').forEach(button => {
    button.addEventListener('click', function() {
        const eventId = this.getAttribute('data-event-id');
        const isFavorite = this.classList.contains('active');

        if (!isFavorite) {
            this.classList.add('active');
            this.innerHTML = '<i class="fas fa-heart"></i>';
        } else {
            this.classList.remove('active');
            this.innerHTML = '<i class="far fa-heart"></i>';
        }

        fetch('update_favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${eventId}&action=${isFavorite ? 'remove' : 'add'}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Event ${eventId} ${isFavorite ? 'removed from' : 'added to'} favorites`);

                if (isFavorite) {
                    const favoritesTab = document.getElementById('my-events');
                    const cardElement = favoritesTab.querySelector(`.card button[data-event-id="${eventId}"]`)?.closest('.col-md-6');
                    if (cardElement) {
                        cardElement.remove();

                        if (favoritesTab.querySelectorAll('.card').length === 0) {
                            favoritesTab.querySelector('.row').innerHTML = `
                                <div class="alert alert-info col-12">
                                    <i class="fas fa-info-circle me-2"></i> You haven't saved any favorites yet. Browse the "All Events" tab to find events you're interested in.
                                </div>
                            `;
                        }
                    }
                }
            } else {
                console.error('Error updating favorites:', data.message);

                if (!isFavorite) {
                    this.classList.remove('active');
                    this.innerHTML = '<i class="far fa-heart"></i>';
                } else {
                    this.classList.add('active');
                    this.innerHTML = '<i class="fas fa-heart"></i>';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);

            if (!isFavorite) {
                this.classList.remove('active');
                this.innerHTML = '<i class="far fa-heart"></i>';
            } else {
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-heart"></i>';
            }
        });
    });
});

document.querySelectorAll('.add-to-schedule').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const eventId = this.getAttribute('data-event-id');

        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${eventId}&action=add`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Event ${eventId} added to schedule`);

                this.innerHTML = '<i class="fas fa-check me-1"></i> Added';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-success');
                this.disabled = true;
            } else {
                console.error('Error updating schedule:', data.message);
                alert('Error adding event to schedule. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding event to schedule. Please try again.');
        });
    });
});

document.querySelectorAll('.remove-from-schedule').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const eventId = this.getAttribute('data-event-id');
        const cardElement = this.closest('.col-md-6');

        fetch('update_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${eventId}&action=remove`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Event ${eventId} removed from schedule`);
                cardElement.remove();

                const scheduledEventsTab = document.getElementById('scheduled-events');

                if (scheduledEventsTab.querySelectorAll('.card').length === 0) {
                    scheduledEventsTab.querySelector('.row').innerHTML = `
                        <div class="alert alert-info col-12">
                            <i class="fas fa-info-circle me-2"></i> You haven't scheduled any events yet. Browse the "All Events" tab to find events you'd like to attend.
                        </div>
                    `;
                }
            } else {
                console.error('Error updating schedule:', data.message);
                alert('Error removing event from schedule. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing event from schedule. Please try again.');
        });
    });
});

document.querySelectorAll('.remove-from-favorites').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const eventId = this.getAttribute('data-event-id');
        const cardElement = this.closest('.col-md-6');

        fetch('update_favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `event_id=${eventId}&action=remove`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Event ${eventId} removed from favorites`);
                cardElement.remove();

                const allEventsTab = document.getElementById('all-events');
                const heartButton = allEventsTab.querySelector(`.btn-favorite[data-event-id="${eventId}"]`);
                if (heartButton) {
                    heartButton.classList.remove('active');
                    heartButton.innerHTML = '<i class="far fa-heart"></i>';
                }

                const myEventsTab = document.getElementById('my-events');
                if (myEventsTab.querySelectorAll('.card').length === 0) {
                    myEventsTab.querySelector('.row').innerHTML = `
                        <div class="alert alert-info col-12">
                            <i class="fas fa-info-circle me-2"></i> You haven't saved any favorites yet. Browse the "All Events" tab to find events you're interested in.
                        </div>
                    `;
                }
            } else {
                console.error('Error updating favorites:', data.message);
                alert('Error removing event from favorites. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing event from favorites. Please try again.');
        });
    });
});
</script>

<?php
require_once 'footer.php';
?>