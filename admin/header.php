<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Connect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #4e73df;
            --secondary: #6f42c1;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
        }

        .campus-logo {
            max-height: 50px;
            margin-right: 10px;
        }

        .brand-text {
            font-weight: 700;
            color: #fff;
            font-size: 1.2rem;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 10%, var(--secondary) 100%);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: all 0.3s;
            z-index: 999;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            padding: 0;
            list-style: none;
        }

        .sidebar-nav li {
            position: relative;
            margin: 0;
            padding: 0;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            transition: all 0.3s;
        }

        .sidebar-nav li a:hover,
        .sidebar-nav li.active a {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #fff;
        }

        .sidebar-nav li a i {
            margin-right: 10px;
            width: 25px;
            text-align: center;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        .topbar {
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            position: relative;
            z-index: 1;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-radius: 5px;
        }

        .navbar-dropdown .dropdown-menu {
            position: absolute;
            right: 0;
            left: auto;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0 15px 0 0;
        }

        .sidebar-toggle:focus {
            outline: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }

        .stat-card {
            border-left: 4px solid;
            border-radius: 4px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .stat-card-primary {
            border-left-color: var(--primary);
        }

        .stat-card-success {
            border-left-color: var(--success);
        }

        .stat-card-info {
            border-left-color: var(--info);
        }

        .stat-card-warning {
            border-left-color: var(--warning);
        }

        .card-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0.1;
            font-size: 3rem;
        }
    </style>
</head>
<body>
  
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center justify-content-center">
         
                <img src="../imgs/logo.png" alt="Campus Connect" class="campus-logo">
                <div class="brand-text">Campus Connect</div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="active">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="events.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Events</span>
                </a>
            </li>

            <li>
                <a href="add-event.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Event</span>
                </a>
            </li>

            <li>
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>

            <li>
    <a href="admin_management.php">
        <i class="fas fa-user-shield"></i>
        <span>Admin Dashboard</span>
    </a>
</li>

<li>
    <a href="faqs.php">
        <i class="fas fa-question-circle"></i>
        <span>FAQ</span>
    </a>
</li>

<li>
    <a href="contact_form.php">
        <i class="fas fa-envelope"></i>
        <span>Contact</span>
    </a>
</li>

            <li>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="main-content">
     
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="navbar-dropdown">
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        Admin User
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user fa-sm me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog fa-sm me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-sm me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

       