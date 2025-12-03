<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 1. Security Check
Auth::requireRole('patient');
$user_id = Auth::user();
$db = Database::getInstance()->getConnection();

// 2. Get Patient ID (Required for filtering)
$stmt = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

if (!$patient) {
    // If patient profile doesn't exist, redirect to profile creation
    redirect('patient/profile.php');
}

// 3. Fetch Prescriptions
// Join with doctors table to get the doctor's name
$sql = "SELECT p.*, d.full_name as doctor_name, d.specialization 
        FROM prescriptions p 
        JOIN doctors d ON p.doctor_id = d.doctor_id 
        WHERE p.patient_id = ? 
        ORDER BY p.upload_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$patient['patient_id']]);
$prescriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .prescription-card { transition: transform 0.2s; border-left: 5px solid #0d6efd; }
        .prescription-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .file-icon { font-size: 2.5rem; color: #dc3545; } /* Red for PDF look */
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-dark bg-primary mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand"><i class="fas fa-file-prescription me-2"></i>My Prescriptions</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <?php if (count($prescriptions) > 0): ?>
                <h5 class="mb-3 text-muted">Found <?php echo count($prescriptions); ?> prescription(s)</h5>
                
                <div class="row">
                    <?php foreach ($prescriptions as $rx): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card prescription-card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <i class="fas fa-file-pdf file-icon"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-0">Dr. <?php echo htmlspecialchars($rx['doctor_name']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($rx['specialization']); ?></small>
                                        </div>
                                        <div class="ms-auto text-end">
                                            <small class="text-secondary d-block">Uploaded on</small>
                                            <strong><?php echo date('d M Y', strtotime($rx['upload_date'])); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text bg-light p-2 rounded small text-muted border">
                                        <strong>Notes:</strong><br>
                                        <?php echo !empty($rx['description']) ? htmlspecialchars($rx['description']) : 'No additional notes provided.'; ?>
                                    </p>

                                    <div class="d-grid mt-3">
                                        <a href="../uploads/<?php echo htmlspecialchars($rx['file_path']); ?>" 
                                           target="_blank" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i> View / Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-folder-open fa-4x text-muted opacity-50"></i>
                    </div>
                    <h4>No Prescriptions Yet</h4>
                    <p class="text-muted">Prescriptions uploaded by your doctor after consultation will appear here.</p>
                    <a href="dashboard.php" class="btn btn-secondary mt-2">Return Home</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>