<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireRole('patient');
$db = Database::getInstance()->getConnection();

// Get Patient ID
$stmt = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([Auth::user()]);
$patient = $stmt->fetch();

if (!$patient) redirect('patient/profile.php');

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = clean_input($_POST['reason']);

    // Server-side validation (Double check availability)
    $check = $db->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'");
    $check->execute([$doctor_id, $date, $time]);

    if ($check->rowCount() > 0) {
        $error = "Sorry, this slot was just booked. Please choose another.";
    } else {
        try {
            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
            $stmt = $db->prepare($sql);
            $stmt->execute([$patient['patient_id'], $doctor_id, $date, $time, $reason]);

            // Optional: Send Email Notification here using PHPMailer

            set_flash_message("Appointment requested successfully! Wait for confirmation.");
            redirect('patient/dashboard.php');
        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}

// Fetch Specializations for Dropdown
$specs = $db->query("SELECT DISTINCT specialization FROM doctors WHERE status='active'")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <style>
        /* Container padding for small screens */
        .container {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        /* Ensure card uses full width on small screens and not too wide on large screens */
        .card {
            border-radius: .6rem;
            overflow: hidden;
        }

        /* Header adjustments */
        .card-header h4 {
            font-size: 1.05rem;
            margin: 0;
        }

        /* Make action button full width on xs and comfortably big on mobile */
        @media (max-width: 575.98px) {
            .btn-lg {
                padding: .65rem .8rem;
                font-size: 1rem;
            }
            .d-grid .btn {
                width: 100%;
            }
            /* Inputs & selects slightly larger touch targets */
            .form-control,
            .form-select,
            textarea {
                padding: .6rem .65rem;
                font-size: 0.95rem;
            }
            label.form-label {
                font-size: 0.95rem;
            }
            /* Reduce horizontal margins inside card body on tiny screens */
            .card-body {
                padding: 0.9rem;
            }
            /* Bring alert padding down */
            .alert {
                margin-bottom: .6rem;
                padding: .6rem .7rem;
                font-size: .95rem;
            }
        }

        /* Medium screens (phones in landscape / small tablets) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .card-body { padding: 1rem; }
            .card-header { padding: .9rem 1rem; }
        }

        /* Improve spacing between form groups on all screens but compact on small */
        .mb-3 {
            margin-bottom: 0.9rem !important;
        }

        /* Make select placeholder clearly readable */
        .form-select option[disabled] { color: #6c757d; }

        /* Optional: visually denote disabled selects/inputs more clearly on mobile */
        .form-select:disabled,
        .form-control:disabled {
            background-color: #f8f9fa;
        }

        /* Date control: ensure icon and text fit on small screens */
        input[type="date"] {
            min-height: 44px;
        }

        /* Loading text smaller but readable */
        #loading-slots { font-size: .88rem; }

    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Book New Appointment</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm">
                        
                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <select id="specialization" class="form-select" required>
                                <option value="">Select Specialization</option>
                                <?php foreach($specs as $s) echo "<option value='$s'>$s</option>"; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Doctor</label>
                            <select name="doctor_id" id="doctor" class="form-select" disabled required>
                                <option value="">Select Doctor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preferred Date</label>
                            <input type="date" name="date" id="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" disabled required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Time Slots</label>
                            <select name="time" id="time" class="form-select" disabled required>
                                <option value="">Select Date First</option>
                            </select>
                            <div id="loading-slots" class="text-muted small mt-1" style="display:none;">Checking availability...</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Visit / Symptoms</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Confirm Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // 1. Load Doctors on Specialization Change
    $('#specialization').change(function() {
        let spec = $(this).val();
        $('#doctor').html('<option value="">Loading...</option>').prop('disabled', true);
        
        if (spec) {
            $.get('../api/get_doctors.php', { specialization: spec }, function(data) {
                let options = '<option value="">Select Doctor</option>';
                data.forEach(function(doc) {
                    options += `<option value="${doc.doctor_id}">${doc.full_name} (Fee: $${doc.consultation_fee})</option>`;
                });
                $('#doctor').html(options).prop('disabled', false);
            });
        }
    });

    // 2. Enable Date when Doctor is selected
    $('#doctor').change(function() {
        if($(this).val()) {
            $('#date').prop('disabled', false);
            $('#time').html('<option value="">Select Date First</option>').prop('disabled', true);
        } else {
            $('#date').prop('disabled', true);
        }
    });

    // 3. Load Slots on Date Change
    $('#date').change(function() {
        let date = $(this).val();
        let doctor_id = $('#doctor').val();
        
        if (date && doctor_id) {
            $('#loading-slots').show();
            $('#time').prop('disabled', true);

            $.get('../api/get_slots.php', { doctor_id: doctor_id, date: date }, function(data) {
                $('#loading-slots').hide();
                let options = '<option value="">Select Time Slot</option>';
                
                if (data.length === 0) {
                    options = '<option value="">No slots available / Doctor off</option>';
                } else {
                    data.forEach(function(slot) {
                        options += `<option value="${slot.value}">${slot.label}</option>`;
                    });
                    $('#time').prop('disabled', false);
                }
                $('#time').html(options);
            });
        }
    });
});
</script>

</body>
</html>