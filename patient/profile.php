<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 1. Security Check
Auth::requireRole('patient');
$user_id = Auth::user();
$db = Database::getInstance()->getConnection();

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $phone = clean_input($_POST['phone']);
    $gender = clean_input($_POST['gender']);
    $dob = clean_input($_POST['dob']);
    $blood_group = clean_input($_POST['blood_group']);
    $address = clean_input($_POST['address']);
    $emergency = clean_input($_POST['emergency_contact']);

    try {
        // Check if profile exists
        $check = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $check->execute([$user_id]);
        
        if ($check->rowCount() > 0) {
            // Update existing
            $sql = "UPDATE patients SET full_name=?, phone=?, gender=?, dob=?, blood_group=?, address=?, emergency_contact=? WHERE user_id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$full_name, $phone, $gender, $dob, $blood_group, $address, $emergency, $user_id]);
        } else {
            // Insert new (Rare case if registration failed halfway)
            $sql = "INSERT INTO patients (user_id, full_name, phone, gender, dob, blood_group, address, emergency_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id, $full_name, $phone, $gender, $dob, $blood_group, $address, $emergency]);
        }
        set_flash_message("Profile updated successfully!");
        header("Location: profile.php"); // Post-Redirect-Get pattern
        exit();
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// 3. Fetch Current Data
$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$p = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
        /* Slight container padding on small screens for better touch targets */
        .container { padding-left: .75rem; padding-right: .75rem; }

        /* Navbar styling and responsive collapse */
        .navbar-brand { font-weight: 600; font-size: 1rem; }
        .navbar .btn { padding: .35rem .55rem; font-size: .9rem; }

        /* Card and header tweaks */
        .card { border-radius: .6rem; overflow: hidden; }
        .card-header h4 { margin: 0; font-size: 1.05rem; }

        /* Form spacing adjustments */
        .mb-3 { margin-bottom: .9rem !important; }
        label.form-label { font-size: .95rem; }

        /* Make two-column rows stack on very small screens and stay two columns on md+ */
        .col-md-6 { padding-left: .5rem; padding-right: .5rem; }
        @media (max-width: 575.98px) {
            .col-md-6 { width: 100%; display: block; padding-left: 0; padding-right: 0; }
            .row.mb-3 { margin-left: 0; margin-right: 0; }
            .card-body { padding: .85rem; }
            /* Make inputs larger for touch */
            .form-control, .form-select, textarea {
                padding: .6rem .65rem;
                font-size: 1rem;
            }
            /* Make Save button full width and prominent on mobile */
            .d-grid .btn {
                width: 100%;
                padding: .7rem .75rem;
                font-size: 1rem;
            }
            /* Navbar collapse: show button to access Back to Dashboard */
            .navbar .collapse { margin-top: .5rem; }
        }

        /* Medium screens adjustments */
        @media (min-width: 576px) and (max-width: 991.98px) {
            .card-body { padding: 1rem; }
            .form-control, .form-select, textarea { padding: .55rem .6rem; }
        }

        /* Improve alert sizing */
        .alert { padding: .6rem .75rem; font-size: .95rem; }

        /* Make the icon + title fit better on small screens */
        .fas.fa-user-circle { font-size: 1.15rem; vertical-align: -2px; margin-right: .45rem; }

        /* Reduce visual noise for disabled fields on mobile */
        .form-control:disabled, .form-select:disabled {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar Placeholder -->
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">Patient Portal</span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($p['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($p['phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($p['dob'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo ($p['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($p['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($p['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select</option>
                                    <?php 
                                    $bgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                    foreach($bgs as $bg) {
                                        $selected = ($p['blood_group'] ?? '') == $bg ? 'selected' : '';
                                        echo "<option value='$bg' $selected>$bg</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($p['emergency_contact'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($p['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>