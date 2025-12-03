<?php
require_once '../includes/doctor_header.php'; // This provides $doctor_id and $doctor array

// 1. Get Today's Stats
$today = date('Y-m-d');

// STRICT ISOLATION: Ensure we only count records for THIS doctor_id
// Count Today's Appointments
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'Cancelled'");
$stmt->execute([$doctor_id, $today]);
$today_count = $stmt->fetchColumn();

// Count Pending Requests
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Pending'");
$stmt->execute([$doctor_id]);
$pending_count = $stmt->fetchColumn();

// 2. Fetch Today's Schedule
// STRICT ISOLATION: WHERE a.doctor_id = ?
$stmt = $db->prepare("
    SELECT a.*, p.full_name as patient_name, p.gender, p.phone 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status != 'Cancelled'
    ORDER BY a.appointment_time ASC
");
$stmt->execute([$doctor_id, $today]);
$todays_appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
     <style>
        :root {
            --card-radius: 0.6rem;
        }

        body { background: #f8f9fa; }

        /* Navbar actions collapse on small screens */
        .navbar .actions {
            gap: .5rem;
        }

        /* Stats card adjustments for mobile */
        .stat-number {
            font-size: 2.25rem;
            line-height: 1;
        }
        @media (max-width: 575.98px) {
            .stat-number { font-size: 1.6rem; }
            .card .card-body { padding: .75rem; }
            .card-title { font-size: .95rem; }
        }

        /* Table -> stacked card view for very small screens */
        @media (max-width: 575.98px) {
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
            .table tbody td .badge { margin-left: .5rem; }
            .table tbody td .btn { white-space: nowrap; padding: .35rem .5rem; font-size: .85rem; }
        }

        /* Make action area buttons full-width on xs and inline on sm+ */
        .top-actions { gap: .5rem; }
        @media (max-width: 575.98px) {
            .top-actions { flex-direction: column; }
            .top-actions .btn { width: 100%; }
            .navbar-brand { font-size: 1rem; }
            .navbar .btn { font-size: .9rem; }
        }

        /* Minor visual polish */
        .card { border-radius: var(--card-radius); }
        .card-header h5 { margin: 0; font-size: 1rem; }
        .badge { font-size: .85rem; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <!-- Display Name AND Specialization for Context -->
        <a class="navbar-brand" href="#">
            Welcome!, <?php echo htmlspecialchars($doctor['full_name']); ?> 
            <small class="fs-6 opacity-75"> | <?php echo htmlspecialchars($doctor['specialization']); ?></small>
        </a>
        <div class="d-flex gap-2">
            <a href="manage_availability.php" class="btn btn-outline-light btn-sm">My Schedule</a>
            <a href="view_appointments.php" class="btn btn-outline-light btn-sm">All Appointments</a>
            <a href="../logout.php" class="btn btn-warning btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <?php display_flash_message(); ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Today's Appointments</h5>
                    <p class="display-4"><?php echo $today_count; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending Requests</h5>
                    <p class="display-4"><?php echo $pending_count; ?></p>
                    <a href="view_appointments.php?status=Pending" class="btn btn-light btn-sm">Review Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Table -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Schedule for Today (<?php echo date('M d, Y'); ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Gender</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($todays_appointments) > 0): ?>
                            <?php foreach ($todays_appointments as $appt): ?>
                                <tr>
                                    <td><strong><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                    <td><?php echo $appt['gender']; ?></td>
                                    <td><?php echo htmlspecialchars($appt['notes']); ?></td>
                                    <td><span class="badge bg-success"><?php echo $appt['status']; ?></span></td>
                                    <td>
                                        <?php if($appt['status'] == 'Confirmed'): ?>
                                            <a href="add_medical_record.php?appt_id=<?php echo $appt['appointment_id']; ?>" 
                                               class="btn btn-sm btn-primary">Consult & Complete</a>
                                        <?php else: ?>
                                            <span class="text-muted">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No appointments scheduled for today.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>