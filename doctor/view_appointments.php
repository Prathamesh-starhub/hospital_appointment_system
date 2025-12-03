<?php
require_once '../includes/doctor_header.php'; // Auth check & $doctor_id retrieval

// 1. Filter Logic
$status_filter = $_GET['status'] ?? 'all';

// Base Query: Strictly filtered by 'doctor_id'
// This guarantees data isolation between doctors
$sql = "SELECT a.*, p.full_name, p.phone, p.gender 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        WHERE a.doctor_id = ?";
$params = [$doctor_id];

// Apply Status Filter if not 'all'
if ($status_filter != 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// 2. Handle Actions (Accept/Reject)
if (isset($_POST['action_type']) && isset($_POST['appt_id'])) {
    $appt_id = $_POST['appt_id'];
    $action = $_POST['action_type']; // 'confirm' or 'cancel'
    
    $new_status = ($action === 'confirm') ? 'Confirmed' : 'Cancelled';
    
    // Security check: ensure appointment belongs to this doctor
    $upd = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
    $upd->execute([$new_status, $appt_id, $doctor_id]);
    
    set_flash_message("Appointment marked as $new_status.");
    header("Location: view_appointments.php?status=$status_filter");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        :root {
            --card-radius: 0.6rem;
        }

        body { background: #f8f9fa; }

        .page-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }

        /* Filters area */
        .filters-desktop { display: inline-flex; gap: .35rem; }
        .filters-mobile { display: none; }

        /* Table stacking on very small screens */
        @media (max-width: 575.98px) {
            .filters-desktop { display: none; }
            .filters-mobile { display: block; width: 100%; }

            .table thead { display: none; }
            .table tbody tr {
                display: block;
                margin-bottom: .75rem;
                border: 1px solid #e9ecef;
                border-radius: .5rem;
                padding: .5rem;
                background: #fff;
            }
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .35rem .5rem;
                border: none;
            }
            .table tbody td[data-label]::before {
                content: attr(data-label) ": ";
                font-weight: 600;
                margin-right: .35rem;
                white-space: nowrap;
            }
            .table tbody td .btn { white-space: nowrap; padding: .35rem .5rem; font-size: .85rem; }

            /* Make actions column stack nicely */
            .action-stack { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
        }

        /* Make the table more compact on small devices but readable */
        @media (max-width: 767.98px) {
            .table td, .table th { padding: .55rem .6rem; font-size: .94rem; }
            .btn { font-size: .9rem; padding: .45rem .6rem; }
        }

        /* Desktop/tablet: give action buttons some breathing room */
        .btn-group .btn { min-width: 86px; }

        .card { border-radius: var(--card-radius); }
        .card-body { padding: .8rem; }
        .card-header { padding: .7rem 1rem; }

        /* Small visual polish */
        .badge { font-size: .82rem; }
        .no-results { padding: 1.6rem; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Appointment Management</h3>
            <p class="text-muted mb-0">Department: <strong><?php echo htmlspecialchars($doctor['specialization']); ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Filter Buttons -->
    <div class="btn-group mb-3">
        <a href="?status=all" class="btn btn-outline-primary <?php echo $status_filter=='all'?'active':''; ?>">All</a>
        <a href="?status=Pending" class="btn btn-outline-warning <?php echo $status_filter=='Pending'?'active':''; ?>">Pending</a>
        <a href="?status=Confirmed" class="btn btn-outline-success <?php echo $status_filter=='Confirmed'?'active':''; ?>">Confirmed</a>
        <a href="?status=Completed" class="btn btn-outline-secondary <?php echo $status_filter=='Completed'?'active':''; ?>">Completed</a>
    </div>

    <?php display_flash_message(); ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo $apt['appointment_date']; ?></td>
                                <td><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($apt['full_name']); ?><br>
                                    <small class="text-muted"><?php echo $apt['phone']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($apt['status']) {
                                            'Confirmed' => 'primary',
                                            'Pending' => 'warning',
                                            'Completed' => 'success',
                                            'Cancelled' => 'danger',
                                            default => 'secondary'
                                        }; 
                                    ?>"><?php echo $apt['status']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($apt['notes']); ?></td>
                                
                                <!-- ACTIONS COLUMN -->
                                <td>
                                    <?php if ($apt['status'] == 'Pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="appt_id" value="<?php echo $apt['appointment_id']; ?>">
                                            <input type="hidden" name="action_type" value="confirm">
                                            <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="appt_id" value="<?php echo $apt['appointment_id']; ?>">
                                            <input type="hidden" name="action_type" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </form>

                                    <?php elseif ($apt['status'] == 'Confirmed'): ?>
                                        <a href="add_medical_record.php?appt_id=<?php echo $apt['appointment_id']; ?>" 
                                           class="btn btn-sm btn-primary">Start Consult</a>

                                    <?php elseif ($apt['status'] == 'Completed'): ?>
                                        <a href="upload_prescription.php?appt_id=<?php echo $apt['appointment_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                           <i class="fas fa-upload"></i> Upload Rx
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>