<?php
session_start();
require_once 'config.php';

// Enable error reporting (TEMPORARY - remove in production!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's preferences (if any) -  Create this table if you haven't!
$user_prefs_query = "SELECT default_map_location, default_map_zoom FROM user_preferences WHERE user_id = ?";
$user_prefs_stmt = $conn->prepare($user_prefs_query);
if ($user_prefs_stmt) { // Check if prepare was successful
    $user_prefs_stmt->bind_param("i", $user_id);
    $user_prefs_stmt->execute();
    $user_prefs_result = $user_prefs_stmt->get_result();
    $user_prefs = $user_prefs_result->fetch_assoc();
    $user_prefs_stmt->close();
} else {
    // Handle prepare failure (e.g., log the error)
    error_log("Prepare failed for user_prefs_query: " . $conn->error);
    $user_prefs = null; // Set to null to avoid undefined variable errors
}

// Fetch all events with location data, sorted by proximity
$events_query = "SELECT 
    e.event_id,
    e.title,
    e.description,
    e.location,
    e.latitude,
    e.longitude,
    e.event_date,
    e.start_time,
    e.end_time,
    COALESCE(e.event_image_resized, e.event_image_original, 'images/default-event.jpg') as image_url,
    e.department AS category_name,
    ABS(DATEDIFF(e.event_date, CURDATE())) as date_proximity,
    (SELECT COUNT(*) FROM user_schedules WHERE event_id = e.event_id) as attendee_count,
    (SELECT COUNT(*) FROM user_favorites WHERE event_id = e.event_id) as favorite_count
FROM events e
WHERE e.latitude IS NOT NULL 
    AND e.longitude IS NOT NULL 
    AND e.event_date >= CURDATE()
ORDER BY date_proximity ASC, e.start_time ASC";

$events_stmt = $conn->prepare($events_query);
if(!$events_stmt) {
    die("Prepare failed: " . $conn->error); //Crucial for debugging
}
$events_stmt->execute();
$events_result = $events_stmt->get_result();

$all_events = [];
$events_by_location = [];
$categories = [];
$upcoming_events = [];

while ($row = $events_result->fetch_assoc()) {
    $all_events[] = $row;

    $location_key = $row['latitude'] . ',' . $row['longitude'];
    if (!isset($events_by_location[$location_key])) {
        $events_by_location[$location_key] = [
            'location' => $row['location'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'events' => []
        ];
    }
    $events_by_location[$location_key]['events'][] = [
        'event_id' => $row['event_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'date_time' => date('M d, Y', strtotime($row['event_date'])) . ' at ' . date('g:i A', strtotime($row['start_time'])),
        'category' => $row['category_name'],
        'attendee_count' => $row['attendee_count'],
        'image_url' => $row['image_url'] ?: 'images/default-event.jpg' // Fallback
    ];
        // Collect unique categories for filter (using department as category)
    if (!in_array($row['category_name'], $categories) && $row['category_name']) {
        $categories[] = $row['category_name'];
    }

    // Collect upcoming events for sidebar (next 7 days) -  Correct date comparison
    $event_date = new DateTime($row['event_date']);
    $current_date = new DateTime();
    $interval = $current_date->diff($event_date);
    if ($interval->days <= 7 && $current_date <= $event_date) { // Also check if event_date is in the future
        $upcoming_events[] = $row;
    }
}
$events_stmt->close();

// Calculate map center and default zoom
$center_latitude = 37.7749;  // Default: San Francisco
$center_longitude = -122.4194;
$default_zoom = 13;

if (count($events_by_location) > 0) {
    $latitude_sum = 0;
    $longitude_sum = 0;
    foreach ($events_by_location as $location) {
        $latitude_sum += $location['latitude'];
        $longitude_sum += $location['longitude'];
    }
    $center_latitude = $latitude_sum / count($events_by_location);
    $center_longitude = $longitude_sum / count($events_by_location);
}

// User preferences override
if ($user_prefs && $user_prefs['default_map_location']) {
    list($center_latitude, $center_longitude) = explode(',', $user_prefs['default_map_location']);
    $default_zoom = $user_prefs['default_map_zoom'] ?: 13; // Use 13 as default if not set
}

// Get a random event for initial focus
$random_event = null;
if (!empty($all_events)) {
    $random_event = $all_events[array_rand($all_events)];
}

// Get user info (for header)
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Maps - Campus Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>

    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --secondary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            padding-bottom: 2rem;
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

        .map-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .map-container {
            flex: 1;
            min-width: 300px;
            position: relative;
            height: 500px !important;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .events-sidebar {
            width: 350px;
            max-height: 600px;
            overflow-y: auto;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 1rem;
        }

        #campus-map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .map-filters {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .filter-btn {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            border: 1px solid var(--secondary-color);
            background-color: white;
            transition: all 0.2s ease;
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-btn:hover:not(.active) {
            background-color: #f0f0f0;
        }

        .search-box {
            margin-bottom: 10px;
        }

        .event-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .event-item:last-child {
            border-bottom: none;
        }

        .event-item:hover {
            background-color: #f5f5f5;
        }

        .event-item h5 {
            margin: 0;
            font-size: 1rem;
            color: var(--primary-color);
        }

        .event-meta {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 5px;
        }

        .event-meta i {
            margin-right: 5px;
            width: 14px;
            text-align: center;
        }

        .category-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            background-color: #e9ecef;
            color: var(--dark-color);
            margin-right: 5px;
        }

        .leaflet-popup-content-wrapper {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            max-width: 300px;
            overflow: hidden;
        }

        .event-popup {
            padding: 0;
        }

        .event-popup-image {
            width: 100%;
            height: 120px;
            background-size: cover;
            background-position: center;
            margin-bottom: 10px;
        }

        .event-popup-content {
            padding: 10px 15px;
        }

        .event-popup h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .event-popup p {
            font-size: 13px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .event-popup-details {
            font-size: 12px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .event-popup-details i {
            margin-right: 5px;
            width: 14px;
            text-align: center;
        }

        .event-popup-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .event-popup-btn {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
        }

        .btn-details {
            background-color: var(--primary-color);
            color: white;
            flex-grow: 1;
        }

        .btn-details:hover {
            background-color: var(--primary-hover);
            color: white;
        }

        .no-events-message {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255,255,255,0.9);
            padding: 2rem;
            border-radius: .5rem;
            z-index: 2;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0, 0.3);
            font-weight: 600;
            color: #777;
        }
        .marker-cluster {
            background-color: rgba(0, 123, 255, 0.6);
        }
        .marker-cluster div {
            background-color: rgba(0, 123, 255, 0.8);
            color: white;
            font-weight: bold;
        }

        .save-preference {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 999;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none;
        }
        /* Custom markers */
        .custom-marker {
            text-align: center;
            color: white;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Add some animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 1.5s infinite;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .map-section {
                flex-direction: column;
            }

            .events-sidebar {
                width: 100%;
                max-height: 300px;
            }

            .map-container {
                height: 400px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-4 main-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Campus Maps</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-map-marked-alt me-2"></i> Interactive Campus Event Map</h1>
            <div>
                <button id="locate-me" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-location-arrow me-1"></i> Find My Location
                </button>
                <button id="reset-view" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-redo me-1"></i> Reset View
                </button>
            </div>
        </div>

        <!-- Map Filters -->
        <div class="map-filters">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group search-box">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="event-search" class="form-control" placeholder="Search events by name or location...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="filter-row">
                        <span class="me-2">Categories:</span>
                        <button class="filter-btn active" data-category="all">All</button>
                        <?php foreach ($categories as $category): ?>
                            <button class="filter-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="filter-row">
                        <span class="me-2">Time:</span>
                        <button class="filter-btn active" data-time="all">All</button>
                        <button class="filter-btn" data-time="today">Today</button>
                        <button class="filter-btn" data-time="week">This Week</button>
                        <button class="filter-btn" data-time="month">This Month</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section with Sidebar -->
        <div class="map-section">
            <div class="map-container">
                <div id="campus-map"></div>
                <?php if (empty($all_events)): ?>
                    <div class="no-events-message">
                        <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                        <p>No upcoming events with location data found.</p>
                        <a href="create-event.php" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus-circle me-1"></i> Create an Event
                        </a>
                    </div>
                <?php endif; ?>

                <div class="save-preference">
                    <p class="mb-2">Save this view as your default?</p>
                    <button id="save-view" class="btn btn-sm btn-primary">Save</button>
                    <button id="cancel-save" class="btn btn-sm btn-outline-secondary ms-2">Cancel</button>
                </div>
            </div>

            <div class="events-sidebar">
                <h4><i class="fas fa-calendar-day me-2"></i>Upcoming Events</h4>
                <p class="small text-muted mb-3">Click on an event to locate it on the map</p>

                <div id="events-list">
                    <?php if (empty($upcoming_events)): ?>
                        <p class="text-center text-muted my-4">No upcoming events in the next 7 days</p>
                    <?php else: ?>
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-item" data-event-id="<?php echo $event['event_id']; ?>"
                                data-lat="<?php echo $event['latitude']; ?>"
                                data-lng="<?php echo $event['longitude']; ?>">
                                <h5><?php echo htmlspecialchars($event['title']); ?></h5>
                                <div class="event-meta">
                                    <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                    <div><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                                    <div><i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])); ?></div>
                                    <?php if ($event['category_name']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($event['category_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>

  

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            const map = L.map('campus-map').setView([<?php echo $center_latitude; ?>, <?php echo $center_longitude; ?>], <?php echo $default_zoom; ?>);

            // Use a better map tile provider
            L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles style by <a href="https://www.hotosm.org/" target="_blank">HOT</a>',
                maxZoom: 19
            }).addTo(map);

            // Add location control
            L.control.locate({
                position: 'topright',
                strings: {
                    title: "Show me where I am"
                },
                locateOptions: {
                    enableHighAccuracy: true
                }
            }).addTo(map);

            // Create marker clusters
            const markers = L.markerClusterGroup({
                disableClusteringAtZoom: 16,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true
            });

            // Store all markers for filtering
            const allMarkers = [];

            // Create custom marker icons based on categories
            function createCustomIcon(category) {
                const colors = {
                    'Academic': '#007bff',
                    'Social': '#28a745',
                    'Sports': '#dc3545',
                    'Arts': '#6610f2',
                    'Workshop': '#fd7e14',
                    'Default': '#6c757d'
                };

                const color = colors[category] || colors['Default'];

                return L.divIcon({
                    className: 'custom-marker',
                    html: `<i class="fas fa-map-marker-alt fa-2x" style="color: ${color};"></i>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 30]
                });
            }

            // 1. Build a JavaScript array of marker data in PHP
            const markerData = [];
            <?php foreach ($events_by_location as $location_data): ?>
                <?php
                $location_categories = array_map(function($event) {
                    return $event['category'];
                }, $location_data['events']);
                $primary_category = !empty($location_categories) && $location_categories[0] ? $location_categories[0] : 'Default';

                // Build popup content (same as before)
                $popup_content = "<div class=\"event-popup\">";
                foreach ($location_data['events'] as $index => $event) {
                    if ($index === 0) {
                        $popup_content .= "<div class=\"event-popup-image\" style=\"background-image: url('" . $event['image_url'] . "')\"></div>";
                        $popup_content .= "<div class=\"event-popup-content\">";
                        $popup_content .= "<h3>" . addslashes(htmlspecialchars($event['title'])) . "</h3>";
                        $popup_content .= "<p class=\"event-popup-details\"><i class=\"fas fa-map-marker-alt\"></i> " . addslashes(htmlspecialchars($location_data['location'])) . "</p>";
                        $popup_content .= "<p class=\"event-popup-details\"><i class=\"far fa-calendar-alt\"></i> " . addslashes(htmlspecialchars($event['date_time'])) . "</p>";
                        if ($event['category']) {
                            $popup_content .= "<p class=\"event-popup-details\"><i class=\"fas fa-tag\"></i> " . addslashes(htmlspecialchars($event['category'])) . "</p>";
                        }
                        $popup_content .= "<p class=\"event-popup-details\"><i class=\"fas fa-users\"></i> " . $event['attendee_count'] . " attending</p>";
                        $popup_content .= "<div class=\"event-popup-actions\"><a href=\"event_details.php?id=" . $event['event_id'] . "\" class=\"event-popup-btn btn-details\">View Details</a></div>";
                        $popup_content .= "</div>";  // Close event-popup-content
                    }
                }
                if (count($location_data['events']) > 1) {
                    $popup_content .= "<div class=\"p-2 bg-light border-top\"><small>" . count($location_data['events']) . " events at this location</small><div class=\"d-flex mt-1\">";
                    foreach ($location_data['events'] as $index => $event) {
                         $popup_content .= "<a href=\"event_details.php?id=" . $event['event_id'] . "\" class=\"badge bg-primary me-1 text-decoration-none\">" . ($index + 1) . "</a>";
                    }
                    $popup_content .= "</div></div>"; // Close d-flex and p-2
                }
                 $popup_content .= "</div>"; // Close event-popup

                ?>

                markerData.push({
                    lat: <?php echo $location_data['latitude']; ?>,
                    lng: <?php echo $location_data['longitude']; ?>,
                    popupContent: <?php echo json_encode($popup_content); ?>, // Use JSON.stringify!
                    category: <?php echo json_encode($primary_category);?>,
                    eventData: {
                         location: <?php echo json_encode(htmlspecialchars($location_data['location'])); ?>,
                         events: <?php echo json_encode($location_data['events']); ?>,
                         categories: <?php echo json_encode($location_categories);?>
                    }
                });
            <?php endforeach; ?>

            // 2.  Create the Leaflet markers in JavaScript, looping through markerData
            markerData.forEach(data => {
                const marker = L.marker([data.lat, data.lng], { icon: createCustomIcon(data.category) })
                    .bindPopup(data.popupContent, { maxWidth: 300 });

                marker.eventData = data.eventData; // Store event data
                allMarkers.push(marker);
                markers.addLayer(marker);
            });


            // Add markers to map (no longer inside the PHP loop)
            map.addLayer(markers);

            // ... (The rest of your JavaScript code for event handlers, filters, etc., remains the same) ...
            // If we have a random event, zoom to it on load with animation
           <?php if ($random_event): ?>
               setTimeout(function() {
                   map.flyTo([<?php echo $random_event['latitude']; ?>, <?php echo $random_event['longitude']; ?>], 16, {
                       animate: true,
                       duration: 1.5
                   });

                   // Find and open the popup for this event.  Loop through allMarkers.
                   allMarkers.forEach(marker => {
                        if (marker.getLatLng().lat === <?php echo $random_event['latitude']; ?> &&
                           marker.getLatLng().lng === <?php echo $random_event['longitude']; ?>) {
                           setTimeout(() => {
                               marker.openPopup();
                           }, 1600);  // Delay to ensure map is ready
                       }
                   });
               }, 1000); // Delay initial flyTo
           <?php endif; ?>

           // Event handlers
           // Click on event in sidebar
           document.querySelectorAll('.event-item').forEach(item => {
               item.addEventListener('click', function() {
                   const lat = parseFloat(this.dataset.lat);
                   const lng = parseFloat(this.dataset.lng);

                   map.flyTo([lat, lng], 17, {
                       animate: true,
                       duration: 1
                   });

                   // Find and open the popup for this location
                   allMarkers.forEach(marker => {
                       if (marker.getLatLng().lat === lat && marker.getLatLng().lng === lng) {
                           setTimeout(() => {
                               marker.openPopup();
                           }, 1100);
                       }
                   });

                   // Highlight the clicked item
                   document.querySelectorAll('.event-item').forEach(el => {
                       el.classList.remove('bg-light');
                   });
                   this.classList.add('bg-light');
               });
           });

           // Filter buttons
           document.querySelectorAll('.filter-btn').forEach(btn => {
               btn.addEventListener('click', function() {
                   // Update UI for button group
                   const siblings = Array.from(this.parentElement.children).filter(el => el.classList.contains('filter-btn'));
                   siblings.forEach(el => el.classList.remove('active'));
                   this.classList.add('active');

                   // Apply filters
                   applyFilters();
               });
           });

     // Search events
     document.getElementById('event-search').addEventListener('input', function() {
               applyFilters();
           });

           // Function to apply all active filters
           function applyFilters() {
               const searchTerm = document.getElementById('event-search').value.toLowerCase();
               const categoryFilter = document.querySelector('.filter-btn[data-category].active').dataset.category;
               const timeFilter = document.querySelector('.filter-btn[data-time].active').dataset.time;

               // Clear all markers and events list
               markers.clearLayers();
               const eventsList = document.getElementById('events-list');
               eventsList.innerHTML = '';

               // Get current date for time filtering
               const now = new Date();
               const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
               const oneWeek = new Date(today);
               oneWeek.setDate(oneWeek.getDate() + 7);
               const oneMonth = new Date(today);
               oneMonth.setMonth(oneMonth.getMonth() + 1);

               // Filter and add markers back
               let visibleEvents = [];

               allMarkers.forEach(marker => {
                   let shouldShow = false;

                   // Process each event at this location
                   marker.eventData.events.forEach(event => {
                       // Parse the event date
                       const dateParts = event.date_time.split(' at ')[0];
                       const eventDate = new Date(dateParts);

                       // Check if the event matches all filters
                       const matchesSearch =
                           event.title.toLowerCase().includes(searchTerm) ||
                           marker.eventData.location.toLowerCase().includes(searchTerm);

                       const matchesCategory =
                           categoryFilter === 'all' ||
                           marker.eventData.categories.includes(categoryFilter);

                       let matchesTime = true;
                       if (timeFilter === 'today') {
                           matchesTime = eventDate.toDateString() === today.toDateString();
                       } else if (timeFilter === 'week') {
                           matchesTime = eventDate >= today && eventDate <= oneWeek;
                       } else if (timeFilter === 'month') {
                           matchesTime = eventDate >= today && eventDate <= oneMonth;
                       }

                       if (matchesSearch && matchesCategory && matchesTime) {
                           shouldShow = true;
                           visibleEvents.push({
                               ...event,
                               location: marker.eventData.location,
                               latitude: marker.getLatLng().lat,
                               longitude: marker.getLatLng().lng
                           });
                       }
                   });

                   if (shouldShow) {
                       markers.addLayer(marker);
                   }
               });

               // Update events sidebar with filtered events
               if (visibleEvents.length > 0) {
                   // Sort events by date
                   visibleEvents.sort((a, b) => {
                       const dateA = new Date(a.date_time.split(' at ')[0]);
                       const dateB = new Date(b.date_time.split(' at ')[0]);
                       return dateA - dateB;
                   });

                   visibleEvents.forEach(event => {
                       const dateParts = event.date_time.split(' at ');
                       const eventDate = dateParts[0];
                       const eventTime = dateParts[1];

                       const eventItem = document.createElement('div');
                       eventItem.className = 'event-item';
                       eventItem.dataset.eventId = event.event_id;
                       eventItem.dataset.lat = event.latitude;
                       eventItem.dataset.lng = event.longitude;

                       eventItem.innerHTML = `
                           <h5>${event.title}</h5>
                           <div class="event-meta">
                               <div><i class="fas fa-map-marker-alt"></i> ${event.location}</div>
                               <div><i class="far fa-calendar"></i> ${eventDate}</div>
                               <div><i class="far fa-clock"></i> ${eventTime}</div>
                               ${event.category ? `<span class="category-badge">${event.category}</span>` : ''}
                           </div>
                       `;

                       eventItem.addEventListener('click', function() {
                           const lat = parseFloat(this.dataset.lat);
                           const lng = parseFloat(this.dataset.lng);

                           map.flyTo([lat, lng], 17, {
                               animate: true,
                               duration: 1
                           });

                           // Find and open the popup for this location
                           allMarkers.forEach(marker => {
                               if (marker.getLatLng().lat === lat && marker.getLatLng().lng === lng) {
                                   setTimeout(() => {
                                       marker.openPopup();
                                   }, 1100);
                               }
                           });

                           // Highlight the clicked item
                           document.querySelectorAll('.event-item').forEach(el => {
                               el.classList.remove('bg-light');
                           });
                           this.classList.add('bg-light');
                       });

                       eventsList.appendChild(eventItem);
                   });
               } else {
                   // No events match filters
                   eventsList.innerHTML = `
                       <p class="text-center text-muted my-4">
                           <i class="fas fa-search me-2"></i>
                           No events match your filters
                       </p>
                   `;
               }

               // If no markers visible, adjust map view
               if (markers.getLayers().length === 0) {
                   map.setView([<?php echo $center_latitude; ?>, <?php echo $center_longitude; ?>], <?php echo $default_zoom; ?>);
               } else if (markers.getLayers().length === 1) {
                   // If only one marker, zoom to it
                   const marker = markers.getLayers()[0];
                   map.setView(marker.getLatLng(), 16);
               } else {
                   // Fit bounds to visible markers
                   map.fitBounds(markers.getBounds(), { padding: [50, 50] });
               }
           }

           // Find my location button
           document.getElementById('locate-me').addEventListener('click', function() {
               map.locate({setView: true, maxZoom: 16});

               map.on('locationfound', function(e) {
                   // Show user's location with a pulsing circle
                   const radius = e.accuracy / 2;

                   // Remove existing location markers if any
                   map.eachLayer(function(layer) {
                       if (layer._userLocationMarker || layer._userLocationCircle) {
                           map.removeLayer(layer);
                       }
                   });

                   const locationMarker = L.marker(e.latlng, {
                       icon: L.divIcon({
                           className: 'custom-marker pulse',
                           html: '<i class="fas fa-user-circle fa-2x" style="color: #007bff;"></i>',
                           iconSize: [30, 30],
                           iconAnchor: [15, 15]
                       })
                   }).addTo(map);
                   locationMarker._userLocationMarker = true;

                   const locationCircle = L.circle(e.latlng, {
                       radius: radius,
                       color: '#007bff',
                       fillColor: '#007bff',
                       fillOpacity: 0.2
                   }).addTo(map);
                   locationCircle._userLocationCircle = true;

                   // Show "Save as default" option
                   document.querySelector('.save-preference').style.display = 'block';

                   // Find nearby events
                   let nearbyEvents = [];
                   const userLocation = e.latlng;

                   allMarkers.forEach(marker => {
                       const distance = userLocation.distanceTo(marker.getLatLng());
                       // If within 1km
                       if (distance <= 1000) {
                           nearbyEvents.push({
                               distance: distance,
                               marker: marker
                           });
                       }
                   });

                   if (nearbyEvents.length > 0) {
                       // Sort by distance
                       nearbyEvents.sort((a, b) => a.distance - b.distance);

                       // Create a popup showing nearby events
                       let popupContent = `
                           <div class="p-2">
                               <h5 class="mb-2">Nearby Events</h5>
                               <p class="small text-muted mb-2">Found ${nearbyEvents.length} events within 1km</p>
                               <ul class="list-unstyled mb-0">
                       `;

                       // Show up to 5 nearest events
                       const eventsToShow = nearbyEvents.slice(0, 5);
                       eventsToShow.forEach(nearbyEvent => {
                           const eventInfo = nearbyEvent.marker.eventData.events[0];
                           popupContent += `
                               <li class="mb-2">
                                   <a href="#" class="nearby-event text-decoration-none"
                                      data-lat="${nearbyEvent.marker.getLatLng().lat}"
                                      data-lng="${nearbyEvent.marker.getLatLng().lng}">
                                       <strong>${eventInfo.title}</strong>
                                       <br><small>${Math.round(nearbyEvent.distance)} meters away</small>
                                   </a>
                               </li>
                           `;
                       });

                       popupContent += `
                               </ul>
                           </div>
                       `;

                       locationMarker.bindPopup(popupContent).openPopup();

                       // Add event handlers for nearby events (after popup is shown)
                       locationMarker.on('popupopen', function() {
                           document.querySelectorAll('.nearby-event').forEach(link => {
                               link.addEventListener('click', function(e) {
                                   e.preventDefault();
                                   const lat = parseFloat(this.dataset.lat);
                                   const lng = parseFloat(this.dataset.lng);

                                   map.flyTo([lat, lng], 17);

                                   // Find and open the marker popup
                                   allMarkers.forEach(marker => {
                                       if (marker.getLatLng().lat === lat && marker.getLatLng().lng === lng) {
                                           setTimeout(() => {
                                               marker.openPopup();
                                           }, 1000);
                                       }
                                   });
                               });
                           });
                       });
                   } else {
                       locationMarker.bindPopup("No events found nearby.").openPopup();
                   }
               });

               map.on('locationerror', function(e) {
                   alert("Could not find your location: " + e.message);
               });
           });

           // Reset view button
           document.getElementById('reset-view').addEventListener('click', function() {
               map.setView([<?php echo $center_latitude; ?>, <?php echo $center_longitude; ?>], <?php echo $default_zoom; ?>);

               // Reset filters
               document.querySelectorAll('.filter-btn[data-category]').forEach(btn => {
                   btn.classList.remove('active');
               });
               document.querySelector('.filter-btn[data-category="all"]').classList.add('active');

               document.querySelectorAll('.filter-btn[data-time]').forEach(btn => {
                   btn.classList.remove('active');
               });
               document.querySelector('.filter-btn[data-time="all"]').classList.add('active');

               document.getElementById('event-search').value = '';

               // Re-apply filters (which will reset to default)
               applyFilters();

               // Remove user location markers
               map.eachLayer(function(layer) {
                   if (layer._userLocationMarker || layer._userLocationCircle) {
                       map.removeLayer(layer);
                   }
               });

               // Hide save preference
               document.querySelector('.save-preference').style.display = 'none';
           });

           // Save view preferences
           document.getElementById('save-view').addEventListener('click', function() {
               const center = map.getCenter();
               const zoom = map.getZoom();

               // Save via AJAX
               fetch('save-map-preference.php', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded',
                   },
                   body: `lat=${center.lat}&lng=${center.lng}&zoom=${zoom}`
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success) {
                       alert('Your map preferences have been saved!');
                       document.querySelector('.save-preference').style.display = 'none';
                   } else {
                       alert('Error saving preferences: ' + data.message);
                   }
               })
               .catch(error => {
                   console.error('Error:', error);
                   alert('An error occurred while saving your preferences.');
               });
           });

           document.getElementById('cancel-save').addEventListener('click', function() {
               document.querySelector('.save-preference').style.display = 'none';
           });

           // Map movement detection (for showing save preference option)
           let userMovedMap = false;
           map.on('moveend', function() {
               if (!userMovedMap) {
                   userMovedMap = true;
                   document.querySelector('.save-preference').style.display = 'block';
               }
           });

           // Add real-time updates with polling
           let lastUpdateTime = new Date().getTime();

           function checkForNewEvents() {
               fetch(`check-new-events.php?last_update=${lastUpdateTime}`)
               .then(response => response.json())
               .then(data => {
                   if (data.success && data.new_events.length > 0) {
                       // Update last update time
                       lastUpdateTime = new Date().getTime();

                       // Notify user
                       const notification = document.createElement('div');
                       notification.className = 'alert alert-info alert-dismissible fade show';
                       notification.innerHTML = `
                           <strong>New events added!</strong> ${data.new_events.length} new events are now on the map.
                           <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                           <button class="btn btn-sm btn-primary ms-3 refresh-events">Refresh Map</button>
                       `;

                       document.querySelector('.map-filters').prepend(notification);

                       // Add refresh handler
                       notification.querySelector('.refresh-events').addEventListener('click', function() {
                           window.location.reload();
                       });

                       // Auto-dismiss after 10 seconds
                       setTimeout(() => {
                           const bsAlert = new bootstrap.Alert(notification);
                           bsAlert.close();
                       }, 10000);
                   }
               })
               .catch(error => {
                   console.error('Error checking for new events:', error);
               });
           }

           // Check for new events every 5 minutes
           setInterval(checkForNewEvents, 300000);

           // Initial filter application
           applyFilters();
        });
    </script>
</body>
</html>