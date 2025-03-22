<?php

include '../config.php';

$success_message = '';
$error_message = '';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate inputs
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $latitude = !empty($_POST['latitude']) ? mysqli_real_escape_string($conn, $_POST['latitude']) : NULL;
    $longitude = !empty($_POST['longitude']) ? mysqli_real_escape_string($conn, $_POST['longitude']) : NULL;
    $speakers = mysqli_real_escape_string($conn, $_POST['speakers']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $tag = mysqli_real_escape_string($conn, $_POST['tag']);
    $estimated_participants = mysqli_real_escape_string($conn, $_POST['estimated_participants']);

    $admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

    if ($admin_id === null) {
        $error_message = "Error: Admin ID not found in session. Please log in again.";
    } else {
        // --- Image Upload and Processing ---
        $event_image_original = '';
        $event_image_resized = '';

        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $target_dir = "../uploads/events/";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Generate unique filename
            $file_extension = pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file_original = $target_dir . "original_" . $new_filename;
            $target_file_resized = $target_dir . "resized_" . $new_filename;


            // Construct full URLs for database storage
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $script_dir = dirname($_SERVER['PHP_SELF']);
            $base_url = str_replace('/admin', '', $script_dir);  // Remove /admin
             if($base_url != '/'){
                $base_url = rtrim($base_url, '/'); //Remove Trailing /
            }

            $db_event_image_original = $protocol . "://" . $host . $base_url . "/uploads/events/original_" . $new_filename;
            $db_event_image_resized = $protocol . "://" . $host .  $base_url . "/uploads/events/resized_" . $new_filename;


            // Validate image
            $check = getimagesize($_FILES["event_image"]["tmp_name"]);
            if ($check !== false) {
                // Move uploaded file
                if (move_uploaded_file($_FILES["event_image"]["tmp_name"], $target_file_original)) {
                    $event_image_original = $db_event_image_original; // Store URL
                    $event_image_resized = $db_event_image_resized;      //Store URL

                    // Resize Image
                    $source = imagecreatefromstring(file_get_contents($target_file_original));
                    $width = imagesx($source);
                    $height = imagesy($source);
                    $new_width = 600;
                    $new_height = floor($height * ($new_width / $width));

                    $resized = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($resized, $target_file_resized, 80);  // Save resized image

                    imagedestroy($source);
                    imagedestroy($resized);
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error_message = "File is not an image.";
            }
        }

        // --- Database Insertion ---
        if (empty($error_message)) {
            //Prepared statement
            $sql = "INSERT INTO events (title, description, event_date, start_time, end_time, location, 
                    latitude, longitude, speakers, department, topic, tag, estimated_participants, 
                    event_image_original, event_image_resized, admin_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            //Bind parameters
            $stmt->bind_param("ssssssddssssissi", $title, $description, $event_date, $start_time, $end_time,
                $location, $latitude, $longitude, $speakers, $department, $topic, $tag,
                $estimated_participants, $event_image_original, $event_image_resized, $admin_id);


            if ($stmt->execute()) {
                $success_message = "Event added successfully!";
                $_POST = array(); // Clear form data after successful submission
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

include 'header.php';
?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #map {
        height: 300px; /* Adjust height as needed */
        width: 100%;
        margin-bottom: 20px;
    }
</style>

<!-- Page content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Event</h1>
        <a href="events.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Event Details</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <!-- Basic Event Information -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" required value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        </div>
                         <!-- Leaflet Map -->
                        <div class="mb-3">
                            <label for="map" class="form-label">Select Location on Map</label>
                            <div id="map"></div>
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : ''; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : ''; ?>">
                        </div>



                        <div class="mb-3">
                            <label for="speakers" class="form-label">Speakers</label>
                            <input type="text" class="form-control" id="speakers" name="speakers" placeholder="Comma-separated list of speakers" value="<?php echo isset($_POST['speakers']) ? htmlspecialchars($_POST['speakers']) : ''; ?>">
                        </div>
                    </div>

                    <!-- Additional Event Information (Right Column) -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="topic" class="form-label">Topic</label>
                            <input type="text" class="form-control" id="topic" name="topic" value="<?php echo isset($_POST['topic']) ? htmlspecialchars($_POST['topic']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="tag" class="form-label">Tag</label>
                            <select class="form-select" id="tag" name="tag">
                                <option value="">Select a tag (optional)</option>
                                <option value="featured" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'featured') ? 'selected' : ''; ?>>Featured</option>
                                <option value="upcoming" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="important" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'important') ? 'selected' : ''; ?>>Important</option>
                                <option value="workshop" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'workshop') ? 'selected' : ''; ?>>Workshop</option>
                                <option value="seminar" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'seminar') ? 'selected' : ''; ?>>Seminar</option>
                                <option value="conference" <?php echo (isset($_POST['tag']) && $_POST['tag'] == 'conference') ? 'selected' : ''; ?>>Conference</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="estimated_participants" class="form-label">Estimated Participants</label>
                            <input type="number" class="form-control" id="estimated_participants" name="estimated_participants" min="0" value="<?php echo isset($_POST['estimated_participants']) ? htmlspecialchars($_POST['estimated_participants']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="event_image" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*">
                            <small class="form-text text-muted">Upload an image for the event (JPEG, PNG)</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_users" name="notify_users">
                                <label class="form-check-label" for="notify_users">
                                    Notify users about this event
                                </label>
                            </div>
                        </div>

                        <div id="image_preview" class="mb-3 d-none">
                            <label class="form-label">Image Preview</label>
                            <div class="border p-2 rounded">
                                <img id="preview_img" src="#" alt="Event image preview" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-secondary me-2">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Image Preview
        document.getElementById('event_image').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('image_preview');
            const previewImg = document.getElementById('preview_img');
            const file = e.target.files[0];

            if (file) {
                previewContainer.classList.remove('d-none');
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('d-none');
            }
        });

        
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            if (startTime && endTime && startTime >= endTime) {
                event.preventDefault();
                alert('End time must be after start time');
            }
        });

         
        var map = L.map('map').setView([52.5862, -2.1286], 13); // Default: Wolverhampton


        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker; 

       
        function updateLatLngInputs(lat, lng) {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        }

        // Event listener for map clicks
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);  
            var lng = e.latlng.lng.toFixed(6);

            // Remove existing marker if it exists
            if (marker) {
                map.removeLayer(marker);
            }
             // Add a new marker
            marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup("Selected Location").openPopup();

            // Update the hidden input fields
            updateLatLngInputs(lat, lng);
        });

         // Initialize with previous values if available
         let initialLat = document.getElementById('latitude').value;
         let initialLng = document.getElementById('longitude').value;

         if (initialLat && initialLng) {
            initialLat = parseFloat(initialLat);  // Convert to numbers
            initialLng = parseFloat(initialLng);
            map.setView([initialLat, initialLng], 13); // Set the view
            marker = L.marker([initialLat, initialLng]).addTo(map); // Add marker
            marker.bindPopup("Selected Location").openPopup();
        }


    });
</script>

<?php
include 'footer.php';
?>