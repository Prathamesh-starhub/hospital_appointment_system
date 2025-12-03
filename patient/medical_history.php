<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireRole('patient');
$user_id = Auth::user();
$db = Database::getInstance()->getConnection();

// Get Patient ID
$stmt = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

// Fetch History
$sql = "SELECT mh.*, d.full_name as doc_name, d.specialization 
        FROM medical_history mh 
        JOIN doctors d ON mh.doctor_id = d.doctor_id 
        WHERE mh.patient_id = ? 
        ORDER BY mh.visit_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$patient['patient_id']]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">Medical History</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <?php if(count($history) > 0): ?>
            <?php foreach($history as $record): ?>
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm border-start border-4 border-primary">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 text-primary">
                                    <i class="fas fa-user-md me-2"></i>Dr. <?php echo htmlspecialchars($record['doc_name']); ?>
                                </h5>
                                <small class="text-muted"><?php echo htmlspecialchars($record['specialization']); ?></small>
                            </div>
                            <div class="text-end">
                                <strong><?php echo date('d M Y', strtotime($record['visit_date'])); ?></strong>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6 class="fw-bold text-danger">Diagnosis:</h6>
                                    <p><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="fw-bold">Symptoms:</h6>
                                    <p><?php echo htmlspecialchars($record['symptoms']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="fw-bold text-success">Prescription:</h6>
                                    <p style="white-space: pre-line;"><?php echo htmlspecialchars($record['prescribed_medications']); ?></p>
                                </div>
                            </div>
                            <?php if(!empty($record['notes'])): ?>
                                <div class="alert alert-secondary mt-2 mb-0">
                                    <strong>Doctor's Notes:</strong> <?php echo htmlspecialchars($record['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-5">
                <div class="text-muted">
                    <i class="fas fa-file-medical fa-4x mb-3"></i>
                    <h4>No Medical Records Found</h4>
                    <p>Consultation records will appear here after you visit a doctor.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>