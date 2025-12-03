-- --------------------------------------------------------
-- Database: hospital_appointment_system
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `hospital_appointment_system`;
USE `hospital_appointment_system`;

-- 1. Users Table (Base table for Auth)
CREATE TABLE `users` (
  `user_id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'doctor', 'patient') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Patients Table
CREATE TABLE `patients` (
  `patient_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `address` TEXT,
  `blood_group` VARCHAR(5),
  `emergency_contact` VARCHAR(15),
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Doctors Table
CREATE TABLE `doctors` (
  `doctor_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `specialization` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `experience_years` INT DEFAULT 0,
  `consultation_fee` DECIMAL(10,2) NOT NULL,
  `profile_image` VARCHAR(255),
  `bio` TEXT,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Doctor Availability (Weekly Schedule)
CREATE TABLE `doctor_availability` (
  `availability_id` INT PRIMARY KEY AUTO_INCREMENT,
  `doctor_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `max_appointments` INT DEFAULT 20,
  `is_active` BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Appointments Table
CREATE TABLE `appointments` (
  `appointment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `status` ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `cancellation_reason` TEXT,
  `notes` TEXT,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Medical History
CREATE TABLE `medical_history` (
  `history_id` INT PRIMARY KEY AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `diagnosis` TEXT NOT NULL,
  `symptoms` TEXT,
  `prescribed_medications` TEXT,
  `visit_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Prescriptions
CREATE TABLE `prescriptions` (
  `prescription_id` INT PRIMARY KEY AUTO_INCREMENT,
  `appointment_id` INT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `description` TEXT,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Notifications
CREATE TABLE `notifications` (
  `notification_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'general',
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Appointment Queue
CREATE TABLE `appointment_queue` (
  `queue_id` INT PRIMARY KEY AUTO_INCREMENT,
  `appointment_id` INT NOT NULL UNIQUE,
  `queue_number` INT NOT NULL,
  `status` ENUM('Waiting', 'In Consultation', 'Completed', 'Skipped') DEFAULT 'Waiting',
  `check_in_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INDEXES for Performance
CREATE INDEX idx_doctor_specialization ON doctors(specialization);
CREATE INDEX idx_appt_date ON appointments(appointment_date);
CREATE INDEX idx_appt_status ON appointments(status);
CREATE INDEX idx_avail_day ON doctor_availability(day_of_week);

-- --------------------------------------------------------
-- SAMPLE DATA (Passwords are 'password123')
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- --------------------------------------------------------

-- Insert Admin/Doctor/Patient Users
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('dr_smith', 'smith@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('patient_doe', 'john@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient');

-- Insert Doctor Profile
INSERT INTO `doctors` (`user_id`, `full_name`, `specialization`, `qualification`, `experience_years`, `consultation_fee`) 
VALUES (2, 'Dr. John Smith', 'Cardiology', 'MBBS, MD', 12, 100.00);

-- Insert Patient Profile
INSERT INTO `patients` (`user_id`, `full_name`, `dob`, `gender`, `phone`, `blood_group`) 
VALUES (3, 'John Doe', '1990-05-15', 'Male', '1234567890', 'O+');

-- Insert Doctor Availability (Mon-Fri 9-5)
INSERT INTO `doctor_availability` (`doctor_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 'Monday', '09:00:00', '17:00:00'),
(1, 'Tuesday', '09:00:00', '17:00:00'),
(1, 'Wednesday', '09:00:00', '17:00:00');