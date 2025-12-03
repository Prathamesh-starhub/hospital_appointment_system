<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'patient') redirect('patient/dashboard.php');
    if ($_SESSION['role'] == 'doctor') redirect('doctor/dashboard.php');
    if ($_SESSION['role'] == 'admin') redirect('admin/dashboard.php');
}

$error = '';
$debug_info = ''; // Variable to store debug messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    // DEBUGGING LINES (Remove these after fixing)
    $debug_info .= "<strong>Debug Info:</strong><br>";
    $debug_info .= "Input Email: [" . htmlspecialchars($email) . "]<br>";
    $debug_info .= "Input Password Length: " . strlen($password) . "<br>";

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT user_id, username, password_hash, role FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $debug_info .= "User Found in DB: Yes (ID: " . $user['user_id'] . ")<br>";
            $debug_info .= "Stored Hash: " . substr($user['password_hash'], 0, 20) . "...<br>";
            
            // Verify Password
            if (password_verify($password, $user['password_hash'])) {
                // SUCCESS
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :uid");
                $update->execute([':uid' => $user['user_id']]);

                if ($user['role'] == 'patient') redirect('patient/dashboard.php');
                elseif ($user['role'] == 'doctor') redirect('doctor/dashboard.php');
                elseif ($user['role'] == 'admin') redirect('admin/dashboard.php');
                exit; // Stop script execution after redirect
            } else {
                $error = "Invalid email or password (Password mismatch).";
                $debug_info .= "<span style='color:red'>Password Verify Failed!</span><br>";
            }
        } else {
            $error = "Invalid email or password (User not found).";
            $debug_info .= "<span style='color:red'>User Not Found in DB!</span><br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            /* Changed from height: 100vh to min-height to allow scrolling on small screens when keyboard opens */
            min-height: 100vh; 
            /* Add padding so the card doesn't touch the edges on mobile */
            padding: 20px;
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            padding: 2rem; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            background: white; 
        }
        /* Mobile Optimization: Prevent iOS zoom on inputs */
        @media (max-width: 768px) {
            input.form-control {
                font-size: 16px; 
            }
            .login-card {
                padding: 1.5rem; /* Slightly reduced padding on very small screens */
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <h3 class="text-center mb-4">Patient Login</h3>
    
    <!-- DEBUG OUTPUT AREA -->
    <?php if(!empty($debug_info)): ?>
        <div class="alert alert-info" style="font-size: 0.85rem; word-break: break-all;">
            <?php echo $debug_info; ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="mt-3 text-center">
        <a href="register_patient.php">Register as Patient</a>
    </div>
</div>

</body>
</html>
