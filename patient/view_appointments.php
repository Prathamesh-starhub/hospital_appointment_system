<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireRole('patient');
$user_id = Auth::user();
$db = Database::getInstance()->getConnection();

// 1. Get Patient ID
$stmt = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

// 2. Handle Cancellation
if (isset($_POST['cancel_appt_id'])) {
    $appt_id = $_POST['cancel_appt_id'];
    $reason = clean_input($_POST['cancellation_reason']);
    
    // Validate: Can only cancel if date is in future AND status is not already cancelled
    $check = $db->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ? AND patient_id = ? AND status != 'Cancelled' AND appointment_date >= CURDATE()");
    $check->execute([$appt_id, $patient['patient_id']]);
    
    if ($check->rowCount() > 0) {
        $upd = $db->prepare("UPDATE appointments SET status = 'Cancelled', cancellation_reason = ? WHERE appointment_id = ?");
        $upd->execute([$reason, $appt_id]);
        set_flash_message("Appointment cancelled successfully.", "warning");
    } else {
        set_flash_message("Cannot cancel this appointment. It may be in the past or already completed.", "danger");
    }
    header("Location: view_appointments.php");
    exit();
}

// 3. Fetch Appointments
$filter = $_GET['filter'] ?? 'upcoming';
$sql = "SELECT a.*, d.full_name as doc_name, d.specialization, d.consultation_fee 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        WHERE a.patient_id = ?";

if ($filter == 'upcoming') {
    $sql .= " AND a.appointment_date >= CURDATE() AND a.status != 'Cancelled'";
} elseif ($filter == 'history') {
    $sql .= " AND (a.appointment_date < CURDATE() OR a.status = 'Cancelled' OR a.status = 'Completed')";
}
// 'all' implies no extra WHERE clause

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$patient['patient_id']]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        /* container padding for small screens */
        .container { padding-left: .75rem; padding-right: .75rem; }

        /* Navbar adjustments */
        .navbar-brand { font-weight: 600; }
        .navbar .btn { padding: .375rem .6rem; font-size: .9rem; }

        /* Responsive controls: stack on xs, inline on sm+ */
        .controls-row {
            gap: .5rem;
        }
        @media (max-width: 575.98px) {
            .controls-row { flex-direction: column; align-items: stretch; }
            .controls-row .btn { width: 100%; }
        }

        /* Compact table -> stacked card on very small screens */
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
                margin-right: .5rem;
                white-space: nowrap;
            }
            .table tbody td .text-muted { display: block; }
            .table tbody td .badge { margin-left: .5rem; }
            .table tbody td .btn { white-space: nowrap; padding: .35rem .5rem; font-size: .85rem; }
            .card-body { padding: .6rem; }
        }

        /* Make modal full width on extra small screens */
        @media (max-width: 575.98px) {
            .modal-dialog { max-width: 95%; margin: 0.75rem auto; }
            .modal-content { border-radius: .5rem; }
        }

        /* Small visual improvements */
        .card { border-radius: .6rem; }
        .badge { font-size: .85rem; }
        .table td, .table th { vertical-align: middle; }
        .no-results { padding: 2rem 1rem; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">My Appointments</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
    </div>
</nav>

<div class="container">
    <?php display_flash_message(); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group">
            <a href="?filter=upcoming" class="btn btn-outline-primary <?php echo $filter=='upcoming'?'active':''; ?>">Upcoming</a>
            <a href="?filter=history" class="btn btn-outline-secondary <?php echo $filter=='history'?'active':''; ?>">History</a>
            <a href="?filter=all" class="btn btn-outline-info <?php echo $filter=='all'?'active':''; ?>">All</a>
        </div>
        <a href="book_appointment.php" class="btn btn-success">+ Book New</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($appointments) > 0): ?>
                            <?php foreach($appointments as $apt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></strong><br>
                                    <span class="text-muted"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></span>
                                </td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($apt['doc_name']); ?><br>
                                    <small class="text-info"><?php echo htmlspecialchars($apt['specialization']); ?></small>
                                </td>
                                <td>$<?php echo $apt['consultation_fee']; ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($apt['status']) {
                                        'Confirmed' => 'primary',
                                        'Pending' => 'warning',
                                        'Completed' => 'success',
                                        'Cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $apt['status']; ?></span>
                                </td>
                                <td>
                                    <?php if(($apt['status'] == 'Pending' || $apt['status'] == 'Confirmed') && strtotime($apt['appointment_date']) >= strtotime(date('Y-m-d'))): ?>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $apt['appointment_id']; ?>">
                                            Cancel
                                        </button>

                                        <!-- Cancel Modal -->
                                        <div class="modal fade" id="cancelModal<?php echo $apt['appointment_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Cancel Appointment</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to cancel your appointment with Dr. <?php echo htmlspecialchars($apt['doc_name']); ?>?</p>
                                                            <input type="hidden" name="cancel_appt_id" value="<?php echo $apt['appointment_id']; ?>">
                                                            <label class="form-label">Reason for Cancellation:</label>
                                                            <textarea name="cancellation_reason" class="form-control" required></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4">No appointments found.</td></tr>
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