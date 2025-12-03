<?php
require_once '../includes/doctor_header.php';

if (!isset($_GET['patient_id'])) {
    redirect('doctor/view_appointments.php');
}

$patient_id = $_GET['patient_id'];

// 1. Fetch Patient Demographics
$stmt = $db->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) die("Patient not found");

// 2. Fetch Medical History
$stmt = $db->prepare("
    SELECT mh.*, d.full_name as doctor_name 
    FROM medical_history mh
    JOIN doctors d ON mh.doctor_id = d.doctor_id
    WHERE mh.patient_id = ?
    ORDER BY mh.visit_date DESC
");
$stmt->execute([$patient_id]);
$history = $stmt->fetchAll();

// 3. Fetch Prescriptions
$stmt = $db->prepare("
    SELECT p.*, d.full_name as doctor_name
    FROM prescriptions p
    JOIN doctors d ON p.doctor_id = d.doctor_id
    WHERE p.patient_id = ?
    ORDER BY p.upload_date DESC
");
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
       <style>
        :root {
            --card-radius: .6rem;
            --gutter: .9rem;
        }

        body { background: #f8f9fa; -webkit-font-smoothing: antialiased; }

        .container { padding-left: .75rem; padding-right: .75rem; }

        /* Demographics card adjustments */
        .demographics .fa-user-circle { font-size: 4.5rem; color: #6c757d; }
        .demographics h4 { margin-bottom: .25rem; font-size: 1.15rem; }
        .demographics .badge { font-size: .85rem; }

        /* Make the two-column demographics stack on small screens */
        @media (max-width: 767.98px) {
            .demographics .col-md-3, .demographics .col-md-9 {
                width: 100%;
                display: block;
                padding: .65rem 0;
            }
            .demographics .border-end { border-right: none !important; border-bottom: 1px solid #e9ecef; padding-bottom: .75rem; margin-bottom: .6rem; }
            .demographics .ps-4 { padding-left: 0 !important; }
            .demographics h4 { font-size: 1.05rem; text-align: center; }
            .demographics p.fw-bold { font-size: .98rem; text-align: center; }
            .demographics .fa-user-circle { font-size: 3.75rem; display:block; margin: 0 auto; }
        }

        /* Medical timeline: make items card-like and readable on phones */
        .list-group-item {
            border: none;
            border-radius: .5rem;
            margin-bottom: .6rem;
            padding: .75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            background: #fff;
        }
        .list-group-item .text-primary { font-size: 1rem; margin-bottom: .2rem; }

        @media (max-width: 575.98px) {
            .list-group-item { padding: .65rem; }
            .list-group-item p { font-size: .95rem; }
            .list-group-item .alert { margin-top: .5rem; padding: .5rem; font-size: .92rem; }
            .list-group-item .d-flex.w-100 { flex-direction: column; align-items: flex-start; gap: .35rem; }
            .list-group-item small.text-muted { order: 2; }
        }

        /* Documents column: full-width cards on small screens, stretch links */
        .documents .list-group-item {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            padding: .7rem 1rem;
            border-radius: .5rem;
            margin-bottom: .5rem;
        }
        .documents .list-group-item h6 { margin: 0; font-size: .98rem; }
        .documents .list-group-item small { color: #6c757d; }

        @media (max-width: 767.98px) {
            .documents { margin-top: var(--gutter); }
            .documents .list-group-item { padding: .65rem; }
            .documents .list-group-item a { width: 100%; display: block; }
        }

        /* Utility tweaks */
        .card { border-radius: var(--card-radius); }
        .card.shadow { box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
        .border-primary { border-left: .4rem solid rgba(13,110,253,0.12) !important; }
        .text-muted.small { font-size: .85rem; }
        .btn-secondary { min-width: 96px; }

        /* Improve spacing on very small screens */
        @media (max-width: 479.98px) {
            .container { padding-left: .5rem; padding-right: .5rem; }
            .demographics h4 { font-size: 1rem; }
            .btn-secondary { width: 100%; margin-top: .5rem; }
            .d-flex.justify-content-between { flex-direction: column; gap: .5rem; align-items: stretch; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Patient Record</h3>
        <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
    </div>

    <!-- Demographics Card -->
    <div class="card shadow mb-4 border-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 border-end text-center">
                    <i class="fas fa-user-circle fa-5x text-secondary mb-2"></i>
                    <h4><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                    <span class="badge bg-info text-dark"><?php echo $patient['gender']; ?></span>
                </div>
                <div class="col-md-9 ps-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Date of Birth</label>
                            <p class="fw-bold"><?php echo $patient['dob']; ?> (<?php echo date_diff(date_create($patient['dob']), date_create('today'))->y; ?> yrs)</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Blood Group</label>
                            <p class="fw-bold"><?php echo $patient['blood_group'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="fw-bold"><?php echo $patient['phone']; ?></p>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small">Address</label>
                            <p class="fw-bold"><?php echo htmlspecialchars($patient['address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Medical History Column -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Medical Timeline</h5>
                </div>
                <div class="card-body">
                    <?php if (count($history) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($history as $rec): ?>
                                <li class="list-group-item py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($rec['diagnosis']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($rec['visit_date'])); ?></small>
                                    </div>
                                    <p class="mb-1 text-muted small">Attended by Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></p>
                                    <p class="mb-1"><strong>Symptoms:</strong> <?php echo htmlspecialchars($rec['symptoms']); ?></p>
                                    <div class="alert alert-light border mt-2">
                                        <small><strong>Rx:</strong> <?php echo htmlspecialchars($rec['prescribed_medications']); ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">No medical history found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Prescriptions Column -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-file-prescription me-2"></i>Documents</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (count($prescriptions) > 0): ?>
                            <?php foreach ($prescriptions as $file): ?>
                                <a href="../uploads/<?php echo $file['file_path']; ?>" target="_blank" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Prescription</h6>
                                        <small><i class="fas fa-external-link-alt"></i></small>
                                    </div>
                                    <small class="text-muted">By Dr. <?php echo $file['doctor_name']; ?></small><br>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($file['upload_date'])); ?></small>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted small">No documents uploaded.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>