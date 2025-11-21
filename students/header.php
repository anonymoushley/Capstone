<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHMSU Scholarship Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background-color: #00692a;
            padding: 0.5rem 0;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 500;
            white-space: nowrap;
        }
        .navbar-brand img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            object-fit: contain;
        }
        .navbar-brand .brand-text {
            display: inline-block;
        }
        .navbar-brand .brand-text-mobile {
            display: none;
        }
        .nav-link {
            color: white !important;
            padding: 0.5rem 1rem !important;
        }
        .nav-link:hover {
            color: #e0e0e0 !important;
        }
        .dropdown-menu {
            background-color: #00692a;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .dropdown-item {
            color: white !important;
            padding: 0.75rem 1rem !important;
        }
        .dropdown-item:hover,
        .dropdown-item:active,
        .dropdown-item:focus {
            background-color: #005223;
            color: white !important;
        }
        .dropdown-item {
            transition: background-color 0.2s ease;
        }
        .navbar-toggler {
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.25rem 0.5rem;
        }
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .user-name-full {
            display: inline;
        }
        .user-name-short {
            display: none;
        }
        
        /* Tablet Styles */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.4rem 0;
            }
            .navbar-brand {
                font-size: 0.9rem;
            }
            .navbar-brand img {
                width: 32px;
                height: 32px;
                margin-right: 8px;
            }
            .navbar-brand .brand-text {
                display: none;
            }
            .navbar-brand .brand-text-mobile {
                display: inline-block;
            }
            .navbar-nav {
                text-align: left;
                margin-top: 0.5rem;
            }
            .nav-link {
                padding: 0.75rem 1rem !important;
                font-size: 0.95rem;
                min-height: 44px;
                display: flex;
                align-items: center;
                touch-action: manipulation;
            }
            .container-fluid {
                padding: 0 15px;
            }
            .dropdown-menu {
                width: 100%;
                margin-top: 0.5rem;
            }
            .dropdown-item {
                min-height: 44px;
                display: flex;
                align-items: center;
                touch-action: manipulation;
            }
            .user-name-full {
                display: none;
            }
            .user-name-short {
                display: inline;
            }
        }
        
        /* Mobile Styles */
        @media (max-width: 576px) {
            .navbar {
                padding: 0.35rem 0;
            }
            .navbar-brand {
                font-size: 0.85rem;
            }
            .navbar-brand img {
                width: 28px;
                height: 28px;
                margin-right: 6px;
            }
            .navbar-brand .brand-text,
            .navbar-brand .brand-text-mobile {
                display: none;
            }
            .navbar-toggler {
                padding: 0.2rem 0.4rem;
                font-size: 0.9rem;
            }
            .navbar-nav {
                margin-top: 0.75rem;
            }
            .nav-link {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem;
            }
            .nav-link i {
                margin-right: 0.5rem;
            }
            .container-fluid {
                padding: 0 10px;
            }
            .dropdown-menu {
                width: 100%;
                margin-top: 0.5rem;
                border-radius: 0.375rem;
            }
            .dropdown-item {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem;
            }
        }
        
        /* Extra Small Mobile */
        @media (max-width: 360px) {
            .navbar-brand img {
                width: 24px;
                height: 24px;
                margin-right: 4px;
            }
            .nav-link {
                padding: 0.65rem 0.75rem !important;
                font-size: 0.85rem;
            }
            .dropdown-item {
                padding: 0.65rem 0.75rem !important;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="applicant_dashboard.php">
                <img src="../assets/images/chmsu-logo.png" alt="CHMSU Logo">
                <span class="brand-text">CHMSU Scholarship Portal</span>
                <span class="brand-text-mobile">CHMSU Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="applicant_dashboard.php"><i class="fas fa-home d-lg-none"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user d-lg-none"></i> My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exam.php"><i class="fas fa-clipboard-list d-lg-none"></i> Take Exam</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> 
                            <span class="user-name-full"><?php 
                                $name = $_SESSION['name'] ?? 'User';
                                // Split name into parts and capitalize each part
                                $nameParts = explode(' ', trim($name));
                                $capitalizedParts = array();
                                foreach($nameParts as $part) {
                                    $capitalizedParts[] = ucfirst(strtolower(trim($part)));
                                }
                                $name = implode(' ', $capitalizedParts);
                                echo htmlspecialchars($name); 
                            ?></span>
                            <span class="user-name-short"><?php 
                                $name = $_SESSION['name'] ?? 'User';
                                $nameParts = explode(' ', trim($name));
                                if (count($nameParts) > 0) {
                                    echo htmlspecialchars(ucfirst(strtolower(trim($nameParts[0]))));
                                } else {
                                    echo 'User';
                                }
                            ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="account_settings.php"><i class="fas fa-cog me-2"></i> Account Settings</a></li>
                            <li><hr class="dropdown-divider" style="border-color: rgba(255,255,255,0.2);"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 