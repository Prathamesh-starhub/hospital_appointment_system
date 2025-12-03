<?php
require_once '../includes/doctor_header.php';
require_once '../includes/queue_handler.php';

// Handle Queue Actions
if (isset($_POST['action']) && isset($_POST['appt_id'])) {
    $appt_id = $_POST['appt_id'];
    $action = $_POST['action'];

    if ($action == 'next') {
        // 1. Mark current 'In Consultation' as 'Completed'
        $db->prepare("UPDATE appointment_queue SET status = 'Completed' WHERE status = 'In Consultation' AND appointment_id IN (SELECT appointment_id FROM appointments WHERE doctor_id = ?)")->execute([$doctor_id]);
        
        // 2. Mark this specific patient as 'In Consultation'
        $db->prepare("UPDATE appointment_queue SET status = 'In Consultation' WHERE appointment_id = ?")->execute([$appt_id]);
        
        set_flash_message("Called next patient.");
    } 
    elseif ($action == 'complete') {
        // Mark as Completed
        $db->prepare("UPDATE appointment_queue SET status = 'Completed' WHERE appointment_id = ?")->execute([$appt_id]);
        
        // Also update main appointment status
        $db->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?")->execute([$appt_id]);
        
        set_flash_message("Consultation completed.");
    }
    elseif ($action == 'skip') {
        $db->prepare("UPDATE appointment_queue SET status = 'Skipped' WHERE appointment_id = ?")->execute([$appt_id]);
        set_flash_message("Patient skipped.");
    }

    header("Location: queue_management.php");
    exit();
}

// Fetch Today's Queue
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT q.*, p.full_name, p.gender, a.appointment_time, a.status as appt_status
    FROM appointment_queue q
    JOIN appointments a ON q.appointment_id = a.appointment_id
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? 
    AND a.appointment_date = ?
    AND q.status != 'Completed'
    AND q.status != 'Skipped'
    ORDER BY q.queue_number ASC
");
$stmt->execute([$doctor_id, $today]);
$queue = $stmt->fetchAll();

// Separate Current vs Waiting
$current_patient = null;
$waiting_list = [];

foreach ($queue as $q) {
    if ($q['status'] == 'In Consultation') {
        $current_patient = $q;
    } else {
        $waiting_list[] = $q;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Queue Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="30"> <!-- Auto refresh every 30s -->
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-stopwatch me-2"></i>Live Queue Management</h3>
        <a href="dashboard.php" class="btn btn-secondary">Exit Queue</a>
    </div>
    
    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Current Patient Card (Big and Prominent) -->
        <div class="col-md-12 mb-4">
            <div class="card text-center shadow border-success border-3">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">CURRENTLY SERVING</h4>
                </div>
                <div class="card-body py-5">
                    <?php if ($current_patient): ?>
                        <h1 class="display-1 fw-bold text-success"><?php echo $current_patient['queue_number']; ?></h1>
                        <h3 class="mb-3"><?php echo htmlspecialchars($current_patient['full_name']); ?></h3>
                        <p class="text-muted">Check-in Time: <?php echo date('h:i A', strtotime($current_patient['check_in_time'])); ?></p>
                        
                        <div class="mt-4">
                            <a href="add_medical_record.php?appt_id=<?php echo $current_patient['appointment_id']; ?>" 
                               class="btn btn-primary btn-lg px-5 me-2">
                               <i class="fas fa-stethoscope me-2"></i> Open Consult Screen
                            </a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="appt_id" value="<?php echo $current_patient['appointment_id']; ?>">
                                <input type="hidden" name="action" value="complete">
                                <button class="btn btn-outline-success btn-lg">Finish</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <h3 class="text-muted">Waiting for next patient...</h3>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Waiting List -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Up Next in Queue</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Token</th>
                        <th>Patient Name</th>
                        <th>Gender</th>
                        <th>Appt Time</th>
                        <th>Wait Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($waiting_list) > 0): ?>
                        <?php foreach ($waiting_list as $p): ?>
                        <tr>
                            <td><span class="badge rounded-pill bg-dark fs-6"><?php echo $p['queue_number']; ?></span></td>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo $p['gender']; ?></td>
                            <td><?php echo date('h:i A', strtotime($p['appointment_time'])); ?></td>
                            <td>
                                <?php 
                                    // Simple wait calculation
                                    $wait = round((time() - strtotime($p['check_in_time'])) / 60);
                                    echo $wait . " mins";
                                ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="appt_id" value="<?php echo $p['appointment_id']; ?>">
                                    <input type="hidden" name="action" value="next">
                                    <button class="btn btn-sm btn-primary">Call In</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="appt_id" value="<?php echo $p['appointment_id']; ?>">
                                    <input type="hidden" name="action" value="skip">
                                    <button class="btn btn-sm btn-outline-danger">No Show</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Queue is empty.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>