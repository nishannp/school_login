<?php
ini_set('display_errors', 0); //We will  Turn off error display in production
ini_set('display_startup_errors', 0);
error_reporting(0);


require_once '../config.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

$success_message = '';
$error_message = '';

if (!$conn) {
    $error_message = "Database connection failed: " . mysqli_connect_error();
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $phone_number = trim($_POST['phone_number']);
            $role = $_POST['role'];

            if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($role)) {
                $error_message = "All fields marked with * are required.";
            } else {
                $check_query = "SELECT * FROM admins WHERE username = ? OR email = ?";
                $stmt = mysqli_prepare($conn, $check_query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
                    mysqli_stmt_execute($stmt);
                    $check_result = mysqli_stmt_get_result($stmt);
                    if (mysqli_num_rows($check_result) > 0) {
                        $error_message = "Username or email already exists.";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $insert_query = "INSERT INTO admins (username, email, password_hash, full_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt2 = mysqli_prepare($conn, $insert_query);
                        if ($stmt2) {
                            mysqli_stmt_bind_param($stmt2, "ssssss", $username, $email, $password_hash, $full_name, $phone_number, $role);
                            if (mysqli_stmt_execute($stmt2)) {
                                $success_message = "Admin created successfully!";
                            } else {
                                $error_message = "Error creating admin: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmt2);
                        } else {
                            $error_message = "Error preparing insert statement: " . mysqli_error($conn);
                        }
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = "Error preparing check statement: " . mysqli_error($conn);
                }
            }
        } elseif ($action === 'update') {
            $admin_id = $_POST['admin_id'];
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $phone_number = trim($_POST['phone_number']);
            $role = $_POST['role'];

            if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
                $error_message = "All fields marked with * are required.";
            } else {
                $check_query = "SELECT * FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?";
                $stmt = mysqli_prepare($conn, $check_query);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $admin_id);
                    mysqli_stmt_execute($stmt);
                    $check_result = mysqli_stmt_get_result($stmt);
                    if (mysqli_num_rows($check_result) > 0) {
                        $error_message = "Username or email already exists for another admin.";
                    } else {
                        $update_query = "UPDATE admins SET username = ?, email = ?, full_name = ?, phone_number = ?, role = ? WHERE admin_id = ?";
                        $stmt2 = mysqli_prepare($conn, $update_query);
                        if ($stmt2) {
                            mysqli_stmt_bind_param($stmt2, "sssssi", $username, $email, $full_name, $phone_number, $role, $admin_id);
                            if (mysqli_stmt_execute($stmt2)) {
                                $success_message = "Admin updated successfully!";
                            } else {
                                $error_message = "Error updating admin: " . mysqli_error($conn);
                            }
                            mysqli_stmt_close($stmt2);
                        } else {
                            $error_message = "Error preparing update statement: " . mysqli_error($conn);
                        }
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = "Error preparing check statement: " . mysqli_error($conn);
                }
            }
        } elseif ($action === 'delete') {
            if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
                $admin_id = $_POST['admin_id'];
                if (!is_numeric($admin_id)) {
                    $error_message = "Invalid admin ID.";
                } elseif ($admin_id == $_SESSION['admin_id']) {
                    $error_message = "You cannot delete your own account.";
                } else {
                    $delete_query = "DELETE FROM admins WHERE admin_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_query);
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $admin_id);
                        if (mysqli_stmt_execute($stmt)) {
                            $success_message = "Admin deleted successfully!";
                        } else {
                            $error_message = "Error deleting admin: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error_message = "Error preparing delete statement: " . mysqli_error($conn);
                    }
                }
            } else {
                $error_message = "admin_id is not set or is empty.";
            }
        } elseif ($action === 'change_password') {
            $admin_id = $_POST['admin_id'];
            $new_password = $_POST['new_password'];

            if (empty($new_password)) {
                $error_message = "New password is required.";
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE admins SET password_hash = ? WHERE admin_id = ?";
                $stmt = mysqli_prepare($conn, $update_query);

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "si", $password_hash, $admin_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $success_message = "Password updated successfully!";
                    } else {
                        $error_message = "Error updating password: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = "Error preparing statement: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<?php include 'header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-users-cog"></i> Admin Management
            </h1>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="admin-list-tab" data-bs-toggle="tab"
                                data-bs-target="#admin-list" type="button" role="tab" aria-controls="admin-list"
                                aria-selected="true">
                                <i class="fas fa-list"></i> Admin List
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="create-admin-tab" data-bs-toggle="tab"
                                data-bs-target="#create-admin" type="button" role="tab" aria-controls="create-admin"
                                aria-selected="false">
                                <i class="fas fa-user-plus"></i> Create Admin
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="adminTabsContent">
                        <!-- Admin List Tab -->
                        <div class="tab-pane fade show active" id="admin-list" role="tabpanel"
                            aria-labelledby="admin-list-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="adminTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Full Name</th>
                                            <th>Role</th>
                                            <th>Created At</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM admins ORDER BY admin_id DESC";
                                        $result = mysqli_query($conn, $query);

                                        while ($admin = mysqli_fetch_assoc($result)) {
                                            echo "<tr" . ($admin['admin_id'] == $_SESSION['admin_id'] ? " class='table-primary'" : "") . ">";
                                            echo "<td>{$admin['admin_id']}</td>";
                                            echo "<td>{$admin['username']}</td>";
                                            echo "<td>{$admin['email']}</td>";
                                            echo "<td>{$admin['full_name']}</td>";
                                            echo "<td>";
                                            switch ($admin['role']) {
                                                case 'super_admin':
                                                    echo "<span class='badge bg-danger'>Super Admin</span>";
                                                    break;
                                                case 'event_manager':
                                                    echo "<span class='badge bg-success'>Event Manager</span>";
                                                    break;
                                                case 'moderator':
                                                    echo "<span class='badge bg-info'>Moderator</span>";
                                                    break;
                                                default:
                                                    echo "<span class='badge bg-secondary'>{$admin['role']}</span>";
                                            }
                                            echo "</td>";
                                            echo "<td>" . date('Y-m-d', strtotime($admin['account_created_at'])) . "</td>";
                                            echo "<td>" . (!empty($admin['last_login']) ? date('Y-m-d H:i', strtotime($admin['last_login'])) : "Never") . "</td>";
                                            echo "<td>";
                                            echo "<div class='btn-group'>";
                                            echo "<button type='button' class='btn btn-sm btn-primary edit-admin' data-id='{$admin['admin_id']}' data-bs-toggle='modal' data-bs-target='#editAdminModal'><i class='fas fa-edit'></i></button>";
                                            echo "<button type='button' class='btn btn-sm btn-warning change-password' data-id='{$admin['admin_id']}' data-bs-toggle='modal' data-bs-target='#changePasswordModal'><i class='fas fa-key'></i></button>";

                                            if ($admin['admin_id'] != $_SESSION['admin_id']) {
                                                echo "<button type='button' class='btn btn-sm btn-danger delete-admin' data-id='{$admin['admin_id']}' data-username='{$admin['username']}' data-bs-toggle='modal' data-bs-target='#deleteAdminModal'><i class='fas fa-trash'></i></button>";
                                            }
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Create Admin Tab -->
                        <div class="tab-pane fade" id="create-admin" role="tabpanel" aria-labelledby="create-admin-tab">
                            <div class="card-body">
                                <form id="createAdminForm" method="POST" action="">
                                    <input type="hidden" name="action" value="create">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" class="form-control" id="username" name="username"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-envelope"></i></span>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    <input type="password" class="form-control" id="password"
                                                        name="password" required>
                                                    <button class="btn btn-outline-secondary toggle-password"
                                                        type="button"><i class="fas fa-eye"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="role" class="form-label">Role *</label>
                                                <select class="form-select" id="role" name="role" required>
                                                    <option value="" disabled selected>Select a role</option>
                                                    <option value="super_admin">Super Admin</option>
                                                    <option value="event_manager">Event Manager</option>
                                                    <option value="moderator">Moderator</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="full_name" class="form-label">Full Name *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                    <input type="text" class="form-control" id="full_name"
                                                        name="full_name" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="phone_number" class="form-label">Phone Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    <input type="tel" class="form-control" id="phone_number"
                                                        name="phone_number">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-user-plus"></i> Create Admin
                                            </button>
                                            <button type="reset" class="btn btn-secondary">
                                                <i class="fas fa-undo"></i> Reset Form
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editAdminModalLabel"><i class="fas fa-edit"></i> Edit Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit_admin_id" name="admin_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="edit_username" name="username" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_full_name" class="form-label">Full Name *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="edit_full_name" name="full_name"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone_number" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="edit_phone_number" name="phone_number">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_role" class="form-label">Role *</label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="event_manager">Event Manager</option>
                                    <option value="moderator">Moderator</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="changePasswordModalLabel"><i class="fas fa-key"></i> Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" id="password_admin_id" name="admin_id">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Changing password for: <strong
                            id="password_admin_username"></strong>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button"><i
                                    class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAdminModalLabel"><i class="fas fa-trash"></i> Delete Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteAdminForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete_admin_id" name="admin_id">

                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Are you sure you want to delete the admin <strong
                            id="delete_admin_username"></strong>? This action cannot be undone!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
    $('#adminTable').DataTable({
        order: [
            [0, 'desc']
        ],
        responsive: true,
        columnDefs: [{
            orderable: false,
            targets: -1
        }]
    });

    $('.toggle-password').click(function() {
        const passwordInput = $(this).siblings('input');
        const icon = $(this).find('i');

        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type') = 'password';
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('.edit-admin').click(function() {
        const adminId = $(this).data('id');

        $.ajax({
            url: 'get_admin_data.php',
            type: 'POST',
            dataType: 'json',
            data: {
                admin_id: adminId
            },
            success: function(response) {
                $('#edit_admin_id').val(response.admin_id);
                $('#edit_username').val(response.username);
                $('#edit_email').val(response.email);
                $('#edit_full_name').val(response.full_name);
                $('#edit_phone_number').val(response.phone_number);
                $('#edit_role').val(response.role);
            },
            error: function(xhr, status, error) {
                alert('Error fetching admin data. Please try again.');
            }
        });
    });

    $('.change-password').click(function() {
        const adminId = $(this).data('id');
        const row = $(this).closest('tr');
        const username = row.find('td:eq(1)').text();

        $('#password_admin_id').val(adminId);
        $('#password_admin_username').text(username);
    });

    $('.delete-admin').click(function() {
        const adminId = $(this).data('id');
        const username = $(this).data('username');


        if (typeof adminId === 'undefined' || adminId === null || adminId === '') {
            alert("Error: Invalid Admin ID.  Please try again.");
            return;
        }

        $('#delete_admin_id').val(adminId);
        $('#delete_admin_username').text(username);
    });

    $('#createAdminForm').submit(function(e) {
        const password = $('#password').val();

        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }

        return true;
    });

    const hash = window.location.hash;
    if (hash) {
        $(`a[href="${hash}"]`).tab('show');
    }

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        window.location.hash = e.target.hash;
    });
});
</script>

<?php include 'footer.php'; ?>