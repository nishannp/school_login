<?php

require_once '../config.php';


// Sorting and Ordering
$orderBy = isset($_GET['orderby']) ? $_GET['orderby'] : 'event_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$validColumns = ['event_id', 'title', 'event_date', 'location', 'department', 'estimated_participants', 'created_at', 'admin_id']; // Include admin_id

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
    $searchCondition = " WHERE (title LIKE ? OR description LIKE ? OR location LIKE ? OR department LIKE ? OR topic LIKE ? OR tag LIKE ?)"; // Added parentheses
    $searchTerm = "%$search%";
    $searchParams = array_fill(0, 6, $searchTerm); // 6 placeholders
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

// Bind search parameters for count query
if (!empty($searchParams)) {
    $types = str_repeat('s', count($searchParams));
    $countStmt->bind_param($types, ...$searchParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);
$countStmt->close(); // Close the count statement


// Fetch events with JOIN for admin username
$sql = "SELECT events.*, admins.username AS admin_username FROM events 
        INNER JOIN admins ON events.admin_id = admins.admin_id" .
        $searchCondition .
        " ORDER BY $orderBy $order LIMIT ?, ?";
$stmt = $conn->prepare($sql);

$bindParams = $searchParams;
$bindParams[] = $offset;
$bindParams[] = $recordsPerPage;

$types = str_repeat('s', count($searchParams)) . 'ii';
$stmt->bind_param($types, ...$bindParams);
$stmt->execute();
$result = $stmt->get_result();



// Generate table HTML
$tableHtml = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tableHtml .= '<tr>';
        $tableHtml .= '<td>' . $row['event_id'] . '</td>';
        $tableHtml .= '<td>';
        $tableHtml .= '<div class="d-flex align-items-center">';
        if (!empty($row['event_image_resized'])) {
            $tableHtml .= '<img src="' . htmlspecialchars($row['event_image_resized']) . '" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;" alt="Event Image">';
        }
        $tableHtml .= '<div>';
        $tableHtml .= '<div class="fw-bold">' . htmlspecialchars($row['title']) . '</div>';
        if (!empty($row['tag'])) {
            $tableHtml .= '<span class="badge bg-primary">' . htmlspecialchars($row['tag']) . '</span>';
        }
        $tableHtml .= '</div>';
        $tableHtml .= '</div>';
        $tableHtml .= '</td>';
        $tableHtml .= '<td>' . date('M d, Y', strtotime($row['event_date'])) . '</td>';
        $tableHtml .= '<td>' . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . '</td>';
        $tableHtml .= '<td>' . htmlspecialchars($row['location']) . '</td>';
        $tableHtml .= '<td>' . htmlspecialchars($row['department']) . '</td>';
        $tableHtml .= '<td>' . $row['estimated_participants'] . '</td>';
        $tableHtml .= '<td>' . htmlspecialchars($row['admin_username']) . '</td>'; // Display admin username
        $tableHtml .= '<td>';
        $tableHtml .= '<div class="btn-group" role="group">';
        $tableHtml .= '<a href="../event_details.php?id=' . $row['event_id'] . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>';
        $tableHtml .= '<a href="edit-event.php?id=' . $row['event_id'] . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>';
        $tableHtml .= '<button type="button" class="btn btn-danger btn-sm delete-event" data-id="' . $row['event_id'] . '" data-title="' . htmlspecialchars($row['title']) . '"><i class="fas fa-trash"></i></button>';
        $tableHtml .= '</div>';
        $tableHtml .= '</td>';
        $tableHtml .= '</tr>';
    }
} else {
    $tableHtml .= '<tr><td colspan="9" class="text-center">No events found</td></tr>'; // Correct colspan
}

// Generate pagination HTML (Corrected)
$paginationHtml = '';
if ($totalPages > 1) {
    $paginationHtml .= '<nav aria-label="Page navigation">';
    $paginationHtml .= '<ul class="pagination justify-content-center">';
    $paginationHtml .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
    $paginationHtml .= '<a class="page-link" href="?page=' . ($page - 1) . "&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter\">Previous</a>";
    $paginationHtml .= '</li>';

    for ($i = 1; $i <= $totalPages; $i++) {
        $paginationHtml .= '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
        $paginationHtml .= '<a class="page-link" href="?page=' . $i . "&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter\">" . $i . "</a>";
        $paginationHtml .= '</li>';
    }
    $paginationHtml .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">';
    $paginationHtml .= '<a class="page-link" href="?page=' . ($page + 1) . "&orderby=$orderBy&order=$order&search=$search&department=$departmentFilter\">Next</a>";
    $paginationHtml .= '</li>';
    $paginationHtml .= '</ul>';
    $paginationHtml .= '</nav>';
}

$stmt->close(); //close the statement.

// Prepare data to be sent as JSON
$data = [
    'tableHtml' => $tableHtml,
    'paginationHtml' => $paginationHtml,
    'orderBy' => $orderBy,        // Returning orderBy
    'order' => $order,           // Returning order
    'totalPages' => $totalPages,   // Returning these for consistency (optional)
    'currentPage' => $page       // Returning these for consistency (optional)
];

// Set response header to JSON and output the data
header('Content-Type: application/json');
echo json_encode($data);
exit;

?>