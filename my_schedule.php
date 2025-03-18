<?php

session_start();
include 'config.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$filter_tag = isset($_GET['tag']) ? $_GET['tag'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_asc'; // Default sorting


$query = "SELECT e.*, us.added_at 
          FROM events e 
          JOIN user_schedules us ON e.event_id = us.event_id 
          WHERE us.user_id = ?";


$params = array($user_id);
$types = "i";

if (!empty($filter_tag)) {
    $query .= " AND e.tag = ?";
    $params[] = $filter_tag;
    $types .= "s";
}

if (!empty($filter_date)) {
    $query .= " AND e.event_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}


switch ($sort_by) {
    case 'date_desc':
        $query .= " ORDER BY e.event_date DESC, e.start_time ASC";
        break;
    case 'title_asc':
        $query .= " ORDER BY e.title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY e.title DESC";
        break;
    case 'recently_added':
        $query .= " ORDER BY us.added_at DESC";
        break;
    case 'date_asc':
    default:
        $query .= " ORDER BY e.event_date ASC, e.start_time ASC";
        break;
}


$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


$tag_query = "SELECT DISTINCT e.tag FROM events e 
              JOIN user_schedules us ON e.event_id = us.event_id 
              WHERE us.user_id = ? AND e.tag IS NOT NULL AND e.tag != ''
              ORDER BY e.tag ASC";
$tag_stmt = $conn->prepare($tag_query);
$tag_stmt->bind_param("i", $user_id);
$tag_stmt->execute();
$tag_result = $tag_stmt->get_result();
$tags = [];
while ($tag_row = $tag_result->fetch_assoc()) {
    $tags[] = $tag_row['tag'];
}

include 'header.php';
?>

<div class="container my-5">
    
    <div class="row mb-4 fade-in">
        <div class="col">
            <h1 class="display-4 text-primary fw-bold">
                <i class="fas fa-calendar-alt me-2 pulse"></i>My Schedule
            </h1>
            <p class="lead text-muted">Manage your personalized event calendar</p>
        </div>
    </div>

    
    <div class="row mb-4 slide-in">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-body">
                    <form id="filterForm" method="get" class="row g-3 align-items-end">
                        <!-- Tag Filter -->
                        <div class="col-md-3">
                            <label for="tagFilter" class="form-label"><i class="fas fa-tag me-1"></i> Filter by Tag</label>
                            <select class="form-select" id="tagFilter" name="tag">
                                <option value="">All Tags</option>
                                <?php foreach ($tags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag); ?>" <?php echo ($filter_tag == $tag) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tag); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label"><i class="fas fa-calendar me-1"></i> Filter by Date</label>
                            <input type="date" class="form-control" id="dateFilter" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        
                       
                        <div class="col-md-3">
                            <label for="sortBy" class="form-label"><i class="fas fa-sort me-1"></i> Sort By</label>
                            <select class="form-select" id="sortBy" name="sort">
                                <option value="date_asc" <?php echo ($sort_by == 'date_asc') ? 'selected' : ''; ?>>Date (Earliest First)</option>
                                <option value="date_desc" <?php echo ($sort_by == 'date_desc') ? 'selected' : ''; ?>>Date (Latest First)</option>
                                <option value="title_asc" <?php echo ($sort_by == 'title_asc') ? 'selected' : ''; ?>>Title (A-Z)</option>
                                <option value="title_desc" <?php echo ($sort_by == 'title_desc') ? 'selected' : ''; ?>>Title (Z-A)</option>
                                <option value="recently_added" <?php echo ($sort_by == 'recently_added') ? 'selected' : ''; ?>>Recently Added</option>
                            </select>
                        </div>
                        
                       
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                </button>
                                <a href="my_schedule.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <?php if ($result->num_rows > 0): ?>
    <div class="row mb-4 slide-in-delay">
        <div class="col-lg-12">
            <div class="stats-container d-flex flex-wrap gap-3">
                <div class="stat-card bg-primary text-white">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $result->num_rows; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                </div>
                
                <?php
               // Reset pointer 
                $result->data_seek(0);
                
                
                $upcoming = 0;
                $today = date('Y-m-d');
                while ($event = $result->fetch_assoc()) {
                    if ($event['event_date'] >= $today) {
                        $upcoming++;
                    }
                }
                
                // Reset result pointer again
                $result->data_seek(0);
                ?>
                
                <div class="stat-card bg-success text-white">
                    <div class="stat-icon"><i class="fas fa-hourglass-start"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $upcoming; ?></div>
                        <div class="stat-label">Upcoming Events</div>
                    </div>
                </div>
                
                <div class="stat-card bg-info text-white">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" id="nextEventCountdown">-</div>
                        <div class="stat-label">Until Next Event</div>
                    </div>
                </div>
                
                <?php if (!empty($tags)): ?>
                <div class="stat-card bg-secondary text-white">
                    <div class="stat-icon"><i class="fas fa-tags"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo count($tags); ?></div>
                        <div class="stat-label">Event Categories</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <!-- Events Listing -->
        <div class="row" id="scheduleContainer">
            <?php while ($event = $result->fetch_assoc()): 
              //  checkign whether event is today or in future
                $event_date = new DateTime($event['event_date']);
                $today = new DateTime('today');
                $is_today = ($event_date->format('Y-m-d') === $today->format('Y-m-d'));
                $is_past = ($event_date < $today);
                
               
                $card_class = $is_past ? 'past-event' : ($is_today ? 'today-event' : 'upcoming-event');
            ?>
                <div class="col-md-6 col-lg-4 mb-4 event-card fade-in-card" data-event-id="<?php echo $event['event_id']; ?>">
                    <div class="card h-100 shadow hover-card <?php echo $card_class; ?>">
                        <?php if ($is_today): ?>
                            <div class="today-badge">Today</div>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['event_image_resized'])): ?>
                            <div class="card-img-wrapper">
                                <img src="<?php echo htmlspecialchars($event['event_image_resized']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                                     loading="lazy">
                                <div class="card-img-overlay-gradient">
                                    <?php if (!empty($event['tag'])): ?>
                                        <span class="badge bg-primary tag-badge"><?php echo htmlspecialchars($event['tag']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card-img-wrapper">
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-day fa-4x text-secondary"></i>
                                </div>
                                <div class="card-img-overlay-top">
                                    <?php if (!empty($event['tag'])): ?>
                                        <span class="badge bg-primary tag-badge"><?php echo htmlspecialchars($event['tag']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title text-truncate" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </h5>
                            
                            <div class="card-text">
                                <p class="description-preview"><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>
                                <?php if (strlen($event['description']) > 100): ?>
                                    <span class="text-primary read-more" data-bs-toggle="tooltip" title="Click to read full description">...</span>
                                <?php endif; ?>
                                </p>
                                
                                <div class="event-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-day text-primary"></i>
                                        <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-clock text-primary"></i>
                                        <span>
                                            <?php 
                                            echo date('g:i A', strtotime($event['start_time'])) . ' - ' . 
                                                 date('g:i A', strtotime($event['end_time'])); 
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                        <span class="text-truncate" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($event['location']); ?>">
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($event['department'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-building text-primary"></i>
                                            <span><?php echo htmlspecialchars($event['department']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 action-buttons">
                                <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                                <div class="btn-group">
                                    <a href="#" class="btn btn-outline-primary btn-sm share-btn" 
                                       data-event-id="<?php echo $event['event_id']; ?>"
                                       data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <i class="fas fa-share-alt"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm remove-btn" 
                                            data-event-id="<?php echo $event['event_id']; ?>"
                                            data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <i class="fas fa-calendar-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                            <small>Added <?php echo date('M j, Y', strtotime($event['added_at'])); ?></small>
                            <?php if ($is_past): ?>
                                <span class="badge bg-secondary">Past</span>
                            <?php elseif ($is_today): ?>
                                <span class="badge bg-success">Today</span>
                            <?php else: ?>
                                <?php
                                    $days_until = $event_date->diff($today)->days;
                                    if ($days_until <= 7) {
                                        echo '<span class="badge bg-warning text-dark">Soon</span>';
                                    }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (strlen($event['description']) > 100): ?>
               
                <div class="modal fade" id="descriptionModal-<?php echo $event['event_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
        
        <div class="row mt-3 mb-5 fade-in-delay">
            <div class="col">
                <div class="alert alert-info shadow-sm border-0">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                        </div>
                        <div>
                            <h4 class="alert-heading fw-bold">Tips</h4>
                            <p>Click on "Details" to see complete information about an event. Use the filter options above to find specific events.</p>
                          <!--
                            <div class="mt-2">
                                <a href="calendar_view.php" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-calendar-alt me-1"></i> Calendar View
                                </a>
                                <a href="export_calendar.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-file-export me-1"></i> Export to iCal
                                </a>
                            </div>

                -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="row fade-in">
            <div class="col">
                <div class="empty-state text-center py-5">
                    <img src="assets/img/empty-calendar.svg" alt="Empty Calendar" class="empty-state-image mb-4" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';">
                    <h3 class="display-6 text-primary">No events in your schedule yet!</h3>
                    <p class="lead text-muted mb-4">Your personalized event calendar is waiting to be filled with exciting events.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="events.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i> Browse Events
                        </a>
                        <a href="event_categories.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-th-large me-2"></i> View Categories
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


<div class="modal fade" id="removeEventModal" tabindex="-1" aria-labelledby="removeEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeEventModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Remove Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-calendar-times text-danger fa-4x mb-3"></i>
                    <p class="lead">Are you sure you want to remove this event from your schedule?</p>
                    <p class="fw-bold" id="eventTitle"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" id="confirmRemove" class="btn btn-danger">
                    <i class="fas fa-calendar-times me-1"></i> Remove
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="shareEventModal" tabindex="-1" aria-labelledby="shareEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="shareEventModalLabel">
                    <i class="fas fa-share-alt me-2"></i>Share Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-share-alt text-primary fa-3x mb-3"></i>
                    <p>Share <span id="shareEventTitle" class="fw-bold"></span> with your friends:</p>
                </div>
                
                <div class="d-flex justify-content-center gap-3 mb-4">
                    <button class="btn btn-outline-primary share-option" data-platform="email">
                        <i class="fas fa-envelope fa-lg"></i>
                    </button>
                    <button class="btn btn-outline-primary share-option" data-platform="facebook">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </button>
                    <button class="btn btn-outline-primary share-option" data-platform="twitter">
                        <i class="fab fa-twitter fa-lg"></i>
                    </button>
                    <button class="btn btn-outline-primary share-option" data-platform="whatsapp">
                        <i class="fab fa-whatsapp fa-lg"></i>
                    </button>
                </div>
                
                <div class="input-group mb-3">
                    <input type="text" id="shareLink" class="form-control" readonly>
                    <button class="btn btn-outline-secondary copy-link" type="button">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="alertToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-bell me-2"></i>
            <strong class="me-auto">Notification</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
      
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 }
        });
    });

    document.querySelectorAll('.read-more').forEach(function(element) {
        element.addEventListener('click', function() {
            var eventId = this.closest('.event-card').getAttribute('data-event-id');
            var modal = new bootstrap.Modal(document.getElementById('descriptionModal-' + eventId));
            modal.show();
        });
    });
    
  
    document.querySelectorAll('.description-preview').forEach(function(element) {
        element.addEventListener('click', function() {
            if (this.querySelector('.read-more')) {
                var eventId = this.closest('.event-card').getAttribute('data-event-id');
                var modal = new bootstrap.Modal(document.getElementById('descriptionModal-' + eventId));
                modal.show();
            }
        });
    });
    
   
    const removeButtons = document.querySelectorAll('.remove-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            
            document.getElementById('eventTitle').textContent = eventTitle;
            document.getElementById('confirmRemove').setAttribute('data-event-id', eventId);
            
           
            const removeModal = new bootstrap.Modal(document.getElementById('removeEventModal'));
            removeModal.show();
        });
    });
    
   
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            
            document.getElementById('shareEventTitle').textContent = eventTitle;
            document.getElementById('shareLink').value = window.location.origin + '/event_details.php?id=' + eventId;
            
           
            const shareModal = new bootstrap.Modal(document.getElementById('shareEventModal'));
            shareModal.show();
        });
    });
    

    document.querySelectorAll('.share-option').forEach(button => {
        button.addEventListener('click', function() {
            const platform = this.getAttribute('data-platform');
            const url = document.getElementById('shareLink').value;
            const title = document.getElementById('shareEventTitle').textContent;
            
            let shareUrl = '';
            
            switch(platform) {
                case 'email':
                    shareUrl = `mailto:?subject=Check out this event: ${title}&body=I thought you might be interested in this event: ${url}`;
                    break;
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent('Check out this event: ' + title)}&url=${encodeURIComponent(url)}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent('Check out this event: ' + title + ' ' + url)}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank');
            }
        });
    });
    
    
    document.querySelector('.copy-link').addEventListener('click', function() {
        const shareLink = document.getElementById('shareLink');
        shareLink.select();
        document.execCommand('copy');
        
        showAlert('Link copied to clipboard!', 'success');
    });
    
    window.showAlert = function(message, type) {
        const toast = document.getElementById('alertToast');
        const toastBody = toast.querySelector('.toast-body');
        
      
        toastBody.textContent = message;
        toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
        
        switch(type) {
            case 'success':
                toast.classList.add('bg-success', 'text-white');
                break;
            case 'danger':
                toast.classList.add('bg-danger', 'text-white');
                break;
            case 'warning':
                toast.classList.add('bg-warning');
                break;
            case 'info':
                toast.classList.add('bg-info', 'text-white');
                break;
        }
        
        
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
    };
    

   document.getElementById('confirmRemove').addEventListener('click', function() {
        const eventId = this.getAttribute('data-event-id');
        const card = document.querySelector(`.event-card[data-event-id="${eventId}"]`);
        
        
        const formData = new FormData();
        formData.append('event_id', eventId);
        formData.append('action', 'remove');
        
        
        fetch('update_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide modal
                bootstrap.Modal.getInstance(document.getElementById('removeEventModal')).hide();
                
                
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    card.remove();
                    
                   
                    if (document.querySelectorAll('.event-card').length === 0) {
                        document.getElementById('scheduleContainer').innerHTML = `
                            <div class="col">
                                <div class="empty-state text-center py-5">
                                    <img src="assets/img/empty-calendar.svg" alt="Empty Calendar" class="empty-state-image mb-4" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';">
                                    <h3 class="display-6 text-primary">No events in your schedule yet!</h3>
                                    <p class="lead text-muted mb-4">Your personalized event calendar is waiting to be filled with exciting events.</p>
                                    <div class="d-flex justify-content-center gap-3">
                                        <a href="events.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-search me-2"></i> Browse Events
                                        </a>
                                        <a href="event_categories.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="fas fa-th-large me-2"></i> View Categories
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Update event count in stats
                    const eventCountElement = document.querySelector('.stat-card .stat-value');
                    if (eventCountElement) {
                        const currentCount = parseInt(eventCountElement.textContent);
                        eventCountElement.textContent = currentCount - 1;
                    }
                    
                    showAlert('Event removed from your schedule', 'success');
                }, 500);
            } else {
                showAlert('Failed to remove event: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred while removing the event', 'danger');
            console.error('Error:', error);
        });
    });
    
   // countdown
    function updateNextEventCountdown() {
        const events = document.querySelectorAll('.upcoming-event, .today-event');
        if (events.length === 0) {
            document.getElementById('nextEventCountdown').textContent = 'N/A';
            return;
        }
        
        let nextEventDate = null;
        let nextEventTime = null;
        
        events.forEach(event => {
            const dateText = event.querySelector('.detail-item:nth-child(1) span').textContent;
            const timeText = event.querySelector('.detail-item:nth-child(2) span').textContent.split(' - ')[0];
            
            const eventDateTime = new Date(dateText + ' ' + timeText);
            
            if (!nextEventDate || eventDateTime < nextEventDate) {
                nextEventDate = eventDateTime;
                
                
                const now = new Date();
                const diff = nextEventDate - now;
                
                if (diff <= 0) {
                    nextEventTime = 'Now!';
                } else {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    
                    if (days > 0) {
                        nextEventTime = `${days}d ${hours}h`;
                    } else if (hours > 0) {
                        nextEventTime = `${hours}h ${minutes}m`;
                    } else {
                        nextEventTime = `${minutes}m`;
                    }
                }
            }
        });
        
        document.getElementById('nextEventCountdown').textContent = nextEventTime;
    }
    
    
    if (document.getElementById('nextEventCountdown')) {
        updateNextEventCountdown();
        setInterval(updateNextEventCountdown, 60000);
    }
    

    document.getElementById('tagFilter').addEventListener('change', function() {
        if (this.value !== '') {
            this.classList.add('bg-light');
        } else {
            this.classList.remove('bg-light');
        }
    });
    
    document.getElementById('dateFilter').addEventListener('change', function() {
        if (this.value !== '') {
            this.classList.add('bg-light');
        } else {
            this.classList.remove('bg-light');
        }
    });
    
   
    document.getElementById('tagFilter').dispatchEvent(new Event('change'));
    document.getElementById('dateFilter').dispatchEvent(new Event('change'));
});

</script>

<?php

require_once 'footer.php';
?>
<style>
   
.hover-card {
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    overflow: hidden;
    border: none;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
}

.card-img-wrapper {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.card-img-top {
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.hover-card:hover .card-img-top {
    transform: scale(1.05);
}

.card-img-overlay-gradient {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    padding: 1rem;
    pointer-events: none;
}

.card-img-overlay-top {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    padding: 0.75rem;
}

.tag-badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    border-radius: 0.25rem;
    font-weight: 500;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.card-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
    font-size: 1.1rem;
    color: #32325d;
}

.description-preview {
    font-size: 0.9rem;
    color: #6b7c93;
    margin-bottom: 1rem;
    cursor: pointer;
}

.read-more {
    font-weight: 600;
    cursor: pointer;
}

.event-details {
    font-size: 0.85rem;
}

.detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.detail-item i {
    width: 20px;
    margin-right: 0.5rem;
}

.action-buttons {
    margin-top: 1rem;
}

.action-buttons .btn {
    border-radius: 0.4rem;
    font-weight: 500;
}

.card-footer {
    background-color: rgba(0,0,0,0.02);
    font-size: 0.8rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}


.fade-in {
    animation: fadeIn 0.6s ease-in-out;
}

.fade-in-delay {
    animation: fadeIn 0.6s ease-in-out 0.3s forwards;
    opacity: 0;
}

.slide-in {
    animation: slideIn 0.6s ease-in-out;
}

.slide-in-delay {
    animation: slideIn 0.6s ease-in-out 0.3s forwards;
    opacity: 0;
}

.fade-in-card {
    animation: fadeIn 0.6s ease-in-out;
    animation-fill-mode: both;
    animation-delay: calc(var(--card-index, 0) * 0.1s);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.past-event {
    opacity: 0.7;
}

.today-event {
    border-left: 4px solid #28a745;
}

.today-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0 0.75rem 0 0.5rem;
    z-index: 1;
}


.stats-container {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    border-radius: 0.75rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 2rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}


.empty-state {
    background-color: #f8f9fa;
    border-radius: 1rem;
    padding: 3rem 1.5rem;
}

.empty-state-image {
    max-width: 200px;
    margin-bottom: 1.5rem;
}


@media (max-width: 767.98px) {
    .card-title {
        font-size: 1rem;
    }
    
    .btn {
        font-size: 0.85rem;
        padding: 0.375rem 0.65rem;
    }
    
    .stats-container {
        gap: 0.75rem;
    }
    
    .stat-card {
        min-width: 100%;
    }
}

@media (max-width: 991.98px) {
    .stats-container {
        flex-wrap: wrap;
    }
    
    .stat-card {
        flex: 1 0 calc(50% - 0.75rem);
        margin-bottom: 0.75rem;
    }
}
</style>