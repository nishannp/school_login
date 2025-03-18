<?php

include '../config.php';

$success_message = '';
$error_message = '';
$event = null;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid event ID. Please select a valid event.";
} else {
    $event_id = $_GET['id'];

    $query = "SELECT * FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error_message = "Event not found.";
    } else {
        $event = $result->fetch_assoc();
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_event'])) {

    $event_id = mysqli_real_escape_string($conn, $_POST['event_id']);
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

        $event_image_original = $event['event_image_original'];
        $event_image_resized = $event['event_image_resized'];

        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
            $target_dir = "../uploads/events/";  

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;

            $target_file_original = $target_dir . "original_" . $new_filename;
            $target_file_resized = $target_dir . "resized_" . $new_filename;

            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];

            $script_dir = dirname($_SERVER['PHP_SELF']);

            $base_url = str_replace('/admin', '', $script_dir);

            if($base_url != '/'){
                $base_url = rtrim($base_url, '/');
            }

            $db_event_image_original = $protocol . "://" . $host . $base_url. "/uploads/events/original_" . $new_filename;
            $db_event_image_resized = $protocol . "://" . $host . $base_url. "/uploads/events/resized_" . $new_filename;

            $check = getimagesize($_FILES["event_image"]["tmp_name"]);
            if ($check !== false) {

                if (!empty($event['event_image_original'])) {
                    $old_original = str_replace($protocol . "://" . $host . $base_url, "..", parse_url($event['event_image_original'], PHP_URL_PATH));
                    if (file_exists($old_original)) {
                        unlink($old_original);
                    }
                }

                if (!empty($event['event_image_resized'])) {
                    $old_resized = str_replace($protocol . "://" . $host . $base_url, "..", parse_url($event['event_image_resized'], PHP_URL_PATH));
                    if (file_exists($old_resized)) {
                        unlink($old_resized);
                    }
                }

                if (move_uploaded_file($_FILES["event_image"]["tmp_name"], $target_file_original)) {

                    $event_image_original = $db_event_image_original;
                    $event_image_resized = $db_event_image_resized;

                    $source = imagecreatefromstring(file_get_contents($target_file_original));
                    $width = imagesx($source);
                    $height = imagesy($source);
                    $new_width = 600;
                    $new_height = floor($height * ($new_width / $width));

                    $resized = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    imagejpeg($resized, $target_file_resized, 80);

                    imagedestroy($source);
                    imagedestroy($resized);
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error_message = "File is not an image.";
            }
        }

        if (empty($error_message)) {
            $sql = "UPDATE events SET 
                    title = ?, 
                    description = ?, 
                    event_date = ?, 
                    start_time = ?, 
                    end_time = ?, 
                    location = ?, 
                    latitude = ?, 
                    longitude = ?, 
                    speakers = ?, 
                    department = ?, 
                    topic = ?, 
                    tag = ?, 
                    estimated_participants = ?, 
                    event_image_original = ?, 
                    event_image_resized = ? 
                    WHERE event_id = ? AND admin_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssddssssissii", $title, $description, $event_date, $start_time, $end_time,
                $location, $latitude, $longitude, $speakers, $department, $topic, $tag,
                $estimated_participants, $event_image_original, $event_image_resized, $event_id, $admin_id);

            if ($stmt->execute()) {
                $success_message = "Event updated successfully!";

                $query = "SELECT * FROM events WHERE event_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $event_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $event = $result->fetch_assoc();
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

include 'header.php';
?>

<!-- Page content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Event</h1>
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

    <?php if ($event): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit Event Details</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $event['event_id']); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">

                <div class="row">
                    <!-- Basic Event Information -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $event['start_time']; ?>" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo $event['end_time']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="0.0000001" class="form-control" id="latitude" name="latitude" value="<?php echo $event['latitude']; ?>">
                                <small class="form-text text-muted">Optional, for map display</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="0.0000001" class="form-control" id="longitude" name="longitude" value="<?php echo $event['longitude']; ?>">
                                <small class="form-text text-muted">Optional, for map display</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="speakers" class="form-label">Speakers</label>
                            <input type="text" class="form-control" id="speakers" name="speakers" placeholder="Comma-separated list of speakers" value="<?php echo htmlspecialchars($event['speakers']); ?>">
                        </div>
                    </div>

                    <!-- Additional Event Information -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($event['department']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="topic" class="form-label">Topic</label>
                            <input type="text" class="form-control" id="topic" name="topic" value="<?php echo htmlspecialchars($event['topic']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="tag" class="form-label">Tag</label>
                            <select class="form-select" id="tag" name="tag">
                                <option value="">Select a tag (optional)</option>
                                <option value="featured" <?php echo ($event['tag'] == 'featured') ? 'selected' : ''; ?>>Featured</option>
                                <option value="upcoming" <?php echo ($event['tag'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                                <option value="important" <?php echo ($event['tag'] == 'important') ? 'selected' : ''; ?>>Important</option>
                                <option value="workshop" <?php echo ($event['tag'] == 'workshop') ? 'selected' : ''; ?>>Workshop</option>
                                <option value="seminar" <?php echo ($event['tag'] == 'seminar') ? 'selected' : ''; ?>>Seminar</option>
                                <option value="conference" <?php echo ($event['tag'] == 'conference') ? 'selected' : ''; ?>>Conference</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="estimated_participants" class="form-label">Estimated Participants</label>
                            <input type="number" class="form-control" id="estimated_participants" name="estimated_participants" min="0" value="<?php echo $event['estimated_participants']; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="event_image" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*">
                            <small class="form-text text-muted">Upload a new image to replace the current one (JPEG, PNG)</small>
                        </div>

                        <?php if(!empty($event['event_image_resized'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div class="border p-2 rounded">
                                <img src="<?php echo $event['event_image_resized']; ?>" alt="Current event image" class="img-fluid" style="max-height: 200px;">
                            </div>
                        </div>
                        <?php endif; ?>

                        <div id="image_preview" class="mb-3 d-none">
                            <label class="form-label">New Image Preview</label>
                            <div class="border p-2 rounded">
                                <img id="preview_img" src="#" alt="Event image preview" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end">
                    <a href="events.php" class="btn btn-secondary me-2">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="update_event" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        Event not found or you don't have permission to edit it.
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript for image preview -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

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
    });
</script>

<?php

include 'footer.php';
?>