
<?php
session_start();
require_once '../config/database.php';

// Check if chairperson is logged in
if (!isset($_SESSION['chair_id']) || $_SESSION['user_type'] !== 'chairperson') {
    header('Location: chair_login.php');
    exit();
}

// Create MySQLi connection for compatibility with existing pages
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="icon" href="images/chmsu.png" type="image/png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <style>
    body {
      background: url('images/chmsubg.jpg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    .overlay {
      background-color: rgba(255, 255, 255, 0.85);
      min-height: 100vh;
      padding-top: 70px;
    }
    .header-bar {
      background-color: rgb(0, 105, 42);
      color: white;
      padding: 1rem;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }
    .header-bar img {
      width: 65px;
      margin-right: 10px;
    }
    .sidebar {
      background-color: rgba(232, 245, 233, 0.88);
      position: fixed;
      top: 90px;
      bottom: 0;
      left: 0;
      width: 250px;
      padding: 1rem;
      overflow-y: auto;
    }
    .sidebar a {
      display: block;
      padding: 10px;
      margin-bottom: 5px;
      color: #000;
      text-decoration: none;
      border-radius: 5px;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #c8e6c9;
      font-weight: bold;
    }
    .main-content {
      margin-left: 250px;
      padding: 0;
      padding-top: 10px;
      width: calc(100% - 250px);
    }
    
    /* Full width when sidebar is hidden */
    .main-content.full-width {
      margin-left: 0;
      width: 100%;
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
    @media (max-width: 800px) {
      .sidebar {
        position: relative;
        width: 90%;
        top: 0;
        margin-bottom: 1rem;
      }
      .main-content {
        margin:auto;
      }
    }

  </style>
</head>
<body>
<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'chair_dashboard';
$allowed_pages = ['chair_dashboard','chair_applicants','exam_versions', 'notifications', 'interviewers', 'settings','chair_reports','chair_changepass','maintenance','scheduling'];
?>
<div class="overlay">
  <div class="header-bar d-flex align-items-center">
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
      <p class="mb-0">Academic Program Application and Screening Management System</p>
    </div>
  </div>

  <div class="container-fluid px-0">
    <div class="row g-0">
      <?php if ($page !== 'chair_applicants' && $page !== 'chair_reports' && $page !== 'maintenance' && $page !== 'scheduling'): ?>
      <div class="col-md-3 sidebar">
        <a href="?page=chair_dashboard" class="<?= $page === 'chair_dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
          <a href="?page=chair_applicants" class="<?= $page === 'chair_applicants' ? 'active' : '' ?>"><i class="fas fa-users"></i> Applicants</a>
        <a href="?page=exam_versions" class="<?= $page === 'exam_versions' ? 'active' : '' ?>"><i class="fas fa-clipboard-check"></i> Manage Examinations</a>
        <a href="?page=interviewers" class="<?= $page === 'interviewers' ? 'active' : '' ?>"><i class="fas fa-user-plus"></i> Add Interviewer</a>
        <a href="?page=chair_reports" class="<?= $page === 'chair_reports' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="?page=scheduling" class="<?= $page === 'scheduling' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Scheduling</a>
        <a href="?page=maintenance" class="<?= $page === 'maintenance' ? 'active' : '' ?>"><i class="fas fa-tools"></i> Maintenance</a>
        <a href="?page=chair_changepass" class="<?= $page === 'chair_changepass' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Password Setting</a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
      <?php endif; ?>

      <div class="<?= ($page === 'chair_applicants' || $page === 'chair_reports' || $page === 'maintenance' || $page === 'scheduling') ? 'col-12 main-content full-width' : 'col-12 main-content' ?>">
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
      <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to logout?</p>
        <p class="text-muted">You will need to login again to access the system.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);">Logout</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
