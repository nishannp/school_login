<?php

require_once '../config.php';

$pageTitle = "Manage Events";


// Delete Event
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = $_GET['delete'];

    // Get image paths before deleting the event
    $imageQuery = "SELECT event_image_original, event_image_resized FROM events WHERE event_id = ?";
    $stmtImage = $conn->prepare($imageQuery);
    $stmtImage->bind_param("i", $deleteId);
    $stmtImage->execute();
    $imageResult = $stmtImage->get_result();

    if ($imageRow = $imageResult->fetch_assoc()) {
        // Delete original image if it exists
        if (!empty($imageRow['event_image_original']) && file_exists($imageRow['event_image_original'])) {
            unlink($imageRow['event_image_original']);
        }
        // Delete resized image if it exists
        if (!empty($imageRow['event_image_resized']) && file_exists($imageRow['event_image_resized'])) {
            unlink($imageRow['event_image_resized']);
        }
    }

    // Delete the event from the database
    $deleteQuery = "DELETE FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $deleteId);

    if ($stmt->execute()) {
        $deleteSuccess = "Event successfully deleted!";
    } else {
        $deleteError = "Error deleting event: " . $conn->error;
    }
}

// Sorting and Ordering
$orderBy = isset($_GET['orderby']) ? $_GET['orderby'] : 'event_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$validColumns = ['event_id', 'title', 'event_date', 'location', 'department', 'estimated_participants', 'created_at', 'admin_id']; // Added admin_id

if (!in_array($orderBy, $validColumns)) {
    $orderBy = 'event_date';
}
if ($order != 'ASC' && $order != 'DESC') {
    $order = 'ASC';
}

// Searching
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " WHERE title LIKE ? OR description LIKE ? OR location LIKE ? OR department LIKE ? OR topic LIKE ? OR tag LIKE ?";
    $searchTerm = "%$search%";
    $searchParams = array_fill(0, 6, $searchTerm);  // 6 placeholders for the 6 columns in the search
}

// Department Filtering
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';
if (!empty($departmentFilter)) {
    $searchCondition = empty($searchCondition) ? " WHERE department = ?" : $searchCondition . " AND department = ?";
    $searchParams[] = $departmentFilter;
}

// Pagination
$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

// Count total records (for pagination)
$countSql = "SELECT COUNT(*) as total FROM events" . $searchCondition;
$countStmt = $conn->prepare($countSql);

// Bind search parameters for the count query
if (!empty($searchParams)) {
    $types = str_repeat('s', count($searchParams));
    $countStmt->bind_param($types, ...$searchParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get distinct departments for the filter dropdown
$deptQuery = "SELECT DISTINCT department FROM events WHERE department IS NOT NULL AND department != '' ORDER BY department";
$deptResult = $conn->query($deptQuery);
$departments = [];
while ($deptRow = $deptResult->fetch_assoc()) {
    $departments[] = $deptRow['department'];
}

// Fetch events with JOIN to get admin's username
$sql = "SELECT events.*, admins.username AS admin_username FROM events 
        INNER JOIN admins ON events.admin_id = admins.admin_id" .
       $searchCondition .
       " ORDER BY $orderBy $order LIMIT ?, ?";  // Include search condition
$stmt = $conn->prepare($sql);


$bindParams = $searchParams;
$bindParams[] = $offset;
$bindParams[] = $recordsPerPage;

$types = str_repeat('s', count($searchParams)) . 'ii';
$stmt->bind_param($types, ...$bindParams);

$stmt->execute();
$result = $stmt->get_result();
include_once 'header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Events</h1>

    <?php if (isset($deleteSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $deleteSuccess; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($deleteError)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $deleteError; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Event List</h6>
            <a href="add-event.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Event
            </a>
        </div>
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form id="searchForm" class="d-flex" method="get" action="">
                        <input type="hidden" name="orderby" value="<?php echo $orderBy; ?>">
                        <input type="hidden" name="order" value="<?php echo $order; ?>">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search events..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        <select id="departmentFilter" class="form-select me-2" style="max-width: 200px;">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="clearFilters" class="btn btn-outline-secondary">Clear Filters</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="eventsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                    <tr>
                        <th class="sortable" data-column="event_id">
                            ID
                            <?php if ($orderBy == 'event_id'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th class="sortable" data-column="title">
                            Title
                            <?php if ($orderBy == 'title'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th class="sortable" data-column="event_date">
                            Date
                            <?php if ($orderBy == 'event_date'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th>Time</th>
                        <th class="sortable" data-column="location">
                            Location
                            <?php if ($orderBy == 'location'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th class="sortable" data-column="department">
                            Department
                            <?php if ($orderBy == 'department'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th class="sortable" data-column="estimated_participants">
                            Participants
                            <?php if ($orderBy == 'estimated_participants'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th class="sortable" data-column="admin_id">  <!-- Added Admin Username Column -->
                            Admin
                            <?php if ($orderBy == 'admin_id'): ?>
                                <i class="fas fa-sort-<?php echo $order == 'ASC' ? 'up' : 'down'; ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-sort"></i>
                            <?php endif; ?>
                        </th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['event_id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($row['event_image_resized'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['event_image_resized']); ?>"
                                                 class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;"
                                                 alt="Event Image">
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <?php if (!empty($row['tag'])): ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($row['tag']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo $row['estimated_participants']; ?></td>
                                <td><?php echo htmlspecialchars($row['admin_username']); ?></td> <!-- Display Admin Username -->
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="../event_details.php?id=<?php echo $row['event_id']; ?>"
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-event.php?id=<?php echo $row['event_id']; ?>"
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-event"
                                                data-id="<?php echo $row['event_id']; ?>"
                                                data-title="<?php echo htmlspecialchars($row['title']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No events found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="<?php echo "?page=" . ($page - 1) . "&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter"; ?>">Previous</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link"
                                   href="<?php echo "?page=$i&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter"; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="<?php echo "?page=" . ($page + 1) . "&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter"; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete "<span id="eventTitle"></span>"? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sorting
        const sortableHeaders = document.querySelectorAll('.sortable');
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                const currentOrderBy = '<?php echo $orderBy; ?>';
                const currentOrder = '<?php echo $order; ?>';

                let newOrder = 'ASC';
                if (column === currentOrderBy && currentOrder === 'ASC') {
                    newOrder = 'DESC';
                }

                const searchParams = new URLSearchParams(window.location.search);
                searchParams.set('orderby', column);
                searchParams.set('order', newOrder);
                // No need to set 'page' here.  Keep the current page.

                updateTableWithoutRefresh(searchParams.toString());
            });
        });

        // Department Filter Change
        const departmentFilter = document.getElementById('departmentFilter');
        departmentFilter.addEventListener('change', function() {
            const department = this.value;
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('department', department);
            searchParams.set('page', 1); // Reset to page 1 on filter change

            updateTableWithoutRefresh(searchParams.toString());
        });

        // Clear Filters
        document.getElementById('clearFilters').addEventListener('click', function() {
            const searchParams = new URLSearchParams();
            searchParams.set('orderby', 'event_date'); // Reset to default sorting
            searchParams.set('order', 'ASC');

            // Clear search input and department selection
            document.querySelector('input[name="search"]').value = '';
            document.getElementById('departmentFilter').value = '';

            updateTableWithoutRefresh(searchParams.toString()); // Update the table

        });

        // Search Form Submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
            const searchValue = document.querySelector('input[name="search"]').value;
            const orderby = document.querySelector('input[name="orderby"]').value;  //get current orderby
            const order = document.querySelector('input[name="order"]').value;     //get current order
            const department = document.getElementById('departmentFilter').value;

            const searchParams = new URLSearchParams();
            searchParams.set('search', searchValue);
            searchParams.set('orderby', orderby);  //keep order by
            searchParams.set('order', order);     // keep order
            searchParams.set('department', department);
            searchParams.set('page', 1); // Reset to page 1 on search

            updateTableWithoutRefresh(searchParams.toString());
        });

        // Delete Event Button (Modal Trigger)
        const deleteButtons = document.querySelectorAll('.delete-event');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                const eventTitle = this.getAttribute('data-title');

                document.getElementById('eventTitle').textContent = eventTitle;
                document.getElementById('confirmDelete').href = 'events.php?delete=' + eventId;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Function to update the table content and pagination via AJAX
        function updateTableWithoutRefresh(queryParams) {
            // Update the URL in the browser's address bar
            window.history.pushState({}, '', 'events.php?' + queryParams);


            fetch('events-ajax.php?' + queryParams)  //Using events-ajax.php
                .then(response => response.json())
                .then(data => {
                    // Update the table body with the new HTML
                    const tableBody = document.querySelector('#eventsTable tbody');
                    tableBody.innerHTML = data.tableHtml;

                    // Update pagination controls
                    const paginationContainer = document.querySelector('.pagination');
                    if (paginationContainer) {
                        paginationContainer.innerHTML = data.paginationHtml;
                    }

                    // Re-initialize event listeners for dynamically added elements (delete buttons)
                    reinitializeEventListeners();
                    //Update sort icons.
                    updateSortIcons(data.orderBy, data.order);

                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        }


        // Function to reinitialize event listeners (for delete buttons)
        function reinitializeEventListeners() {
            // Delete Event Button (Modal Trigger) *inside reinitializeEventListeners*
            const deleteButtons = document.querySelectorAll('.delete-event');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-id');
                    const eventTitle = this.getAttribute('data-title');

                    document.getElementById('eventTitle').textContent = eventTitle;
                    document.getElementById('confirmDelete').href = 'events.php?delete=' + eventId;

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });
        }

        function updateSortIcons(orderBy, order) {
            const sortableHeaders = document.querySelectorAll('.sortable');
            sortableHeaders.forEach(header => {
                const column = header.getAttribute('data-column');
                const iconElement = header.querySelector('i');

                // Set the icon based on current sorting
                if (column === orderBy) {
                    iconElement.className = 'fas fa-sort-' + (order === 'ASC' ? 'up' : 'down');
                } else {
                    iconElement.className = 'fas fa-sort'; // Default sort icon
                }
            });
        }
    });
</script>

<?php

include_once 'footer.php';
?>