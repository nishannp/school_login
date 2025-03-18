<?php

session_start();
require_once '../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

include 'header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faq'])) {
    $question = mysqli_real_escape_string($conn, trim($_POST['question']));
    $answer = mysqli_real_escape_string($conn, trim($_POST['answer']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if (empty($question) || empty($answer) || empty($category)) {
        $message = "All fields are required";
        $messageType = "danger";
    } else {
        $query = "INSERT INTO faqs (question, answer, category, is_published) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $question, $answer, $category, $isPublished);

        if (mysqli_stmt_execute($stmt)) {
            $message = "FAQ added successfully";
            $messageType = "success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faq'])) {
    $faqId = mysqli_real_escape_string($conn, $_POST['faq_id']);
    $question = mysqli_real_escape_string($conn, trim($_POST['question']));
    $answer = mysqli_real_escape_string($conn, trim($_POST['answer']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if (empty($question) || empty($answer) || empty($category)) {
        $message = "All fields are required";
        $messageType = "danger";
    } else {
        $query = "UPDATE faqs SET question = ?, answer = ?, category = ?, is_published = ? WHERE faq_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssii", $question, $answer, $category, $isPublished, $faqId);

        if (mysqli_stmt_execute($stmt)) {
            $message = "FAQ updated successfully";
            $messageType = "success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_faq'])) {
    $faqId = mysqli_real_escape_string($conn, $_POST['faq_id']);

    $query = "DELETE FROM faqs WHERE faq_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $faqId);

    if (mysqli_stmt_execute($stmt)) {
        $message = "FAQ deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

$query = "SELECT * FROM faqs ORDER BY category, faq_id DESC";
$result = mysqli_query($conn, $query);
$faqs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $faqs[] = $row;
    }
}

$query = "SELECT * FROM user_questions ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$userQuestions = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $userQuestions[] = $row;
    }
}

$query = "SELECT DISTINCT category FROM faqs ORDER BY category";
$result = mysqli_query($conn, $query);
$categories = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['category'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - FAQ Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .admin-container {
            padding: 30px 0;
        }
        .card {
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .form-check {
            padding-left: 0;
        }
        .tab-content {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.25rem 0.25rem;
        }
        .badge-published {
            background-color: #28a745;
        }
        .badge-unpublished {
            background-color: #6c757d;
        }
        textarea {
            min-height: 150px;
        }
    </style>
</head>
<body>
    <div class="container admin-container">
        <h1 class="mb-4">FAQ Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="faqs-tab" data-toggle="tab" href="#faqs" role="tab">Manage FAQs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="add-tab" data-toggle="tab" href="#add" role="tab">Add New FAQ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="questions-tab" data-toggle="tab" href="#questions" role="tab">User Questions 
                    <?php if (count($userQuestions) > 0): ?>
                        <span class="badge badge-danger"><?php echo count($userQuestions); ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Manage FAQs Tab -->
            <div class="tab-pane fade show active" id="faqs" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All FAQs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Question</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($faqs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No FAQs found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($faqs as $faq): ?>
                                            <tr>
                                                <td><?php echo $faq['faq_id']; ?></td>
                                                <td><?php echo htmlspecialchars(substr($faq['question'], 0, 50)) . (strlen($faq['question']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo htmlspecialchars($faq['category']); ?></td>
                                                <td>
                                                    <?php if ($faq['is_published']): ?>
                                                        <span class="badge badge-published">Published</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-unpublished">Unpublished</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-info edit-faq" 
                                                            data-id="<?php echo $faq['faq_id']; ?>"
                                                            data-question="<?php echo htmlspecialchars($faq['question']); ?>"
                                                            data-answer="<?php echo htmlspecialchars($faq['answer']); ?>"
                                                            data-category="<?php echo htmlspecialchars($faq['category']); ?>"
                                                            data-published="<?php echo $faq['is_published']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-faq" data-id="<?php echo $faq['faq_id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add New FAQ Tab -->
            <div class="tab-pane fade" id="add" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add New FAQ</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="question">Question</label>
                                <input type="text" class="form-control" id="question" name="question" required>
                            </div>

                            <div class="form-group">
                                <label for="answer">Answer</label>
                                <textarea class="form-control" id="answer" name="answer" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" class="form-control" id="category" name="category" list="categoryList" required>
                                <datalist id="categoryList">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="form-text text-muted">Enter existing category or create a new one</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="isPublished" name="is_published" checked>
                                    <label class="custom-control-label" for="isPublished">Publish immediately</label>
                                </div>
                            </div>

                            <button type="submit" name="add_faq" class="btn btn-success">Add FAQ</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Questions Tab -->
            <div class="tab-pane fade" id="questions" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">User Submitted Questions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userQuestions)): ?>
                            <div class="alert alert-info">No questions submitted by users yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Question</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($userQuestions as $question): ?>
                                            <tr>
                                                <td><?php echo $question['question_id']; ?></td>
                                                <td><?php echo htmlspecialchars($question['name']); ?></td>
                                                <td><?php echo htmlspecialchars($question['email']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($question['question'], 0, 50)) . (strlen($question['question']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($question['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success convert-to-faq" data-question="<?php echo htmlspecialchars($question['question']); ?>">
                                                        <i class="fas fa-plus"></i> Create FAQ
                                                    </button>
                                                    <button class="btn btn-sm btn-warning view-question" 
                                                           data-id="<?php echo $question['question_id']; ?>"
                                                           data-name="<?php echo htmlspecialchars($question['name']); ?>"
                                                           data-email="<?php echo htmlspecialchars($question['email']); ?>"
                                                           data-question="<?php echo htmlspecialchars($question['question']); ?>"
                                                           data-date="<?php echo date('M d, Y', strtotime($question['created_at'])); ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div class="modal fade" id="editFaqModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Edit FAQ</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="editFaqForm">
                        <input type="hidden" name="faq_id" id="edit_faq_id">

                        <div class="form-group">
                            <label for="edit_question">Question</label>
                            <input type="text" class="form-control" id="edit_question" name="question" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_answer">Answer</label>
                            <textarea class="form-control" id="edit_answer" name="answer" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_category">Category</label>
                            <input type="text" class="form-control" id="edit_category" name="category" list="editCategoryList" required>
                            <datalist id="editCategoryList">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="edit_is_published" name="is_published">
                                <label class="custom-control-label" for="edit_is_published">Published</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="editFaqForm" name="update_faq" class="btn btn-primary">Update FAQ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete FAQ Modal -->
    <div class="modal fade" id="deleteFaqModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete FAQ</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this FAQ? This action cannot be undone.</p>
                    <form method="POST" action="" id="deleteFaqForm">
                        <input type="hidden" name="faq_id" id="delete_faq_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteFaqForm" name="delete_faq" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Question Modal -->
    <div class="modal fade" id="viewQuestionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">User Question</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title" id="view_question_text"></h5>
                            <h6 class="card-subtitle mb-2 text-muted">From: <span id="view_question_name"></span></h6>
                            <p class="card-text mb-1">Email: <a href="#" id="view_question_email_link"><span id="view_question_email"></span></a></p>
                            <p class="card-text">Date: <span id="view_question_date"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="modalCreateFaq">Create FAQ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {

            $('.edit-faq').click(function() {
                var id = $(this).data('id');
                var question = $(this).data('question');
                var answer = $(this).data('answer');
                var category = $(this).data('category');
                var published = $(this).data('published');

                $('#edit_faq_id').val(id);
                $('#edit_question').val(question);
                $('#edit_answer').val(answer);
                $('#edit_category').val(category);
                $('#edit_is_published').prop('checked', published == 1);

                $('#editFaqModal').modal('show');
            });

            $('.delete-faq').click(function() {
                var id = $(this).data('id');
                $('#delete_faq_id').val(id);
                $('#deleteFaqModal').modal('show');
            });

            $('.view-question').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var email = $(this).data('email');
                var question = $(this).data('question');
                var date = $(this).data('date');

                $('#view_question_text').text(question);
                $('#view_question_name').text(name);
                $('#view_question_email').text(email);
                $('#view_question_email_link').attr('href', 'mailto:' + email);
                $('#view_question_date').text(date);

                $('#viewQuestionModal').modal('show');
            });

            $('.convert-to-faq').click(function() {
                var question = $(this).data('question');

                $('#myTab a[href="#add"]').tab('show');

                $('#question').val(question);

                $('html, body').animate({
                    scrollTop: $("#add").offset().top
                }, 500);
            });

            $('#modalCreateFaq').click(function() {
                var question = $('#view_question_text').text();

                $('#viewQuestionModal').modal('hide');

                $('#myTab a[href="#add"]').tab('show');

                $('#question').val(question);

                $('html, body').animate({
                    scrollTop: $("#add").offset().top
                }, 500);
            });
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>