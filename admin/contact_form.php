<?php
session_start();


require_once '../config.php';

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

if(isset($_POST['delete']) && isset($_POST['message_id'])) {
    $id = (int)$_POST['message_id'];
    $delete_sql = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $id);

    if($stmt->execute()) {
        $success_message = "Message deleted successfully!";
    } else {
        $error_message = "Error deleting message: " . $conn->error;
    }
    $stmt->close();
}

if(isset($_POST['bulk_delete']) && isset($_POST['selected_messages'])) {
    $selected_ids = $_POST['selected_messages'];
    if(!empty($selected_ids)) {
        $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
        $delete_sql = "DELETE FROM contact_messages WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($delete_sql);

        $param_types = str_repeat('i', count($selected_ids));
        $stmt->bind_param($param_types, ...$selected_ids);

        if($stmt->execute()) {
            $success_message = count($selected_ids) . " messages deleted successfully!";
        } else {
            $error_message = "Error deleting messages: " . $conn->error;
        }
        $stmt->close();
    }
}

if(isset($_POST['mark_read']) && isset($_POST['message_id'])) {
    $id = (int)$_POST['message_id'];
    $update_sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $id);

    if($stmt->execute()) {
        $success_message = "Message marked as read!";
    } else {
        $error_message = "Error updating message: " . $conn->error;
    }
    $stmt->close();
}

$where_clause = "";
if($status_filter != 'all') {
    $where_clause = "WHERE status = '$status_filter'";
}

$count_sql = "SELECT COUNT(*) as total FROM contact_messages $where_clause";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT cm.*, u.username, u.email as user_email 
        FROM contact_messages cm 
        LEFT JOIN users u ON cm.user_id = u.user_id 
        $where_clause
        ORDER BY cm.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>


   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .message-container {
            max-height: 100px;
            overflow-y: auto;
        }

        .status-new {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .status-read {
            background-color: #ffffff;
        }

        .status-replied {
            background-color: #e8f4ff;
        }

        .badge-new {
            background-color: #dc3545;
        }

        .badge-read {
            background-color: #6c757d;
        }

        .badge-replied {
            background-color: #28a745;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        .pagination {
            margin-bottom: 0;
        }

        .filters {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message-preview {
            cursor: pointer;
        }

        .message-details {
            display: none;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin-top: 10px;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .indicator-new {
            background-color: #dc3545;
        }

        .indicator-read {
            background-color: #6c757d;
        }

        .indicator-replied {
            background-color: #28a745;
        }

        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .action-buttons .btn {
            margin-right: 5px;
        }
    </style>


<?php include 'header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-body">
                    <h1 class="card-title">
                        <i class="fas fa-envelope me-2"></i> Contact Messages
                    </h1>
                    <p class="text-muted">Review and manage messages from the contact form</p>

                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Stats Cards -->
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Messages</h5>
                            <?php
                            $total_sql = "SELECT COUNT(*) as count FROM contact_messages";
                            $total_result = $conn->query($total_sql);
                            $total_count = $total_result->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $total_count; ?></h2>
                        </div>
                        <i class="fas fa-envelope fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card text-white bg-danger dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">New Messages</h5>
                            <?php
                            $new_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'";
                            $new_result = $conn->query($new_sql);
                            $new_count = $new_result->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $new_count; ?></h2>
                        </div>
                        <i class="fas fa-bell fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Replied Messages</h5>
                            <?php
                            $replied_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'replied'";
                            $replied_result = $conn->query($replied_sql);
                            $replied_count = $replied_result->fetch_assoc()['count'];
                            ?>
                            <h2><?php echo $replied_count; ?></h2>
                        </div>
                        <i class="fas fa-reply fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="filters">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="get" class="d-flex">
                                    <select name="status" class="form-select me-2">
                                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Messages</option>
                                        <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
                                        <option value="replied" <?php echo $status_filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <button id="toggleBulkDelete" class="btn btn-danger">
                                    <i class="fas fa-trash-alt"></i> Bulk Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <form id="bulkActionForm" method="post">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="bulk-action-column" style="display: none;">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr class="<?php echo 'status-' . $row['status']; ?>">
                                                <td class="bulk-action-column" style="display: none;">
                                                    <input type="checkbox" name="selected_messages[]" value="<?php echo $row['id']; ?>" class="form-check-input message-checkbox">
                                                </td>
                                                <td><?php echo $row['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                    <?php if($row['user_id'] > 0): ?>
                                                        <br><small class="text-muted">User: <?php echo htmlspecialchars($row['username']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td>
                                                    <div class="message-preview" data-message-id="<?php echo $row['id']; ?>">
                                                        <?php echo nl2br(htmlspecialchars(substr($row['message'], 0, 50) . (strlen($row['message']) > 50 ? '...' : ''))); ?>
                                                    </div>
                                                    <div id="message-details-<?php echo $row['id']; ?>" class="message-details">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?php echo htmlspecialchars($row['subject']); ?></h5>
                                                                <h6 class="card-subtitle mb-2 text-muted">From: <?php echo htmlspecialchars($row['name']); ?> (<?php echo htmlspecialchars($row['email']); ?>)</h6>
                                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                                                                <div class="d-flex justify-content-end">
                                                                    <button type="button" class="btn btn-sm btn-secondary close-details">
                                                                        <i class="fas fa-times"></i> Close
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge text-bg-<?php 
                                                        echo $row['status'] == 'new' ? 'danger' : 
                                                            ($row['status'] == 'read' ? 'secondary' : 'success'); 
                                                    ?>">
                                                        <i class="fas fa-<?php 
                                                            echo $row['status'] == 'new' ? 'bell' : 
                                                                ($row['status'] == 'read' ? 'eye' : 'reply'); 
                                                        ?>"></i>
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if($row['status'] == 'new'): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="mark_read" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <a href="mailto:<?php echo $row['email']; ?>?subject=Re: <?php echo htmlspecialchars($row['subject']); ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-reply"></i>
                                                        </a>

                                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-message-id="<?php echo $row['id']; ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No messages found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="bulk-actions" style="display: none;">
                            <button type="submit" name="bulk_delete" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete Selected
                            </button>
                            <button type="button" id="cancelBulk" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>

                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this message? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" id="deleteForm">
                    <input type="hidden" name="message_id" id="messageIdInput" value="">
                    <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!--
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                                -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const messageId = this.getAttribute('data-message-id');
                document.getElementById('messageIdInput').value = messageId;
            });
        });

        const messagePreviews = document.querySelectorAll('.message-preview');
        messagePreviews.forEach(preview => {
            preview.addEventListener('click', function() {
                const messageId = this.getAttribute('data-message-id');
                const detailsElement = document.getElementById(`message-details-${messageId}`);

                document.querySelectorAll('.message-details').forEach(elem => {
                    if(elem.id !== `message-details-${messageId}`) {
                        elem.style.display = 'none';
                    }
                });

                detailsElement.style.display = detailsElement.style.display === 'block' ? 'none' : 'block';
            });
        });

        const closeButtons = document.querySelectorAll('.close-details');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const detailsCard = this.closest('.message-details');
                detailsCard.style.display = 'none';
            });
        });

        const toggleBulkBtn = document.getElementById('toggleBulkDelete');
        const cancelBulkBtn = document.getElementById('cancelBulk');
        const bulkActionColumns = document.querySelectorAll('.bulk-action-column');
        const bulkActionsDiv = document.querySelector('.bulk-actions');
        const selectAllCheckbox = document.getElementById('selectAll');

        toggleBulkBtn.addEventListener('click', function() {
            bulkActionColumns.forEach(col => {
                col.style.display = 'table-cell';
            });
            bulkActionsDiv.style.display = 'block';
            this.style.display = 'none';
        });

        cancelBulkBtn.addEventListener('click', function() {
            bulkActionColumns.forEach(col => {
                col.style.display = 'none';
            });
            bulkActionsDiv.style.display = 'none';
            toggleBulkBtn.style.display = 'inline-block';

            document.querySelectorAll('.message-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
        });

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.message-checkbox').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    });
</script>

