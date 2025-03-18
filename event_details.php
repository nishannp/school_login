<?php
session_start();
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$event_id = intval($_GET['id']);  

$event_query = "SELECT 
    e.event_id, 
    e.title, 
    e.description, 
    DATE_FORMAT(e.event_date, '%Y-%m-%d') as event_date, 
    DATE_FORMAT(e.start_time, '%H:%i') as start_time, 
    DATE_FORMAT(e.end_time, '%H:%i') as end_time, 
    CONCAT(DATE_FORMAT(e.event_date, '%W, %M %e, %Y'), ' ', DATE_FORMAT(e.start_time, '%l:%i %p'), ' - ', DATE_FORMAT(e.end_time, '%l:%i %p')) as formatted_date_time,
    e.location, 
    e.latitude,
    e.longitude,
    e.speakers,
    e.department,
    e.topic,
    e.tag,
    e.estimated_participants,
    COALESCE(e.event_image_resized, e.event_image_original, 'https://via.placeholder.com/800x400') as image,
    a.username as organizer_name,  -- Corrected column names
    a.email as organizer_email     -- Corrected column names
FROM events e
LEFT JOIN admins a ON e.admin_id = a.admin_id  -- Use LEFT JOIN
WHERE e.event_id = ?";

$event_stmt = $conn->prepare($event_query);
if (!$event_stmt) {
    die("Prepare failed: " . $conn->error); 
}
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();

if ($event_result->num_rows === 0) {

    header("Location: events.php");
    exit;
}

$event = $event_result->fetch_assoc();
$event_stmt->close();

$start = new DateTime($event['event_date'] . ' ' . $event['start_time']);
$end = new DateTime($event['event_date'] . ' ' . $event['end_time']);
$interval = $start->diff($end);
$duration = '';
if ($interval->h > 0) {
    $duration .= $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
}
if ($interval->i > 0) {
    if ($duration) $duration .= ' and ';
    $duration .= $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
}

$speakers_array = [];
if (!empty($event['speakers'])) {
    $speakers_array = explode(',', $event['speakers']);
}

$is_favorite = false;
$favorite_query = "SELECT 1 FROM user_favorites WHERE user_id = ? AND event_id = ? LIMIT 1";
$favorite_stmt = $conn->prepare($favorite_query);
$favorite_stmt->bind_param("ii", $user_id, $event_id);
$favorite_stmt->execute();
if ($favorite_stmt->get_result()->num_rows > 0) {
    $is_favorite = true;
}
$favorite_stmt->close();

$is_scheduled = false;
$schedule_query = "SELECT 1 FROM user_schedules WHERE user_id = ? AND event_id = ? LIMIT 1";
$schedule_stmt = $conn->prepare($schedule_query);
$schedule_stmt->bind_param("ii", $user_id, $event_id);
$schedule_stmt->execute();
if ($schedule_stmt->get_result()->num_rows > 0) {
    $is_scheduled = true;
}
$schedule_stmt->close();

$similar_events = [];
$similar_query = "SELECT 
    e.event_id, 
    e.title,
    DATE_FORMAT(e.event_date, '%Y-%m-%d') as event_date,
    DATE_FORMAT(e.start_time, '%H:%i') as start_time,
    CONCAT(DATE_FORMAT(e.event_date, '%a, %b %e'), ' ', DATE_FORMAT(e.start_time, '%l:%i %p')) as short_date_time,
    e.location,
    COALESCE(e.event_image_resized, e.event_image_original, 'https://via.placeholder.com/350x200') as image
FROM events e
WHERE (e.department = ? OR e.topic = ?) 
AND e.event_id != ? 
AND e.event_date >= CURDATE()  -- Only future events
ORDER BY e.event_date
LIMIT 3";

$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->bind_param("ssi", $event['department'], $event['topic'], $event_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
while ($row = $similar_result->fetch_assoc()) {
    $similar_events[] = $row;
}
$similar_stmt->close();

$attendee_query = "SELECT COUNT(*) as count FROM user_schedules WHERE event_id = ?";
$attendee_stmt = $conn->prepare($attendee_query);
$attendee_stmt->bind_param("i", $event_id);
$attendee_stmt->execute();
$attendee_result = $attendee_stmt->get_result();
$attendee_count = $attendee_result->fetch_assoc()['count'];
$attendee_stmt->close();

$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();  
$user_stmt->close();

$formatted_description = nl2br(htmlspecialchars($event['description']));

?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($event['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(strip_tags(substr($event['description'], 0, 200))) . '...'; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($event['image']); ?>">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    <?php if ($event['latitude'] && $event['longitude']): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <?php endif; ?>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --card-bg-color: #fff;
            --card-border-color: #dee2e6;
            --card-header-bg-color: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
            color: #333;
        }

        .breadcrumb {
            background-color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--secondary-color);
        }

        .event-header {
            position: relative;
            height: 400px;
            overflow: hidden;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .event-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
            color: white;
        }

        .event-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            font-size: 1rem;
        }

        .event-meta-item i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-right: 0.5rem;
            text-shadow: none;
        }

        .badge-department {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-topic {
            background-color: var(--success-color);
            color: white;
        }

        .badge-tag {
            background-color: var(--warning-color);
            color: black;
        }

        .event-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-favorite {
            background-color: white;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .btn-favorite:hover {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-favorite.active {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-schedule {
            background-color: white;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .btn-schedule:hover {
            background-color: var(--success-color);
            color: white;
        }

        .btn-schedule.active {
            background-color: var(--success-color);
            color: white;
        }

        .btn-share {
            background-color: white;
            color: var(--info-color);
            border: 1px solid var(--info-color);
        }

        .btn-share:hover {
            background-color: var(--info-color);
            color: white;
        }

        .content-section {
            background-color: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .event-description {
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 1.5rem;
            color: #555;
        }

        .details-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .details-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .details-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(0, 123, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .details-content {
            flex-grow: 1;
        }

        .details-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #555;
        }

        .details-value {
            color: #333;
        }

        #event-map {
            height: 300px; 
            width: 100%;  
            border-radius: 0.5rem;
            margin-top: 1rem;

            z-index: 1;
        }

        .speakers-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .speaker-card {
            background: linear-gradient(135deg, #f0f4f8, #eaf0f7);
            border-radius: 0.5rem;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            flex: 1;
            min-width: 200px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .speaker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .speaker-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .similar-events-container {

            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;

        }

        .similar-event-card {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .similar-event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }

        .similar-event-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .similar-event-body {
            padding: 1rem;
        }

        .similar-event-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .similar-event-meta {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
        }

        .similar-event-meta i {
            margin-right: 0.3rem;
            color: var(--primary-color);
        }

        .btn-details {
            display: block;
            width: 100%;
            text-align: center;
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .btn-details:hover {
            background-color: var(--primary-hover);
            color: white;
        }

        .attendance-stats {
            background: linear-gradient(135deg, #f0f6ff, #e6f0ff);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .share-options {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .share-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .share-option:hover {
            background-color: #f0f0f0;
        }

        .share-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .share-facebook { color: #3b5998; }
        .share-twitter { color: #1da1f2; }
        .share-email { color: #d44638; }
        .share-whatsapp { color: #25d366; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @media (max-width: 768px) {
            .event-title {
                font-size: 1.8rem;
            }

            .event-header {
                height: 300px;
            }

            .event-meta {
                gap: 1rem;
            }

            .event-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .content-section {
                padding: 1.5rem;
            }

            .similar-events-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <?php include 'header.php'; ?>
    <div class="container mt-4 fade-in">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="events.php">Events</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($event['title']); ?></li>
            </ol>
        </nav>

        <!-- Event Header with Cover Image -->
        <div class="event-header">
            <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
            <div class="event-header-overlay">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <div class="event-meta">
                    <div class="event-meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo htmlspecialchars($event['formatted_date_time']); ?></span>
                    </div>
                    <div class="event-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    <div class="event-meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $duration; ?></span>
                    </div>
                </div>
                <div>
                    <?php if (!empty($event['department'])): ?>
                        <span class="badge badge-department"><?php echo htmlspecialchars($event['department']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($event['topic'])): ?>
                        <span class="badge badge-topic"><?php echo htmlspecialchars($event['topic']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($event['tag'])): ?>
                        <span class="badge badge-tag"><?php echo htmlspecialchars($event['tag']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="event-actions">
                    <button id="btnFavorite" class="btn-action btn-favorite <?php echo $is_favorite ? 'active' : ''; ?>" data-event-id="<?php echo $event['event_id']; ?>">
                        <i class="fas <?php echo $is_favorite ? 'fa-heart' : 'fa-heart'; ?>"></i>
                        <span><?php echo $is_favorite ? 'Remove from Favorites' : 'Add to Favorites'; ?></span>
                    </button>
                    <button id="btnSchedule" class="btn-action btn-schedule <?php echo $is_scheduled ? 'active' : ''; ?>" data-event-id="<?php echo $event['event_id']; ?>">
                        <i class="fas <?php echo $is_scheduled ? 'fa-check' : 'fa-plus'; ?>"></i>
                        <span><?php echo $is_scheduled ? 'Added to Schedule' : 'Add to Schedule'; ?></span>
                    </button>
                    <button class="btn-action btn-share" data-bs-toggle="modal" data-bs-target="#shareModal">
                        <i class="fas fa-share-alt"></i>
                        <span>Share Event</span>
                    </button>
                </div>
             </div>
           </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Event Description -->
                <div class="content-section">
                    <h2 class="section-title">About this Event</h2>
                    <div class="event-description">
                        <?php echo $formatted_description; ?>
                    </div>
                </div>

                <!-- Event Location with Map -->
                <div class="content-section">
                    <h2 class="section-title">Location</h2>
                    <p class="mb-3"><?php echo htmlspecialchars($event['location']); ?></p>

                    <?php if ($event['latitude'] && $event['longitude']): ?>
                    <div id="event-map"></div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Map location information not available.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Speakers Section -->
                <?php if (!empty($speakers_array)): ?>
                <div class="content-section">
                    <h2 class="section-title">Speakers</h2>
                    <div class="speakers-container">
                        <?php foreach ($speakers_array as $speaker): ?>
                        <div class="speaker-card">
                            <h3 class="speaker-name"><?php echo htmlspecialchars(trim($speaker)); ?></h3>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Event Details -->
                <div class="content-section">
                    <h2 class="section-title">Event Details</h2>
                    <ul class="details-list">
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="far fa-calendar"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Date & Time</div>
                                <div class="details-value"><?php echo htmlspecialchars($event['formatted_date_time']); ?></div>
                            </div>
                        </li>
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Location</div>
                                <div class="details-value"><?php echo htmlspecialchars($event['location']); ?></div>
                            </div>
                        </li>
                        <?php if (!empty($event['department'])): ?>
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Department</div>
                                <div class="details-value"><?php echo htmlspecialchars($event['department']); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($event['topic'])): ?>
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Topic</div>
                                <div class="details-value"><?php echo htmlspecialchars($event['topic']); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Organizer</div>
                                <div class="details-value"><?php echo htmlspecialchars($event['organizer_name'] ?? 'Campus Connect'); ?></div>
                            </div>
                        </li>
                        <li class="details-item">
                            <div class="details-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="details-content">
                                <div class="details-label">Contact</div>
                                <div class="details-value">
                                    <a href="mailto:<?php echo htmlspecialchars($event['organizer_email'] ?? 'contact@campusconnect.com'); ?>">
                                        <?php echo htmlspecialchars($event['organizer_email'] ?? 'contact@campusconnect.com'); ?>
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Attendance Stats -->
                    <div class="attendance-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $attendee_count; ?></span>
                            <span class="stat-label">Going</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $event['estimated_participants']; ?></span>
                            <span class="stat-label">Expected</span>
                        </div>
                    </div>
                </div>

                <!-- Similar Events -->
                <?php if (!empty($similar_events)): ?>
                <div class="content-section">
                    <h2 class="section-title">Similar Events</h2>
                    <div class="similar-events-container">
                        <?php foreach ($similar_events as $similar): ?>
                            <div class="similar-event-card">
                            <img src="<?php echo htmlspecialchars($similar['image']); ?>" alt="Image of <?php echo htmlspecialchars($similar['title']); ?>" class="similar-event-img">
                            <div class="similar-event-body">
                                <h5 class="similar-event-title"><?php echo htmlspecialchars($similar['title']); ?></h5>
                                <div class="similar-event-meta">
                                    <div><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($similar['short_date_time']); ?></div>
                                    <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($similar['location']); ?></div>
                                </div>
                                <a href="event_details.php?id=<?php echo $similar['event_id']; ?>" class="btn-details">View Details</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share this Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Share this event with your friends and colleagues:</p>
                    <div class="mb-3">
                        <label for="shareUrl" class="form-label">Event URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" value="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyLinkBtn">Copy</button>
                        </div>
                    </div>
                    <div class="share-options">
                        <a href="<?php 
                            $fb_share_url = 'https://www.facebook.com/dialog/share';
                            $params = http_build_query([
                                'app_id' => '994903035830603',
                                'display' => 'popup',
                                'href' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                                'quote' => $event['title'] . ' - ' . $event['formatted_date_time'] . ' at ' . $event['location'],
                                'hashtag' => '#CampusEvent'
                            ]);
                            echo $fb_share_url . '?' . $params;
                        ?>" 
                        target="_blank"
                        class="share-option"
                        onclick="window.open(this.href, 'facebook-share', 'width=580,height=580'); return false;">
                            <i class="fab fa-facebook share-icon share-facebook"></i>
                            <span>Facebook</span>
                        </a>
                      
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Check out this event: ' . $event['title']); ?>&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-option">
                            <i class="fab fa-twitter share-icon share-twitter"></i>
                            <span>Twitter</span>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode('Check out this event: ' . $event['title']); ?>&body=<?php echo urlencode('I thought you might be interested in this event: ' . $event['title'] . '\n\n' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-option">
                            <i class="fas fa-envelope share-icon share-email"></i>
                            <span>Email</span>
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Check out this event: ' . $event['title'] . ' - ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-option">
                            <i class="fab fa-whatsapp share-icon share-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    <!--
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

                        -->
      <?php if ($event['latitude'] && $event['longitude']): ?>
    <script>
       document.addEventListener('DOMContentLoaded', function() {

            const mapElement = document.getElementById('event-map');
            if (mapElement) {
                const map = L.map('event-map').setView([<?php echo $event['latitude']; ?>, <?php echo $event['longitude']; ?>], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([<?php echo $event['latitude']; ?>, <?php echo $event['longitude']; ?>])
                    .addTo(map)
                    .bindPopup('<?php echo htmlspecialchars($event['location']); ?>')
                    .openPopup();
            }
        });
    </script>
     <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const btnFavorite = document.getElementById('btnFavorite');
            if (btnFavorite) {
                btnFavorite.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    const isFavorite = this.classList.contains('active');

                    fetch('update_favorites.php', {  
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'event_id=' + eventId + '&action=' + (isFavorite ? 'remove' : 'add')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (isFavorite) {
                                btnFavorite.classList.remove('active');
                                btnFavorite.querySelector('span').textContent = 'Add to Favorites';
                            } else {
                                btnFavorite.classList.add('active');
                                btnFavorite.querySelector('span').textContent = 'Remove from Favorites';
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }

            const btnSchedule = document.getElementById('btnSchedule');
            if (btnSchedule) {
                btnSchedule.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    const isScheduled = this.classList.contains('active');

                    fetch('update_schedule.php', {  
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'event_id=' + eventId + '&action=' + (isScheduled ? 'remove' : 'add')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (isScheduled) {
                                btnSchedule.classList.remove('active');
                                btnSchedule.querySelector('i').classList.remove('fa-check');
                                btnSchedule.querySelector('i').classList.add('fa-plus');
                                btnSchedule.querySelector('span').textContent = 'Add to Schedule';
                            } else {
                                btnSchedule.classList.add('active');
                                btnSchedule.querySelector('i').classList.remove('fa-plus');
                                btnSchedule.querySelector('i').classList.add('fa-check');
                                btnSchedule.querySelector('span').textContent = 'Added to Schedule';
                            }
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }

            const copyLinkBtn = document.getElementById('copyLinkBtn');
            if (copyLinkBtn) {
                copyLinkBtn.addEventListener('click', function() {
                    const shareUrl = document.getElementById('shareUrl');
                    shareUrl.select();
                    document.execCommand('copy');

                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            }
        });
    </script>