<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 1. Authenticate and authorize
Auth::requireRole('patient');
$user_id = Auth::user();

// 2. Fetch Patient Details
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$patient = $stmt->fetch();

// Redirect to profile if patient details don't exist yet
if (!$patient) {
    set_flash_message("Please complete your profile to continue.", "warning");
    redirect('patient/profile.php');
}

// 3. Fetch Stats
$stats = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0
];

// Get appointment counts
$stmt = $db->prepare("
    SELECT status, COUNT(*) as count 
    FROM appointments 
    WHERE patient_id = :pid 
    GROUP BY status
");
$stmt->execute([':pid' => $patient['patient_id']]);
while ($row = $stmt->fetch()) {
    $status = strtolower($row['status']);
    if (isset($stats[$status])) {
        $stats[$status] = $row['count'];
    }
}

// 4. Fetch Upcoming Appointments (Limit 5)
$stmt = $db->prepare("
    SELECT a.*, d.full_name as doctor_name, d.specialization 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.doctor_id 
    WHERE a.patient_id = :pid AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 5
");
$stmt->execute([':pid' => $patient['patient_id']]);
$upcoming = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
      <style>
        /* Make top action buttons stack on xs and align horizontally on sm+ */
        .actions-row {
            gap: 0.5rem;
        }
        @media (max-width: 575.98px) {
            .actions-row .btn {
                width: 100%;
            }
        }

        /* Stats cards: ensure readable sizing on small screens */
        .stat-number {
            font-size: 2.25rem;
            line-height: 1;
        }
        @media (max-width: 575.98px) {
            .stat-number { font-size: 1.75rem; }
            .card .card-body { padding: .75rem; }
        }

        /* Responsive table -> stacked card view on very small screens */
        @media (max-width: 575.98px) {
            .table-responsive { padding: 0; margin: 0; }
            .table thead { display: none; }
            .table tbody tr {
                display: block;
                border: 1px solid #e9ecef;
                border-radius: .5rem;
                margin-bottom: .75rem;
                padding: .5rem;
            }
            .table tbody td {
                display: flex;
                justify-content: space-between;
                padding: .35rem .5rem;
                align-items: center;
                border: none;
            }
            .table tbody td .text-muted { display: block; }
            /* show label before each td using data-label attribute */
            .table tbody td[data-label]::before {
                content: attr(data-label) ": ";
                font-weight: 600;
                margin-right: .35rem;
                white-space: nowrap;
            }
            .table tbody td .btn {
                white-space: nowrap;
                padding: .35rem .5rem;
            }
        }

        /* Minor visual improvements */
        .card-header.bg-white h5 { margin: 0; font-size: 1.05rem; }
        .navbar-brand { font-weight: 600; }
        .navbar-text { font-size: .95rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">MediCare Patient</a>
        <div class="d-flex">
            <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($patient['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php display_flash_message(); ?>

    <div class="row mb-4">
        <div class="col-md-12 text-end">
            <a href="book_appointment.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Book Appointment</a>
            <a href="profile.php" class="btn btn-info text-white"><i class="fas fa-user-edit"></i> Edit Profile</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <p class="card-text display-4"><?php echo $stats['pending']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Confirmed</h5>
                    <p class="card-text display-4"><?php echo $stats['confirmed']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Completed Visits</h5>
                    <p class="card-text display-4"><?php echo $stats['completed']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Upcoming Appointments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($upcoming) > 0): ?>
                            <?php foreach ($upcoming as $apt): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['specialization']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] == 'Confirmed' ? 'primary' : 
                                                ($apt['status'] == 'Pending' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo $apt['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_appointments.php?id=<?php echo $apt['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No upcoming appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>