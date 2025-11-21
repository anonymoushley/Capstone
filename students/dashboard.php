<?php
/**
 * Student Dashboard Home Page
 * 
 * Displays welcome message and college information for students
 * 
 * @package Students
 */

// Get user information if needed
$user_id = $_SESSION['user_id'] ?? null;
?>

<div class="container-fluid px-4 py-3">
    <!-- Welcome Section -->
    <div class="welcome-section mb-4">
        <h3 class="mb-3"><i class="fas fa-graduation-cap me-2" style="color: #00692a;"></i>Welcome to CHMSU Application Portal</h3>
        <p class="lead">Carlos Hilado Memorial State University - College of Computer Studies</p>
    </div>

    <!-- Programs Section -->
    <section id="programs" class="programs-container mb-4">
        <h2 class="section-title">College of Computer Studies</h2>
        <div class="program-cards">
            <div class="program-card">
                <div class="program-image">
                    <img src="images/ccs.png" alt="CHMSU Logo">
                </div>
                <div class="program-content">
                    <h3 class="program-title">BS Information Technology</h3>
                    <div class="program-details">
                        <div class="program-detail">
                            <span>⏱️</span>
                            <span>4 Years</span>
                        </div>
                    </div>
                    <h7>Availability: <i> Alijis Campus, Binalbagan Campus</i></h7>
                    <p class="program-description mt-2">
                        The Bachelor of Science in Information Technology program focuses on the practical applications of computing technology in business and organizational settings. Students learn to design, implement, and manage IT systems that support business operations and strategic initiatives.
                    </p>
                </div>
            </div>
            
            <div class="program-card">
                <div class="program-image">
                    <img src="images/ccs.png" alt="CHMSU Logo">
                </div>
                <div class="program-content">
                    <h3 class="program-title">BS Information Systems</h3>
                    <div class="program-details">
                        <div class="program-detail">
                            <span>⏱️</span>
                            <span>4 Years</span>
                        </div>
                    </div>
                    <h7>Availability: <i> Talisay Campus, Alijis Campus, Fortune Towne Campus</i></h7>
                    <p class="program-description mt-2">
                        The Bachelor of Science in Information Systems program bridges the gap between business and technology. Students learn how information systems can be leveraged to improve organizational processes, support decision-making, and drive business strategy.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Information Cards -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100" style="border-top: 3px solid #00692a;">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-3x mb-3" style="color: #00692a;"></i>
                    <h5>Application Status</h5>
                    <p class="text-muted">Track your application progress and view your status updates here.</p>
                    <a href="?page=profiling" class="btn btn-sm" style="background-color: #00692a; color: white;">
                        View Profile <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100" style="border-top: 3px solid #00692a;">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x mb-3" style="color: #00692a;"></i>
                    <h5>Application Requirements</h5>
                    <p class="text-muted">Ensure all required documents are uploaded and verified.</p>
                    <a href="?page=profiling" class="btn btn-sm" style="background-color: #00692a; color: white;">
                        Upload Documents <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100" style="border-top: 3px solid #00692a;">
                <div class="card-body text-center">
                    <i class="fas fa-info-circle fa-3x mb-3" style="color: #00692a;"></i>
                    <h5>Important Reminders</h5>
                    <p class="text-muted">Review application guidelines and important announcements.</p>
                    <button class="btn btn-sm" style="background-color: #00692a; color: white;" data-bs-toggle="modal" data-bs-target="#guidelinesModal">
                        View Guidelines <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Guidelines Modal -->
    <div class="modal fade" id="guidelinesModal" tabindex="-1" aria-labelledby="guidelinesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00692a; color: white;">
                    <h5 class="modal-title" id="guidelinesModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Application Guidelines
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Important Guidelines</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Applicants must have passed the CHMSU Entrance Examination.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>If you are a shiftee or an old student that is enrolled or was enrolled in CHMSU, <strong>DO NOT USE THIS SYSTEM</strong>.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Use only one email address in the application. We prohibit the applicant to use multiple email addresses to create multiple accounts with the same name. Once traced, only the first entry will be acknowledged and the rest will be disregarded.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Applicants are prohibited from sharing the same email address to other applicants. Use your own email address in applying.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Input all your information with honesty and integrity. The data encoded and submitted documents will be subjected to verification and validation.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Prepare your requirements before proceeding to the full application.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Only one application per department is allowed.</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-danger me-2"></i><strong>Applicants who will violate the aforementioned guidelines will be disqualified from the list of application.</strong></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --light-color: #eff6ff;
            --dark-color: #1e3a8a;
            --gray-color: #f1f5f9;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        main {
            padding: 2rem 0;
        }
        
        .programs-container {
            margin-bottom: 2rem;
        }
        
        .section-title {
            margin-bottom: 1rem;
            color: var(--dark-color);
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
        }
        
        .program-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .program-card {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .program-card:hover {
            transform: translateY(-5px);
        }
        
        .program-image {
            height: 150px;
            background-color: var(--gray-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 3rem;
        }
        
        .program-content {
            padding: 1.5rem;
        }
        
        .program-title {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .program-description {
            color: #4b5563;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .program-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .program-detail {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .program-image img {
            width: 23%;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #e8f5e9 0%, #ffffff 100%);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 105, 42, 0.1);
        }
        
        .welcome-section h3 {
            color: #00692a;
            font-weight: 600;
        }
        
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 105, 42, 0.2) !important;
        }
        
        .card-body i {
            transition: transform 0.3s ease;
        }
        
        .card:hover .card-body i {
            transform: scale(1.1);
        }
    </style>
</div>

