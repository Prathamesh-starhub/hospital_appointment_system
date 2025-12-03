<?php
require_once '../includes/doctor_header.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        // Prepare UPSERT statement (Insert if new, Update if exists)
        $sql = "INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, max_appointments, is_active) 
                VALUES (:did, :day, :start, :end, :max, :active)
                ON DUPLICATE KEY UPDATE start_time=:start, end_time=:end, max_appointments=:max, is_active=:active";
        
        $stmt = $db->prepare($sql);

        foreach ($days as $day) {
            $isActive = isset($_POST['active'][$day]) ? 1 : 0;
            $start = $_POST['start'][$day];
            $end = $_POST['end'][$day];
            $max = $_POST['max'][$day];

            $stmt->execute([
                ':did' => $doctor_id,
                ':day' => $day,
                ':start' => $start,
                ':end' => $end,
                ':max' => $max,
                ':active' => $isActive
            ]);
        }
        
        $db->commit();
        set_flash_message("Schedule updated successfully.");
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error updating schedule: " . $e->getMessage();
    }
}

// Fetch Current Schedule
$schedule = [];
$stmt = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
while($row = $stmt->fetch()) {
    $schedule[$row['day_of_week']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
        :root {
            --card-radius: .5rem;
            --gap: .6rem;
        }

        body { background: #f8f9fa; }

        .card { border-radius: var(--card-radius); }

        /* Table styling tweaks for better mobile readability */
        .table thead th { white-space: nowrap; }
        .table td, .table th { vertical-align: middle; }

        /* Make inputs more touch friendly */
        .form-control, .form-check-input, .form-select, input[type="time"], input[type="number"] {
            min-height: 44px;
            padding: .5rem .6rem;
            font-size: .95rem;
            border-radius: .45rem;
        }

        /* Small screens: hide header row and present each row as a card-like panel */
        @media (max-width: 767.98px) {
            .table thead { display: none; }

            .table tbody tr {
                display: block;
                margin-bottom: var(--gap);
                border: 1px solid #e9ecef;
                border-radius: var(--card-radius);
                background: #fff;
                padding: .6rem;
                box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .35rem 0;
                border: none;
            }

            /* Show a label before each cell using data-label attribute */
            .table tbody td[data-label]::before {
                content: attr(data-label) ": ";
                font-weight: 600;
                margin-right: .5rem;
                white-space: nowrap;
            }

            /* Reduce visual clutter for switch control and number input */
            .form-check { margin: 0; }
            .form-check-label { font-weight: 600; }

            /* Make actions stack and full-width friendly */
            .actions-wrap { display: flex; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; }
            .actions-wrap .btn { flex: 1 1 auto; min-width: 120px; }
        }

        /* Very small phones: enlarge tap targets */
        @media (max-width: 479.98px) {
            .form-control, .form-select, input[type="time"], input[type="number"] {
                min-height: 52px;
                font-size: 1rem;
            }
            .actions-wrap .btn { padding: .6rem .5rem; font-size: .95rem; }
        }

        /* Desktop/tablet: keep table compact and inputs aligned */
        @media (min-width: 768px) {
            .table tbody tr td input[type="number"] { width: 90px; }
        }

        /* Buttons area */
        .btn-group-actions { display: flex; gap: .5rem; }
        @media (max-width: 767.98px) {
            .btn-group-actions { flex-direction: column; }
            .btn-group-actions .btn { width: 100%; }
        }

        /* Small polish */
        .card-header { padding: .75rem 1rem; }
        .card-body { padding: .8rem; }
        .table-responsive { overflow: visible; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Weekly Availability Configuration</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <?php display_flash_message(); ?>

                    <form method="POST">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Status</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Max Patients</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $day): 
                                    $curr = $schedule[$day] ?? null;
                                    $isActive = ($curr && $curr['is_active']) ? 'checked' : '';
                                    $start = $curr['start_time'] ?? '09:00';
                                    $end = $curr['end_time'] ?? '17:00';
                                    $max = $curr['max_appointments'] ?? 20;
                                ?>
                                <tr>
                                    <td><strong><?php echo $day; ?></strong></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="active[<?php echo $day; ?>]" <?php echo $isActive; ?>>
                                            <label class="form-check-label">Available</label>
                                        </div>
                                    </td>
                                    <td><input type="time" name="start[<?php echo $day; ?>]" value="<?php echo $start; ?>" class="form-control"></td>
                                    <td><input type="time" name="end[<?php echo $day; ?>]" value="<?php echo $end; ?>" class="form-control"></td>
                                    <td><input type="number" name="max[<?php echo $day; ?>]" value="<?php echo $max; ?>" class="form-control" style="width: 80px;"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-end">
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>