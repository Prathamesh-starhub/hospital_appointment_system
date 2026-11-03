MediCare - Hospital Appointment Booking System 🏥
A complete, production-grade Hospital Appointment Booking System developed using PHP (OOP), MySQL, Bootstrap 5, and vanilla JavaScript/jQuery. This system streamlines the process of booking medical appointments, managing doctor schedules, digitizing medical records, and tracking live patient queues.
🤖 AI Acknowledgment: This project was conceptualized and developed with the assistance of Artificial Intelligence (Google Gemini). AI tools were utilized to accelerate full-stack code generation, implement secure database architectures, and design responsive UI components following modern industry best practices.
🌟 Key Features
🧑‍⚕️ For Patients:
Secure Authentication: Registration and login system.
Smart Booking System: Dynamic, AJAX-powered booking workflow (Specialization -> Doctor -> Date -> Available Time Slot).
Appointment Management: View upcoming/past appointments and cancel pending ones.
Digital Medical Records: Access consultation history, symptoms, and doctor diagnoses.
Prescription Downloads: View and download securely uploaded prescription files (PDF/Images).
Live Notifications: Real-time system alerts.
🩺 For Doctors:
Dashboard Analytics: Quick overview of daily appointments and pending requests.
Availability Management: Set weekly working hours, slot durations, and days off.
Patient Records: 360-degree view of a patient's complete medical history.
Consultation Mode: Add clinical notes, symptoms, and prescribed medications during visits.
File Uploads: Securely upload prescription files directly to a patient's record.
Live Queue Management: Real-time token system to call the next patient, finish consultations, or mark as no-shows.
🛡️ System & Security:
Object-Oriented PHP: Clean, modular architecture.
PDO Prepared Statements: Complete protection against SQL Injection.
Secure Passwords: bcrypt password hashing algorithm.
File Security: Strict MIME-type checking and random hash renaming for uploaded files.
💻 Technology Stack
Backend: PHP 8.x
Database: MySQL 8.x (Relational Schema in 3NF)
Frontend: HTML5, CSS3, JavaScript (ES6+)
UI Framework: Bootstrap 5.3
Libraries: jQuery (for AJAX requests), FontAwesome 6 (Icons)
Server Environment: XAMPP (Apache)
🚀 Installation Instructions (XAMPP)
Follow these steps to get the project running on your local machine:
1. Prerequisites
Install XAMPP (Ensure PHP 8.0 or higher is included).
Start the Apache and MySQL modules in the XAMPP Control Panel.
2. Setup Files
Navigate to your XAMPP htdocs directory (usually C:\xampp\htdocs\ on Windows or /opt/lampp/htdocs/ on Linux/Mac).
Create a folder named hospital_appointment_system.
Extract/Copy all project files into this folder.
Important: Create an uploads folder inside the project root and add two subfolders inside it: prescriptions and doctor_profiles. Ensure these folders have write permissions.
3. Database Configuration
Open your browser and go to http://localhost/phpmyadmin.
Click on New to create a new database. Name it exactly: hospital_appointment_system (Collation: utf8mb4_general_ci).
Select the newly created database, go to the Import tab.
Choose the hospital_appointment_system.sql file (found in the database/ folder) and click Import. (Note: Also run any additional SQL scripts provided, such as the one to insert the 8 extra doctors).
4. System Configuration
If you changed the database name, username, or password, update the config/config.php file:
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password is empty
define('DB_NAME', 'hospital_appointment_system');
5. Launch
Open your web browser and navigate to: 👉 http://localhost/hospital_appointment_system/
🔑 Default Testing Credentials
Use the following credentials to explore the system (All passwords are password123):
1. Doctor Account:
Email: smith@hospital.com
Password: password123
2. Patient Account:
Email: john@gmail.com (or any newly registered patient)
Password: password123
📁 Directory Structure
hospital_appointment_system/
│
├── api/                        # AJAX Endpoints (JSON responses)
├── assets/                     # CSS, JS, and UI Images
├── config/                     # Database & Global constants
├── database/                   # SQL Schema and dummy data
├── doctor/                     # Doctor-specific pages and logic
├── includes/                   # Reusable classes (Auth, Uploads, Queue)
├── patient/                    # Patient-specific pages and logic
├── uploads/                    # User-uploaded files (Ignored by Git)
│
├── index.php                   # Public Landing Page
├── login.php                   # Universal Login
├── register_patient.php        # Patient Registration
└── logout.php                  # Session Destroyer
🛠️ Troubleshooting
Time Slots Not Showing: Ensure the doctor you selected has active working hours set in the doctor_availability table (or by logging in as the doctor and configuring "My Schedule").
Image Uploads Failing: Ensure the uploads/ directory exists and has the correct read/write permissions (chmod 755 on Linux/Mac).
Database Connection Error: Verify your MySQL credentials in config/config.php.