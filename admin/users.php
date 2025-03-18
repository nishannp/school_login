<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once '../config.php';

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-users mr-2"></i>User Management</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="users-table" class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Academic Interest</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <!-- User data will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit mr-2"></i>Edit User</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_username"><i class="fas fa-user mr-1"></i> Username</label>
                            <input type="text" class="form-control rounded-pill" id="edit_username" name="username" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_email"><i class="fas fa-envelope mr-1"></i> Email</label>
                            <input type="email" class="form-control rounded-pill" id="edit_email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_first_name"><i class="fas fa-id-card mr-1"></i> First Name</label>
                            <input type="text" class="form-control rounded-pill" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_last_name"><i class="fas fa-id-card mr-1"></i> Last Name</label>
                            <input type="text" class="form-control rounded-pill" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_phone_number"><i class="fas fa-phone mr-1"></i> Phone Number</label>
                            <input type="text" class="form-control rounded-pill" id="edit_phone_number" name="phone_number">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_academic_interest"><i class="fas fa-graduation-cap mr-1"></i> Academic Interest</label>
                            <input type="text" class="form-control rounded-pill" id="edit_academic_interest" name="academic_interest">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_password"><i class="fas fa-lock mr-1"></i> New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control rounded-pill" id="edit_password" name="password">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary rounded-pill" id="saveUserChanges">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-gradient-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel"><i class="fas fa-exclamation-triangle mr-2"></i>Confirm Delete</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-user-times fa-4x text-danger mb-3"></i>
                    <p class="lead">Are you sure you want to delete this user?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="card bg-light p-3">
                    <p class="mb-1"><strong><i class="fas fa-user mr-1"></i> Username: </strong><span id="delete_username" class="font-weight-bold"></span></p>
                    <input type="hidden" id="delete_user_id">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger rounded-pill" id="confirmDelete">
                    <i class="fas fa-trash mr-1"></i> Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<style>

    .bg-gradient-primary {
        background: linear-gradient(45deg, #4e73df, #2e59d9);
    }

    .bg-gradient-danger {
        background: linear-gradient(45deg, #e74a3b, #c0392b);
    }

    .card {
        border-radius: 10px;
        overflow: hidden;
    }

    #users-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    #users-table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        border-top: none;
    }

    .btn-action {
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 5px 15px;
        margin: 0 2px;
        transition: all 0.3s;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: none;
        padding: 15px 20px;
    }

    .modal-body {
        padding: 25px;
    }

    .modal-footer {
        border-top: none;
        padding: 15px 20px;
    }

    .form-control {
        border: 1px solid #e3e6f0;
        padding: 12px 20px;
        height: auto;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    label {
        font-weight: 600;
        font-size: 13px;
        color: #5a5c69;
        margin-bottom: 8px;
    }
</style>

<!-- Add the JavaScript for handling the user management -->
<script>
    $(document).ready(function() {

        $('.modal .close, .btn[data-dismiss="modal"]').on('click', function() {
            $(this).closest('.modal').modal('hide');
        });

        loadUsers();

        function loadUsers() {
            $.ajax({
                url: 'get_users.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    let tableContent = '';

                    if (data.length === 0) {
                        tableContent = '<tr><td colspan="9" class="text-center">No users found</td></tr>';
                    } else {
                        $.each(data, function(index, user) {
                            tableContent += `
                                <tr data-user-id="${user.user_id}">
                                    <td>${user.user_id}</td>
                                    <td>${user.username}</td>
                                    <td>${user.email}</td>
                                    <td>${user.first_name} ${user.last_name}</td>
                                    <td>${user.phone_number || '-'}</td>
                                    <td>${user.academic_interest || '-'}</td>
                                    <td>${formatDate(user.account_created_at)}</td>
                                    <td>${user.last_login ? formatDate(user.last_login) : 'Never'}</td>
                                    <td class="text-center">
                                        <button class="btn btn-info btn-sm btn-action edit-user" data-user-id="${user.user_id}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-action delete-user" data-user-id="${user.user_id}" data-username="${user.username}">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }

                    $('#users-table-body').html(tableContent);

                    if ($.fn.DataTable.isDataTable('#users-table')) {
                        $('#users-table').DataTable().destroy();
                    }

                    $('#users-table').DataTable({
                        "order": [[0, "desc"]],
                        "pageLength": 10,
                        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        "language": {
                            "search": "<i class='fas fa-search'></i> _INPUT_",
                            "searchPlaceholder": "Search users...",
                            "lengthMenu": "<i class='fas fa-list'></i> _MENU_ users per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ users",
                            "paginate": {
                                "first": "<i class='fas fa-angle-double-left'></i>",
                                "last": "<i class='fas fa-angle-double-right'></i>",
                                "next": "<i class='fas fa-angle-right'></i>",
                                "previous": "<i class='fas fa-angle-left'></i>"
                            }
                        },
                        "dom": '<"top"lf>rt<"bottom"ip>',
                        "drawCallback": function() {
                            $('.dataTables_paginate .paginate_button').addClass('btn btn-sm');
                        }
                    });

                    $('.dataTables_filter input').addClass('form-control-sm rounded-pill');
                    $('.dataTables_length select').addClass('form-control-sm rounded-pill');
                },
                error: function(xhr, status, error) {
                    console.error("Error loading users:", error);
                    alert("Failed to load users. Please try again.");
                }
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        $(document).on('click', '.edit-user', function() {
            const userId = $(this).data('user-id');

            $.ajax({
                url: 'get_user.php',
                type: 'GET',
                data: { user_id: userId },
                dataType: 'json',
                success: function(user) {

                    $('#edit_user_id').val(user.user_id);
                    $('#edit_username').val(user.username);
                    $('#edit_email').val(user.email);
                    $('#edit_first_name').val(user.first_name);
                    $('#edit_last_name').val(user.last_name);
                    $('#edit_phone_number').val(user.phone_number);
                    $('#edit_academic_interest').val(user.academic_interest);
                    $('#edit_password').val(''); 

                    $('#editUserModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching user data:", error);
                    alert("Failed to load user data. Please try again.");
                }
            });
        });

        $('#saveUserChanges').click(function() {
            const formData = $('#editUserForm').serialize();

            $.ajax({
                url: 'update_user.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {

                        $('#editUserModal').modal('hide');

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'User updated successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert("User updated successfully!");
                        }

                        loadUsers();
                    } else {

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        } else {
                            alert("Error: " + response.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error updating user:", error);
                    alert("Failed to update user. Please try again.");
                }
            });
        });

        $(document).on('click', '.delete-user', function() {
            const userId = $(this).data('user-id');
            const username = $(this).data('username');

            $('#delete_user_id').val(userId);
            $('#delete_username').text(username);

            $('#deleteUserModal').modal('show');
        });

        $('#confirmDelete').click(function() {
            const userId = $('#delete_user_id').val();

            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {

                        $('#deleteUserModal').modal('hide');

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'User deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert("User deleted successfully!");
                        }

                        loadUsers();
                    } else {

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        } else {
                            alert("Error: " + response.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting user:", error);
                    alert("Failed to delete user. Please try again.");
                }
            });
        });
    });
</script>

<?php

include 'footer.php';
?>