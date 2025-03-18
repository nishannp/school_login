<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sort_by = $_GET['sort'] ?? 'date_asc'; 
$sort_options = [
    'date_asc' => ['column' => 'e.event_date, e.start_time', 'order' => 'ASC'],
    'date_desc' => ['column' => 'e.event_date, e.start_time', 'order' => 'DESC'],
    'title_asc' => ['column' => 'e.title', 'order' => 'ASC'],
    'title_desc' => ['column' => 'e.title', 'order' => 'DESC'],
    'department_asc' => ['column' => 'e.department', 'order' => 'ASC'],
    'department_desc' => ['column' => 'e.department', 'order' => 'DESC'],
];

$sort = $sort_options[$sort_by] ?? $sort_options['date_asc'];

$filter_department = $_GET['department'] ?? '';
$filter_topic = $_GET['topic'] ?? '';
$filter_date = $_GET['date'] ?? '';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($filter_department)) {
    $where_clauses[] = "e.department = ?";
    $params[] = $filter_department;
    $param_types .= 's';
}
if (!empty($filter_topic)) {
    $where_clauses[] = "e.topic = ?";
    $params[] = $filter_topic;
    $param_types .= 's';
}
if (!empty($filter_date)) {
    $where_clauses[] = "e.event_date = ?";
    $params[] = $filter_date;
    $param_types .= 's';
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$events_query = "SELECT 
    e.event_id, 
    e.title, 
    e.description, 
    DATE_FORMAT(e.event_date, '%Y-%m-%d') as event_date, 
    DATE_FORMAT(e.start_time, '%H:%i') as start_time, 
    DATE_FORMAT(e.end_time, '%H:%i') as end_time, 
    CONCAT(DATE_FORMAT(e.event_date, '%W, %M %e, %Y'), ' ', DATE_FORMAT(e.start_time, '%l:%i %p'), ' - ', DATE_FORMAT(e.end_time, '%l:%i %p')) as formatted_date_time,
     CONCAT(DATE_FORMAT(e.event_date, '%a, %b %e'), ' ', DATE_FORMAT(e.start_time, '%l:%i %p')) as short_date_time,
    e.location, 
    e.speakers,
    e.department,
    e.topic,
    e.tag,
    e.estimated_participants,
    COALESCE(e.event_image_resized, e.event_image_original, 'https://via.placeholder.com/350x200') as image
FROM events e
$where_clause
ORDER BY {$sort['column']} {$sort['order']}";

$events_stmt = $conn->prepare($events_query);

if (!empty($params)) {
    $events_stmt->bind_param($param_types, ...$params);
}

$events_stmt->execute();
$events_result = $events_stmt->get_result();
$events = [];

if ($events_result->num_rows > 0) {
    while ($row = $events_result->fetch_assoc()) {
        $events[] = $row;
    }
}
$events_stmt->close();

$departments_query = "SELECT DISTINCT department FROM events WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = $conn->query($departments_query);
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row['department'];
}

$topics_query = "SELECT DISTINCT topic FROM events WHERE topic IS NOT NULL AND topic != '' ORDER BY topic";
$topics_result = $conn->query($topics_query);
$topics = [];
while ($row = $topics_result->fetch_assoc()) {
    $topics[] = $row['topic'];
}

$user_favorites = [];
$favorites_query = "SELECT event_id FROM user_favorites WHERE user_id = ?";
$favorites_stmt = $conn->prepare($favorites_query);
$favorites_stmt->bind_param("i", $user_id);
$favorites_stmt->execute();
$favorites_result = $favorites_stmt->get_result();
while ($row = $favorites_result->fetch_assoc()) {
    $user_favorites[] = $row['event_id'];
}
$favorites_stmt->close();

$user_schedules = [];
$schedules_query = "SELECT event_id FROM user_schedules WHERE user_id = ?";
$schedules_stmt = $conn->prepare($schedules_query);
$schedules_stmt->bind_param("i", $user_id);
$schedules_stmt->execute();
$schedules_result = $schedules_stmt->get_result();
while ($row = $schedules_result->fetch_assoc()) {
    $user_schedules[] = $row['event_id'];
}
$schedules_stmt->close();

$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc(); 
$user_stmt->close();

?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
       :root {
            --primary-color: #007bff;
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
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-color); }
        .breadcrumb { background-color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .breadcrumb-item a { color: var(--primary-color); text-decoration: none; }
        .breadcrumb-item.active { color: var(--secondary-color); }

        .filter-sort-container { background-color: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .filter-sort-container label { font-weight: 600; margin-bottom: 0.5rem; display: block; color: var(--dark-color);}
        .form-select, .form-control { border-radius: 0.375rem; padding: 0.5rem 0.75rem;  border-color: #ced4da; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
        .form-select:focus, .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); }

       .card {
            background-color: var(--card-bg-color);
            border: 1px solid var(--card-border-color);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden; 
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .card-img-container{
            height: 200px;
            overflow: hidden;
            display: flex;
        }
        .card-img-top {
            width: 100%; 
            height: 100%;
            object-fit: cover; 
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;

        }

        .card-body {
            padding: 1.25rem;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
            white-space: nowrap;        
            overflow: hidden;           
            text-overflow: ellipsis;    
        }

       .event-meta {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
        }

        .event-meta i {
            margin-right: 0.3rem; 
            color: var(--primary-color); 
        }

        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.8rem; 
            margin-right: 0.3rem; 
            font-weight: 500; 

        }

        .badge-department { background-color: #007bff; color: white; }
        .badge-topic { background-color: #28a745; color: white; }
        .badge-tag { background-color: #ffc107; color: black;}

        .btn-favorite, .btn-schedule {
            width: 36px; 
            height: 36px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            color: #ccc;
            border: 1px solid #ccc; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-left: 0.3rem;
        }
        .btn-favorite:hover, .btn-schedule:hover  { transform: scale(1.05);  border-color: var(--primary-color);}
        .btn-favorite.active { color: var(--danger-color); border-color: var(--danger-color); }
        .btn-schedule.active { color: var(--success-color); border-color: var(--success-color);}
        .btn-group-actions { display: flex; justify-content: flex-end; margin-top: 0.75rem; }
        .card-footer {
            padding: 0.75rem 1.25rem;
            background-color: var(--card-header-bg-color);
            border-top: 1px solid var(--card-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-details {
                background-color: var(--primary-color);
                color: white;
                border: none;
                padding: 0.375rem 0.75rem; 
                border-radius: 0.25rem;
                text-decoration: none; 
                transition: background-color 0.3s ease;
                font-size: 0.9rem;
            }
        .btn-details:hover {
                background-color: #0056b3;
                text-decoration: none; 

        }
        .alert-no-events { text-align: center; margin-top: 2rem; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card { animation: fadeIn 0.5s ease-out; }

    </style>

<?php include 'header.php'; ?>
    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Events</li>
            </ol>
        </nav>

        <h1 class="mb-4">Upcoming Events</h1>

        <!-- Filters and Sorting -->
        <div class="filter-sort-container">
             <form id="filterForm" action="events.php" method="get" class="row g-3"> <!-- Added id="filterForm" -->
                <div class="col-md-3">
                    <label for="sort">Sort By:</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="date_asc" <?php echo $sort_by === 'date_asc' ? 'selected' : ''; ?>>Date (Ascending)</option>
                        <option value="date_desc" <?php echo $sort_by === 'date_desc' ? 'selected' : ''; ?>>Date (Descending)</option>
                        <option value="title_asc" <?php echo $sort_by === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="title_desc" <?php echo $sort_by === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                        <option value="department_asc" <?php echo $sort_by === 'department_asc' ? 'selected' : ''; ?>>Department (A-Z)</option>
                        <option value="department_desc" <?php echo $sort_by === 'department_desc' ? 'selected' : ''; ?>>Department (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department">Department:</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filter_department === $dept ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="topic">Topic:</label>
                    <select class="form-select" id="topic" name="topic">
                        <option value="">All Topics</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo htmlspecialchars($topic); ?>" <?php echo $filter_topic === $topic ? 'selected' : ''; ?>><?php echo htmlspecialchars($topic); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date">Date:</label>
                    <input type="text" class="form-control" id="date" name="date" placeholder="Select Date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-12">
                    <a href="events.php" class="btn btn-reset">Reset Filters</a>
                </div>
            </form>
        </div>

        <div id="eventsContainer"> <!-- Container for events, for AJAX updates -->
           <?php if (empty($events)): ?>
                <div class="alert alert-info alert-no-events" role="alert">
                    No events found matching your criteria.  Please adjust your filters or check back later!
                </div>
            <?php else: ?>
               <div class="row">
                    <?php foreach ($events as $event): ?>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-img-container">
                                    <img src="<?php echo htmlspecialchars($event['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <p class="event-meta">
                                        <i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($event['short_date_time']); ?><br>
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                    </p>
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
                                     <div class="btn-group-actions">
                                        <button class="btn-favorite <?php echo in_array($event['event_id'], $user_favorites) ? 'active' : ''; ?>" data-event-id="<?php echo $event['event_id']; ?>">
                                             <i class="fas <?php echo in_array($event['event_id'], $user_favorites) ? 'fa-heart' : 'fa-heart'; ?>"></i>
                                        </button>
                                        <button class="btn-schedule <?php echo in_array($event['event_id'], $user_schedules) ? 'active' : ''; ?>" data-event-id="<?php echo $event['event_id']; ?>">
                                            <i class="fas <?php echo in_array($event['event_id'], $user_schedules) ? 'fa-check' : 'fa-plus'; ?>"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer">
                                        <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-details">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>

        flatpickr("#date", {
            dateFormat: "Y-m-d",
             onChange: function(selectedDates, dateStr) {
                if (dateStr) {
                    document.getElementById('date').value = dateStr;
                    fetchEvents(); 
                }
            }
        });

        function fetchEvents() {
            const formData = new FormData(document.getElementById('filterForm'));
            let url = 'events.php?';
            for (var pair of formData.entries()) {

                if (pair[0] !== 'submit') {
                    url += encodeURIComponent(pair[0]) + '=' + encodeURIComponent(pair[1]) + '&';
                }
            }
            url = url.slice(0, -1); 

            fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                document.getElementById('eventsContainer').innerHTML = doc.getElementById('eventsContainer').innerHTML;

                attachButtonListeners();
                 updateFilterDropdowns(doc);
            })
            .catch(error => console.error('Error fetching events:', error));
        }

        function updateFilterDropdowns(doc) {

            const newDepartments = Array.from(doc.querySelectorAll('#department option')).map(opt => ({value: opt.value, text: opt.textContent}));
            const departmentSelect = document.getElementById('department');
            updateDropdownOptions(departmentSelect, newDepartments);

            const newTopics = Array.from(doc.querySelectorAll('#topic option')).map(opt => ({value: opt.value, text: opt.textContent}));
            const topicSelect = document.getElementById('topic');
            updateDropdownOptions(topicSelect, newTopics);

        }

        function updateDropdownOptions(selectElement, newOptions) {
            const currentValue = selectElement.value;  
            selectElement.innerHTML = ''; 

            newOptions.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                selectElement.appendChild(opt);
            });

            selectElement.value = currentValue;

            if(selectElement.selectedIndex === -1){
                selectElement.selectedIndex = 0;
            }
        }

        document.getElementById('sort').addEventListener('change', fetchEvents);
        document.getElementById('department').addEventListener('change', fetchEvents);
        document.getElementById('topic').addEventListener('change', fetchEvents);

       function attachButtonListeners() {

            document.querySelectorAll('.btn-favorite').forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    const isActive = this.classList.contains('active');
                    const icon = this.querySelector('i');

                    this.classList.toggle('active');
                    icon.classList.toggle('fa-heart', !isActive);
                    icon.classList.toggle('fa-heart', isActive);

                    fetch('update_favorites.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `event_id=${eventId}&action=${isActive ? 'remove' : 'add'}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            this.classList.toggle('active');
                            icon.classList.toggle('fa-heart', isActive);
                            icon.classList.toggle('fa-heart', !isActive);
                            console.error("Error updating favorites:", data.message);
                            alert('Error updating favorites. Please try again.');
                        }
                    })
                    .catch(error => {
                        this.classList.toggle('active');
                        icon.classList.toggle('fa-heart', isActive);
                        icon.classList.toggle('fa-heart', !isActive);
                        console.error("Network error:", error);
                        alert('Network error. Please try again.');
                    });
                });
            });

            document.querySelectorAll('.btn-schedule').forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    const isScheduled = this.classList.contains('active');
                    const icon = this.querySelector('i');

                    this.classList.toggle('active');
                    icon.classList.toggle('fa-plus', !isScheduled);
                    icon.classList.toggle('fa-check', isScheduled);

                    fetch('update_schedule.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `event_id=${eventId}&action=${isScheduled ? 'remove' : 'add'}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            this.classList.toggle('active');
                            icon.classList.toggle('fa-plus', isScheduled);
                            icon.classList.toggle('fa-check', !isScheduled);
                            console.error("Error updating schedule:", data.message);
                            alert('Error updating schedule. Please try again.');
                        }
                    })
                    .catch(error => {
                        this.classList.toggle('active');
                        icon.classList.toggle('fa-plus', isScheduled);
                        icon.classList.toggle('fa-check', !isScheduled);
                        console.error("Network error:", error);
                        alert('Network error. Please try again.');
                    });
                });
            });
        }

        attachButtonListeners();
    </script>