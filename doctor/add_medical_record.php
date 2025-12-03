<?php
require_once '../includes/doctor_header.php';
require_once '../includes/file_upload_handler.php'; // Required for file upload

if (!isset($_GET['appt_id'])) redirect('doctor/dashboard.php');
$appt_id = $_GET['appt_id'];

// 1. Fetch Appointment & Patient Data (Verify ownership)
$stmt = $db->prepare("
    SELECT a.*, p.full_name, p.dob, p.blood_group, p.gender, p.patient_id 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    WHERE a.appointment_id = ? AND a.doctor_id = ?
");
$stmt->execute([$appt_id, $doctor_id]);
$appt = $stmt->fetch();

if (!$appt) die("Invalid Appointment ID or Access Denied");

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $diagnosis = clean_input($_POST['diagnosis']);
    $symptoms = clean_input($_POST['symptoms']);
    $medications = clean_input($_POST['medications']); 
    $notes = clean_input($_POST['notes']);

    try {
        $db->beginTransaction();

        // A. Insert into Medical History
        $sql = "INSERT INTO medical_history (patient_id, doctor_id, diagnosis, symptoms, prescribed_medications, visit_date, notes) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        $ins = $db->prepare($sql);
        $ins->execute([$appt['patient_id'], $doctor_id, $diagnosis, $symptoms, $medications, $notes]);

        // B. Handle Prescription File Upload (Optional but Recommended)
        if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
            
            // Use the FileUploadHandler helper class
            $uploadResult = FileUploadHandler::upload($_FILES['prescription_file'], 'prescriptions');
            
            if ($uploadResult['success']) {
                $sql_pres = "INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, file_path, description) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmt_pres = $db->prepare($sql_pres);
                $stmt_pres->execute([
                    $appt_id, 
                    $appt['patient_id'], 
                    $doctor_id, 
                    $uploadResult['path'], 
                    "Prescription for visit on " . date('Y-m-d')
                ]);
            } else {
                throw new Exception("File Upload Failed: " . $uploadResult['message']);
            }
        }

        // C. Mark Appointment as Completed
        $upd = $db->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
        $upd->execute([$appt_id]);

        $db->commit();
        set_flash_message("Consultation completed & records saved successfully.", "success");
        redirect('doctor/dashboard.php');

    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row">
        <!-- Patient Info Sidebar -->
        <div class="col-md-4">
            <div class="card shadow mb-3">
                <div class="card-header bg-primary text-white">
                    Patient Details
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($appt['full_name']); ?></h4>
                    <p><strong>Age:</strong> <?php echo date_diff(date_create($appt['dob']), date_create('today'))->y; ?> Years</p>
                    <p><strong>Gender:</strong> <?php echo $appt['gender']; ?></p>
                    <p><strong>Blood Group:</strong> <?php echo $appt['blood_group']; ?></p>
                    <hr>
                    <p><strong>Appointment Reason:</strong><br> <?php echo htmlspecialchars($appt['notes']); ?></p>
                </div>
            </div>
        </div>

        <!-- Consultation Form -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    Consultation & Diagnosis
                </div>
                <div class="card-body">
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <!-- ADDED: enctype for file upload -->
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label">Symptoms Observed</label>
                            <textarea name="symptoms" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <input type="text" name="diagnosis" class="form-control" required placeholder="e.g. Viral Fever, Migraine">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prescribed Medications (Text)</label>
                            <textarea name="medications" class="form-control" rows="3" placeholder="Drug Name - Dosage - Duration"></textarea>
                        </div>

                        <!-- NEW: File Upload Field -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-primary">Upload Digital Prescription (PDF/Image)</label>
                            <input type="file" name="prescription_file" class="form-control">
                            <div class="form-text">Optional. Max 5MB.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes / Advice</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success btn-lg">Complete Consultation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>