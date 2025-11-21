<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header('Location: students/login.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'profiling', 'documents', 'notifications', 'support', 'account_settings', 'settings', 'my_account'];

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM registration WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>
    <link rel="icon" href="images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .overlay {
            background-color: rgba(255, 255, 255, 0.8);
            min-height: 100vh;
            padding-top: 80px;
        }
        .header-bar {
            background-color: rgb(0, 105, 42);
            color: white;
            padding: 0.9rem 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        .header-bottom-row {
            display: none;
            align-items: center;
            width: 100%;
            margin-top: 0.5rem;
            padding-left: calc(1.5rem + 1.5rem + 0.5rem); /* Account for hamburger + logo width */
            padding-right: 1rem;
        }
        .header-left {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
        }
        .header-right {
            display: flex;
            align-items: center;
            padding-right: 1rem;
        }
        .header-bar img {
            width: 65px;
            height: 65px;
            margin-right: 10px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .header-user-info {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .header-user-info i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        /* Hide user name and hamburger in header when on my_account page */
        body.my-account-page-active .header-right,
        body.my-account-page-active .header-bottom-row,
        body.my-account-page-active .hamburger-btn,
        body:has(.my-account-page) .header-right,
        body:has(.my-account-page) .header-bottom-row,
        body:has(.my-account-page) .hamburger-btn {
            display: none !important;
        }
        .sidebar {
            background-color: rgba(232, 245, 233, 0.95);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 1rem;
            padding-top: calc(70px + 2.5rem);
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            -webkit-overflow-scrolling: touch;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 5px;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .sidebar a i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            margin-left: 8px;
            flex-shrink: 0;
        }
        .sidebar a:hover {
            background-color: #c8e6c9;
            font-weight: bold;
        }
        .sidebar a.active {
            background-color: #c8e6c9;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 1.75rem;
            padding-top: 1.5rem;
            transition: margin-left 0.3s ease-in-out;
            min-height: calc(100vh - 70px);
        }
        /* Hamburger Menu Button */
        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-right: 1rem;
            transition: transform 0.3s ease;
        }
        .hamburger-btn:hover {
            opacity: 0.8;
        }
        .hamburger-btn.active {
            transform: rotate(90deg);
        }
        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
            pointer-events: all;
        }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 10px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 50%;
        }
        @media (max-width: 768px) {
            .hamburger-btn {
                display: block;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                max-width: 85%;
                height: 100vh;
                padding-top: calc(100px + 2.5rem);
                transform: translateX(-100%);
                z-index: 999;
                box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
                padding: 1rem;
                padding-top: 1.25rem;
                width: 100%;
            }
            .header-bar {
                padding: 0.6rem 0.75rem;
                min-height: 90px;
            }
            .header-top-row {
                align-items: flex-start;
            }
            .header-bottom-row {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                margin-top: 0.75rem;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                padding-top: 0.25rem;
            }
            .header-bar img {
                width: 50px;
                height: 50px;
                margin-right: 8px;
            }
            .header-left {
                flex: 1;
                min-width: 0;
            }
            .header-left h4 {
                font-size: 0.95rem;
                line-height: 1.3;
                margin: 0;
            }
            .header-left p {
                font-size: 0.65rem;
                margin: 0.25rem 0 0 0;
                display: block;
                line-height: 1.2;
            }
            .header-right {
                display: none;
            }
            .header-user-info {
                font-size: 0.9rem;
            }
            .header-user-info i {
                font-size: 1rem;
            }
            .overlay {
                padding-top: 100px;
            }
            .sidebar a {
                padding: 8px;
                font-size: 0.9rem;
            }
            .sidebar-overlay {
                top: 0;
            }
            /* Ensure container and row don't interfere */
            .container-fluid {
                padding-left: 0;
                padding-right: 0;
            }
            .row {
                margin-left: 0;
                margin-right: 0;
            }
            .col-md-3,
            .col-md-9 {
                padding-left: 0;
                padding-right: 0;
            }
        }
        @media (max-width: 576px) {
            .header-bar {
                padding: 0.5rem 0.6rem;
                min-height: 95px;
            }
            .header-top-row {
                align-items: flex-start;
            }
            .header-bottom-row {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding-left: 0.6rem;
                padding-right: 0.6rem;
                margin-top: 0.5rem;
            }
            .header-bar img {
                width: 45px;
                height: 45px;
                margin-right: 6px;
            }
            .header-left h4 {
                font-size: 0.85rem;
                line-height: 1.2;
            }
            .header-left p {
                display: block;
                font-size: 0.6rem;
                margin: 0.2rem 0 0 0;
                line-height: 1.15;
            }
            .header-user-info {
                font-size: 0.85rem;
            }
            .header-user-info i {
                font-size: 0.95rem;
            }
            .overlay {
                padding-top: 95px;
            }
            .main-content {
                padding: 0.75rem;
                padding-top: 1rem;
            }
            .sidebar {
                width: 260px;
                max-width: 80%;
                padding-top: calc(95px + 2rem);
            }
            .sidebar a {
                padding: 6px;
                font-size: 0.85rem;
            }
            .hamburger-btn {
                font-size: 1.3rem;
                margin-right: 0.5rem;
                padding: 0.4rem;
            }
            .sidebar-overlay {
                top: 0;
            }
        }
        
        @media (max-width: 360px) {
            .header-bar {
                padding: 0.4rem 0.5rem;
                min-height: 90px;
            }
            .header-bottom-row {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .header-bar img {
                width: 40px;
                height: 40px;
                margin-right: 5px;
            }
            .header-left h4 {
                font-size: 0.75rem;
                line-height: 1.15;
            }
            .header-left p {
                display: block;
                font-size: 0.55rem;
                margin: 0.15rem 0 0 0;
                line-height: 1.1;
            }
            .header-user-info {
                font-size: 0.8rem;
            }
            .overlay {
                padding-top: 90px;
            }
            .sidebar {
                padding-top: calc(90px + 1.5rem);
            }
        }
        .dropdown-toggle::after {
            margin-left: 8px;
        }
    </style>
</head>
<body class="<?= $page === 'my_account' ? 'my-account-page-active' : '' ?>">
<div class="overlay">
    <div class="header-bar">
        <div class="header-top-row">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-left">
                <img src="images/chmsu.png" alt="CHMSU Logo">
                <div class="ms-1">
                    <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
                    <p class="mb-0">Academic Program Application and Screening Management System</p>
                </div>
            </div>
            <div class="header-right">
                <a class="header-user-info" href="?page=my_account">
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars(ucwords(strtolower(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>
                </a>
            </div>
        </div>
        <div class="header-bottom-row">
            <a class="header-user-info" href="?page=my_account">
                <i class="fas fa-user"></i> 
                <?= htmlspecialchars(ucwords(strtolower(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>
            </a>
        </div>
    </div>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar" id="sidebar">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a>
                <a href="?page=profiling" class="<?= $page === 'profiling' ? 'active' : '' ?>"><i class="fas fa-user"></i> Applicant Profiling</a>
                <a href="?page=account_settings" class="<?= $page === 'account_settings' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Password Setting</a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

            <div class="col-md-9 main-content">
                <?php
                if (in_array($page, $allowed_pages) && file_exists("$page.php")) {
                    include("$page.php");
                } else {
                    echo "<div class='alert alert-danger'>Page not found or under development.</div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #00692a; color: white;">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to logout?</p>
        <p class="text-muted">You will need to login again to access the system.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn" style="background-color: #00692a; color: white; border: 1px solid #00692a;">Logout</a>
      </div>
    </div>
  </div>
</div>

<!-- Temporary Password Sent Modal -->
<?php 
$show_temp_password_modal = isset($_SESSION['temp_password_sent']) && $_SESSION['temp_password_sent'];
$temp_password_email = isset($_SESSION['temp_password_email']) ? $_SESSION['temp_password_email'] : '';
$temp_password_email_error = isset($_SESSION['temp_password_email_error']) && $_SESSION['temp_password_email_error'];

if ($show_temp_password_modal): 
?>
<div class="modal fade" id="tempPasswordModal" tabindex="-1" aria-labelledby="tempPasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #00692a; color: white;">
        <h5 class="modal-title" id="tempPasswordModalLabel">
          <i class="fas fa-envelope-circle-check"></i> Temporary Password Sent
        </h5>
      </div>
      <div class="modal-body">
        <?php if ($temp_password_email_error): ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Your account has been created, but we encountered an issue sending the email.
          </div>
        <?php else: ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Your temporary password has been successfully sent!
          </div>
        <?php endif; ?>
        <p>A temporary password has been sent to your Gmail account:</p>
        <p class="text-center">
          <strong style="color: #00692a; font-size: 1.1em;"><?= htmlspecialchars($temp_password_email) ?></strong>
        </p>
        <p class="text-muted mt-3">
          <i class="fas fa-info-circle"></i> Please check your inbox (and spam folder) for the email containing your temporary password. 
          You can use this password to login directly, or continue using Google Sign-In.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" style="background-color: #00692a; color: white; border: 1px solid #00692a;" data-bs-dismiss="modal">
          <i class="fas fa-check"></i> Got it!
        </button>
      </div>
    </div>
  </div>
</div>
<?php 
  // Clear the session flag after displaying
  unset($_SESSION['temp_password_sent']);
  unset($_SESSION['temp_password_email']);
  if (isset($_SESSION['temp_password_email_error'])) {
    unset($_SESSION['temp_password_email_error']);
  }
endif; 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Hamburger Menu Toggle Functionality
  document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Initialize sidebar state based on screen size
    function initializeSidebar() {
      if (window.innerWidth > 768) {
        // Desktop: ensure sidebar is visible
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        document.body.style.overflow = '';
        const icon = hamburgerBtn.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-times');
          icon.classList.add('fa-bars');
        }
      } else {
        // Mobile: ensure sidebar starts hidden
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        document.body.style.overflow = '';
        const icon = hamburgerBtn.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-times');
          icon.classList.add('fa-bars');
        }
      }
    }
    
    // Initialize on page load
    initializeSidebar();
    
    // Toggle sidebar function
    function toggleSidebar() {
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
      hamburgerBtn.classList.toggle('active');
      
      // Prevent body scroll when sidebar is open on mobile
      if (window.innerWidth <= 768) {
        if (sidebar.classList.contains('active')) {
          document.body.style.overflow = 'hidden';
        } else {
          document.body.style.overflow = '';
        }
      }
      
      // Change icon
      const icon = hamburgerBtn.querySelector('i');
      if (sidebar.classList.contains('active')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    }
    
    // Hamburger button click
    if (hamburgerBtn) {
      hamburgerBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
      });
    }
    
    // Close sidebar when overlay is clicked
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', function() {
        if (sidebar.classList.contains('active')) {
          toggleSidebar();
        }
      });
    }
    
    // Close sidebar when clicking on a sidebar link (mobile only)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function() {
        // Only close on mobile devices
        if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
          toggleSidebar();
        }
      });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
      initializeSidebar();
    });
    
    // Close sidebar when clicking outside (desktop behavior)
    document.addEventListener('click', function(e) {
      if (window.innerWidth > 768) {
        return; // Don't close on desktop
      }
      
      if (sidebar.classList.contains('active') && 
          !sidebar.contains(e.target) && 
          !hamburgerBtn.contains(e.target)) {
        toggleSidebar();
      }
    });
  });
</script>
<?php if ($show_temp_password_modal): ?>
<script>
  // Auto-show the modal when page loads
  document.addEventListener('DOMContentLoaded', function() {
    var tempPasswordModal = new bootstrap.Modal(document.getElementById('tempPasswordModal'));
    tempPasswordModal.show();
  });
</script>
<?php endif; ?>
</body>
</html>
