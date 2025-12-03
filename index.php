<?php
require_once 'config/config.php';
session_start();

// If logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'patient') header("Location: patient/dashboard.php");
    elseif ($_SESSION['role'] == 'doctor') header("Location: doctor/dashboard.php");
    elseif ($_SESSION['role'] == 'admin') header("Location: admin/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
        /* Base Hero Styles */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/images/hospital_bg.jpg');
            background-color: #2c3e50; /* Fallback */
            background-size: cover;
            background-position: center;
            /* Changed from fixed height to min-height for mobile safety */
            min-height: 100vh; 
            display: flex;
            align-items: center;
            color: white;
            /* Add padding to prevent navbar overlap on small screens */
            padding-top: 80px; 
            padding-bottom: 50px;
        }

        .feature-icon { 
            font-size: 3rem; 
            margin-bottom: 1rem; 
            color: #0d6efd; 
        }

        /* --- MOBILE RESPONSIVE TWEAKS --- */
        @media (max-width: 768px) {
            /* Shrink the main title on mobile to prevent overflow */
            .hero-section h1 {
                font-size: 2.5rem; 
            }
            /* Adjust lead text size */
            .hero-section p.lead {
                font-size: 1.1rem;
            }
            /* Adjust spacing for feature icons */
            .feature-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-absolute w-100" style="z-index: 10;">
    <div class="container">
        <!-- Logo / Brand -->
        <a class="navbar-brand fw-bold" href="#">
            <!-- Optional: <img src="assets/images/logo.png" width="30" height="30" class="d-inline-block align-top" alt=""> -->
            MediCare
        </a>

        <!-- Right Side: Login/Signup -->
        <div class="d-flex align-items-center">
            <!-- Label specifically for Patients -->
            <span class="text-white me-3 d-none d-sm-block">For Patients:</span>
            
            <a href="login.php" class="btn btn-outline-light me-2">Login</a>
            <a href="register_patient.php" class="btn btn-primary">Sign Up</a>
        </div>
    </div>
</nav>

<section class="hero-section text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-3 fw-bold mb-4">Modern Healthcare at Your Fingertips</h1>
                <p class="lead mb-5">Book appointments with top specialists, manage your medical history, and receive digital prescriptions securely.</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                    <a href="register_patient.php" class="btn btn-primary btn-lg px-4 gap-3">Book Appointment Now</a>
                    <a href="doctor_login.php" class="btn btn-outline-light btn-lg px-4">Doctor Portal</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <i class="fas fa-user-md feature-icon"></i>
                <h3>Expert Doctors</h3>
                <p>Choose from our panel of certified specialists across various departments.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-calendar-check feature-icon"></i>
                <h3>Easy Scheduling</h3>
                <p>Real-time slot availability makes booking appointments hassle-free.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="fas fa-file-medical feature-icon"></i>
                <h3>Digital Records</h3>
                <p>Access your prescriptions and medical history anytime, anywhere.</p>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white py-4 text-center">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>