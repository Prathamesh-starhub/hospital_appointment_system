<?php
/**
 * System Configuration
 * Define all global constants here.
 */

// Error Reporting (Enable for Dev, Disable for Prod)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'hospital_appointment_system');

// System Settings
define('SITE_NAME', 'MediCare Booking System');
define('BASE_URL', 'http://localhost/hospital_appointment_system/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust as per your location
?>