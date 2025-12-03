<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();
if (isset($_SESSION['user_id'])) redirect('patient/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $dob = clean_input($_POST['dob']);
    $gender = clean_input($_POST['gender']);

    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $db = Database::getInstance()->getConnection();
        
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Email address is already registered.");
            }

            // START TRANSACTION
            $db->beginTransaction();

            // 1. Insert into Users Table
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $username = explode('@', $email)[0] . rand(100, 999); // Auto-generate username
            
            $sql_user = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'patient')";
            $stmt = $db->prepare($sql_user);
            $stmt->execute([$username, $email, $hash]);
            
            $new_user_id = $db->lastInsertId();

            // 2. Insert into Patients Table
            $sql_patient = "INSERT INTO patients (user_id, full_name, dob, gender, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql_patient);
            $stmt->execute([$new_user_id, $full_name, $dob, $gender, $phone]);

            // COMMIT TRANSACTION
            $db->commit();

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Registration successful! Please login.'];
            redirect('login.php');

        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
        /* Mobile-first tweaks */
        body {
            background-color: #f8f9fa;
        }
        /* Ensure inputs don't zoom on iOS when focused (requires font-size 16px+) */
        input, select, textarea {
            font-size: 16px !important;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Create Patient Account</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account? Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>