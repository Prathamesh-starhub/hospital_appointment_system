USE hospital_appointment_system;

-- 1. Create Medical History Table (If not exists)
CREATE TABLE IF NOT EXISTS `medical_history` (
  `history_id` INT PRIMARY KEY AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `doctor_id` INT, -- Removed NOT NULL to allow SET NULL on delete
  `diagnosis` TEXT NOT NULL,
  `symptoms` TEXT,
  `prescribed_medications` TEXT,
  `visit_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create Prescriptions Table (Just in case it's also missing)
CREATE TABLE IF NOT EXISTS `prescriptions` (
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