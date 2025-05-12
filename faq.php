<?php
session_start();

require_once 'config.php';

function getFaqs($conn) {
    $query = "SELECT faq_id, question, answer, category FROM faqs WHERE is_published = TRUE ORDER BY category, faq_id";
    $result = mysqli_query($conn, $query);

    $faqs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $faqs[] = $row;
        }
    }
    return $faqs;
}

function getFaqCategories($conn) {
    $query = "SELECT DISTINCT category FROM faqs WHERE is_published = TRUE ORDER BY category";
    $result = mysqli_query($conn, $query);

    $categories = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row['category'];
        }
    }
    return $categories;
}

$formSubmitted = false;
$formError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {

    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $question = mysqli_real_escape_string($conn, trim($_POST['question']));
    $user_id = null;

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    if (empty($name) || empty($email) || empty($question)) {
        $formError = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = "Please enter a valid email address.";
    } else {

        $query = "INSERT INTO user_questions (user_id, name, email, question) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $name, $email, $question);

        if (mysqli_stmt_execute($stmt)) {
            $formSubmitted = true;
        } else {
            $formError = "An error occurred. Please try again.";
        }
    }
}

$faqs = getFaqs($conn);
$categories = getFaqCategories($conn);
include 'header.php';
?>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .faq-section {
            padding: 50px 0;
            background-color: #f8f9fa;
        }
        .faq-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .faq-title {
            text-align: center;
            margin-bottom: 40px;
            color: #343a40;
        }
        .faq-tabs {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .faq-tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            margin-bottom: -1px;
            border: 1px solid transparent;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
            transition: all 0.3s ease;
        }
        .faq-tab.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .faq-tab:hover:not(.active) {
            background-color: rgba(0,0,0,0.05);
        }
        .faq-content {
            margin-bottom: 50px;
        }
        .faq-category {
            margin-bottom: 30px;
            display: none;
        }
        .faq-category.active {
            display: block;
        }
        .faq-item {
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .faq-question {
            padding: 15px 20px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .faq-question:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .faq-question i {
            transition: transform 0.3s ease;
        }
        .faq-question.active i {
            transform: rotate(180deg);
        }
        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }
        .faq-answer.active {
            padding: 0 20px 20px;
            max-height: 1000px;
        }
        .ask-question {
            background-color: #fff;
            border-radius: 5px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        textarea.form-control {
            min-height: 150px;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .search-container {
            margin-bottom: 30px;
        }
        .search-input {
            width: 100%;
            padding: 15px;
            border: 1px solid #ced4da;
            border-radius: 30px;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .search-input:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .no-results {
            text-align: center;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .faq-tabs {
                flex-direction: column;
                border-bottom: none;
            }
            .faq-tab {
                margin-right: 0;
                margin-bottom: 5px;
                border: 1px solid #dee2e6;
                border-radius: 5px;
            }
            .faq-tab.active {
                border-color: #007bff;
            }
            .ask-question {
                padding: 20px;
            }
        }
    </style>

    <section class="faq-section">
        <div class="faq-container">
            <h1 class="faq-title">Frequently Asked Questions</h1>

            <!-- Search Bar -->
            <div class="search-container">
                <input type="text" id="faqSearch" class="search-input" placeholder="Search FAQs...">
            </div>

            <!-- FAQ Content -->
            <div class="faq-content">
                <!-- FAQ Categories Tabs -->
                <div class="faq-tabs">
                    <div class="faq-tab active" data-category="all">All FAQs</div>
                    <?php foreach ($categories as $category): ?>
                        <div class="faq-tab" data-category="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Display FAQs -->
                <div class="faq-category active" data-category="all">
                    <?php if (empty($faqs)): ?>
                        <div class="no-results">No FAQs available yet.</div>
                    <?php else: ?>
                        <?php foreach ($faqs as $faq): ?>
                            <div class="faq-item" data-category="<?php echo htmlspecialchars($faq['category']); ?>">
                                <div class="faq-question">
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Category specific FAQs -->
                <?php foreach ($categories as $category): ?>
                    <div class="faq-category" data-category="<?php echo htmlspecialchars($category); ?>">
                        <?php 
                        $categoryFaqs = array_filter($faqs, function($faq) use ($category) {
                            return $faq['category'] === $category;
                        });

                        if (empty($categoryFaqs)): 
                        ?>
                            <div class="no-results">No FAQs available in this category.</div>
                        <?php else: ?>
                            <?php foreach ($categoryFaqs as $faq): ?>
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- No search results message -->
                <div class="no-results" id="noSearchResults" style="display: none;">
                    No matching FAQs found. Please try another search term or submit your question below.
                </div>
            </div>

            <!-- Ask a Question Form -->
            <div class="ask-question">
                <h2>Can't find what you're looking for?</h2>
                <p>Submit your question below and we'll get back to you as soon as possible.</p>

                <?php if ($formSubmitted): ?>
                    <div class="alert alert-success">
                        Thank you for your question! We will respond to you shortly.
                    </div>
                <?php endif; ?>

                <?php if ($formError): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($formError); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="question" class="form-label">Your Question</label>
                        <textarea id="question" name="question" class="form-control" required></textarea>
                    </div>

                    <button type="submit" name="submit_question" class="btn-primary">Submit Question</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const faqQuestions = document.querySelectorAll('.faq-question');
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const isActive = this.classList.contains('active');

                    document.querySelectorAll('.faq-question').forEach(q => {
                        q.classList.remove('active');
                        q.nextElementSibling.classList.remove('active');
                    });

                    if (!isActive) {
                        this.classList.add('active');
                        answer.classList.add('active');
                    }
                });
            });

            const faqTabs = document.querySelectorAll('.faq-tab');
            faqTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');

                    document.querySelectorAll('.faq-tab').forEach(t => {
                        t.classList.remove('active');
                    });
                    document.querySelectorAll('.faq-category').forEach(c => {
                        c.classList.remove('active');
                    });

                    this.classList.add('active');
                    document.querySelector(`.faq-category[data-category="${category}"]`).classList.add('active');
                });
            });

            const searchInput = document.getElementById('faqSearch');
            const noResults = document.getElementById('noSearchResults');
            const allFaqItems = document.querySelectorAll('.faq-item');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let hasResults = false;

                if (searchTerm === '') {

                    allFaqItems.forEach(item => {
                        item.style.display = 'block';
                    });
                    noResults.style.display = 'none';
                    return;
                }

                allFaqItems.forEach(item => {
                    const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer p').textContent.toLowerCase();

                    if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                        item.style.display = 'block';
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                noResults.style.display = hasResults ? 'none' : 'block';

                document.querySelectorAll('.faq-tab').forEach(t => {
                    t.classList.remove('active');
                });
                document.querySelector('.faq-tab[data-category="all"]').classList.add('active');

                document.querySelectorAll('.faq-category').forEach(c => {
                    c.classList.remove('active');
                });
                document.querySelector('.faq-category[data-category="all"]').classList.add('active');
            });
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>