<?php
require_once '../includes/doctor_header.php';
require_once '../includes/file_upload_handler.php';
require_once '../includes/notification_handler.php';

// 1. Verify Appointment Context
if (!isset($_GET['appt_id'])) {
    set_flash_message("No appointment selected.", "danger");
    redirect('doctor/dashboard.php');
}

$appt_id = $_GET['appt_id'];

// 2. Fetch Appointment & Patient Details (Security Check)
$stmt = $db->prepare("
    SELECT a.*, p.full_name, p.user_id as patient_user_id
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    WHERE a.appointment_id = ? AND a.doctor_id = ?
");
$stmt->execute([$appt_id, $doctor_id]);
$appt = $stmt->fetch();

if (!$appt) {
    die("Access Denied: Invalid Appointment or You are not the assigned doctor.");
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = clean_input($_POST['description']);
    
    // Check if file is selected
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
        
        // A. Use the FileUploadHandler
        $uploadResult = FileUploadHandler::upload($_FILES['prescription_file'], 'prescriptions');
        
        if ($uploadResult['success']) {
            try {
                $db->beginTransaction();

                // B. Insert Record into Database
                $sql = "INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, file_path, description) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $appt_id, 
                    $appt['patient_id'], 
                    $doctor_id, 
                    $uploadResult['path'], 
                    $description
                ]);

                // C. Send Notification to Patient
                $msg = "Dr. " . $doctor['full_name'] . " has uploaded a prescription for your visit on " . $appt['appointment_date'];
                NotificationHandler::create($appt['patient_user_id'], $msg, 'success');

                $db->commit();

                set_flash_message("Prescription uploaded successfully!", "success");
                redirect('doctor/view_appointments.php'); // Or back to dashboard

            } catch (Exception $e) {
                $db->rollBack();
                $error = "Database Error: " . $e->getMessage();
            }
        } else {
            $error = $uploadResult['message']; // Error from FileUploadHandler (e.g., "File too large")
        }
    } else {
        $error = "Please select a valid file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Prescription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Upload Prescription</h4>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info">
                        <strong>Patient:</strong> <?php echo htmlspecialchars($appt['full_name']); ?><br>
                        <strong>Date:</strong> <?php echo $appt['appointment_date']; ?>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label">Prescription File (PDF, JPG, PNG)</label>
                            <input type="file" name="prescription_file" class="form-control" required>
                            <div class="form-text">Max size: 5MB. Ensure the image is clear.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes / Instructions</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="e.g. Take 2 tablets after dinner..."></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-success">Upload & Notify Patient</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>